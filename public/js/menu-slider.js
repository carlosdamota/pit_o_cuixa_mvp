/**
 * Pit o Cuixa — Menu Image Slider
 *
 * Vanilla ESM module: autoplay 5s, Pointer Events swipe (50px threshold),
 * keyboard navigation (ArrowLeft/Right, Home/End), dot indicators,
 * pause on interaction/resume after 5s idle, reduced-motion support.
 *
 * Only initialises if `[data-menu-slider]` exists in the DOM.
 *
 * @module menu-slider
 */

/**
 * Slide direction constants.
 * @readonly
 * @enum {number}
 */
const DIR = {
  NEXT: 1,
  PREV: -1,
};

/**
 * @typedef {object} SliderState
 * @property {HTMLElement} root     — section[data-menu-slider]
 * @property {HTMLElement} track    — .menu-slider__track
 * @property {HTMLElement[]} slides — .menu-slider__slide[]
 * @property {HTMLElement[]} dots   — .menu-slider__dot button[]
 * @property {number} current       — zero-based slide index
 * @property {number} total         — total slide count
 * @property {number|null} timer    — autoplay interval ID
 * @property {number|null} resumeTimer — idle resume timeout ID
 * @property {boolean} reduced      — prefers-reduced-motion active
 * @property {boolean} interacting  — user is currently interacting
 */

/** @type {SliderState|null} */
let state = null;

/**
 * Move the slider to the given slide index.
 * @param {number} index — zero-based target index (clamped [0, total-1])
 */
function goTo(index) {
  if (!state) return;

  const clamped = Math.max(0, Math.min(index, state.total - 1));
  if (clamped === state.current) return;

  state.current = clamped;

  // Translate the track
  const offset = -clamped * 100;
  state.track.style.transform = state.reduced
    ? `translateX(${offset}%)`
    : `translateX(${offset}%)`; // transition is always on via CSS

  // Update dots
  state.dots.forEach((btn, i) => {
    const isActive = i === clamped;
    btn.setAttribute('aria-current', isActive ? 'true' : 'false');
    btn.parentElement?.classList.toggle('menu-slider__dot--active', isActive);
  });

  // Update aria-live region
  const liveRegion = state.root.querySelector('[data-slider-live]');
  if (liveRegion) {
    liveRegion.textContent = `Slide ${clamped + 1} of ${state.total}`;
  }
}

/**
 * Advance or rewind by direction.
 * @param {number} dir — DIR.NEXT (+1) or DIR.PREV (-1)
 */
function slide(dir) {
  if (!state) return;
  const next = state.current + dir;

  // Wrap around
  if (next < 0) {
    goTo(state.total - 1);
  } else if (next >= state.total) {
    goTo(0);
  } else {
    goTo(next);
  }
}

/**
 * Start autoplay interval.
 */
function startAutoplay() {
  if (!state || state.reduced) return;
  stopAutoplay();
  state.timer = setInterval(() => slide(DIR.NEXT), 5000);
}

/**
 * Stop autoplay interval.
 */
function stopAutoplay() {
  if (!state) return;
  if (state.timer !== null) {
    clearInterval(state.timer);
    state.timer = null;
  }
}

/**
 * Pause autoplay and schedule resume after 5s of inactivity.
 */
function pauseAndScheduleResume() {
  if (!state) return;
  stopAutoplay();

  if (state.resumeTimer !== null) {
    clearTimeout(state.resumeTimer);
  }

  state.resumeTimer = setTimeout(() => {
    if (state && !state.interacting) {
      startAutoplay();
    }
    state.resumeTimer = null;
  }, 5000);
}

/**
 * Mark interaction start and pause autoplay.
 */
function onInteractionStart() {
  if (!state) return;
  state.interacting = true;
  pauseAndScheduleResume();
}

/**
 * Mark interaction end (allows resume timer to fire).
 */
function onInteractionEnd() {
  if (!state) return;
  state.interacting = false;
}

// ── Pointer Events (unified touch + mouse) ──────────────────────────

/** @type {{ x: number, y: number }|null} */
let pointerStart = null;

/**
 * Handle pointerdown — record start position.
 * @param {PointerEvent} e
 */
function onPointerDown(e) {
  if (!state) return;

  // Only primary button
  if (e.button !== 0) return;

  pointerStart = { x: e.clientX, y: e.clientY };
  onInteractionStart();

  // Capture pointer for reliable tracking
  state.track.setPointerCapture(e.pointerId);
}

/**
 * Handle pointerup — determine if swipe exceeds threshold.
 * @param {PointerEvent} e
 */
function onPointerUp(e) {
  if (!state || !pointerStart) {
    pointerStart = null;
    return;
  }

  const dx = e.clientX - pointerStart.x;
  const dy = e.clientY - pointerStart.y;
  pointerStart = null;

  // Only horizontal swipes ≥ 50px
  if (Math.abs(dx) >= 50 && Math.abs(dx) > Math.abs(dy)) {
    if (dx < 0) {
      slide(DIR.NEXT);  // Swipe left → next
    } else {
      slide(DIR.PREV);  // Swipe right → previous
    }
  }

  onInteractionEnd();
}

