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

