# Proposal: Fix PWA Installation on Mobile (PNG Icons)

## Problem Statement

The PWA install button on the home page does not trigger installation on mobile devices (Android Chrome, Samsung Internet). The `beforeinstallprompt` event never fires because the web app manifest declares only SVG icons, which these browsers do not accept for PWA installation.

## Business Impact

- Users cannot install the PWA on their phones → no offline access, no home screen icon
- Lost engagement: installed PWAs have significantly higher return rates
- The install button is visible but non-functional → broken user expectation

## Root Cause

`public/manifest.json` declares 3 icons, all with `type: "image/svg+xml"`:
- `/img/icons/icon-192.svg` (192×192)
- `/img/icons/icon-512.svg` (512×512)
- `/img/icons/icon-maskable.svg` (512×512, purpose: maskable)

Chrome for Android requires at least one PNG icon (192×192 and 512×512) for the install prompt to appear. SVG icons in manifests are not supported for this purpose on most Android browsers.

Additionally, `apple-touch-icon` is SVG (`/img/apple-touch-icon.svg`), which is unreliable on iOS Safari.

## Proposed Solution

1. **Generate PNG icons** from the existing SVG designs:
   - `icon-192.png` (192×192)
   - `icon-512.png` (512×512)
   - `icon-maskable.png` (512×512, with safe zone for maskable cropping)
   - `apple-touch-icon.png` (180×180)
2. **Update `manifest.json`** to include PNG entries alongside existing SVG entries (SVG kept for future browser support)
3. **Update `default.php`** to reference the PNG `apple-touch-icon`
4. **Optionally add `id` field** to manifest for PWA identity stability across manifest updates

## Scope

- `public/manifest.json` — add PNG icon entries
- `public/img/icons/` — add 4 new PNG files
- `public/img/apple-touch-icon.png` — new file
- `src/frontend/templates/layouts/default.php` — update apple-touch-icon href

## Out of Scope

- Changes to the Service Worker logic
- Changes to the install button UI/UX
- Changes to the SVG source files (they remain as-is)
- iOS-specific install flow changes (the manual instructions fallback is already implemented)

## Risks

- **Low risk**: Adding PNG icons is additive; no existing functionality breaks
- **PNG generation**: Need a tool to convert SVG→PNG. Options: build script with sharp/canvas, manual export from design tool, or use an online converter for these simple shapes
- **Visual fidelity**: The SVGs are simple (yellow rect + "P" + red dot), so PNG conversion should be trivial

## Rollback Plan

If PNG icons cause issues, revert `manifest.json` to the previous state (SVG-only) and remove the PNG files. No database or backend changes involved.

## Dependencies

- None — this is a frontend-only change
- No Composer/Node dependencies needed (PNGs are static assets)

## Estimated Effort

- Small: ~1-2 hours including PNG generation, manifest update, and testing
