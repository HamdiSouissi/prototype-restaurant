/**
 * Notifications: short messages rising from the bottom of the screen.
 *
 *   toast({ title: 'Added to your order', text: 'Tortelli d’erbetta', action: { label: 'View cart', run } })
 */

import { gsap } from './libs.js';
import { $, env } from './utils.js';

const LIFETIME = 3400; // ms
const MAX_VISIBLE = 2;

export function toast({ title, text = '', action = null }) {
    const stack = $('[data-toasts]');
    const template = $('#tpl-toast');
    if (!stack || !template) return;

    const element = template.content.firstElementChild.cloneNode(true);
    $('strong', element).textContent = title;
    $('.toast__text span', element).textContent = text;

    const button = $('.toast__action', element);
    if (action) {
        button.textContent = action.label;
        button.addEventListener('click', () => {
            dismiss(element);
            action.run();
        });
    } else {
        button.remove();
    }

    // Older messages make room
    const visible = Array.from(stack.children).filter((child) => !child.dataset.leaving);
    visible.slice(0, Math.max(0, visible.length - MAX_VISIBLE + 1)).forEach(dismiss);
    stack.append(element);

    if (gsap && !env.reducedMotion) {
        gsap.fromTo(element, { autoAlpha: 0, y: 24, scale: 0.94 }, { autoAlpha: 1, y: 0, scale: 1, duration: 0.7, ease: 'expo.out' });
    }
    window.setTimeout(() => dismiss(element), LIFETIME);
}

function dismiss(element) {
    if (element.dataset.leaving) return;
    element.dataset.leaving = 'true';

    if (gsap && !env.reducedMotion) {
        gsap.to(element, { autoAlpha: 0, y: 12, scale: 0.96, duration: 0.35, ease: 'power2.in', onComplete: () => element.remove() });
    } else {
        element.remove();
    }
}
