/**
 * A discreet burst of gold confetti, drawn on a temporary canvas.
 * Used to celebrate a reservation or an order.
 *
 *   confetti()                  from the upper middle of the screen
 *   confetti({ x: 0.5, y: 0.3 }) origin as fractions of the viewport
 */

import { env } from './utils.js';

const COLOURS = ['#C9A96E', '#F4E6C3', '#E2CB9A', '#B08E56', '#FFFFFF'];
const DURATION = 3200; // ms

export function confetti({ x = 0.5, y = 0.32, count = 90 } = {}) {
    if (env.reducedMotion) return;

    const canvas = document.createElement('canvas');
    if (!canvas.getContext) return;
    canvas.className = 'confetti';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.append(canvas);

    const context = canvas.getContext('2d');
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    const width = window.innerWidth;
    const height = window.innerHeight;
    canvas.width = Math.round(width * ratio);
    canvas.height = Math.round(height * ratio);
    context.scale(ratio, ratio);

    const random = (min, max) => min + Math.random() * (max - min);
    const amount = Math.round(count * (width < 640 ? 0.6 : 1));

    // Each piece leaves the origin in a wide upward fan
    const pieces = Array.from({ length: amount }, () => {
        const angle = random(-Math.PI * 0.92, -Math.PI * 0.08);
        const force = random(260, 760);
        return {
            x: width * x + random(-18, 18),
            y: height * y + random(-8, 8),
            vx: Math.cos(angle) * force,
            vy: Math.sin(angle) * force,
            size: random(4, 9),
            ratio: random(0.35, 0.9),          // thin strips to near squares
            round: Math.random() < 0.22,       // a few round sequins
            spin: random(-9, 9),
            angle: random(0, Math.PI * 2),
            flip: random(0, Math.PI * 2),
            flipSpeed: random(4, 11),
            colour: COLOURS[Math.floor(Math.random() * COLOURS.length)],
        };
    });

    const started = performance.now();
    let last = started;

    const draw = (now) => {
        const elapsed = now - started;
        const delta = Math.min(0.04, (now - last) / 1000);
        last = now;
        const fade = Math.min(1, Math.max(0, (DURATION - elapsed) / 900));

        context.clearRect(0, 0, width, height);

        pieces.forEach((piece) => {
            // Air resistance, gravity, a little sideways flutter
            piece.vx *= 1 - 1.6 * delta;
            piece.vy = piece.vy * (1 - 1.6 * delta) + 880 * delta;
            piece.x += piece.vx * delta + Math.sin(piece.flip) * 0.6;
            piece.y += piece.vy * delta;
            piece.angle += piece.spin * delta;
            piece.flip += piece.flipSpeed * delta;

            context.save();
            context.globalAlpha = fade;
            context.translate(piece.x, piece.y);
            context.rotate(piece.angle);
            context.scale(1, Math.cos(piece.flip)); // tumbling in 3D
            context.fillStyle = piece.colour;
            if (piece.round) {
                context.beginPath();
                context.arc(0, 0, piece.size * 0.45, 0, Math.PI * 2);
                context.fill();
            } else {
                context.fillRect(-piece.size / 2, (-piece.size * piece.ratio) / 2, piece.size, piece.size * piece.ratio);
            }
            context.restore();
        });

        if (elapsed < DURATION) requestAnimationFrame(draw);
        else canvas.remove();
    };

    requestAnimationFrame(draw);
}
