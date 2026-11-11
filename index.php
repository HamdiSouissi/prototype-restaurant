<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 *  AUREA — Ristorante · Parma
 *  Single-page restaurant website · native PHP / HTML5 / CSS3 / vanilla JS
 * ─────────────────────────────────────────────────────────────────────────────
 *  No framework, no database, no back-office: every interactive feature
 *  (reservation, online order, cart, newsletter) is simulated in the browser.
 *
 *  This file is organised in four parts:
 *    1. CONFIGURATION  → restaurant identity, contact details, opening hours
 *    2. CONTENT        → menu, gallery, reviews, FAQ (plain PHP arrays)
 *    3. HELPERS        → tiny rendering functions (escaping, icons, images)
 *    4. TEMPLATE       → the page itself, one commented block per section
 *
 *  To adapt the site to another restaurant, parts 1 and 2 are all you need.
 */

declare(strict_types=1);
date_default_timezone_set('Europe/Rome');


/* ═════════════════════════════════════════════════════════════════════════════
   1. CONFIGURATION
   ═════════════════════════════════════════════════════════════════════════════ */

$site = [
    'name'        => 'Aurea',
    'descriptor'  => 'Ristorante',
    'city'        => 'Parma',
    'since'       => 1987,
    'title'       => 'Aurea — Ristorante in Parma · Fine Italian dining',
    'description' => 'A candlelit dining room beneath seventeenth-century vaults in the heart of Parma. Handmade pasta, Culatello di Zibello, a 240-label cellar. Reserve your table online.',
    'street'      => 'Borgo dell’Aurora 12',
    'zip'         => '43121',
    'country'     => 'Italy',
    'phone'       => '+39 0521 00 19 87',
    'email'       => 'ciao@aurea-parma.example',
    'maps_url'    => 'https://www.google.com/maps/dir/?api=1&destination=Piazza+Duomo%2C+Parma',
    'vat'         => 'P.IVA 00000000000',
    'chef'        => 'Lorenzo Bellini',
    'rating'      => ['score' => 4.9, 'count' => 1280],
    'social'      => [
        'instagram' => ['label' => 'Instagram', 'url' => 'https://www.instagram.com/'],
        'facebook'  => ['label' => 'Facebook',  'url' => 'https://www.facebook.com/'],
        'tiktok'    => ['label' => 'TikTok',    'url' => 'https://www.tiktok.com/'],
    ],
];

/** Main navigation — label => anchor */
$nav = [
    'Home'        => '#home',
    'Menu'        => '#menu',
    'Gallery'     => '#gallery',
    'Reservation' => '#reservation',
    'Delivery'    => '#delivery',
    'About'       => '#about',
    'Contact'     => '#contact',
];

/**
 * Opening hours — key = ISO weekday as returned by JS Date#getDay() (0 = Sunday).
 * Each day holds a list of [opening, closing] services. An empty list = closed.
 * Used for the timetable, the "open now" badge and the bookable time slots.
 */
$hours = [
    1 => [],
    2 => [['19:00', '23:00']],
    3 => [['19:00', '23:00']],
    4 => [['19:00', '23:00']],
    5 => [['12:30', '14:30'], ['19:00', '23:30']],
    6 => [['12:30', '14:30'], ['19:00', '23:30']],
    0 => [['12:30', '15:00']],
];

/** Bookable arrival times, grouped by service */
$slots = [
    'Lunch'  => ['12:30', '13:00', '13:30', '14:00'],
    'Dinner' => ['19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00'],
];

/** Online-order settings (amounts in euros, times in minutes) */
$order = [
    'deliveryFee'  => 4.5,
    'freeFrom'     => 80,
    'deliveryEta'  => [35, 45],
    'takeawayEta'  => [15, 20],
];


/* ═════════════════════════════════════════════════════════════════════════════
   2. CONTENT
   ═════════════════════════════════════════════════════════════════════════════ */

/**
 * Menu — one entry per category. Each dish: id (also its image name in
 * /assets/img/menu), name, description, price and an optional tag.
 */
$menu = [
    'starters' => [
        'label' => 'Starters',
        'it'    => 'Antipasti',
        'items' => [
            ['id' => 'culatello',  'name' => 'Culatello di Zibello',          'price' => 24, 'tag' => 'Signature',  'desc' => 'Aged thirty months in the fog of the Bassa, served with warm torta fritta.'],
            ['id' => 'parmigiano', 'name' => 'Parmigiano, three ages',        'price' => 18, 'tag' => 'Vegetarian', 'desc' => '24, 36 and 60 months, acacia honey, walnuts and traditional balsamic.'],
            ['id' => 'battuta',    'name' => 'Battuta di Fassona',            'price' => 22, 'tag' => '',           'desc' => 'Hand-cut Piedmontese beef, cured yolk, Parmigiano shavings, olive oil.'],
            ['id' => 'sformato',   'name' => 'Sformato di Parmigiano',        'price' => 19, 'tag' => 'Vegetarian', 'desc' => 'Warm Parmigiano flan, saffron fondue, shaved black truffle.'],
            ['id' => 'prosciutto', 'name' => 'Prosciutto di Parma',           'price' => 20, 'tag' => '',           'desc' => 'Thirty months, sliced by hand to order, with late-summer figs.'],
            ['id' => 'uovo',       'name' => 'Uovo morbido',                  'price' => 17, 'tag' => '',           'desc' => 'Slow-cooked farm egg, green asparagus, Parmigiano foam, crisp pancetta.'],
        ],
    ],
    'pasta' => [
        'label' => 'Pasta',
        'it'    => 'Primi',
        'items' => [
            ['id' => 'tortelli-erbetta', 'name' => 'Tortelli d’erbetta',     'price' => 22, 'tag' => 'Signature',  'desc' => 'Ricotta and chard parcels, melted mountain butter, 36-month Parmigiano.'],
            ['id' => 'anolini',          'name' => 'Anolini in brodo',       'price' => 21, 'tag' => '',           'desc' => 'Filled with slow-braised beef and Parmigiano, in a clear capon broth.'],
            ['id' => 'tortelli-zucca',   'name' => 'Tortelli di zucca',      'price' => 21, 'tag' => 'Vegetarian', 'desc' => 'Pumpkin, amaretti and mostarda, brown butter, crisp sage.'],
            ['id' => 'tagliatelle-ragu', 'name' => 'Tagliatelle al ragù',    'price' => 20, 'tag' => '',           'desc' => 'Egg pasta rolled by hand, eight-hour ragù of beef and pork.'],
        ],
    ],
    'pizza' => [
        'label' => 'Pizza',
        'it'    => 'Dal forno a legna',
        'items' => [
            ['id' => 'pizza-margherita', 'name' => 'Margherita',             'price' => 13, 'tag' => 'Vegetarian', 'desc' => 'San Marzano tomato, fior di latte, basil. Forty-eight-hour dough.'],
            ['id' => 'pizza-bufala',     'name' => 'Bufala',                 'price' => 16, 'tag' => 'Vegetarian', 'desc' => 'Tomato, buffalo mozzarella added out of the oven, basil, Ligurian oil.'],
            ['id' => 'pizza-crudo',      'name' => 'Parma',                  'price' => 18, 'tag' => 'Signature',  'desc' => 'Fior di latte, Prosciutto di Parma aged twenty-four months, basil.'],
            ['id' => 'pizza-pomodoro',   'name' => 'Tre pomodori',           'price' => 14, 'tag' => 'Vegan',      'desc' => 'Datterino, San Marzano and sun-dried tomato, oregano, garlic oil.'],
            ['id' => 'pizza-bresaola',   'name' => 'Bresaola e rucola',      'price' => 17, 'tag' => '',           'desc' => 'Bresaola, wild rocket, Parmigiano shavings, lemon oil.'],
        ],
    ],
    'mains' => [
        'label' => 'Main Courses',
        'it'    => 'Secondi',
        'items' => [
            ['id' => 'rosa-di-parma', 'name' => 'Rosa di Parma',             'price' => 34, 'tag' => 'Signature',  'desc' => 'Beef fillet rolled with Prosciutto and Parmigiano, Lambrusco jus.'],
            ['id' => 'filetto',       'name' => 'Filetto di manzo',          'price' => 36, 'tag' => '',           'desc' => 'Grilled beef fillet, green asparagus, potato fondant, roasting jus.'],
            ['id' => 'guancia',       'name' => 'Guancia brasata',           'price' => 29, 'tag' => '',           'desc' => 'Veal cheek braised for twelve hours, soft stone-ground polenta.'],
            ['id' => 'tagliata',      'name' => 'Tagliata di Fassona',       'price' => 32, 'tag' => '',           'desc' => 'Seared and sliced rare, bitter leaves, rosemary jus poured at the table.'],
            ['id' => 'salmerino',     'name' => 'Salmerino alla brace',      'price' => 30, 'tag' => '',           'desc' => 'Ember-grilled Alpine char, purple potato, wild herbs.'],
        ],
    ],
    'desserts' => [
        'label' => 'Desserts',
        'it'    => 'Dolci',
        'items' => [
            ['id' => 'tiramisu',    'name' => 'Tiramisù della casa',         'price' => 11, 'tag' => 'Signature',  'desc' => 'Mascarpone cream, espresso-soaked savoiardi, bitter cocoa.'],
            ['id' => 'panna-cotta', 'name' => 'Panna cotta',                 'price' => 10, 'tag' => '',           'desc' => 'Vanilla cream, sour-cherry compote, toasted almond crumble.'],
            ['id' => 'semifreddo',  'name' => 'Semifreddo al torroncino',    'price' => 11, 'tag' => '',           'desc' => 'Nougat parfait, raspberry coulis, Bronte pistachio.'],
            ['id' => 'affogato',    'name' => 'Affogato al caffè',           'price' => 9,  'tag' => '',           'desc' => 'Fior di latte gelato, a ristretto poured at the table.'],
        ],
    ],
    'drinks' => [
        'label' => 'Drinks',
        'it'    => 'Dalla cantina',
        'items' => [
            ['id' => 'lambrusco',    'name' => 'Lambrusco di Sorbara',       'price' => 8,  'tag' => '',           'desc' => 'Dry, sparkling and ruby red. By the glass.'],
            ['id' => 'malvasia',     'name' => 'Malvasia di Candia',         'price' => 9,  'tag' => '',           'desc' => 'Aromatic white from the Colli di Parma. By the glass.'],
            ['id' => 'negroni',      'name' => 'Negroni',                    'price' => 12, 'tag' => 'Classic',    'desc' => 'Gin, bitter, red vermouth, orange peel.'],
            ['id' => 'boulevardier', 'name' => 'Boulevardier',               'price' => 13, 'tag' => '',           'desc' => 'Bourbon, bitter and sweet vermouth, served up.'],
            ['id' => 'spritz',       'name' => 'Spritz della casa',          'price' => 10, 'tag' => '',           'desc' => 'Bitter-orange aperitivo, Malvasia frizzante, soda.'],
            ['id' => 'espresso',     'name' => 'Espresso',                   'price' => 3,  'tag' => '',           'desc' => 'House blend, roasted a few streets away.'],
        ],
    ],
];

