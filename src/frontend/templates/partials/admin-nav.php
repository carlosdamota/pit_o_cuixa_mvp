<?php
/**
 * Pit o Cuixa — Admin Navigation Partial
 *
 * Sidebar-style navigation for admin pages.
 * Requires $csrfToken from the page controller.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

$currentPath = $_SERVER['REQUEST_URI'] ?? '/pitocuixa';
?>
<nav class="admin-nav" aria-label="Navegación del Panel de Administración">
    <div class="admin-nav__header">
        <a href="/pitocuixa" class="admin-nav__brand">
            <span class="admin-nav__brand-title">Pit o Cuixa</span>
            <span class="admin-nav__brand-badge">Admin</span>
        </a>
        <button type="button"
                class="admin-nav__toggle"
                data-nav-toggle
                aria-controls="admin-nav-list"
                aria-expanded="false"
                aria-label="Abrir menú de secciones">
            <svg class="admin-nav__toggle-icon admin-nav__toggle-icon--open" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            <svg class="admin-nav__toggle-icon admin-nav__toggle-icon--close" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <ul class="admin-nav__list" id="admin-nav-list">
        <li class="admin-nav__item">
            <a href="/pitocuixa"
               class="admin-nav__link <?= $currentPath === '/pitocuixa' ? 'admin-nav__link--active' : '' ?>">
                <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="admin-nav__item">
            <a href="/pitocuixa/products"
               class="admin-nav__link <?= str_starts_with($currentPath, '/pitocuixa/products') ? 'admin-nav__link--active' : '' ?>">
                <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Productos</span>
            </a>
        </li>
        <li class="admin-nav__item">
            <a href="/pitocuixa/categories"
               class="admin-nav__link <?= str_starts_with($currentPath, '/pitocuixa/categories') ? 'admin-nav__link--active' : '' ?>">
                <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <span>Categorías</span>
            </a>
        </li>
        <li class="admin-nav__item">
            <a href="/pitocuixa/import-export"
               class="admin-nav__link <?= str_starts_with($currentPath, '/pitocuixa/import-export') ? 'admin-nav__link--active' : '' ?>">
                <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Importar / Exportar</span>
            </a>
        </li>
        <li class="admin-nav__item">
            <a href="/pitocuixa/settings"
               class="admin-nav__link <?= str_starts_with($currentPath, '/pitocuixa/settings') ? 'admin-nav__link--active' : '' ?>">
                <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Ajustes</span>
            </a>
        </li>
    </ul>

    <div class="admin-nav__footer">
        <a href="/" class="admin-nav__link admin-nav__link--external" target="_blank" rel="noopener">
            <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            <span>Ver sitio público</span>
        </a>
        <form method="POST" action="/api/auth/logout" data-logout-form class="admin-nav__logout-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="admin-nav__link admin-nav__link--logout">
                <svg class="admin-nav__icon" width="20" height="20" style="width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</nav>
