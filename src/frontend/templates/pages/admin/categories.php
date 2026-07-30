<?php
/**
 * Pit o Cuixa — Admin Categories Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - categories: array of ALL category rows (including inactive)
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user       = $pageData['user'] ?? [];
$categories = $pageData['categories'] ?? [];
$csrfToken  = $pageData['csrf_token'] ?? '';
$lang       = $pageData['locale'] ?? LANG;
?>
<!-- ============================================================
     Admin Categories — Phase 3
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Categorías</h1>
            <div class="admin-header__actions">
                <a href="/admin" class="admin-header__back">← Dashboard</a>
            </div>
        </header>

        <!-- Alerts -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- Add Button -->
        <button class="admin-btn admin-btn--primary" data-create-btn aria-label="Crear nueva categoría">
            + Nueva Categoría
        </button>

        <!-- ── Drawer Overlay ─────────────────────────────────────────── -->
        <div class="admin-drawer__overlay" data-drawer-overlay hidden></div>

        <!-- ── Category Drawer ────────────────────────────────────────── -->
        <div class="admin-drawer" data-drawer role="dialog" aria-modal="true" aria-labelledby="drawer-title" hidden>
            <div class="admin-drawer__header">
                <h2 class="admin-drawer__title" id="drawer-title" data-drawer-title>Nueva Categoría</h2>
                <button class="admin-drawer__close" data-drawer-close aria-label="Cerrar">&times;</button>
            </div>

            <div class="admin-drawer__body">
                <form class="admin-form" data-category-form>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" data-field-id value="">
                    <input type="hidden" name="_method" data-field-method value="POST">

                    <div class="admin-form__grid">
                        <div class="admin-field">
                            <label class="admin-field__label" for="cat-slug">Slug *</label>
                            <input id="cat-slug" name="slug" class="admin-field__input" required
                                   pattern="[a-z0-9-]+" title="Minúsculas, números y guiones">
                        </div>

                        <div class="admin-field">
                            <label class="admin-field__label" for="cat-name-es">Nombre (ES) *</label>
                            <input id="cat-name-es" name="name_es" class="admin-field__input" required>
                        </div>

                        <div class="admin-field">
                            <label class="admin-field__label" for="cat-name-en">Name (EN) *</label>
                            <input id="cat-name-en" name="name_en" class="admin-field__input" required>
                        </div>

                        <div class="admin-field">
                            <label class="admin-field__label" for="cat-order">Orden</label>
                            <input id="cat-order" name="sort_order" type="number" min="0"
                                   class="admin-field__input" value="0">
                        </div>

                        <div class="admin-field">
                            <label class="admin-checkbox">
                                <input type="checkbox" name="is_active" value="1" checked>
                                Activo
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="admin-drawer__footer">
                <button type="button" class="admin-btn admin-btn--ghost" data-drawer-cancel>Cancelar</button>
                <button type="button" class="admin-btn admin-btn--primary" data-btn-submit>Guardar</button>
            </div>
        </div>

        <!-- ── Category List ──────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Todas las Categorías (<?= count($categories) ?>)</h2>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Slug</th>
                            <th>Nombre (ES)</th>
                            <th>Name (EN)</th>
                            <th>Orden</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-categories-tbody>
                        <?php if ($categories === []): ?>
                            <tr>
                                <td colspan="7" class="admin-table__empty">
                                    No hay categorías.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($categories as $cat): ?>
                            <tr data-category-id="<?= (int) $cat['id'] ?>">
                                <td><?= (int) $cat['id'] ?></td>
                                <td><?= htmlspecialchars($cat['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($cat['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($cat['name_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($cat['sort_order'] ?? 0) ?></td>
                                <td><?= !empty($cat['is_active']) ? '✓' : '✗' ?></td>
                                <td class="admin-table__actions">
                                    <button class="admin-btn-sm" data-edit-category="<?= (int) $cat['id'] ?>"
                                            data-slug="<?= htmlspecialchars($cat['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-name-es="<?= htmlspecialchars($cat['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-name-en="<?= htmlspecialchars($cat['name_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-sort-order="<?= (int) ($cat['sort_order'] ?? 0) ?>"
                                            data-is-active="<?= !empty($cat['is_active']) ? '1' : '0' ?>">
                                        Editar
                                    </button>
                                    <button class="admin-btn-sm admin-btn-sm--danger"
                                            data-delete-category="<?= (int) $cat['id'] ?>"
                                            data-name="<?= htmlspecialchars($cat['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script type="module">
/**
 * Admin Categories — Phase 3: CRUD via AJAX with in-place DOM updates.
 * Uses drawer and keyboard shortcuts.
 */
