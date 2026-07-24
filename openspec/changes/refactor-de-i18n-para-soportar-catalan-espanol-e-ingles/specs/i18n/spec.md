# i18n Specification

## Purpose

Locale detection, translation loading, fallback chain, and locale switcher UI for 3 locales (CA, ES, EN). Default locale is Catalan (CA) — local-first for Torredembarra.

## Requirements

### I-001: Supported Locales

The system SHALL support exactly 3 locales: `ca` (Catalan), `es` (Spanish), `en` (English). The default locale SHALL be `ca`.

#### Scenario: Default locale resolution

- GIVEN no `?lang=` parameter and no locale cookie
- WHEN the system resolves the locale
- THEN the active locale SHALL be `ca`

#### Scenario: Invalid locale fallback

- GIVEN `?lang=fr` (unsupported locale)
- WHEN the system resolves the locale
- THEN the active locale SHALL fall back to `ca`

### I-002: Locale Detection

The system SHALL resolve the active locale from (in priority order): query parameter `?lang=`, cookie `lang`, then default `ca`.

#### Scenario: Query parameter takes priority

- GIVEN a cookie `lang=en` and URL `?lang=es`
- WHEN the system resolves the locale
- THEN the active locale SHALL be `es`

#### Scenario: Cookie used when no query parameter

- GIVEN cookie `lang=es` and no `?lang=` parameter
- WHEN the system resolves the locale
- THEN the active locale SHALL be `es`

### I-003: Translation Loading

The system SHALL load translations from `src/shared/i18n/{locale}.php`. Each locale file MUST contain all required translation keys.

#### Scenario: Catalan translations loaded

- GIVEN active locale is `ca`
- WHEN translations are loaded
- THEN strings SHALL come from `src/shared/i18n/ca.php`

#### Scenario: Spanish translations loaded

- GIVEN active locale is `es`
- WHEN translations are loaded
- THEN strings SHALL come from `src/shared/i18n/es.php`

### I-004: Fallback Chain

When a translation key is missing in the requested locale, the system SHALL fall back: requested → `ca` → `en`.

#### Scenario: Missing Spanish key falls back to Catalan

- GIVEN active locale is `es` and key `X` is missing from `es.php`
- WHEN key `X` is requested
- THEN the Catalan value from `ca.php` SHALL be returned

#### Scenario: Missing Catalan key falls back to English

- GIVEN active locale is `ca` and key `X` is missing from `ca.php`
- WHEN key `X` is requested
- THEN the English value from `en.php` SHALL be returned

### I-005: Locale Switcher UI

The navigation SHALL provide a `<select>` dropdown with 3 options: Català, Castellano, English. The switcher MUST NOT require JavaScript.

#### Scenario: Dropdown shows 3 locales

- GIVEN any public page
- WHEN the navigation is rendered
- THEN a `<select>` element SHALL contain options for `ca`, `es`, `en`

#### Scenario: Switching locale

- GIVEN the user selects "Castellano" from the dropdown
- WHEN the form is submitted
- THEN the page SHALL reload with `?lang=es` and display Spanish strings

### I-006: Phone Number Consistency

All locale files and JSON-LD structured data SHALL use the standardized phone number `+34 977 64 20 10`.

#### Scenario: Phone number in all locales

- GIVEN any active locale (`ca`, `es`, or `en`)
- WHEN the phone number is displayed or embedded in JSON-LD
- THEN it SHALL be `+34 977 64 20 10`
