<?php
/**
 * Pit o Cuixa — Admin Products Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - products: array of product rows (bilingual)
 *   - categories: array of category rows
 *   - total: total number of active products
 *   - page: current page number
 *   - limit: items per page
 *   - total_pages: total pages
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user       = $pageData['user'] ?? [];
$products   = $pageData['products'] ?? [];
$categories = $pageData['categories'] ?? [];
$total      = $pageData['total'] ?? 0;
$page       = $pageData['page'] ?? 1;
$totalPages = $pageData['total_pages'] ?? 1;
$csrfToken  = $pageData['csrf_token'] ?? '';
$lang       = $pageData['locale'] ?? LANG;
?>
<!-- ============================================================
     Admin Products — Phase 3
     ============================================================ -->
<div class="admin-layout">
    <?php require __DIR__ . '/../../partials/admin-nav.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header__title">Productos</h1>
            <div class="admin-header__actions">
                <a href="/admin" class="admin-header__back">← Dashboard</a>
            </div>
        </header>

        <!-- ── Alerts ────────────────────────────────────────────────── -->
        <div class="admin-alert admin-alert--success" data-alert-success hidden></div>
        <div class="admin-alert admin-alert--error" data-alert-error hidden></div>

        <!-- ── Add Product Button ─────────────────────────────────────── -->
        <button class="admin-btn admin-btn--primary" data-create-btn aria-label="Crear nuevo producto">
            + Nuevo Producto
        </button>

        <!-- ── Drawer Overlay ─────────────────────────────────────────── -->
        <div class="admin-drawer__overlay" data-drawer-overlay hidden></div>

        <!-- ── Product Drawer ─────────────────────────────────────────── -->
        <div class="admin-drawer" data-drawer role="dialog" aria-modal="true" aria-labelledby="drawer-title" hidden>
            <div class="admin-drawer__header">
                <h2 class="admin-drawer__title" id="drawer-title" data-drawer-title>Nuevo Producto</h2>
                <button class="admin-drawer__close" data-drawer-close aria-label="Cerrar">&times;</button>
            </div>

            <div class="admin-drawer__body">
                <form class="admin-form" data-product-form>
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

                        <div class="admin-field admin-field--full">
                            <label class="admin-field__label">Canales de Disponibilidad</label>
                            <div class="admin-checkboxes">
                                <label class="admin-checkbox">
                                    <input type="checkbox" name="is_dine_in" value="1" checked>
                                    🍽️ Restaurante (Local)
                                </label>
                                <label class="admin-checkbox">
                                    <input type="checkbox" name="is_delivery" value="1" checked>
                                    🛵 Domicilio (Delivery)
                                </label>
                            </div>
                        </div>

                        <div class="admin-field">
                            <label class="admin-field__label" for="prod-type">Tipo de Producto</label>
                            <select id="prod-type" name="type" class="admin-field__select" data-type-select>
                                <option value="simple">Producto Simple (A la carta)</option>
                                <option value="menu">Menú / Pack Combo</option>
                            </select>
                        </div>

                        <div class="admin-field admin-field--full" data-menu-editor hidden>
                            <label class="admin-field__label" for="prod-menu-data">Estructura del Menú (JSON)</label>
                            <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:6px;">
                                Define las secciones y platos del menú en formato JSON.
                            </p>
                            <textarea id="prod-menu-data" name="menu_data" class="admin-field__textarea" rows="6"
                                      placeholder='{ "badge": "De lunes a viernes", "includes": "Incluye agua, vino o cerveza", "sections": [{ "title_es": "Primeros Platos", "items_es": ["Pasta boloñesa", "Ensaladilla rusa"] }] }'></textarea>
                            <button type="button" class="admin-btn-sm" data-btn-template-menu style="margin-top:6px;">
                                ✨ Cargar plantilla de Menú del Día
                            </button>
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
                </form>
            </div>

            <div class="admin-drawer__footer">
                <button type="button" class="admin-btn admin-btn--ghost" data-drawer-cancel>Cancelar</button>
                <button type="button" class="admin-btn admin-btn--primary" data-btn-submit>Guardar</button>
            </div>
        </div>

        <!-- ── Product List ───────────────────────────────────────────── -->
        <section class="admin-section">
            <h2 class="admin-section__title">Todos los Productos (<?= $total ?>)</h2>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Slug</th>
                            <th>Nombre (ES)</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Canal</th>
                            <th>Tipo</th>
                            <th>Clics</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-products-tbody>
                        <?php if ($products === []): ?>
                            <tr>
                                <td colspan="10" class="admin-table__empty">
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
                            $channels = [];
                            if (!empty($p['is_dine_in'])) $channels[] = '🍽️ Local';
                            if (!empty($p['is_delivery'])) $channels[] = '🛵 Delivery';
                            $channelStr = implode(' ', $channels) ?: 'Ninguno';
                            $typeStr = ($p['type'] ?? 'simple') === 'menu' ? '🗂️ Menú' : 'Simple';
                            $menuDataAttr = is_array($p['menu_data']) ? json_encode($p['menu_data'], JSON_UNESCAPED_UNICODE) : (string)($p['menu_data'] ?? '');
                        ?>
                            <tr data-product-id="<?= (int) $p['id'] ?>">
                                <td><?= (int) $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($p['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>€<?= number_format((float) ($p['price'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small><?= htmlspecialchars($channelStr, ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><small><?= htmlspecialchars($typeStr, ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td>🔥 <?= (int) ($p['clicks_count'] ?? 0) ?></td>
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
                                            data-is-featured="<?= !empty($p['is_featured']) ? '1' : '0' ?>"
                                            data-is-dine-in="<?= !empty($p['is_dine_in']) ? '1' : '0' ?>"
                                            data-is-delivery="<?= !empty($p['is_delivery']) ? '1' : '0' ?>"
                                            data-type="<?= htmlspecialchars($p['type'] ?? 'simple', ENT_QUOTES, 'UTF-8') ?>"
                                            data-menu-data='<?= htmlspecialchars($menuDataAttr, ENT_QUOTES, 'UTF-8') ?>'>
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

            <!-- ── Pagination ─────────────────────────────────────────── -->
            <div class="admin-pagination" data-pagination
                 data-total-pages="<?= $totalPages ?>"
                 data-current-page="<?= $page ?>"
                 data-total="<?= $total ?>">
            </div>
        </section>
    </main>
</div>

<script type="module">
/**
 * Admin Products — Phase 3: CRUD via AJAX with in-place DOM updates.
 * Uses drawer, pagination, and keyboard shortcuts.
 */
