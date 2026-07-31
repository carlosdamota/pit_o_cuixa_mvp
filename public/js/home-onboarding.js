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

    // ── Touch / Pointer Events (Mobile & Touchscreen) ─────────────────
    dragItems.forEach((item) => {
        item.addEventListener('pointerdown', (e) => {
            // Only handle primary touch / mouse button
            if (e.button !== 0 && e.pointerType === 'mouse') return;

            isDragging = true;
            activeDragItem = item;
            item.setPointerCapture(e.pointerId);

            // Create floating clone for drag visual
            pointerClone = item.cloneNode(true);
            pointerClone.classList.add('onboarding__drag-clone');
            document.body.appendChild(pointerClone);

            moveClone(e.clientX, e.clientY);
            item.classList.add('onboarding__drag-item--dragging');
        });

        item.addEventListener('pointermove', (e) => {
            if (!isDragging || !pointerClone) return;

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

            if (isOverTarget(e.clientX, e.clientY) && activeDragItem) {
                completeSelection(activeDragItem.dataset.mode);
            }

            activeDragItem = null;
        };

        item.addEventListener('pointerup', handlePointerEnd);
        item.addEventListener('pointercancel', handlePointerEnd);
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
                }, 5000);
            }
        } catch (e) {
            // Ignore parsing error gracefully
        }
    }
});
