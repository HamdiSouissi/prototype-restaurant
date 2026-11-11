/**
 * Custom cursor (mouse users only): a gold dot followed by a soft ring.
 * The ring grows over anything clickable and shows a hint over elements
 * that carry one, e.g. <div data-cursor="Drag">.
 */

import { gsap } from './libs.js';
import { $, env } from './utils.js';

const CLICKABLE = 'a, button, label, select, summary, [role="button"]';
const TEXT_FIELD = 'input, textarea';

export function initCursor() {
    const root = $('[data-cursor-root]');
    if (!root || !gsap || !env.finePointer || env.reducedMotion) return;

    const dot = $('.cursor__dot', root);
    const ring = $('.cursor__ring', root);
    const label = $('[data-cursor-label]', root);

    // The dot sticks to the pointer, the ring trails slightly behind
    const moveDot = { x: gsap.quickTo(dot, 'x', { duration: 0.1, ease: 'power3' }), y: gsap.quickTo(dot, 'y', { duration: 0.1, ease: 'power3' }) };
    const moveRing = { x: gsap.quickTo(ring, 'x', { duration: 0.5, ease: 'power3' }), y: gsap.quickTo(ring, 'y', { duration: 0.5, ease: 'power3' }) };

    root.classList.add('is-hidden'); // until the mouse first moves
    let started = false;

    window.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') return;

        if (!started) {
            started = true;
            document.documentElement.classList.add('has-cursor');
            gsap.set([dot, ring], { x: event.clientX, y: event.clientY });
        }
        moveDot.x(event.clientX);
        moveDot.y(event.clientY);
        moveRing.x(event.clientX);
        moveRing.y(event.clientY);
        update(event.target);
    }, { passive: true });

    /** Adapts the cursor to what lies under the pointer. */
    function update(target) {
        if (!(target instanceof Element)) return;

        const hint = target.closest('[data-cursor]');
        const overField = Boolean(target.closest(TEXT_FIELD));

        if (hint) label.textContent = hint.dataset.cursor;
        root.classList.toggle('is-label', Boolean(hint));
        root.classList.toggle('is-link', !hint && Boolean(target.closest(CLICKABLE)));
        root.classList.toggle('is-hidden', overField); // text fields keep the native caret
    }

    // Elements can change under a still pointer (scrolling, overlays opening)
    document.addEventListener('pointerover', (event) => {
        if (started && event.pointerType === 'mouse') update(event.target);
    }, { passive: true });

    window.addEventListener('pointerdown', () => root.classList.add('is-down'), { passive: true });
    window.addEventListener('pointerup', () => root.classList.remove('is-down'), { passive: true });
    document.documentElement.addEventListener('mouseleave', () => root.classList.add('is-hidden'));
    document.documentElement.addEventListener('mouseenter', () => root.classList.remove('is-hidden'));
}