import {
    api, showToast, withLoading, AdminModal, bindImagePreview,
    validateForm, validateField, getCsrfToken,
    Drawer, insertTableRow, removeTableRow, updateTableRow, toggleEmptyState,
    renderPagination, paginationClickHandler, fetchPaginated, swapTableRows, setPageParam,
    initKeyboardShortcuts
} from '/js/admin.js';

const API_BASE = '/api/admin/products';
const TBODY = document.querySelector('[data-products-tbody]');
const CATEGORIES = <?= json_encode(array_map(function($c) { return ['id' => (int)$c['id'], 'name_es' => $c['name_es'], 'name_en' => $c['name_en']]; }, $categories)) ?>;
const LANG = '<?= $lang ?>';

// ── Drawer ────────────────────────────────────────────────────────
const drawer = new Drawer({
    drawer: '[data-drawer]',
    overlay: '[data-drawer-overlay]',
});

/** Get category name from ID */
function catName(categoryId) {
    const c = CATEGORIES.find(c => c.id === categoryId);
    return c ? (c[`name_${LANG}`] || c.name_es) : '';
}

/** Escape HTML */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/** Build a product table row HTML from data */
function buildRow(p) {
    const channels = [];
    if (p.is_dine_in) channels.push('🍽️ Local');
    if (p.is_delivery) channels.push('🛵 Delivery');
    const channelStr = channels.join(' ') || 'Ninguno';
    const typeStr = (p.type === 'menu') ? '🗂️ Menú' : 'Simple';
    const menuDataAttr = (typeof p.menu_data === 'object' && p.menu_data !== null) ? JSON.stringify(p.menu_data) : (p.menu_data || '');

    return `<tr data-product-id="${p.id}">
        <td>${p.id}</td>
        <td>${escHtml(p.slug)}</td>
        <td>${escHtml(p.name_es)}</td>
        <td>€${parseFloat(p.price).toFixed(2)}</td>
        <td>${escHtml(catName(p.category_id))}</td>
        <td><small>${escHtml(channelStr)}</small></td>
        <td><small>${escHtml(typeStr)}</small></td>
        <td>${p.is_active ? '✓' : '✗'}</td>
        <td class="admin-table__actions">
            <button class="admin-btn-sm" data-edit-product="${p.id}"
                    data-slug="${escHtml(p.slug)}"
                    data-name-es="${escHtml(p.name_es)}"
                    data-name-en="${escHtml(p.name_en)}"
                    data-desc-es="${escHtml(p.description_es || '')}"
                    data-desc-en="${escHtml(p.description_en || '')}"
                    data-price="${p.price}"
                    data-category-id="${p.category_id}"
                    data-image-url="${escHtml(p.image_url || '')}"
                    data-last-shop-url="${escHtml(p.last_shop_url || '')}"
                    data-sort-order="${p.sort_order || 0}"
                    data-is-active="${p.is_active ? '1' : '0'}"
                    data-is-featured="${p.is_featured ? '1' : '0'}"
                    data-is-dine-in="${p.is_dine_in ? '1' : '0'}"
                    data-is-delivery="${p.is_delivery ? '1' : '0'}"
                    data-type="${escHtml(p.type || 'simple')}"
                    data-menu-data='${escHtml(menuDataAttr)}'>
                Editar
            </button>
            <button class="admin-btn-sm admin-btn-sm--danger"
                    data-delete-product="${p.id}"
                    data-name="${escHtml(p.name_es)}">
                Eliminar
            </button>
        </td>
    </tr>`;
}

