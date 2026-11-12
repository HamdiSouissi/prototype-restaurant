/**
 * Small shared helpers: DOM queries, environment flags, formatting.
 */

/** First element matching a selector. */
export const $ = (selector, scope = document) => scope.querySelector(selector);

/** All elements matching a selector, as a real array. */
export const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

/** What the visitor's device and preferences allow. */
export const env = {
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    finePointer: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
};

/** Data rendered by PHP for the scripts (dishes, order settings, opening hours). */
export const appData = (() => {
    try {
        return JSON.parse($('#app-data').textContent);
    } catch (error) {
        return { dishes: {}, order: {}, hours: {} };
    }
})();

export const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

/** 7 → "07" */
export const pad = (number) => String(number).padStart(2, '0');

/* Numbers are written the Italian way: decimal comma, euro sign after the amount. */
const euro = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });

/** Amount for totals: 12.5 → "12,50 €" */
export const money = (amount) => euro.format(amount);

/** Price as printed on the menu: 22 → "22 €", 4.5 → "4,50 €" */
export const price = (amount) => (Number.isInteger(amount) ? `${amount}\u00A0€` : money(amount));

/** Decimal number: (4.9, 1) → "4,9" */
export const decimal = (value, digits = 0) => value.toFixed(digits).replace('.', ',');

/** "venerdì" → "Venerdì" */
export const capitalise = (text) => text.charAt(0).toUpperCase() + text.slice(1);

/** Date → "2026-11-14", the format of a date field */
export const toISO = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

/** "2026-11-14" → Date at local midnight (null if unreadable) */
export const parseDate = (value) => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    if (!match) return null;
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    return Number.isNaN(date.getTime()) ? null : date;
};

/** "19:30" → 1170 (minutes since midnight) */
export const toMinutes = (time) => {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
};

export const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim());

/** Sets the text of every element matching a selector. */
export const setText = (selector, text, scope = document) => {
    $$(selector, scope).forEach((element) => { element.textContent = text; });
};

/** Restarts a CSS animation driven by a class. */
export const replay = (element, className) => {
    element.classList.remove(className);
    void element.offsetWidth; // forces a reflow so the animation starts again
    element.classList.add(className);
};

/**
 * Tells when an element is on screen (true) or out of sight (false).
 * Used to stop endless animations nobody is looking at.
 */
export const watchVisibility = (element, callback, margin = '120px') => {
    if (!element || !('IntersectionObserver' in window)) return;
    new IntersectionObserver(([entry]) => callback(entry.isIntersecting), { rootMargin: margin }).observe(element);
};

/**
 * Runs one feature in isolation: if it throws, the rest of the page keeps working.
 */
export const safely = (label, task) => {
    try {
        return task();
    } catch (error) {
        console.error(`[${label}]`, error);
        return undefined;
    }
};
