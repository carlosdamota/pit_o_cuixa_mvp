<?php
/**
 * Pit o Cuixa — Admin Settings Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - menu_slider_enabled: '0' or '1'
 *   - image_count: int
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user                  = $pageData['user'] ?? [];
$sliderEnabled         = $pageData['menu_slider_enabled'] ?? '0';
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
            <h1 class="admin-header__title"><?= __('admin.settings.title') ?></h1>
            <div class="admin-header__actions">
                <a href="/admin" class="admin-header__back">← <?= __('admin.dashboard') ?></a>
            </div>
        </header>

        <!-- ── Alerts ────────────────────────────────────────────────── -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- ── Slider Toggle ─────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title"><?= __('admin.settings.slider') ?></h2>

            <div class="admin-field">
                <label class="admin-field__label" for="menu-slider-toggle">
                    <?= __('admin.settings.slider_label') ?>
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
                    <?= __('admin.settings.slider_hint') ?>
                </p>
            </div>

            <!-- ── Image Count Info ────────────────────────────────────── -->
            <div class="admin-field">
                <span class="admin-field__label"><?= __('admin.settings.images') ?></span>
                <p class="admin-field__value">
                    <?php if ($imageCount > 0): ?>
                        <?= sprintf(__('admin.settings.images_count'), $imageCount) ?>
                    <?php else: ?>
                        <?= __('admin.settings.images_none') ?>
                    <?php endif; ?>
                </p>
                <p class="admin-field__hint">
                    <?= __('admin.settings.images_hint') ?>
                </p>
            </div>
        </section>
    </main>
</div>

<script type="module">
/**
 * Settings page toggle handler.
 * Sends PUT /api/admin/settings on toggle change.
 */
import { api } from '/js/admin.js';

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
            const result = await api('PUT', '/api/admin/settings', {
                menu_slider_enabled: enabled,
            });

            if (result.error) {
                if (alertError) {
                    alertError.textContent = result.errors?.join(', ') || 'Error saving setting';
                    alertError.hidden = false;
                }
                // Revert toggle
                toggle.checked = !toggle.checked;
                return;
            }

            if (alertSuccess) {
                alertSuccess.textContent = '<?= __('admin.settings.saved') ?>';
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
</script>
