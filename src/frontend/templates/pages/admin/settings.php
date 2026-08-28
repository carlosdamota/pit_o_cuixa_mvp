<?php
/**
 * Pit o Cuixa — Admin Settings Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - menu_slider_enabled: '0' or '1'
 *   - company_address: string ('' when unset)
 *   - company_phone: primary phone (falls back to Config::phone())
 *   - company_whatsapp: secondary mobile ('' when unset)
 *   - image_count: int
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user                  = $pageData['user'] ?? [];
$sliderEnabled         = $pageData['menu_slider_enabled'] ?? '0';
$companyAddress        = (string) ($pageData['company_address'] ?? '');
$companyPhone          = (string) ($pageData['company_phone'] ?? '');
$companyWhatsapp       = (string) ($pageData['company_whatsapp'] ?? '');
$imageCount            = (int) ($pageData['image_count'] ?? 0);
$csrfToken             = $pageData['csrf_token'] ?? '';
$lang                  = $pageData['locale'] ?? LANG;
?>
<!-- ============================================================
     Admin Settings
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Ajustes del Sistema</h1>
            <div class="admin-header__actions">
                <a href="/pitocuixa" class="admin-header__back">← Volver al Dashboard</a>
            </div>
        </header>

        <!-- ── Alerts ────────────────────────────────────────────────── -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- ── Slider Toggle ─────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Carrusel Visual de la Carta</h2>

            <div class="admin-field">
                <label class="admin-field__label" for="menu-slider-toggle">
                    Activar visor interactivo de imágenes en la carta pública
                </label>
                <div class="admin-field__toggle">
                    <input type="checkbox"
                           id="menu-slider-toggle"
                           class="admin-toggle"
                           data-setting="menu_slider_enabled"
                           value="1"
                           <?= $sliderEnabled === '1' ? 'checked' : '' ?>
                           aria-describedby="slider-hint">
                    <label for="menu-slider-toggle" class="admin-toggle__visual" aria-hidden="true"></label>
                </div>
                <p id="slider-hint" class="admin-field__hint">
                    Muestra un carrusel dinámico de fotos de platos en la parte superior del menú público.
                </p>
            </div>

            <!-- ── Image Count Info ────────────────────────────────────── -->
            <div class="admin-field">
                <span class="admin-field__label">Imágenes de Productos Disponibles</span>
                <p class="admin-field__value">
                    <?php if ($imageCount > 0): ?>
                        <?= sprintf('%d imágenes cargadas en el sistema', $imageCount) ?>
                    <?php else: ?>
                        No hay imágenes cargadas actualmente
                    <?php endif; ?>
                </p>
                <p class="admin-field__hint">
                    Las imágenes se asignan editando cada producto en la sección de Productos.
                </p>
            </div>
        </section>

        <!-- ── Company Data ─────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Datos de la Empresa</h2>

            <form data-company-form>
                <div class="admin-field">
                    <label class="admin-field__label" for="company-address">
                        Dirección
                    </label>
                    <input type="text"
                           id="company-address"
                           name="company_address"
                           class="admin-field__input"
                           data-company-input="company_address"
                           value="<?= htmlspecialchars($companyAddress, ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="200"
                           autocomplete="off">
                    <p class="admin-field__hint">
                        Se muestra en el pie de página de la web. Si lo dejas vacío se usa el texto por defecto.
                    </p>
                </div>

                <div class="admin-field">
                    <label class="admin-field__label" for="company-phone">
                        Teléfono principal (reservas)
                    </label>
                    <input type="tel"
                           id="company-phone"
                           name="company_phone"
                           class="admin-field__input"
                           data-company-input="company_phone"
                           value="<?= htmlspecialchars($companyPhone, ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="20"
                           placeholder="+34 977 64 20 10"
                           autocomplete="off">
                    <p class="admin-field__hint">
                        Usado en el botón «Reservar ahora» y en el pie de página. Formato: +34 977 64 20 10
                    </p>
                </div>

                <div class="admin-field">
                    <label class="admin-field__label" for="company-whatsapp">
                        Móvil de WhatsApp (opcional)
                    </label>
                    <input type="tel"
                           id="company-whatsapp"
                           name="company_whatsapp"
                           class="admin-field__input"
                           data-company-input="company_whatsapp"
                           value="<?= htmlspecialchars($companyWhatsapp, ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="20"
                           placeholder="+34 612 34 56 78"
                           autocomplete="off">
                    <p class="admin-field__hint">
                        Si tienes un móvil secundario para WhatsApp, indícalo aquí. Si lo dejas vacío se usa el teléfono principal.
                    </p>
                </div>

                <button type="submit" class="admin-btn admin-btn--primary" data-company-submit>
                    Guardar cambios
                </button>
            </form>
        </section>
    </main>
</div>

<script type="module">
/**
 * Settings page toggle handler.
 * Sends PUT /api/admin/settings on toggle change.
 */
import { api } from '/js/admin.js<?= assetVersion('/js/admin.js') ?>';

const toggle = document.getElementById('menu-slider-toggle');

if (toggle) {
    toggle.addEventListener('change', async () => {
        const enabled = toggle.checked ? '1' : '0';
        const alertSuccess = document.querySelector('[data-alert-success]');
        const alertError = document.querySelector('[data-alert-error]');

        // Hide previous alerts
        if (alertSuccess) alertSuccess.hidden = true;
        if (alertError) alertError.hidden = true;

        try {
            const result = await api('PUT', '/api/pitocuixa/settings', {
                menu_slider_enabled: enabled,
            });

            if (result.error) {
                if (alertError) {
                    alertError.textContent = result.errors?.join(', ') || 'Error al guardar los ajustes';
                    alertError.hidden = false;
                }
                // Revert toggle
                toggle.checked = !toggle.checked;
                return;
            }

            if (alertSuccess) {
                alertSuccess.textContent = 'Ajustes guardados correctamente';
                alertSuccess.hidden = false;

                // Auto-hide after 3 seconds
                setTimeout(() => { alertSuccess.hidden = true; }, 3000);
            }
        } catch (err) {
            if (alertError) {
                alertError.textContent = 'Network error: ' + err.message;
                alertError.hidden = false;
            }
            toggle.checked = !toggle.checked;
        }
    });
}

/**
 * Company data form handler.
 * Sends PUT /api/admin/settings with all company fields at once.
 */
const companyForm = document.querySelector('[data-company-form]');

if (companyForm) {
    companyForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const alertSuccess = document.querySelector('[data-alert-success]');
        const alertError = document.querySelector('[data-alert-error]');
        const submitBtn = companyForm.querySelector('[data-company-submit]');

        const payload = {};
        companyForm.querySelectorAll('[data-company-input]').forEach((input) => {
            payload[input.dataset.companyInput] = input.value.trim();
        });

        if (alertSuccess) alertSuccess.hidden = true;
        if (alertError) alertError.hidden = true;
        if (submitBtn) submitBtn.disabled = true;

        try {
            const result = await api('PUT', '/api/pitocuixa/settings', payload);

            if (result.error) {
                if (alertError) {
                    alertError.textContent = result.errors?.join('. ') || 'Error al guardar los ajustes';
                    alertError.hidden = false;
                }
                return;
            }

            if (alertSuccess) {
                alertSuccess.textContent = 'Datos de la empresa guardados correctamente';
                alertSuccess.hidden = false;
                setTimeout(() => { alertSuccess.hidden = true; }, 3000);
            }
        } catch (err) {
            if (alertError) {
                alertError.textContent = 'Network error: ' + err.message;
                alertError.hidden = false;
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}
</script>