import {
    api, showToast, withLoading, AdminModal,
    validateForm, validateField,
    Drawer, insertTableRow, removeTableRow, updateTableRow, toggleEmptyState,
    initKeyboardShortcuts
} from '/js/admin.js';

const API_BASE = '/api/admin/categories';
const TBODY = document.querySelector('[data-categories-tbody]');

/** Escape HTML */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/** Build a category table row HTML from data */
function buildRow(cat) {
    return `<tr data-category-id="${cat.id}">
        <td>${cat.id}</td>
        <td>${escHtml(cat.slug)}</td>
        <td>${escHtml(cat.name_es)}</td>
        <td>${escHtml(cat.name_en)}</td>
        <td>${cat.sort_order || 0}</td>
        <td>${cat.is_active ? '✓' : '✗'}</td>
        <td class="admin-table__actions">
            <button class="admin-btn-sm" data-edit-category="${cat.id}"
                    data-slug="${escHtml(cat.slug)}"
                    data-name-es="${escHtml(cat.name_es)}"
                    data-name-en="${escHtml(cat.name_en)}"
                    data-sort-order="${cat.sort_order || 0}"
                    data-is-active="${cat.is_active ? '1' : '0'}">
                Editar
            </button>
            <button class="admin-btn-sm admin-btn-sm--danger"
                    data-delete-category="${cat.id}"
                    data-name="${escHtml(cat.name_es)}">
                Eliminar
            </button>
        </td>
    </tr>`;
}

/** Collect form data */
function getFormData(form) {
    return {
        slug: form.querySelector('[name="slug"]').value,
        name_es: form.querySelector('[name="name_es"]').value,
        name_en: form.querySelector('[name="name_en"]').value,
        sort_order: parseInt(form.querySelector('[name="sort_order"]').value) || 0,
        is_active: form.querySelector('[name="is_active"]').checked,
    };
}

/** Fill form with category data */
function fillForm(btn) {
    const form = document.querySelector('[data-category-form]');
    if (!form) return;

    const drawerTitle = document.querySelector('[data-drawer-title]');
    if (drawerTitle) drawerTitle.textContent = 'Editar Categoría';

    const submitBtn = document.querySelector('[data-btn-submit]');
    if (submitBtn) submitBtn.textContent = 'Actualizar';

    form.querySelector('[data-field-method]').value = 'PUT';
    form.querySelector('[data-field-id]').value = btn.dataset.editCategory;
    form.querySelector('[name="slug"]').value = btn.dataset.slug || '';
    form.querySelector('[name="name_es"]').value = btn.dataset.nameEs || '';
    form.querySelector('[name="name_en"]').value = btn.dataset.nameEn || '';
    form.querySelector('[name="sort_order"]').value = btn.dataset.sortOrder || '0';
    form.querySelector('[name="is_active"]').checked = btn.dataset.isActive === '1';
}

/** Reset form for create */
function resetForm() {
    const form = document.querySelector('[data-category-form]');
    if (!form) return;

    const drawerTitle = document.querySelector('[data-drawer-title]');
    if (drawerTitle) drawerTitle.textContent = 'Nueva Categoría';

    const submitBtn = document.querySelector('[data-btn-submit]');
    if (submitBtn) submitBtn.textContent = 'Guardar';

    form.querySelector('[data-field-method]').value = 'POST';
    form.querySelector('[data-field-id]').value = '';
    form.reset();
    form.querySelector('[name="is_active"]').checked = true;
}

/** Update section title count */
function updateCount() {
    const rows = TBODY.querySelectorAll('tr:not(.admin-table__empty)');
    const title = document.querySelector('.admin-section__title');
    if (title) {
        title.textContent = `Todas las Categorías (${rows.length})`;
    }
}

