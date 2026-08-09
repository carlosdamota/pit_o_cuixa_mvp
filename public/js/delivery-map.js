/**
 * Pit o Cuixa — Delivery Area Map & Stylized Chicken Layer
 *
 * Initializes Leaflet.js map with OpenStreetMap tiles, custom town markers,
 * and a vector polygon outlining the delivery area in a stylized chicken shape.
 */

document.addEventListener('DOMContentLoaded', () => {
  const mapEl = document.getElementById('delivery-map');
  if (!mapEl) return;

  // Dynamically load Leaflet if not present
  if (typeof L === 'undefined') {
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);

    const js = document.createElement('script');
    js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    js.onload = () => initDeliveryMap(mapEl);
    document.head.appendChild(js);
  } else {
    initDeliveryMap(mapEl);
  }
});

function initDeliveryMap(container) {
  // Center of delivery zone (Torredembarra / Altafulla / Creixell area)
  const centerLat = 41.1515;
  const centerLng = 1.3930;

  // Popup link label comes from PHP via data-popup-link-label (JS has no i18n layer)
  const popupLabel = container.dataset.popupLinkLabel || 'View on Google Maps';

  const map = L.map(container, {
    center: [centerLat, centerLng],
    zoom: 12.8,
    zoomControl: true,
    scrollWheelZoom: false
  });

  // Clean, high-performance OpenStreetMap / CartoDB tile layer
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
    maxZoom: 18,
    subdomains: 'abcd'
  }).addTo(map);

  // Locations coordinates
  const towns = [
    { name: 'Torredembarra (Sede)', coords: [41.1444, 1.4056], hub: true },
    { name: 'Altafulla', coords: [41.1425, 1.3769], hub: false },
    { name: 'La Móra', coords: [41.1305, 1.3486], hub: false },
    { name: 'La Riera de Gaià', coords: [41.1642, 1.3619], hub: false },
    { name: 'Pobla de Montornès', coords: [41.1778, 1.4144], hub: false },
    { name: 'Creixell', coords: [41.1681, 1.4419], hub: false }
  ];

  // Custom Icon for Hub (Torredembarra) and Towns
  const hubIcon = L.divIcon({
    className: 'custom-hub-icon',
    html: `<div class="pit-map-marker" title="Pit o Cuixa - Torredembarra">🍗</div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 18]
  });

  const townIcon = L.divIcon({
    className: 'custom-town-icon',
    html: `<div style="background:#f7e721; border:2px solid #d32f2f; border-radius:50%; width:16px; height:16px; box-shadow:0 2px 4px rgba(0,0,0,0.2);"></div>`,
    iconSize: [16, 16],
    iconAnchor: [8, 8]
  });

  // Add markers
  towns.forEach(t => {
    const marker = L.marker(t.coords, {
      icon: t.hub ? hubIcon : townIcon
    }).addTo(map);

    const popupContent =
      `<strong>${t.name}</strong><br>${t.hub ? '📍 Local principal & Rosticería' : '🛵 Zona con servicio a domicilio'}` +
      (t.hub ? `<br><a href="https://www.google.com/maps/dir/?api=1&destination=41.1413,1.3894" target="_blank" rel="noopener">${popupLabel}</a>` : '');

    marker.bindPopup(popupContent);
  });

  // Perimeter polygon points defining the chicken outline around all 6 towns
  // Clockwise order starting from Beak/Head (Pobla / Creixell), Tail (Riera), Belly (Móra), Base (Torredembarra coast)
  const chickenPolygonCoords = [
    [41.1850, 1.4250], // Crest / Head top
    [41.1790, 1.4550], // Beak tip (pointing East)
    [41.1710, 1.4480], // Wattle
    [41.1620, 1.4520], // Chest
    [41.1400, 1.4250], // Front body
    [41.1250, 1.3980], // Bottom right leg/tail
    [41.1210, 1.3400], // Belly / Base coast (La Móra)
    [41.1380, 1.3320], // Tail feathers lower (West)
    [41.1550, 1.3450], // Tail feathers upper
    [41.1720, 1.3520], // Back neck (La Riera / Pobla)
    [41.1830, 1.3900]  // Head back
  ];

  // Draw the Chicken Shaped Delivery Polygon Overlay
  const chickenPoly = L.polygon(chickenPolygonCoords, {
    className: 'chicken-poly-path'
  }).addTo(map);

  chickenPoly.bindTooltip('🐓 Zona de Reparto Pit o Cuixa', {
    sticky: true,
    direction: 'top'
  });

  // Fit bounds to polygon with padding
  map.fitBounds(chickenPoly.getBounds(), { padding: [20, 20] });
}
