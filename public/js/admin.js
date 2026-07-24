/**
 * Pit o Cuixa — Admin Shared Module
 *
 * ES module consumed by all admin page scripts.
 * Provides API client, alert/toast feedback, loading states,
 * modal dialog, image preview, form validation,
 * and UI helpers used across CRUD pages.
 *
 * @module admin
 */

/**
 * Read CSRF token from <meta name="csrf-token">.
 * @returns {string}
 */
export function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Generic admin API fetch wrapper.
 * Automatically injects CSRF token and JSON content-type.
 *
 * @param   {string}  method  HTTP method (GET, POST, PUT, DELETE)
 * @param   {string}  url     Full URL to fetch
 * @param   {object}  [body]  Optional JSON body (will be JSON.stringify'd)
 * @returns {Promise<object>} Parsed JSON response
 */
export async function api(method, url, body = null) {
  const headers = {
    'Content-Type': 'application/json',
    'X-CSRF-Token': getCsrfToken(),
  };

  const res = await fetch(url, {
    method,
    headers,
    body: body ? JSON.stringify(body) : null,
    credentials: 'same-origin',
  });

  return res.json();
}

/**
 * Show an alert banner on the page.
 * Looks for [data-alert-success] and [data-alert-error] elements.
 * Auto-hides after 5 seconds.
 *
 * @param {string}  message  Alert text
 * @param {string}  type     'success' or 'error'
 */
export function showAlert(message, type) {
  const sel = type === 'success' ? '[data-alert-success]' : '[data-alert-error]';
  const el = document.querySelector(sel);
  if (!el) return;

  el.textContent = message;
  el.hidden = false;

  setTimeout(() => {
    el.hidden = true;
  }, 5000);
}

/**
 * Initialize CSRF token by reading from meta tag.
 * Useful as an early init call — idempotent.
 */
export function initCsrfToken() {
  getCsrfToken();
}

/**
 * Accessible modal dialog component.
 * Replaces native confirm() with keyboard-navigable modal.
 *
 * @example
 *   const modal = new AdminModal();
 *   modal.open('Eliminar producto', `¿Eliminar "${name}"?`, async () => {
 *     await api('DELETE', url);
 *   });
 */
export class AdminModal {
  constructor() {
    this._overlay = null;
    this._onKeyDown = this._handleKeyDown.bind(this);
    this._previousActiveElement = null;
  }

  /**
   * Open a confirmation modal.
   *
   * @param {string}   title      Modal header text
   * @param {string}   message    Modal body text
   * @param {Function} onConfirm  Async or sync callback when user confirms
   */
  open(title, message, onConfirm) {
    // Guard: close any existing modal first
    this.close();

    this._previousActiveElement = document.activeElement;

    this._overlay = document.createElement('div');
    this._overlay.className = 'admin-modal__overlay';
    this._overlay.setAttribute('data-modal-overlay', '');

    const dialog = document.createElement('div');
    dialog.className = 'admin-modal__content';
    dialog.setAttribute('role', 'alertdialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'admin-modal-title');

    dialog.innerHTML =
      '<div class="admin-modal__header">' +
        '<h2 class="admin-modal__title" id="admin-modal-title">' + this._escapeHtml(title) + '</h2>' +
        '<button class="admin-modal__close" data-modal-close aria-label="Cerrar">&times;</button>' +
      '</div>' +
      '<div class="admin-modal__body">' + this._escapeHtml(message) + '</div>' +
      '<div class="admin-modal__footer">' +
        '<button class="admin-btn admin-btn--ghost" data-modal-cancel>Cancelar</button>' +
        '<button class="admin-btn admin-btn--danger" data-modal-confirm>Eliminar</button>' +
      '</div>';

    this._overlay.appendChild(dialog);
    document.body.appendChild(this._overlay);

    // Set focus to confirm button
    const confirmBtn = dialog.querySelector('[data-modal-confirm]');
    if (confirmBtn) confirmBtn.focus();

    // Event handlers
    dialog.querySelector('[data-modal-close]').addEventListener('click', () => this.close());
    dialog.querySelector('[data-modal-cancel]').addEventListener('click', () => this.close());
    dialog.querySelector('[data-modal-confirm]').addEventListener('click', () => {
      const result = onConfirm();
      // Close only if the callback doesn't return a promise
      if (result && typeof result.then === 'function') {
        // For async callbacks, close is handled by the caller
        return;
      }
      this.close();
    });
    this._overlay.addEventListener('click', (e) => {
      if (e.target === this._overlay) this.close();
    });

    document.addEventListener('keydown', this._onKeyDown);
  }

  /**
   * Close the modal and restore focus to the previously active element.
   */
  close() {
    if (this._overlay) {
      this._overlay.remove();
      this._overlay = null;
    }
    document.removeEventListener('keydown', this._onKeyDown);

    if (this._previousActiveElement && typeof this._previousActiveElement.focus === 'function') {
      this._previousActiveElement.focus();
      this._previousActiveElement = null;
    }
  }

  /**
   * Destroy the modal instance and clean up.
   */
  destroy() {
    this.close();
    this._onKeyDown = null;
  }

  /** @private */
  _handleKeyDown(e) {
    if (e.key === 'Escape') {
      this.close();
      e.preventDefault();
      return;
    }

    if (e.key === 'Tab') {
      const focusable = this._overlay.querySelectorAll(
        'button:not([disabled]):not([hidden]), [href], input:not([disabled]):not([hidden]), select:not([disabled]):not([hidden]), textarea:not([disabled]):not([hidden]), [tabindex]:not([tabindex="-1"])'
      );
      if (focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  }

  /** @private */
  _escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
}

/**
 * Show a toast notification.
 * Creates or reuses a toast region container.
 * Auto-dismisses after `duration` ms.
 * Uses CSS animation for slide-in. On dismiss, applies removing class for fade-out.
 *
 * @param {string}  message   Toast text
 * @param {string}  [type]    'success' | 'error' | 'info' (default 'info')
 * @param {number}  [duration]  Auto-dismiss in ms (default 4000). Pass 0 for manual close only.
 */
export function showToast(message, type = 'info', duration = 4000) {
  const region = getToastRegion();

  const toast = document.createElement('div');
  toast.className = `admin-toast admin-toast--${type}`;
  toast.textContent = message;
  toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

  const close = document.createElement('button');
  close.className = 'admin-toast__close';
  close.innerHTML = '&times;';
  close.setAttribute('aria-label', 'Cerrar');
  close.addEventListener('click', () => {
    dismissToast(toast);
  });

  toast.appendChild(close);
  region.appendChild(toast);

  // Auto-dismiss (not for manual-close errors)
  if (duration > 0) {
    setTimeout(() => {
      if (toast.parentNode) {
        dismissToast(toast);
      }
    }, duration);
  }
}

/**
 * Helper: remove a toast with fade-out animation.
 * @param {HTMLElement} toast
 */
function dismissToast(toast) {
  toast.classList.add('admin-toast--removing');
  toast.addEventListener('animationend', () => {
    if (toast.parentNode) toast.remove();
  }, { once: true });
  // Fallback: remove after 300ms if animationend doesn't fire
  setTimeout(() => {
    if (toast.parentNode) toast.remove();
  }, 300);
}

/** @type {HTMLElement|null} */
let _toastRegion = null;

/**
 * Get or create the toast container element.
 * @returns {HTMLElement}
 */
function getToastRegion() {
  if (_toastRegion && _toastRegion.parentNode) return _toastRegion;

  _toastRegion = document.querySelector('[data-toast-region]');
  if (_toastRegion) return _toastRegion;

  _toastRegion = document.createElement('div');
  _toastRegion.className = 'admin-toast-region';
  _toastRegion.setAttribute('data-toast-region', '');
  _toastRegion.setAttribute('aria-live', 'polite');
  document.body.appendChild(_toastRegion);

  return _toastRegion;
}

/**
 * Wrap an async operation with button loading state.
 * Disables the button, shows a spinner via aria-busy,
 * restores state when the promise settles.
 *
 * @param {HTMLButtonElement}  btn       The button element
 * @param {() => Promise<any>} asyncFn   Async function to execute
 * @returns {Promise<any>}               Resolution of asyncFn
 */
export async function withLoading(btn, asyncFn) {
  const originalText = btn.textContent;
  const originalWidth = btn.offsetWidth;

  btn.disabled = true;
  btn.classList.add('admin-btn--loading');
  btn.setAttribute('aria-busy', 'true');
  btn.style.minWidth = originalWidth + 'px';
  btn.textContent = 'Cargando...';

  try {
    const result = await asyncFn();
    return result;
  } finally {
    btn.disabled = false;
    btn.classList.remove('admin-btn--loading');
    btn.removeAttribute('aria-busy');
    btn.style.minWidth = '';
    btn.textContent = originalText;
  }
}

/**
 * Bind image URL input to a preview thumbnail.
 * Shows a preview as the user types the URL.
 *
 * @param {HTMLInputElement} inputEl  The image_url input element
 * @param {HTMLElement}      previewEl  The preview container element
 */
export function bindImagePreview(inputEl, previewEl) {
  if (!inputEl || !previewEl) return;

  const img = document.createElement('img');
  img.className = 'admin-image-preview__img';
  img.alt = '';
  img.loading = 'lazy';

  const placeholder = document.createElement('span');
  placeholder.className = 'admin-image-preview__placeholder';
  placeholder.textContent = '?';

  let currentUrl = '';

  function updatePreview() {
    const url = inputEl.value.trim();

    if (url === currentUrl) return;
    currentUrl = url;

    // Clear previous content
    previewEl.innerHTML = '';
    previewEl.classList.remove('admin-image-preview--visible');

    if (!url) return;

    // Show placeholder immediately
    previewEl.appendChild(placeholder);
    previewEl.classList.add('admin-image-preview--visible');

    // Try loading the image
    const testImg = new Image();
    testImg.onload = () => {
      img.src = url;
      previewEl.innerHTML = '';
      previewEl.appendChild(img);
      previewEl.classList.add('admin-image-preview--visible');
    };
    testImg.onerror = () => {
      // Keep placeholder on error
      previewEl.innerHTML = '';
      previewEl.appendChild(placeholder);
      previewEl.classList.add('admin-image-preview--visible');
    };
    testImg.src = url;
  }

  // Update on input and blur
  inputEl.addEventListener('input', updatePreview);
  inputEl.addEventListener('blur', updatePreview);

  // Initial check
  updatePreview();
}

/**
 * Validate a form field and show/hide error messages.
 * Uses Constraint Validation API when available.
 *
 * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
 * @param {string} [customMessage]  Optional custom error message
 * @returns {boolean}  Whether the field is valid
 */
export function validateField(field, customMessage) {
  const wrapper = field.closest('.admin-field');
  if (!wrapper) return field.validity?.valid ?? true;

  const isInvalid = !field.validity.valid || (field.hasAttribute('required') && !field.value.trim());

  wrapper.classList.remove('admin-field--invalid', 'admin-field--valid');

  // Ensure error element exists
  let errorEl = wrapper.querySelector('.admin-field__error');
  if (!errorEl) {
    errorEl = document.createElement('div');
    errorEl.className = 'admin-field__error';
    wrapper.appendChild(errorEl);
  }

  if (isInvalid) {
    wrapper.classList.add('admin-field--invalid');
    errorEl.textContent = customMessage || field.validationMessage || 'Este campo es obligatorio';
    field.setAttribute('aria-invalid', 'true');
    return false;
  }

  // Only show valid state when field has been touched (has value)
  if (field.value.trim()) {
    wrapper.classList.add('admin-field--valid');
  }
  field.removeAttribute('aria-invalid');
  errorEl.textContent = '';
  return true;
}

/**
 * Validate all fields in a form.
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}  Whether the entire form is valid
 */
export function validateForm(form) {
  let allValid = true;
  const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea');

  fields.forEach((field) => {
    if (!validateField(field)) {
      allValid = false;
    }
  });

  return allValid;
}