// ── Drawer ────────────────────────────────────────────────────────
const drawer = new Drawer({
    drawer: '[data-drawer]',
    overlay: '[data-drawer-overlay]',
});

// ── Create Button ────────────────────────────────────────────────
document.querySelector('[data-create-btn]')?.addEventListener('click', () => {
    resetForm();
    drawer.open('Nueva Categoría');
});

// ── Submit via Drawer Footer ──────────────────────────────────────
document.querySelector('[data-btn-submit]')?.addEventListener('click', async () => {
    const form = document.querySelector('[data-category-form]');
    if (!form) return;

    if (!validateForm(form)) {
        const firstInvalid = form.querySelector('.admin-field--invalid input, .admin-field--invalid select');
        if (firstInvalid) firstInvalid.focus();
        showToast('Corrige los campos marcados en rojo', 'error');
        return;
    }

    const submitBtn = document.querySelector('[data-btn-submit]');
    const id = form.querySelector('[data-field-id]').value;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_BASE}/${id}` : API_BASE;
    const data = getFormData(form);

    await withLoading(submitBtn, async () => {
        const json = await api(method, url, data);

        if (json.error) {
            const msg = json.errors ? json.errors.join('; ') : (json.message || 'Error');
            showToast(msg, 'error');
            return;
        }

        if (method === 'POST') {
            // Create: append new row
            const cat = json.data;
            const rowHtml = buildRow(cat);
            insertTableRow(TBODY, rowHtml);
            showToast('Categoría creada', 'success');
            drawer.close();
            updateCount();
        } else {
            // Update: update row in-place
            const cat = json.data;
            const existingRow = TBODY.querySelector(`[data-category-id="${id}"]`);
            if (existingRow) {
                updateTableRow(existingRow, buildRow(cat));
            }
            showToast('Categoría actualizada', 'success');
            drawer.close();
        }
    });
});

// ── Event Delegation for Edit/Delete ─────────────────────────────
TBODY?.addEventListener('click', (e) => {
    // Edit
    const editBtn = e.target.closest('[data-edit-category]');
    if (editBtn) {
        fillForm(editBtn);
        drawer.open('Editar Categoría');
        return;
    }

    // Delete
    const deleteBtn = e.target.closest('[data-delete-category]');
    if (deleteBtn) {
        const id = deleteBtn.dataset.deleteCategory;
        const name = deleteBtn.dataset.name || 'esta categoría';
        const modal = new AdminModal();

        modal.open('Eliminar categoría', `¿Eliminar "${name}"?`, async () => {
            modal.close();
            const json = await api('DELETE', `${API_BASE}/${id}`);

            if (json.error) {
                showToast(json.message || 'Error al eliminar', 'error');
            } else {
                const row = TBODY.querySelector(`[data-category-id="${id}"]`);
                if (row) {
                    await removeTableRow(row);
                    toggleEmptyState(TBODY, 7, 'No hay categorías.');
                    updateCount();
                }
                showToast('Categoría eliminada', 'success');
            }
        });
    }
});

// ── Keyboard Shortcuts ───────────────────────────────────────────
initKeyboardShortcuts({
    escape: () => {
        const drawerEl = document.querySelector('[data-drawer]');
        if (drawerEl && !drawerEl.hidden) drawer.close();
    },
    submit: () => {
        const drawerEl = document.querySelector('[data-drawer]');
        if (drawerEl && !drawerEl.hidden) {
            document.querySelector('[data-btn-submit]')?.click();
        }
    },
    create: () => {
        document.querySelector('[data-create-btn]')?.click();
    },
    help: () => {
        showToast('Atajos: Esc=Cerrar, Ctrl+Enter=Guardar, Ctrl+N=Nuevo', 'info', 5000);
    },
});

// ── Form Field Validation on Blur ───────────────────────────────
const catForm = document.querySelector('[data-category-form]');
if (catForm) {
    catForm.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select').forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => {
            const wrapper = field.closest('.admin-field');
            if (wrapper) {
                wrapper.classList.remove('admin-field--invalid');
            }
        });
    });
}

// ── Drawer close resets validation ───────────────────────────────
drawer.onClose = () => {
    document.querySelectorAll('.admin-field--invalid').forEach(el => {
        el.classList.remove('admin-field--invalid');
    });
};
</script>
