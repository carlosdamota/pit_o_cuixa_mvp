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

        //Productos de la DB.
        $dbProducts = $this->all(onlyActive : false);

        //Mapa Slugs
        $catRepo = new Category();
        $catMap = $catRepo->MapSlug();

        
        $filterProducts = [];
        
        foreach($dbProducts as $p){

            //Convertimos el array en dict
            $filterProducts[$p["slug"]] = $p;
        }

        $scrapedMap = [];

        foreach($scrapedProducts as $p){
    
            $p['category_id'] = $catMap[$p['category']];
            unset($p['category']);

            $slug = $p['slug'];

            $scrapedMap[$slug] = $p;

            //Insertar Datos
            if(!isset($filterProducts[$slug])){
                
                var_dump($slug);

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

            //Desactivar Producto

            foreach($filterProducts as $slug => $p){

                if(!isset($scrapedMap[$slug]) && $p['is_active']){
                    $this->setStatus($slug, false);
                }
            }
        }
    }
    /**
     * Summary of insert
     * @param array $product
     * @return void
     */

    public function insert(array $product): void{

        //Preparamos la insercion
        $stmt = $this->pdo->prepare(
            'INSERT INTO products(
            category_id, slug, name_es, name_en, name_ca, description_es, description_en, description_ca, price, image_url, last_shop_url, sort_order, is_active, is_featured)
            VALUES(
            :category_id, :slug, :name_es, :name_en, :name_ca, :description_es, :description_en, :description_ca, :price, :image_url, :last_shop_url, :sort_order, :is_active, :is_featured)'
        );
        var_dump($product['category_id']);
        $stmt -> execute([
            ':category_id' => $product['category_id'],
            ':slug' => $product['slug'],
            ':name_es' => $product['name_es'],
            ':name_en' => '',
            ':name_ca' => '',
            ':description_es' => $product['description_es'],
            ':description_en' => '',
            ':description_ca' => '',
            ':price' => $product['price'],
            ':image_url' => $product['image_url'],
            ':last_shop_url' => $product['last_shop_url'],
            ':sort_order' => $product['sort_order'],
            ':is_active' => 1,
            ':is_featured' => 0
        ]);

        return;
    }

    public function update(string $slug, array $changes) : void{

        if(empty($changes)){
            return;
        }

        $fields = [];
        $params = [];

        foreach($changes as $col => $val){
            $fields[] = "{$col} = :{$col}";
            $params[":{$col}"] = $val;
        }

        $fields[] = 'updated_at = datetime("now")';
        $params[':slug'] = $slug;

        $sql = 'UPDATE products SET '.implode(', ', $fields). ' WHERE slug = :slug';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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

        $sql = 'SELECT p.*, c.slug AS category_slug, c.name_ca AS category_name_ca, c.name_es AS category_name_es, c.name_en AS category_name_en
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
            'SELECT p.*, c.slug AS category_slug, c.name_ca AS category_name_ca, c.name_es AS category_name_es, c.name_en AS category_name_en
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
        $totalClicksStmt = $this->pdo->query('SELECT SUM(clicks_count) FROM products WHERE is_active = 1');
        $totalClicks = (int) $totalClicksStmt->fetchColumn();

        if ($totalClicks > 0) {
            $sql = 'SELECT p.*, c.slug AS category_slug, c.name_ca AS category_name_ca, c.name_es AS category_name_es, c.name_en AS category_name_en
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    WHERE p.is_active = 1
                    ORDER BY p.clicks_count DESC, p.sort_order ASC
                    LIMIT :limit';
        } else {
            $sql = 'SELECT p.*, c.slug AS category_slug, c.name_ca AS category_name_ca, c.name_es AS category_name_es, c.name_en AS category_name_en
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


        /*if($db['name_ca'] !== $scrap['name_ca']){
            $changes['name_ca'] = $scrap['name_ca'];
        }*/

        if($db['description_es'] !== $scrap['description_es']){
            $changes['description_es'] = $scrap['description_es'];
        }

        /*if($db['description_en'] !== $scrap['description_en']){
            $changes['description_en'] = $scrap['description_en'];
        }*/

        /*if($db['description_ca'] !== $scrap['description_ca']){
            $changes['description_ca'] = $scrap['description_ca'];
        }*/

        if((float)$db['price'] !== (float)$scrap['price']){
            $changes['price'] = (float)$scrap['price'];
        }

        if($db['image_url'] !== $scrap['image_url']){
            $changes['image_url'] = $scrap['image_url'];
        }

        if($db['last_shop_url'] !== $scrap['last_shop_url']){
            $changes['last_shop_url'] = $scrap['last_shop_url'];
        }

        if((int)$db['sort_order'] !== (int)$scrap['sort_order']){
            $changes['sort_order'] = (int)$scrap['sort_order'];
        }

        return $changes;
    }

}
