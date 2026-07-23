<?php
/**
 * Pit o Cuixa — Admin Navigation Partial
 *
 * Sidebar-style navigation for admin pages.
 * Requires $user and $csrfToken from the page controller.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

$currentPath = $_SERVER['REQUEST_URI'] ?? '/admin';
$userDisplay = $user['display_name'] ?? $user['username'] ?? '';
?>
<nav class="admin-nav" aria-label="Admin navigation">
    <div class="admin-nav__header">
        <a href="/admin" class="admin-nav__brand">Pit o Cuixa</a>
        <span class="admin-nav__user"><?= htmlspecialchars($userDisplay, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <ul class="admin-nav__list">
        <li class="admin-nav__item">
            <a href="/admin"
               class="admin-nav__link <?= $currentPath === '/admin' ? 'admin-nav__link--active' : '' ?>">
                Dashboard
            </a>
        </li>
        <li class="admin-nav__item">
            <a href="/admin/products"
               class="admin-nav__link <?= str_starts_with($currentPath, '/admin/products') ? 'admin-nav__link--active' : '' ?>">
                Productos
            </a>
        </li>
        <li class="admin-nav__item">
            <a href="/admin/categories"
               class="admin-nav__link <?= str_starts_with($currentPath, '/admin/categories') ? 'admin-nav__link--active' : '' ?>">
                Categorías
            </a>
        </li>
    </ul>

    <div class="admin-nav__footer">
        <a href="/" class="admin-nav__link" target="_blank">Ver sitio</a>
        <form method="POST" action="/api/auth/logout" data-logout-form style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="admin-nav__link admin-nav__link--logout">
                <?= __('nav.logout') ?>
            </button>
        </form>
    </div>
</nav>
