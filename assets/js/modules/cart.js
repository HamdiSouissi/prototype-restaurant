/**
 * Online order: cart state, quantity controls, cart drawer and checkout.
 *
 * This is a front-end simulation: nothing is sent anywhere. The cart lives in
 * memory (and in sessionStorage, so a page refresh does not empty it).
 *
 * Every element showing a piece of the cart is refreshed by render(), so the
 * menu cards, the order board, the header badge and the drawer always agree.
 */

import { gsap } from './libs.js';
import { $, $$, appData, env, money, price, replay, setText } from './utils.js';
import { openOverlay, closeOverlay } from './overlay.js';
import { toast } from './toast.js';
import { confetti } from './confetti.js';

const STORAGE_KEY = 'aurea-cart';
const MAX_PER_DISH = 20;

const dishes = appData.dishes;
const settings = appData.order;

/** dish id → quantity (insertion order = order of the lines in the drawer) */
const cart = new Map();
let mode = 'delivery'; // or "takeaway"

/* ── State ────────────────────────────────────────────────────────────────── */

function totals() {
    let count = 0;
    let subtotal = 0;
    cart.forEach((quantity, id) => {
        count += quantity;
        subtotal += quantity * dishes[id].price;
    });
    const fee = mode === 'delivery' && count > 0 && subtotal < settings.freeFrom ? settings.deliveryFee : 0;
    return { count, subtotal, fee, total: subtotal + fee };
}

function setQuantity(id, quantity) {
    if (!dishes[id]) return;
    const next = Math.max(0, Math.min(MAX_PER_DISH, quantity));
    if (next === 0) cart.delete(id);
    else cart.set(id, next);
    save();
    render(id);
}

const change = (id, step) => setQuantity(id, (cart.get(id) || 0) + step);

function save() {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ mode, items: Array.from(cart) }));
    } catch (error) { /* private browsing: the cart simply is not kept */ }
}

function restore() {
    try {
        const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY));
        if (!saved) return;
        if (saved.mode === 'takeaway') mode = 'takeaway';
        saved.items.forEach(([id, quantity]) => {
            if (dishes[id] && Number.isInteger(quantity) && quantity > 0) cart.set(id, Math.min(MAX_PER_DISH, quantity));
        });
    } catch (error) { /* nothing saved, or unreadable */ }
}

/* ── Rendering ────────────────────────────────────────────────────────────── */

/** Refreshes every view of the cart. "changed" is the dish that just moved, if any. */
function render(changed = null) {
    const { count, subtotal, fee, total } = totals();
    const drawer = $('[data-overlay="cart"]');

    renderLines(drawer);

    // Badges (header, dock, drawer title)
    $$('[data-cart-count]').forEach((badge) => {
        const before = badge.textContent;
        badge.textContent = count;
        badge.toggleAttribute('data-empty', count === 0);
        if (changed && before !== String(count)) replay(badge, 'is-bumped');
    });

    // Quantity controls (order board, drawer)
    $$('[data-qty]').forEach((control) => {
        const quantity = cart.get(control.dataset.qty) || 0;
        control.dataset.value = quantity;
        $('output', control).textContent = quantity;
        if (control.dataset.qty === changed) replay(control, 'is-bumped');
    });

    // Markers on the dishes (menu cards, order rows)
    $$('[data-dish]').forEach((dish) => {
        const quantity = cart.get(dish.dataset.dish) || 0;
        const marker = $('[data-dish-qty]', dish);
        dish.classList.toggle('is-in-cart', quantity > 0);
        if (marker) {
            marker.textContent = quantity;
            marker.hidden = quantity === 0;
        }
    });

    // Summary under the order board
    setText('[data-cart-summary]', count ? `${count} ${count === 1 ? 'item' : 'items'} in your cart` : 'Your cart is empty');
    setText('[data-cart-total]', money(subtotal));

    // Drawer: empty state, free-delivery gauge, totals
    drawer.classList.toggle('is-empty', count === 0);
    $('[data-cart-empty]', drawer).hidden = count > 0;

    const missing = settings.freeFrom - subtotal;
    const gauge = $('[data-free-text]', drawer);
    if (mode === 'takeaway') say(gauge, 'Collect at ', appData.address, ' — no fee, no minimum.');
    else if (missing > 0) say(gauge, 'Add ', money(missing), ' more and delivery is on the house.');
    else say(gauge, '', 'Free delivery unlocked', ' — grazie!');
    $('[data-free-bar]', drawer).style.transform = `scaleX(${mode === 'takeaway' ? 1 : Math.min(1, subtotal / settings.freeFrom)})`;

    setText('[data-cart-subtotal]', money(subtotal));
    setText('[data-fee-label]', mode === 'delivery' ? 'Delivery fee' : 'Take-away');
    setText('[data-cart-fee]', fee ? money(fee) : 'Free');
    setText('[data-cart-grand]', money(total));
}

