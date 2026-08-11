/**
 * Pit o Cuixa — Animated Tab Favicon
 *
 * Animates the brand chicken logo (favicon.png) in the browser tab.
 * Mimics the fun waddling/hopping animation from the homepage onboarding hero.
 *
 * Features:
 * - High-DPI 64x64 Canvas rendering for crisp tab icons
 * - Smooth waddling tilt, vertical hop, dynamic ground shadow, squash & stretch
 * - Tab visibility detection (pauses when hidden to save CPU/battery)
 * - Accessible: respects prefers-reduced-motion
 *
 * @module animated-favicon
 */

/**
 * Initialise the animated favicon on the browser tab.
 * @param {Object} [options]
 * @param {string} [options.iconPath='/img/icons/favicon.png'] - Path to favicon image
 * @param {number} [options.fps=15] - Target frames per second
 */
export function initAnimatedFavicon(options = {}) {
  // Respect reduced motion preferences
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const iconPath = options.iconPath || '/img/icons/favicon.png';
  const targetFps = options.fps || 15;
  const frameInterval = 1000 / targetFps;

  // Find or create favicon <link> element
  let linkEl = document.querySelector('link[rel~="icon"]');
  if (!linkEl) {
    linkEl = document.createElement('link');
    linkEl.rel = 'icon';
    linkEl.type = 'image/png';
    document.head.appendChild(linkEl);
  }
  linkEl.id = 'dynamic-favicon';

  // Load chicken image
  const img = new Image();
  img.crossOrigin = 'anonymous';

  img.onload = () => {
    startFaviconAnimation(img, linkEl, frameInterval);
  };

  // Fallback path attempt if initial load fails
  img.onerror = () => {
    if (!iconPath.startsWith('/public') && iconPath.startsWith('/img')) {
      img.src = '/public' + iconPath;
    }
  };

  img.src = iconPath;
}

/**
 * Run the animation loop on canvas and update the favicon link.
 *
 * @param {HTMLImageElement} img
 * @param {HTMLLinkElement} linkEl
 * @param {number} frameInterval
 */
function startFaviconAnimation(img, linkEl, frameInterval) {
  const CANVAS_SIZE = 64; // 64x64 for HiDPI/Retina crispness
  const canvas = document.createElement('canvas');
  canvas.width = CANVAS_SIZE;
  canvas.height = CANVAS_SIZE;
  const ctx = canvas.getContext('2d');

  if (!ctx) {
    return;
  }

  let animationFrameId = null;
  let lastFrameTime = 0;
  let time = 0;
  let isTabActive = !document.hidden;

  /**
   * Render a single frame of the waddling chicken.
   * @param {number} t - Elapsed animation time factor
   */
  function renderFrame(t) {
    ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);

    // Motion parameters (synchronized rhythm)
    const bounceSpeed = 9.0;
    const bounceHeight = Math.abs(Math.sin(t * bounceSpeed)) * 5.5; // 0 to 5.5px hop
    const tiltAngle = Math.sin(t * bounceSpeed) * 0.16; // ~9 degrees waddle

    // Squash & Stretch on landing
    const isLanding = bounceHeight < 1.0;
    const scaleX = isLanding ? 1.06 : 0.96;
    const scaleY = isLanding ? 0.94 : 1.04;

    // Ground Shadow properties (shrinks when chicken hops up)
    const shadowAlpha = 0.25 - (bounceHeight / 5.5) * 0.14;
    const shadowWidth = 18 - (bounceHeight / 5.5) * 4;
    const shadowHeight = 4.5;
    const shadowY = 56;

    // 1. Draw Ground Shadow
    ctx.save();
    ctx.beginPath();
    ctx.ellipse(CANVAS_SIZE / 2, shadowY, shadowWidth, shadowHeight, 0, 0, Math.PI * 2);
    ctx.fillStyle = `rgba(0, 0, 0, ${Math.max(0.05, shadowAlpha)})`;
    ctx.fill();
    ctx.restore();

    // 2. Draw Waddling Chicken Logo
    ctx.save();

    // Move origin to chicken baseline pivot
    const pivotX = CANVAS_SIZE / 2;
    const pivotY = shadowY - 4 - bounceHeight;

    ctx.translate(pivotX, pivotY);
    ctx.rotate(tiltAngle);
    ctx.scale(scaleX, scaleY);

    // Draw chicken centered over pivot
    const drawWidth = 46;
    const drawHeight = 46;
    ctx.drawImage(
      img,
      -drawWidth / 2,
      -drawHeight,
      drawWidth,
      drawHeight
    );

    ctx.restore();

    // Update <link rel="icon"> with canvas PNG data
    linkEl.href = canvas.toDataURL('image/png');
  }

  /**
   * Main animation loop driven by target FPS.
   * @param {number} timestamp
   */
  function loop(timestamp) {
    if (!lastFrameTime) {
      lastFrameTime = timestamp;
    }

    const delta = timestamp - lastFrameTime;

    if (delta >= frameInterval) {
      lastFrameTime = timestamp - (delta % frameInterval);

      if (isTabActive) {
        time += 0.045; // Step forward animation time
        renderFrame(time);
      }
    }

    animationFrameId = requestAnimationFrame(loop);
  }

  // Handle Tab Visibility (Pause/Resume to optimize CPU)
  document.addEventListener('visibilitychange', () => {
    isTabActive = !document.hidden;
    if (isTabActive) {
      lastFrameTime = performance.now();
      // Joyful double hop when returning to tab
      renderFrame(time);
    }
  });

  // Start loop
  animationFrameId = requestAnimationFrame(loop);
}
