/**
 * FAQ accordion: one answer open at a time.
 * Heights are animated in CSS (grid rows); this module only switches states.
 */

import { $, $$ } from './utils.js';

export function initAccordion() {
    $$('[data-accordion]').forEach((accordion) => {
        const items = $$('.accordion__item', accordion);

        const setOpen = (item, open) => {
            item.classList.toggle('is-open', open);
            $('.accordion__trigger', item).setAttribute('aria-expanded', String(open));
            // A closed answer is skipped by keyboards and screen readers
            $('.accordion__panel', item).toggleAttribute('inert', !open);
        };

        items.forEach((item) => setOpen(item, item.classList.contains('is-open')));

        accordion.addEventListener('click', (event) => {
            const trigger = event.target.closest('.accordion__trigger');
            if (!trigger) return;

            const item = trigger.closest('.accordion__item');
            const willOpen = !item.classList.contains('is-open');
            items.forEach((other) => setOpen(other, other === item && willOpen));
        });
    });
}
