<?php
/**
 * 404 — Page Not Found
 *
 * @package Pit\Cuixa
 */
?>
<section class="error-page" aria-labelledby="error-title">
    <div class="container error-page__content">
        <h1 id="error-title" class="error-page__title"><?= __('error.404.title') ?></h1>
        <p class="error-page__message"><?= __('error.404.message') ?></p>
        <a href="/" class="btn btn--primary error-page__cta"><?= __('error.404.cta') ?></a>
    </div>
</section>
