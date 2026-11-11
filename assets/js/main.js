/**
 * AUREA — application entry point.
 *
 * Each feature lives in its own module (./modules) and is started here, in
 * reading order. Features are isolated from each other: if one fails, the
 * others keep working and the page always remains usable.
 *
 * Libraries (GSAP, ScrollTrigger, SplitText, Lenis, Swiper) are self-hosted
 * in ./vendor and loaded before this file — see the end of index.php.
 */

import { safely } from './modules/utils.js';
import { initScroll } from './modules/scroll.js';
import { initLoader } from './modules/loader.js';
import { initOverlays } from './modules/overlay.js';
import { initCursor } from './modules/cursor.js';
import { initHeader } from './modules/header.js';
import { initHero } from './modules/hero.js';
import { initScrollEffects } from './modules/scroll-effects.js';
import { initPointerEffects } from './modules/pointer.js';
import { initFilters } from './modules/filters.js';
import { initCart } from './modules/cart.js';
import { initGallery } from './modules/gallery.js';
import { initReservation } from './modules/reservation.js';
import { initAccordion } from './modules/accordion.js';
import { initContact } from './modules/contact.js';
import { initNewsletter } from './modules/newsletter.js';

/** Last resort: whatever went wrong, the loading screen must never stay in the way. */
function releasePage() {
    const root = document.documentElement;
    root.classList.remove('is-loading');
    root.classList.add('is-released');
}

// 1. Foundations: smooth scrolling first, then the loading screen takes over
safely('scroll', initScroll);
let pageReady = safely('loader', initLoader);
if (!pageReady) {
    releasePage();
    pageReady = Promise.resolve();
}

// 2. Features that need the page in its final layout (filters hide dishes, carousels size slides…)
safely('overlays', initOverlays);
safely('filters', initFilters);
safely('cart', initCart);
safely('gallery', initGallery);
safely('reservation', initReservation);
safely('accordion', initAccordion);
safely('contact', initContact);
safely('newsletter', initNewsletter);
safely('header', initHeader);

// 3. Motion: measured on that final layout
const playHero = safely('hero', initHero) || (() => {});
safely('scroll effects', initScrollEffects);
safely('pointer effects', initPointerEffects);
safely('cursor', initCursor);

// 4. Curtain up
pageReady
    .then(() => safely('hero entrance', playHero))
    .catch((error) => {
        console.error('[loader]', error);
        releasePage();
    });
