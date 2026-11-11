/**
 * Reservation form — front-end simulation.
 * Checks the fields, adapts the time slots to the opening hours of the chosen
 * day, then shows the confirmation dialog. Nothing is stored or sent.
 */

import { $, $$, appData, isEmail, replay, setText, toMinutes } from './utils.js';
import { openOverlay } from './overlay.js';
import { confetti } from './confetti.js';

const MIN_GUESTS = 1;
const MAX_GUESTS = 8;
const BOOKING_WINDOW = 180;  // days ahead
const LAST_SEATING = 30;     // minutes before the kitchen closes
const NOTICE = 60;           // minutes needed before a table can be ready

const pad = (number) => String(number).padStart(2, '0');
const toISO = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

/** "2026-11-14" → Date at local midnight (null if unreadable). */
function parseDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) return null;
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    return Number.isNaN(date.getTime()) ? null : date;
}

function today() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

export function initReservation() {
    const form = $('form[data-reservation]');
    if (!form) return;

    const fields = {
        name: form.elements.name,
        phone: form.elements.phone,
        email: form.elements.email,
        date: form.elements.date,
        time: form.elements.time,
    };
    const guests = $('[data-guests]', form);
    const guestsLabel = $('[data-guests-label]', form);
    const submit = $('[data-submit]', form);
    const visual = $('[data-seating-visual]');
    const hours = appData.hours || {};

    /* ── Date limits ──────────────────────────────────────────────────────── */

    const first = today();
    const last = new Date(first);
    last.setDate(last.getDate() + BOOKING_WINDOW);
    fields.date.min = toISO(first);
    fields.date.max = toISO(last);

    /* ── Time slots ───────────────────────────────────────────────────────── */

    /** Is this arrival time possible on that date? */
    const slotAvailable = (slot, date) => {
        const minutes = toMinutes(slot);
        const services = hours[date.getDay()] || [];
        const served = services.some(([open, close]) => minutes >= toMinutes(open) && minutes <= toMinutes(close) - LAST_SEATING);
        if (!served) return false;

        // Today: only the tables that can still be prepared
        if (date.getTime() === today().getTime()) {
            const now = new Date();
            return minutes >= now.getHours() * 60 + now.getMinutes() + NOTICE;
        }
        return true;
    };

    /** Enables the slots matching the chosen day; returns how many remain. */
    const updateSlots = () => {
        const date = parseDate(fields.date.value);
        let available = 0;

        $$('optgroup', fields.time).forEach((group) => {
            let open = 0;
            $$('option', group).forEach((option) => {
                option.disabled = Boolean(date) && !slotAvailable(option.value, date);
                if (!option.disabled) open += 1;
            });
            group.disabled = open === 0;
            group.hidden = open === 0;
            available += open;
        });

        const selected = fields.time.selectedOptions[0];
        if (selected && selected.disabled && selected.value) fields.time.value = '';
        return available;
    };

    /* ── Validation ───────────────────────────────────────────────────────── */

    const rules = {
        name(value) {
            if (!value.trim()) return 'Please enter your name.';
            if (value.trim().length < 2) return 'This name looks a little short.';
            return '';
        },
        phone(value) {
            const digits = value.replace(/\D/g, '');
            if (!value.trim()) return 'Please enter a phone number.';
            if (!/^\+?[\d\s().-]+$/.test(value.trim()) || digits.length < 7 || digits.length > 15) return 'This phone number doesn’t look right.';
            return '';
        },
        email(value) {
            if (!value.trim()) return 'Please enter your email address.';
            if (!isEmail(value)) return 'This email address doesn’t look right.';
            return '';
        },
        date(value) {
            const date = parseDate(value);
            if (!value) return 'Please choose a date.';
            if (!date || date < first) return 'Please choose today or a later date.';
            if (date > last) return 'We take reservations up to six months ahead.';
            if (!(hours[date.getDay()] || []).length) {
                return `We are closed on ${date.toLocaleDateString('en-GB', { weekday: 'long' })}s — please choose another day.`;
            }
            if (!$$('option:not([disabled])', fields.time).some((option) => option.value)) {
                return 'No more tables on this day — please choose another one.';
            }
            return '';
        },
        time(value) {
            return value ? '' : 'Please choose a time.';
        },
    };

    /** Shows or clears the message of one field; returns true when the field is valid. */
    const validate = (name) => {
        const input = fields[name];
        const field = input.closest('[data-field]');
        const error = $('[data-error]', field);
        const message = rules[name](input.value);

        if (!error.id) error.id = `${input.id}-error`;
        error.textContent = message;
        field.classList.toggle('is-invalid', Boolean(message));
        input.setAttribute('aria-invalid', String(Boolean(message)));
        if (message) input.setAttribute('aria-describedby', error.id);
        else input.removeAttribute('aria-describedby');

        return !message;
    };

    const clear = () => {
        $$('[data-field]', form).forEach((field) => field.classList.remove('is-invalid'));
        Object.values(fields).forEach((input) => {
            input.removeAttribute('aria-invalid');
            input.removeAttribute('aria-describedby');
        });
    };

    Object.entries(fields).forEach(([name, input]) => {
        const isInvalid = () => input.closest('[data-field]').classList.contains('is-invalid');

        // Checked when leaving the field; while typing, only to lift an error already shown
        input.addEventListener('blur', () => { if (input.value || isInvalid()) validate(name); });
        input.addEventListener('input', () => { if (isInvalid()) validate(name); });
    });

    fields.date.addEventListener('change', () => {
        updateSlots();
        validate('date');
        if (fields.time.closest('[data-field]').classList.contains('is-invalid')) validate('time');
    });
    fields.time.addEventListener('change', () => validate('time'));

    /* ── Guests ───────────────────────────────────────────────────────────── */

    const setGuests = (count) => {
        const value = Math.max(MIN_GUESTS, Math.min(MAX_GUESTS, count));
        guests.value = value;
        guestsLabel.textContent = `${value} ${value === 1 ? 'guest' : 'guests'}`;
        $('[data-guests-step="-1"]', form).disabled = value <= MIN_GUESTS;
        $('[data-guests-step="1"]', form).disabled = value >= MAX_GUESTS;
    };

    form.addEventListener('click', (event) => {
        const step = event.target.closest('[data-guests-step]');
        if (step) setGuests(Number(guests.value) + Number(step.dataset.guestsStep));
    });
    setGuests(Number(guests.value) || 2);

    /* ── Seating: the photograph follows the choice ───────────────────────── */

    form.addEventListener('change', (event) => {
        if (event.target.name === 'seating' && visual) visual.dataset.seatingVisual = event.target.value;
    });

    /* ── Submission ───────────────────────────────────────────────────────── */

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (submit.classList.contains('is-loading')) return;

        updateSlots();
        const invalid = Object.keys(fields).filter((name) => !validate(name));
        if (invalid.length) {
            replay(form, 'is-shaking');
            fields[invalid[0]].focus();
            return;
        }

        const data = new FormData(form);
        const date = parseDate(data.get('date'));
        const count = Number(data.get('guests'));

        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');

        // A short pause stands in for the exchange with a server
        window.setTimeout(() => {
            submit.classList.remove('is-loading');
            submit.removeAttribute('aria-busy');

            setText('[data-res-name]', `, ${String(data.get('name')).trim().split(/\s+/)[0]}`);
            setText('[data-res="date"]', date.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }));
            setText('[data-res="time"]', data.get('time'));
            setText('[data-res="guests"]', `${count} ${count === 1 ? 'guest' : 'guests'}`);
            setText('[data-res="seating"]', data.get('seating'));

            openOverlay('reservation');
            confetti();

            // The form is ready for another request
            form.reset();
            clear();
            setGuests(2);
            updateSlots();
            if (visual) visual.dataset.seatingVisual = 'Indoor';
        }, 1200);
    });
}
