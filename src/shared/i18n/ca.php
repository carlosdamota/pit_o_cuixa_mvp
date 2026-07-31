<?php
/**
 * Pit o Cuixa — Catalan Translations (CA)
 *
 * Pure data array. Loaded by bootstrap.php with fallback to EN.
 * Phone number canonical source: Config::phone()
 *
 * @package Pit\Cuixa\Shared
 */

declare(strict_types=1);

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
    'lang.switch'      => 'Canviar idioma',
    'lang.code'        => 'ca',
    'nav.faq'          => 'FAQ',
    'footer.rights'    => 'Tots els drets reservats.',
    'footer.hours'     => 'Horari: Dll-Dg 11:00–23:00',

    // ── FAQ Page ─────────────────────────────────────────────────
    'faq.title'      => 'Preguntes freqüents',
    'faq.desc'       => 'Respostes a les preguntes més comunes sobre Pit o Cuixa.',
    'faq.items'      => [
        [
            'q' => 'Feu comandes per emportar?',
            'a' => 'Sí, pots demanar per telèfon al 977 64 20 10 i recollir a la botiga. No tenim servei a domicili.',
        ],
        [
            'q' => 'Quin és l\'horari?',
            'a' => 'Obert de dilluns a diumenge d\'11:00 a 23:00. Tancats només en dies assenyalats (consulta les nostres xarxes).',
        ],
        [
            'q' => 'Teniu opcions sense gluten?',
            'a' => 'Sí, disposem de plats combinats sense gluten. Consulta la nostra carta i pregunta al personal.',
        ],
        [
            'q' => 'Accepteu targetes de crèdit?',
            'a' => 'Sí, acceptem Visa, Mastercard i efectiu. També admetem bizum.',
        ],
        [
            'q' => 'Hi ha opcions vegetarianes?',
            'a' => 'Sí, oferim amanides, patates braves i altres plats vegetarians. Pregunta per les nostres opcions del dia.',
        ],
        [
            'q' => 'Es pot reservar taula?',
            'a' => 'No tenim servei de reserves. El servei és per ordre d\'arribada, però sempre procurem atendre\'t el més aviat possible.',
        ],
        [
            'q' => 'Oferiu menú infantil?',
            'a' => 'Sí, tenim opcions per als més petits: mitja ració de pollastre, patates fregides i nuggets.',
        ],
    ],

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
    'home.info.phone'  => 'Tel. +34 977 64 20 10',
    'home.info.hours'  => 'Obert cada dia d\'11:00 a 23:00',

    // ── Home Landing (fullscreen index) ──────────────────────────
    'home.landing.title'     => 'Pit o Cuixa — Pollería a Torredembarra',
    'home.landing.aria'      => 'Què et ve de gust?',
    'home.landing.pollos'    => 'Pollos a l\'ast',
    'home.landing.combinados' => 'Plats combinats',
    'home.landing.picapica'  => 'Pica-pica',
    'home.onboarding.in_local'  => 'Al local',
    'home.onboarding.delivery'  => 'A domicili',
    'home.onboarding.drag_hint' => 'Arrossega la teva opció al local per començar',

    // ── Menu Page ────────────────────────────────────────────────
    'menu.title'       => 'Carta — Pit o Cuixa',
    'menu.desc'        => 'Explora la nostra carta: pollo a l\'ast, broquetes, hamburgueses, amanides i molt més.',
    'menu.heading'     => 'La nostra carta',
    'menu.subtitle'    => 'Tot fet al moment amb ingredients de primera qualitat.',
    'menu.filter.all'  => 'Tot',
    'menu.filter.popular' => '🔥 Més venuts',
    'menu.order.cta'   => 'Demanar a last.shop',
    'menu.price.from'  => 'Des de %s',
    'menu.no_products' => 'No hi ha productes disponibles en aquesta categoria.',
    'menu.search.label' => 'Cerca productes',
    'menu.search.placeholder' => 'Cerca productes...',
    'menu.search.no_results' => 'No s\'han trobat productes',
    'menu.map.title'       => 'Zona de repartiment a domicili',
    'menu.map.subtitle'    => 'Arribem acabats de fer i ben calents a la teva porta.',
    'menu.map.towns_label' => 'Cobertura directa:',
    'menu.map.delivery_note' => '🛵 Repartiment disponible a Torredembarra, Altafulla, Creixell, La Móra, Pobla de Montornès i La Riera de Gaià.',

    // ── Product Labels ──────────────────────────────────────────
    'product.price'    => '%s €',
    'product.featured' => 'Destacat',
    'product.view'     => 'Veure',

    // ── Errors ───────────────────────────────────────────────────
    'error.404'        => 'Pàgina no trobada',
    'error.404.desc'   => 'La pàgina que busques no existeix.',
    'error.404.title'  => 'Pàgina no trobada',
    'error.404.message' => 'Ho sentim, la pàgina que busques no existeix o ha estat moguda.',
    'error.404.cta'    => 'Tornar a l\'inici',
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

    // ── Admin: Settings ──────────────────────────────────────────
    'admin.settings.nav'        => 'Ajustos',
    'admin.settings.title'      => 'Ajustos',
    'admin.settings.slider'     => 'Slider d\'imatges de la carta',
    'admin.settings.slider_label' => 'Activar slider d\'imatges a la pàgina de carta',
    'admin.settings.slider_hint' => 'En activar-lo, l\'heroi de la carta mostrarà un slider. Puja imatges a /img/menu-slider/ (JPG, PNG, WebP).',
    'admin.settings.images'     => 'Imatges disponibles',
    'admin.settings.images_count' => '%d imatge(es) trobada(es) a /img/menu-slider/',
    'admin.settings.images_none' => 'No s\'han trobat imatges. Puja-les a /img/menu-slider/ per usar el slider.',
    'admin.settings.images_hint' => 'Les imatges es mostren en ordre alfabètic. Formats: JPG, PNG, WebP.',
    'admin.settings.saved'      => 'Ajust guardat correctament.',

    // ── Menu Slider ──────────────────────────────────────────────
    'menu.slider.aria'     => 'Slider d\'imatges de la carta',
    'menu.slider.slide_n' => 'Diapositiva %d de %d',
    'menu.slider.image_alt' => 'Imatge del menú %d',
    'menu.slider.dots'    => 'Anar a diapositiva',
    'menu.slider.prev'    => 'Diapositiva anterior',
    'menu.slider.next'    => 'Següent diapositiva',

    // ── Home Quotes ───────────────────────────────────────────────
    'home.quotes' => [
        '¡En aquesta família ens llepem els dits!',
        'L\'ingredient secret és el pollastre... i estar tots junts.',
        'Menys drama i més pollastre a la taula.',
        'Família unida, pollastre devorat.',
        'On hi ha bon pollastre i família, hi ha felicitat.',
        '¡Aquí el pollastre ens uneix a tots!',
        'Menjar junts sap millor amb un bon pollastre.',
        '¡Aletes amunt les famílies felices!',
    ],

    // ── PWA Installation ──────────────────────────────────────────
    'pwa.install' => 'Instal·lar App',
];
