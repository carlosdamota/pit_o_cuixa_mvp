<?php
/**
 * Pit o Cuixa — Admin Import/Export Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user      = $pageData['user'] ?? [];
$csrfToken = $pageData['csrf_token'] ?? '';
$lang      = $pageData['locale'] ?? LANG;
?>
<!-- ============================================================
     Admin Import / Export
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Importar / Exportar</h1>
            <a href="/admin" class="admin-header__back">← Dashboard</a>
        </header>

        <!-- ── Alert region ─────────────────────────────────────────── -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- ═══════════════════════════════════════════════════════════
             Import Section
             ═══════════════════════════════════════════════════════════ -->
        <section class="admin-section">
            <h2 class="admin-section__title">Importar productos desde CSV</h2>

            <div class="admin-card">
                <p class="admin-card__desc" style="margin-bottom:var(--space-md);color:var(--color-text-muted);font-size:var(--font-size-sm);">
                    Sube un archivo CSV con las columnas:
                    <code style="display:block;margin-top:var(--space-xs);padding:var(--space-sm);background:var(--color-surface-container-low);border-radius:var(--radius-sm);font-size:var(--font-size-xs);">
                        slug, name_es, name_en, description_es, description_en, price,
                        category_id, image_url, last_shop_url, sort_order, is_active, is_featured
                    </code>
                </p>

                <form id="import-form" method="POST" action="/api/admin/import" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="admin-field">
                        <label class="admin-field__label" for="import-file">
                            Archivo CSV
                        </label>
                        <input id="import-file"
                               name="file"
                               type="file"
                               accept=".csv"
                               class="admin-field__input"
                               required
                               style="padding:var(--space-sm);font-family:var(--font-family);">
                    </div>

                    <div id="import-progress" class="admin-alert" hidden role="status">
                        <span data-import-status>Procesando...</span>
                        <span data-import-count></span>
                    </div>

                    <div class="admin-form__actions">
                        <button type="submit" class="admin-btn admin-btn--primary" id="import-btn">
                            Importar productos
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════
             Export Section
             ═══════════════════════════════════════════════════════════ -->
        <section class="admin-section">
            <h2 class="admin-section__title">Exportar datos</h2>

            <div class="admin-card">
                <p style="margin-bottom:var(--space-md);color:var(--color-text-muted);font-size:var(--font-size-sm);">
                    Descarga los datos en formato CSV para su análisis o respaldo.
                </p>

                <div class="admin-header__actions">
                    <a href="/api/admin/export?type=products" class="admin-btn admin-btn--primary" data-export-products>
                        Exportar productos
                    </a>
                    <button type="button" class="admin-btn admin-btn--secondary" data-export-categories disabled title="Próximamente">
                        Exportar categorías
                    </button>
                </div>
            </div>
        </section>
    </main>
</div>

<script type="module">
/**
 * Admin Import/Export — Upload CSV and download exports.
 */
import { showAlert, showToast, withLoading, getCsrfToken } from '/js/admin.js<?= assetVersion('/js/admin.js') ?>';

// ── Import ──────────────────────────────────────────────────────
const importForm  = document.getElementById('import-form');
const importBtn   = document.getElementById('import-btn');
const fileInput   = importForm?.querySelector('[name="file"]');
const progress    = document.getElementById('import-progress');
const statusEl    = progress?.querySelector('[data-import-status]');
const countEl     = progress?.querySelector('[data-import-count]');

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

// Validate file on change
fileInput?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Check file size
    if (file.size > MAX_FILE_SIZE) {
        showToast('El archivo supera el límite de 5MB', 'error');
        fileInput.value = ''; // Clear invalid file
        return;
    }

    // Check file type
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showToast('Solo se aceptan archivos CSV', 'error');
        fileInput.value = ''; // Clear invalid file
        return;
    }
});

importForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const file = fileInput?.files[0];
    if (!file) {
        showToast('Selecciona un archivo CSV', 'error');
        return;
    }

    // Double-check size before submit
    if (file.size > MAX_FILE_SIZE) {
        showToast('El archivo supera el límite de 5MB', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    // Show progress region
    if (progress) progress.hidden = false;
    if (statusEl) statusEl.textContent = 'Subiendo archivo...';
    if (countEl) countEl.textContent = '';

    await withLoading(importBtn, async () => {
        try {
            const res = await fetch('/api/admin/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': getCsrfToken(),
                },
                body: formData,
                credentials: 'same-origin',
            });

            const json = await res.json();

            if (json.error) {
                const msg = json.message || 'Error al importar';
                showAlert(msg, 'error');
                if (statusEl) statusEl.textContent = 'Error';
                if (progress) progress.className = 'admin-alert admin-alert--error';
            } else {
                const imported = json.data?.imported ?? 0;
                const errors   = json.data?.errors ?? [];
                const msg = `Importación completada: ${imported} productos procesados`;
                showAlert(msg, 'success');
                if (statusEl) statusEl.textContent = 'Completado';
                if (countEl) countEl.textContent = `${imported} productos`;
                if (progress) progress.className = 'admin-alert admin-alert--success';

                if (errors.length > 0) {
                    showAlert(`Se encontraron ${errors.length} errores. Revisa los detalles.`, 'error');
                    console.warn('Import errors:', errors);
                }

                // Reset file input
                const fileInput = importForm.querySelector('[name="file"]');
                if (fileInput) fileInput.value = '';
            }
        } catch (err) {
            showAlert('Error de conexión al importar', 'error');
            if (statusEl) statusEl.textContent = 'Error de conexión';
            if (progress) progress.className = 'admin-alert admin-alert--error';
        }
    });
});

// ── Export ───────────────────────────────────────────────────────
document.querySelector('[data-export-products]')?.addEventListener('click', (e) => {
    // Native navigation triggers download; show a brief toast
    showToast('Descargando productos...', 'info', 2000);
});
</script>
