/**
 * Clippy IIFE. Vanilla ES6+ (no jQuery, no build step).
 */

(function (window, document) {
    'use strict';

    const D = window.cffClippyI18n || {};
    if (!D.idleMs) {
        return;
    }

    // REQ-CFF-CLIPPY-06 / 07: respect prior dismissal. Storage failure
    // is treated as "not dismissed" so private browsing still works.
    const dismissed = (() => {
        try {
            return window.localStorage.getItem('cff_clippy_dismissed') === '1';
        } catch (e) {
            return false;
        }
    })();
    if (dismissed) {
        return;
    }

    const root = document.getElementById('cff-clippy-root');
    if (!root) {
        return;
    }
    const bubble   = root.querySelector('#cff-clippy-bubble'),
          cta      = root.querySelector('#cff-clippy-cta'),
          closeBtn = root.querySelector('#cff-clippy-close'),
          mascot   = root.querySelector('#cff-clippy-mascot');
    if (!bubble || !cta || !closeBtn || !mascot) {
        return;
    }

    // Populate text from the localized data object.
    const msgEl = root.querySelector('.cff-clippy-message');
    if (msgEl) {
        msgEl.textContent = D.bubbleMessage;
    }
    bubble.setAttribute('aria-label', D.bubbleMessage);
    cta.textContent = D.ctaLabel;
    cta.setAttribute('aria-label', D.ctaLabel);
    closeBtn.setAttribute('aria-label', D.closeLabel);

    const state = {
        status: 'idle',
        idleHandle: null,
        visibilityHandle: null,
        reappearHandle: null,
        lastCtaClickAt: 0,
        reducedMotion: prefersReducedMotion()
    };

    // ---------- Interaction detection ----------
    // Defer the show if the user is actively interacting (clicking,
    // typing, scrolling, touching). 500ms of quiet = interaction ended.
    // The longer debounce covers typical typing pauses between words.
    let isInteracting = false,
        interactionTimeout = null;

    function noteInteraction() {
        isInteracting = true;
        clearTimeout(interactionTimeout);
        interactionTimeout = setTimeout(function () {
            isInteracting = false;
        }, 500);
    }

    ['mousemove', 'mousedown', 'keydown', 'input', 'focusin', 'touchstart', 'wheel', 'scroll'].forEach(function (ev) {
        window.addEventListener(ev, noteInteraction, { passive: true });
    });

    // Defer the hide when the user is hovering over Clippy or has focus
    // on its controls. Wait until they leave before running the animation.
    let userOnClippy = false,
        pendingHideReason = null;

    root.addEventListener('mouseenter', function () { userOnClippy = true; });
    root.addEventListener('mouseleave', function () {
        userOnClippy = false;
        tryPendingHide();
    });
    root.addEventListener('focusin', function () { userOnClippy = true; });
    root.addEventListener('focusout', function (e) {
        if (e.relatedTarget && root.contains(e.relatedTarget)) {
            return; // Focus is moving within root, not leaving.
        }
        userOnClippy = false;
        tryPendingHide();
    });

    function now() {
        return (window.performance && typeof window.performance.now === 'function')
            ? window.performance.now()
            : Date.now();
    }

    function prefersReducedMotion() {
        try {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {
            return false;
        }
    }

    function applyReducedMotion(matches) {
        state.reducedMotion = !!matches;
        root.style.transition = state.reducedMotion ? 'none' : '';
    }

    function clearAllTimers() {
        clearTimeout(state.idleHandle);
        state.idleHandle = null;
        clearTimeout(state.visibilityHandle);
        state.visibilityHandle = null;
        clearTimeout(state.reappearHandle);
        state.reappearHandle = null;
    }

    function startIdleTimer() {
        clearTimeout(state.idleHandle);
        state.idleHandle = setTimeout(showClippy, D.idleMs);
    }

    function showClippy() {
        if (state.status === 'dismissed' || state.status === 'visible') {
            return;
        }
        if (isInteracting) {
            setTimeout(showClippy, 100);
            return;
        }
        state.status = 'visible';
        root.hidden = false;
        root.classList.remove('is-leaving');
        // Force a reflow so the browser paints the initial state before
        // applying is-visible; without this, the transition has no
        // starting point and the overlay appears instantly.
        // eslint-disable-next-line no-unused-expressions
        root.offsetWidth;
        root.classList.add('is-visible');

        clearTimeout(state.visibilityHandle);
        state.visibilityHandle = setTimeout(function () {
            hideClippy('auto');
        }, D.visibleMs);
    }

    function tryPendingHide() {
        if (pendingHideReason !== null && !userOnClippy) {
            const reason = pendingHideReason;
            pendingHideReason = null;
            actuallyHideClippy(reason);
        }
    }

    function hideClippy(reason) {
        // Dismissals (X click, CTA click, Esc) proceed immediately.
        // Only the auto-hide timer defers while the user is on Clippy.
        if (userOnClippy && reason === 'auto') {
            pendingHideReason = reason;
            return;
        }
        actuallyHideClippy(reason);
    }

    function actuallyHideClippy(reason) {
        root.classList.remove('is-visible');
        if (!state.reducedMotion) {
            // Force a reflow so the browser paints the visible state
            // before applying is-leaving.
            // eslint-disable-next-line no-unused-expressions
            root.offsetWidth;
            root.classList.add('is-leaving');
        }
        clearTimeout(state.visibilityHandle);
        state.visibilityHandle = null;
        setTimeout(function () {
            // Remove the leaving class first, then wait a frame before
            // hiding the element so the transition completes.
            root.classList.remove('is-leaving');
            setTimeout(function () {
                root.hidden = true;
            }, 80);
        }, state.reducedMotion ? 0 : 600);

        if (reason === 'dismissed') {
            return;
        }
        // REQ-CFF-CLIPPY-05: only the auto-hide path starts the reappear loop.
        state.status = 'hidden_pending_reappear';
        clearTimeout(state.reappearHandle);
        state.reappearHandle = setTimeout(showClippy, D.reappearMs);
    }

    function dismiss() {
        // REQ-CFF-CLIPPY-06 / 07: persist terminal dismissal.
        try {
            window.localStorage.setItem('cff_clippy_dismissed', '1');
        } catch (e) { /* swallow */ }
        state.status = 'dismissed';
        clearAllTimers();
        hideClippy('dismissed');
    }

    // ---------- Dismiss listeners ----------
    closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        dismiss();
    });

    // REQ-CFF-CLIPPY-14: debounced CTA click, noopener+noreferrer.
    cta.addEventListener('click', function (e) {
        e.preventDefault();
        const t = now();
        if (t - state.lastCtaClickAt < 600) {
            return;
        }
        state.lastCtaClickAt = t;
        try {
            window.open(D.customizationUrl, '_blank', 'noopener,noreferrer');
        } catch (err) { /* swallow */ }
        dismiss();
    });

    // REQ-CFF-CLIPPY-13: Esc keypress dismisses while visible.
    document.addEventListener('keydown', function (e) {
        if (state.status === 'visible' && (e.key === 'Escape' || e.keyCode === 27)) {
            e.preventDefault();
            dismiss();
        }
    });

    // REQ-CFF-CLIPPY-19: cleanup on page hide.
    window.addEventListener('pagehide', clearAllTimers);

    // REQ-CFF-CLIPPY-12: reduced-motion short-circuit on first paint + live updates.
    if (state.reducedMotion) {
        root.style.transition = 'none';
    }
    try {
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (mq) {
            if (typeof mq.addEventListener === 'function') {
                mq.addEventListener('change', function (ev) { applyReducedMotion(ev.matches); });
            } else if (typeof mq.addListener === 'function') {
                // Older Safari/Chrome API.
                mq.addListener(function (ev) { applyReducedMotion(ev.matches); });
            }
        }
    } catch (e) { /* matchMedia unsupported */ }

    startIdleTimer();
}(window, document));
