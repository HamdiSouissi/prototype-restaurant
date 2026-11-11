/**
 * Pointer-driven details (mouse users only):
 *
 *   data-magnetic    the element is gently attracted by the pointer
 *   .btn             a soft light follows the pointer inside the button
 *   data-spotlight   a glow and a gilded edge follow the pointer on cards
 *   data-tilt        the element tilts in 3D, with a moving sheen
 */

import { gsap } from './libs.js';
import { $$, env } from './utils.js';

export function initPointerEffects() {
    if (!env.finePointer) return;

    initLights();
    if (!gsap || env.reducedMotion) return;

    $$('[data-magnetic]').forEach(makeMagnetic);
    $$('[data-tilt]').forEach(makeTilt);
}

/**
 * One listener for every button and card: writes the pointer position, relative
 * to the element, into CSS variables read by the style sheets.
 */
function initLights() {
    const lights = [
        { selector: '.btn', x: '--mx', y: '--my' },
        { selector: '[data-spotlight]', x: '--sx', y: '--sy' },
    ];

    document.addEventListener('pointermove', (event) => {
        if (!(event.target instanceof Element)) return;

        lights.forEach((light) => {
            const element = event.target.closest(light.selector);
            if (!element) return;
            const box = element.getBoundingClientRect();
            element.style.setProperty(light.x, `${event.clientX - box.left}px`);
            element.style.setProperty(light.y, `${event.clientY - box.top}px`);
        });
    }, { passive: true });
}

function makeMagnetic(element) {
    const strength = 0.32;
    const moveX = gsap.quickTo(element, 'x', { duration: 0.6, ease: 'power3' });
    const moveY = gsap.quickTo(element, 'y', { duration: 0.6, ease: 'power3' });

    element.addEventListener('pointermove', (event) => {
        const box = element.getBoundingClientRect();
        moveX((event.clientX - (box.left + box.width / 2)) * strength);
        moveY((event.clientY - (box.top + box.height / 2)) * strength);
    }, { passive: true });

    element.addEventListener('pointerleave', () => {
        gsap.to(element, { x: 0, y: 0, duration: 1.1, ease: 'elastic.out(1, 0.45)', overwrite: 'auto' });
    });
}

function makeTilt(element) {
    const max = 7; // degrees
    gsap.set(element, { transformPerspective: 900 });
    const rotateX = gsap.quickTo(element, 'rotationX', { duration: 0.7, ease: 'power3' });
    const rotateY = gsap.quickTo(element, 'rotationY', { duration: 0.7, ease: 'power3' });

    element.addEventListener('pointermove', (event) => {
        const box = element.getBoundingClientRect();
        const x = (event.clientX - box.left) / box.width;
        const y = (event.clientY - box.top) / box.height;
        rotateY((x - 0.5) * max * 2);
        rotateX((0.5 - y) * max * 2);
        element.style.setProperty('--gx', `${x * 100}%`);
        element.style.setProperty('--gy', `${y * 100}%`);
    }, { passive: true });

    element.addEventListener('pointerleave', () => {
        rotateX(0);
        rotateY(0);
    });
}
