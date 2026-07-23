<?php
/**
 * Pit o Cuixa — Spanish Translations
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
 * Return all Spanish translation key-value pairs.
 *
 * @return array<string, string>
 */
function getTranslations(): array
{
    return [
        // ── Global / Layout ──────────────────────────────────────────
        'site.name'        => 'Pit o Cuixa',
        'site.tagline'     => 'Pollería i rostería a Torredembarra',
        'site.description' => 'Pollería i rostería artesana a Torredembarra. Pollo a l\'ast, broquetes, hamburgueses i més. Demana online i recull a la botiga.',
        'nav.home'         => 'Inici',
        'nav.menu'         => 'Carta',
        'nav.admin'        => 'Admin',
        'nav.login'        => 'Iniciar sessió',
        'nav.logout'       => 'Tancar sessió',
        'lang.switch'      => 'English',
        'lang.code'        => 'es',
        'footer.rights'    => 'Tots els drets reservats.',
        'footer.hours'     => 'Horari: Dll-Dg 11:00–23:00',

        // ── Home Page ────────────────────────────────────────────────
        'home.title'       => 'Pit o Cuixa — Pollería a Torredembarra',
        'home.desc'        => 'La millor pollería i rostería de Torredembarra. Pollo a l\'ast, broquetes, hamburgueses i plats casolans.',
        'home.hero.title'  => 'El millor pollo a l\'ast de Torredembarra',
        'home.hero.subtitle' => 'Des de 1998 cuinant amb amor per a tu i els teus.',
        'home.hero.cta'    => 'Veure la carta',
        'home.featured'    => 'Més demanats',
        'home.featured.subtitle' => 'Els nostres clients ho saben: aquests són els imprescindibles.',
        'home.info.title'  => 'Visita\'ns',
        'home.info.address' => 'Carrer Major, 25, 43800 Torredembarra, Tarragona',
        'home.info.phone'  => 'Tel. 977 64 20 10',
        'home.info.hours'  => 'Obert cada dia d\'11:00 a 23:00',

        // ── Menu Page ────────────────────────────────────────────────
        'menu.title'       => 'Carta — Pit o Cuixa',
        'menu.desc'        => 'Explora la nostra carta: pollo a l\'ast, broquetes, hamburgueses, amanides i molt més.',
        'menu.heading'     => 'La nostra carta',
        'menu.subtitle'    => 'Tot fet al moment amb ingredients de primera qualitat.',
        'menu.filter.all'  => 'Tot',
        'menu.order.cta'   => 'Demanar a last.shop',
        'menu.price.from'  => 'Des de %s',
        'menu.no_products' => 'No hi ha productes disponibles en aquesta categoria.',

        // ── Product Labels ──────────────────────────────────────────
        'product.price'    => '%s €',
        'product.featured' => 'Destacat',
        'product.view'     => 'Veure',

        // ── Errors ───────────────────────────────────────────────────
        'error.404'        => 'Pàgina no trobada',
        'error.404.desc'   => 'La pàgina que busques no existeix.',
        'error.404.title'  => 'Página no encontrada',
        'error.404.message' => 'Lo sentimos, la página que buscas no existe o ha sido movida.',
        'error.404.cta'    => 'Volver al inicio',
        'error.500'        => 'Error del servidor',
        'error.500.desc'   => 'Alguna cosa ha anat malament. Torna-ho a intentar més tard.',
        'error.401'        => 'No autoritzat',
        'error.401.desc'   => 'Has d\'iniciar sessió per accedir a aquesta pàgina.',

        // ── Admin ────────────────────────────────────────────────────
        'admin.title'            => 'Administració',
        'admin.login.title'      => 'Inici de sessió',
        'admin.login.error'      => 'Usuari o contrasenya incorrectes',
        'admin.logout.success'   => 'Sessió tancada',
        'admin.dashboard'        => 'Panell',
        'admin.products'         => 'Productes',
        'admin.categories'       => 'Categories',
        'admin.product.new'      => 'Nou Producte',
        'admin.product.edit'     => 'Editar Producte',
        'admin.product.delete'   => 'Eliminar',
        'admin.category.new'     => 'Nova Categoria',
        'admin.category.edit'    => 'Editar Categoria',
        'admin.category.delete'  => 'Eliminar',
        'admin.save'             => 'Guardar',
        'admin.cancel'           => 'Cancel·lar',
        'admin.update'           => 'Actualitzar',
        'admin.no_products'      => 'No hi ha productes.',
        'admin.no_categories'    => 'No hi ha categories.',
        'admin.import'           => 'Importar CSV',
        'admin.export'           => 'Exportar CSV',
        'admin.view_site'       => 'Veure lloc',
        'admin.password'        => 'Contrasenya',
        'admin.username'        => 'Usuari',
    ];
}

// Expose translations for direct access if needed
$GLOBALS['_translations_es'] = getTranslations();
