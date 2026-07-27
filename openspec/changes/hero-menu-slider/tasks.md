# Tasks: Hero Menu Slider

## Review Workload Forecast

- Estimated changed lines: ~350-400
- `400-line budget risk: Low`
- `Chained PRs recommended: No`
- `Decision needed before apply: No`
- `Chain strategy: pending`

## Grupo 1: Base de datos + Settings Repository

- [x] 1.1 Añadir tabla `settings` a `schema.sql` + migración `002_settings.sql`
- [x] 1.2 Crear `Settings` repository con `get()`/`set()`/`ensureSchema()`
- [x] 1.3 Registrar rutas API para Settings (`GET/PUT /api/admin/settings`)

## Grupo 2: Admin Settings Page

- [x] 2.1 Crear controller `Admin/Settings.php`
- [x] 2.2 Crear API controller `AdminSettings.php`
- [x] 2.3 Crear template `admin/settings.php` con toggle + image count
- [x] 2.4 Añadir link "Settings" al admin nav
- [x] 2.5 Añadir strings i18n para Settings (ca/es/en)

## Grupo 3: Menu Page Hero

- [x] 3.1 Modificar `Menu` controller: pasar `slider_enabled` e `images` al template
- [x] 3.2 Modificar `menu.php` template: reemplazar hero con slider condicional
- [x] 3.3 Añadir fallback CSS decorativo (gradient hero) + slider CSS
- [x] 3.4 Cargar `menu-slider.css` condicionalmente en `default.php`

## Grupo 4: Slider JavaScript

- [x] 4.1 Crear `menu-slider.js` ESM module (autoplay 5s, swipe, keyboard, pause on interaction, reduced motion)
- [x] 4.2 Importar e init en `main.js` con guard `[data-menu-slider]`

## Grupo 5: Slider CSS

- [x] 5.1 Cachear slider CSS en Service Worker

## Grupo 6: Verificación

- [x] 6.1 Verificar PHP syntax check en todos los archivos modificados
- [x] 6.2 Crear directorio `public/img/menu-slider/.gitkeep`
- [ ] 6.3 Testing manual
