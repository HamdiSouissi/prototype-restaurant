/**
 * Overlays: one small manager for the cart drawer, dialogs and the lightbox.
 * Handles visibility, scroll lock, focus (trap + restore) and the Escape key.
 *
 *   <div data-overlay="cart" inert aria-hidden="true"> … <button data-overlay-close>
 *   <button data-modal-open="legal">
 */

import { $ } from './utils.js';
import { lockScroll, unlockScroll } from './scroll.js';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const stack = []; // open overlays, top-most last
const opener = new WeakMap(); // overlay → element focused before it opened

const resolve = (ref) => (typeof ref === 'string' ? $(`[data-overlay="${ref}"]`) : ref);
const focusables = (overlay) => Array.from(overlay.querySelectorAll(FOCUSABLE)).filter((el) => el.offsetParent !== null);

export function openOverlay(ref) {
    const overlay = resolve(ref);
    if (!overlay || overlay.classList.contains('is-open')) return;

    opener.set(overlay, document.activeElement);
    overlay.removeAttribute('inert');
    overlay.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-open');
    stack.push(overlay);
    lockScroll();

    requestAnimationFrame(() => {
        const first = focusables(overlay)[0];
        if (first) first.focus({ preventScroll: true });
    });
    overlay.dispatchEvent(new CustomEvent('overlay:open', { bubbles: true }));
}

export function closeOverlay(ref) {
    const overlay = resolve(ref);
    if (!overlay || !overlay.classList.contains('is-open')) return;

    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    overlay.setAttribute('inert', '');
    stack.splice(stack.indexOf(overlay), 1);
    unlockScroll();

    const previous = opener.get(overlay);
    if (previous && document.contains(previous)) previous.focus({ preventScroll: true });
    overlay.dispatchEvent(new CustomEvent('overlay:close', { bubbles: true }));
}

export function initOverlays() {
    document.addEventListener('click', (event) => {
        const closer = event.target.closest('[data-overlay-close]');
        if (closer) {
            closeOverlay(closer.closest('[data-overlay]'));
            return;
        }
        const trigger = event.target.closest('[data-modal-open]');
        if (trigger) openOverlay(trigger.dataset.modalOpen);
    });

    document.addEventListener('keydown', (event) => {
        const top = stack[stack.length - 1];
        if (!top) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeOverlay(top);
            return;
        }

        // Keep keyboard focus inside the top-most overlay
        if (event.key === 'Tab') {
            const items = focusables(top);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
}
