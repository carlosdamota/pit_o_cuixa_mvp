# Apply Report: Fix PWA Installation on Mobile

## Status: ✅ COMPLETE

## Changes Made

### 1. Generated 4 PNG icons (PowerShell System.Drawing)

| File | Size | Dimensions |
|---|---|---|
| `public/img/icons/icon-192.png` | 1.8KB | 192×192 |
| `public/img/icons/icon-512.png` | 5.3KB | 512×512 |
| `public/img/icons/icon-maskable.png` | 5.3KB | 512×512 |
| `public/img/apple-touch-icon.png` | 1.7KB | 180×180 |

Design matches existing SVGs: yellow `#f7e721` background with rounded corners, dark `#1a1c1e` "P" letter centered, red `#d32f2f` dot upper-right.

### 2. Updated `public/manifest.json`

- Added `id: "pitocuixa-pwa"` for PWA identity stability
- Added 3 PNG icon entries (192, 512, maskable) with `purpose` fields
- Preserved existing 3 SVG icon entries for forward compatibility
- PNG entries listed first so browsers pick them preferentially

### 3. Updated `src/frontend/templates/layouts/default.php`

- Changed `<link rel="apple-touch-icon">` from `.svg` to `.png`

## Verification

- [x] Manifest is valid JSON
- [x] All PNG files exist with correct sizes
- [x] No build dependencies introduced
- [x] No changes to SW logic, routing, SEO, or i18n
- [ ] **TODO**: Test on actual mobile device (Chrome Android)
- [ ] **TODO**: Verify `beforeinstallprompt` fires
- [ ] **TODO**: Verify iOS home screen icon

## Files Changed

- `public/manifest.json` — edited
- `public/img/icons/icon-192.png` — created
- `public/img/icons/icon-512.png` — created
- `public/img/icons/icon-maskable.png` — created
- `public/img/apple-touch-icon.png` — created
- `src/frontend/templates/layouts/default.php` — edited
