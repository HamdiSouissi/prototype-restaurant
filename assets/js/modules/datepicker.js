/**
 * Calendar of a date field.
 *
 * A native date input writes its format hint and its calendar in the language
 * of the browser. This one always speaks Italian, like the rest of the page,
 * and greys out the days on which no table can be booked.
 *
 * The native input stays in the form, hidden: it keeps the value, so the code
 * that reads it does not change. Without JavaScript it is simply used as it is.
 */

import { $, $$, capitalise, parseDate, replay, toISO } from './utils.js';
import { scrollToTarget } from './scroll.js';

const LOCALE = 'it-IT';
const format = (options) => new Intl.DateTimeFormat(LOCALE, options);

const MONTH = format({ month: 'long', year: 'numeric' });                                  // "novembre 2026"
const FULL = format({ weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });  // "venerdì 13 novembre 2026"
const INITIAL = format({ weekday: 'narrow' });                                             // "V"

/** What the field shows, from the most complete wording to the shortest: the first that fits is used. */
const WORDINGS = [
    format({ weekday: 'long', day: 'numeric', month: 'long' }),   // "Venerdì 13 novembre"
    format({ weekday: 'short', day: 'numeric', month: 'long' }),  // "Ven 13 novembre"
    format({ weekday: 'short', day: 'numeric', month: 'short' }), // "Ven 13 nov"
];

const WEEKS = 6; // rows of the grid: the calendar keeps its height from month to month

const addDays = (date, count) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + count);
const firstOfMonth = (date, shift = 0) => new Date(date.getFullYear(), date.getMonth() + shift, 1);
const daysInMonth = (date) => new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
const mondayIndex = (date) => (date.getDay() + 6) % 7; // weeks start on Monday
const sameDay = (a, b) => Boolean(a && b) && a.getTime() === b.getTime();
const sameMonth = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();

function today() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

/**
 * Replaces a native date input with a button and its calendar (markup in index.php).
 *
 * @param {HTMLInputElement} input              its `min` and `max` bound the calendar
 * @param {(date: Date) => boolean} unavailable days that cannot be chosen (closing days…)
 */
