# Delta for i18n

## MODIFIED Requirements

### Requirement: I-001: Supported Locales

The system SHALL support exactly 3 locales: `ca` (Catalan), `es` (Spanish), `en` (English). The default locale SHALL be `ca`.

The `.env` and `.env.example` files MUST set `DEFAULT_LOCALE=ca`. The configuration comment SHALL list all supported locales: `ca|es|en`.

#### Scenario: Default locale resolution

- GIVEN no `?lang=` parameter and no locale cookie
- WHEN the system resolves the locale
- THEN the active locale SHALL be `ca`

#### Scenario: Invalid locale fallback

- GIVEN `?lang=fr` (unsupported locale)
- WHEN the system resolves the locale
- THEN the active locale SHALL fall back to `ca`

#### Scenario: Environment configuration enforces Catalan default

- GIVEN a fresh installation with `.env` configured
- WHEN `DEFAULT_LOCALE` is read by `Config::defaultLocale()`
- THEN the value SHALL be `ca`
- AND the `.env` comment SHALL document `ca|es|en` as valid options

(Previously: `.env` set `DEFAULT_LOCALE=es` with comment excluding `ca`, violating spec I-001)

## ADDED Requirements

### Requirement: I-007: JavaScript Client Locale Support

The JavaScript API client (`public/js/api-client.js`) SHALL include `ca` in the locale whitelist for the `X-Locale` HTTP header. The `getLocale()` function MUST accept `ca`, `es`, and `en` as valid values.

When the `<html lang>` attribute is set, the client SHALL use it as a fallback locale if no explicit locale is provided.

#### Scenario: JavaScript sends Catalan locale header

- GIVEN `<html lang="ca">` is set in the document
- WHEN `api-client.js` makes an API request
- THEN the `X-Locale` header SHALL be `ca`

#### Scenario: JavaScript locale whitelist validation

- GIVEN `getLocale()` is called with value `ca`
- WHEN the function validates against the whitelist
- THEN `ca` SHALL be accepted and returned

#### Scenario: HTML lang attribute fallback

- GIVEN no explicit locale in JavaScript context
- WHEN `<html lang="es">` is present
- THEN `getLocale()` SHALL return `es`

(Previously: `getLocale()` whitelist excluded `ca`, blocking Catalan from `X-Locale` header)

### Requirement: I-008: FAQ Route Locale Resolution

The FAQ route handler at `/{lang}/faq` (e.g., `/ca/faq`, `/es/faq`, `/en/faq`) SHALL use the bootstrap locale resolution mechanism instead of inline translation merging.

When a user accesses `/{lang}/faq`, the system SHALL redirect to `/faq?lang={lang}` to ensure the `LANG` constant matches the displayed locale.

#### Scenario: FAQ route redirects to query parameter pattern

- GIVEN user accesses `/ca/faq`
- WHEN the route handler executes
- THEN the system SHALL redirect to `/faq?lang=ca`
- AND the `LANG` constant SHALL be `ca`

#### Scenario: FAQ route preserves locale across all languages

- GIVEN user accesses `/es/faq` or `/en/faq`
- WHEN the route handler executes
- THEN the system SHALL redirect to `/faq?lang=es` or `/faq?lang=en` respectively
- AND translations SHALL load from the correct locale file

#### Scenario: LANG constant consistency on FAQ page

- GIVEN the FAQ page is rendered after redirect
- WHEN template code reads the `LANG` constant
- THEN `LANG` SHALL match the active locale used for translation loading

(Previously: `/{lang}/faq` handler performed inline translation merge, causing `LANG` constant mismatch with displayed locale)
