<?php
/**
 * Pit o Cuixa — Admin Products Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - products: array of product rows (bilingual)
 *   - categories: array of category rows
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user       = $pageData['user'] ?? [];
$products   = $pageData['products'] ?? [];
$categories = $pageData['categories'] ?? [];
$csrfToken  = $pageData['csrf_token'] ?? '';
$lang       = $pageData['locale'] ?? LANG;
?>
<!-- ============================================================
     Admin Products
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Productos</h1>
            <a href="/admin" class="admin-header__back">← Dashboard</a>
        </header>

        <!-- ── Alerts ────────────────────────────────────────────────── -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- ── Add Product Button ─────────────────────────────────────── -->
        <button class="admin-btn admin-btn--primary" data-toggle-form="product">
            + Nuevo Producto
        </button>

        <!-- ── Product Form ───────────────────────────────────────────── -->
        <div class="admin-form-panel" data-form-panel="product" hidden>
            <h2 class="admin-form-panel__title" data-form-title>Nuevo Producto</h2>

            <form class="admin-form" data-product-form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" data-field-id value="">
                <input type="hidden" name="_method" data-field-method value="POST">

                <div class="admin-form__grid">
                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-slug">Slug *</label>
                        <input id="prod-slug" name="slug" class="admin-field__input" required
                               pattern="[a-z0-9-]+" title="Minúsculas, números y guiones">
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-category">Categoría *</label>
                        <select id="prod-category" name="category_id" class="admin-field__select" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>">
                                    <?= htmlspecialchars($cat["name_{$lang}"] ?? $cat['name_es'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-name-es">Nombre (ES) *</label>
                        <input id="prod-name-es" name="name_es" class="admin-field__input" required>
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-name-en">Name (EN) *</label>
                        <input id="prod-name-en" name="name_en" class="admin-field__input" required>
                    </div>

                    <div class="admin-field admin-field--full">
                        <label class="admin-field__label" for="prod-desc-es">Descripción (ES)</label>
                        <textarea id="prod-desc-es" name="description_es" class="admin-field__textarea" rows="2"></textarea>
                    </div>

                    <div class="admin-field admin-field--full">
                        <label class="admin-field__label" for="prod-desc-en">Description (EN)</label>
                        <textarea id="prod-desc-en" name="description_en" class="admin-field__textarea" rows="2"></textarea>
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-price">Precio (€)</label>
                        <input id="prod-price" name="price" type="number" step="0.01" min="0"
                               class="admin-field__input" value="0">
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-image">Image URL</label>
                        <div style="display:flex;gap:8px;align-items:flex-start;">
                            <input id="prod-image" name="image_url" type="url" class="admin-field__input"
                                   placeholder="https://..." style="flex:1;">
                            <div class="admin-image-preview" data-preview="image" aria-hidden="true"></div>
                        </div>
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-shop-url">last.shop URL</label>
                        <input id="prod-shop-url" name="last_shop_url" type="url" class="admin-field__input"
                               placeholder="https://last.shop/..."
                               title="Debe empezar con https://">
                    </div>

                    <div class="admin-field">
                        <label class="admin-field__label" for="prod-order">Orden</label>
                        <input id="prod-order" name="sort_order" type="number" min="0"
                               class="admin-field__input" value="0">
                    </div>

                    <div class="admin-field admin-checkboxes">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Activo
                        </label>
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_featured" value="1">
                            Destacado
                        </label>
                    </div>
                </div>

                <div class="admin-form__actions">
                    <button type="submit" class="admin-btn admin-btn--primary" data-btn-submit>Guardar</button>
                    <button type="button" class="admin-btn admin-btn--ghost" data-cancel-form>Cancelar</button>
                </div>
            </form>
        </div>

        <!-- ── Product List ───────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Todos los Productos (<?= count($products) ?>)</h2>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Slug</th>
                            <th>Nombre (ES)</th>
                            <th>Nombre (EN)</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products === []): ?>
                            <tr>
                                <td colspan="8" class="admin-table__empty">
                                    No hay productos. ¡Crea el primero!
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($products as $p):
                            $catName = '';
                            foreach ($categories as $cat) {
                                if ((int) $cat['id'] === (int) $p['category_id']) {
                                    $catName = $cat["name_{$lang}"] ?? $cat['name_es'];
                                    break;
                                }
                            }
                        ?>
                            <tr>
                                <td><?= (int) $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($p['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($p['name_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>€<?= number_format((float) ($p['price'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($p['is_active']) ? '✓' : '✗' ?></td>
                                <td class="admin-table__actions">
                                    <button class="admin-btn-sm" data-edit-product="<?= (int) $p['id'] ?>"
                                            data-slug="<?= htmlspecialchars($p['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-name-es="<?= htmlspecialchars($p['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-name-en="<?= htmlspecialchars($p['name_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-desc-es="<?= htmlspecialchars($p['description_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-desc-en="<?= htmlspecialchars($p['description_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-price="<?= (float) ($p['price'] ?? 0) ?>"
                                            data-category-id="<?= (int) ($p['category_id'] ?? 0) ?>"
                                            data-image-url="<?= htmlspecialchars($p['image_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-last-shop-url="<?= htmlspecialchars($p['last_shop_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-sort-order="<?= (int) ($p['sort_order'] ?? 0) ?>"
                                            data-is-active="<?= !empty($p['is_active']) ? '1' : '0' ?>"
                                            data-is-featured="<?= !empty($p['is_featured']) ? '1' : '0' ?>">
                                        Editar
                                    </button>
                                    <button class="admin-btn-sm admin-btn-sm--danger"
                                            data-delete-product="<?= (int) $p['id'] ?>"
                                            data-name="<?= htmlspecialchars($p['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
 * Admin Products — CRUD operations via AJAX.
 */
