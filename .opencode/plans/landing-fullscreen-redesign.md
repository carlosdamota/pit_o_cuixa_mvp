# Plan: Landing índice fullscreen + rediseño minimalista corporativo

## Objetivo

Convertir la home (`/`) en una **landing-índice fullscreen**: logo de la empresa + 3 botones
gigantes (Pollos / Platos combinados / Picapica) que redirigen a `/menu` con el filtro de
categoría preseleccionado. Estilo minimalista con los colores corporativos (amarillo
`#f7e721`, rojo `#d32f2f`) como protagonistas.

## Decisiones tomadas (con el usuario)

1. **Destino de los botones** → `/menu?cat=<slug>` con filtro preseleccionado en cliente.
   - Pollos → `?cat=pollos` (categoría existente)
   - Platos combinados → `?cat=menus` (categoría existente "Menús")
   - Picapica → `?cat=picapica` (**categoría nueva** a crear desde el admin)
2. **La landing REEMPLAZA la home actual.** Desaparecen el hero, los destacados y la
   sección de info. La dirección y el horario sobreviven en el footer.
3. **El fondo de la landing es `--color-primary` (amarillo `#f7e721`).** Los botones
   se diseñan en oscuro para contrastar (ver Fase 2.2).
4. **El teléfono se mueve al footer** (obligatorio, no opcional): al eliminar la sección
   info de la home, no puede desaparecer de la web.

## Estado actual (hallazgos clave)

- Las clases `.hero`, `.section`, `.info` de la home **no tienen CSS**: la home está
  prácticamente sin estilar. No hay estilos que heredar ni conflictos que resolver.
- El filtro de `/menu` es 100% cliente (`public/js/menu-filter.js`): `activeCategory`
  + `applyFilters()`. No lee la URL → hay que añadir lectura de `?cat=`.
- Idioma: `?lang=` → cookie → defecto (CA). La cookie persiste el idioma, pero los
  botones añadirán `&lang=` explícito cuando el idioma activo no sea CA.
- El footer ya muestra dirección (`home.info.address`) y horario. El **teléfono solo
  existe en la home** → desaparecería de toda la web (ver Fase 4, paso opcional).
- Un producto pertenece a UNA sola categoría (`products.category_id`). Reasignar
  productos a `picapica` los quita de `patates`/`extras` → decisión de datos del admin.
- Logo disponible: `/img/apple-touch-icon.svg` (cuadrado amarillo + "P" + punto rojo).

---

## Fase 1 — Estructura de la landing (template + controlador + i18n)

### 1.1 Reescribir `src/frontend/templates/pages/home.php`

Estructura BEM nueva, sin contenido anterior:

```html
<section class="landing">
  <div class="landing__inner">
    <img class="landing__logo" src="/img/apple-touch-icon.svg" ...>
    <h1 class="visually-hidden"><?= __('home.landing.title') ?></h1>
    <nav class="landing__nav" aria-label="<?= __('home.landing.aria') ?>">
      <a class="landing__btn" data-animate href="/menu?cat=pollos<?= $langSuffix ?>">
        <?= __('home.landing.pollos') ?>
      </a>
      <a class="landing__btn" data-animate href="/menu?cat=menus<?= $langSuffix ?>">
        <?= __('home.landing.combinados') ?>
      </a>
      <a class="landing__btn" data-animate href="/menu?cat=picapica<?= $langSuffix ?>">
        <?= __('home.landing.picapica') ?>
      </a>
    </nav>
  </div>
</section>
```

- `$langSuffix = LANG === 'ca' ? '' : '&lang=' . LANG;` (CA es el defecto, no necesita param).
- `data-animate` = hook reservado para las animaciones que definiremos después.
- Los enlaces son `<a>` reales (no `<button>`): funcionan sin JS, SEO y accesibilidad nativos.
- Se mantienen header y footer del layout; la landing llena el viewport entre ambos.

### 1.2 Simplificar `src/backend/pages/home.php`

- Eliminar el query de productos destacados (`Product` repo, fallback, array_slice).
- Mantener intactos `$meta` (title/desc/canonical/langs siguen siendo válidos para SEO).
- `$data` pasa a ser solo `['locale' => LANG]`.

### 1.3 Nuevas claves i18n (ca.php / es.php / en.php)

| Clave | CA | ES | EN |
|---|---|---|---|
| `home.landing.title` | Pit o Cuixa — Pollería a Torredembarra | (igual) | Pit o Cuixa — Rotisserie in Torredembarra |
| `home.landing.aria` | Què et ve de gust? | ¿Qué te apetece? | What are you craving? |
| `home.landing.pollos` | Pollos a l'ast | Pollos al ast | Rotisserie Chicken |
| `home.landing.combinados` | Plats combinats | Platos combinados | Set Menus |
| `home.landing.picapica` | Pica-pica | Picapica | Nibbles |

- **NO borrar `home.info.address` ni `home.info.phone`** (ambas pasan a usarlas el footer).
- Limpieza opcional: `home.hero.*`, `home.featured.*`, `home.info.title/hours`
  quedan huérfanas; se pueden retirar de los 3 locale files.

## Fase 2 — CSS de la landing (nuevo `public/css/pages/home.css`)

Bloque BEM `.landing`, mobile-first, solo tokens (DS-005 colores, DS-006 radios).

### 2.1 Layout

- **Fondo:** `.landing` con `background-color: var(--color-primary)` (amarillo corporativo
  a pantalla completa, requisito del usuario).
- **Móvil (base):** `.landing` con `min-height: calc(100dvh - 56px)` (resta el header
  sticky). `display: flex; flex-direction: column`. Logo arriba; `.landing__nav` con
  `flex: 1` reparte el alto restante entre los 3 botones en vertical (`flex: 1` cada uno,
  separados por `--space-sm/md`). Alto completo garantizado.
