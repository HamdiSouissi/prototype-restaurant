/**
 * Scroll-driven motion.
 *
 *   data-reveal            fades up when it enters the screen
 *   data-reveal="fade"     fades in (no movement)
 *   data-reveal="clip"     unveils like a curtain (photographs, map)
 *   data-reveal-group      reveals its children one after the other
 *   data-split             headline rising line by line from behind a mask
 *   data-parallax="12"     image drifting inside its frame (percentage)
 *   data-float="40"        element drifting with the scroll (pixels)
 *   data-count="240"       number counting up (data-decimals="1" optional)
 *   data-rest              endless animations inside pause while it is off screen
 *
 * Hidden states are applied here, in JavaScript, never in the style sheets:
 * if scripts are unavailable the whole page simply stays visible.
 */

import { gsap, ScrollTrigger, SplitText } from './libs.js';
import { $, $$, decimal, env, watchVisibility } from './utils.js';

const ENTER = 'top 88%'; // an element "enters" when its top passes 88% of the screen height

export function initScrollEffects() {
    initRest();
    if (!gsap || !ScrollTrigger) return;

    initMarquee();
    if (env.reducedMotion) return; // content stays in place, fully visible

    initReveals();
    initParallax();
    initCounters();
    initWordmark();

    // Line breaks are only reliable once the web fonts are in place
    const fonts = document.fonts ? document.fonts.ready : Promise.resolve();
    fonts.then(() => {
        initSplitTitles();
        ScrollTrigger.refresh();
    });
    window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

/* ── Rest ─────────────────────────────────────────────────────────────────── */

/**
 * Looping animations (rotating seals, breathing photograph, map pulses) are paused
 * while their block is out of sight: nothing is computed for what nobody sees.
 * The style sheet pauses CSS animations under `.is-resting`; SVG ones are paused here.
 */
function initRest() {
    $$('[data-rest]').forEach((block) => {
        const drawings = $$('svg', block).filter((svg) => typeof svg.pauseAnimations === 'function' && svg.querySelector('animate, animateMotion, animateTransform'));

        watchVisibility(block, (visible) => {
            block.classList.toggle('is-resting', !visible);
            drawings.forEach((svg) => (visible ? svg.unpauseAnimations() : svg.pauseAnimations()));
        });
    });
}

/* ── Reveals ──────────────────────────────────────────────────────────────── */

function initReveals() {
    const all = $$('[data-reveal]');
    const rising = all.filter((element) => element.dataset.reveal === '');
    const fading = all.filter((element) => element.dataset.reveal === 'fade');
    const curtains = all.filter((element) => element.dataset.reveal === 'clip');

    // Elements entering together are staggered as one group
    gsap.set(rising, { autoAlpha: 0, y: 36 });
    ScrollTrigger.batch(rising, {
        start: ENTER,
        once: true,
        onEnter: (batch) => enter(batch, { autoAlpha: 1, y: 0, duration: 1.25, ease: 'power3.out', clearProps: 'transform' }),
    });

    gsap.set(fading, { autoAlpha: 0 });
    ScrollTrigger.batch(fading, {
        start: ENTER,
        once: true,
        onEnter: (batch) => enter(batch, { autoAlpha: 1, duration: 1.4, ease: 'power2.out' }),
    });

    // Curtain: the frame opens from the bottom while the photograph settles
    curtains.forEach((frame) => {
        const image = $('img:not([data-parallax])', frame);
        const timeline = gsap.timeline({
            defaults: { duration: 1.5, ease: 'power4.inOut' },
            scrollTrigger: { trigger: frame, start: ENTER, once: true },
        });

        timeline.fromTo(frame,
            { clipPath: 'inset(100% 0% 0% 0%)' },
            { clipPath: 'inset(0% 0% 0% 0%)', clearProps: 'clipPath' });
        if (image) timeline.fromTo(image, { scale: 1.3 }, { scale: 1, duration: 1.9, ease: 'power3.out', clearProps: 'transform' }, 0);
    });

    // Lists: children follow each other
    $$('[data-reveal-group]').forEach((group) => {
        const children = Array.from(group.children);
        gsap.set(children, { autoAlpha: 0, y: 30 });
        ScrollTrigger.create({
            trigger: group,
            start: ENTER,
            once: true,
            onEnter: () => gsap.to(children, {
                autoAlpha: 1, y: 0, duration: 1.1, ease: 'power3.out', stagger: 0.09, clearProps: 'transform',
            }),
        });
    });
}

/**
 * Plays the entrance of a group of elements that crossed the threshold together.
 *
 * After a jump far down the page (menu link, restored position), everything above
 * "enters" at the same moment: those elements are simply shown, and only the ones
 * actually on screen are animated — otherwise the visible content would wait its
 * turn behind dozens of elements nobody can see.
 */
function enter(batch, to) {
    const passed = batch.filter((element) => element.getBoundingClientRect().bottom < 0);
    const visible = batch.filter((element) => !passed.includes(element));

    if (passed.length) gsap.set(passed, { autoAlpha: 1, y: 0, overwrite: true, clearProps: 'transform' });
    if (!visible.length) return;

    // One after the other, but the whole group never takes more than 0.6 s to start
    const spread = Math.min(0.1 * (visible.length - 1), 0.6);
    gsap.to(visible, { ...to, stagger: { amount: spread }, overwrite: true });
}

/* ── Headlines ────────────────────────────────────────────────────────────── */

function initSplitTitles() {
    if (!SplitText) return;

    $$('[data-split]').forEach((title) => {
        SplitText.create(title, {
            type: 'lines',
            mask: 'lines',
            linesClass: 'split-line',
            autoSplit: true, // splits again if the width changes before the reveal
            onSplit: (split) => gsap.from(split.lines, {
                yPercent: 112,
                duration: 1.35,
                ease: 'power4.out',
                stagger: 0.11,
                scrollTrigger: { trigger: title, start: ENTER, once: true },
                // Once revealed, the headline becomes plain text again and reflows freely
                onComplete: () => split.revert(),
            }),
        });
    });
}

/* ── Parallax ─────────────────────────────────────────────────────────────── */

function initParallax() {
    // Photographs sliding inside their frame
    $$('[data-parallax]').forEach((image) => {
        const amount = parseFloat(image.dataset.parallax) || 10;
        gsap.set(image, { scale: 1 + (amount * 2) / 100 + 0.02 });
        gsap.fromTo(image, { yPercent: -amount }, {
            yPercent: amount,
            ease: 'none',
            scrollTrigger: { trigger: image.parentElement, start: 'top bottom', end: 'bottom top', scrub: true },
        });
    });

    // Elements floating at their own pace
    $$('[data-float]').forEach((element) => {
        const distance = parseFloat(element.dataset.float) || 0;
        gsap.fromTo(element, { y: -distance }, {
            y: distance,
            ease: 'none',
            scrollTrigger: { trigger: element, start: 'top bottom', end: 'bottom top', scrub: 0.6 },
        });
    });
}

/* ── Counters ─────────────────────────────────────────────────────────────── */

function initCounters() {
    $$('[data-count]').forEach((element) => {
        const end = parseFloat(element.dataset.count);
        const decimals = parseInt(element.dataset.decimals || '0', 10);
        if (Number.isNaN(end)) return;

        const counter = { value: 0 };
        const write = () => { element.textContent = decimal(counter.value, decimals); };
        write();

        gsap.to(counter, {
            value: end,
            duration: 2.2,
            ease: 'power2.out',
            onUpdate: write,
            scrollTrigger: { trigger: element, start: 'top 92%', once: true },
        });
    });
}

/* ── Footer wordmark ──────────────────────────────────────────────────────── */

function initWordmark() {
    const wordmark = $('[data-wordmark]');
    if (!wordmark) return;

    gsap.fromTo(wordmark, { yPercent: 38, opacity: 0.15 }, {
        yPercent: 0,
        opacity: 1,
        ease: 'none',
        scrollTrigger: { trigger: wordmark, start: 'top bottom', end: 'bottom bottom', scrub: 0.6 },
    });
}

/* ── Marquee ──────────────────────────────────────────────────────────────── */

/** The band of specialities runs on its own and accelerates with the scroll. */
function initMarquee() {
    const track = $('[data-marquee-track]');
    if (!track) return;

    // The track holds two identical groups: sliding by half its width loops seamlessly
    const loop = gsap.to(track, { xPercent: -50, duration: 46, ease: 'none', repeat: -1 });
    watchVisibility(track.parentElement, (visible) => loop.paused(!visible));
    if (env.reducedMotion) {
        loop.timeScale(0.35);
        return;
    }

    // Starts far into its repeats, so the band can also run backwards when scrolling up
    loop.totalTime(loop.duration() * 500);
    let direction = 1;

    ScrollTrigger.create({
        trigger: document.documentElement,
        start: 0,
        end: 'max',
        onUpdate: (self) => {
            direction = self.direction || direction;
            const boost = Math.min(6, Math.abs(self.getVelocity()) / 350);
            loop.timeScale(direction * (1 + boost));
            // …then eases back to cruising speed
            gsap.to(loop, { timeScale: direction, duration: 1.1, ease: 'power2.out', overwrite: true });
        },
    });
}
