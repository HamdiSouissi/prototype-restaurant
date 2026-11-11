/**
 * Newsletter form — front-end simulation: checks the address and thanks
 * the visitor. Nothing is stored or sent.
 */

import { $, $$, isEmail } from './utils.js';

export function initNewsletter() {
    $$('[data-newsletter]').forEach((form) => {
        const input = $('input[type="email"]', form);
        const message = $('[data-newsletter-msg]', form);

        const say = (state, text) => {
            form.classList.remove('is-success', 'is-error');
            if (state) form.classList.add(state);
            message.textContent = text;
            input.setAttribute('aria-invalid', String(state === 'is-error'));
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!isEmail(input.value)) {
                say('is-error', input.value.trim() ? 'This email address doesn’t look right.' : 'Please enter your email address.');
                input.focus();
                return;
            }
            say('is-success', 'Grazie! The next letter will find its way to you.');
            form.reset();
        });

        // The error leaves as soon as the visitor starts correcting
        input.addEventListener('input', () => {
            if (form.classList.contains('is-error')) say('', '');
        });
    });
}
