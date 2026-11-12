/**
 * Carousels (Swiper) and the photo lightbox.
 *
 *   data-gallery   draggable photo carousel; each photograph slides inside
 *                  its frame while the carousel moves (inner parallax)
 *   data-reviews   guest reviews carousel
 *   data-lightbox  link opening the photograph in the full-screen viewer
 */

import { gsap, Swiper } from './libs.js';
import { $, $$, clamp, env, pad } from './utils.js';
import { openOverlay } from './overlay.js';

const EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';

export function initGallery() {
    initLightbox(); // works with or without the carousel
    if (!Swiper) return;

    initPhotoCarousel();
    initReviewsCarousel();
}

/* ── Photo carousel ───────────────────────────────────────────────────────── */

function initPhotoCarousel() {
    const root = $('[data-gallery]');
    if (!root) return;

    const current = $('[data-gallery-current]');
    const bar = $('[data-gallery-progress]');
    const images = $$('[data-gallery-img]', root);
    const shift = env.reducedMotion ? 0 : 6.5; // % of the photograph's width, each way
    let origin = 0; // left edge of the carousel on screen

    root.style.setProperty('--swiper-wrapper-transition-timing-function', EASE);

    const measure = (swiper) => { origin = swiper.el.getBoundingClientRect().left; };

    /** Places every photograph inside its frame according to where its slide sits on screen. */
    const parallax = (swiper) => {
        const viewport = window.innerWidth;

        swiper.slides.forEach((slide, index) => {
            const width = swiper.slidesSizesGrid[index];
            const centre = origin + slide.swiperSlideOffset + swiper.translate + width / 2;
            // -1 when the slide leaves on the left, +1 when it enters from the right
            const position = clamp((centre - viewport / 2) / (viewport / 2 + width / 2), -1, 1);
            if (images[index]) images[index].style.transform = `translate3d(${-position * shift}%, 0, 0)`;
        });
    };

    /** Counter (the leading photograph; the last one once the end is reached) and gauge. */
    const report = (swiper) => {
        const progress = clamp(swiper.progress || 0, 0, 1);
        const leading = swiper.isEnd ? swiper.slides.length : swiper.activeIndex + 1;
        if (current) current.textContent = pad(leading);
        if (bar) bar.style.transform = `scaleX(${0.1 + 0.9 * progress})`;
    };

    const refresh = (swiper) => {
        measure(swiper);
        parallax(swiper);
        report(swiper);
    };

    new Swiper(root, {
        slidesPerView: 'auto',
        spaceBetween: 14,
        speed: 1000,
        grabCursor: true,
        navigation: { prevEl: '[data-gallery-prev]', nextEl: '[data-gallery-next]' },
        a11y: announcements('Fotografie precedenti', 'Fotografie successive'),
        breakpoints: { 900: { spaceBetween: 26 } },
        on: {
            init: refresh,
            resize: refresh,
            setTranslate: parallax,
            progress: report,
            activeIndexChange: report,
            reachEnd: report,
            fromEdge: report,
            // Photographs and gauge move in step with the carousel, whatever drives it
            setTransition: (swiper, duration) => root.parentElement.style.setProperty('--carousel-speed', `${duration}ms`),
        },
    });
}

/**
 * What a carousel says to assistive technologies. Swiper labels its arrows itself
 * (in English by default), so the Italian wording is handed over here.
 */
const announcements = (previous, next) => ({
    prevSlideMessage: previous,
    nextSlideMessage: next,
    firstSlideMessage: 'Sei all’inizio',
    lastSlideMessage: 'Sei alla fine',
});

/* ── Reviews carousel ─────────────────────────────────────────────────────── */

function initReviewsCarousel() {
    const root = $('[data-reviews]');
    if (!root) return;

    root.style.setProperty('--swiper-wrapper-transition-timing-function', EASE);

    new Swiper(root, {
        slidesPerView: 'auto',
        spaceBetween: 16,
        speed: 900,
        grabCursor: true,
        navigation: { prevEl: '[data-reviews-prev]', nextEl: '[data-reviews-next]' },
        a11y: announcements('Recensioni precedenti', 'Recensioni successive'),
        breakpoints: { 900: { spaceBetween: 28 } },
    });
}

