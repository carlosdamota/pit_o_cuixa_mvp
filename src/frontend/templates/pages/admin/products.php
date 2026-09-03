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
                <a href="/pitocuixa" class="admin-header__back">← Dashboard</a>
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
                        <!-- ══ Tipo de Producto — Toggle Principal ══ -->
                        <div class="admin-field admin-field--full">
                            <label class="admin-field__label">¿Qué vas a crear?</label>
                            <div class="admin-type-toggle" style="display:flex;gap:8px;margin-top:8px;">
                                <button type="button" class="admin-type-btn" data-type-value="simple" style="flex:1;padding:12px;border:2px solid var(--color-outline-variant, #cbd5e1);border-radius:8px;background:var(--color-surface, #fff);cursor:pointer;transition:all 0.2s;font-weight:600;">
                                    🍽️<br>Producto
                                </button>
                                <button type="button" class="admin-type-btn" data-type-value="menu" style="flex:1;padding:12px;border:2px solid var(--color-outline-variant, #cbd5e1);border-radius:8px;background:var(--color-surface, #fff);cursor:pointer;transition:all 0.2s;font-weight:600;">
                                    📋<br>Menú
                                </button>
                                <button type="button" class="admin-type-btn" data-type-value="carta" style="flex:1;padding:12px;border:2px solid var(--color-outline-variant, #cbd5e1);border-radius:8px;background:var(--color-surface, #fff);cursor:pointer;transition:all 0.2s;font-weight:600;">
                                    📖<br>Carta
                                </button>
                            </div>
                            <input type="hidden" name="type" value="simple" data-type-input>
                        </div>

                        <!-- ══ Campos Básicos (siempre visibles) ══ -->
                        <div class="admin-field admin-field--full">
                            <label class="admin-field__label" for="prod-name-es">Nombre *</label>
                            <input id="prod-name-es" name="name_es" class="admin-field__input" placeholder="Ej: Pollo a l'ast entero, Menú del día, Carta de vinos" required>
                        </div>

                        <div class="admin-field admin-field--full">
                            <label class="admin-field__label" for="prod-desc-es">Descripción</label>
                            <textarea id="prod-desc-es" name="description_es" class="admin-field__textarea" rows="2" placeholder="Ej: Pollo asado crujiente al estilo tradicional..."></textarea>
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

                        <div class="admin-field" data-price-container>
                            <label class="admin-field__label" for="prod-price" data-price-label>Precio (€)</label>
                            <input id="prod-price" name="price" type="number" step="0.01" min="0"
                                   class="admin-field__input" value="0" data-price-field>
                            <small style="display:block;margin-top:4px;color:var(--color-text-muted);" data-price-help>
                                Precio del producto
                            </small>
                        </div>

                        <!-- ══ Imagen (siempre visible) ══ -->
                        <div class="admin-field admin-field--full">
                            <label class="admin-field__label" for="prod-image">Imagen / Cartel</label>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <input id="prod-image" name="image_url" class="admin-field__input"
                                       placeholder="https://... o sube una imagen" style="flex:1;min-width:200px;">
                                <input type="file" id="prod-image-file" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" hidden>
                                <button type="button" class="admin-btn-sm admin-btn-sm--secondary" data-btn-upload-file style="white-space:nowrap;">
                                    📁 Subir Imagen
                                </button>
                                <div class="admin-image-preview" data-preview="image" aria-hidden="true"></div>
                            </div>
                        </div>

                        <!-- ══ Auto-traducir ══ -->
                        <div class="admin-field admin-field--full">
                            <label class="admin-checkbox" style="font-size:0.85rem;color:var(--color-primary);">
                                <input type="checkbox" name="auto_translate" value="1" checked>
                                🌐 Traducir automáticamente con DeepL (Inglés, Ucraniano)
                            </label>
                        </div>

                        <!-- ══ Diseñador Visual (solo Menú y Carta) ══ -->
                        <div class="admin-field admin-field--full" data-menu-editor hidden>
                            <label class="admin-field__label">Diseñador Visual de Menú</label>
                            <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:8px;">
                                Configura las secciones y platos del menú sin necesidad de escribir código JSON.
                            </p>

                            <div style="padding:12px;background:var(--color-surface-container-low, #f8f9fa);border-radius:8px;border:1px solid var(--color-outline-variant, #e2e8f0);margin-bottom:8px;">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                    <div>
                                        <label style="font-size:0.75rem;font-weight:600;display:block;margin-bottom:4px;">Etiqueta / Badge</label>
                                        <input type="text" class="admin-field__input" data-menu-builder-badge placeholder="Ej: De lunes a viernes">
                                    </div>
                                    <div>
                                        <label style="font-size:0.75rem;font-weight:600;display:block;margin-bottom:4px;">Incluye / Notas</label>
                                        <input type="text" class="admin-field__input" data-menu-builder-includes placeholder="Ej: Incluye agua, vino y postre">
                                    </div>
                                </div>

                                <div data-menu-builder-sections style="display:flex;flex-direction:column;gap:12px;"></div>

                                <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                                    <button type="button" class="admin-btn-sm admin-btn-sm--secondary" data-btn-add-section>
                                        + Añadir Sección
                                    </button>
                                    <button type="button" class="admin-btn-sm" data-btn-template-menu>
                                        ✨ Cargar Plantilla de Ejemplo
                                    </button>
                                </div>
                            </div>

                            <details style="margin-top:4px;">
                                <summary style="font-size:0.8rem;cursor:pointer;color:var(--color-text-muted);">Modo Avanzado (JSON)</summary>
                                <textarea id="prod-menu-data" name="menu_data" class="admin-field__textarea" rows="4" style="margin-top:6px;font-family:monospace;font-size:0.8rem;"
                                          placeholder='{ "badge": "De lunes a viernes", "sections": [] }'></textarea>
                            </details>
                        </div>

                        <!-- ══ Estado ══ -->
                        <div class="admin-field admin-field--full">
                            <label class="admin-checkbox">
                                <input type="checkbox" name="is_active" value="1" checked>
                                ✓ Activo (visible en la web)
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
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-products-tbody>
                        <?php if ($products === []): ?>
                            <tr>
                                <td colspan="6" class="admin-table__empty">
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
                            $typeStr = ($p['type'] ?? 'simple') === 'menu' ? '📋 Menú' : (($p['type'] ?? 'simple') === 'carta' ? '📖 Carta' : '🍽️ Producto');
                            $menuDataAttr = is_array($p['menu_data']) ? json_encode($p['menu_data'], JSON_UNESCAPED_UNICODE) : (string)($p['menu_data'] ?? '');
                        ?>
                            <tr data-product-id="<?= (int) $p['id'] ?>">
                                <td><?= htmlspecialchars($p['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small><?= htmlspecialchars($typeStr, ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>€<?= number_format((float) ($p['price'] ?? 0), 2) ?></td>
                                <td><?= !empty($p['is_active']) ? '✓' : '✗' ?></td>
                                <td class="admin-table__actions">
                                    <button class="admin-btn-sm" data-edit-product="<?= (int) $p['id'] ?>"
                                            data-name-es="<?= htmlspecialchars($p['name_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-desc-es="<?= htmlspecialchars($p['description_es'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-price="<?= (float) ($p['price'] ?? 0) ?>"
                                            data-category-id="<?= (int) ($p['category_id'] ?? 0) ?>"
                                            data-image-url="<?= htmlspecialchars($p['image_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-is-active="<?= !empty($p['is_active']) ? '1' : '0' ?>"
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
} from '/js/admin.js<?= assetVersion('/js/admin.js') ?>';

const API_BASE = '/api/pitocuixa/products';
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
    const typeStr = (p.type === 'menu') ? '📋 Menú' : ((p.type === 'carta') ? '📖 Carta' : '🍽️ Producto');
    const menuDataAttr = (typeof p.menu_data === 'object' && p.menu_data !== null) ? JSON.stringify(p.menu_data) : (p.menu_data || '');

    return `<tr data-product-id="${p.id}">
        <td>${escHtml(p.name_es)}</td>
        <td><small>${escHtml(typeStr)}</small></td>
        <td>${escHtml(catName(p.category_id))}</td>
        <td>€${parseFloat(p.price).toFixed(2)}</td>
        <td>${p.is_active ? '✓' : '✗'}</td>
        <td class="admin-table__actions">
            <button class="admin-btn-sm" data-edit-product="${p.id}"
                    data-name-es="${escHtml(p.name_es)}"
                    data-desc-es="${escHtml(p.description_es || '')}"
                    data-price="${p.price}"
                    data-category-id="${p.category_id}"
                    data-image-url="${escHtml(p.image_url || '')}"
                    data-is-active="${p.is_active ? '1' : '0'}"
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

// ── Visual Menu Builder Logic ────────────────────────────────────
const menuBuilderBadgeInput = document.querySelector('[data-menu-builder-badge]');
const menuBuilderIncludesInput = document.querySelector('[data-menu-builder-includes]');
const menuBuilderSectionsContainer = document.querySelector('[data-menu-builder-sections]');

function createItemRow(name = '', price = '') {
    const currentType = typeInput?.value || 'simple';
    const showPrice = currentType === 'carta';
    
    const div = document.createElement('div');
    div.className = 'menu-item-row';
    div.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:4px;';
    div.innerHTML = `
        <input type="text" class="admin-field__input data-item-name" placeholder="Nombre del plato (Ej: Ensalada)" value="${escHtml(name)}" style="flex:2;font-size:0.8rem;padding:4px 8px;">
        <input type="number" step="0.01" min="0" class="admin-field__input data-item-price" placeholder="€" value="${price !== '' && price !== null ? escHtml(String(price)) : ''}" style="flex:1;max-width:110px;font-size:0.8rem;padding:4px 8px;${showPrice ? '' : 'display:none;'}">
        <button type="button" class="admin-btn-sm admin-btn-sm--danger data-btn-remove-item" title="Eliminar plato" style="padding:2px 6px;font-size:0.75rem;">&times;</button>
    `;
    div.querySelector('.data-btn-remove-item').addEventListener('click', () => {
        div.remove();
        syncMenuBuilderToJson();
    });
    div.querySelector('.data-item-name').addEventListener('input', syncMenuBuilderToJson);
    const priceInput = div.querySelector('.data-item-price');
    if (priceInput) priceInput.addEventListener('input', syncMenuBuilderToJson);
    return div;
}

function createSectionCard(title = '', items = []) {
    const card = document.createElement('div');
    card.className = 'menu-section-card';
    card.style.cssText = 'padding:10px;background:#ffffff;border:1px solid var(--color-outline-variant, #cbd5e1);border-radius:6px;';

    card.innerHTML = `
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <input type="text" class="admin-field__input data-section-title" placeholder="Nombre de sección (Ej: PRIMEROS PLATOS)" value="${escHtml(title)}" style="flex:1;font-weight:600;font-size:0.85rem;">
            <button type="button" class="admin-btn-sm admin-btn-sm--danger data-btn-remove-section" style="padding:4px 8px;font-size:0.75rem;">Eliminar Sección</button>
        </div>
        <div class="data-section-items" style="margin-left:8px;"></div>
        <button type="button" class="admin-btn-sm data-btn-add-item" style="margin-top:6px;font-size:0.75rem;">+ Añadir Plato</button>
    `;

    const itemsContainer = card.querySelector('.data-section-items');
    items.forEach(item => {
        if (typeof item === 'object' && item !== null) {
            const iName = item.name_es || item.name || '';
            const iPrice = item.price !== undefined ? item.price : '';
            itemsContainer.appendChild(createItemRow(iName, iPrice));
        } else {
            itemsContainer.appendChild(createItemRow(String(item || ''), ''));
        }
    });

    card.querySelector('.data-btn-add-item').addEventListener('click', () => {
        itemsContainer.appendChild(createItemRow('', ''));
        syncMenuBuilderToJson();
    });

    card.querySelector('.data-btn-remove-section').addEventListener('click', () => {
        card.remove();
        syncMenuBuilderToJson();
    });

    card.querySelector('.data-section-title').addEventListener('input', syncMenuBuilderToJson);

    return card;
}

function renderMenuBuilder(data) {
    if (!menuBuilderSectionsContainer) return;
    menuBuilderSectionsContainer.innerHTML = '';

    const badge = data?.badge || '';
    const includes = data?.includes || '';
    if (menuBuilderBadgeInput) menuBuilderBadgeInput.value = badge;
    if (menuBuilderIncludesInput) menuBuilderIncludesInput.value = includes;

    const sections = Array.isArray(data?.sections) ? data.sections : [];
    sections.forEach(sec => {
        const secTitle = sec.title_es || sec.title || '';
        const secItems = Array.isArray(sec.items_es) ? sec.items_es : (Array.isArray(sec.items) ? sec.items : []);
        menuBuilderSectionsContainer.appendChild(createSectionCard(secTitle, secItems));
    });
}

function syncMenuBuilderToJson() {
    const textarea = document.querySelector('[name="menu_data"]');
    if (!textarea) return;

    const currentType = document.querySelector('[name="type"]')?.value || 'simple';
    const badge = menuBuilderBadgeInput?.value.trim() || '';
    const includes = menuBuilderIncludesInput?.value.trim() || '';
    const sections = [];

    if (menuBuilderSectionsContainer) {
        menuBuilderSectionsContainer.querySelectorAll('.menu-section-card').forEach(card => {
            const secTitle = card.querySelector('.data-section-title')?.value.trim() || '';
            const items = [];
            card.querySelectorAll('.menu-item-row').forEach(row => {
                const nameInput = row.querySelector('.data-item-name');
                const priceInput = row.querySelector('.data-item-price');
                if (nameInput) {
                    const nameVal = nameInput.value.trim();
                    const priceVal = priceInput?.value.trim();
                    if (nameVal !== '') {
                        if (priceVal !== '' && priceVal !== null && !isNaN(parseFloat(priceVal))) {
                            items.push({
                                name_es: nameVal,
                                price: parseFloat(priceVal)
                            });
                        } else if (currentType === 'carta') {
                            items.push({
                                name_es: nameVal,
                                price: 0
                            });
                        } else {
                            items.push(nameVal);
                        }
                    }
                }
            });
            if (secTitle !== '' || items.length > 0) {
                sections.push({
                    title_es: secTitle,
                    items_es: items
                });
            }
        });
    }

    if (!badge && !includes && sections.length === 0) {
        textarea.value = '';
    } else {
        const dataObj = {};
        if (badge) dataObj.badge = badge;
        if (includes) dataObj.includes = includes;
        dataObj.sections = sections;
        textarea.value = JSON.stringify(dataObj, null, 2);
    }
}

document.querySelector('[data-btn-add-section]')?.addEventListener('click', () => {
    if (menuBuilderSectionsContainer) {
        const card = createSectionCard('NUEVA SECCIÓN', ['']);
        menuBuilderSectionsContainer.appendChild(card);
        syncMenuBuilderToJson();
    }
});

menuBuilderBadgeInput?.addEventListener('input', syncMenuBuilderToJson);
menuBuilderIncludesInput?.addEventListener('input', syncMenuBuilderToJson);

/** Collect form data into object */
function getFormData(form) {
    const typeVal = form.querySelector('[name="type"]')?.value;
    if (typeVal === 'menu' || typeVal === 'carta') {
        syncMenuBuilderToJson();
    }

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
        name_es: form.querySelector('[name="name_es"]').value,
        description_es: form.querySelector('[name="description_es"]').value,
        auto_translate: form.querySelector('[name="auto_translate"]')?.checked ? 1 : 0,
        price: parseFloat(form.querySelector('[name="price"]').value) || 0,
        category_id: parseInt(form.querySelector('[name="category_id"]').value) || 0,
        image_url: form.querySelector('[name="image_url"]').value,
        is_active: form.querySelector('[name="is_active"]').checked,
        is_dine_in: 1,  // Always local
        is_delivery: 0, // No delivery for local management
        source: 'manual',
        type: form.querySelector('[name="type"]').value,
        menu_data: parsedMenuData,
    };
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

    // Fill basic fields
    const fields = {
        name_es: 'nameEs',
        description_es: 'descEs',
        price: 'price',
        category_id: 'categoryId',
        image_url: 'imageUrl',
    };

    for (const [name, dataKey] of Object.entries(fields)) {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) input.value = btn.dataset[dataKey] || '';
    }

    // Set type and update toggle buttons
    const type = btn.dataset.type || 'simple';
    form.querySelector('[name="type"]').value = type;
    
    // Update toggle button styles
    typeButtons.forEach(b => {
        b.style.borderColor = 'var(--color-outline-variant, #cbd5e1)';
        b.style.background = 'var(--color-surface, #fff)';
        b.style.color = 'inherit';
    });
    const activeBtn = document.querySelector(`[data-type-value="${type}"]`);
    if (activeBtn) {
        activeBtn.style.borderColor = 'var(--color-primary, #3b82f6)';
        activeBtn.style.background = 'var(--color-primary, #3b82f6)';
        activeBtn.style.color = 'white';
    }

    form.querySelector('[name="is_active"]').checked = btn.dataset.isActive === '1';

    const rawMenu = btn.dataset.menuData || '';
    const menuDataInput = form.querySelector('[name="menu_data"]');
    if (menuDataInput) {
        menuDataInput.value = rawMenu;
    }

    let parsedMenu = null;
    if (rawMenu) {
        try {
            parsedMenu = JSON.parse(rawMenu);
        } catch (_) {}
    }
    renderMenuBuilder(parsedMenu);

    // Update form visibility based on type
    updateFormForType(type);
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
    form.querySelector('[name="type"]').value = 'simple';

    // Reset toggle buttons
    typeButtons.forEach(b => {
        b.style.borderColor = 'var(--color-outline-variant, #cbd5e1)';
        b.style.background = 'var(--color-surface, #fff)';
        b.style.color = 'inherit';
    });
    if (typeButtons.length > 0) {
        typeButtons[0].style.borderColor = 'var(--color-primary, #3b82f6)';
        typeButtons[0].style.background = 'var(--color-primary, #3b82f6)';
        typeButtons[0].style.color = 'white';
    }

    renderMenuBuilder(null);
    syncMenuBuilderToJson();

    // Reset form visibility
    updateFormForType('simple');

    // Clear image preview if present
    const previewEl = document.querySelector('[data-preview="image"]');
    if (previewEl) {
        previewEl.innerHTML = '';
        previewEl.classList.remove('admin-image-preview--visible');
    }
}

// ── Type Toggle Buttons ─────────────────────────────────────────────
const typeButtons = document.querySelectorAll('[data-type-value]');
const typeInput = document.querySelector('[data-type-input]');

typeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const type = btn.dataset.typeValue;
        
        // Update hidden input
        if (typeInput) typeInput.value = type;
        
        // Update button styles
        typeButtons.forEach(b => {
            b.style.borderColor = 'var(--color-outline-variant, #cbd5e1)';
            b.style.background = 'var(--color-surface, #fff)';
            b.style.color = 'inherit';
        });
        btn.style.borderColor = 'var(--color-primary, #3b82f6)';
        btn.style.background = 'var(--color-primary, #3b82f6)';
        btn.style.color = 'white';
        
        // Update form fields based on type
        updateFormForType(type);
    });
});

// Initialize first button as selected
if (typeButtons.length > 0) {
    typeButtons[0].click();
}

/** Update form fields visibility based on product type */
function updateFormForType(type) {
    const priceContainer = document.querySelector('[data-price-container]');
    const priceLabel = document.querySelector('[data-price-label]');
    const priceHelp = document.querySelector('[data-price-help]');
    const menuEditor = document.querySelector('[data-menu-editor]');
    
    // Price visibility
    if (priceContainer) {
        if (type === 'carta') {
            priceContainer.hidden = true;
        } else {
            priceContainer.hidden = false;
            if (priceLabel) priceLabel.textContent = type === 'menu' ? 'Precio Global del Menú (€)' : 'Precio (€)';
            if (priceHelp) priceHelp.textContent = type === 'menu' ? 'Precio fijo para todo el menú' : 'Precio del producto';
        }
    }
    
    // Menu editor visibility
    if (menuEditor) {
        menuEditor.hidden = (type !== 'menu' && type !== 'carta');
    }
    
    // Update item price visibility in menu builder
    updateMenuItemPrices(type);
}

/** Show/hide price field in menu items based on type */
function updateMenuItemPrices(type) {
    const itemPrices = document.querySelectorAll('.data-item-price');
    itemPrices.forEach(input => {
        if (type === 'menu') {
            input.parentElement.style.display = 'none';
        } else {
            input.parentElement.style.display = '';
        }
    });
}

// ── Pre-fill Sample Menu Template ──────────────────────────────────
document.querySelector('[data-btn-template-menu]')?.addEventListener('click', () => {
    const sample = {
        badge: "De lunes a viernes (Suplemento de +3€ en fin de semana)",
        includes: "Incluye agua, refresco, vino o cerveza",
        sections: [
            {
                title_es: "PRIMEROS PLATOS",
                items_es: [
                    "Pasta con boloñesa artesana",
                    "Ensaladilla rusa clásica",
                    "Croquetas caseras de pollo y jamón",
                    "Fideuá con sabor a mediterráneo"
                ]
            },
            {
                title_es: "SEGUNDOS PLATOS",
                items_es: [
                    "Cuarto de pollo a l'ast, la joya de la casa",
                    "Arroz con verduras y pollo al curry suave",
                    "Fingers de pollo con salsa de yogur, miel y mostaza",
                    "Canelones de rustido al estilo de la abuela"
                ]
            },
            {
                title_es: "POSTRES",
                items_es: [
                    "Flan casero",
                    "Tarta de Queso",
                    "Yogurt con mermelada",
                    "Helado o Café"
                ]
            }
        ]
    };
    renderMenuBuilder(sample);
    syncMenuBuilderToJson();
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
                    toggleEmptyState(TBODY, 6, 'No hay productos. ¡Crea el primero!');
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
    toggleEmptyState(TBODY, 6, 'No hay productos.');

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

// ── Image Upload Handling ────────────────────────────────────────
const uploadBtn = document.querySelector('[data-btn-upload-file]');
const fileInput = document.getElementById('prod-image-file');

uploadBtn?.addEventListener('click', () => {
    fileInput?.click();
});

fileInput?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showToast('La imagen supera los 5 MB permitidos', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', getCsrfToken());

    uploadBtn.disabled = true;
    uploadBtn.textContent = '⏳ Subiendo...';

    try {
        const response = await fetch('/api/pitocuixa/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': getCsrfToken(),
            },
        });

        const json = await response.json();

        if (json.error) {
            showToast(json.message || 'Error al subir imagen', 'error');
        } else if (json.url) {
            if (imageInput) {
                imageInput.value = json.url;
                imageInput.dispatchEvent(new Event('input', { bubbles: true }));
                imageInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            showToast('Imagen subida y optimizada a WebP', 'success');
        }
    } catch (err) {
        showToast('Error de conexión al subir la imagen', 'error');
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.textContent = '📁 Subir Imagen';
        fileInput.value = '';
    }
});

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
