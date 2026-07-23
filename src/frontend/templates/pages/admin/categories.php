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
     Admin Categories
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Categorías</h1>
            <a href="/admin" class="admin-header__back">← Dashboard</a>
        </header>

        <!-- Alerts -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- Add Button -->
        <button class="admin-btn admin-btn--primary" data-toggle-form="category">
            + Nueva Categoría
        </button>

        <!-- ── Category Form ──────────────────────────────────────────── -->
        <div class="admin-form-panel" data-form-panel="category" hidden>
            <h2 class="admin-form-panel__title" data-form-title>Nueva Categoría</h2>

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

                <div class="admin-form__actions">
                    <button type="submit" class="admin-btn admin-btn--primary" data-btn-submit>Guardar</button>
                    <button type="button" class="admin-btn admin-btn--ghost" data-cancel-form="category">Cancelar</button>
                </div>
            </form>
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
                    <tbody>
                        <?php if ($categories === []): ?>
                            <tr>
                                <td colspan="7" class="admin-table__empty">
                                    No hay categorías.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($categories as $cat): ?>
                            <tr>
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
 * Admin Categories — CRUD via AJAX.
 */
const API_BASE = '/api/admin/categories';

async function api(method, url, body = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const headers = {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    };

    const res = await fetch(url, {
        method,
        headers,
        body: body ? JSON.stringify(body) : null,
        credentials: 'same-origin'
    });
    return res.json();
}

function showAlert(msg, type) {
    const sel = type === 'success' ? '[data-alert-success]' : '[data-alert-error]';
    const el = document.querySelector(sel);
    if (el) {
        el.textContent = msg;
        el.hidden = false;
        setTimeout(() => { el.hidden = true; }, 5000);
    }
}

// Toggle form
document.querySelectorAll('[data-toggle-form]').forEach(btn => {
    btn.addEventListener('click', () => {
        const panel = document.querySelector('[data-form-panel="category"]');
        if (panel) {
            panel.hidden = !panel.hidden;
            if (!panel.hidden) {
                panel.querySelector('[data-form-title]').textContent = 'Nueva Categoría';
                panel.querySelector('[data-field-method]').value = 'POST';
                panel.querySelector('[data-btn-submit]').textContent = 'Guardar';
                panel.querySelector('[data-category-form]').reset();
                panel.querySelector('[data-field-id]').value = '';
            }
        }
    });
});

document.querySelector('[data-cancel-form="category"]')?.addEventListener('click', () => {
    document.querySelector('[data-form-panel="category"]').hidden = true;
});

// Submit form
document.querySelector('[data-category-form]')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const id = form.querySelector('[data-field-id]').value;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_BASE}/${id}` : API_BASE;

    const data = {
        slug: form.querySelector('[name="slug"]').value,
        name_es: form.querySelector('[name="name_es"]').value,
        name_en: form.querySelector('[name="name_en"]').value,
        sort_order: parseInt(form.querySelector('[name="sort_order"]').value) || 0,
        is_active: form.querySelector('[name="is_active"]').checked,
    };

    const json = await api(method, url, JSON.stringify(data));

    if (json.error) {
        const msg = json.errors ? json.errors.join('; ') : (json.message || 'Error');
        showAlert(msg, 'error');
    } else {
        showAlert(id ? 'Categoría actualizada' : 'Categoría creada', 'success');
        setTimeout(() => window.location.reload(), 1000);
    }
});

// Edit
document.querySelectorAll('[data-edit-category]').forEach(btn => {
    btn.addEventListener('click', () => {
        const panel = document.querySelector('[data-form-panel="category"]');
        panel.hidden = false;
        panel.querySelector('[data-form-title]').textContent = 'Editar Categoría';
        panel.querySelector('[data-field-method]').value = 'PUT';
        panel.querySelector('[data-btn-submit]').textContent = 'Actualizar';
        panel.querySelector('[data-field-id]').value = btn.dataset.editCategory;
        panel.querySelector('[name="slug"]').value = btn.dataset.slug || '';
        panel.querySelector('[name="name_es"]').value = btn.dataset.nameEs || '';
        panel.querySelector('[name="name_en"]').value = btn.dataset.nameEn || '';
        panel.querySelector('[name="sort_order"]').value = btn.dataset.sortOrder || '0';
        panel.querySelector('[name="is_active"]').checked = btn.dataset.isActive === '1';
    });
});

// Delete
document.querySelectorAll('[data-delete-category]').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.deleteCategory;
        const name = btn.dataset.name || 'esta categoría';
        if (!confirm(`¿Eliminar "${name}"?`)) return;

        const json = await api('DELETE', `${API_BASE}/${id}`);

        if (json.error) {
            showAlert(json.message || 'Error al eliminar', 'error');
        } else {
            showAlert('Categoría eliminada', 'success');
            setTimeout(() => window.location.reload(), 1000);
        }
    });
});
</script>
