/**
 * Smooth scrolling (Lenis) kept in sync with GSAP ScrollTrigger,
 * animated anchor navigation and a scroll lock used by overlays.
 */

import { gsap, ScrollTrigger, Lenis } from './libs.js';
import { env } from './utils.js';

let lenis = null;
let locks = 0;

export function initScroll() {
    if (Lenis && gsap && !env.reducedMotion) {
        lenis = new Lenis({ lerp: 0.09, smoothWheel: true, wheelMultiplier: 1 });

        // One clock for everything: GSAP's ticker drives Lenis, Lenis informs ScrollTrigger
        if (ScrollTrigger) lenis.on('scroll', ScrollTrigger.update);
        gsap.ticker.add((time) => lenis.raf(time * 1000));
        gsap.ticker.lagSmoothing(0);
    }

    // Every in-page link glides to its target
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="#"]');
        if (!link || event.defaultPrevented) return;

        const hash = link.getAttribute('href');
        const target = hash.length > 1 ? document.querySelector(hash) : null;
        if (!target) return;

        event.preventDefault();
        // Next frame: lets menus and drawers close (and release the scroll) first
        requestAnimationFrame(() => scrollToTarget(target));
    });
}

/** Scrolls to an element (or a position in pixels). */
export function scrollToTarget(target, options = {}) {
    if (lenis) {
        lenis.scrollTo(target, {
            duration: 1.5,
            easing: (t) => 1 - Math.pow(1 - t, 4),
            ...options,
        });
        return;
    }
    const top = typeof target === 'number' ? target : target.getBoundingClientRect().top + window.scrollY;
    window.scrollTo({ top, behavior: env.reducedMotion ? 'auto' : 'smooth' });
}

/** Freezes page scrolling. Calls can be nested (drawer + dialog). */
export function lockScroll() {
    locks += 1;
    if (locks > 1) return;
    if (lenis) lenis.stop();
    else document.documentElement.style.overflow = 'hidden';
}

export function unlockScroll() {
    if (locks === 0) return;
    locks -= 1;
    if (locks > 0) return;
    if (lenis) lenis.start();
    else document.documentElement.style.overflow = '';
}
