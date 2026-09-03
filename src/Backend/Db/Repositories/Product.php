<?php
/**
 * Pit o Cuixa — Product Repository
 *
 * Data access layer for the products table.
 * All methods use PDO prepared statements exclusively.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class Product
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Summary of sync
     * @param array $scrapedProducts
     * @return void
     */

    public function sync (array $scrapedProducts) : void{

        // Guard: an empty scrape must NEVER wipe the catalog. If the external
        // page changed, the network failed, or WebScraper returned [], the
        // scraped map built below stays empty and every existing product would
        // look "not in the map" — the deactivation loop would turn off the
        // whole visible menu on a single failed cron run. The previous
        // nested-loop code was accidentally safe on empty input (the loop
        // never ran), but the deactivation loop now runs AFTER the scrape
        // loop, so this guard is required. Empty scrape = no-op for activation
        // state; existing products are left untouched.
        if (empty($scrapedProducts)) {
            error_log('[Product::sync] Empty scrape received; skipping sync, existing products left untouched.');
            return;
        }

        //Productos de la DB.
        // Load the full catalog (not the default LIMIT 100 window) so a re-fill
        // on a catalog larger than 100 rows does not treat out-of-window products
        // as new and abort on the UNIQUE(products.slug) constraint.
        $dbProducts = $this->all(onlyActive : false, limit : 10000);

        // Repair rows whose price is still stored as TEXT by the old bug. The
        // change-detection path reads SERIALIZED rows (all() casts price to
        // float), which hides the stored column type — a TEXT row like
        // "11,00 €" compares float-equal to its normalized value (both 11.0)
        // and would never be flagged. This rewrites every TEXT price from its
        // own content as a proper REAL, so no corrupted rows survive re-sync.
        $this->repairTextPrices();

        //Mapa Slugs
        $catRepo = new Category();
        $catMap = $catRepo->MapSlug();

        
        $filterProducts = [];
        
        foreach($dbProducts as $p){

            //Convertimos el array en dict
            $filterProducts[$p["slug"]] = $p;
        }

        $scrapedMap = [];

        // Slugs claimed before the scrape loop: the whole DB catalog (with its
        // type) plus every scraped slug (all simple). The collision resolver
        // checks this in-memory set BEFORE the DB, so a renamed slug can never
        // collide with an existing row NOR with another item of the same batch,
        // regardless of processing order.
        $reservedSlugs = [];
        foreach ($filterProducts as $fp) {
            $reservedSlugs[$fp['slug']] = $fp['type'] ?? 'simple';
        }
        foreach ($scrapedProducts as $sp) {
            $spSlug = (string) ($sp['slug'] ?? '');
            if ($spSlug !== '' && !isset($reservedSlugs[$spSlug])) {
                $reservedSlugs[$spSlug] = 'simple';
            }
        }

        foreach($scrapedProducts as $p){
    
            // Defensive category lookup: unknown slugs fall back to `otros`;
            // if even that is missing, skip the product instead of aborting
            // the whole fill (NOT NULL constraint on products.category_id).
            $p['category_id'] = $catMap[$p['category'] ?? ''] ?? $catMap['otros'] ?? null;
            unset($p['category']);

            if ($p['category_id'] === null) {
                continue;
            }

            $slug = $p['slug'];

            //Admin-created menus (type='menu') are immune to the scrape: the
            //admin is the single editor for them, so a re-sync must neither
            //overwrite their fields nor reactivate them. Scraped products are
            //always type='simple' (WebScraper never sends 'type'; insert()
            //defaults to 'simple'), so this exemption can never shield scraped
            //items — it only protects rows the admin created as menus.
            //A slug collision between a scraped simple and an admin menu is NOT
            //a drop: the scraped item is renamed with the delivery suffix and
            //persisted under its own slug, so the menu keeps its slug (and its
            //immunity) while the scraped item keeps its place in the catalog.
            $existing = $filterProducts[$slug] ?? null;
            if ($existing !== null && ($existing['type'] ?? 'simple') === 'menu') {
                // REUSE the previously renamed row instead of creating a fresh
                // -N row on every re-sync. Night 1 inserts {base}-delivery;
                // night 2 must find it (same lineage: slug + last_shop_url) and
                // UPDATE only what changed via getChanges(), keeping the slug,
                // its public URL and its /img/pic/{slug}.webp image stable.
                // The last_shop_url check is the anti-clobber guard: a simple
                // row the admin created with the same slug but a different
                // source URL is never UPDATE-reused (it falls through to the
                // counter). Note it only protects against the update: a manual
                // simple row is still subject to the deactivation loop below
                // when it is not in scrapedMap.
                $renamedSlug = $slug . '-delivery';
                $prevRenamed = $filterProducts[$renamedSlug] ?? null;
                $prevSameLineage = $prevRenamed !== null
                    && ($prevRenamed['type'] ?? 'simple') === 'simple'
                    && ($prevRenamed['last_shop_url'] ?? '') === ($p['last_shop_url'] ?? '');

                if ($prevSameLineage) {
                    $p['slug'] = $renamedSlug;
                    $slug      = $renamedSlug;
                    $scrapedMap[$slug] = $p;

                    $changes = $this->getChanges($prevRenamed, $p);
                    if (!empty($changes)) {
                        $this->update($slug, $changes);
                    }
                    if (!$prevRenamed['is_active']) {
                        $this->setStatus($slug, true);
                    }
                    continue;
                }

                $resolved = $this->resolveSlugCollision($slug, 'simple', 0, $reservedSlugs);
                if ($resolved === null) {
                    // Unreachable in practice: a scraped simple vs an admin menu
                    // is always a cross-type collision. Kept defensive so a
                    // rename can never silently drop data.
                    error_log('[Product::sync] Slug "' . $slug . '" collides with a same-type row; scraped product skipped.');
                    continue;
                }
                $p['slug'] = $resolved;
                $slug      = $resolved;
                $scrapedMap[$slug] = $p;

                if (!isset($filterProducts[$slug])) {
                    $this->insert($p);
                } else {
                    // Defensive: the resolver guarantees a fresh slug, so this
                    // branch is unreachable today; kept mirroring the main loop.
                    $changes = $this->getChanges($filterProducts[$slug], $p);
                    if (!empty($changes)) {
                        $this->update($slug, $changes);
                    }
                    if (!$filterProducts[$slug]['is_active']) {
                        $this->setStatus($slug, true);
                    }
                }
                continue;
            }

            $scrapedMap[$slug] = $p;

            //Insertar Datos
            if(!isset($filterProducts[$slug])){

                $this->insert($p);
                continue;
            }

            //Actualizar Datos
            $changes = $this->getChanges($filterProducts[$slug], $p);

            if(!empty($changes)){
                $this->update($slug, $changes);
            }

            //Activar Producto
            if(!$filterProducts[$slug]['is_active']){
                $this->setStatus($slug, true);
            }
        }

        // Second guard: the input can be non-empty yet yield no usable products
        // (every slug dropped during cleanup because no category — not even the
        // `otros` fallback — resolved). An empty scraped map carries the same
        // wipe hazard as an empty scrape, so deactivation is skipped as well.
        if (empty($scrapedMap)) {
            error_log('[Product::sync] Scrape yielded no usable products; skipping deactivation, existing products left untouched.');
            return;
        }

        //Desactivar Producto
        // NOTE: must run AFTER the loop above — $scrapedMap is only complete once every
        // scraped product has been processed. Running this inside the loop deactivated
        // every product except the first-seen one on re-sync (stale partial map).
        // Admin-created menus (type='menu') are also skipped here: the admin is the
        // single editor for them, so a menu absent from the scrape is never turned off.
        foreach($filterProducts as $slug => $p){

            if(!isset($scrapedMap[$slug]) && $p['is_active'] && ($p['type'] ?? 'simple') !== 'menu'){
                $this->setStatus($slug, false);
            }
        }
    }
    /**
     * Summary of insert
     * @param array $product
     * @return void
     */

    /**
     * Insert a product row.
     *
     * Contract (asymmetric by design, must be documented):
     *   REQUIRED  — category_id, slug, name_es, price, last_shop_url.
     *               A caller that omits one of these gets a PHP error / NULL
     *               insert that fails the NOT NULL constraint: fail early,
     *               never publish a catalog row missing its primary language.
     *   OPTIONAL  — every other column falls back to a sane default:
     *               names/descriptions in other languages → '' (the translate
     *               API fills them later), description_es → '', image_url → NULL
     *               (a missing/broken scrape image must not abort the fill),
     *               sort_order → 0 (schema default), plus the existing
     *               is_active/is_featured/channel/source/type/menu_data
     *               fallbacks below.
     *
     * @param  array $product
     * @return void
     */
    public function insert(array $product): void{

        //Preparamos la insercion
        $stmt = $this->pdo->prepare(
            'INSERT INTO products(
            category_id, slug, name_es, name_en, name_uk, description_es, description_en, description_uk, price, image_url, last_shop_url, sort_order, is_active, is_featured, is_dine_in, is_delivery, source, type, menu_data)
            VALUES(
            :category_id, :slug, :name_es, :name_en, :name_uk, :description_es, :description_en, :description_uk, :price, :image_url, :last_shop_url, :sort_order, :is_active, :is_featured, :is_dine_in, :is_delivery, :source, :type, :menu_data)'
        );
        $stmt->execute([
            ':category_id' => $product['category_id'],
            ':slug' => $product['slug'],
            ':name_es' => $product['name_es'],
            ':name_en' => $product['name_en'] ?? '',
            ':name_uk' => $product['name_uk'] ?? '',
            ':description_es' => $product['description_es'] ?? '',
            ':description_en' => $product['description_en'] ?? '',
            ':description_uk' => $product['description_uk'] ?? '',
            // Normalize BEFORE binding: the scraper emits raw DOM text like
            // "19,50 €" and a naive (float) cast truncates it to 19.0, storing
            // corrupted prices in the REAL column (see getChanges() for the
            // re-sync repair path).
            ':price' => $this->normalizePrice($product['price'] ?? 0),
            // "No image" is stored as NULL, never '' — normalize BEFORE binding
            // so scraper/admin '' (or whitespace) converges to NULL.
            ':image_url' => self::normalizeImageUrl($product['image_url'] ?? null),
            ':last_shop_url' => $product['last_shop_url'],
            ':sort_order' => $product['sort_order'] ?? 0,
            // Active/featured now respect an explicit caller value and only
            // fall back when absent. `sync()` sends neither key, so scraped
            // products are unchanged (is_active=1, is_featured=0), while the
            // admin's explicit input is preserved instead of being overridden.
            ':is_active' => (int)(bool)($product['is_active'] ?? 1),
            ':is_featured' => (int)(bool)($product['is_featured'] ?? 0),
            // Channel defaults: scraped products are delivery-only. Explicit
            // values (from the scraper or admin) win; anything missing falls
            // back to delivery = 1, dine_in = 0.
            ':is_dine_in'  => (int) ($product['is_dine_in']  ?? 0),
            ':is_delivery' => (int) ($product['is_delivery'] ?? 1),
            // source/type/menu_data were previously left to the column defaults;
            // binding them explicitly keeps those same defaults for the scraper
            // (source='delivery', type='simple', menu_data=NULL) while letting
            // the admin persist its own values.
            ':source'     => $product['source'] ?? 'delivery',
            ':type'       => $product['type'] ?? 'simple',
            ':menu_data'  => $product['menu_data'] ?? null
        ]);

        return;
    }

    public function update(string $slug, array $changes) : void{

        if(empty($changes)){
            return;
        }

        $params = $this->buildUpdateParams($changes);
        $params[':slug'] = $slug;

        $stmt = $this->pdo->prepare(
            'UPDATE products SET ' . $this->buildUpdateFields($changes) . ' WHERE slug = :slug'
        );
        $stmt->execute($params);
    }

    /**
     * Overwrite a product by its primary key.
     *
     * The admin layer works by id (the URL path carries the product id), while
     * sync() keys on slug. This method routes the admin writes through the
     * repository so the products table has a single write path. Pass every
     * column as a change for a full overwrite; partial updates only touch the
     * provided columns.
     *
     * @param  int   $id       Primary key of the product to update
     * @param  array $changes  Column => value map to overwrite
     * @return void
     */
    public function updateById(int $id, array $changes) : void{

        if(empty($changes)){
            return;
        }

        // "No image" is stored as NULL, never '' — normalize here so every
        // updateById caller (admin API, CSV import) converges on NULL.
        if (array_key_exists('image_url', $changes)) {
            $changes['image_url'] = self::normalizeImageUrl($changes['image_url'] ?? null);
        }

        $params = $this->buildUpdateParams($changes);
        $params[':id'] = $id;

        $stmt = $this->pdo->prepare(
            'UPDATE products SET ' . $this->buildUpdateFields($changes) . ' WHERE id = :id'
        );
        $stmt->execute($params);
    }

    /**
     * Build the UPDATE SET clause (without the WHERE part) for a set of column
     * changes, always appending the updated_at timestamp.
     *
     * @param  array $changes
     * @return string
     */
    private function buildUpdateFields(array $changes): string
    {
        $fields = [];

        foreach($changes as $col => $val){
            $fields[] = "{$col} = :{$col}";
        }

        $fields[] = 'updated_at = datetime("now")';

        return implode(', ', $fields);
    }

    /**
     * Build the named-parameter map for a set of column changes.
     *
     * @param  array $changes
     * @return array<string, mixed>
     */
    private function buildUpdateParams(array $changes): array
    {
        $params = [];

        foreach($changes as $col => $val){
            $params[":{$col}"] = $val;
        }

        return $params;
    }

    /**
     * Summary of deactivate
     * @param string $slug
     * @return void
     */
    public function setStatus(string $slug, bool $active) : void{

        $stmt = $this->pdo->prepare('UPDATE products SET is_active = :active, updated_at = datetime("now") WHERE slug = :slug');
        $stmt->execute([
            ':slug' => $slug,
            ':active' => (int)$active
        ]);
    }

    /**
     * Set the active state of a product by its primary key.
     *
     * Counterpart of setStatus() keyed on id, used by the admin soft-delete
     * flow (DELETE /api/admin/products/{id}) which works by id, not slug.
     *
     * @param  int  $id
     * @param  bool $active
     * @return void
     */
    public function setStatusById(int $id, bool $active) : void{

        $stmt = $this->pdo->prepare('UPDATE products SET is_active = :active, updated_at = datetime("now") WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':active' => (int)$active
        ]);
    }

    /**
     * Resolve a slug collision for a product of the given type.
     *
     * The slug is the product's identity — and the base of its local image file
     * /img/pic/{slug}.webp — so two products can never share one (UNIQUE
     * constraint on products.slug). This method implements the cross-type
     * rename rule shared by the admin API (create/update) and sync():
     *
     *   - slug free                    → returned unchanged
     *   - slug taken by the SAME type  → returns null (the caller reports a
     *       validation error instead of letting the UNIQUE constraint crash)
     *   - slug taken by a DIFFERENT type → returned as "{slug}-local" for
     *       menus or "{slug}-delivery" for simple products; when that
     *       candidate is itself taken, a numeric counter is appended
     *       ("{slug}-local-2", "{slug}-local-3", ...) until a free slug is
     *       found.
     *
     * The database is the source of truth, so an admin request (no batch
     * context) resolves with $reservedSlugs = []. sync() passes the whole
     * catalog plus the slugs already claimed by the current batch, so a
     * renamed slug can never collide with an existing row nor with another
     * item of the same batch.
     *
     * @param  string                $slug          Proposed slug
     * @param  string                $type          Type of the product being stored ('menu'|'simple')
     * @param  int                   $excludeId     Row to ignore (the product itself on update)
     * @param  array<string, string> $reservedSlugs Slugs already claimed in this batch (slug => type)
     * @return string|null Final slug to persist, or null when the slug is taken by the same type
     */
    public function resolveSlugCollision(string $slug, string $type, int $excludeId = 0, array $reservedSlugs = []): ?string
    {
        $occupantType = $this->slugOccupantType($slug, $excludeId, $reservedSlugs);

        if ($occupantType === null) {
            return $slug;
        }

        if ($occupantType === $type) {
            return null;
        }

        // Cross-type collision: rename with the suffix of the incoming type.
        // Anything that is not an admin menu/carta behaves like a simple product.
        $suffix    = ($type === 'menu' || $type === 'carta') ? 'local' : 'delivery';
        $candidate = $slug . '-' . $suffix;

        for ($n = 2; $this->slugOccupantType($candidate, $excludeId, $reservedSlugs) !== null; $n++) {
            $candidate = $slug . '-' . $suffix . '-' . $n;
        }

        return $candidate;
    }

    /**
     * Type of the product occupying a slug, if any.
     *
     * Checks the caller-provided batch map first (sync() keeps the whole
     * catalog and the current batch in memory), then the database with a
     * prepared SELECT. $excludeId lets an update keep its own slug without
     * self-colliding.
     *
     * @param  string                $slug
     * @param  int                   $excludeId
     * @param  array<string, string> $reservedSlugs
     * @return string|null Type of the occupant, or null when the slug is free
     */
    private function slugOccupantType(string $slug, int $excludeId, array $reservedSlugs): ?string
    {
        if (isset($reservedSlugs[$slug])) {
            return (string) $reservedSlugs[$slug];
        }

        $stmt = $this->pdo->prepare(
            'SELECT type FROM products WHERE slug = :slug AND id != :excludeId LIMIT 1'
        );
        $stmt->execute([':slug' => $slug, ':excludeId' => $excludeId]);

        $type = $stmt->fetchColumn();

        return $type === false ? null : (string) $type;
    }

    /**
     * Return products, optionally filtered by category and/or channel.
     *
     * The fourth parameter is kept backward-compatible so existing calls can pass either
     * a boolean for the active-state filter or a channel string for channel filtering.
     *
     * @param  int|null $categoryId  Filter by category ID (null = all)
     * @param  int      $limit       Maximum rows to return (safety cap)
     * @param  int      $offset      Offset for pagination
     * @param  bool|string|null $onlyActive
     * @param  string|null $channel  Filter by channel ('dine_in' | 'delivery' | null)
     * @return array<int, array<string, mixed>>
     */
    public function all(?int $categoryId = null, int $limit = 100, int $offset = 0, bool|string|null $onlyActive = true, ?string $channel = null): array
    {
        if (is_string($onlyActive) && $onlyActive !== '') {
            $channel = $onlyActive;
            $onlyActive = true;
        }

        $sql = 'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en, c.name_uk AS category_name_uk
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE 1 = 1';

        if($onlyActive){
            $sql .= ' AND p.is_active = 1';
        }

        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        if ($channel === 'dine_in') {
            $sql .= ' AND p.is_dine_in = 1';
        } elseif ($channel === 'delivery') {
            $sql .= ' AND p.is_delivery = 1';
        }

        $sql .= ' ORDER BY c.sort_order, p.sort_order LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    /**
     * Count all active products, optionally filtered by category and channel.
     *
     * @param  int|null    $categoryId  Filter by category ID (null = all)
     * @param  string|null $channel     Filter by channel ('dine_in' | 'delivery' | null)
     * @return int
     */
    public function count(?int $categoryId = null, ?string $channel = null): int
    {
        $sql = 'SELECT COUNT(*) FROM products p WHERE p.is_active = 1';
        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        if ($channel === 'dine_in') {
            $sql .= ' AND p.is_dine_in = 1';
        } elseif ($channel === 'delivery') {
            $sql .= ' AND p.is_delivery = 1';
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a single active product by its slug.
     *
     * @param  string $slug  URL-safe product identifier
     * @return array<string, mixed>|null
     */
    public function bySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en, c.name_uk AS category_name_uk
             FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE p.slug = :slug AND p.is_active = 1'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $this->serialize($row) : null;
    }

    /**
     * Alias: return all products in a given category.
     *
     * @param  int         $categoryId
     * @param  string|null $channel
     * @return array<int, array<string, mixed>>
     */
    public function byCategory(int $categoryId, ?string $channel = null): array
    {
        return $this->all($categoryId, 100, 0, true, $channel);
    }

    /**
     * Increment the click count for a product by 1.
     *
     * @param  int $productId
     * @return bool
     */
    public function incrementClick(int $productId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE products SET clicks_count = clicks_count + 1 WHERE id = :id AND is_active = 1');
        return $stmt->execute([':id' => $productId]);
    }

    /**
     * Get top N most clicked active products.
     * Falls back to featured products if total clicks are 0.
     *
     * @param  int $limit
     * @return array<int, array<string, mixed>>
     */
    public function popular(int $limit = 5): array
    {
        // CLEAN-7: the aggregate is read through a prepared statement, matching
        // repository hygiene (no raw interpolation of user/derived values).
        $totalClicksStmt = $this->pdo->prepare('SELECT SUM(clicks_count) FROM products WHERE is_active = 1');
        $totalClicksStmt->execute();
        $totalClicks = (int) $totalClicksStmt->fetchColumn();

        if ($totalClicks > 0) {
            $sql = 'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en, c.name_uk AS category_name_uk
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    WHERE p.is_active = 1
                    ORDER BY p.clicks_count DESC, p.sort_order ASC
                    LIMIT :limit';
        } else {
            $sql = 'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en, c.name_uk AS category_name_uk
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    WHERE p.is_active = 1
                    ORDER BY p.is_featured DESC, c.sort_order ASC, p.sort_order ASC
                    LIMIT :limit';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    /**
     * Normalise types for JSON-safe output.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function serialize(array $row): array
    {
        $row['id']           = (int) $row['id'];
        $row['category_id']  = (int) $row['category_id'];
        $row['price']        = (float) $row['price'];
        $row['sort_order']   = (int) $row['sort_order'];
        $row['clicks_count'] = isset($row['clicks_count']) ? (int) $row['clicks_count'] : 0;
        $row['is_active']    = (bool) $row['is_active'];
        $row['is_featured']  = (bool) $row['is_featured'];
        $row['is_dine_in']   = isset($row['is_dine_in']) ? (bool) $row['is_dine_in'] : true;
        $row['is_delivery']  = isset($row['is_delivery']) ? (bool) $row['is_delivery'] : true;
        $row['source']       = (string) ($row['source'] ?? 'delivery');
        $row['type']         = (string) ($row['type'] ?? 'simple');

        if (isset($row['menu_data']) && is_string($row['menu_data']) && $row['menu_data'] !== '') {
            $decoded = json_decode($row['menu_data'], true);
            $row['menu_data'] = is_array($decoded) ? $decoded : null;
        } else if (!isset($row['menu_data'])) {
            $row['menu_data'] = null;
        }

        return $row;
    }

    private function getChanges(array $db, array $scrap) : array{
        
        $changes = [];

        if($db['category_id'] !== $scrap['category_id']){
            $changes['category_id'] = $scrap['category_id'];
        }

        if($db['name_es'] !== $scrap['name_es']){
            $changes['name_es'] = $scrap['name_es'];
        }

        // The scraper only syncs the fields below (name_es, description_es,
        // price, image_url, last_shop_url, sort_order, channels). The other
        // locale fields (name_en/uk, description_en/uk) are owned by
        // the translate API and the admin panel, so a re-sync must never
        // overwrite them.

        if($db['description_es'] !== $scrap['description_es']){
            $changes['description_es'] = $scrap['description_es'];
        }

        // Normalize the scraped price BEFORE comparing: a naive (float) cast
        // on raw DOM text ("19,50 €") truncates it to 19.0, so a change would
        // either be missed or written back corrupted. Comparing the normalized
        // value against the DB value also repairs rows already corrupted by the
        // old code (price stored as TEXT) on the next re-sync.
        $scrapedPrice = $this->normalizePrice($scrap['price'] ?? 0);
        if ((float) $db['price'] !== $scrapedPrice) {
            $changes['price'] = $scrapedPrice;
        }

        // image_url: NULL and '' both mean "no image". Normalize both sides
        // before comparing so a DB stored as NULL and a scrape emitting ''
        // (or vice versa) are EQUAL — no spurious UPDATE every night. When
        // they genuinely differ, emit the normalized scrap value, which is
        // NULL for empty input.
        $dbImage    = self::normalizeImageUrl($db['image_url'] ?? null);
        $scrapImage = self::normalizeImageUrl($scrap['image_url'] ?? null);
        if ((string) $dbImage !== (string) $scrapImage) {
            $changes['image_url'] = $scrapImage;
        }

        if($db['last_shop_url'] !== $scrap['last_shop_url']){
            $changes['last_shop_url'] = $scrap['last_shop_url'];
        }

        if((int)$db['sort_order'] !== (int)$scrap['sort_order']){
            $changes['sort_order'] = (int)$scrap['sort_order'];
        }

        // Channel flags are enforced by the source of truth: scraped products
        // are delivery-only. Any manual dine-in/delivery change in the admin
        // panel is reverted on the next re-sync (security/net-guard: the
        // external menu is authoritative for availability).
        if((bool)($db['is_dine_in'] ?? false) !== (bool)($scrap['is_dine_in'] ?? false)){
            $changes['is_dine_in'] = (int)(bool)($scrap['is_dine_in'] ?? false);
        }

        if((bool)($db['is_delivery'] ?? false) !== (bool)($scrap['is_delivery'] ?? true)){
            $changes['is_delivery'] = (int)(bool)($scrap['is_delivery'] ?? true);
        }

        return $changes;
    }

    /**
     * Repair rows whose price column still holds raw scraped TEXT.
     *
     * The pre-fix code bound raw DOM text ("19,50 €") directly into the REAL
     * column; SQLite keeps it as TEXT because a comma decimal is not a valid
     * REAL literal. Rows with an integer value ("11,00 €") compare float-equal
     * to their normalized value (both 11.0), so the getChanges() comparison
     * alone never rewrites them — the type must be repaired explicitly.
     * Each TEXT price is normalized from its own content (the scraper format
     * is the only source of such rows), so this is safe to run on any DB.
     */
    private function repairTextPrices(): void
    {
        $stmt = $this->pdo->query(
            "SELECT id, price FROM products WHERE typeof(price) = 'text'"
        );
        $stmt->execute();

        $update = $this->pdo->prepare(
            'UPDATE products SET price = :price, updated_at = datetime("now") WHERE id = :id'
        );

        foreach ($stmt->fetchAll() as $row) {
            $update->execute([
                ':id'    => $row['id'],
                ':price' => $this->normalizePrice($row['price']),
            ]);
        }
    }

    /**
     * Normalize a price into a clean float BEFORE it reaches the REAL column.
     *
     * The scraper emits raw DOM text like "19,50 €" (comma decimal, non-breaking
     * space, € symbol) — a naive (float) cast on that string yields 19.0, which
     * silently corrupted every non-integer price on the live menu. Admin/API
     * input is already a plain number ("19.50") and passes through unchanged.
     *
     * Rules:
     *   - int/float input passes through as-is.
     *   - € symbol (U+20AC) and every space variant (regular, NBSP U+00A0,
     *     narrow NBSP U+202F) are stripped.
     *   - Comma-decimal ("19,50") and dot-decimal ("19.50") both parse.
     *   - When both separators are present ("1.234,56"), the LAST one is the
     *     decimal separator and the other is thousands grouping.
     *   - Unparseable input degrades to 0.0 (matches the column default).
     *
     * @param  mixed $price
     * @return float
     */
    private function normalizePrice(mixed $price): float
    {
        if (is_int($price) || is_float($price)) {
            return (float) $price;
        }

        $raw = str_replace(
            ["\u{00A0}", "\u{202F}", ' ', "\u{20AC}"],
            '',
            trim((string) $price)
        );

        // Nothing numeric left (empty, stray symbol, garbage) → column default.
        if ($raw === '' || !preg_match('/\d/', $raw)) {
            return 0.0;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot   = str_contains($raw, '.');

        if ($hasComma && !$hasDot) {
            // "19,50" → 19.5 (Spanish decimal comma).
            $raw = str_replace(',', '.', $raw);
        } elseif ($hasComma && $hasDot) {
            // "1.234,56" / "1,234.56" → the last separator is the decimal one.
            $raw = str_replace(',', '.', $raw);
            $raw = (string) preg_replace('/\.(?=.*\.)/', '', $raw);
        }

        return (float) $raw;
    }

    /**
     * Normalize an image URL before it reaches the image_url column.
     *
     * "No image" is stored as NULL, never ''. The scraper emits NULL when the
     * <img> src is missing or empty, and legacy rows/admin input may still
     * carry '' (or whitespace) — every writer converges on NULL via this
     * helper, and getChanges() uses it to compare both sides normalized.
     *
     * @param  mixed $value
     * @return string|null Normalized URL, or null when empty/whitespace
     */
    public static function normalizeImageUrl(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

}
