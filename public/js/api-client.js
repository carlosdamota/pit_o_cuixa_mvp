/**
 * Pit o Cuixa — API Client
 *
 * Lightweight fetch wrapper for JSON API endpoints.
 * Automatically sets locale header and handles errors.
 *
 * @module api-client
 */

const API_BASE = '/api';

/**
 * Get the current locale from the <html> lang attribute
 * or from the ?lang= query parameter.
 *
 * @returns {string}  Current locale code ('es' | 'en')
 */
function getLocale() {
  const htmlLang = document.documentElement.getAttribute('lang');
  if (htmlLang === 'es' || htmlLang === 'en') {
    return htmlLang;
  }

  const params = new URLSearchParams(window.location.search);
  const lang = params.get('lang');
  if (lang === 'es' || lang === 'en') {
    return lang;
  }

  return 'es';
}

/**
 * Parse a Response object to JSON.
 * Throws on HTTP errors and non-JSON responses.
 *
 * @param   {Response}  response  Fetch Response object
 * @returns {Promise<any>}
 */
async function parseJSON(response) {
  const contentType = response.headers.get('content-type') || '';

  if (!contentType.includes('application/json')) {
    throw new Error(`Unexpected content type: ${contentType}`);
  }

  const body = await response.json();

  if (!response.ok) {
    const message = body?.message || `HTTP ${response.status}`;
    const err = new Error(message);
    err.status = response.status;
    err.body = body;
    throw err;
  }

  return body;
}

/**
 * Generic fetch wrapper for API endpoints.
 *
 * @param   {string}         path     API path (e.g. '/products')
 * @param   {RequestInit}    options  Optional fetch options
 * @returns {Promise<any>}            Parsed JSON response
 */
export async function apiFetch(path, options = {}) {
  const url = `${API_BASE}${path}`;

  const headers = {
    Accept: 'application/json',
    'X-Locale': getLocale(),
    ...options.headers,
  };

  const response = await fetch(url, {
    ...options,
    headers,
  });

  return parseJSON(response);
}

/**
 * Fetch all products, optionally filtered by category.
 *
 * @param   {number|null}  categoryId
 * @returns {Promise<{data: Array, error: boolean}>}
 */
export async function getProducts(categoryId = null) {
  const query = categoryId !== null ? `?id_category=${categoryId}` : '';
  return apiFetch(`/products${query}`);
}

/**
 * Fetch a single product by slug.
 *
 * @param   {string}  slug
 * @returns {Promise<{data: object|null, error: boolean}>}
 */
export async function getProduct(slug) {
  return apiFetch(`/products/${encodeURIComponent(slug)}`);
}

/**
 * Fetch all categories.
 *
 * @returns {Promise<{data: Array, error: boolean}>}
 */
export async function getCategories() {
  return apiFetch('/categories');
}

/**
 * Fetch the full menu (products grouped by category).
 *
 * @returns {Promise<{data: Array, error: boolean}>}
 */
export async function getMenu() {
  return apiFetch('/menu');
}
