/**
 * Loading screen.
 * Waits for what matters to the first impression (fonts + hero photograph),
 * lets the brand animation breathe, then lifts like a curtain.
 * Resolves when the page behind may start its entrance.
 */

import { gsap } from './libs.js';
import { $, env } from './utils.js';
import { lockScroll, unlockScroll } from './scroll.js';

const MIN_DISPLAY = 1900; // ms — time for the monogram and name to draw
const MAX_WAIT = 4500;    // ms — never hold the visitor longer than this

export function initLoader() {
    const root = document.documentElement;
    const loader = $('[data-loader]');

    // The inline safety timer in <head> is no longer needed: scripts are running
    window.clearTimeout(window.__loaderSafety);

    const release = () => {
        root.classList.remove('is-loading');
        root.classList.add('is-loaded');
    };

    if (!loader || !gsap || root.classList.contains('is-released')) {
        root.classList.add('is-released');
        release();
        return Promise.resolve();
    }

    lockScroll();

    const bar = $('[data-loader-bar]', loader);
    const count = $('[data-loader-count]', loader);
    const progress = { value: 0 };
    const paint = () => {
        count.textContent = Math.round(progress.value);
        bar.style.transform = `scaleX(${progress.value / 100})`;
    };

    const quick = env.reducedMotion;
    const delay = (ms) => new Promise((done) => window.setTimeout(done, ms));
    const heroImage = $('[data-hero-img]');
    const imageReady = !heroImage || heroImage.complete
        ? Promise.resolve()
        : new Promise((done) => {
            heroImage.addEventListener('load', done, { once: true });
            heroImage.addEventListener('error', done, { once: true });
        });
    const fontsReady = document.fonts ? document.fonts.ready : Promise.resolve();
    const assetsReady = Promise.race([Promise.all([fontsReady, imageReady]), delay(MAX_WAIT)]);

    // The counter climbs on its own while assets arrive…
    const climb = gsap.to(progress, { value: 84, duration: quick ? 0.2 : 1.8, ease: 'power2.out', onUpdate: paint });

    return new Promise((resolve) => {
        Promise.all([assetsReady, delay(quick ? 150 : MIN_DISPLAY)]).then(() => {
            climb.kill();
            // …then completes and the curtain lifts
            gsap.timeline({ onComplete: () => loader.remove() })
                .to(progress, { value: 100, duration: 0.45, ease: 'power2.out', onUpdate: paint })
                .to([$('.loader__inner', loader), $('.loader__foot', loader)], {
                    autoAlpha: 0, y: -26, duration: 0.6, ease: 'power3.inOut', stagger: 0.06,
                }, '+=0.12')
                .add(() => {
                    unlockScroll();
                    release();
                    resolve();
                }, '-=0.2')
                .to(loader, { yPercent: -100, duration: quick ? 0.01 : 1.15, ease: 'power4.inOut' }, '-=0.2');
        });
    });
}