/** Collect form data into object */
function getFormData(form) {
    const rawMenuData = form.querySelector('[name="menu_data"]')?.value.trim() || '';
    let parsedMenuData = null;
    if (rawMenuData !== '') {
        try {
            parsedMenuData = JSON.parse(rawMenuData);
        } catch (_) {
            parsedMenuData = rawMenuData;
        }
    }

    return {
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
        is_dine_in: form.querySelector('[name="is_dine_in"]').checked ? 1 : 0,
        is_delivery: form.querySelector('[name="is_delivery"]').checked ? 1 : 0,
        source: form.querySelector('[name="last_shop_url"]').value ? 'delivery' : 'manual',
        type: form.querySelector('[name="type"]').value,
        menu_data: parsedMenuData,
    };
}

/** Toggle menu editor visibility */
function toggleMenuEditor(type) {
    const editor = document.querySelector('[data-menu-editor]');
    if (editor) editor.hidden = (type !== 'menu');
}

/** Fill form with product data for editing */
function fillForm(btn) {
    const form = document.querySelector('[data-product-form]');
    if (!form) return;

    const drawerTitle = document.querySelector('[data-drawer-title]');
    if (drawerTitle) drawerTitle.textContent = 'Editar Producto';

    const submitBtn = document.querySelector('[data-btn-submit]');
    if (submitBtn) submitBtn.textContent = 'Actualizar';

    form.querySelector('[data-field-method]').value = 'PUT';
    form.querySelector('[data-field-id]').value = btn.dataset.editProduct;

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
        type: 'type',
    };

    for (const [name, dataKey] of Object.entries(fields)) {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) input.value = btn.dataset[dataKey] || (name === 'type' ? 'simple' : '');
    }

    form.querySelector('[name="is_active"]').checked = btn.dataset.isActive === '1';
    form.querySelector('[name="is_featured"]').checked = btn.dataset.isFeatured === '1';
    form.querySelector('[name="is_dine_in"]').checked = btn.dataset.isDineIn !== '0';
    form.querySelector('[name="is_delivery"]').checked = btn.dataset.isDelivery !== '0';

    const menuDataInput = form.querySelector('[name="menu_data"]');
    if (menuDataInput) {
        menuDataInput.value = btn.dataset.menuData || '';
    }

    toggleMenuEditor(btn.dataset.type || 'simple');
}