/** Gallery — image id in /assets/img, caption, layout (wide = 3:2, tall = 4:5) */
$gallery = [
    ['img' => 'gallery/room',       'label' => 'Sala delle Volte',              'shape' => 'wide'],
    ['img' => 'gallery/pass',       'label' => 'The last gesture, at the pass', 'shape' => 'tall'],
    ['img' => 'gallery/long-table', 'label' => 'The long table',                'shape' => 'tall'],
    ['img' => 'gallery/table',      'label' => 'Mise en place',                 'shape' => 'wide'],
    ['img' => 'gallery/cellar',     'label' => 'The cellar · 240 labels',       'shape' => 'tall'],
    ['img' => 'gallery/sfoglia',    'label' => 'Sfoglia, every morning',        'shape' => 'tall'],
    ['img' => 'gallery/duomo',      'label' => 'Piazza Duomo, two minutes away','shape' => 'wide'],
    ['img' => 'gallery/kitchen',    'label' => 'The kitchen, 7 pm',             'shape' => 'tall'],
    ['img' => 'gallery/bar',        'label' => 'The bar',                       'shape' => 'wide'],
    ['img' => 'gallery/wine',       'label' => 'Malvasia hour',                 'shape' => 'tall'],
];

/** Key figures shown under the story ("{years}" is computed from $site['since']) */
$stats = [
    ['value' => (int) date('Y') - $site['since'], 'suffix' => '',   'label' => 'Years of family service'],
    ['value' => 240,                              'suffix' => '+',  'label' => 'Labels resting in the cellar'],
    ['value' => 36,                               'suffix' => '',   'label' => 'Months of ageing for our Parmigiano'],
    ['value' => 14,                               'suffix' => '',   'label' => 'Producers, all within thirty kilometres'],
];

/** Guest reviews — "dish" refers to a menu id (thumbnail shown on the card) */
$reviews = [
    ['name' => 'Giulia M.',       'from' => 'Milan',  'occasion' => 'Dinner for two',     'dish' => 'tortelli-erbetta', 'text' => 'The tortelli alone are worth the trip to Parma. And a service that reads the table without ever interrupting it.'],
    ['name' => 'Thomas & Claire', 'from' => 'Lyon',   'occasion' => 'Anniversary',        'dish' => 'tiramisu',         'text' => 'We were looked after from the first glass of Malvasia to the last spoonful of tiramisù. Flawless, and never stiff.'],
    ['name' => 'Hiroshi T.',      'from' => 'Tokyo',  'occasion' => 'Tasting menu',       'dish' => 'culatello',        'text' => 'A dining room lit by candles, a cellar full of surprises and a culatello I am still thinking about weeks later.'],
    ['name' => 'Elena R.',        'from' => 'Parma',  'occasion' => 'Sunday lunch',       'dish' => 'anolini',          'text' => 'Rare to find a kitchen this precise that still cooks with so much heart. The anolini tasted like Sundays at my grandmother’s.'],
    ['name' => 'Marco B.',        'from' => 'Parma',  'occasion' => 'Take away',          'dish' => 'pizza-crudo',      'text' => 'Ordered for a family lunch: everything arrived beautifully packed and still warm. Restaurant quality, at our own table.'],
    ['name' => 'Sophie L.',       'from' => 'London', 'occasion' => 'Business dinner',    'dish' => 'rosa-di-parma',    'text' => 'From the welcome to the wine pairing, every detail felt considered. Easily one of the finest tables in Emilia.'],
];

/** Frequently asked questions */
$faq = [
    ['q' => 'Do I need a reservation?',                           'a' => 'We recommend booking, especially from Thursday to Sunday. A few seats at the bar are always kept for guests who simply walk in. Tables are held for fifteen minutes.'],
    ['q' => 'Is there a dress code?',                             'a' => 'Smart casual. Come as you would to dinner at a friend’s beautiful home — jackets are welcome, never required.'],
    ['q' => 'Can you accommodate allergies and dietary needs?',   'a' => 'Of course. Vegetarian and gluten-free alternatives are available for most dishes. Please mention any allergy when you reserve so the kitchen can prepare with care.'],
    ['q' => 'Are children welcome?',                              'a' => 'Always. We have high chairs, a short menu of small portions and plenty of patience. Lunch service is the calmest moment for families.'],
    ['q' => 'Where can I park?',                                  'a' => 'The restaurant sits inside the limited-traffic zone. The nearest car park is five minutes away on foot, and we are happy to book a taxi for your journey home.'],
    ['q' => 'Do you host private dinners and events?',            'a' => 'Our vaulted cellar room seats up to eighteen guests around a single table, with bespoke menus and pairings. Call us and we will compose the evening with you.'],
    ['q' => 'How do delivery and take-away work?',                'a' => 'Choose your dishes online, then pick delivery within five kilometres or collection at the restaurant. Orders are cooked to order and leave the kitchen in insulated boxes.'],
    ['q' => 'What is your cancellation policy?',                  'a' => 'Plans change. You can cancel or modify a reservation free of charge up to twenty-four hours before your table.'],
];


/* ═════════════════════════════════════════════════════════════════════════════
   3. HELPERS
   ═════════════════════════════════════════════════════════════════════════════ */