import { api, showAlert, showToast, withLoading, AdminModal, bindImagePreview, validateForm, validateField } from '/js/admin.js';

const API_BASE = '/api/admin/products';

// ── Toggle Form ─────────────────────────────────────────────────
document.querySelectorAll('[data-toggle-form]').forEach(btn => {
    btn.addEventListener('click', () => {
        const panel = document.querySelector('[data-form-panel="product"]');
        if (panel) {
            panel.hidden = !panel.hidden;
            if (!panel.hidden) {
                panel.querySelector('[data-form-title]').textContent = 'Nuevo Producto';
                panel.querySelector('[data-field-method]').value = 'POST';
                panel.querySelector('[data-btn-submit]').textContent = 'Guardar';
                panel.querySelector('[data-product-form]').reset();
                panel.querySelector('[data-field-id]').value = '';
            }
        }
    });
});

document.querySelector('[data-cancel-form]')?.addEventListener('click', () => {
    const panel = document.querySelector('[data-form-panel="product"]');
    if (panel) panel.hidden = true;
});

// ── Submit Form ─────────────────────────────────────────────────
document.querySelector('[data-product-form]')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const submitBtn = form.querySelector('[data-btn-submit]');

    // Validate all fields before submit
    if (!validateForm(form)) {
        const firstInvalid = form.querySelector('.admin-field--invalid input, .admin-field--invalid select, .admin-field--invalid textarea');
        if (firstInvalid) firstInvalid.focus();
        showAlert('Corrige los campos marcados en rojo', 'error');
        return;
    }

    const id = form.querySelector('[data-field-id]').value;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_BASE}/${id}` : API_BASE;

    const data = {
        slug: form.querySelector('[name="slug"]').value,
        name_es: form.querySelector('[name="name_es"]').value,
        name_en: form.querySelector('[name="name_en"]').value,
        description_es: form.querySelector('[name="description_es"]').value,
        description_en: form.querySelector('[name="description_en"]').value,
        price: parseFloat(form.querySelector('[name="price"]').value) || 0,
        category_id: parseInt(form.querySelector('[name="category_id"]').value) || 0,
        image_url: form.querySelector('[name="image_url"]').value,
        last_shop_url: form.querySelector('[name="last_shop_url"]').value,
        sort_order: parseInt(form.querySelector('[name="sort_order"]').value) || 0,
        is_active: form.querySelector('[name="is_active"]').checked,
        is_featured: form.querySelector('[name="is_featured"]').checked,
    };

    await withLoading(submitBtn, async () => {
        const json = await api(method, url, data);

        if (json.error) {
            const msg = json.errors ? json.errors.join('; ') : (json.message || 'Error');
            showAlert(msg, 'error');
        } else {
            showAlert(id ? 'Producto actualizado' : 'Producto creado', 'success');
            setTimeout(() => window.location.reload(), 1000);
        }
    });
});

// ── Edit Product ────────────────────────────────────────────────
document.querySelectorAll('[data-edit-product]').forEach(btn => {
    btn.addEventListener('click', () => {
        const panel = document.querySelector('[data-form-panel="product"]');
        panel.hidden = false;

        panel.querySelector('[data-form-title]').textContent = 'Editar Producto';
        panel.querySelector('[data-field-method]').value = 'PUT';
        panel.querySelector('[data-btn-submit]').textContent = 'Actualizar';
        panel.querySelector('[data-field-id]').value = btn.dataset.editProduct;

        const fields = {
            slug: 'slug',
            name_es: 'nameEs',
            name_en: 'nameEn',
            description_es: 'descEs',
            description_en: 'descEn',
            price: 'price',
            category_id: 'categoryId',
            image_url: 'imageUrl',
            last_shop_url: 'lastShopUrl',
            sort_order: 'sortOrder',
        };

        for (const [name, dataKey] of Object.entries(fields)) {
            const input = panel.querySelector(`[name="${name}"]`);
            if (input) input.value = btn.dataset[dataKey] || '';
        }

        panel.querySelector('[name="is_active"]').checked = btn.dataset.isActive === '1';
        panel.querySelector('[name="is_featured"]').checked = btn.dataset.isFeatured === '1';
    });
});

// ── Delete Product ──────────────────────────────────────────────
const modal = new AdminModal();

document.querySelectorAll('[data-delete-product]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.deleteProduct;
        const name = btn.dataset.name || 'este producto';

        modal.open('Eliminar producto', `¿Eliminar "${name}"?`, async () => {
            modal.close();
            const json = await api('DELETE', `${API_BASE}/${id}`);

            if (json.error) {
                showAlert(json.message || 'Error al eliminar', 'error');
            } else {
                showAlert('Producto eliminado', 'success');
                setTimeout(() => window.location.reload(), 1000);
            }
        });
    });
});

// ── Image Preview ───────────────────────────────────────────────
const imageInput = document.querySelector('[name="image_url"]');
const imagePreview = document.querySelector('[data-preview="image"]');
if (imageInput && imagePreview) {
    bindImagePreview(imageInput, imagePreview);
}

// ── Form Field Validation on Blur ───────────────────────────────
const productForm = document.querySelector('[data-product-form]');
if (productForm) {
    productForm.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => {
            const wrapper = field.closest('.admin-field');
            if (wrapper) {
                wrapper.classList.remove('admin-field--invalid');
            }
        });
    });
}
</script>