/** Writes a sentence with its key part emphasised: before <strong>key</strong> after. */
function say(element, before, key, after) {
    const strong = document.createElement('strong');
    strong.textContent = key;
    element.replaceChildren(before, strong, after);
}

/** Keeps the lines of the drawer in step with the cart (adds, updates, removes). */
function renderLines(drawer) {
    const list = $('[data-cart-list]', drawer);
    const template = $('#tpl-cart-item');
    const animated = Boolean(gsap) && !env.reducedMotion && drawer.classList.contains('is-open');
    const lines = new Map($$('.cart-item:not([data-leaving])', list).map((line) => [line.dataset.id, line]));

    cart.forEach((quantity, id) => {
        const dish = dishes[id];
        let line = lines.get(id);

        if (!line) {
            line = template.content.firstElementChild.cloneNode(true);
            line.dataset.id = id;
            $('.cart-item__img', line).src = dish.image;
            $('.cart-item__name', line).textContent = dish.name;
            $('.cart-item__unit', line).textContent = `${dish.category} · ${price(dish.price)}`;
            $('.qty', line).dataset.qty = id;
            $('[data-dec]', line).setAttribute('aria-label', `Remove one ${dish.name}`);
            $('[data-inc]', line).setAttribute('aria-label', `Add one ${dish.name}`);
            $('[data-remove]', line).setAttribute('aria-label', `Remove ${dish.name} from your cart`);
            list.append(line);
            if (animated) gsap.from(line, { height: 0, paddingTop: 0, paddingBottom: 0, autoAlpha: 0, duration: 0.5, ease: 'power3.out', clearProps: 'all' });
        }

        $('.cart-item__total', line).textContent = money(quantity * dish.price);
        lines.delete(id);
    });

    // Whatever is left is no longer in the cart
    lines.forEach((line) => {
        if (!animated) {
            line.remove();
            return;
        }
        line.dataset.leaving = 'true';
        gsap.to(line, {
            height: 0, paddingTop: 0, paddingBottom: 0, autoAlpha: 0, x: 40, duration: 0.45, ease: 'power3.inOut', onComplete: () => line.remove(),
        });
    });
}

/** Delivery or take-away: updates the switches and every sentence that depends on it. */
function renderMode() {
    $$('[data-order-mode]').forEach((radio) => { radio.checked = radio.value === mode; });

    const delivery = mode === 'delivery';
    const [from, to] = delivery ? settings.deliveryEta : settings.takeawayEta;
    const copy = delivery
        ? {
            eta: `${from}–${to} min`,
            etaLabel: 'Estimated delivery',
            fee: price(settings.deliveryFee),
            feeLabel: `Delivery fee · free over ${price(settings.freeFrom)}`,
            zone: 'Within 5 km',
            zoneLabel: 'Parma centre and Oltretorrente',
        }
        : {
            eta: `${from}–${to} min`,
            etaLabel: 'Ready to collect',
            fee: 'Free',
            feeLabel: 'No fee, no minimum order',
            zone: 'At the restaurant',
            zoneLabel: appData.address,
        };

    Object.entries(copy).forEach(([key, text]) => setText(`[data-mode-text="${key}"]`, text));
}

/* ── Feedback ─────────────────────────────────────────────────────────────── */

/**
 * Where the cart button currently is: the header button on large screens, the
 * dock on small ones. If the header is tucked away, it is called back first.
 */
function cartButtonBox() {
    const header = $('[data-header]');
    const buttons = $$('[data-cart-target]').filter((button) => button.getBoundingClientRect().width > 0);

    const onScreen = buttons.find((button) => {
        const box = button.getBoundingClientRect();
        return box.top >= 0 && box.bottom <= window.innerHeight;
    });
    if (onScreen) return onScreen.getBoundingClientRect();

    const inHeader = buttons.find((button) => header && header.contains(button));
    if (!inHeader) return null;

    const box = inHeader.getBoundingClientRect();
    const offset = -header.getBoundingClientRect().top; // how far the header has slid out
    header.classList.remove('is-hidden');
    return { left: box.left, top: box.top + offset, width: box.width, height: box.height };
}

