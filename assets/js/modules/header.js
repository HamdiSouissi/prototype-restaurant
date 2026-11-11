/**
 * Header behaviour: glass background once scrolled, hide on the way down /
 * show on the way up, active link (scroll-spy), reading progress,
 * full-screen navigation on small screens and the quick-actions dock.
 */

import { $, $$ } from './utils.js';
import { lockScroll, unlockScroll } from './scroll.js';

export function initHeader() {
    const root = document.documentElement;
    const header = $('[data-header]');
    const progress = $('[data-progress]');
    const dock = $('[data-dock]');
    if (!header) return;

    /* ── Scroll state ─────────────────────────────────────────────────────── */

    const HIDE_AFTER = 12; // px travelled downwards before the header leaves
    const SHOW_AFTER = 56; // px travelled upwards before it comes back

    let lastY = Math.max(0, window.scrollY);
    let turnY = lastY;     // where the scroll last changed direction
    let direction = 0;     // 1 = down, -1 = up
    let ticking = false;

    const onScroll = () => {
        ticking = false;
        const y = Math.max(0, window.scrollY);
        const viewport = window.innerHeight;
        const max = document.documentElement.scrollHeight - viewport;

        header.classList.toggle('is-scrolled', y > 40);

        // Hides when reading down, comes back on a deliberate scroll up. Small moves
        // (a few pixels of drift, a bouncing touchpad) change nothing.
        if (y !== lastY) {
            const heading = y > lastY ? 1 : -1;
            if (heading !== direction) {
                direction = heading;
                turnY = lastY;
            }
            if (y < viewport * 0.6) header.classList.remove('is-hidden');
            else if (direction === 1 && y - turnY > HIDE_AFTER) header.classList.add('is-hidden');
            else if (direction === -1 && turnY - y > SHOW_AFTER) header.classList.remove('is-hidden');
            lastY = y;
        }

        if (progress) progress.style.transform = `scaleX(${max > 0 ? Math.min(1, y / max) : 0})`;
        if (dock) dock.classList.toggle('is-visible', y > viewport * 0.5);
    };

    window.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(onScroll);
    }, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    onScroll();

    // Keyboard users always see the header they are tabbing through
    header.addEventListener('focusin', () => header.classList.remove('is-hidden'));

    /* ── Scroll-spy: highlights the link of the section being read ────────── */

    const links = $$('[data-nav-link]');
    const setActive = (id) => {
        links.forEach((link) => {
            const active = link.getAttribute('href') === `#${id}`;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
    };

    if ('IntersectionObserver' in window) {
        // A section is "current" while it crosses the middle of the screen
        const spy = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) setActive(entry.target.id);
            });
        }, { rootMargin: '-50% 0px -50% 0px', threshold: 0 });

        $$('[data-section][id]').forEach((section) => spy.observe(section));
    }

    /* ── Full-screen navigation (tablet & mobile) ─────────────────────────── */

    const toggle = $('[data-nav-toggle]');
    const panel = $('[data-mobile-nav]');
    if (!toggle || !panel) return;

    let open = false;
    const behind = $$('main, footer, [data-dock]'); // not reachable while the panel covers them

    const setOpen = (next) => {
        if (next === open) return;
        open = next;

        root.classList.toggle('nav-open', open);
        panel.classList.toggle('is-open', open);
        panel.toggleAttribute('inert', !open);
        panel.setAttribute('aria-hidden', String(!open));
        behind.forEach((element) => element.toggleAttribute('inert', open));
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close the menu' : 'Open the menu');

        if (open) lockScroll();
        else unlockScroll();
    };

    toggle.addEventListener('click', () => setOpen(!open));

    // Any link inside the panel — and the brand or cart button above it — closes the panel
    panel.addEventListener('click', (event) => {
        if (event.target.closest('a')) setOpen(false);
    });
    header.addEventListener('click', (event) => {
        if (open && event.target.closest('a, [data-cart-open]')) setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !open) return;
        setOpen(false);
        toggle.focus();
    });

    // Back on a wide screen: the panel has no reason to stay open
    window.matchMedia('(min-width: 1221px)').addEventListener('change', (event) => {
        if (event.matches) setOpen(false);
    });
}
