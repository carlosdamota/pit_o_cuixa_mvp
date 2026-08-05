<?php
/**
 * Pit o Cuixa — Menu Translator Service
 *
 * Service for batch translating categories and products using DeepLService.
 *
 * @package Pit\Cuixa\Backend\Services
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Services;

use Pit\Cuixa\Backend\Db\Connection;
use Pit\Cuixa\Backend\Services\DeepLService;

class MenuTranslator
{
    /**
     * Whitelist of target languages whose columns are writable (TRANSL-6).
     *
     * `es` is deliberately absent: it is the source language, never a target,
     * so `Config::supportedLocales()` (which includes it) is NOT the right
     * whitelist here. Any language outside this list must fail before any
     * SQL is built — dynamic interpolation of `$lang` into column names is a
     * SQL injection surface.
     */
    private const LANGS = ['ca', 'en', 'uk'];

    private DeepLService $deepl;
    private \PDO $pdo;

    public function __construct(?string $apiKey = null)
    {
        $this->deepl = new DeepLService($apiKey);
        $this->pdo   = Connection::get();
    }

    /**
     * Resolve a language-derived column name against the whitelist.
     *
     * The ONLY place where `name_{$lang}` / `description_{$lang}` style column
     * names are built. Throws for any language outside LANGS BEFORE the value
     * can reach a query.
     *
     * @param  string $pfx Column prefix, e.g. 'name' or 'description'
     * @param  string $l   Target language ('ca' | 'en' | 'uk')
     * @return string      Whitelisted column name, e.g. 'name_ca'
     * @throws \InvalidArgumentException When $l is not a supported target
     */
    private function col(string $pfx, string $l): string
    {
        if (!in_array($l, self::LANGS, true)) {
            throw new \InvalidArgumentException(
                "Unsupported target language '{$l}' — expected one of: " . implode(', ', self::LANGS)
            );
        }

        return "{$pfx}_{$l}";
    }

    /**
     * Translate missing name and description fields for categories and products.
     *
     * @param array<int, string> $targetLangs Target languages ('ca', 'en', 'uk')
     * @return array{categories: int, products: int} Translation count stats
     */
    public function translateMissing(array $targetLangs = ['ca', 'en', 'uk']): array
    {
        $stats = ['categories' => 0, 'products' => 0];

        foreach ($targetLangs as $lang) {
            // Resolve columns through the whitelist — throws before any SQL
            // for an unsupported language (TRANSL-6).
            $catCol = $this->col('name', $lang);
            $categories = $this->pdo->query("SELECT id, name_es FROM categories WHERE {$catCol} IS NULL OR {$catCol} = ''")->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($categories)) {
                $texts = array_column($categories, 'name_es');
                $translated = $this->deepl->translate($texts, $lang, 'ES');
                $stmt = $this->pdo->prepare("UPDATE categories SET {$catCol} = :val WHERE id = :id");
                foreach ($categories as $idx => $cat) {
                    $stmt->execute([
                        ':val' => $translated[$idx] ?? $cat['name_es'],
                        ':id'  => $cat['id'],
                    ]);
                    $stats['categories']++;
                }
            }

            // 2. Products
            $nameCol = $this->col('name', $lang);
            $descCol = $this->col('description', $lang);
            $products = $this->pdo->query("SELECT id, name_es, description_es, {$nameCol}, {$descCol} FROM products")->fetchAll(\PDO::FETCH_ASSOC);

            $payloadTexts = [];
            $map = [];

            foreach ($products as $p) {
                $id = $p['id'];
                if (!empty($p['name_es']) && empty($p[$nameCol])) {
                    $map[] = ['id' => $id, 'field' => $nameCol];
                    $payloadTexts[] = $p['name_es'];
                }
                if (!empty($p['description_es']) && empty($p[$descCol])) {
                    $map[] = ['id' => $id, 'field' => $descCol];
                    $payloadTexts[] = $p['description_es'];
                }
            }

            if (!empty($payloadTexts)) {
                $chunks = array_chunk($payloadTexts, 50);
                $mapChunks = array_chunk($map, 50);

                foreach ($chunks as $chunkIdx => $chunkTexts) {
                    $translatedChunk = $this->deepl->translate($chunkTexts, $lang, 'ES');
                    $chunkMap = $mapChunks[$chunkIdx];

                    $this->pdo->beginTransaction();
                    foreach ($chunkMap as $idx => $meta) {
                        $val = $translatedChunk[$idx] ?? '';
                        $col = $meta['field'];
                        $id  = $meta['id'];

                        $stmt = $this->pdo->prepare("UPDATE products SET {$col} = :val, updated_at = datetime('now') WHERE id = :id");
                        $stmt->execute([':val' => $val, ':id' => $id]);
                        $stats['products']++;
                    }
                    $this->pdo->commit();
                }
            }
        }

        return $stats;
    }
}
