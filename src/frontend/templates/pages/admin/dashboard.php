<?php
/**
 * Pit o Cuixa — Admin Dashboard Template
 *
 * Variables from $pageData:
 *   - total_products: int
 *   - total_categories: int
 *   - featured_products: int
 *   - per_category: array of [name_es, name_en, cnt]
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$totalProducts   = $pageData['total_products'] ?? 0;
$totalCategories = $pageData['total_categories'] ?? 0;
$featuredProducts = $pageData['featured_products'] ?? 0;
$perCategory     = $pageData['per_category'] ?? [];
$csrfToken       = $pageData['csrf_token'] ?? '';
$twofaEnabled    = $pageData['twofa_enabled'] ?? false;
$lang            = $pageData['locale'] ?? LANG;
?>
<!-- ============================================================
     Admin Dashboard
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Panel de Administración</h1>
        </header>

        <!-- ── Stats Cards ─────────────────────────────────────────────── -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <span class="admin-stat-card__value"><?= $totalProducts ?></span>
                <span class="admin-stat-card__label">Productos</span>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-card__value"><?= $totalCategories ?></span>
                <span class="admin-stat-card__label">Categorías</span>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-card__value"><?= $featuredProducts ?></span>
                <span class="admin-stat-card__label">Destacados</span>
            </div>
        </div>

        <!-- ── Products per Category ───────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Productos por Categoría</h2>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perCategory as $cat): 
                            $catName = !empty($cat["name_{$lang}"]) ? $cat["name_{$lang}"] : (!empty($cat['name_ca']) ? $cat['name_ca'] : (!empty($cat['name_es']) ? $cat['name_es'] : ($cat['name_en'] ?? '')));
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $cat['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Two-Factor Authentication (TOTP) ──────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Autenticación en dos pasos (2FA)</h2>
            <p class="admin-section__desc">
                Protege el acceso de administrador con una segunda factor (TOTP) mediante
                una app autenticadora. Se generarán códigos de respaldo de un solo uso.
            </p>

            <?php if ($twofaEnabled): ?>
                <p class="admin-alert admin-alert--success" role="status">
                    2FA activado correctamente.
                </p>
                <button type="button" id="btn-2fa-start" class="admin-btn admin-btn--secondary">
                    Regenerar secreto y códigos de respaldo
                </button>
            <?php else: ?>
                <button type="button" id="btn-2fa-start" class="admin-btn admin-btn--primary">
                    Activar autenticación en dos pasos
                </button>
            <?php endif; ?>
        </section>

        <!-- 2FA enrollment modal -->
        <div id="twofa-modal" class="admin-modal" hidden>
            <div class="admin-modal__backdrop" data-2fa-close></div>
            <div class="admin-modal__dialog" role="dialog" aria-modal="true">
                <button type="button" class="admin-modal__close" data-2fa-close aria-label="Cerrar">×</button>

                <h3 class="admin-modal__title">Configurar 2FA</h3>

                <div data-2fa-status class="admin-2fa-status" role="status"></div>

                <!-- Step 1: scan -->
                <div data-2fa-step-scan>
                    <p>Escanea este código QR con tu app autenticadora:</p>
                    <div id="twofa-qrcode" class="admin-2fa-qr"></div>
                    <p>O introduce manualmente el secreto (base32):</p>
                    <code id="twofa-secret" class="admin-2fa-secret"></code>

                    <p class="admin-2fa-backup-title">Códigos de respaldo (guárdalos ahora):</p>
                    <ul id="twofa-backup" class="admin-2fa-backup"></ul>
                </div>

                <!-- Step 2: confirm -->
                <div data-2fa-step-confirm hidden>
                    <p>Introduce el código de 6 dígitos que muestra tu app para confirmar:</p>
                    <input id="twofa-code" type="text" inputmode="numeric" maxlength="6"
                           class="admin-field__input" autocomplete="one-time-code">
                    <div data-2fa-confirm-error class="admin-login__error" role="alert" hidden></div>
                </div>

                <div class="admin-modal__actions">
                    <button type="button" id="twofa-cancel" class="admin-btn admin-btn--ghost" data-2fa-close>
                        Cancelar
                    </button>
                    <button type="button" id="twofa-confirm" class="admin-btn admin-btn--primary" hidden>
                        Confirmar y activar
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Manual Menu Sync ────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Sincronización de Carta</h2>
            <p class="admin-section__desc">
                Pulsa el botón para sincronizar manualmente la carta con el proveedor externo.
            </p>
            <button
                type="button"
                id="btn-update-menu"
                class="admin-btn admin-btn--primary"
            >
                Actualizar carta
            </button>
        </section>
    </main>
</div>

<script type="module">
    import { api, showToast, withLoading } from '/js/admin.js<?= assetVersion('/js/admin.js') ?>';

    const btn = document.getElementById('btn-update-menu');
    if (!btn) throw new Error('Update-menu button not found');

    btn.addEventListener('click', () => {
        withLoading(btn, async () => {
            try {
                const data = await api('POST', '/api/update-menu');

                if (data?.status === 'ok') {
                    const t = data.translated || {};
                    const parts = [];
                    if (t.categories) parts.push(`${t.categories} categorías`);
                    if (t.products) parts.push(`${t.products} productos`);
                    const detail = parts.length ? ` — ${parts.join(', ')}` : '';
                    showToast(`Carta actualizada correctamente${detail}`, 'success');
                } else {
                    showToast('No se pudo sincronizar la carta', 'error');
                }
            } catch (err) {
                showToast('Error de red al sincronizar la carta', 'error');
            }
        });
    });
</script>

<!-- 2FA enrollment (qrcode lib from unpkg, allowed by CSP) -->
<script src="https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js"></script>
<script type="module">
    import { api, showToast } from '/js/admin.js<?= assetVersion('/js/admin.js') ?>';

    const startBtn = document.getElementById('btn-2fa-start');
    const modal    = document.getElementById('twofa-modal');
    const statusEl = document.querySelector('[data-2fa-status]');
    const stepScan = document.querySelector('[data-2fa-step-scan]');
    const stepConfirm = document.querySelector('[data-2fa-step-confirm]');
    const confirmBtn  = document.getElementById('twofa-confirm');
    const confirmErr  = document.querySelector('[data-2fa-confirm-error]');

    function openModal() {
        modal.hidden = false;
        stepScan.hidden = false;
        stepConfirm.hidden = true;
        confirmBtn.hidden = true;
        statusEl.textContent = 'Generando secreto…';
        enrollStart();
    }

    function closeModal() {
        modal.hidden = true;
    }

    document.querySelectorAll('[data-2fa-close]').forEach((el) =>
        el.addEventListener('click', closeModal)
    );

    startBtn?.addEventListener('click', openModal);

    async function enrollStart() {
        try {
            const json = await api('POST', '/api/auth/2fa-enroll');
            if (json?.error) {
                statusEl.textContent = json.message || 'No se pudo iniciar el enrolamiento.';
                return;
            }
            const data = json.data ?? {};
            statusEl.textContent = '';

            // QR code (qrcodejs global from unpkg)
            const qr = document.getElementById('twofa-qrcode');
            if (qr && typeof QRCode !== 'undefined') {
                qr.innerHTML = '';
                new QRCode(qr, {
                    text: data.provisioning_uri,
                    width: 200,
                    height: 200,
                    colorDark: '#1a1a1a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M,
                });
            }
            document.getElementById('twofa-secret').textContent = data.secret_base32 ?? '';

            const list = document.getElementById('twofa-backup');
            list.innerHTML = '';
            (data.backup_codes ?? []).forEach((c) => {
                const li = document.createElement('li');
                li.textContent = c;
                list.appendChild(li);
            });

            // Reveal confirm step
            stepConfirm.hidden = false;
            confirmBtn.hidden = false;
        } catch (err) {
            statusEl.textContent = 'Error de red al iniciar el enrolamiento.';
        }
    }

    confirmBtn?.addEventListener('click', async () => {
        const code = document.getElementById('twofa-code').value.trim();
        confirmErr.hidden = true;

        try {
            const json = await api('POST', '/api/auth/2fa-enroll', { code });
            if (json?.error) {
                confirmErr.textContent = json.message || 'Código incorrecto.';
                confirmErr.hidden = false;
                return;
            }
            showToast('2FA activado correctamente', 'success');
            closeModal();
            window.location.reload();
        } catch (err) {
            confirmErr.textContent = 'Error de red al confirmar.';
            confirmErr.hidden = false;
        }
    });
</script>