/** Reset form for create mode */
function resetForm() {
    const form = document.querySelector('[data-product-form]');
    if (!form) return;

    const drawerTitle = document.querySelector('[data-drawer-title]');
    if (drawerTitle) drawerTitle.textContent = 'Nuevo Producto';

    const submitBtn = document.querySelector('[data-btn-submit]');
    if (submitBtn) submitBtn.textContent = 'Guardar';

    form.querySelector('[data-field-method]').value = 'POST';
    form.querySelector('[data-field-id]').value = '';
    form.reset();
    form.querySelector('[name="is_active"]').checked = true;
    form.querySelector('[name="is_dine_in"]').checked = true;
    form.querySelector('[name="is_delivery"]').checked = true;
    form.querySelector('[name="type"]').value = 'simple';

    toggleMenuEditor('simple');

    // Clear image preview if present
    const previewEl = document.querySelector('[data-preview="image"]');
    if (previewEl) {
        previewEl.innerHTML = '';
        previewEl.classList.remove('admin-image-preview--visible');
    }
}

// ── Type Select Change Event ───────────────────────────────────────
document.querySelector('[data-type-select]')?.addEventListener('change', (e) => {
    toggleMenuEditor(e.target.value);
});

// ── Pre-fill Sample Menu Template ──────────────────────────────────
document.querySelector('[data-btn-template-menu]')?.addEventListener('click', () => {
    const textarea = document.querySelector('[name="menu_data"]');
    if (!textarea) return;
    const sample = {
        badge: "De lunes a viernes (Suplemento de +3€ en fin de semana)",
        includes: "Incluye agua, refresco, vino o cerveza",
        sections: [
            {
                title_es: "PRIMEROS PLATOS",
                items_es: [
                    "Pasta con boloñesa artesana",
                    "Ensaladilla rusa clásica",
                    "Ensalada César de pollo crujiente",
                    "Croquetas caseras de pollo y jamón",
                    "Fideuá con sabor a mediterráneo",
                    "Plato de la semana"
                ]
            },
            {
                title_es: "SEGUNDOS PLATOS",
                items_es: [
                    "Cuarto de pollo a l'ast, la joya de la casa",
                    "Arroz con verduras y pollo al curry suave",
                    "Fingers de pollo con salsa de yogur, miel y mostaza",
                    "Berenjena rellena gratinada al horno",
                    "Canelones de rustido al estilo de la abuela",
                    "Plato de la semana"
                ]
            },
            {
                title_es: "POSTRES",
                items_es: [
                    "Flan",
                    "Tarta de Queso",
                    "Brownie de chocolate",
                    "Yogurt con mermelada de frambuesa",
                    "Helado",
                    "Café"
                ]
            }
        ]
    };
    textarea.value = JSON.stringify(sample, null, 2);
});

// ── Create Button ────────────────────────────────────────────────
document.querySelector('[data-create-btn]')?.addEventListener('click', () => {
    resetForm();
    drawer.open('Nuevo Producto');
});

// ── Submit Form via Drawer Footer Button ──────────────────────────
document.querySelector('[data-btn-submit]')?.addEventListener('click', async () => {
    const form = document.querySelector('[data-product-form]');
    if (!form) return;

    // Validate
    if (!validateForm(form)) {
        const firstInvalid = form.querySelector('.admin-field--invalid input, .admin-field--invalid select, .admin-field--invalid textarea');
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
            const product = json.data;
            const rowHtml = buildRow(product);
            const row = insertTableRow(TBODY, rowHtml);

            // Highlight the new row
            row.style.animation = 'admin-row-in 250ms ease';

            showToast('Producto creado', 'success');
            drawer.close();

            // Update section title count
            updateCount();

        } else {
            // Update: update row in-place
            const product = json.data;
            const existingRow = TBODY.querySelector(`[data-product-id="${id}"]`);
            if (existingRow) {
                updateTableRow(existingRow, buildRow(product));
            }
            showToast('Producto actualizado', 'success');
            drawer.close();
        }
    });
});

