# Spec: Fix PWA Installation on Mobile — PNG Icons

## Requirement 1: Manifest MUST include PNG icons

The web app manifest SHALL declare at least two PNG icon entries: one 192×192 and one 512×512.

### Scenario 1.1: Chrome Android fires beforeinstallprompt

- **Given** the user visits the site on Chrome for Android
- **When** the page loads and the manifest is parsed
- **Then** the manifest MUST contain at least one icon with `type: "image/png"` and `sizes: "192x192"`
- **And** the manifest MUST contain at least one icon with `type: "image/png"` and `sizes: "512x512"`
- **And** the browser SHALL be able to fire the `beforeinstallprompt` event

### Scenario 1.2: PWA install button triggers native dialog

- **Given** the user is on the home page on Chrome for Android
- **When** the user taps the PWA install button
- **Then** the native install dialog SHALL appear (via `beforeinstallprompt.prompt()`)

---

## Requirement 2: Maskable icon MUST be PNG

The maskable icon entry in the manifest SHALL use PNG format for maximum compatibility.

### Scenario 2.1: Maskable icon is PNG

- **Given** the manifest is parsed by any browser
- **When** the maskable icon entry is evaluated
- **Then** the icon with `purpose: "maskable"` MUST have `type: "image/png"`

---

## Requirement 3: Apple touch icon MUST be PNG

The `apple-touch-icon` link element SHALL reference a PNG file for iOS Safari compatibility.

### Scenario 3.1: iOS Safari uses apple-touch-icon

- **Given** the user visits the site on iOS Safari
- **When** the user taps "Add to Home Screen"
- **Then** the `apple-touch-icon` MUST be a PNG file (180×180)
- **And** iOS SHALL display the PNG icon on the home screen

---

## Requirement 4: SVG icons SHOULD be preserved

Existing SVG icon entries in the manifest SHOULD be retained for forward compatibility with browsers that support SVG manifest icons.

### Scenario 4.1: SVG icons coexist with PNG

- **Given** the manifest contains both SVG and PNG icon entries
- **When** a browser that supports SVG manifest icons parses it
- **Then** the SVG entries SHALL still be present and valid
- **And** the browser MAY prefer the SVG entries over PNG

---

## Requirement 5: Manifest SHOULD include an `id` field

The manifest SHOULD include a stable `id` field to prevent PWA identity changes on manifest updates.

### Scenario 5.1: Manifest has stable id

- **Given** the manifest is updated (e.g., icon versions change)
- **When** the browser re-parses the manifest
- **Then** the `id` field SHALL remain constant
- **And** the PWA SHALL be treated as the same installed application

---

## Non-Functional Requirements

- **NFR-1**: PNG icons MUST match the visual design of the existing SVG icons (yellow `#f7e721` background, dark `#1a1c1e` "P" letter, red `#d32f2f` dot)
- **NFR-2**: PNG generation MUST NOT introduce any build-time dependencies (no Node.js, no Composer) — PNGs are committed as static assets
- **NFR-3**: The change MUST NOT affect any existing functionality (Service Worker, routing, SEO, i18n)
