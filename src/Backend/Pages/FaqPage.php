<?php
/**
 * Pit o Cuixa — FAQ Page Controller
 *
 * SSR: renders the FAQ page with accordion Q&A, FAQPage JSON-LD,
 * and i18n content in ca/es/en.
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

class FaqPage
{
    /**
     * Render the FAQ page.
     */
    public static function render(): void
    {
        $faqItems = __('faq.items');

        // ── Build FAQPage JSON-LD schema ───────────────────────────
        $faqJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static function (array $item): array {
                return [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['a'],
                    ],
                ];
            }, $faqItems),
        ];

        $siteUrl = \Config::siteUrl();

        $meta = [
            'title'       => __('faq.title'),
            'description' => __('faq.desc'),
            'canonical'   => $siteUrl . '/faq',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'es' => $siteUrl . '/faq?lang=es',
                'en' => $siteUrl . '/faq?lang=en',
                'uk' => $siteUrl . '/faq?lang=uk',
            ],
            'jsonld'      => $faqJsonLd,
        ];

        $data = [
            'locale'   => LANG,
            'faqItems' => $faqItems,
        ];

        \renderPage('faq', $meta, $data);
    }
}
