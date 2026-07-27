<?php
/**
 * Pit o Cuixa — Admin Login Page Controller
 *
 * GET /admin/login — Renders login form.
 * POST handling is done via /api/auth/login (API).
 *
 * @package Pit\Cuixa\Backend\Pages\Admin
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages\Admin;

class Login
{
    /**
     * Render the admin login page.
     */
    public static function render(): void
    {
        $meta = [
            'title'       => __('nav.login') . ' — ' . __('site.name'),
            'description' => __('error.401.desc'),
            'canonical'   => \Config::siteUrl() . '/admin/login',
            'index'       => false, // noindex
        ];

        $data = [
            'locale'     => LANG,
            'csrf_token' => \Pit\Cuixa\Backend\Auth\Auth::getCsrfToken(),
        ];

        \renderPage('admin/login', $meta, $data);
    }
}
