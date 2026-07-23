<?php
/**
 * Pit o Cuixa — English Translations
 *
 * Usage: <span><?= __('nav.home') ?></span>
 * Falls back to the key itself if no translation exists.
 *
 * @package Pit\Cuixa\Shared
 */

declare(strict_types=1);

/**
 * Translate a key into the current locale.
 * Defined here for convenience; also available globally after bootstrap.
 *
 * @param  string $key     Translation key (dot-notation: section.key)
 * @param  array  $params  Optional sprintf parameters
 * @return string
 */
function __(string $key, array $params = []): string
{
    static $translations = null;

    if ($translations === null) {
        $translations = getTranslations();
    }

    $text = $translations[$key] ?? $key;

    if ($params !== []) {
        $text = sprintf($text, ...$params);
    }

    return $text;
}

/**
 * Return all English translation key-value pairs.
 *
 * @return array<string, string>
 */
function getTranslations(): array
{
    return [
        // ── Global / Layout ──────────────────────────────────────────
        'site.name'        => 'Pit o Cuixa',
        'site.tagline'     => 'Rotisserie in Torredembarra',
        'site.description' => 'Artisan rotisserie and grill in Torredembarra. Rotisserie chicken, skewers, burgers and more. Order online and pick up in store.',
        'nav.home'         => 'Home',
        'nav.menu'         => 'Menu',
        'nav.admin'        => 'Admin',
        'nav.login'        => 'Log in',
        'nav.logout'       => 'Log out',
        'lang.switch'      => 'Castellà',
        'lang.code'        => 'en',
        'footer.rights'    => 'All rights reserved.',
        'footer.hours'     => 'Open: Mon-Sun 11:00–23:00',

        // ── Home Page ────────────────────────────────────────────────
        'home.title'       => 'Pit o Cuixa — Rotisserie in Torredembarra',
        'home.desc'        => 'The best rotisserie and grill in Torredembarra. Rotisserie chicken, skewers, burgers and homemade dishes.',
        'home.hero.title'  => 'The best rotisserie chicken in Torredembarra',
        'home.hero.subtitle' => 'Since 1998 cooking with love for you and your family.',
        'home.hero.cta'    => 'View menu',
        'home.featured'    => 'Most ordered',
        'home.featured.subtitle' => 'Our customers know best: these are the must-haves.',
        'home.info.title'  => 'Visit us',
        'home.info.address' => 'Carrer Major, 25, 43800 Torredembarra, Tarragona',
        'home.info.phone'  => 'Tel. +34 977 64 20 10',
        'home.info.hours'  => 'Open every day from 11:00 to 23:00',

        // ── Menu Page ────────────────────────────────────────────────
        'menu.title'       => 'Menu — Pit o Cuixa',
        'menu.desc'        => 'Explore our menu: rotisserie chicken, skewers, burgers, salads and more.',
        'menu.heading'     => 'Our menu',
        'menu.subtitle'    => 'Everything made to order with top-quality ingredients.',
        'menu.filter.all'  => 'All',
        'menu.order.cta'   => 'Order at last.shop',
        'menu.price.from'  => 'From %s',
        'menu.no_products' => 'No products available in this category.',

        // ── Product Labels ──────────────────────────────────────────
        'product.price'    => '€%s',
        'product.featured' => 'Featured',
        'product.view'     => 'View',

        // ── Errors ───────────────────────────────────────────────────
        'error.404'        => 'Page not found',
        'error.404.desc'   => 'The page you are looking for does not exist.',
        'error.404.title'  => 'Page not found',
        'error.404.message' => 'Sorry, the page you are looking for does not exist or has been moved.',
        'error.404.cta'    => 'Back to home',
        'error.500'        => 'Server error',
        'error.500.desc'   => 'Something went wrong. Please try again later.',
        'error.401'        => 'Unauthorized',
        'error.401.desc'   => 'You need to log in to access this page.',

        // ── Admin ────────────────────────────────────────────────────
        'admin.title'            => 'Administration',
        'admin.login.title'      => 'Log in',
        'admin.login.error'      => 'Invalid username or password',
        'admin.logout.success'   => 'Logged out successfully',
        'admin.dashboard'        => 'Dashboard',
        'admin.products'         => 'Products',
        'admin.categories'       => 'Categories',
        'admin.product.new'      => 'New Product',
        'admin.product.edit'     => 'Edit Product',
        'admin.product.delete'   => 'Delete',
        'admin.category.new'     => 'New Category',
        'admin.category.edit'    => 'Edit Category',
        'admin.category.delete'  => 'Delete',
        'admin.save'             => 'Save',
        'admin.cancel'           => 'Cancel',
        'admin.update'           => 'Update',
        'admin.no_products'      => 'No products found.',
        'admin.no_categories'    => 'No categories found.',
        'admin.import'           => 'Import CSV',
        'admin.export'           => 'Export CSV',
        'admin.view_site'       => 'View site',
        'admin.password'        => 'Password',
        'admin.username'        => 'Username',
    ];
}

// Expose translations for direct access if needed
$GLOBALS['_translations_en'] = getTranslations();