export function initDatePicker(input, unavailable = () => false) {
    const control = input.closest('[data-date-field]');
    const toggle = control && $('[data-date-toggle]', control);
    const calendar = control && $('[data-calendar]', control);
    if (!toggle || !calendar) return;

    const field = control.closest('[data-field]') || control;
    const text = $('[data-date-text]', toggle);
    const spoken = $('[data-date-spoken]', toggle);
    const title = $('[data-calendar-title]', calendar);
    const week = $('[data-calendar-week]', calendar);
    const grid = $('[data-calendar-days]', calendar);
    const [previous, next] = $$('[data-calendar-step]', calendar);
    const placeholder = text.textContent;

    let range = { min: today(), max: today() };
    let view = firstOfMonth(today()); // month on display
    let focusDay = today();           // day the keyboard is on
    let isOpen = false;
    let hiding = 0;

    /* ── Field ────────────────────────────────────────────────────────────── */

    /** Writes the chosen date in the field: in full for screen readers, as complete as it fits on screen. */
    const write = () => {
        const date = parseDate(input.value);
        toggle.classList.toggle('is-empty', !date);
        spoken.textContent = date ? FULL.format(date) : placeholder;
        text.textContent = placeholder;
        if (!date) return;

        for (const wording of WORDINGS) {
            text.textContent = capitalise(wording.format(date));
            if (text.scrollWidth <= text.clientWidth) break;
        }
    };

    /* ── Days ─────────────────────────────────────────────────────────────── */

    const readRange = () => {
        const min = parseDate(input.min) || today();
        return { min, max: parseDate(input.max) || addDays(min, 365) };
    };

    const within = (date) => (date < range.min ? range.min : date > range.max ? range.max : date);
    const selectable = (date) => date >= range.min && date <= range.max && !unavailable(date);

    /** First day that can be chosen, walking from a date by steps of `step` days. */
    const seek = (from, step) => {
        for (let date = from; date >= range.min && date <= range.max; date = addDays(date, step)) {
            if (!unavailable(date)) return date;
        }
        return null;
    };

    const dayButton = (date) => $(`[data-date="${toISO(date)}"]`, grid);

    /** Draws the month on display. */
    const render = () => {
        const now = today();
        const chosen = parseDate(input.value);
        const offset = mondayIndex(view);
        const length = daysInMonth(view);

        title.textContent = capitalise(MONTH.format(view));
        previous.disabled = view <= firstOfMonth(range.min);
        next.disabled = view >= firstOfMonth(range.max);

        grid.textContent = '';
        for (let cell = 0; cell < WEEKS * 7; cell += 1) {
            const number = cell - offset + 1;
            if (number < 1 || number > length) {
                grid.append(document.createElement('span')); // blank cell before the 1st and after the last day
                continue;
            }

            const date = new Date(view.getFullYear(), view.getMonth(), number);
            const day = document.createElement('button');
            day.type = 'button';
            day.className = 'calendar__day';
            day.textContent = String(number);
            day.dataset.date = toISO(date);
            day.tabIndex = -1;
            day.disabled = !selectable(date);
            day.setAttribute('aria-label', FULL.format(date));
            if (sameDay(date, now)) day.setAttribute('aria-current', 'date');
            if (sameDay(date, chosen)) day.setAttribute('aria-pressed', 'true');
            grid.append(day);
        }

        // One day only is reached with Tab; the arrow keys move from there
        const entry = sameMonth(focusDay, view) ? dayButton(focusDay) : null;
        const reachable = entry && !entry.disabled ? entry : $('.calendar__day:not(:disabled)', grid);
        if (reachable) reachable.tabIndex = 0;
    };

    /** Shows the month of a day and puts the keyboard on it. */
    const focusOn = (date) => {
        focusDay = date;
        if (!sameMonth(date, view)) {
            view = firstOfMonth(date);
            replay(grid, 'is-changing');
        }
        render();
        const day = dayButton(date);
        if (day) day.focus({ preventScroll: true });
    };

    /* ── Opening and closing ──────────────────────────────────────────────── */

    /** Downwards, or upwards when the section (which clips its content) ends too soon. */
    const place = () => {
        const frame = (control.closest('section') || document.documentElement).getBoundingClientRect();
        const room = frame.bottom - control.getBoundingClientRect().bottom;
        calendar.classList.toggle('calendar--above', room < calendar.offsetHeight + 24);
    };

    /** Scrolls just enough to show the whole calendar when the screen cuts it. */
    const reveal = () => {
        // Where the calendar sits once its entrance is over (it starts slightly shifted and scaled down)
        const top = control.getBoundingClientRect().top + control.clientTop + calendar.offsetTop;
        const bottom = top + calendar.offsetHeight;

        // What the screen leaves free between the header and, on phones, the bar of shortcuts
        const header = $('.header');
        const dock = $('[data-dock]');
        const dockBox = dock ? dock.getBoundingClientRect() : null;
        const upper = (header ? header.offsetHeight : 0) + 12;
        const lower = (dockBox && dockBox.height && dockBox.top < window.innerHeight ? dockBox.top : window.innerHeight) - 12;

        let shift = 0;
        if (bottom > lower) shift = bottom - lower;
        if (top - shift < upper) shift = top - upper; // the top of the calendar comes first
        if (Math.abs(shift) > 4) scrollToTarget(window.scrollY + shift, { duration: 0.8 });
    };

    const open = () => {
        if (isOpen) return;
        isOpen = true;
        window.clearTimeout(hiding);

        range = readRange();
        const chosen = parseDate(input.value);
        focusDay = (chosen && selectable(chosen) ? chosen : null) || seek(range.min, 1) || range.min;
        view = firstOfMonth(focusDay);
        render();

        calendar.hidden = false;
        place();
        toggle.setAttribute('aria-expanded', 'true');

        // Next frame: the entrance starts from the closed state
        requestAnimationFrame(() => {
            control.classList.add('is-open');
            ($('.calendar__day[tabindex="0"]', grid) || calendar).focus({ preventScroll: true });
            reveal();
        });
    };

    const close = (returnFocus = true) => {
        if (!isOpen) return;
        isOpen = false;
        control.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) toggle.focus({ preventScroll: true });
        hiding = window.setTimeout(() => { calendar.hidden = true; }, 300); // once the exit transition is over
    };

    const choose = (date) => {
        input.value = toISO(date);
        // The form reacts as it does to the native control (time slots, validation)
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    };

    /* ── Events ───────────────────────────────────────────────────────────── */

    toggle.addEventListener('click', () => (isOpen ? close() : open()));
    toggle.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowDown' || isOpen) return;
        event.preventDefault();
        open();
    });

    calendar.addEventListener('click', (event) => {
        const step = event.target.closest('[data-calendar-step]');
        const day = event.target.closest('.calendar__day');

        if (day) {
            choose(parseDate(day.dataset.date));
        } else if (step) {
            view = firstOfMonth(view, Number(step.dataset.calendarStep));
            replay(grid, 'is-changing');
            render();
            // At the end of the booking period the arrow is disabled: the focus moves on to the days
            if (step.disabled) ($('.calendar__day[tabindex="0"]', grid) || calendar).focus({ preventScroll: true });
        }
    });

    grid.addEventListener('keydown', (event) => {
        const current = parseDate(event.target.dataset.date);
        if (!current) return;

        const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[event.key];
        let target = null;

        if (step) {
            target = seek(addDays(current, step), step);
        } else if (event.key === 'Home') {
            target = seek(within(addDays(current, -mondayIndex(current))), 1);
        } else if (event.key === 'End') {
            target = seek(within(addDays(current, 6 - mondayIndex(current))), -1);
        } else if (event.key === 'PageUp' || event.key === 'PageDown') {
            // Same day of the previous or next month, or the nearest one that can be chosen
            const direction = event.key === 'PageUp' ? -1 : 1;
            const month = firstOfMonth(current, direction);
            const same = within(new Date(month.getFullYear(), month.getMonth(), Math.min(current.getDate(), daysInMonth(month))));
            target = seek(same, direction) || seek(same, -direction);
        } else {
            return;
        }

        event.preventDefault();
        if (target) focusOn(target);
    });

    control.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !isOpen) return;
        event.stopPropagation();
        close();
    });

    // A press elsewhere, or the focus moving on to another field, closes the calendar
    document.addEventListener('pointerdown', (event) => {
        if (isOpen && !field.contains(event.target)) close(false);
    });
    field.addEventListener('focusout', (event) => {
        if (isOpen && event.relatedTarget && !field.contains(event.relatedTarget)) close(false);
    });

    // The field follows the value, whoever changes it: a choice, the form being reset,
    // the browser restoring the page with what had been filled in
    input.addEventListener('change', write);
    if (input.form) input.form.addEventListener('reset', () => window.setTimeout(write)); // fields are emptied after the event
    window.addEventListener('pageshow', write);
    window.addEventListener('resize', () => {
        write();
        if (isOpen) place();
    });

    /* ── The button takes the place of the native control ─────────────────── */

    const monday = new Date(2024, 0, 1);
    for (let index = 0; index < 7; index += 1) {
        const initial = document.createElement('span');
        initial.textContent = INITIAL.format(addDays(monday, index)).toUpperCase();
        week.append(initial);
    }

    const label = input.labels && input.labels[0];
    if (label) label.htmlFor = toggle.id;

    input.hidden = true;
    input.dataset.face = toggle.id; // the control shown in its place: validation points to it
    toggle.hidden = false;
    control.classList.add('has-calendar');
    write();
}
