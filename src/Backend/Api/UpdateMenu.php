<?php

/**
 * Pit o Cuixa — UpdateMenu API Controller
 *
 * Scrapes products, syncs SQLite database, and auto-translates missing fields into CA, EN, UK.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Api\WebScraper;
use Pit\Cuixa\Backend\Db\Repositories\Product;
use Pit\Cuixa\Backend\Services\MenuTranslator;

class UpdateMenu
{
    /**
     * Inserta los datos en las bases de datos y ejecuta la traducción de campos faltantes
     * @return array
     */
    public function update(): array
    {
        $scraper = new WebScraper();
        $repo    = new Product();

        $products = $scraper->scraper();
        $repo->sync($products);

        // Auto-translate missing fields in CA, EN, UK
        $translatedStats = ['categories' => 0, 'products' => 0];
        $warning = null;

        if (empty(getenv('DEEPL_API_KEY'))) {
            $warning = 'DEEPL_API_KEY no configurada en .env — traducción automática omitida.';
            error_log('MenuTranslator skipped: ' . $warning);
        } else {
            try {
                $translator = new MenuTranslator();
                $translatedStats = $translator->translateMissing();
            } catch (\Throwable $e) {
                error_log('Menu translation error: ' . $e->getMessage());
                $warning = 'Traducción falló: ' . $e->getMessage();
            }
        }

        $result = [
            'status'     => 'ok',
            'translated' => $translatedStats,
        ];
        if ($warning !== null) {
            $result['warning'] = $warning;
        }

        return $result;
    }
}
