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
    private DeepLService $deepl;
    private \PDO $pdo;

    public function __construct(?string $apiKey = null)
    {
        $this->deepl = new DeepLService($apiKey);
        $this->pdo   = Connection::get();
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
            // 1. Categories
            $catCol = "name_{$lang}";
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
            $nameCol = "name_{$lang}";
            $descCol = "description_{$lang}";
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
