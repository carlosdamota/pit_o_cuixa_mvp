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
        <h1 class="admin-login__title">Pit o Cuixa</h1>
        <p class="admin-login__subtitle">Acceso Administración</p>

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
                    Usuario
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
                Iniciar Sesión
            </button>
        </form>

        <!-- ── Step 2: TOTP second factor (hidden until required) ─────── -->
        <form class="admin-login__form" data-admin-2fa hidden>
            <p class="admin-login__subtitle">Verificación en dos pasos</p>

            <div class="admin-field">
                <label for="login-code" class="admin-field__label">
                    Código de 6 dígitos
                </label>
                <input id="login-code"
                       name="code"
                       type="text"
                       inputmode="text"
                       autocomplete="one-time-code"
                       class="admin-field__input"
                       maxlength="32"
                       required
                       autofocus>
            </div>

            <div class="admin-login__error" data-2fa-error role="alert" hidden></div>

            <button type="submit" class="admin-btn admin-btn--primary admin-login__submit">
                Verificar
            </button>

            <label class="admin-login__backup-toggle">
                <input type="checkbox" data-2fa-backup-toggle>
                Usar código de respaldo
            </label>
        </form>

        <a href="/" class="admin-login__back">← Volver al sitio</a>
    </div>
</section>

<script type="module">
/**
 * Admin Login — AJAX form submission with TOTP second factor.
 * Prevents redirect, handles errors inline.
 */
const loginForm   = document.querySelector('[data-admin-login]');
const twoFaForm   = document.querySelector('[data-admin-2fa]');
const loginError  = document.querySelector('[data-login-error]');
const twoFaError  = document.querySelector('[data-2fa-error]');
const backupToggle = document.querySelector('[data-2fa-backup-toggle]');

let challengeToken = null;

function showError(el, msg) {
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
}

function showTwoFactor(token) {
    challengeToken = token;
    loginForm.hidden = true;
    twoFaForm.hidden = false;
    twoFaForm.querySelector('input[name="code"]')?.focus();
}

loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const form      = e.currentTarget;
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    const body = JSON.stringify({
        username: formData.get('username'),
        password: formData.get('password'),
    });

    submitBtn.disabled = true;
    submitBtn.textContent = '...';

    try {
        const res  = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
        });

        const json = await res.json();

        if (json.error) {
            showError(loginError, json.message || 'Error de autenticación');
        } else if (json.two_factor_required) {
            // Switch to the second-step panel
            showTwoFactor(json.challenge_token);
        } else {
            window.location.href = '/admin';
        }
    } catch (err) {
        showError(loginError, 'Error de conexión');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Iniciar Sesión';
    }
});

twoFaForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const form      = e.currentTarget;
    const submitBtn = form.querySelector('button[type="submit"]');
    const codeInput = form.querySelector('input[name="code"]');

    if (!challengeToken) {
        showError(twoFaError, 'Sesión de verificación caducada. Vuelve a iniciar sesión.');
        return;
    }

    const body = JSON.stringify({
        challenge_token: challengeToken,
        code: codeInput.value.trim(),
    });

    submitBtn.disabled = true;
    submitBtn.textContent = '...';

    try {
        const res  = await fetch('/api/auth/2fa-verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
        });

        const json = await res.json();

        if (json.error) {
            showError(twoFaError, json.message || 'Código incorrecto');
        } else {
            window.location.href = '/admin';
        }
    } catch (err) {
        showError(twoFaError, 'Error de conexión');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Verificar';
    }
});

// Toggle placeholder/label depending on backup-code mode
backupToggle?.addEventListener('change', () => {
    const codeInput = twoFaForm?.querySelector('input[name="code"]');
    if (!codeInput) return;
    if (backupToggle.checked) {
        codeInput.placeholder = 'Código de respaldo';
        codeInput.maxLength = 32;
    } else {
        codeInput.placeholder = '';
        codeInput.maxLength = 6;
    }
});
</script>
