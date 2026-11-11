/**
 * Hero: entrance choreography, depth on scroll and pointer,
 * and a few embers drifting through the candle light.
 */

import { gsap, ScrollTrigger, SplitText } from './libs.js';
import { $, $$, env } from './utils.js';

/**
 * Prepares the hero (hidden states) and returns the function that plays its
 * entrance — called once the loading screen lifts.
 */
export function initHero() {
    const hero = $('.hero');
    if (!hero || !gsap) return () => {};

    const media = $('[data-hero-media]', hero);
    const title = $('[data-hero-title]', hero);
    const items = $$('[data-hero-item]', hero);
    const foot = $('[data-hero-foot]', hero);
    const seal = $('[data-hero-seal]', hero);
    const headerInner = $('.header__inner');

    initEmbers($('[data-embers]', hero), hero);

    if (env.reducedMotion) return () => {};

    /* ── Hidden states (set while the loading screen still covers the page) ── */

    gsap.set(title, { autoAlpha: 0 });
    gsap.set(items, { autoAlpha: 0, y: 34 });
    gsap.set(foot, { autoAlpha: 0, y: 24 });
    gsap.set(seal, { autoAlpha: 0, scale: 0.6, rotation: -70 });
    gsap.set(media, { scale: 1.2 });
    gsap.set(headerInner, { autoAlpha: 0, y: -24 });

    /* ── Depth: the photograph drifts slower than the page… ───────────────── */

    if (ScrollTrigger) {
        gsap.to(media, {
            yPercent: 10,
            ease: 'none',
            scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
        });
        gsap.to('.hero__content', {
            yPercent: -6,
            opacity: 0.25,
            ease: 'none',
            scrollTrigger: { trigger: hero, start: '35% top', end: 'bottom top', scrub: true },
        });
    }

    /* ── …and leans gently towards the pointer ────────────────────────────── */

    if (env.finePointer) {
        const moveX = gsap.quickTo(media, 'x', { duration: 1.4, ease: 'power3' });
        const moveY = gsap.quickTo(media, 'y', { duration: 1.4, ease: 'power3' });

        hero.addEventListener('pointermove', (event) => {
            const x = event.clientX / window.innerWidth - 0.5;
            const y = event.clientY / window.innerHeight - 0.5;
            moveX(x * -window.innerWidth * 0.022);
            moveY(y * -window.innerHeight * 0.02);
        }, { passive: true });
    }

    /* ── Entrance ─────────────────────────────────────────────────────────── */

    return function playHero() {
        const timeline = gsap.timeline({ defaults: { ease: 'power4.out' } });

        timeline.to(media, { scale: 1, duration: 2.6, ease: 'power3.out' }, 0);

        // The headline rises line by line from behind a mask
        if (SplitText) {
            const split = SplitText.create(title, { type: 'lines', mask: 'lines', linesClass: 'split-line' });
            gsap.set(title, { autoAlpha: 1 });
            timeline.from(split.lines, {
                yPercent: 112,
                rotation: 2.5,
                transformOrigin: '0% 100%',
                duration: 1.5,
                stagger: 0.12,
                // Back to plain text afterwards, so the headline reflows freely on resize
                onComplete: () => split.revert(),
            }, 0.25);
        } else {
            timeline.to(title, { autoAlpha: 1, duration: 1.2 }, 0.25);
        }

        timeline
            .to(items, { autoAlpha: 1, y: 0, duration: 1.3, stagger: 0.12 }, 0.6)
            .to(headerInner, { autoAlpha: 1, y: 0, duration: 1.2, clearProps: 'all' }, 0.7)
            .to(foot, { autoAlpha: 1, y: 0, duration: 1.2 }, 1.05)
            .to(seal, { autoAlpha: 1, scale: 1, rotation: 0, duration: 1.6, ease: 'expo.out', clearProps: 'transform' }, 1.1);

        return timeline;
    };
}

/**
 * Embers: a handful of warm sparks rising slowly, drawn on a canvas.
 * Paused whenever the hero is off screen or the tab is hidden.
 */
function initEmbers(canvas, hero) {
    if (!canvas || env.reducedMotion || !canvas.getContext) return;

    const context = canvas.getContext('2d');
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    let width = 0;
    let height = 0;
    let embers = [];
    let frame = 0;
    let visible = true;
    let last = performance.now();

    const random = (min, max) => min + Math.random() * (max - min);

    const spawn = (anywhere = false) => ({
        x: random(0, width),
        y: anywhere ? random(0, height) : height + 12,
        radius: random(0.6, 2.1),
        speed: random(10, 34),          // px per second, upwards
        sway: random(8, 26),            // horizontal wander
        phase: random(0, Math.PI * 2),
        alpha: random(0.18, 0.7),
        twinkle: random(0.6, 1.8),
    });

    const resize = () => {
        width = hero.clientWidth;
        height = hero.clientHeight;
        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        embers = Array.from({ length: Math.round(Math.min(46, Math.max(16, width / 30))) }, () => spawn(true));
    };

    const draw = (now) => {
        const delta = Math.min(0.05, (now - last) / 1000);
        last = now;
        context.clearRect(0, 0, width, height);

        embers.forEach((ember, index) => {
            ember.y -= ember.speed * delta;
            ember.phase += delta * ember.twinkle;
            const x = ember.x + Math.sin(ember.phase) * ember.sway;
            // Brightest in the lower half, fading as it rises
            const life = Math.min(1, Math.max(0, ember.y / height));
            const alpha = ember.alpha * (0.35 + 0.65 * life) * (0.7 + 0.3 * Math.sin(ember.phase * 3));

            const glow = context.createRadialGradient(x, ember.y, 0, x, ember.y, ember.radius * 5);
            glow.addColorStop(0, `rgba(244, 222, 170, ${alpha})`);
            glow.addColorStop(0.3, `rgba(201, 169, 110, ${alpha * 0.45})`);
            glow.addColorStop(1, 'rgba(201, 169, 110, 0)');
            context.fillStyle = glow;
            context.beginPath();
            context.arc(x, ember.y, ember.radius * 5, 0, Math.PI * 2);
            context.fill();

            if (ember.y < -12) embers[index] = spawn();
        });

        frame = requestAnimationFrame(draw);
    };

    const start = () => {
        if (frame || !visible || document.hidden) return;
        last = performance.now();
        frame = requestAnimationFrame(draw);
    };
    const stop = () => {
        cancelAnimationFrame(frame);
        frame = 0;
    };

    resize();
    window.addEventListener('resize', resize, { passive: true });
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            if (visible) start();
            else stop();
        }).observe(hero);
    } else {
        start();
    }
}
