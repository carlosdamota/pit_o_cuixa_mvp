/**
 * Pit o Cuixa — Home Onboarding Drag & Drop Controller
 *
 * Handles drag and drop interaction (Mouse/HTML5 + Pointer/Touch Events)
 * for selecting "En local" vs "A domicilio" and navigating to the menu.
 */

document.addEventListener('DOMContentLoaded', () => {
    const dragSection = document.getElementById('drag-section');
    const dragItems = document.querySelectorAll('.onboarding__drag-item');
    const dropTarget = document.getElementById('drop-target');

    if (!dragSection || !dropTarget || dragItems.length === 0) {
        return;
    }

    // Get active lang suffix from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('lang');
    const langQuery = currentLang ? `&lang=${encodeURIComponent(currentLang)}` : '';

    let activeDragItem = null;
    let pointerClone = null;
    let isDragging = false;

    // Helper: navigate to menu with chosen mode
    function completeSelection(mode) {
        // Save preference in sessionStorage
        try {
            sessionStorage.setItem('pitocuixa_order_mode', mode);
        } catch (e) {
            // Ignore storage errors
        }

        // Animate drop target success state
        dropTarget.classList.add('onboarding__target--success');

        // Redirect after brief visual feedback
        setTimeout(() => {
            window.location.href = `/menu?mode=${mode}${langQuery}`;
        }, 350);
    }

    // Check if a point (x, y) collides with target rect
    function isOverTarget(x, y) {
        const rect = dropTarget.getBoundingClientRect();
        // Expand hit area slightly for better touch ergonomics
        const padding = 20;
        return (
            x >= rect.left - padding &&
            x <= rect.right + padding &&
            y >= rect.top - padding &&
            y <= rect.bottom + padding
        );
    }

    // ── HTML5 Drag & Drop (Desktop) ──────────────────────────────────
    dragItems.forEach((item) => {
        item.addEventListener('dragstart', (e) => {
            activeDragItem = item;
            item.classList.add('onboarding__drag-item--dragging');
            e.dataTransfer.setData('text/plain', item.dataset.mode);
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', () => {
            if (activeDragItem) {
                activeDragItem.classList.remove('onboarding__drag-item--dragging');
                activeDragItem = null;
            }
            dropTarget.classList.remove('onboarding__target--dragover');
        });
    });

    dropTarget.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        dropTarget.classList.add('onboarding__target--dragover');
    });

    dropTarget.addEventListener('dragenter', (e) => {
        e.preventDefault();
        dropTarget.classList.add('onboarding__target--dragover');
    });

    dropTarget.addEventListener('dragleave', () => {
        dropTarget.classList.remove('onboarding__target--dragover');
    });

    dropTarget.addEventListener('drop', (e) => {
        e.preventDefault();
        dropTarget.classList.remove('onboarding__target--dragover');
        const mode = e.dataTransfer.getData('text/plain') || (activeDragItem ? activeDragItem.dataset.mode : null);

        if (mode) {
            completeSelection(mode);
        }
    });

    // ── Touch / Pointer Events (Mobile & Touchscreen) & Direct Click ───
    dragItems.forEach((item) => {
        let startX = 0;
        let startY = 0;
        let hasMovedFar = false;

        item.addEventListener('pointerdown', (e) => {
            // Only handle primary touch / mouse button
            if (e.button !== 0 && e.pointerType === 'mouse') return;

            isDragging = true;
            hasMovedFar = false;
            activeDragItem = item;
            startX = e.clientX;
            startY = e.clientY;
            try {
                item.setPointerCapture(e.pointerId);
            } catch (err) {
                // Ignore pointer capture errors on older browsers
            }

            // Create floating clone for drag visual
            pointerClone = item.cloneNode(true);
            pointerClone.classList.add('onboarding__drag-clone');
            document.body.appendChild(pointerClone);

            moveClone(e.clientX, e.clientY);
            item.classList.add('onboarding__drag-item--dragging');
        });

        item.addEventListener('pointermove', (e) => {
            if (!isDragging || !pointerClone) return;

            const dist = Math.hypot(e.clientX - startX, e.clientY - startY);
            if (dist > 8) {
                hasMovedFar = true;
            }

            moveClone(e.clientX, e.clientY);

            if (isOverTarget(e.clientX, e.clientY)) {
                dropTarget.classList.add('onboarding__target--dragover');
            } else {
                dropTarget.classList.remove('onboarding__target--dragover');
            }
        });

        const handlePointerEnd = (e) => {
            if (!isDragging) return;

            isDragging = false;
            dropTarget.classList.remove('onboarding__target--dragover');

            if (pointerClone) {
                pointerClone.remove();
                pointerClone = null;
            }

            if (activeDragItem) {
                activeDragItem.classList.remove('onboarding__drag-item--dragging');
            }

            const isOver = isOverTarget(e.clientX, e.clientY);
            const isClickTap = !hasMovedFar;

            if (activeDragItem && (isOver || isClickTap)) {
                completeSelection(activeDragItem.dataset.mode);
            }

            activeDragItem = null;
        };

        item.addEventListener('pointerup', handlePointerEnd);
        item.addEventListener('pointercancel', handlePointerEnd);

        // Keyboard interaction (Enter / Space)
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                completeSelection(item.dataset.mode);
            }
        });
    });

    function moveClone(x, y) {
        if (!pointerClone) return;
        pointerClone.style.left = `${x}px`;
        pointerClone.style.top = `${y}px`;
    }

    // ── 5-Second Random Rotating Quotes Ticker ────────────────────────
    const quoteBox = document.getElementById('home-quote-box');
    const quoteText = document.getElementById('home-quote-text');
    const quoteDotsContainer = document.getElementById('home-quote-dots');

    if (quoteBox && quoteText && quoteBox.dataset.quotes) {
        try {
            const quotes = JSON.parse(quoteBox.dataset.quotes);
            if (Array.isArray(quotes) && quotes.length > 1) {
                let currentIndex = 0;

                const updateDots = (activeIdx) => {
                    if (!quoteDotsContainer) return;
                    const dots = quoteDotsContainer.querySelectorAll('.quote-dot');
                    dots.forEach((dot, idx) => {
                        if (idx === activeIdx) {
                            dot.classList.add('quote-dot--active');
                        } else {
                            dot.classList.remove('quote-dot--active');
                        }
                    });
                };

                setInterval(() => {
                    let nextIndex;
                    do {
                        nextIndex = Math.floor(Math.random() * quotes.length);
                    } while (nextIndex === currentIndex);

                    currentIndex = nextIndex;

                    quoteBox.classList.add('onboarding__quote-card--fading');

                    setTimeout(() => {
                        quoteText.textContent = `“${quotes[currentIndex]}”`;
                        updateDots(currentIndex);
                        quoteBox.classList.remove('onboarding__quote-card--fading');
                    }, 350);
                }, 8000);
            }
        } catch (e) {
            // Ignore parsing error gracefully
        }
    }

    // ── PWA Installation Controller ────────────────────────────────────
    const pwaContainer = document.getElementById('pwa-install-container');
    const pwaBtn = document.getElementById('pwa-install-btn');
    let deferredPrompt = null;

    // Detect iOS (iPadOS reports as MacIntel with touch capabilities)
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    // Check if already installed / running in standalone mode or previously recorded in localStorage
    const isInstalled = window.matchMedia('(display-mode: standalone)').matches ||
                        window.navigator.standalone === true ||
                        localStorage.getItem('pwa_installed') === 'true';

    if (isInstalled && pwaContainer) {
        pwaContainer.hidden = true;
    } else if (pwaContainer) {
        // Show button by default on all platforms — not gated behind beforeinstallprompt
        pwaContainer.hidden = false;

        // Check for native installed related apps API (Chrome/Android/Desktop)
        if ('getInstalledRelatedApps' in navigator) {
            navigator.getInstalledRelatedApps().then((apps) => {
                if (apps && apps.length > 0) {
                    localStorage.setItem('pwa_installed', 'true');
                    pwaContainer.hidden = true;
                    return;
                }
            }).catch(() => {});
        }

        // Chromium: capture beforeinstallprompt for native install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        if (pwaBtn) {
            pwaBtn.addEventListener('click', async () => {
                // Chromium path: use deferred prompt for native install dialog
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        localStorage.setItem('pwa_installed', 'true');
                        pwaContainer.hidden = true;
                    }
                    deferredPrompt = null;
                    return;
                }

                // iOS / non-Chromium path: show manual "Add to Home Screen" instructions
                if (isIOS) {
                    showIOSInstallInstructions(pwaContainer);
                }
            });
        }

        // Hide button automatically if app is installed
        window.addEventListener('appinstalled', () => {
            localStorage.setItem('pwa_installed', 'true');
            pwaContainer.hidden = true;
            deferredPrompt = null;
        });
    }

    /**
     * Show a toast with manual "Add to Home Screen" instructions for iOS.
     * Reads translated strings from data attributes on the PWA container.
     */
    function showIOSInstallInstructions(anchor) {
        // Remove any existing toast first
        const existing = document.getElementById('pwa-ios-toast');
        if (existing) existing.remove();

        const title = anchor.dataset.iosTitle || 'Install App';
        const step1 = anchor.dataset.iosStep1 || 'Tap the Share button';
        const step2 = anchor.dataset.iosStep2 || 'Select "Add to Home Screen"';
        const gotit = anchor.dataset.iosGotit || 'Got it';

        const toast = document.createElement('div');
        toast.id = 'pwa-ios-toast';
        toast.className = 'onboarding__pwa-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.innerHTML =
            '<div class="onboarding__pwa-toast-content">' +
                '<strong class="onboarding__pwa-toast-title">' + title + '</strong>' +
                '<ol class="onboarding__pwa-toast-steps">' +
                    '<li>' + step1 + '</li>' +
                    '<li>' + step2 + '</li>' +
                '</ol>' +
            '</div>' +
            '<button type="button" class="onboarding__pwa-toast-close" aria-label="' + gotit + '">' +
                gotit +
            '</button>';

        document.body.appendChild(toast);

        // Animate in on next frame
        requestAnimationFrame(function () {
            toast.classList.add('onboarding__pwa-toast--visible');
        });

        // Close button handler
        toast.querySelector('.onboarding__pwa-toast-close').addEventListener('click', function () {
            toast.classList.remove('onboarding__pwa-toast--visible');
            toast.addEventListener('transitionend', function () { toast.remove(); }, { once: true });
        });

        // Auto-dismiss after 10 seconds
        setTimeout(function () {
            if (toast.parentNode) {
                toast.classList.remove('onboarding__pwa-toast--visible');
                toast.addEventListener('transitionend', function () { toast.remove(); }, { once: true });
            }
        }, 10000);
    }
});
