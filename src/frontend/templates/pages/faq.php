<?php
/**
 * Pit o Cuixa — FAQ Page Template
 *
 * Renders the FAQ accordion list using native <details>/<summary>.
 * Each item is a <details> with the question as <summary> and the
 * answer as plain text inside a <div>.
 *
 * Available variables from renderPage():
 *   $metaData  — SEO meta array
 *   $pageData  — page-specific data (faqItems, locale)
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages
 */

$faqItems = $pageData['faqItems'] ?? [];
?>
<section class="faq" aria-labelledby="faq-title">
    <div class="faq__inner container">
        <h1 id="faq-title" class="faq__title"><?= __('faq.title') ?></h1>
        <p class="faq__desc"><?= __('faq.desc') ?></p>

        <div class="faq__list" role="list">
            <?php foreach ($faqItems as $index => $item): ?>
            <details class="faq__item" role="listitem">
                <summary class="faq__question"><?= htmlspecialchars($item['q'], ENT_QUOTES, 'UTF-8') ?></summary>
                <div class="faq__answer">
                    <p><?= htmlspecialchars($item['a'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