/** A thumbnail of the dish flies to the cart button. */
function flyToCart(source) {
    if (!source || !gsap || env.reducedMotion) return;

    const to = cartButtonBox();
    if (!to) return;

    const from = source.getBoundingClientRect();
    const size = 64;
    const ghost = document.createElement('img');
    ghost.className = 'fly';
    ghost.alt = '';
    ghost.src = source.currentSrc || source.src;
    ghost.width = size;
    ghost.height = size;
    document.body.append(ghost);

    gsap.timeline({ onComplete: () => ghost.remove() })
        .fromTo(ghost,
            { x: from.left + from.width / 2 - size / 2, y: from.top + from.height / 2 - size / 2, scale: 0.3, autoAlpha: 0 },
            { scale: 1, autoAlpha: 1, duration: 0.3, ease: 'back.out(2)' })
        // Horizontal and vertical moves use different easings: the path bends into an arc
        .to(ghost, { x: to.left + to.width / 2 - size / 2, duration: 0.8, ease: 'power1.inOut' }, 0.15)
        .to(ghost, { y: to.top + to.height / 2 - size / 2, duration: 0.8, ease: 'power3.in' }, 0.15)
        .to(ghost, { scale: 0.2, autoAlpha: 0.4, duration: 0.45, ease: 'power2.in' }, 0.5);
}

/** The photograph that represents a dish near the control that was used. */
const photoNear = (control) => $('img', control.closest('[data-dish]') || control.closest('section') || document);

/* ── Checkout (simulated) ─────────────────────────────────────────────────── */

function checkout(button) {
    const { count, total } = totals();
    if (!count || button.classList.contains('is-loading')) return;

    button.classList.add('is-loading');
    button.setAttribute('aria-busy', 'true');

    // A short pause stands in for the exchange with a server
    window.setTimeout(() => {
        button.classList.remove('is-loading');
        button.removeAttribute('aria-busy');

        const delivery = mode === 'delivery';
        const [from, to] = delivery ? settings.deliveryEta : settings.takeawayEta;

        setText('[data-order="ref"]', `№ ${String(Math.floor(1000 + Math.random() * 9000))}`);
        setText('[data-order="text"]', delivery
            ? `The kitchen is already at work. Your order will be at your door in about ${from}–${to} minutes.`
            : `The kitchen is already at work. Your order will be ready to collect at ${appData.address} in ${from}–${to} minutes.`);
        setText('[data-order="eta"]', delivery ? `Delivery in ${from}–${to} min` : `Ready in ${from}–${to} min`);
        setText('[data-order="items"]', `${count} ${count === 1 ? 'item' : 'items'}`);
        setText('[data-order="total"]', `${money(total)} · ${delivery ? 'pay on delivery' : 'pay at collection'}`);

        cart.clear();
        save();
        closeOverlay('cart');
        render();
        openOverlay('order');
        confetti();
    }, 1100);
}

/* ── Start ────────────────────────────────────────────────────────────────── */

export function initCart() {
    if (!$('[data-overlay="cart"]') || !settings) return;

    restore();
    renderMode();
    render();

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;

        // "Add to order" buttons (menu cards, chef's special)
        const add = target.closest('[data-add]');
        if (add) {
            const dish = dishes[add.dataset.add];
            if (!dish) return;
            change(dish.id, 1);
            flyToCart(photoNear(add));
            toast({
                title: 'Added to your order',
                text: `${dish.name} · ${price(dish.price)}`,
                action: { label: 'View cart', run: () => openOverlay('cart') },
            });
            return;
        }

        // Quantity steppers (order board, drawer)
        const step = target.closest('[data-inc], [data-dec]');
        if (step) {
            const control = step.closest('[data-qty]');
            if (!control) return;
            const adding = step.hasAttribute('data-inc');
            change(control.dataset.qty, adding ? 1 : -1);
            if (adding && !control.closest('[data-overlay]')) flyToCart(photoNear(control));
            return;
        }

        const remove = target.closest('[data-remove]');
        if (remove) {
            setQuantity(remove.closest('.cart-item').dataset.id, 0);
            return;
        }

        const pay = target.closest('[data-checkout]');
        if (pay) {
            checkout(pay);
            return;
        }

        if (target.closest('[data-cart-open]')) openOverlay('cart');
    });

    document.addEventListener('change', (event) => {
        if (!event.target.matches('[data-order-mode]')) return;
        mode = event.target.value === 'takeaway' ? 'takeaway' : 'delivery';
        save();
        renderMode();
        render();
    });
}
