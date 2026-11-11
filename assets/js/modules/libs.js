/**
 * Third-party libraries.
 * They are loaded as classic deferred scripts (see index.php) and exposed as
 * globals; this module is the single place where the application reads them,
 * so every other module keeps a clean, explicit import.
 */

export const gsap = window.gsap || null;
export const ScrollTrigger = window.ScrollTrigger || null;
export const SplitText = window.SplitText || null;
export const Lenis = window.Lenis || null;
export const Swiper = window.Swiper || null;

if (gsap) {
    gsap.registerPlugin(...[ScrollTrigger, SplitText].filter(Boolean));
    gsap.defaults({ ease: 'power3.out', duration: 1 });
}
