/**
 * Category filters (menu cards and order board).
 *
 *   <div data-filter-tabs="menu">            group of buttons
 *       <span data-filter-pill>              sliding highlight (optional)
 *       <button data-filter="pasta" data-filter-title="Primi">
 *   <span data-filter-heading="menu">        receives the title of the category (optional)
 *   <div data-filter-grid="menu">            items carrying data-category="pasta"
 *
 * Without JavaScript every item is listed; with it, one category at a time.
 */

import { gsap, ScrollTrigger } from './libs.js';
import { $, $$, env } from './utils.js';

export function initFilters() {
    $$('[data-filter-tabs]').forEach(setupGroup);
}

function setupGroup(tabs) {
    const name = tabs.dataset.filterTabs;
    const grid = $(`[data-filter-grid="${name}"]`);
    if (!grid) return;

    const buttons = $$('[data-filter]', tabs);
    const items = $$('[data-category]', grid);
    const pill = $('[data-filter-pill]', tabs);
    const heading = $(`[data-filter-heading="${name}"]`);
    const animated = Boolean(gsap) && !env.reducedMotion;
    let current = (buttons.find((button) => button.classList.contains('is-active')) || buttons[0]).dataset.filter;

    const matching = (category) => items.filter((item) => item.dataset.category === category);
    const show = (category) => items.forEach((item) => { item.hidden = item.dataset.category !== category; });

    /** Slides the highlight under the active button. */
    const movePill = () => {
        const active = buttons.find((button) => button.dataset.filter === current);
        if (!pill || !active) return;
        pill.style.width = `${active.offsetWidth}px`;
        pill.style.transform = `translateX(${active.offsetLeft}px)`;
    };

    const select = (category, { focus = false } = {}) => {
        if (category === current) return;
        current = category;

        buttons.forEach((button) => {
            const active = button.dataset.filter === category;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
            if (!active) return;

            if (focus) button.focus({ preventScroll: true });
            // Keeps the active button in view when the row scrolls sideways (small screens)
            tabs.scrollTo({ left: button.offsetLeft - (tabs.clientWidth - button.offsetWidth) / 2, behavior: 'smooth' });
            if (heading && button.dataset.filterTitle) swapHeading(heading, button.dataset.filterTitle, animated);
        });
        movePill();

        const incoming = matching(category);
        grid.scrollTop = 0;

        if (!animated) {
            show(category);
            return;
        }

        // The grid glides from its current height to the new one, cards follow in sequence
        const before = grid.offsetHeight;
        gsap.killTweensOf([grid, ...items]);
        gsap.set(grid, { clearProps: 'height' });
        show(category);
        const after = grid.offsetHeight;

        if (before !== after) {
            gsap.fromTo(grid, { height: before }, {
                height: after,
                duration: 0.7,
                ease: 'power3.inOut',
                clearProps: 'height',
                onComplete: () => ScrollTrigger && ScrollTrigger.refresh(),
            });
        }
        gsap.fromTo(incoming, { autoAlpha: 0, y: 28 }, {
            autoAlpha: 1, y: 0, duration: 0.9, ease: 'power3.out', stagger: 0.07, clearProps: 'transform,opacity,visibility',
        });
    };

    /** Small screens: marks the side(s) of the row where more categories are waiting (faded in the style sheet). */
    const hintEdges = () => {
        const hidden = tabs.scrollWidth - tabs.clientWidth - tabs.scrollLeft;
        tabs.classList.toggle('has-more-start', tabs.scrollLeft > 4);
        tabs.classList.toggle('has-more-end', hidden > 4);
    };

    /* ── Events ───────────────────────────────────────────────────────────── */

    tabs.addEventListener('scroll', hintEdges, { passive: true });
    window.addEventListener('resize', hintEdges, { passive: true });

    tabs.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter]');
        if (button) select(button.dataset.filter);
    });

    // Arrow keys move between categories, as expected from a list of tabs
    tabs.addEventListener('keydown', (event) => {
        const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
        const index = buttons.findIndex((button) => button.dataset.filter === current);
        let next = null;

        if (step) next = buttons[(index + step + buttons.length) % buttons.length];
        if (event.key === 'Home') next = buttons[0];
        if (event.key === 'End') next = buttons[buttons.length - 1];
        if (!next) return;

        event.preventDefault();
        select(next.dataset.filter, { focus: true });
    });

    /* ── Initial state ────────────────────────────────────────────────────── */

    buttons.forEach((button) => { button.tabIndex = button.dataset.filter === current ? 0 : -1; });
    show(current);
    hintEdges();
    if (document.fonts) document.fonts.ready.then(hintEdges);

    if (pill) {
        // No transition for the first placement, nor while the layout is being resized
        const place = () => {
            pill.style.transition = 'none';
            movePill();
            void pill.offsetWidth;
            pill.style.transition = '';
        };
        place();
        window.addEventListener('resize', place, { passive: true });
        if (document.fonts) document.fonts.ready.then(place);
    }

    // First appearance: the cards of the opening category rise one after the other
    if (animated && ScrollTrigger) {
        const first = matching(current);
        gsap.set(first, { autoAlpha: 0, y: 40 });
        ScrollTrigger.create({
            trigger: grid,
            start: 'top 85%',
            once: true,
            onEnter: () => gsap.to(first, {
                autoAlpha: 1, y: 0, duration: 1.1, ease: 'power3.out', stagger: 0.08, clearProps: 'transform,opacity,visibility',
            }),
        });
    }
}

/** Replaces the category title: the old one leaves upwards, the new one rises. */
let headingMotion = null;

function swapHeading(heading, text, animated) {
    if (!animated) {
        heading.textContent = text;
        return;
    }
    if (headingMotion) headingMotion.kill();
    headingMotion = gsap.timeline()
        .to(heading, { yPercent: -110, duration: 0.3, ease: 'power2.in' })
        .add(() => { heading.textContent = text; })
        .fromTo(heading, { yPercent: 110 }, { yPercent: 0, duration: 0.7, ease: 'power4.out' });
}
