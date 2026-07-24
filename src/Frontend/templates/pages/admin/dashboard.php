<?php
/**
 * Pit o Cuixa — Admin Dashboard Template
 *
 * Variables from $pageData:
 *   - user: authenticated user row
 *   - total_products: int
 *   - total_categories: int
 *   - featured_products: int
 *   - per_category: array of [name_es, name_en, cnt]
 *   - csrf_token: CSRF token
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$user            = $pageData['user'] ?? [];
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
            <p class="admin-header__user">
                <?= htmlspecialchars($user['display_name'] ?? $user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </p>
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
                        <?php foreach ($perCategory as $cat): ?>
                            <tr>
                                <td><?= htmlspecialchars($cat["name_{$lang}"] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $cat['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
