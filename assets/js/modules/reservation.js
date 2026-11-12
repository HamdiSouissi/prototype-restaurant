/**
 * Reservation form — front-end simulation.
 * Checks the fields, adapts the calendar and the time slots to the opening
 * hours, then shows the confirmation dialog. Nothing is stored or sent.
 */

import { $, $$, appData, capitalise, isEmail, parseDate, replay, safely, setText, toISO, toMinutes } from './utils.js';
import { openOverlay } from './overlay.js';
import { confetti } from './confetti.js';
import { initDatePicker } from './datepicker.js';

const MIN_GUESTS = 1;
const MAX_GUESTS = 8;
const BOOKING_WINDOW = 180;  // days ahead
const LAST_SEATING = 30;     // minutes before the kitchen closes
const NOTICE = 60;           // minutes needed before a table can be ready

const LOCALE = 'it-IT';      // dates are written in Italian

const guestsText = (count) => `${count} ${count === 1 ? 'ospite' : 'ospiti'}`;

/** The control the visitor uses for a field: the calendar button stands in for the native date input. */
const face = (input) => document.getElementById(input.dataset.face || '') || input;

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

    // The calendar greys out the days without any bookable time: closing days, today once it is too late.
    // Should it fail to start, the native date input simply stays in place.
    const slots = $$('option', fields.time).map((option) => option.value).filter(Boolean);
    safely('calendar', () => initDatePicker(fields.date, (date) => !slots.some((slot) => slotAvailable(slot, date))));

    /* ── Validation ───────────────────────────────────────────────────────── */

    const rules = {
        name(value) {
            if (!value.trim()) return 'Inserisci il tuo nome.';
            if (value.trim().length < 2) return 'Questo nome sembra un po’ corto.';
            return '';
        },
        phone(value) {
            const digits = value.replace(/\D/g, '');
            if (!value.trim()) return 'Inserisci un numero di telefono.';
            if (!/^\+?[\d\s().-]+$/.test(value.trim()) || digits.length < 7 || digits.length > 15) return 'Questo numero non sembra corretto.';
            return '';
        },
        email(value) {
            if (!value.trim()) return 'Inserisci il tuo indirizzo email.';
            if (!isEmail(value)) return 'Questo indirizzo email non sembra corretto.';
            return '';
        },
        date(value) {
            const date = parseDate(value);
            if (!value) return 'Scegli una data.';
            if (!date || date < first) return 'Scegli la data di oggi o una successiva.';
            if (date > last) return 'Accettiamo prenotazioni fino a sei mesi di anticipo.';
            if (!(hours[date.getDay()] || []).length) {
                // "Il lunedì siamo chiusi" — Sunday is the only feminine day of the week
                const weekday = date.toLocaleDateString(LOCALE, { weekday: 'long' });
                return `${date.getDay() === 0 ? 'La' : 'Il'} ${weekday} siamo chiusi: scegli un altro giorno.`;
            }
            if (!$$('option:not([disabled])', fields.time).some((option) => option.value)) {
                return 'Nessun tavolo disponibile in questo giorno: scegline un altro.';
            }
            return '';
        },
        time(value) {
            return value ? '' : 'Scegli un orario.';
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
        face(input).setAttribute('aria-invalid', String(Boolean(message)));
        if (message) face(input).setAttribute('aria-describedby', error.id);
        else face(input).removeAttribute('aria-describedby');

        return !message;
    };

    const clear = () => {
        $$('[data-field]', form).forEach((field) => field.classList.remove('is-invalid'));
        Object.values(fields).forEach((input) => {
            face(input).removeAttribute('aria-invalid');
            face(input).removeAttribute('aria-describedby');
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
        guestsLabel.textContent = guestsText(value);
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
            face(fields[invalid[0]]).focus();
            return;
        }

        const data = new FormData(form);
        const date = parseDate(data.get('date'));
        const count = Number(data.get('guests'));
        // The seating is shown with its label ("Sala", "Terrazza"), not with its internal value
        const seat = $('input[name="seating"]:checked', form);
        const seating = seat ? seat.closest('label').textContent.trim() : '';

        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');

        // A short pause stands in for the exchange with a server
        window.setTimeout(() => {
            submit.classList.remove('is-loading');
            submit.removeAttribute('aria-busy');

            setText('[data-res-name]', `, ${String(data.get('name')).trim().split(/\s+/)[0]}`);
            setText('[data-res="date"]', capitalise(date.toLocaleDateString(LOCALE, { weekday: 'long', day: 'numeric', month: 'long' })));
            setText('[data-res="time"]', data.get('time'));
            setText('[data-res="guests"]', guestsText(count));
            setText('[data-res="seating"]', seating);

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
