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

            <a href="#" class="admin-login__reenroll" data-2fa-reenroll>Perdí mi autentificador</a>

            <p class="admin-login__alt">
                <a href="#" data-mail-code-toggle>Envíame un código por email</a>
            </p>
            <div class="admin-field" data-mail-code-wrap hidden>
                <label for="mail-code" class="admin-field__label">Código de 6 dígitos (email)</label>
                <input id="mail-code" name="mail_code" type="text" class="admin-field__input" maxlength="6" autocomplete="one-time-code" inputmode="numeric">
                <button type="button" class="admin-btn admin-btn--ghost" data-mail-code-submit>Entrar con código email</button>
            </div>
        </form>

        <!-- ── Step 3: 2FA enrollment (hidden until required) ───────── -->
        <form class="admin-login__form" data-admin-enroll hidden>
            <p class="admin-login__subtitle">Configura o reconfigura tu autenticación en dos pasos</p>
            <p class="admin-login__desc">
                Escanea el código QR con tu app autenticadora y confirma con el
                código de 6 dígitos para activar la protección de este acceso.
                Si ya tenías un autenticador configurado, este paso lo sustituye.
            </p>

            <div data-enroll-status class="admin-2fa-status" role="status"></div>

            <div id="enroll-qrcode" class="admin-2fa-qr" hidden></div>

            <div class="admin-field">
                <label for="enroll-code" class="admin-field__label">
                    Código de 6 dígitos
                </label>
                <input id="enroll-code"
                       name="code"
                       type="text"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       class="admin-field__input"
                       maxlength="6"
                       required
                       autofocus>
            </div>

            <div class="admin-login__error" data-enroll-error role="alert" hidden></div>

            <button type="submit" class="admin-btn admin-btn--primary admin-login__submit">
                Activar y acceder
            </button>

            <p class="admin-login__alt">
                ¿No tienes el autenticador a mano?
                <a href="#" data-backup-toggle>Usa un código de respaldo</a>
            </p>
            <div class="admin-field" data-backup-wrap hidden>
                <label for="backup-code" class="admin-field__label">Código de respaldo</label>
                <input id="backup-code" name="backup_code" type="text" class="admin-field__input" maxlength="32" autocomplete="off">
                <button type="button" class="admin-btn admin-btn--ghost" data-backup-submit>Entrar con respaldo</button>
            </div>
        </form>

        <a href="/" class="admin-login__back">← Volver al sitio</a>
    </div>
</section>

<!-- qrcode lib from unpkg (allowed by CSP) for enrollment QR rendering -->
<script src="https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js"></script>
<script type="module">
/**
 * Admin Login — AJAX form submission with TOTP second factor.
 * Prevents redirect, handles errors inline.
 */
const loginForm   = document.querySelector('[data-admin-login]');
const twoFaForm   = document.querySelector('[data-admin-2fa]');
const loginError  = document.querySelector('[data-login-error]');
const twoFaError  = document.querySelector('[data-2fa-error]');

const enrollForm  = document.querySelector('[data-admin-enroll]');
const enrollError = document.querySelector('[data-enroll-error]');
const enrollStatus = document.querySelector('[data-enroll-status]');

let challengeToken = null;
let enrollToken    = null;

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
        } else if (json.two_factor_enroll_required) {
            // Admin has no 2FA yet — enroll at the login screen.
            showEnrollment(json.enroll_token);
        } else {
            window.location.href = '/pitocuixa';
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
            window.location.href = '/pitocuixa';
        }
    } catch (err) {
        showError(twoFaError, 'Error de conexión');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Verificar';
    }
});

// Re-enroll handler: reuse the existing enroll flow with the current challenge
// token. enroll-start accepts it whether totp_enabled = 0 (first-time) or = 1
// (re-enroll), so this safely stages a new secret without overwriting the
// active one until confirmed.
document.querySelector('[data-2fa-reenroll]')?.addEventListener('click', (e) => {
    e.preventDefault();
    if (challengeToken) {
        showEnrollment(challengeToken);
    }
});

// ── 2FA enrollment-at-login flow ──────────────────────────────────────────
function showEnrollment(token) {
    enrollToken = token;
    loginForm.hidden = true;
    twoFaForm.hidden = true;
    enrollForm.hidden = false;
    enrollError.hidden = true;
    enrollStatus.textContent = 'Generando configuración…';
    enrollStart();
}

