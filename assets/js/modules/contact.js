/**
 * Contact section: "open now" badge, today's line in the timetable
 * and the stylised map (route drawing, slight depth under the pointer).
 */

import { gsap } from './libs.js';
import { $, $$, appData, env, toMinutes } from './utils.js';

/** English names, only used to read the weekday returned by Intl for the restaurant's time zone. */
const INTL_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
/** Names shown to the visitor (0 = Sunday). */
const DAY_NAMES = ['domenica', 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato'];
const TIME_ZONE = 'Europe/Rome'; // the restaurant's clock, wherever the visitor is

export function initContact() {
    initOpeningStatus();
    initMap();
}

/* ── Opening hours ────────────────────────────────────────────────────────── */

/** Current weekday (0 = Sunday) and minutes since midnight in Parma. */
function restaurantTime() {
    try {
        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: TIME_ZONE, weekday: 'long', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        }).formatToParts(new Date());
        const read = (type) => parts.find((part) => part.type === type).value;
        return { day: INTL_DAYS.indexOf(read('weekday')), minutes: Number(read('hour')) * 60 + Number(read('minute')) };
    } catch (error) {
        const now = new Date(); // very old browsers: the visitor's own clock
        return { day: now.getDay(), minutes: now.getHours() * 60 + now.getMinutes() };
    }
}

function initOpeningStatus() {
    const badge = $('[data-open-status]');
    const hours = appData.hours;
    if (!hours) return;

    const refresh = () => {
        const { day, minutes } = restaurantTime();
        const services = hours[day] || [];
        const current = services.find(([open, close]) => minutes >= toMinutes(open) && minutes < toMinutes(close));

        $$('.hours__row[data-days]').forEach((row) => {
            row.classList.toggle('is-today', row.dataset.days.split(',').map(Number).includes(day));
        });
        if (!badge) return;

        let text;
        if (current) {
            text = `Aperto ora · fino alle ${current[1]}`;
        } else {
            // Next service: later today, or the first one of the coming days
            const later = services.find(([open]) => toMinutes(open) > minutes);
            if (later) {
                text = `Apre oggi alle ${later[0]}`;
            } else {
                text = 'Chiuso';
                for (let ahead = 1; ahead <= 7; ahead += 1) {
                    const next = (day + ahead) % 7;
                    if ((hours[next] || []).length) {
                        text = `Apre ${ahead === 1 ? 'domani' : DAY_NAMES[next]} alle ${hours[next][0][0]}`;
                        break;
                    }
                }
            }
        }

        $('[data-open-label]', badge).textContent = text;
        badge.classList.toggle('is-open', Boolean(current));
        badge.hidden = false;
    };

    refresh();
    window.setInterval(refresh, 60 * 1000);
}

/* ── Map ──────────────────────────────────────────────────────────────────── */

function initMap() {
    const map = $('[data-map]');
    if (!map) return;

    // The walking route draws itself (CSS) the first time the map is seen
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(([entry]) => {
            if (!entry.isIntersecting) return;
            map.classList.add('is-inview');
            observer.disconnect();
        }, { threshold: 0.35 });
        observer.observe(map);
    } else {
        map.classList.add('is-inview');
    }

    // The drawing shifts slightly under the pointer
    const canvas = $('[data-map-canvas]', map);
    if (!canvas || !gsap || !env.finePointer || env.reducedMotion) return;

    const moveX = gsap.quickTo(canvas, 'x', { duration: 1.2, ease: 'power3' });
    const moveY = gsap.quickTo(canvas, 'y', { duration: 1.2, ease: 'power3' });

    map.addEventListener('pointermove', (event) => {
        const box = map.getBoundingClientRect();
        moveX(((event.clientX - box.left) / box.width - 0.5) * -box.width * 0.045);
        moveY(((event.clientY - box.top) / box.height - 0.5) * -box.height * 0.045);
    }, { passive: true });

    map.addEventListener('pointerleave', () => {
        moveX(0);
        moveY(0);
    });
}
