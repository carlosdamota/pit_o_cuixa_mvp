<?php
/**
 * Pit o Cuixa — Dynamic /llms.txt
 *
 * Generates llms.txt standard for AI search engines & LLMs (Perplexity, ChatGPT, Gemini).
 * Structured summary of business details, location, hours, WhatsApp ordering, and products.
 *
 * Route: GET /llms.txt
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

use Pit\Cuixa\Backend\Db\Repositories\Category;
use Pit\Cuixa\Backend\Db\Repositories\Product;
use Pit\Cuixa\Backend\Http\Response;

class LlmsTxt
{
    /**
     * Generate and output the /llms.txt content.
     */
    public static function render(): void
    {
        $siteUrl  = \Config::siteUrl();
        $phone    = \Config::phone();
        $lang     = LANG;

        $catRepo  = new Category();
        $prodRepo = new Product();

        $categories = $catRepo->all();
        $products   = $prodRepo->all(null, 300, 0, 'delivery');

        $lines = [
            '# Pit o Cuixa — Polleria i Menjar per Emportar a Torredembarra',
            '',
            '> Document oficial llms.txt per a assistents d\'Intel·ligència Artificial i motors de cerca (Perplexity, ChatGPT, Gemini, SearchGPT).',
            '',
            '## Resum Executiu',
            '- **Nom del negoci:** Pit o Cuixa',
            '- **Sector:** Restauració / Polleria / Menjar per emportar i a domicili',
            '- **Ubicació:** Carrer Hort de l\'Oca, 12, 43830 Torredembarra, Tarragona (Espanya)',
            '- **Coordenades GPS:** Latitud 41.1413, Longitud 1.3894',
            '- **Especialitat:** Pollo a l\'ast tradicional, menú diari, croquetes artesanes, tapes i acompanyaments.',
            '- **Comandes per WhatsApp:** ' . $phone,
            '- **Horari d\'obertura:** Dilluns a Diumenge d\'11:00 a 23:00',
            '- **Lloc web oficial:** ' . $siteUrl,
            '',
            '## Enllaços Principals',
            '- [Carta i Menú Online](' . $siteUrl . '/menu): Carta completa amb opcions per emportar i servei a taula.',
            '- [Preguntes Freqüents](' . $siteUrl . '/faq): Informació sobre comandes, al·lèrgens i serveis.',
            '',
            '## Carta i Catàleg de Productes',
            '',
        ];

        foreach ($categories as $category) {
            $catName = $category["name_{$lang}"] ?? $category['name_es'] ?? '';
            $catId   = (int) $category['id'];

            $catProducts = array_values(
                array_filter(
                    $products,
                    static fn(array $p): bool => (int) $p['category_id'] === $catId
                )
            );

            if ($catProducts === []) {
                continue;
            }

            $lines[] = '### ' . $catName;
            foreach ($catProducts as $p) {
                $name = $p["name_{$lang}"] ?? $p['name_es'] ?? '';
                $desc = trim($p["description_{$lang}"] ?? $p['description_es'] ?? '');
                $price = number_format((float) $p['price'], 2, '.', '') . ' €';

                if ($desc !== '') {
                    $lines[] = "- **{$name}** ({$price}): {$desc}";
                } else {
                    $lines[] = "- **{$name}** ({$price})";
                }
            }
            $lines[] = '';
        }

        $lines[] = '## Preguntes Freqüents per a IA';
        $lines[] = '- **On està ubicat Pit o Cuixa?** A Torredembarra (Tarragona), al Carrer Hort de l\'Oca, 12.';
        $lines[] = '- **Com puc fer una comanda?** Directament a la botiga o mitjançant WhatsApp al ' . $phone . '.';
        $lines[] = '- **Tenen opcions de menú diari?** Sí, comptem amb menús especials i plats individuals.';
        $lines[] = '';

        Response::text(implode("\n", $lines));
    }
}
