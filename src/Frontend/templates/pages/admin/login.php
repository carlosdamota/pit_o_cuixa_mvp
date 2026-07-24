<?php
/**
 * Pit o Cuixa — Admin Login Template
 *
 * Variables from $pageData:
 *   - csrf_token: CSRF token for form validation
 *   - locale: current language code
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages\Admin
 */

$csrfToken = $pageData['csrf_token'] ?? '';
?>
<!-- ============================================================
     Admin Login
     ============================================================ -->
<section class="admin-login">
    <div class="admin-login__card">
        <h1 class="admin-login__title"><?= __('site.name') ?></h1>
        <p class="admin-login__subtitle"><?= __('nav.login') ?></p>

        <?php if (isset($_GET['error'])): ?>
            <div class="admin-alert admin-alert--error" role="alert">
                <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="admin-login__form"
              method="POST"
              action="/api/auth/login"
              data-admin-login>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="admin-field">
                <label for="login-username" class="admin-field__label">
                    <?= __('nav.login') ?>
                </label>
                <input id="login-username"
                       name="username"
                       type="text"
                       class="admin-field__input"
                       autocomplete="username"
                       required
                       autofocus>
            </div>

            <div class="admin-field">
                <label for="login-password" class="admin-field__label">
                    Contraseña
                </label>
                <input id="login-password"
                       name="password"
                       type="password"
                       class="admin-field__input"
                       autocomplete="current-password"
                       required>
            </div>

            <div class="admin-login__error" data-login-error role="alert" hidden></div>

            <button type="submit" class="admin-btn admin-btn--primary admin-login__submit">
                <?= __('nav.login') ?>
            </button>
        </form>

        <a href="/" class="admin-login__back">← <?= __('nav.home') ?></a>
    </div>
</section>

<script type="module">
/**
 * Admin Login — AJAX form submission.
 * Prevents redirect, handles errors inline.
 */
document.querySelector('[data-admin-login]')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const form    = e.currentTarget;
    const errorEl = document.querySelector('[data-login-error]');
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    // Build JSON body
    const body = JSON.stringify({
        username: formData.get('username'),
        password: formData.get('password'),
    });

    submitBtn.disabled = true;
    submitBtn.textContent = '...';

    try {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
        });

        const json = await res.json();

        if (json.error) {
            errorEl.textContent = json.message || 'Error de autenticación';
            errorEl.hidden = false;
        } else {
            // Success — redirect to admin dashboard
            window.location.href = '/admin';
        }
    } catch (err) {
        errorEl.textContent = 'Error de conexión';
        errorEl.hidden = false;
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '<?= __('nav.login') ?>';
    }
});
</script>
