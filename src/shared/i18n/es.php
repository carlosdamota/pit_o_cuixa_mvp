<?php
/**
 * Pit o Cuixa — Spanish Translations (ES)
 *
 * Pure data array. Loaded by bootstrap.php with fallback CA→EN.
 * Phone number canonical source: Config::phone()
 *
 * @package Pit\Cuixa\Shared
 */

declare(strict_types=1);

return [
    // ── Global / Layout ──────────────────────────────────────────
    'site.name'        => 'Pit o Cuixa',
    'site.tagline'     => 'Pollería y rosticería en Torredembarra',
    'site.description' => 'Pollería y rosticería artesana en Torredembarra. Pollo al ast, brochetas, hamburguesas y más. Pide online y recoge en tienda.',
    'nav.home'         => 'Inicio',
    'nav.menu'         => 'Carta',
    'nav.admin'        => 'Admin',
    'nav.login'        => 'Iniciar sesión',
    'nav.logout'       => 'Cerrar sesión',
    'lang.switch'      => 'Cambiar idioma',
    'lang.code'        => 'es',
    'nav.faq'          => 'FAQ',
    'footer.rights'    => 'Todos los derechos reservados.',
    'footer.hours'     => 'Horario: Lun-Dom 11:00–23:00',

    // ── FAQ Page ─────────────────────────────────────────────────
    'faq.title'      => 'Preguntas frecuentes',
    'faq.desc'       => 'Respuestas a las preguntas más comunes sobre Pit o Cuixa.',
    'faq.items'      => [
        [
            'q' => '¿Hacéis pedidos para llevar?',
            'a' => 'Sí, puedes pedir por teléfono al 977 64 20 10 y recoger en tienda. No tenemos servicio a domicilio.',
        ],
        [
            'q' => '¿Cuál es el horario?',
            'a' => 'Abierto de lunes a domingo de 11:00 a 23:00. Cerrados solo en días señalados ( consulta nuestras redes).',
        ],
        [
            'q' => '¿Tenéis opciones sin gluten?',
            'a' => 'Sí, disponemos de platos combinados sin gluten. Consulta nuestra carta y pregunta al personal.',
        ],
        [
            'q' => '¿Aceptáis tarjetas de crédito?',
            'a' => 'Sí, aceptamos Visa, Mastercard y efectivo. También admitimos bizum.',
        ],
        [
            'q' => '¿Hay opciones vegetarianas?',
            'a' => 'Sí, ofrecemos ensaladas, patatas bravas y otros platos vegetarianos. Pregunta por nuestras opciones del día.',
        ],
        [
            'q' => '¿Se puede reservar mesa?',
            'a' => 'No tenemos servicio de reservas. El servicio es por orden de llegada, pero siempre procuramos atenderte lo antes posible.',
        ],
        [
            'q' => '¿Ofrecéis menú infantil?',
            'a' => 'Sí, tenemos opciones para los más pequeños: media ración de pollo, patatas fritas y nuggets.',
        ],
    ],

    // ── Home Page ────────────────────────────────────────────────
    'home.title'       => 'Pit o Cuixa — Pollería en Torredembarra',
    'home.desc'        => 'La mejor pollería y rosticería de Torredembarra. Pollo al ast, brochetas, hamburguesas y platos caseros.',
    'home.hero.title'  => 'El mejor pollo al ast de Torredembarra',
    'home.hero.subtitle' => 'Desde 1998 cocinando con amor para ti y los tuyos.',
    'home.hero.cta'    => 'Ver la carta',
    'home.featured'    => 'Más pedidos',
    'home.featured.subtitle' => 'Nuestros clientes lo saben: estos son los imprescindibles.',
    'home.info.title'  => 'Visítanos',
    'home.info.address' => 'Carrer Major, 25, 43800 Torredembarra, Tarragona',
    'home.info.phone'  => 'Tel. +34 977 64 20 10',
    'home.info.hours'  => 'Abierto cada día de 11:00 a 23:00',

    // ── Home Landing (fullscreen index) ──────────────────────────
    'home.landing.title'     => 'Pit o Cuixa — Pollería en Torredembarra',
    'home.landing.aria'      => '¿Qué te apetece?',
    'home.landing.pollos'    => 'Pollos al ast',
    'home.landing.combinados' => 'Platos combinados',
    'home.landing.picapica'  => 'Picapica',
    'home.onboarding.in_local'  => 'En local',
    'home.onboarding.delivery'  => 'A domicilio',
    'home.onboarding.drag_hint' => 'Arrastra tu opción al local para empezar',

    // ── Menu Page ────────────────────────────────────────────────
    'menu.title'       => 'Carta — Pit o Cuixa',
    'menu.desc'        => 'Explora nuestra carta: pollo al ast, brochetas, hamburguesas, ensaladas y mucho más.',
    'menu.heading'     => 'Nuestra carta',
    'menu.subtitle'    => 'Todo hecho al momento con ingredientes de primera calidad.',
    'menu.filter.all'  => 'Todo',
    'menu.filter.popular' => '🔥 Más vendidos',
    'menu.order.cta'   => 'Pedir en last.shop',
    'menu.price.from'  => 'Desde %s',
    'menu.no_products' => 'No hay productos disponibles en esta categoría.',
    'menu.search.label' => 'Buscar productos',
    'menu.search.placeholder' => 'Buscar productos...',
    'menu.search.no_results' => 'No se encontraron productos',
    'menu.map.title'       => 'Zona de reparto a domicilio',
    'menu.map.subtitle'    => 'Llegamos recién hechos y bien calientes a tu puerta.',
    'menu.map.towns_label' => 'Cobertura directa:',
    'menu.map.delivery_note' => '🛵 Reparto disponible en Torredembarra, Altafulla, Creixell, La Móra, Pobla de Montornès y La Riera de Gaià.',

    // ── Product Labels ──────────────────────────────────────────
    'product.price'    => '%s €',
    'product.featured' => 'Destacado',
    'product.view'     => 'Ver',

    // ── Errors ───────────────────────────────────────────────────
    'error.404'        => 'Página no encontrada',
    'error.404.desc'   => 'La página que buscas no existe.',
    'error.404.title'  => 'Página no encontrada',
    'error.404.message' => 'Lo sentimos, la página que buscas no existe o ha sido movida.',
    'error.404.cta'    => 'Volver al inicio',
    'error.500'        => 'Error del servidor',
    'error.500.desc'   => 'Algo ha ido mal. Inténtalo de nuevo más tarde.',
    'error.401'        => 'No autorizado',
    'error.401.desc'   => 'Debes iniciar sesión para acceder a esta página.',

    // ── Admin ────────────────────────────────────────────────────
    'admin.title'            => 'Administración',
    'admin.login.title'      => 'Inicio de sesión',
    'admin.login.error'      => 'Usuario o contraseña incorrectos',
    'admin.logout.success'   => 'Sesión cerrada',
    'admin.dashboard'        => 'Panel',
    'admin.products'         => 'Productos',
    'admin.categories'       => 'Categorías',
    'admin.product.new'      => 'Nuevo Producto',
    'admin.product.edit'     => 'Editar Producto',
    'admin.product.delete'   => 'Eliminar',
    'admin.category.new'     => 'Nueva Categoría',
    'admin.category.edit'    => 'Editar Categoría',
    'admin.category.delete'  => 'Eliminar',
    'admin.save'             => 'Guardar',
    'admin.cancel'           => 'Cancelar',
    'admin.update'           => 'Actualizar',
    'admin.no_products'      => 'No hay productos.',
    'admin.no_categories'    => 'No hay categorías.',
    'admin.import'           => 'Importar CSV',
    'admin.export'           => 'Exportar CSV',
    'admin.view_site'       => 'Ver sitio',
    'admin.password'        => 'Contraseña',
    'admin.username'        => 'Usuario',

    // ── Admin: Settings ──────────────────────────────────────────
    'admin.settings.nav'        => 'Ajustes',
    'admin.settings.title'      => 'Ajustes',
    'admin.settings.slider'     => 'Slider de imágenes del menú',
    'admin.settings.slider_label' => 'Activar slider de imágenes en la página de carta',
    'admin.settings.slider_hint' => 'Al activarlo, el héroe de la carta mostrará un slider. Sube imágenes a /img/menu-slider/ (JPG, PNG, WebP).',
    'admin.settings.images'     => 'Imágenes disponibles',
    'admin.settings.images_count' => '%d imagen(es) encontrada(s) en /img/menu-slider/',
    'admin.settings.images_none' => 'No se encontraron imágenes. Súbelas a /img/menu-slider/ para usar el slider.',
    'admin.settings.images_hint' => 'Las imágenes se muestran en orden alfabético. Formatos: JPG, PNG, WebP.',
    'admin.settings.saved'      => 'Ajuste guardado correctamente.',

    // ── Menu Slider ──────────────────────────────────────────────
    'menu.slider.aria'     => 'Slider de imágenes de la carta',
    'menu.slider.slide_n' => 'Diapositiva %d de %d',
    'menu.slider.image_alt' => 'Imagen del menú %d',
    'menu.slider.dots'    => 'Ir a diapositiva',
    'menu.slider.prev'    => 'Diapositiva anterior',
    'menu.slider.next'    => 'Siguiente diapositiva',

    // ── Home Quotes ───────────────────────────────────────────────
    'home.quotes' => [
        '¡En esta familia se chupa hasta los dedos!',
        'El ingrediente secreto es el pollo... y estar juntos.',
        'Menos drama y más pollo en la mesa.',
        'Familia unida, pollo devorado.',
        'Donde hay buen pollo y familia, hay felicidad.',
        '¡Aquí el pollo nos une a todos!',
        'Comer juntos sabe mejor con buen pollo.',
        '¡Alitas arriba las familias felices!',
    ],
];
