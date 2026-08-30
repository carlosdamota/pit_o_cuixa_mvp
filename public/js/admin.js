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
 * IIS shared hosting (dinahosting) only routes GET/HEAD/POST to PHP —
 * PUT/DELETE requests are rejected by the web server with 405 before PHP
 * runs. Tunnels those verbs through POST with the standard
 * X-HTTP-Method-Override header, which the front controller unwraps.
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

  let fetchMethod = method;
  if (['PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
    fetchMethod = 'POST';
    headers['X-HTTP-Method-Override'] = method.toUpperCase();
  }

  const res = await fetch(url, {
    method: fetchMethod,
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
 * @param {number}  [duration]  Auto-dismiss in ms. Default: 4000 for success/info, 0 (manual) for error.
 */
export function showToast(message, type = 'info', duration = type === 'error' ? 0 : 4000) {
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
 * @param {string}           [altText]  Alt text for the image (default: empty)
 */
export function bindImagePreview(inputEl, previewEl, altText = '') {
  if (!inputEl || !previewEl) return;

  const img = document.createElement('img');
  img.className = 'admin-image-preview__img';
  img.alt = altText;
  img.loading = 'lazy';

  const placeholder = document.createElement('span');
  placeholder.className = 'admin-image-preview__placeholder';
  placeholder.textContent = '?';

  const errorMsg = document.createElement('div');
  errorMsg.className = 'admin-image-preview__error';
  errorMsg.textContent = 'No se pudo cargar la imagen';

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
      img.alt = altText;
      previewEl.innerHTML = '';
      previewEl.appendChild(img);
      previewEl.classList.add('admin-image-preview--visible');
    };
    testImg.onerror = () => {
      // Show placeholder + error message
      previewEl.innerHTML = '';
      previewEl.appendChild(placeholder);
      previewEl.appendChild(errorMsg);
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

// ======================================================================
//  Phase 3 — Row Animation Helpers
// ======================================================================

/**
 * Escape HTML special characters for safe innerHTML insertion.
 * @param {string} str
 * @returns {string}
 */
function escHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

/**
 * Insert a new table row with slide-in animation.
 * @param {HTMLTableSectionElement} tbody
 * @param {string} html  Row HTML (<tr>...</tr>)
 * @param {number} [index]  Insert position (-1 or undefined = append)
 * @returns {HTMLTableRowElement}
 */
export function insertTableRow(tbody, html, index = -1) {
  // Remove empty state row if present
  const emptyRow = tbody.querySelector('.admin-table__empty');
  if (emptyRow) emptyRow.remove();

  const temp = document.createElement('tbody');
  temp.innerHTML = html.trim();
  const row = temp.firstElementChild;

  if (index >= 0 && index < tbody.children.length) {
    tbody.insertBefore(row, tbody.children[index]);
  } else {
    tbody.appendChild(row);
  }

  requestAnimationFrame(() => {
    row.style.animation = 'admin-row-in 250ms ease';
  });

  row.addEventListener('animationend', () => {
    row.style.animation = '';
  }, { once: true });

  return row;
}

/**
 * Remove a table row with slide-out animation.
 * @param {HTMLTableRowElement} row
 * @returns {Promise<void>} Resolves when row is removed
 */
export function removeTableRow(row) {
  return new Promise((resolve) => {
    row.style.animation = 'admin-row-out 200ms ease forwards';
    row.addEventListener('animationend', () => {
      row.remove();
      resolve();
    }, { once: true });
    // Fallback
    setTimeout(() => {
      if (row.parentNode) row.remove();
      resolve();
    }, 300);
  });
}

/**
 * Update a table row's content with a brief highlight pulse.
 * @param {HTMLTableRowElement} row
 * @param {string} html  New <tr>...</tr> HTML
 */
export function updateTableRow(row, html) {
  const temp = document.createElement('tbody');
  temp.innerHTML = html.trim();
  const newRow = temp.firstElementChild;

  row.innerHTML = newRow.innerHTML;
  // Copy data attributes
  for (const attr of newRow.attributes) {
    if (attr.name.startsWith('data-')) {
      row.setAttribute(attr.name, attr.value);
    }
  }

  // Yellow highlight
  row.style.animation = 'admin-row-highlight 600ms ease';
  row.addEventListener('animationend', () => {
    row.style.animation = '';
  }, { once: true });
}

/**
 * Show or hide the empty state row in a table.
 * @param {HTMLTableSectionElement} tbody
 * @param {number} colspan
 * @param {string} message
 */
export function toggleEmptyState(tbody, colspan, message) {
  const existing = tbody.querySelector('.admin-table__empty');
  if (existing) existing.remove();

  if (tbody.children.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="${colspan}" class="admin-table__empty">${escHtml(message)}</td>`;
    tbody.appendChild(tr);
  }
}

// ======================================================================
//  Phase 3 — Drawer Component
// ======================================================================

/**
 * Slide-out drawer panel for forms.
 *
 * @example
 *   const drawer = new Drawer({
 *     drawer: '[data-drawer]',
 *     overlay: '[data-drawer-overlay]',
 *   });
 *   drawer.open('Crear producto');
 *   drawer.close();
 */
export class Drawer {
  constructor(opts = {}) {
    this.drawer = typeof opts.drawer === 'string'
      ? document.querySelector(opts.drawer)
      : opts.drawer;
    this.overlay = typeof opts.overlay === 'string'
      ? document.querySelector(opts.overlay)
      : opts.overlay;
    this.onClose = opts.onClose || null;
    this._previousActive = null;
    this._onKeyDown = this._handleKeyDown.bind(this);

    // Bind close buttons
    const closeBtns = (this.drawer || document).querySelectorAll('[data-drawer-close], [data-drawer-cancel]');
    closeBtns.forEach(btn => btn.addEventListener('click', () => this.close()));

    // Overlay click to close
    if (this.overlay) {
      this.overlay.addEventListener('click', (e) => {
        if (e.target === this.overlay) this.close();
      });
    }
  }

  /**
   * Open the drawer.
   * @param {string} [title]  Optional title to set in [data-drawer-title]
   */
  open(title) {
    this._previousActive = document.activeElement;

    // Only set title if provided
    if (title !== undefined && this.drawer) {
      const titleEl = this.drawer.querySelector('[data-drawer-title]');
      if (titleEl) titleEl.textContent = title;
    }

    this.overlay.hidden = false;
    this.drawer.hidden = false;

    // Trigger animations
    requestAnimationFrame(() => {
      this.overlay.classList.add('admin-drawer__overlay--visible');
      this.drawer.classList.add('admin-drawer--visible');
    });

    // Focus first input after animation starts
    setTimeout(() => {
      const firstInput = this.drawer.querySelector(
        'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (firstInput && typeof firstInput.focus === 'function') {
        firstInput.focus();
      }
    }, 150);

    document.addEventListener('keydown', this._onKeyDown);
  }

  /**
   * Close the drawer with slide-out animation.
   */
  close() {
    this.overlay.classList.remove('admin-drawer__overlay--visible');
    this.drawer.classList.remove('admin-drawer--visible');

    setTimeout(() => {
      this.overlay.hidden = true;
      this.drawer.hidden = true;
    }, 300);

    document.removeEventListener('keydown', this._onKeyDown);

    if (this._previousActive && typeof this._previousActive.focus === 'function') {
      this._previousActive.focus();
      this._previousActive = null;
    }

    if (typeof this.onClose === 'function') this.onClose();
  }

  /**
   * Clean up event listeners.
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
      const focusable = this.drawer.querySelectorAll(
        'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
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
}

// ======================================================================
//  Phase 3 — Pagination
// ======================================================================

/**
 * Fetch a paginated page from an API endpoint.
 * @param {string} url  Base API URL
 * @param {number} page
 * @param {number} limit
 * @returns {Promise<object>}
 */
export async function fetchPaginated(url, page, limit = 20) {
  const separator = url.includes('?') ? '&' : '?';
  const res = await fetch(`${url}${separator}page=${page}&limit=${limit}`, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': getCsrfToken() },
  });
  return res.json();
}

/**
 * Render pagination controls.
 * Callers should attach a single click listener on the container using
 * paginationClickHandler() to handle page navigation.
 *
 * @param {object} opts
 * @param {HTMLElement} opts.container  The pagination wrapper element
 * @param {number}      opts.currentPage
 * @param {number}      opts.totalPages
 * @param {number}      [opts.total]
 */
export function renderPagination(opts) {
  const { container, currentPage, totalPages, total } = opts;

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  const pages = [];
  const start = Math.max(1, currentPage - 2);
  const end = Math.min(totalPages, currentPage + 2);
  if (start > 1) pages.push(1);
  if (start > 2) pages.push('…');
  for (let i = start; i <= end; i++) pages.push(i);
  if (end < totalPages - 1) pages.push('…');
  if (end < totalPages) pages.push(totalPages);

  container.innerHTML = [
    `<div class="admin-pagination__info">Página ${currentPage} de ${totalPages}${total !== undefined ? ` (${total} en total)` : ''}</div>`,
    '<ul class="admin-pagination__list">',
    ...pages.map(p => {
      if (p === '…') {
        return '<li class="admin-pagination__item"><span class="admin-pagination__ellipsis">…</span></li>';
      }
      return `<li class="admin-pagination__item">
              <button class="admin-pagination__link${p === currentPage ? ' admin-pagination__link--active' : ''}" data-page="${p}"${p === currentPage ? ' aria-current="page"' : ''}>${p}</button>
             </li>`;
    }),
    '</ul>',
  ].join('');

  // Store state for click handler
  container.dataset.currentPage = String(currentPage);
  container.dataset.totalPages = String(totalPages);
}

/**
 * Handle a click on a paginated container and determine the target page.
 * Returns the page number to navigate to, or 0 if no navigation needed.
 *
 * @param {HTMLElement} container  The pagination wrapper
 * @param {Event}       e          Click event
 * @returns {number} Page to navigate to, or 0
 */
export function paginationClickHandler(container, e) {
  const btn = e.target.closest('[data-page]');
  if (!btn || btn.disabled) return 0;

  const currentPage = parseInt(container.dataset.currentPage || '1', 10);
  const totalPages = parseInt(container.dataset.totalPages || '1', 10);

  let page = parseInt(btn.dataset.page, 10);
  if (btn.dataset.page === 'prev') page = currentPage - 1;
  if (btn.dataset.page === 'next') page = currentPage + 1;

  if (page < 1 || page > totalPages || page === currentPage) return 0;
  return page;
}

/**
 * Update the URL query parameter for page (for history/bookmark support).
 * @param {number} page
 */
export function setPageParam(page) {
  const url = new URL(window.location);
  url.searchParams.set('page', String(page));
  window.history.pushState({ page }, '', url);
}

/**
 * Fade out old rows and replace with new HTML.
 * @param {HTMLTableSectionElement} tbody
 * @param {string} html  Full HTML string of rows (no wrapping table)
 */
export function swapTableRows(tbody, html) {
  // Fade out existing rows
  const oldRows = [...tbody.querySelectorAll('tr')];
  oldRows.forEach(row => {
    if (row.classList.contains('admin-table__empty')) {
      row.remove();
      return;
    }
    row.style.animation = 'admin-fade-out 150ms ease forwards';
  });

  // After fade, replace content
  setTimeout(() => {
    tbody.innerHTML = html.trim();
    // Fade in new rows
    requestAnimationFrame(() => {
      tbody.querySelectorAll('tr').forEach((row, i) => {
        row.style.animation = `admin-row-in 200ms ease`;
        row.style.animationDelay = `${i * 40}ms`;
      });
    });
  }, 160);
}

// ======================================================================
//  Phase 3 — Keyboard Shortcuts
// ======================================================================

/**
 * Initialize global keyboard shortcuts for admin pages.
 *
 * @param {object} handlers
 * @param {() => void}  [handlers.escape]   Close modal/drawer
 * @param {() => void}  [handlers.submit]   Submit active form (Ctrl/Cmd+Enter)
 * @param {() => void}  [handlers.create]   Open create drawer (Ctrl/Cmd+N)
 * @param {() => void}  [handlers.help]     Show shortcuts help (?)
 */
export function initKeyboardShortcuts(handlers) {
  document.addEventListener('keydown', (e) => {
    // Block shortcuts when typing in inputs (except Escape)
    const tag = document.activeElement?.tagName;
    const isInput = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
    if (isInput && e.key !== 'Escape') return;

    const mod = e.ctrlKey || e.metaKey;

    if (e.key === 'Escape' && handlers.escape) {
      handlers.escape();
      e.preventDefault();
      return;
    }

    if (mod && (e.key === 'Enter' || e.key === 'NumpadEnter') && handlers.submit) {
      handlers.submit();
      e.preventDefault();
      return;
    }

    if (mod && (e.key === 'n' || e.key === 'N') && handlers.create) {
      handlers.create();
      e.preventDefault();
      return;
    }

    if (e.key === '?' && !e.shiftKey && handlers.help && !isInput) {
      handlers.help();
      e.preventDefault();
    }
  });
}

// ======================================================================
//  Mobile Navigation Toggle
// ======================================================================

/**
 * Initialize the mobile admin navigation toggle.
 * Toggles .admin-nav--open on the surrounding nav, keeping
 * aria-expanded and the Spanish aria-label in sync. Closes on
 * link click, Escape, outside click, and when the viewport
 * crosses above the mobile breakpoint (639px).
 *
 * Idempotent — safe to call multiple times on the same page.
 */
export function initNavToggle() {
  const nav = document.querySelector('.admin-nav');
  const toggleBtn = nav?.querySelector('[data-nav-toggle]');
  if (!nav || !toggleBtn || nav.dataset.navToggleBound === 'true') return;

  nav.dataset.navToggleBound = 'true';

  const isOpen = () => nav.classList.contains('admin-nav--open');

  const setOpen = (open) => {
    nav.classList.toggle('admin-nav--open', open);
    toggleBtn.setAttribute('aria-expanded', String(open));
    toggleBtn.setAttribute(
      'aria-label',
      open ? 'Cerrar menú de secciones' : 'Abrir menú de secciones'
    );
  };

  // Toggle button click
  toggleBtn.addEventListener('click', () => setOpen(!isOpen()));

  // Close after navigating via a link inside the menu
  nav.addEventListener('click', (e) => {
    if (isOpen() && e.target.closest('a[href]')) setOpen(false);
  });

  // Close on Escape and return focus to the toggle button
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isOpen()) {
      setOpen(false);
      toggleBtn.focus();
    }
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (isOpen() && !nav.contains(e.target)) setOpen(false);
  });

  // Close when viewport leaves the mobile breakpoint
  const mq = window.matchMedia('(min-width: 640px)');
  const onBreakpointChange = (evt) => {
    if (evt.matches && isOpen()) setOpen(false);
  };
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', onBreakpointChange);
  } else if (typeof mq.addListener === 'function') {
    mq.addListener(onBreakpointChange); // Legacy Safari fallback
  }
}

// Automatically bind the mobile nav toggle on every page that renders
// the admin nav partial (all such pages import this module).
if (typeof document !== 'undefined') {
  initNavToggle();
}

// Automatically intercept [data-logout-form] submissions
if (typeof document !== 'undefined') {
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-logout-form]');
    if (!form) return;
    e.preventDefault();
    try {
      await api('POST', '/api/auth/logout');
    } catch (_) {
      // ignore
    }
    window.location.href = '/pitocuixa/login';
  });
}