async function enrollStart() {
    const qr        = document.getElementById('enroll-qrcode');
    const codeInput = document.getElementById('enroll-code');

    if (codeInput) codeInput.focus();

    try {
        const res  = await fetch('/api/auth/2fa-enroll-start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enroll_token: enrollToken }),
        });

        const json = await res.json();

        if (json.error) {
            showError(enrollError, json.message || 'No se pudo iniciar el enrolamiento.');
            enrollStatus.textContent = '';
            return;
        }

        const data = json.data ?? {};
        enrollStatus.textContent = '';

        // QR code only (qrcodejs global from unpkg). The secret and backup
        // codes are NOT exposed to the client — scan the QR and enter the 6-digit
        // code. This is intentionally "scan → code → confirm", one and done.
        if (qr && typeof QRCode !== 'undefined') {
            qr.hidden = false;
            qr.innerHTML = '';
            new QRCode(qr, {
                text: data.provisioning_uri,
                width: 200,
                height: 200,
                colorDark: '#1a1a1a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        }
    } catch (err) {
        enrollStatus.textContent = '';
        showError(enrollError, 'Error de red al iniciar el enrolamiento.');
    }
}

enrollForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const form      = e.currentTarget;
    const submitBtn = form.querySelector('button[type="submit"]');
    const codeInput = form.querySelector('input[name="code"]');

    if (!enrollToken) {
        showError(enrollError, 'Sesión de enrolamiento caducada. Vuelve a iniciar sesión.');
        return;
    }

    const body = JSON.stringify({
        enroll_token: enrollToken,
        code: codeInput.value.trim(),
    });

    submitBtn.disabled = true;
    submitBtn.textContent = '...';

    try {
        const res  = await fetch('/api/auth/2fa-enroll-confirm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
        });

        const json = await res.json();

        if (json.error) {
            showError(enrollError, json.message || 'Código incorrecto');
        } else {
            // Session cookie is set server-side; just enter the admin.
            window.location.href = '/pitocuixa';
        }
    } catch (err) {
        showError(enrollError, 'Error de conexión');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Activar y acceder';
    }
});

// ── Backup-code alternative on the enroll/recovery screen ────────────────
// Show/hide the backup-code field. When shown, the 6-digit enroll input is
// hidden so the user enters only the backup code.
document.querySelector('[data-backup-toggle]')?.addEventListener('click', (e) => {
    e.preventDefault();
    const wrap   = document.querySelector('[data-backup-wrap]');
    const enrollCode = document.getElementById('enroll-code');
    if (!wrap) return;
    wrap.hidden = !wrap.hidden;
    if (enrollCode) enrollCode.closest('.admin-field')?.classList.toggle('admin-field--hidden', !wrap.hidden);
});

// Submit a backup code: POST to 2fa-verify using the same token (the challenge
// token when reached via re-enroll, or the enroll token for first-time enroll
// — both are valid challenge tokens).
document.querySelector('[data-backup-submit]')?.addEventListener('click', async (e) => {
    e.preventDefault();
    const wrap       = document.querySelector('[data-backup-wrap]');
    const codeInput  = wrap?.querySelector('input[name="backup_code"]');

    if (!enrollToken) {
        showError(enrollError, 'Sesión de enrolamiento caducada. Vuelve a iniciar sesión.');
        return;
    }
    if (!codeInput || codeInput.value.trim() === '') {
        showError(enrollError, 'Introduce el código de respaldo.');
        return;
    }

    const btn = e.currentTarget;
    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res  = await fetch('/api/auth/2fa-verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                challenge_token: enrollToken,
                code: codeInput.value.trim(),
            }),
        });

        const json = await res.json();

        if (json.error) {
            showError(enrollError, json.message || 'Código de respaldo incorrecto');
        } else {
            window.location.href = '/pitocuixa';
        }
    } catch (err) {
        showError(enrollError, 'Error de conexión');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Entrar con respaldo';
    }
});

// ── Mail-code flow: request + verify ────────────────────────────────────────
document.querySelector('[data-mail-code-toggle]')?.addEventListener('click', async (e) => {
    e.preventDefault();
    if (!challengeToken) {
        showError(twoFaError, 'Sesión caducada. Vuelve a iniciar sesión.');
        return;
    }
    // Request the code via email first
    try {
        const res  = await fetch('/api/auth/2fa-mail-code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ challenge_token: challengeToken }),
        });
        const json = await res.json();
        if (json.error) {
            showError(twoFaError, json.message || 'No se pudo enviar el código');
            return;
        }
        // Show the code input
        const wrap = document.querySelector('[data-mail-code-wrap]');
        if (wrap) wrap.hidden = false;
        showError(twoFaError, '');
        twoFaError.hidden = true;
    } catch (err) {
        showError(twoFaError, 'Error de conexión');
    }
});

document.querySelector('[data-mail-code-submit]')?.addEventListener('click', async (e) => {
    e.preventDefault();
    const wrap      = document.querySelector('[data-mail-code-wrap]');
    const codeInput = wrap?.querySelector('input[name="mail_code"]');

    if (!challengeToken) {
        showError(twoFaError, 'Sesión caducada. Vuelve a iniciar sesión.');
        return;
    }
    if (!codeInput || codeInput.value.trim() === '') {
        showError(twoFaError, 'Introduce el código de 6 dígitos.');
        return;
    }

    const btn = e.currentTarget;
    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res  = await fetch('/api/auth/2fa-verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                challenge_token: challengeToken,
                code: codeInput.value.trim(),
            }),
        });
        const json = await res.json();
        if (json.error) {
            showError(twoFaError, json.message || 'Código incorrecto');
        } else {
            window.location.href = '/pitocuixa';
        }
    } catch (err) {
        showError(twoFaError, 'Error de conexión');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Entrar con código email';
    }
});
</script>
