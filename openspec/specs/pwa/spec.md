# PWA Specification

## Purpose

Progressive Web App capabilities: web app manifest for installability, service worker for offline support and caching, and PWA metadata for "Add to Home Screen" prompts. Ensures the site works as a native-like experience on mobile devices.

## Requirements

### PW-001: Web App Manifest

The system MUST serve a valid `manifest.json` at the root path, declaring app metadata for installability.

#### Scenario: Manifest is served correctly

- GIVEN the site is deployed
- WHEN a browser requests `/manifest.json`
- THEN the response SHALL have `Content-Type: application/manifest+json`
- AND the JSON SHALL include: `name`, `short_name`, `start_url`, `display`, `background_color`, `theme_color`, `icons`

#### Scenario: Manifest declares correct identity

- GIVEN the manifest content
- THEN `name` SHALL be "Pit o Cuixa"
- AND `short_name` SHALL be "Pit o Cuixa"
- AND `start_url` SHALL be `/`
- AND `display` SHALL be `standalone`
- AND `theme_color` SHALL be `#f7e721` (primary yellow)
- AND `background_color` SHALL be `#f7f9ff` (surface)

#### Scenario: Icons are declared

- GIVEN the manifest includes icon entries
- THEN icons SHALL be provided at minimum: 192x192 and 512x512 PNG
- AND a `maskable` purpose icon SHALL be included for adaptive icon support

### PW-002: Service Worker Registration

The system MUST register a service worker on page load to enable offline caching.

#### Scenario: Service worker registers

- GIVEN a browser that supports service workers
- WHEN any page loads over HTTPS
- THEN the service worker SHALL be registered at `/sw.js`
- AND the registration SHALL succeed without errors

#### Scenario: Service worker activates

- GIVEN the service worker is registered
- WHEN the page is loaded a second time
- THEN the service worker SHALL be in `activated` state
- AND SHALL control the page

### PW-003: Offline Support

The system MUST cache essential assets (HTML, CSS, JS, fonts, key images) so the site remains viewable without network connectivity.

#### Scenario: Cached page works offline

- GIVEN the visitor has loaded the home page at least once online
- WHEN they lose network connectivity and navigate to `/`
- THEN the cached home page SHALL render from the service worker cache

#### Scenario: Offline fallback

- GIVEN the visitor is offline and navigates to a page they have NOT previously visited
- WHEN the service worker cannot fetch the page
- THEN a minimal offline fallback page SHALL be displayed
- AND the fallback SHALL indicate the site is temporarily unavailable

#### Scenario: Cache strategy for assets

- GIVEN the service worker intercepts a request for a static asset (CSS, JS, font)
- WHEN the asset is in the cache
- THEN the cached version SHALL be served immediately (cache-first strategy)

#### Scenario: Cache strategy for API responses

- GIVEN the service worker intercepts an API request (`/api/*`)
- WHEN the network is available
- THEN the request SHALL be fetched from the network (network-first strategy)
- AND the response SHALL be cached for subsequent offline use

### PW-004: Installability

The system MUST meet all criteria for the browser's "Add to Home Screen" / install prompt.

#### Scenario: Install prompt appears

- GIVEN the site is served over HTTPS
- AND the manifest is valid
- AND the service worker is registered and active
- WHEN the browser determines installability criteria are met
- THEN the browser SHALL show an install prompt (native or `beforeinstallprompt`)

#### Scenario: Installed app launches correctly

- GIVEN the PWA is installed on the device
- WHEN the user opens it from the home screen
- THEN it SHALL launch in standalone mode (no browser chrome)
- AND start at the `start_url` defined in the manifest

### PW-005: HTTPS Requirement

The system MUST function correctly when served over HTTPS. Service workers SHALL NOT register on HTTP (except localhost).

#### Scenario: HTTPS deployment

- GIVEN the site is deployed at `https://pitocuixa.es`
- WHEN the service worker attempts to register
- THEN registration SHALL succeed

## Contracts

### manifest.json

```json
{
  "name": "Pit o Cuixa",
  "short_name": "Pit o Cuixa",
  "description": "Pollería y rostería en Torredembarra",
  "start_url": "/",
  "display": "standalone",
  "orientation": "portrait",
  "theme_color": "#f7e721",
  "background_color": "#f7f9ff",
  "lang": "es",
  "icons": [
    { "src": "/img/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/img/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/img/icon-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```

### Service Worker Cache Strategy

| Resource Type | Strategy | Cache Name |
|---------------|----------|------------|
| HTML pages | Network-first, fallback to cache | `pages-v1` |
| CSS / JS / Fonts | Cache-first | `static-v1` |
| Images | Cache-first, with max entries | `images-v1` |
| API responses | Network-first, fallback to cache | `api-v1` |

## Constraints

- HTTPS required for service worker (except localhost dev)
- No external PWA libraries — vanilla service worker JS
- Cache versioning via cache name suffix (`-v1`) for busting on deploy
- Total cache budget SHOULD stay under 50MB (image-heavy catalog)
- Service worker `skipWaiting()` on update to activate new version immediately

## Acceptance Criteria

- [ ] `manifest.json` is valid and passes Lighthouse PWA audit
- [ ] Icons at 192x192 and 512x512 exist and are referenced
- [ ] Service worker registers on first page load
- [ ] Home page renders offline after initial visit
- [ ] Static assets (CSS, JS, fonts) load from cache when offline
- [ ] API responses cache for offline fallback
- [ ] Install prompt appears on supported browsers
- [ ] Installed app launches in standalone mode
- [ ] Lighthouse PWA score ≥ 90