/** Escapes a value for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Returns an asset URL with a cache-busting version (file modification time). */
function asset(string $path): string
{
    $file = __DIR__ . '/' . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Renders an icon from the SVG sprite (/assets/icons/sprite.svg). */
function icon(string $name, string $class = ''): string
{
    static $sprite = null;
    $sprite = $sprite ?? asset('assets/icons/sprite.svg');
    return '<svg class="icon' . ($class !== '' ? ' ' . $class : '') . '" aria-hidden="true" focusable="false">'
         . '<use href="' . $sprite . '#' . $name . '"></use></svg>';
}

/** Reads the image manifest once per request (/assets/img/manifest.json). */
function image_manifest(): array
{
    static $manifest = null;
    if ($manifest === null) {
        $json     = @file_get_contents(__DIR__ . '/assets/img/manifest.json');
        $manifest = $json ? (json_decode($json, true) ?: []) : [];
    }
    return $manifest;
}

/**
 * Renders a responsive <img> from the image manifest (/assets/img/manifest.json).
 * The manifest stores, for every image id, its available widths, aspect ratio
 * and a dominant colour used as placeholder while the file is loading.
 *
 * @param array $o  Options: class, eager (bool), attrs (raw extra attributes)
 */
function img(string $id, string $alt, string $sizes, array $o = []): string
{
    $entry = image_manifest()[$id] ?? null;
    if ($entry === null) {
        return '';
    }

    $widths = $entry['w'];
    $max    = max($widths);
    $srcset = [];
    foreach ($widths as $w) {
        $srcset[] = 'assets/img/' . $id . '-' . $w . '.webp ' . $w . 'w';
    }
    $eager = !empty($o['eager']);

    return '<img'
         . ' class="' . e($o['class'] ?? '') . '"'
         . ' src="assets/img/' . $id . '-' . min($widths) . '.webp"'
         . ' srcset="' . implode(', ', $srcset) . '"'
         . ' sizes="' . e($sizes) . '"'
         . ' width="' . $max . '" height="' . (int) round($max / $entry['r']) . '"'
         . ' alt="' . e($alt) . '"'
         . ($eager ? ' fetchpriority="high"' : ' loading="lazy"')
         . ' decoding="async"'
         . ' style="background-color:' . e($entry['c']) . '"'
         . (isset($o['attrs']) ? ' ' . $o['attrs'] : '')
         . '>';
}

/** URL of one rendition of an image; without a width, the largest available. */
function img_url(string $id, int $width = 0): string
{
    if ($width === 0) {
        $width = max(image_manifest()[$id]['w'] ?? [0]);
    }
    return 'assets/img/' . $id . '-' . $width . '.webp';
}

/** The monogram used in the header, loader and footer. */
function brand_mark(string $class = 'brand__mark'): string
{
    return '<svg class="' . $class . '" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.1"'
         . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . '<circle cx="24" cy="24" r="22.4" pathLength="1"/>'
         . '<path d="M14.6 33.6 24 12.4l9.4 21.2" pathLength="1"/>'
         . '<path class="mark-gem" d="M24 22.4l2.6 4-2.6 4-2.6-4z" fill="currentColor" stroke="none"/>'
         . '</svg>';
}

/** A circular line of text that slowly rotates (decorative seal). */
function seal(string $id, string $text): string
{
    // The wrapper (a plain box) is what rotates: browsers animate it without repainting the text
    return '<span class="seal__ring" aria-hidden="true"><svg viewBox="0 0 120 120" focusable="false">'
         . '<defs><path id="' . $id . '" d="M60 60m-47 0a47 47 0 1 1 94 0a47 47 0 1 1-94 0"/></defs>'
         . '<text><textPath href="#' . $id . '" textLength="292" lengthAdjust="spacing">' . e($text) . '</textPath></text>'
         . '</svg></span>';
}

/** Formats a price for display on the menu (no decimals when round). */
function price(float $amount): string
{
    return '€' . (floor($amount) == $amount ? number_format($amount, 0) : number_format($amount, 2));
}

/** Initials of a guest name, for the avatar medallion. */
function initials(string $name): string
{
    preg_match_all('/\b\p{L}/u', $name, $m);
    $letters = implode('', array_slice($m[0], 0, 2));
    return function_exists('mb_strtoupper') ? mb_strtoupper($letters) : strtoupper($letters);
}

/**
 * Groups consecutive days that share the same services into timetable rows,
 * e.g. Tuesday → Thursday · 19:00 – 23:00.
 */
function opening_rows(array $hours): array
{
    $names = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'];
    $rows  = [];
    foreach ($names as $day => $name) {
        $services = $hours[$day] ?? [];
        $last     = count($rows) - 1;
        if ($last >= 0 && $rows[$last]['services'] === $services) {
            $rows[$last]['to']     = $name;
            $rows[$last]['days'][] = $day;
            continue;
        }
        $rows[] = ['from' => $name, 'to' => $name, 'days' => [$day], 'services' => $services];
    }
    return $rows;
}

/* ── Derived values ─────────────────────────────────────────────────────────── */

$phoneHref = preg_replace('/[^+\d]/', '', $site['phone']);
/** The number as displayed: non-breaking spaces, so it is never cut in two at the end of a line */
$phoneText = str_replace(' ', '&nbsp;', e($site['phone']));

/** Absolute URL of the site (canonical link, social sharing image). */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
         . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
$year      = (int) date('Y');
$today     = date('Y-m-d');

/** Flat list of dishes, shared with the front-end (cart, order board). */
$dishes = [];
foreach ($menu as $slug => $category) {
    foreach ($category['items'] as $item) {
        $dishes[$item['id']] = [
            'id'       => $item['id'],
            'name'     => $item['name'],
            'price'    => $item['price'],
            'category' => $category['label'],
            'image'    => img_url('menu/' . $item['id'], 480),
        ];
    }
}

/** Data handed to the JavaScript modules (see /assets/js/main.js). */
$appData = [
    'dishes'  => $dishes,
    'order'   => $order,
    'hours'   => $hours,
    'name'    => $site['name'],
    'address' => $site['street'] . ', ' . $site['city'],
];

/** Structured data for search engines (schema.org/Restaurant). */
$schema = [
    '@context'           => 'https://schema.org',
    '@type'              => 'Restaurant',
    'name'               => $site['name'] . ' ' . $site['descriptor'],
    'description'        => $site['description'],
    'servesCuisine'      => ['Italian', 'Emilian'],
    'priceRange'         => '€€€',
    'telephone'          => $site['phone'],
    'email'              => $site['email'],
    'acceptsReservations'=> true,
    'address'            => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $site['street'],
        'postalCode'      => $site['zip'],
        'addressLocality' => $site['city'],
        'addressCountry'  => 'IT',
    ],
    'aggregateRating'    => [
        '@type'       => 'AggregateRating',
        'ratingValue' => $site['rating']['score'],
        'reviewCount' => $site['rating']['count'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($site['title']) ?></title>
    <meta name="description" content="<?= e($site['description']) ?>">
    <link rel="canonical" href="<?= e($baseUrl) ?>">
    <meta name="theme-color" content="#0F0F10">
    <meta name="color-scheme" content="dark">
    <meta name="format-detection" content="telephone=no">

    <!-- Social sharing -->
    <meta property="og:type" content="restaurant">
    <meta property="og:title" content="<?= e($site['title']) ?>">
    <meta property="og:description" content="<?= e($site['description']) ?>">
    <meta property="og:url" content="<?= e($baseUrl) ?>">
    <meta property="og:image" content="<?= e($baseUrl) ?>assets/img/ui/og-cover.jpg">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Icons -->
    <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="assets/icons/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">

    <!-- Performance: fonts and hero image are requested before anything else -->
    <link rel="preload" href="assets/fonts/cormorant-garamond-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="assets/fonts/cormorant-garamond-latin-wght-italic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="assets/fonts/manrope-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" as="image" type="image/webp" media="(min-width: 701px), (orientation: landscape)"
          imagesrcset="assets/img/hero/room-960.webp 960w, assets/img/hero/room-1440.webp 1440w, assets/img/hero/room-1920.webp 1920w" imagesizes="100vw">
    <link rel="preload" as="image" type="image/webp" media="(max-width: 700px) and (orientation: portrait)"
          imagesrcset="assets/img/hero/room-portrait-720.webp 720w, assets/img/hero/room-portrait-1080.webp 1080w" imagesizes="100vw">

    <!-- Styles -->
    <link rel="stylesheet" href="<?= asset('assets/css/vendor/swiper-bundle.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/sections.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/overlays.css') ?>">

    <!--
        Flags the document as script-enabled before first paint (drives the loading screen).
        Safety net: if the scripts below never start, the page is released after a few seconds.
    -->
    <script>
        (function (root) {
            root.className = root.className.replace('no-js', 'js is-loading');
            window.__loaderSafety = setTimeout(function () {
                root.classList.remove('is-loading');
                root.classList.add('is-released');
            }, 7000);
        })(document.documentElement);
    </script>

    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>

<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <!-- ══════════════════════════════════════════════════════════════════════
         LOADING SCREEN
         ══════════════════════════════════════════════════════════════════════ -->
    <div class="loader" data-loader aria-hidden="true">
        <div class="loader__inner">
            <?= brand_mark('loader__mark') ?>
            <p class="loader__name"><?php foreach (preg_split('//u', $site['name'], -1, PREG_SPLIT_NO_EMPTY) as $letter): ?><span><?= e($letter) ?></span><?php endforeach; ?></p>
            <p class="loader__tag"><?= e($site['descriptor']) ?> · <?= e($site['city']) ?></p>
        </div>
        <div class="loader__foot">
            <span class="loader__label">Setting the table</span>
            <span class="loader__bar"><span data-loader-bar></span></span>
            <span class="loader__count"><span data-loader-count>0</span>%</span>
        </div>
    </div>

    <!-- Custom cursor, scroll progress and film grain (decorative layers) -->
    <div class="cursor" data-cursor-root aria-hidden="true">
        <span class="cursor__dot"></span>
        <span class="cursor__ring"><span class="cursor__label" data-cursor-label></span></span>
    </div>
    <div class="progress" aria-hidden="true"><span class="progress__bar" data-progress></span></div>
    <div class="grain" aria-hidden="true"></div>


    <!-- ══════════════════════════════════════════════════════════════════════
         HEADER
         ══════════════════════════════════════════════════════════════════════ -->
    <header class="header" data-header>
        <div class="header__inner">
            <a class="brand" href="#home" aria-label="<?= e($site['name']) ?> — back to the top">
                <?= brand_mark() ?>
                <span class="brand__text">
                    <span class="brand__name"><?= e($site['name']) ?></span>
                    <span class="brand__sub"><?= e($site['descriptor']) ?> · <?= e($site['city']) ?></span>
                </span>
            </a>

            <nav class="nav" aria-label="Primary">
                <ul class="nav__list">
                    <?php foreach ($nav as $label => $href): ?>
                    <li><a class="nav__link" href="<?= e($href) ?>" data-nav-link><span><?= e($label) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="header__actions">
                <button class="icon-btn cart-btn" type="button" data-cart-open data-cart-target aria-label="Open your cart">
                    <?= icon('shopping-bag-line') ?>
                    <span class="cart-btn__count" data-cart-count data-empty>0</span>
                </button>
                <a class="btn btn--primary btn--sm header__cta" href="#reservation" data-magnetic>
                    <span class="btn__label" data-text="Reserve a Table"><span>Reserve a Table</span></span>
                </a>
                <button class="burger" type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="Open the menu">
                    <span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Full-screen navigation (tablet & mobile) -->
    <div class="mobile-nav" id="mobile-nav" data-mobile-nav aria-hidden="true" inert>
        <nav class="mobile-nav__inner" aria-label="Mobile">
            <ul class="mobile-nav__list">
                <?php $i = 0; foreach ($nav as $label => $href): $i++; ?>
                <li style="--i:<?= $i ?>">
                    <a href="<?= e($href) ?>" data-nav-link>
                        <span class="mobile-nav__num"><?= sprintf('%02d', $i) ?></span>
                        <span class="mobile-nav__label"><?= e($label) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="mobile-nav__foot" style="--i:<?= $i + 1 ?>">
                <a class="btn btn--primary btn--block" href="#reservation">
                    <span class="btn__label" data-text="Reserve a Table"><span>Reserve a Table</span></span>
                </a>
                <p class="mobile-nav__contact">
                    <a href="tel:<?= e($phoneHref) ?>"><?= $phoneText ?></a>
                    <span><?= e($site['street']) ?> · <?= e($site['city']) ?></span>
                </p>
            </div>
        </nav>
    </div>


    <main id="main">

        <!-- ══════════════════════════════════════════════════════════════════
             HERO
             ══════════════════════════════════════════════════════════════════ -->
        <section class="hero" id="home" data-section="home" data-rest>
            <div class="hero__media" data-hero-media>
                <picture>
                    <source media="(max-width: 700px) and (orientation: portrait)" type="image/webp" sizes="100vw"
                            srcset="assets/img/hero/room-portrait-720.webp 720w, assets/img/hero/room-portrait-1080.webp 1080w">
                    <img class="hero__img" data-hero-img
                         src="assets/img/hero/room-1440.webp"
                         srcset="assets/img/hero/room-960.webp 960w, assets/img/hero/room-1440.webp 1440w, assets/img/hero/room-1920.webp 1920w"
                         sizes="100vw" width="1920" height="1080" alt="" fetchpriority="high" decoding="async">
                </picture>
            </div>
            <div class="hero__veil" aria-hidden="true"></div>
            <canvas class="hero__embers" data-embers aria-hidden="true"></canvas>

            <div class="container hero__content">
                <p class="eyebrow hero__eyebrow" data-hero-item><span><?= e($site['descriptor']) ?> · <?= e($site['city']) ?><span class="hero__since"> · Since <?= e($site['since']) ?></span></span></p>
                <h1 class="hero__title" data-hero-title>Where <em>time</em> is the first ingredient</h1>
                <p class="hero__lead" data-hero-item>
                    Beneath the seventeenth-century vaults of a Parma townhouse, the treasures of the Food Valley
                    are served with patience, precision and a little poetry.
                </p>
                <div class="hero__actions" data-hero-item>
                    <a class="btn btn--primary btn--lg" href="#reservation" data-magnetic>
                        <span class="btn__label" data-text="Reserve a Table"><span>Reserve a Table</span></span>
                        <?= icon('arrow-right-line', 'btn__icon') ?>
                    </a>
                    <a class="btn btn--glass btn--lg" href="#menu" data-magnetic>
                        <span class="btn__label" data-text="View Menu"><span>View Menu</span></span>
                    </a>
                </div>
            </div>

            <div class="container hero__foot" data-hero-foot>
                <ul class="hero__meta">
                    <li><span>Dinner</span>Tuesday — Saturday, from 7 pm</li>
                    <li><span>Address</span><?= e($site['street']) ?>, <?= e($site['city']) ?></li>
                    <li><span>Guests say</span><?= e(number_format($site['rating']['score'], 1)) ?> / 5 · <?= e(number_format($site['rating']['count'])) ?> reviews</li>
                </ul>
                <a class="scroll-cue" href="#about" aria-label="Scroll to our story">
                    <span class="scroll-cue__label">Scroll</span>
                    <span class="scroll-cue__line"></span>
                </a>
            </div>

            <a class="seal hero__seal" href="#reservation" data-magnetic data-hero-seal aria-label="Reserve a table">
                <?= seal('seal-hero', 'Reserve your table · Buonasera · Reserve your table · Buonasera ·') ?>
                <?= icon('arrow-right-down-line', 'seal__icon') ?>
            </a>
        </section>


        <!-- Running band of house specialities -->
        <div class="marquee" data-marquee aria-hidden="true">
            <div class="marquee__track" data-marquee-track>
                <?php
                $specialities = ['Culatello di Zibello', 'Parmigiano Reggiano', 'Tortelli d’erbetta', 'Lambrusco', 'Prosciutto di Parma', 'Malvasia di Candia', 'Anolini in brodo', 'Aceto balsamico'];
                for ($copy = 0; $copy < 2; $copy++): ?>
                <div class="marquee__group">
                    <?php foreach ($specialities as $n => $word): ?>
                    <span class="marquee__word<?= $n % 2 ? ' marquee__word--outline' : '' ?>"><?= e($word) ?></span>
                    <span class="marquee__sep"></span>
                    <?php endforeach; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>


        <!-- ══════════════════════════════════════════════════════════════════
             ABOUT
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section about" id="about" data-section="about">
            <div class="container about__grid">

                <div class="about__media">
                    <figure class="about__img about__img--main media" data-reveal="clip">
                        <?= img('about/pasta', 'Fresh tagliatelle being portioned by hand on a floured board', '(min-width: 1000px) 38vw, 86vw', ['class' => 'media__img', 'attrs' => 'data-parallax="12"']) ?>
                    </figure>
                    <figure class="about__img about__img--small media" data-reveal="clip" data-float="-60">
                        <?= img('about/parmigiano', 'Wheels of Parmigiano Reggiano ageing in a warehouse', '(min-width: 1000px) 18vw, 40vw', ['class' => 'media__img']) ?>
                    </figure>
                    <div class="about__since" data-reveal="fade" data-float="40">
                        <span>Since</span>
                        <strong><?= e($site['since']) ?></strong>
                    </div>
                </div>

                <div class="about__body">
                    <p class="eyebrow" data-reveal>Our story</p>
                    <h2 class="title" data-split>Three generations, one <span class="nowrap"><em>obsession</em>:</span> <span class="nowrap">the ingredient.</span></h2>
                    <p class="lead" data-reveal>
                        It began in <?= e($site['since']) ?> with six tables, a wood-fired stove and the recipes Ada Bellini
                        carried in her head. Nearly four decades later her grandson Lorenzo cooks in the same
                        townhouse — with the same stubborn respect for the product.
                    </p>
                    <p data-reveal>
                        Culatello aged in the fog of the Bassa, Parmigiano brought down from the hills, pasta rolled at dawn:
                        nothing here is rushed and nothing is left to chance. <?= e($site['name']) ?> is a house of quiet gestures,
                        where every evening is composed like a score.
                    </p>

                    <ul class="features" data-reveal-group>
                        <li class="feature" data-spotlight>
                            <span class="feature__icon"><?= icon('hand-heart-line') ?></span>
                            <h3 class="feature__title">Sfoglia at dawn</h3>
                            <p class="feature__text">Every pasta is rolled, cut and filled by hand each morning.</p>
                        </li>
                        <li class="feature" data-spotlight>
                            <span class="feature__icon"><?= icon('seedling-line') ?></span>
                            <h3 class="feature__title">The Food Valley, unfiltered</h3>
                            <p class="feature__text">Fourteen producers we know by name, all within thirty kilometres.</p>
                        </li>
                        <li class="feature" data-spotlight>
                            <span class="feature__icon"><?= icon('goblet-line') ?></span>
                            <h3 class="feature__title">A cellar with a memory</h3>
                            <p class="feature__text">240 labels, from Lambrusco growers to the great vintages.</p>
                        </li>
                    </ul>

                    <div class="signature-line" data-reveal>
                        <span class="signature-line__name"><?= e($site['chef']) ?></span>
                        <span class="signature-line__role">Chef &amp; third generation</span>
                    </div>
                </div>
            </div>

            <div class="container">
                <dl class="stats" data-reveal-group>
                    <?php foreach ($stats as $stat): ?>
                    <div class="stat">
                        <dt class="stat__label"><?= e($stat['label']) ?></dt>
                        <dd class="stat__value"><span data-count="<?= e($stat['value']) ?>"><?= e($stat['value']) ?></span><?php if ($stat['suffix']): ?><sup><?= e($stat['suffix']) ?></sup><?php endif; ?></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             MENU
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section section--alt menu" id="menu" data-section="menu">
            <div class="container">
                <header class="section__head section__head--split">
                    <div>
                        <p class="eyebrow" data-reveal>La carta</p>
                        <h2 class="title" data-split>A menu written by the <em>seasons</em></h2>
                    </div>
                    <p class="section__intro" data-reveal>
                        Short, precise and rewritten four times a year. Every dish can be enjoyed at our table —
                        or at yours, with delivery and take-away.
                    </p>
                </header>

                <div class="menu__bar" data-reveal>
                    <div class="tabs" role="tablist" aria-label="Menu categories" data-filter-tabs="menu">
                        <span class="tabs__pill" data-filter-pill aria-hidden="true"></span>
                        <?php $first = true; foreach ($menu as $slug => $category): ?>
                        <button class="tabs__btn<?= $first ? ' is-active' : '' ?>" type="button" role="tab"
                                aria-selected="<?= $first ? 'true' : 'false' ?>" data-filter="<?= e($slug) ?>" data-filter-title="<?= e($category['it']) ?>">
                            <?= e($category['label']) ?><sup><?= count($category['items']) ?></sup>
                        </button>
                        <?php $first = false; endforeach; ?>
                    </div>
                    <p class="menu__category" aria-hidden="true"><span data-filter-heading="menu"><?= e(reset($menu)['it']) ?></span></p>
                </div>

                <div class="menu__grid" data-filter-grid="menu" aria-live="polite">
                    <?php foreach ($menu as $slug => $category): foreach ($category['items'] as $item): ?>
                    <article class="dish" data-category="<?= e($slug) ?>" data-dish="<?= e($item['id']) ?>">
                        <div class="dish__media" data-tilt>
                            <?= img('menu/' . $item['id'], $item['name'], '(min-width: 1200px) 300px, (min-width: 900px) 30vw, (min-width: 640px) 45vw, 120px', ['class' => 'dish__img']) ?>
                            <span class="dish__glare" aria-hidden="true"></span>
                            <?php if ($item['tag']): ?><span class="dish__tag"><?= e($item['tag']) ?></span><?php endif; ?>
                            <span class="dish__qty" data-dish-qty hidden>0</span>
                            <button class="dish__add" type="button" data-add="<?= e($item['id']) ?>" aria-label="Add <?= e($item['name']) ?> to your order">
                                <?= icon('add-line') ?><span>Add to order</span>
                            </button>
                        </div>
                        <div class="dish__body">
                            <div class="dish__row">
                                <h3 class="dish__name"><?= e($item['name']) ?></h3>
                                <span class="dish__lead" aria-hidden="true"></span>
                                <span class="dish__price"><?= e(price((float) $item['price'])) ?></span>
                            </div>
                            <p class="dish__desc"><?= e($item['desc']) ?></p>
                        </div>
                    </article>
                    <?php endforeach; endforeach; ?>
                </div>

                <footer class="menu__note" data-reveal>
                    <div class="menu__note-item">
                        <?= icon('sparkling-line') ?>
                        <p><strong>Tasting menu · seven courses · €95</strong><span>Wine pairing by our sommelier, €55. Served to the whole table.</span></p>
                    </div>
                    <div class="menu__note-item">
                        <?= icon('information-line') ?>
                        <p><strong>Allergies &amp; intolerances</strong><span>Tell us when you book: the kitchen adapts every dish it can.</span></p>
                    </div>
                    <a class="link-arrow" href="#reservation"><span>Reserve a Table</span><?= icon('arrow-right-line') ?></a>
                </footer>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             GALLERY
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section gallery" id="gallery" data-section="gallery">
            <div class="container">
                <header class="section__head section__head--split">
                    <div>
                        <p class="eyebrow" data-reveal>Gallery</p>
                        <h2 class="title" data-split>An evening, <em>frame by frame</em></h2>
                    </div>
                    <div class="gallery__controls" data-reveal>
                        <p class="gallery__fraction"><span data-gallery-current>01</span><i></i><span><?= sprintf('%02d', count($gallery)) ?></span></p>
                        <button class="icon-btn icon-btn--line" type="button" data-gallery-prev aria-label="Previous photographs"><?= icon('arrow-left-line') ?></button>
                        <button class="icon-btn icon-btn--line" type="button" data-gallery-next aria-label="Next photographs"><?= icon('arrow-right-line') ?></button>
                    </div>
                </header>
            </div>

            <div class="gallery__viewport" data-reveal="fade">
                <div class="swiper gallery__swiper" data-gallery data-cursor="Drag">
                    <div class="swiper-wrapper">
                        <?php foreach ($gallery as $n => $photo): ?>
                        <div class="swiper-slide gallery__slide gallery__slide--<?= e($photo['shape']) ?>">
                            <a class="gallery__item" href="<?= e(img_url($photo['img'])) ?>" data-lightbox="<?= $n ?>" data-caption="<?= e($photo['label']) ?>">
                                <?= img($photo['img'], $photo['label'], $photo['shape'] === 'wide' ? '(min-width: 900px) 62vw, 88vw' : '(min-width: 900px) 34vw, 70vw', ['class' => 'gallery__img', 'attrs' => 'data-gallery-img']) ?>
                                <span class="gallery__caption">
                                    <span class="gallery__num"><?= sprintf('%02d', $n + 1) ?></span>
                                    <span class="gallery__label"><?= e($photo['label']) ?></span>
                                </span>
                                <span class="gallery__zoom"><?= icon('zoom-in-line') ?></span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="container">
                    <div class="gallery__progress" aria-hidden="true"><span data-gallery-progress></span></div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             CHEF'S SPECIAL
             ══════════════════════════════════════════════════════════════════ -->
        <?php $special = $dishes['tortelli-erbetta']; ?>
        <section class="section section--alt signature" id="signature" data-section="signature" data-rest>
            <div class="container signature__grid">

                <div class="signature__media">
                    <figure class="signature__img media" data-reveal="clip">
                        <?= img('signature/tortelli', 'Tortelli d’erbetta with melted butter and Parmigiano on a charcoal plate', '(min-width: 1000px) 44vw, 90vw', ['class' => 'media__img', 'attrs' => 'data-parallax="10"']) ?>
                    </figure>
                    <figure class="signature__chef media" data-reveal="clip" data-float="-50">
                        <?= img('signature/chef', 'The chef finishing a plate with tweezers', '(min-width: 1000px) 16vw, 36vw', ['class' => 'media__img']) ?>
                    </figure>
                    <div class="seal signature__seal" data-float="30" aria-hidden="true">
                        <?= seal('seal-special', 'Chef’s Special · Signature dish · Chef’s Special · Signature dish ·') ?>
                        <?= brand_mark('seal__mark') ?>
                    </div>
                </div>

                <div class="signature__body">
                    <p class="eyebrow" data-reveal>Chef’s Special</p>
                    <h2 class="title" data-split>Tortelli d’erbetta, the dish that <em>built the house</em></h2>
                    <p class="lead" data-reveal>
                        Paper-thin egg pasta, a filling of fresh ricotta and garden chard, then nothing but melted
                        mountain butter and a snowfall of thirty-six-month Parmigiano. In Parma it is served on
                        the night of San Giovanni; here, every evening.
                    </p>

                    <dl class="specs" data-reveal-group>
                        <div class="specs__item"><dt>Pasta</dt><dd>30 yolks per kilo of flour, rolled by hand</dd></div>
                        <div class="specs__item"><dt>Pairing</dt><dd>Malvasia di Candia, Colli di Parma</dd></div>
                        <div class="specs__item"><dt>Since</dt><dd>On the menu, unchanged, since <?= e($site['since']) ?></dd></div>
                    </dl>

                    <blockquote class="quote" data-reveal>
                        <?= icon('double-quotes-l', 'quote__mark') ?>
                        <p>My grandmother folded two hundred of these every morning. I have never changed her recipe — only tried to deserve it.</p>
                        <cite><?= e($site['chef']) ?>, Chef</cite>
                    </blockquote>

                    <div class="signature__actions" data-reveal>
                        <button class="btn btn--primary btn--lg" type="button" data-add="<?= e($special['id']) ?>" data-magnetic>
                            <span class="btn__label" data-text="Add to order · <?= e(price((float) $special['price'])) ?>"><span>Add to order · <?= e(price((float) $special['price'])) ?></span></span>
                            <?= icon('add-line', 'btn__icon') ?>
                        </button>
                        <a class="link-arrow" href="#reservation"><span>Taste it at our table</span><?= icon('arrow-right-line') ?></a>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             RESERVATION (front-end simulation — nothing is stored or sent)
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section reservation" id="reservation" data-section="reservation">
            <div class="reservation__glow" aria-hidden="true"></div>
            <div class="container reservation__grid">

                <div class="reservation__intro">
                    <p class="eyebrow" data-reveal>Reservation</p>
                    <h2 class="title" data-split>Your table is <em>waiting</em></h2>
                    <p class="lead" data-reveal>
                        Choose the vaulted dining room or, on warm evenings, the courtyard terrace.
                        We will confirm your table by email within the hour.
                    </p>

                    <div class="reservation__visual media" data-reveal="clip" data-seating-visual="Indoor">
                        <?= img('reserve/indoor-room', 'The vaulted dining room, tables set for dinner', '(min-width: 1000px) 34vw, 90vw', ['class' => 'media__img reservation__photo', 'attrs' => 'data-seating-photo="Indoor"']) ?>
                        <?= img('reserve/terrace-b', 'The terrace at dusk under strings of lights', '(min-width: 1000px) 34vw, 90vw', ['class' => 'media__img reservation__photo', 'attrs' => 'data-seating-photo="Terrace"']) ?>
                        <p class="reservation__caption">
                            <span data-seating-caption="Indoor">Sala delle Volte · 42 seats</span>
                            <span data-seating-caption="Terrace">La Corte · open May to September</span>
                        </p>
                    </div>

                    <ul class="facts" data-reveal-group>
                        <li><?= icon('time-line') ?><span>Dinner Tuesday to Saturday · Lunch Friday to Sunday</span></li>
                        <li><?= icon('group-line') ?><span>More than eight guests? Call us on <a href="tel:<?= e($phoneHref) ?>"><?= $phoneText ?></a></span></li>
                        <li><?= icon('shield-check-line') ?><span>Free cancellation up to 24 hours before</span></li>
                    </ul>
                </div>

                <form class="form panel" id="reservation-form" action="#reservation" method="post" novalidate data-reservation data-reveal>
                    <div class="form__head">
                        <h3 class="form__title">Book a table</h3>
                        <p class="form__hint">It takes less than a minute.</p>
                    </div>

                    <div class="form__grid">
                        <div class="field field--full" data-field>
                            <label class="field__label" for="res-name">Name</label>
                            <div class="field__control">
                                <?= icon('user-line') ?>
                                <input class="field__input" id="res-name" name="name" type="text" autocomplete="name" placeholder="Your full name" required minlength="2" maxlength="80">
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-phone">Phone</label>
                            <div class="field__control">
                                <?= icon('phone-line') ?>
                                <input class="field__input" id="res-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="+39 000 000 0000" required maxlength="24">
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-email">Email</label>
                            <div class="field__control">
                                <?= icon('mail-line') ?>
                                <input class="field__input" id="res-email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="you@email.com" required maxlength="120">
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <span class="field__label" id="res-guests-label">Number of guests</span>
                            <div class="field__control field__control--stepper" role="group" aria-labelledby="res-guests-label">
                                <?= icon('group-line') ?>
                                <output class="stepper__value" for="res-guests" data-guests-label>2 guests</output>
                                <input id="res-guests" name="guests" type="hidden" value="2" data-guests>
                                <button class="stepper__btn" type="button" data-guests-step="-1" aria-label="One guest fewer"><?= icon('subtract-line') ?></button>
                                <button class="stepper__btn" type="button" data-guests-step="1" aria-label="One more guest"><?= icon('add-line') ?></button>
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field role="radiogroup" aria-labelledby="res-seating-label">
                            <span class="field__label" id="res-seating-label">Seating</span>
                            <div class="segmented">
                                <label class="segmented__option">
                                    <input type="radio" name="seating" value="Indoor" checked>
                                    <span><?= icon('home-4-line') ?>Indoor</span>
                                </label>
                                <label class="segmented__option">
                                    <input type="radio" name="seating" value="Terrace">
                                    <span><?= icon('sun-line') ?>Terrace</span>
                                </label>
                                <span class="segmented__pill" aria-hidden="true"></span>
                            </div>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-date">Date</label>
                            <div class="field__control">
                                <?= icon('calendar-line') ?>
                                <input class="field__input" id="res-date" name="date" type="date" min="<?= e($today) ?>" required>
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-time">Time</label>
                            <div class="field__control field__control--select">
                                <?= icon('time-line') ?>
                                <select class="field__input" id="res-time" name="time" required>
                                    <option value="" selected disabled hidden>Select a time</option>
                                    <?php foreach ($slots as $service => $times): ?>
                                    <optgroup label="<?= e($service) ?>" data-service="<?= e(strtolower($service)) ?>">
                                        <?php foreach ($times as $time): ?>
                                        <option value="<?= e($time) ?>"><?= e($time) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <?= icon('arrow-down-s-line', 'field__chevron') ?>
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field field--full" data-field>
                            <label class="field__label" for="res-comments">Comments <span class="field__optional">Optional</span></label>
                            <div class="field__control field__control--area">
                                <textarea class="field__input" id="res-comments" name="comments" rows="3" maxlength="400" placeholder="An occasion to celebrate, an allergy, a favourite table…"></textarea>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn--primary btn--lg btn--block" type="submit" data-submit>
                        <span class="btn__label" data-text="Reserve"><span>Reserve</span></span>
                        <?= icon('arrow-right-line', 'btn__icon') ?>
                        <span class="btn__spinner" aria-hidden="true"></span>
                    </button>
                    <p class="form__note"><?= icon('shield-check-line') ?>No payment required. Your details are only used to manage your booking.</p>
                </form>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             ONLINE ORDER (front-end simulation)
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section section--alt order" id="delivery" data-section="delivery">
            <div class="container order__grid">

                <div class="order__intro">
                    <p class="eyebrow" data-reveal>Order online</p>
                    <h2 class="title" data-split><?= e($site['name']) ?>, at <em>your</em> table</h2>
                    <p class="lead" data-reveal>
                        The same kitchen, the same ingredients — cooked to order, packed with care and brought
                        to your door, or ready to collect in a few minutes.
                    </p>

                    <div class="segmented segmented--lg" data-reveal role="radiogroup" aria-label="How would you like your order?">
                        <label class="segmented__option">
                            <input type="radio" name="order-mode" value="delivery" data-order-mode checked>
                            <span><?= icon('e-bike-2-line') ?>Delivery</span>
                        </label>
                        <label class="segmented__option">
                            <input type="radio" name="order-mode" value="takeaway" data-order-mode>
                            <span><?= icon('takeaway-line') ?>Take Away</span>
                        </label>
                        <span class="segmented__pill" aria-hidden="true"></span>
                    </div>

                    <ul class="order__facts" data-reveal-group>
                        <li class="order__fact">
                            <?= icon('timer-line') ?>
                            <strong data-mode-text="eta"><?= $order['deliveryEta'][0] ?>–<?= $order['deliveryEta'][1] ?> min</strong>
                            <span data-mode-text="etaLabel">Estimated delivery</span>
                        </li>
                        <li class="order__fact">
                            <?= icon('wallet-3-line') ?>
                            <strong data-mode-text="fee"><?= e(price($order['deliveryFee'])) ?></strong>
                            <span data-mode-text="feeLabel">Delivery fee · free over <?= e(price((float) $order['freeFrom'])) ?></span>
                        </li>
                        <li class="order__fact">
                            <?= icon('map-pin-line') ?>
                            <strong data-mode-text="zone">Within 5 km</strong>
                            <span data-mode-text="zoneLabel">Parma centre and Oltretorrente</span>
                        </li>
                    </ul>

                    <ol class="steps" data-reveal-group>
                        <li><span>01</span>Choose your dishes</li>
                        <li><span>02</span>Review your cart</li>
                        <li><span>03</span>We cook, you enjoy</li>
                    </ol>
                </div>

                <div class="board panel" data-reveal>
                    <div class="board__head">
                        <h3 class="board__title">Compose your order</h3>
                        <div class="chips" role="tablist" aria-label="Order categories" data-filter-tabs="order">
                            <?php $first = true; foreach ($menu as $slug => $category): ?>
                            <button class="chips__btn<?= $first ? ' is-active' : '' ?>" type="button" role="tab"
                                    aria-selected="<?= $first ? 'true' : 'false' ?>" data-filter="<?= e($slug) ?>"><?= e($category['label']) ?></button>
                            <?php $first = false; endforeach; ?>
                        </div>
                    </div>

                    <ul class="board__list" data-filter-grid="order" data-lenis-prevent>
                        <?php foreach ($menu as $slug => $category): foreach ($category['items'] as $item): ?>
                        <li class="row" data-category="<?= e($slug) ?>" data-dish="<?= e($item['id']) ?>">
                            <img class="row__img" src="<?= e(img_url('menu/' . $item['id'], 480)) ?>" width="72" height="90" alt="" loading="lazy" decoding="async">
                            <div class="row__text">
                                <h4 class="row__name"><?= e($item['name']) ?></h4>
                                <p class="row__desc"><?= e($item['desc']) ?></p>
                            </div>
                            <span class="row__price"><?= e(price((float) $item['price'])) ?></span>
                            <div class="qty" data-qty="<?= e($item['id']) ?>" data-value="0">
                                <button class="qty__btn qty__btn--dec" type="button" data-dec aria-label="Remove one <?= e($item['name']) ?>"><?= icon('subtract-line') ?></button>
                                <output class="qty__value" aria-live="polite">0</output>
                                <button class="qty__btn qty__btn--inc" type="button" data-inc aria-label="Add one <?= e($item['name']) ?>"><?= icon('add-line') ?></button>
                            </div>
                        </li>
                        <?php endforeach; endforeach; ?>
                    </ul>

                    <div class="board__foot">
                        <p class="board__sum">
                            <span data-cart-summary>Your cart is empty</span>
                            <strong data-cart-total>€0.00</strong>
                        </p>
                        <button class="btn btn--primary" type="button" data-cart-open>
                            <span class="btn__label" data-text="View cart"><span>View cart</span></span>
                            <?= icon('shopping-bag-line', 'btn__icon') ?>
                        </button>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             REVIEWS
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section reviews" id="reviews" data-section="reviews">
            <div class="container reviews__head">
                <div>
                    <p class="eyebrow" data-reveal>Guest reviews</p>
                    <h2 class="title" data-split>Words from <em>our tables</em></h2>
                </div>
                <div class="rating" data-reveal>
                    <span class="rating__score" data-count="<?= e($site['rating']['score']) ?>" data-decimals="1"><?= e(number_format($site['rating']['score'], 1)) ?></span>
                    <div class="rating__meta">
                        <span class="stars" role="img" aria-label="<?= e($site['rating']['score']) ?> out of 5"><?= str_repeat(icon('star-fill'), 5) ?></span>
                        <span>Average of <?= e(number_format($site['rating']['count'])) ?> verified guests</span>
                    </div>
                    <div class="rating__nav">
                        <button class="icon-btn icon-btn--line" type="button" data-reviews-prev aria-label="Previous reviews"><?= icon('arrow-left-line') ?></button>
                        <button class="icon-btn icon-btn--line" type="button" data-reviews-next aria-label="Next reviews"><?= icon('arrow-right-line') ?></button>
                    </div>
                </div>
            </div>

            <div class="reviews__viewport" data-reveal="fade">
                <div class="swiper reviews__swiper" data-reviews data-cursor="Drag">
                    <div class="swiper-wrapper">
                        <?php foreach ($reviews as $review): $loved = $dishes[$review['dish']] ?? null; ?>
                        <article class="swiper-slide review" data-spotlight>
                            <header class="review__top">
                                <span class="stars" role="img" aria-label="5 out of 5"><?= str_repeat(icon('star-fill'), 5) ?></span>
                                <span class="review__occasion"><?= e($review['occasion']) ?></span>
                            </header>
                            <blockquote class="review__text"><p>“<?= e($review['text']) ?>”</p></blockquote>
                            <footer class="review__foot">
                                <span class="avatar" aria-hidden="true"><?= e(initials($review['name'])) ?></span>
                                <p class="review__author"><strong><?= e($review['name']) ?></strong><span><?= e($review['from']) ?></span></p>
                                <?php if ($loved): ?>
                                <p class="review__dish">
                                    <img src="<?= e($loved['image']) ?>" width="44" height="44" alt="" loading="lazy" decoding="async">
                                    <span>Loved<strong><?= e($loved['name']) ?></strong></span>
                                </p>
                                <?php endif; ?>
                            </footer>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             FAQ
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section section--alt faq" id="faq" data-section="faq">
            <div class="container faq__grid">
                <div class="faq__aside">
                    <p class="eyebrow" data-reveal>Good to know</p>
                    <h2 class="title" data-split>Questions, <em>answered</em></h2>
                    <p class="lead" data-reveal>Everything you may want to know before your evening with us.</p>
                    <a class="link-arrow" href="tel:<?= e($phoneHref) ?>" data-reveal><span>Still curious? Call <?= $phoneText ?></span><?= icon('arrow-right-up-line') ?></a>
                </div>

                <div class="accordion" data-accordion data-reveal-group>
                    <?php foreach ($faq as $n => $entry): $id = 'faq-' . ($n + 1); ?>
                    <div class="accordion__item<?= $n === 0 ? ' is-open' : '' ?>">
                        <h3 class="accordion__heading">
                            <button class="accordion__trigger" type="button" id="<?= $id ?>-trigger" aria-controls="<?= $id ?>" aria-expanded="<?= $n === 0 ? 'true' : 'false' ?>">
                                <span class="accordion__num"><?= sprintf('%02d', $n + 1) ?></span>
                                <span class="accordion__question"><?= e($entry['q']) ?></span>
                                <span class="accordion__icon" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div class="accordion__panel" id="<?= $id ?>" role="region" aria-labelledby="<?= $id ?>-trigger">
                            <div class="accordion__content"><p><?= e($entry['a']) ?></p></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             CONTACT
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section contact" id="contact" data-section="contact">
            <div class="container contact__grid">

                <div class="contact__info">
                    <p class="eyebrow" data-reveal>Contact</p>
                    <h2 class="title" data-split>Find your way <em>to us</em></h2>

                    <ul class="contact__list" data-reveal-group>
                        <li class="contact__item">
                            <span class="contact__icon"><?= icon('map-pin-line') ?></span>
                            <div>
                                <span class="contact__label">Address</span>
                                <address><?= e($site['street']) ?><br><?= e($site['zip']) ?> <?= e($site['city']) ?>, <?= e($site['country']) ?></address>
                            </div>
                        </li>
                        <li class="contact__item">
                            <span class="contact__icon"><?= icon('phone-line') ?></span>
                            <div>
                                <span class="contact__label">Phone</span>
                                <a href="tel:<?= e($phoneHref) ?>"><?= $phoneText ?></a>
                            </div>
                        </li>
                        <li class="contact__item">
                            <span class="contact__icon"><?= icon('mail-line') ?></span>
                            <div>
                                <span class="contact__label">Email</span>
                                <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a>
                            </div>
                        </li>
                    </ul>

                    <div class="hours panel" data-rest data-reveal data-spotlight>
                        <div class="hours__head">
                            <h3 class="hours__title">Opening hours</h3>
                            <span class="status" data-open-status hidden><i></i><span data-open-label>Open now</span></span>
                        </div>
                        <dl class="hours__list">
                            <?php foreach (opening_rows($hours) as $row): ?>
                            <div class="hours__row" data-days="<?= e(implode(',', $row['days'])) ?>">
                                <dt><?= e($row['from'] === $row['to'] ? $row['from'] : $row['from'] . ' — ' . $row['to']) ?></dt>
                                <dd>
                                    <?php if (!$row['services']): ?>
                                    <span class="hours__closed">Closed</span>
                                    <?php else: foreach ($row['services'] as $service): ?>
                                    <span><?= e($service[0]) ?> – <?= e($service[1]) ?></span>
                                    <?php endforeach; endif; ?>
                                </dd>
                            </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>

                    <div class="contact__actions" data-reveal>
                        <a class="btn btn--primary" href="tel:<?= e($phoneHref) ?>" data-magnetic>
                            <?= icon('phone-line', 'btn__icon btn__icon--lead') ?>
                            <span class="btn__label" data-text="Call us"><span>Call us</span></span>
                        </a>
                        <a class="btn btn--glass" href="<?= e($site['maps_url']) ?>" target="_blank" rel="noopener" data-magnetic>
                            <?= icon('direction-line', 'btn__icon btn__icon--lead') ?>
                            <span class="btn__label" data-text="Get directions"><span>Get directions</span></span>
                        </a>
                    </div>
                </div>

                <div class="map" data-map data-rest data-reveal="clip">
                    <div class="map__canvas" data-map-canvas>
                        <?php /* Static drawing: streets, river, landmarks (painted once) */ ?>
                        <?php readfile(__DIR__ . '/assets/img/ui/map.svg'); ?>

                        <?php /* Moving parts, kept in their own light layer so the drawing below is never repainted */ ?>
                        <svg class="map__svg map__live" viewBox="0 0 800 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
                            <defs>
                                <linearGradient id="map-gold" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#F4E6C3"/>
                                    <stop offset="1" stop-color="#C9A96E"/>
                                </linearGradient>
                            </defs>
                            <!-- Walking route from Piazza Garibaldi, drawn when the map comes into view -->
                            <path class="map__route" d="M423 400l97-2 2-53 68-2" pathLength="1"/>
                            <circle class="map__walker" r="3">
                                <animateMotion dur="7s" repeatCount="indefinite" calcMode="linear" keyPoints="0;1;1" keyTimes="0;0.72;1" path="M423 400l97-2 2-53 68-2"/>
                                <animate attributeName="opacity" dur="7s" repeatCount="indefinite" values="0;1;1;0;0" keyTimes="0;0.06;0.66;0.74;1"/>
                            </circle>
                            <!-- The restaurant -->
                            <g transform="translate(590 343)">
                                <circle class="map__pulse" r="40"/>
                                <circle class="map__pulse map__pulse--late" r="40"/>
                                <ellipse cy="1" rx="7" ry="2.6" fill="#000" fill-opacity="0.5"/>
                                <g class="map__marker">
                                    <path d="M0 0c-8.5-10.5-14-16.5-14-25a14 14 0 1 1 28 0c0 8.5-5.5 14.5-14 25z" fill="url(#map-gold)"/>
                                    <circle cy="-25" r="5.4" fill="#141415"/>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <div class="map__card">
                        <?= brand_mark('map__mark') ?>
                        <p><strong><?= e($site['name']) ?> <?= e($site['descriptor']) ?></strong><span><?= e($site['street']) ?> · 2 min from Piazza Duomo</span></p>
                    </div>
                    <div class="map__legend" aria-hidden="true"><?= icon('walk-line') ?><span>6 min from Piazza Garibaldi</span></div>
                </div>
            </div>
        </section>
    </main>


    <!-- ══════════════════════════════════════════════════════════════════════
         FOOTER
         ══════════════════════════════════════════════════════════════════════ -->
    <footer class="footer" data-section="footer">
        <div class="container footer__cta">
            <p class="eyebrow eyebrow--center" data-reveal>Buonasera</p>
            <h2 class="footer__headline" data-split>Shall we set <em>your</em> table?</h2>
            <a class="btn btn--primary btn--lg" href="#reservation" data-magnetic data-reveal>
                <span class="btn__label" data-text="Reserve a Table"><span>Reserve a Table</span></span>
                <?= icon('arrow-right-line', 'btn__icon') ?>
            </a>
        </div>

        <div class="container footer__grid">
            <div class="footer__brand">
                <a class="brand" href="#home" aria-label="<?= e($site['name']) ?> — back to the top">
                    <?= brand_mark() ?>
                    <span class="brand__text">
                        <span class="brand__name"><?= e($site['name']) ?></span>
                        <span class="brand__sub"><?= e($site['descriptor']) ?> · <?= e($site['city']) ?></span>
                    </span>
                </a>
                <p>Fine Italian dining beneath seventeenth-century vaults, in the heart of Parma. Family-run since <?= e($site['since']) ?>.</p>
                <ul class="socials">
                    <?php foreach ($site['social'] as $network => $profile): ?>
                    <li><a class="icon-btn icon-btn--line" href="<?= e($profile['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($site['name']) ?> on <?= e($profile['label']) ?>"><?= icon($network . '-line') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <nav class="footer__col" aria-label="Footer">
                <h3 class="footer__title">Explore</h3>
                <ul class="footer__links">
                    <?php foreach ($nav as $label => $href): ?>
                    <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="footer__col">
                <h3 class="footer__title">Visit</h3>
                <address class="footer__address">
                    <?= e($site['street']) ?><br>
                    <?= e($site['zip']) ?> <?= e($site['city']) ?>, <?= e($site['country']) ?>
                </address>
                <ul class="footer__links">
                    <li><a href="tel:<?= e($phoneHref) ?>"><?= $phoneText ?></a></li>
                    <li><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></li>
                </ul>
                <p class="footer__hours">Tue — Sat · dinner<br>Fri — Sun · lunch</p>
            </div>

            <div class="footer__col footer__col--news">
                <h3 class="footer__title">La lettera</h3>
                <p>Seasonal menus, truffle evenings and the occasional secret from the cellar. Four letters a year, never more.</p>
                <form class="newsletter" action="#" method="post" novalidate data-newsletter>
                    <label class="sr-only" for="newsletter-email">Email address</label>
                    <input class="newsletter__input" id="newsletter-email" name="email" type="email" autocomplete="email" placeholder="Your email address" required>
                    <button class="newsletter__btn" type="submit" aria-label="Subscribe to the newsletter"><?= icon('arrow-right-line') ?></button>
                    <p class="newsletter__msg" data-newsletter-msg role="status"></p>
                </form>
            </div>
        </div>

        <p class="footer__wordmark" aria-hidden="true" data-wordmark><?= e($site['name']) ?></p>

        <div class="container footer__bottom">
            <p>© <?= $year ?> <?= e($site['name']) ?> <?= e($site['descriptor']) ?> · <?= e($site['vat']) ?></p>
            <ul class="footer__legal">
                <li><button type="button" data-modal-open="legal" data-legal-target="legal-notice">Legal notice</button></li>
                <li><button type="button" data-modal-open="legal" data-legal-target="legal-privacy">Privacy</button></li>
                <li><button type="button" data-modal-open="legal" data-legal-target="legal-cookies">Cookies</button></li>
            </ul>
            <a class="to-top" href="#home"><span>Back to top</span><?= icon('arrow-up-line') ?></a>
        </div>
    </footer>


    <!-- Quick actions bar (small screens) -->
    <nav class="dock" data-dock aria-label="Quick actions">
        <a class="dock__item" href="tel:<?= e($phoneHref) ?>"><?= icon('phone-line') ?><span>Call</span></a>
        <a class="dock__cta" href="#reservation">Reserve a Table</a>
        <button class="dock__item" type="button" data-cart-open data-cart-target>
            <?= icon('shopping-bag-line') ?><span>Cart</span>
            <b class="dock__count" data-cart-count data-empty>0</b>
        </button>
    </nav>


    <!-- ══════════════════════════════════════════════════════════════════════
         CART DRAWER
         ══════════════════════════════════════════════════════════════════════ -->
    <div class="drawer" id="cart" data-overlay="cart" role="dialog" aria-modal="true" aria-labelledby="cart-title" aria-hidden="true" inert>
        <div class="drawer__backdrop" data-overlay-close></div>
        <aside class="drawer__panel">
            <header class="drawer__head">
                <div>
                    <p class="eyebrow">Your order</p>
                    <h2 class="drawer__title" id="cart-title">Cart <sup data-cart-count>0</sup></h2>
                </div>
                <button class="icon-btn icon-btn--line" type="button" data-overlay-close aria-label="Close the cart"><?= icon('close-line') ?></button>
            </header>

            <div class="drawer__mode">
                <div class="segmented" role="radiogroup" aria-label="How would you like your order?">
                    <label class="segmented__option">
                        <input type="radio" name="cart-mode" value="delivery" data-order-mode checked>
                        <span><?= icon('e-bike-2-line') ?>Delivery</span>
                    </label>
                    <label class="segmented__option">
                        <input type="radio" name="cart-mode" value="takeaway" data-order-mode>
                        <span><?= icon('takeaway-line') ?>Take Away</span>
                    </label>
                    <span class="segmented__pill" aria-hidden="true"></span>
                </div>
            </div>

            <div class="drawer__body" data-lenis-prevent>
                <ul class="cart-list" data-cart-list></ul>
                <div class="cart-empty" data-cart-empty>
                    <span class="cart-empty__icon"><?= icon('shopping-bag-line') ?></span>
                    <p class="cart-empty__title">Your cart is empty</p>
                    <p>Add a few dishes and they will be waiting for you here.</p>
                    <a class="btn btn--glass" href="#delivery" data-overlay-close>
                        <span class="btn__label" data-text="Browse the menu"><span>Browse the menu</span></span>
                    </a>
                </div>
            </div>

            <footer class="drawer__foot" data-cart-foot>
                <div class="free" data-free>
                    <p data-free-text></p>
                    <span class="free__bar"><span data-free-bar></span></span>
                </div>
                <dl class="totals">
                    <div><dt>Subtotal</dt><dd data-cart-subtotal>€0.00</dd></div>
                    <div><dt data-fee-label>Delivery fee</dt><dd data-cart-fee>€0.00</dd></div>
                    <div class="totals__total"><dt>Total</dt><dd data-cart-grand>€0.00</dd></div>
                </dl>
                <button class="btn btn--primary btn--lg btn--block" type="button" data-checkout>
                    <span class="btn__label" data-text="Checkout"><span>Checkout</span></span>
                    <?= icon('arrow-right-line', 'btn__icon') ?>
                    <span class="btn__spinner" aria-hidden="true"></span>
                </button>
                <p class="drawer__note"><?= icon('shield-check-line') ?>Pay on delivery or at collection — card or cash.</p>
            </footer>
        </aside>
    </div>


    <!-- ══════════════════════════════════════════════════════════════════════
         MODALS
         ══════════════════════════════════════════════════════════════════════ -->

    <!-- Reservation received -->
    <div class="modal" id="modal-reservation" data-overlay="reservation" role="dialog" aria-modal="true" aria-labelledby="modal-reservation-title" aria-hidden="true" inert>
        <div class="modal__backdrop" data-overlay-close></div>
        <div class="modal__dialog">
            <button class="icon-btn icon-btn--line modal__close" type="button" data-overlay-close aria-label="Close"><?= icon('close-line') ?></button>
            <svg class="tick" viewBox="0 0 72 72" aria-hidden="true" focusable="false">
                <circle cx="36" cy="36" r="33" pathLength="1"/>
                <path d="M23 37.5l9 9 17.5-19" pathLength="1"/>
            </svg>
            <p class="eyebrow eyebrow--center"><span>Grazie<span data-res-name></span></span></p>
            <h2 class="modal__title" id="modal-reservation-title">Your reservation request has been received.</h2>
            <p class="modal__text">We will confirm your table by email within the hour. We look forward to welcoming you.</p>
            <ul class="summary" data-res-summary>
                <li><?= icon('calendar-line') ?><span data-res="date"></span></li>
                <li><?= icon('time-line') ?><span data-res="time"></span></li>
                <li><?= icon('group-line') ?><span data-res="guests"></span></li>
                <li><?= icon('map-pin-line') ?><span data-res="seating"></span></li>
            </ul>
            <button class="btn btn--primary" type="button" data-overlay-close>
                <span class="btn__label" data-text="Wonderful"><span>Wonderful</span></span>
            </button>
        </div>
    </div>

    <!-- Order confirmed -->
    <div class="modal" id="modal-order" data-overlay="order" role="dialog" aria-modal="true" aria-labelledby="modal-order-title" aria-hidden="true" inert>
        <div class="modal__backdrop" data-overlay-close></div>
        <div class="modal__dialog">
            <button class="icon-btn icon-btn--line modal__close" type="button" data-overlay-close aria-label="Close"><?= icon('close-line') ?></button>
            <svg class="tick" viewBox="0 0 72 72" aria-hidden="true" focusable="false">
                <circle cx="36" cy="36" r="33" pathLength="1"/>
                <path d="M23 37.5l9 9 17.5-19" pathLength="1"/>
            </svg>
            <p class="eyebrow eyebrow--center"><span>Order <span data-order="ref"></span></span></p>
            <h2 class="modal__title" id="modal-order-title">Your order is confirmed.</h2>
            <p class="modal__text" data-order="text"></p>
            <ul class="summary">
                <li><?= icon('timer-line') ?><span data-order="eta"></span></li>
                <li><?= icon('shopping-bag-line') ?><span data-order="items"></span></li>
                <li><?= icon('wallet-3-line') ?><span data-order="total"></span></li>
            </ul>
            <button class="btn btn--primary" type="button" data-overlay-close>
                <span class="btn__label" data-text="Buon appetito"><span>Buon appetito</span></span>
            </button>
        </div>
    </div>

    <!-- Legal information -->
    <div class="modal modal--text" id="modal-legal" data-overlay="legal" role="dialog" aria-modal="true" aria-labelledby="modal-legal-title" aria-hidden="true" inert>
        <div class="modal__backdrop" data-overlay-close></div>
        <div class="modal__dialog">
            <button class="icon-btn icon-btn--line modal__close" type="button" data-overlay-close aria-label="Close"><?= icon('close-line') ?></button>
            <p class="eyebrow">Information</p>
            <h2 class="modal__title" id="modal-legal-title">Legal notice</h2>
            <div class="modal__scroll prose" data-lenis-prevent tabindex="0">
                <h3 id="legal-notice">Publisher</h3>
                <p><?= e($site['name']) ?> <?= e($site['descriptor']) ?> · <?= e($site['street']) ?>, <?= e($site['zip']) ?> <?= e($site['city']) ?> · <?= e($site['vat']) ?> · <?= e($site['email']) ?>.</p>
                <p>This website is a design prototype. The restaurant, its menu, prices, reviews and contact details are fictional; reservations, orders and newsletter subscriptions are simulated in your browser and are neither stored nor transmitted.</p>
                <h3 id="legal-privacy">Privacy</h3>
                <p>No personal data is collected. The information typed into the forms never leaves your device and disappears when the page is closed. Fonts, scripts and images are served from this website only — no third-party request is made.</p>
                <h3 id="legal-cookies">Cookies</h3>
                <p>This website does not use cookies, trackers or analytics of any kind. You have nothing to accept.</p>
                <h3>Credits</h3>
                <p>Typefaces: Cormorant Garamond and Manrope (SIL Open Font License). Icons: Remix Icon. Motion: GSAP, Lenis and Swiper. Photography: the dishes, the drinks and the vaulted dining room are original images generated for this prototype; the other photographs are by Unsplash contributors (Unsplash License).</p>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════════════════════════════════════════════════
         LIGHTBOX
         ══════════════════════════════════════════════════════════════════════ -->
    <div class="lightbox" id="lightbox" data-overlay="lightbox" role="dialog" aria-modal="true" aria-label="Photo gallery" aria-hidden="true" inert>
        <div class="lightbox__backdrop" data-overlay-close></div>
        <figure class="lightbox__figure" data-lightbox-figure>
            <img class="lightbox__img" data-lightbox-img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
            <figcaption class="lightbox__caption">
                <span class="lightbox__count" data-lightbox-count></span>
                <span class="lightbox__label" data-lightbox-label></span>
            </figcaption>
        </figure>
        <button class="icon-btn icon-btn--line lightbox__close" type="button" data-overlay-close aria-label="Close the gallery"><?= icon('close-line') ?></button>
        <button class="icon-btn icon-btn--line lightbox__nav lightbox__nav--prev" type="button" data-lightbox-prev aria-label="Previous photograph"><?= icon('arrow-left-line') ?></button>
        <button class="icon-btn icon-btn--line lightbox__nav lightbox__nav--next" type="button" data-lightbox-next aria-label="Next photograph"><?= icon('arrow-right-line') ?></button>
    </div>

    <!-- Notifications -->
    <div class="toasts" data-toasts role="status" aria-live="polite"></div>


    <!-- ══════════════════════════════════════════════════════════════════════
         TEMPLATES (cloned by the JavaScript modules)
         ══════════════════════════════════════════════════════════════════════ -->
    <template id="tpl-cart-item">
        <li class="cart-item">
            <img class="cart-item__img" width="64" height="80" alt="">
            <div class="cart-item__main">
                <h3 class="cart-item__name"></h3>
                <p class="cart-item__unit"></p>
                <div class="qty qty--inline" data-value="1">
                    <button class="qty__btn qty__btn--dec" type="button" data-dec aria-label="Remove one"><?= icon('subtract-line') ?></button>
                    <output class="qty__value">1</output>
                    <button class="qty__btn qty__btn--inc" type="button" data-inc aria-label="Add one"><?= icon('add-line') ?></button>
                </div>
            </div>
            <div class="cart-item__side">
                <strong class="cart-item__total"></strong>
                <button class="cart-item__remove" type="button" data-remove aria-label="Remove from cart"><?= icon('delete-bin-line') ?></button>
            </div>
        </li>
    </template>

    <template id="tpl-toast">
        <div class="toast">
            <span class="toast__icon"><?= icon('check-line') ?></span>
            <p class="toast__text"><strong></strong><span></span></p>
            <button class="toast__action" type="button"></button>
        </div>
    </template>


    <!-- Data shared with the scripts -->
    <script type="application/json" id="app-data"><?= json_encode($appData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

    <!-- Libraries (self-hosted) then the application modules -->
    <script defer src="<?= asset('assets/js/vendor/gsap.min.js') ?>"></script>
    <script defer src="<?= asset('assets/js/vendor/ScrollTrigger.min.js') ?>"></script>
    <script defer src="<?= asset('assets/js/vendor/SplitText.min.js') ?>"></script>
    <script defer src="<?= asset('assets/js/vendor/lenis.min.js') ?>"></script>
    <script defer src="<?= asset('assets/js/vendor/swiper-bundle.min.js') ?>"></script>
    <script type="module" src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