/**
 * Handle pointercancel — reset tracking.
 */
function onPointerCancel() {
  pointerStart = null;
  if (state) onInteractionEnd();
}

// ── Keyboard Navigation ────────────────────────────────────────────

/**
 * Handle keydown on the viewport.
 * @param {KeyboardEvent} e
 */
function onKeyDown(e) {
  if (!state) return;

  let handled = false;

  switch (e.key) {
    case 'ArrowLeft':
      slide(DIR.PREV);
      handled = true;
      break;
    case 'ArrowRight':
      slide(DIR.NEXT);
      handled = true;
      break;
    case 'Home':
      goTo(0);
      handled = true;
      break;
    case 'End':
      goTo(state.total - 1);
      handled = true;
      break;
    default:
      break;
  }

  if (handled) {
    e.preventDefault();
    onInteractionStart();
  }
}

// ── Dot Click ───────────────────────────────────────────────────────

/**
 * Handle dot button click.
 * @param {number} index — zero-based slide index
 */
function onDotClick(index) {
  if (!state) return;
  goTo(index);
  onInteractionStart();
}

// ── Visibility Change ──────────────────────────────────────────────

/**
 * Pause autoplay when tab hidden, resume when visible.
 */
function onVisibilityChange() {
  if (!state) return;

  if (document.hidden) {
    stopAutoplay();
  } else if (!state.reduced) {
    startAutoplay();
  }
}

// ── Initialisation ─────────────────────────────────────────────────

/**
 * Initialise the menu slider.
 * No-op if `[data-menu-slider]` is absent from the DOM.
 */
export function initMenuSlider() {
  const root = document.querySelector('[data-menu-slider]');
  if (!root) return;

  // Guard: already initialised
  if (state) return;

  const track = root.querySelector('.menu-slider__track');
  const slides = root.querySelectorAll('.menu-slider__slide');
  const dotButtons = root.querySelectorAll('[data-slider-dot]');

  if (!track || slides.length === 0) return;

  // Check reduced motion preference
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Create aria-live region for screen readers
  const live = document.createElement('div');
  live.setAttribute('data-slider-live', '');
  live.className = 'visually-hidden';
  live.setAttribute('aria-live', 'polite');
  live.setAttribute('role', 'status');
  root.appendChild(live);

  // Build state
  state = {
    root,
    track,
    slides: Array.from(slides),
    dots: Array.from(dotButtons),
    current: 0,
    total: slides.length,
    timer: null,
    resumeTimer: null,
    reduced,
    interacting: false,
  };

  // Ensure first dot is active
  if (dotButtons.length > 0) {
    dotButtons[0].setAttribute('aria-current', 'true');
    dotButtons[0].parentElement?.classList.add('menu-slider__dot--active');
  }

  // ── Attach event listeners ───────────────────────────────────────
  const viewport = root.querySelector('.menu-slider__viewport');

  // Pointer Events
  if (viewport) {
    viewport.addEventListener('pointerdown', onPointerDown);
    viewport.addEventListener('pointerup', onPointerUp);
    viewport.addEventListener('pointercancel', onPointerCancel);
  }

  // Keyboard
  if (viewport) {
    viewport.addEventListener('keydown', onKeyDown);
  }

  // Dot buttons
  dotButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = parseInt(btn.getAttribute('data-slider-dot') ?? '0', 10);
      onDotClick(idx);
    });
  });

  // Prev / Next buttons
  const prevBtn = root.querySelector('[data-slider-prev]');
  const nextBtn = root.querySelector('[data-slider-next]');

  prevBtn?.addEventListener('click', () => {
    slide(DIR.PREV);
    onInteractionStart();
  });

  nextBtn?.addEventListener('click', () => {
    slide(DIR.NEXT);
    onInteractionStart();
  });

  // Autoplay pause on hover/focus (WCAG 2.2.1)
  root.addEventListener('mouseenter', () => {
    if (state) state.interacting = true;
    pauseAndScheduleResume();
  });
  root.addEventListener('mouseleave', () => {
    if (state) state.interacting = false;
  });
  root.addEventListener('focusin', () => {
    if (state) state.interacting = true;
    pauseAndScheduleResume();
  });
  root.addEventListener('focusout', () => {
    if (state) state.interacting = false;
  });

  // Visibility change (pause when tab hidden)
  document.addEventListener('visibilitychange', onVisibilityChange);

  // Reduced motion change listener
  const mql = window.matchMedia('(prefers-reduced-motion: reduce)');
  mql.addEventListener('change', (evt) => {
    if (!state) return;
    state.reduced = evt.matches;
    if (evt.matches) {
      stopAutoplay();
      // Instant transitions via CSS — track style already handles reduced
    } else {
      startAutoplay();
    }
  });

  // Start autoplay
  if (!reduced) {
    startAutoplay();
  }
}