- **Escritorio (`@media (min-width: 1024px)`):** `.landing__nav` pasa a `flex-direction: row`;
  los 3 botones en horizontal con `flex: 1`, altura generosa (~40–50dvh). Logo centrado arriba.

### 2.2 Botones (contraste sobre fondo amarillo)

Al ser el fondo amarillo, los botones NO pueden ser amarillos. Paleta de contraste:

- Fondo `--color-on-surface` (casi negro `#1a1c1e`), texto
  `--color-surface-container-lowest` (blanco), `--radius`, `--shadow-md`,
  tipografía `--font-size-2xl/3xl` + `--font-weight-bold`.
- Hover/focus: `transform: translateY(-2px)` + subrayado/borde rojo
  (`--color-secondary`) animado.
- Botón central con variante acento (`.landing__btn--accent`): fondo
  `--color-secondary` (rojo), texto blanco — los dos colores corporativos presentes
  en la composición (amarillo fondo + rojo botón central + negro).
- Touch targets ≥ 56px en móvil (garantizado al repartir el viewport entre 3).
- Contraste WCAG verificado: `#1a1c1e` sobre `#f7e721` ≈ 15:1; blanco sobre
  `#d32f2f` ≈ 5:1 → ambos AA/AAA.

### 2.3 Animaciones (base provisional — se refinarán después)

- Entrada escalonada: `opacity: 0 → 1` + `translateY(12px → 0)` con delays
  (0ms / 100ms / 200ms) vía `.landing__btn:nth-child(n)`.
- `@media (prefers-reduced-motion: reduce)`: desactivar entrada y transforms.
- Todo con `transition` sobre tokens (`--transition-normal`) para iterar sin tocar HTML.

### 2.4 Registrar el CSS

En `layouts/default.php`, tras los `<link>` existentes, mismo patrón que admin:

```php
<?php if ($pageName === 'home'): ?>
<link rel="stylesheet" href="/css/pages/home.css">
<?php endif; ?>
```

## Fase 3 — Preselección de categoría en `/menu`

`public/js/menu-filter.js`, al final de `initMenuFilter()` (~10 líneas):

```js
const catParam = new URLSearchParams(window.location.search).get('cat');
if (catParam && catParam !== 'all') {
  const target = filterBar.querySelector(`[data-filter="${CSS.escape(catParam)}"]`);
  if (target) {
    setActiveTab(target);
    activeCategory = catParam;
    applyFilters();
  }
}
```

- Si el slug no existe (p. ej. `picapica` aún sin crear) → fallback silencioso a "Tot".
- Sin JS: `/menu` muestra todo (degradación aceptable, ya es el patrón actual).

## Fase 4 — Datos y verificación

### 4.1 Crear categoría `picapica` (manual, vía `/admin/categories`)

- Slug `picapica`; nombres: CA "Pica-pica", ES "Picapica", EN "Nibbles"; sort_order alto.
- Reasignar productos (patates bravas, boletes, olives, pa, all i oli…).
- **Ojo:** reasignar los quita de su categoría actual (FK única). Alternativa: duplicar
  productos. Decisión del dueño en el admin.
- El filter-bar lista TODAS las categorías (incluidas vacías) pero los grupos vacíos no
  se renderizan → reasignar al menos 1 producto antes de anunciar el botón.

### 4.2 Teléfono en el footer (obligatorio)

Al eliminar la sección info de la home, el teléfono desaparecería de la web. Añadir en
`partials/footer.php` una línea junto a la dirección:

```php
<p class="footer__phone">
  <a href="tel:<?= str_replace(' ', '', \Config::phone()) ?>"><?= __('home.info.phone') ?></a>
</p>
```

La clave `home.info.phone` ya existe en los 3 locales → se reutiliza (NO borrarla en la
limpieza de huérfanas).

### 4.3 Verificación manual

- Móvil 360px: fondo amarillo, logo + 3 botones verticales ocupan exactamente el alto del viewport.
- Desktop 1280px: 3 botones en horizontal sobre fondo amarillo.
- Contraste legible: texto blanco sobre botones oscuros/rojo sobre fondo amarillo.
- El teléfono aparece en el footer de todas las páginas y el enlace `tel:` funciona.
- Cada botón abre `/menu` con su tab activo y los productos filtrados.
- Teclado: Tab recorre botones con `:focus-visible`; Enter navega.
- `prefers-reduced-motion`: sin animaciones de entrada.
- 3 idiomas: etiquetas y sufijo `?lang=` correctos.
- `/menu?cat=inexistente` → muestra "Tot" sin errores.

## Impacto y ficheros

| Fichero | Cambio |
|---|---|
| `src/frontend/templates/pages/home.php` | Reescritura completa (~40 líneas) |
| `src/backend/pages/home.php` | Simplificar (quitar query de destacados) |
| `src/shared/i18n/{ca,es,en}.php` | +5 claves (limpieza opcional de huérfanas) |
| `public/css/pages/home.css` | **Nuevo** (~120 líneas) |
| `src/frontend/templates/layouts/default.php` | +3 líneas (link condicional) |
| `public/js/menu-filter.js` | +10 líneas (`?cat=`) |
| `src/frontend/templates/partials/footer.php` | +3 líneas (teléfono, obligatorio) |

Estimación: ~180 líneas → un solo PR, sin chained PRs.

## Fuera de alcance

- Animaciones finales de los botones (solo hooks/base provisional; se definen después).
- Rediseño de header, footer, `/menu` y tarjetas de producto.
- Logo definitivo (se reutiliza el SVG existente; un `logo.svg` dedicado sería otra tarea).