/* ── Lightbox ─────────────────────────────────────────────────────────────── */

function initLightbox() {
    const viewer = $('[data-overlay="lightbox"]');
    const links = $$('[data-lightbox]');
    if (!viewer || !links.length) return;

    const image = $('[data-lightbox-img]', viewer);
    const figure = $('[data-lightbox-figure]', viewer);
    const count = $('[data-lightbox-count]', viewer);
    const label = $('[data-lightbox-label]', viewer);
    const animated = Boolean(gsap) && !env.reducedMotion;
    let index = 0;

    // One entry per photograph: full-size file, caption, proportions, thumbnail already on the page
    const photos = links.map((link) => {
        const thumbnail = $('img', link);
        return {
            src: link.href,
            caption: link.dataset.caption || '',
            ratio: thumbnail ? thumbnail.getAttribute('width') / thumbnail.getAttribute('height') : 1.5,
            thumbnail,
        };
    });
    const at = (n) => photos[(n + photos.length) % photos.length];

    /** Fetches a photograph; resolves once it can be displayed. */
    const load = (src) => new Promise((resolve) => {
        const request = new Image();
        request.onload = request.onerror = () => resolve();
        request.src = src;
    });

    const paint = (photo, src = photo.src) => {
        figure.style.setProperty('--ratio', photo.ratio); // the frame keeps its proportions while files load
        image.src = src;
        image.alt = photo.caption;
        label.textContent = photo.caption;
        count.textContent = `${pad(index + 1)} / ${pad(photos.length)}`;
    };

    /** Shows photograph n; "direction" (-1 or 1) decides which way it slides in, 0 = no slide. */
    const show = (n, direction = 0) => {
        index = (n + photos.length) % photos.length;
        const photo = photos[index];
        const shown = index;
        const stillCurrent = () => shown === index;

        if (!animated || direction === 0) {
            // Instant: the thumbnail stands in until the full-size file arrives
            const preview = photo.thumbnail && (photo.thumbnail.currentSrc || photo.thumbnail.src);
            if (preview) paint(photo, preview);
            load(photo.src).then(() => { if (stillCurrent()) paint(photo); });
        } else {
            const parts = Array.from(figure.children);
            const pause = new Promise((done) => { window.setTimeout(done, 280); });
            gsap.killTweensOf(parts);
            gsap.to(parts, { autoAlpha: 0, x: -40 * direction, duration: 0.26, ease: 'power2.in' });
            Promise.all([load(photo.src), pause]).then(() => {
                if (!stillCurrent()) return; // the visitor has already moved on
                paint(photo);
                gsap.fromTo(parts, { autoAlpha: 0, x: 40 * direction }, { autoAlpha: 1, x: 0, duration: 0.7, ease: 'power3.out', stagger: 0.05 });
            });
        }

        // Neighbours are fetched in advance, so browsing feels instant
        load(at(index + 1).src);
        load(at(index - 1).src);
    };

    const step = (direction) => show(index + direction, direction);

    links.forEach((link, n) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            if (animated) gsap.set(figure.children, { clearProps: 'all' });
            show(n);
            openOverlay(viewer);
        });
    });

    $('[data-lightbox-prev]', viewer).addEventListener('click', () => step(-1));
    $('[data-lightbox-next]', viewer).addEventListener('click', () => step(1));

    document.addEventListener('keydown', (event) => {
        if (!viewer.classList.contains('is-open')) return;
        if (event.key === 'ArrowLeft') step(-1);
        if (event.key === 'ArrowRight') step(1);
    });

    // Swipe (touch) or drag (mouse) sideways to browse
    let startX = null;
    image.addEventListener('pointerdown', (event) => { startX = event.clientX; });
    image.addEventListener('pointercancel', () => { startX = null; });
    image.addEventListener('pointerup', (event) => {
        if (startX === null) return;
        const distance = event.clientX - startX;
        startX = null;
        if (Math.abs(distance) > 50) step(distance < 0 ? 1 : -1);
    });
}
