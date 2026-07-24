# Changelog

## [Unreleased]

### Changed

- **i18n: trilingual support (CA, ES, EN)** — Replaced binary ES/EN locale toggle with a 3-locale dropdown (Català, Castellano, English). The default locale is now Catalan (`ca`) instead of the mislabeled `es` that was serving Catalan strings. Full refactor details in `openspec/changes/refactor-de-i18n-para-soportar-catalan-espanol-e-ingles/`.

### Breaking Changes

- **`?lang=es` now serves REAL Spanish, not Catalan.** Previously, `?lang=es` incorrectly served Catalan strings. Now it serves proper Spanish translations. Stale `lang=es` cookies from before this change will load Spanish on the first visit (the cookie is refreshed on the next `?lang=` interaction). Users who want Catalan should either clear their `lang` cookie or visit `/?lang=ca`.
- **Default locale changed from `es` (Catalan) to `ca` (Catalan).** The content is identical — only the locale code changed. This affects `hreflang` annotations, `og:locale` meta tags, and the sitemap `x-default` target.