// ── Event Delegation for Edit/Delete ─────────────────────────────
TBODY?.addEventListener('click', (e) => {
    // Edit button
    const editBtn = e.target.closest('[data-edit-product]');
    if (editBtn) {
        fillForm(editBtn);
        drawer.open('Editar Producto');
        return;
    }

    // Delete button
    const deleteBtn = e.target.closest('[data-delete-product]');
    if (deleteBtn) {
        const id = deleteBtn.dataset.deleteProduct;
        const name = deleteBtn.dataset.name || 'este producto';
        const modal = new AdminModal();

        modal.open('Eliminar producto', `¿Eliminar "${name}"?`, async () => {
            modal.close();
            const json = await api('DELETE', `${API_BASE}/${id}`);

            if (json.error) {
                showToast(json.message || 'Error al eliminar', 'error');
            } else {
                // Remove row with animation
                const row = TBODY.querySelector(`[data-product-id="${id}"]`);
                if (row) {
                    await removeTableRow(row);
                    toggleEmptyState(TBODY, 8, 'No hay productos. ¡Crea el primero!');
                    updateCount();
                }
                showToast('Producto eliminado', 'success');
            }
        });
    }
});

// ── Update Section Title Count ────────────────────────────────────
function updateCount() {
    const rows = TBODY.querySelectorAll('tr:not(.admin-table__empty)');
    const title = document.querySelector('.admin-section__title');
    if (title) {
        title.textContent = `Todos los Productos (${rows.length})`;
    }
}

// ── Pagination ───────────────────────────────────────────────────
const paginationContainer = document.querySelector('[data-pagination]');
let currentPaginationPage = 1;

async function loadPage(page) {
    const json = await fetchPaginated(API_BASE, page, 20);
    if (json.error) {
        showToast('Error al cargar página', 'error');
        return;
    }

    // Build rows from data
    const rowsHtml = json.data.map(p => buildRow(p)).join('\n');
    swapTableRows(TBODY, rowsHtml);
    toggleEmptyState(TBODY, 8, 'No hay productos.');

    // Update pagination
    currentPaginationPage = json.page;
    renderPagination({
        container: paginationContainer,
        currentPage: json.page,
        totalPages: json.total_pages,
        total: json.total,
    });

    // Update URL
    setPageParam(json.page);

    // Update count
    const title = document.querySelector('.admin-section__title');
    if (title) {
        title.textContent = `Todos los Productos (${json.total})`;
    }
}

if (paginationContainer) {
    const totalPages = parseInt(paginationContainer.dataset.totalPages, 10);
    const currentPage = parseInt(paginationContainer.dataset.currentPage, 10);
    const totalItems = parseInt(paginationContainer.dataset.total, 10);
    currentPaginationPage = currentPage;

    if (totalPages > 0) {
        renderPagination({
            container: paginationContainer,
            currentPage,
            totalPages,
            total: totalItems,
        });

        // Single delegated click handler
        paginationContainer.addEventListener('click', (e) => {
            const page = paginationClickHandler(paginationContainer, e);
            if (page > 0) loadPage(page);
        });
    }
}

// Handle popstate (back/forward navigation)
window.addEventListener('popstate', (e) => {
    const page = e.state?.page || 1;
    if (page !== currentPaginationPage && page > 0) {
        loadPage(page);
    }
});

// ── Keyboard Shortcuts ───────────────────────────────────────────
initKeyboardShortcuts({
    escape: () => {
        const drawerEl = document.querySelector('[data-drawer]');
        if (drawerEl && !drawerEl.hidden) {
            drawer.close();
        }
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

// ── Drawer close resets form validation state ────────────────────
drawer.onClose = () => {
    // Remove invalid states
    document.querySelectorAll('.admin-field--invalid').forEach(el => {
        el.classList.remove('admin-field--invalid');
    });
};
</script>
