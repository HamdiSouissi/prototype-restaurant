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
 *  The interface is written in Italian; code and comments are in English.
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
    'title'       => 'Aurea — Ristorante a Parma · Alta cucina italiana',
    'description' => 'Una sala a lume di candela sotto volte seicentesche, nel cuore di Parma. Pasta fatta a mano, Culatello di Zibello, una cantina di 240 etichette. Prenota il tuo tavolo online.',
    'street'      => 'Borgo dell’Aurora 12',
    'zip'         => '43121',
    'country'     => 'Italia',
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
    'Home'         => '#home',
    'Menù'         => '#menu',
    'Galleria'     => '#gallery',
    'Prenotazioni' => '#reservation',
    'A domicilio'  => '#delivery',
    'Chi siamo'    => '#about',
    'Contatti'     => '#contact',
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
    'Pranzo' => ['12:30', '13:00', '13:30', '14:00'],
    'Cena'   => ['19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00'],
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
 * Menu — one entry per category: label (tab), motto (the line shown beside the
 * tabs) and dishes. Each dish: id (also its image name in /assets/img/menu),
 * name, description, price and an optional tag.
 */
$menu = [
    'starters' => [
        'label' => 'Antipasti',
        'motto' => 'Per cominciare',
        'items' => [
            ['id' => 'culatello',  'name' => 'Culatello di Zibello',          'price' => 24, 'tag' => 'Piatto firma', 'desc' => 'Stagionato trenta mesi nelle nebbie della Bassa, servito con torta fritta calda.'],
            ['id' => 'parmigiano', 'name' => 'Parmigiano, tre stagionature',  'price' => 18, 'tag' => 'Vegetariano',  'desc' => '24, 36 e 60 mesi, miele di acacia, noci e aceto balsamico tradizionale.'],
            ['id' => 'battuta',    'name' => 'Battuta di Fassona',            'price' => 22, 'tag' => '',             'desc' => 'Fassona piemontese battuta al coltello, tuorlo marinato, scaglie di Parmigiano, olio extravergine.'],
            ['id' => 'sformato',   'name' => 'Sformato di Parmigiano',        'price' => 19, 'tag' => 'Vegetariano',  'desc' => 'Sformato tiepido di Parmigiano, fonduta allo zafferano, lamelle di tartufo nero.'],
            ['id' => 'prosciutto', 'name' => 'Prosciutto di Parma',           'price' => 20, 'tag' => '',             'desc' => 'Trenta mesi, tagliato a mano al momento, con fichi di fine estate.'],
            ['id' => 'uovo',       'name' => 'Uovo morbido',                  'price' => 17, 'tag' => '',             'desc' => 'Uovo di cascina cotto a bassa temperatura, asparagi verdi, spuma di Parmigiano, pancetta croccante.'],
        ],
    ],
    'pasta' => [
        'label' => 'Pasta',
        'motto' => 'Tirata a mano',
        'items' => [
            ['id' => 'tortelli-erbetta', 'name' => 'Tortelli d’erbetta',     'price' => 22, 'tag' => 'Piatto firma', 'desc' => 'Ripieno di ricotta ed erbette, burro fuso di montagna, Parmigiano 36 mesi.'],
            ['id' => 'anolini',          'name' => 'Anolini in brodo',       'price' => 21, 'tag' => '',             'desc' => 'Ripieni di stracotto di manzo e Parmigiano, in brodo limpido di cappone.'],
            ['id' => 'tortelli-zucca',   'name' => 'Tortelli di zucca',      'price' => 21, 'tag' => 'Vegetariano',  'desc' => 'Zucca, amaretti e mostarda, burro nocciola, salvia croccante.'],
            ['id' => 'tagliatelle-ragu', 'name' => 'Tagliatelle al ragù',    'price' => 20, 'tag' => '',             'desc' => 'Sfoglia all’uovo tirata a mano, ragù di manzo e maiale cotto otto ore.'],
        ],
    ],
    'pizza' => [
        'label' => 'Pizza',
        'motto' => 'Dal forno a legna',
        'items' => [
            ['id' => 'pizza-margherita', 'name' => 'Margherita',             'price' => 13, 'tag' => 'Vegetariano',  'desc' => 'Pomodoro San Marzano, fior di latte, basilico. Impasto a 48 ore di lievitazione.'],
            ['id' => 'pizza-bufala',     'name' => 'Bufala',                 'price' => 16, 'tag' => 'Vegetariano',  'desc' => 'Pomodoro, mozzarella di bufala aggiunta a crudo, basilico, olio ligure.'],
            ['id' => 'pizza-crudo',      'name' => 'Parma',                  'price' => 18, 'tag' => 'Piatto firma', 'desc' => 'Fior di latte, Prosciutto di Parma 24 mesi, basilico.'],
            ['id' => 'pizza-pomodoro',   'name' => 'Tre pomodori',           'price' => 14, 'tag' => 'Vegano',       'desc' => 'Datterino, San Marzano e pomodoro secco, origano, olio all’aglio.'],
            ['id' => 'pizza-bresaola',   'name' => 'Bresaola e rucola',      'price' => 17, 'tag' => '',             'desc' => 'Bresaola, rucola selvatica, scaglie di Parmigiano, olio al limone.'],
        ],
    ],
    'mains' => [
        'label' => 'Secondi',
        'motto' => 'Fuoco e pazienza',
        'items' => [
            ['id' => 'rosa-di-parma', 'name' => 'Rosa di Parma',             'price' => 34, 'tag' => 'Piatto firma', 'desc' => 'Filetto di manzo arrotolato con Prosciutto e Parmigiano, fondo al Lambrusco.'],
            ['id' => 'filetto',       'name' => 'Filetto di manzo',          'price' => 36, 'tag' => '',             'desc' => 'Filetto di manzo alla griglia, asparagi verdi, patata fondente, fondo bruno.'],
            ['id' => 'guancia',       'name' => 'Guancia brasata',           'price' => 29, 'tag' => '',             'desc' => 'Guancia di vitello brasata dodici ore, polenta morbida macinata a pietra.'],
            ['id' => 'tagliata',      'name' => 'Tagliata di Fassona',       'price' => 32, 'tag' => '',             'desc' => 'Scottata e servita al sangue, insalate amare, fondo al rosmarino versato al tavolo.'],
            ['id' => 'salmerino',     'name' => 'Salmerino alla brace',      'price' => 30, 'tag' => '',             'desc' => 'Salmerino alpino cotto sulla brace, patata viola, erbe spontanee.'],
        ],
    ],
    'desserts' => [
        'label' => 'Dolci',
        'motto' => 'Per finire',
        'items' => [
            ['id' => 'tiramisu',    'name' => 'Tiramisù della casa',         'price' => 11, 'tag' => 'Piatto firma', 'desc' => 'Crema al mascarpone, savoiardi bagnati nell’espresso, cacao amaro.'],
            ['id' => 'panna-cotta', 'name' => 'Panna cotta',                 'price' => 10, 'tag' => '',             'desc' => 'Panna alla vaniglia, composta di amarene, crumble di mandorle tostate.'],
            ['id' => 'semifreddo',  'name' => 'Semifreddo al torroncino',    'price' => 11, 'tag' => '',             'desc' => 'Semifreddo al torrone, coulis di lamponi, pistacchio di Bronte.'],
            ['id' => 'affogato',    'name' => 'Affogato al caffè',           'price' => 9,  'tag' => '',             'desc' => 'Gelato fior di latte, caffè ristretto versato al tavolo.'],
        ],
    ],
    'drinks' => [
        'label' => 'Bevande',
        'motto' => 'Dalla cantina',
        'items' => [
            ['id' => 'lambrusco',    'name' => 'Lambrusco di Sorbara',       'price' => 8,  'tag' => '',             'desc' => 'Secco, frizzante, rosso rubino. Al calice.'],
            ['id' => 'malvasia',     'name' => 'Malvasia di Candia',         'price' => 9,  'tag' => '',             'desc' => 'Bianco aromatico dei Colli di Parma. Al calice.'],
            ['id' => 'negroni',      'name' => 'Negroni',                    'price' => 12, 'tag' => 'Classico',     'desc' => 'Gin, bitter, vermouth rosso, scorza d’arancia.'],
            ['id' => 'boulevardier', 'name' => 'Boulevardier',               'price' => 13, 'tag' => '',             'desc' => 'Bourbon, bitter e vermouth dolce, servito in coppa.'],
            ['id' => 'spritz',       'name' => 'Spritz della casa',          'price' => 10, 'tag' => '',             'desc' => 'Aperitivo all’arancia amara, Malvasia frizzante, soda.'],
            ['id' => 'espresso',     'name' => 'Espresso',                   'price' => 3,  'tag' => '',             'desc' => 'Miscela della casa, tostata a pochi passi da qui.'],
        ],
    ],
];

/** Gallery — image id in /assets/img, caption, layout (wide = 3:2, tall = 4:5) */
$gallery = [
    ['img' => 'gallery/room',       'label' => 'Sala delle Volte',             'shape' => 'wide'],
    ['img' => 'gallery/pass',       'label' => 'L’ultimo gesto, al pass',      'shape' => 'tall'],
    ['img' => 'gallery/long-table', 'label' => 'La grande tavolata',           'shape' => 'tall'],
    ['img' => 'gallery/table',      'label' => 'La tavola apparecchiata',      'shape' => 'wide'],
    ['img' => 'gallery/cellar',     'label' => 'La cantina · 240 etichette',   'shape' => 'tall'],
    ['img' => 'gallery/sfoglia',    'label' => 'La sfoglia, ogni mattina',     'shape' => 'tall'],
    ['img' => 'gallery/duomo',      'label' => 'Piazza Duomo, a due minuti',   'shape' => 'wide'],
    ['img' => 'gallery/kitchen',    'label' => 'La cucina, ore 19',            'shape' => 'tall'],
    ['img' => 'gallery/bar',        'label' => 'Il bancone',                   'shape' => 'wide'],
    ['img' => 'gallery/wine',       'label' => 'L’ora della Malvasia',         'shape' => 'tall'],
];

/** Key figures shown under the story (the first one is computed from $site['since']) */
$stats = [
    ['value' => (int) date('Y') - $site['since'], 'suffix' => '',   'label' => 'Anni di gestione familiare'],
    ['value' => 240,                              'suffix' => '+',  'label' => 'Etichette a riposo in cantina'],
    ['value' => 36,                               'suffix' => '',   'label' => 'Mesi di stagionatura del nostro Parmigiano'],
    ['value' => 14,                               'suffix' => '',   'label' => 'Produttori, tutti nel raggio di trenta chilometri'],
];

/** Guest reviews — "dish" refers to a menu id (thumbnail shown on the card) */
$reviews = [
    ['name' => 'Giulia M.',       'from' => 'Milano', 'occasion' => 'Cena per due',          'dish' => 'tortelli-erbetta', 'text' => 'I tortelli da soli valgono il viaggio fino a Parma. E un servizio che sa leggere il tavolo senza mai interromperlo.'],
    ['name' => 'Thomas & Claire', 'from' => 'Lione',  'occasion' => 'Anniversario',          'dish' => 'tiramisu',         'text' => 'Ci hanno coccolati dal primo calice di Malvasia all’ultimo cucchiaio di tiramisù. Impeccabile, senza mai essere ingessato.'],
    ['name' => 'Hiroshi T.',      'from' => 'Tokyo',  'occasion' => 'Menù degustazione',     'dish' => 'culatello',        'text' => 'Una sala illuminata dalle candele, una cantina piena di sorprese e un culatello a cui penso ancora, settimane dopo.'],
    ['name' => 'Elena R.',        'from' => 'Parma',  'occasion' => 'Pranzo della domenica', 'dish' => 'anolini',          'text' => 'È raro trovare una cucina così precisa che cucini ancora con tanto cuore. Gli anolini sapevano di domenica dalla nonna.'],
    ['name' => 'Marco B.',        'from' => 'Parma',  'occasion' => 'A domicilio',           'dish' => 'pizza-crudo',      'text' => 'Ordinato per un pranzo in famiglia: è arrivato tutto confezionato con cura e ancora caldo. La qualità del ristorante, alla nostra tavola.'],
    ['name' => 'Sophie L.',       'from' => 'Londra', 'occasion' => 'Cena di lavoro',        'dish' => 'rosa-di-parma',    'text' => 'Dall’accoglienza all’abbinamento dei vini, ogni dettaglio è pensato. Senza dubbio una delle migliori tavole dell’Emilia.'],
];

/** Frequently asked questions */
$faq = [
    ['q' => 'È necessario prenotare?',                   'a' => 'Consigliamo la prenotazione, soprattutto dal giovedì alla domenica. Qualche posto al bancone resta sempre libero per chi arriva senza preavviso. Il tavolo viene tenuto per quindici minuti.'],
    ['q' => 'È richiesto un abbigliamento particolare?', 'a' => 'Elegante ma informale. Vieni come andresti a cena nella bella casa di un amico: la giacca è gradita, mai obbligatoria.'],
    ['q' => 'Gestite allergie ed esigenze alimentari?',  'a' => 'Certamente. Per la maggior parte dei piatti sono disponibili alternative vegetariane e senza glutine. Segnala eventuali allergie al momento della prenotazione, così la cucina potrà prepararsi con cura.'],
    ['q' => 'I bambini sono i benvenuti?',               'a' => 'Sempre. Abbiamo seggioloni, un piccolo menù di mezze porzioni e tanta pazienza. Il pranzo è il momento più tranquillo per le famiglie.'],
    ['q' => 'Dove posso parcheggiare?',                  'a' => 'Il ristorante si trova all’interno della ZTL. Il parcheggio più vicino è a cinque minuti a piedi e, per il rientro, prenotiamo volentieri un taxi.'],
    ['q' => 'Organizzate cene private ed eventi?',       'a' => 'La nostra sala a volta in cantina accoglie fino a diciotto ospiti attorno a un’unica tavola, con menù e abbinamenti su misura. Chiamaci: costruiremo la serata insieme.'],
    ['q' => 'Come funzionano consegna e asporto?',       'a' => 'Scegli i piatti online, poi decidi tra la consegna entro cinque chilometri e il ritiro al ristorante. Ogni ordine è cucinato al momento e lascia la cucina in contenitori termici.'],
    ['q' => 'Qual è la politica di cancellazione?',      'a' => 'I programmi cambiano. Puoi annullare o modificare la prenotazione gratuitamente fino a ventiquattro ore prima.'],
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

/** Formats a number the Italian way: 1280 → "1.280", 4.9 → "4,9". */
function number($value, int $decimals = 0): string
{
    return number_format((float) $value, $decimals, ',', '.');
}

/** Formats a price the Italian way, without decimals when round: "24 €", "4,50 €". */
function price(float $amount): string
{
    // The space before the symbol is non-breaking: a price is never cut in two
    return number($amount, floor($amount) == $amount ? 0 : 2) . "\u{00A0}€";
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
 * e.g. Martedì → Giovedì · 19:00 – 23:00.
 */
function opening_rows(array $hours): array
{
    $names = [1 => 'Lunedì', 2 => 'Martedì', 3 => 'Mercoledì', 4 => 'Giovedì', 5 => 'Venerdì', 6 => 'Sabato', 0 => 'Domenica'];
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
    'servesCuisine'      => ['Italiana', 'Emiliana'],
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
<html lang="it" class="no-js">
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
    <meta property="og:locale" content="it_IT">
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
    <a class="skip-link" href="#main">Vai al contenuto</a>

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
            <span class="loader__label">Apparecchiamo la tavola</span>
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
            <a class="brand" href="#home" aria-label="<?= e($site['name']) ?> — torna all’inizio">
                <?= brand_mark() ?>
                <span class="brand__text">
                    <span class="brand__name"><?= e($site['name']) ?></span>
                    <span class="brand__sub"><?= e($site['descriptor']) ?> · <?= e($site['city']) ?></span>
                </span>
            </a>

            <nav class="nav" aria-label="Navigazione principale">
                <ul class="nav__list">
                    <?php foreach ($nav as $label => $href): ?>
                    <li><a class="nav__link" href="<?= e($href) ?>" data-nav-link><span><?= e($label) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="header__actions">
                <button class="icon-btn cart-btn" type="button" data-cart-open data-cart-target aria-label="Apri il carrello">
                    <?= icon('shopping-bag-line') ?>
                    <span class="cart-btn__count" data-cart-count data-empty>0</span>
                </button>
                <a class="btn btn--primary btn--sm header__cta" href="#reservation" data-magnetic>
                    <span class="btn__label" data-text="Prenota un tavolo"><span>Prenota un tavolo</span></span>
                </a>
                <button class="burger" type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="Apri la navigazione">
                    <span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Full-screen navigation (tablet & mobile) -->
    <div class="mobile-nav" id="mobile-nav" data-mobile-nav aria-hidden="true" inert>
        <nav class="mobile-nav__inner" aria-label="Navigazione mobile">
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
                    <span class="btn__label" data-text="Prenota un tavolo"><span>Prenota un tavolo</span></span>
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
                <p class="eyebrow hero__eyebrow" data-hero-item><span><?= e($site['descriptor']) ?> · <?= e($site['city']) ?><span class="hero__since"> · Dal <?= e($site['since']) ?></span></span></p>
                <h1 class="hero__title" data-hero-title>Dove il <em>tempo</em> è il primo ingrediente</h1>
                <p class="hero__lead" data-hero-item>
                    Sotto le volte seicentesche di un palazzo parmigiano, i tesori della Food Valley
                    sono serviti con pazienza, precisione e un pizzico di poesia.
                </p>
                <div class="hero__actions" data-hero-item>
                    <a class="btn btn--primary btn--lg" href="#reservation" data-magnetic>
                        <span class="btn__label" data-text="Prenota un tavolo"><span>Prenota un tavolo</span></span>
                        <?= icon('arrow-right-line', 'btn__icon') ?>
                    </a>
                    <a class="btn btn--glass btn--lg" href="#menu" data-magnetic>
                        <span class="btn__label" data-text="Scopri il menù"><span>Scopri il menù</span></span>
                    </a>
                </div>
            </div>

            <div class="container hero__foot" data-hero-foot>
                <ul class="hero__meta">
                    <li><span>Cena</span>Da martedì a sabato, dalle 19:00</li>
                    <li><span>Indirizzo</span><?= e($site['street']) ?>, <?= e($site['city']) ?></li>
                    <li><span>Dicono di noi</span><?= e(number($site['rating']['score'], 1)) ?> / 5 · <?= e(number($site['rating']['count'])) ?> recensioni</li>
                </ul>
                <a class="scroll-cue" href="#about" aria-label="Scorri fino alla nostra storia">
                    <span class="scroll-cue__label">Scorri</span>
                    <span class="scroll-cue__line"></span>
                </a>
            </div>

            <a class="seal hero__seal" href="#reservation" data-magnetic data-hero-seal aria-label="Prenota un tavolo">
                <?= seal('seal-hero', 'Prenota un tavolo · Buonasera · Prenota un tavolo · Buonasera ·') ?>
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
                        <?= img('about/pasta', 'Tagliatelle fresche porzionate a mano su un tagliere infarinato', '(min-width: 1000px) 38vw, 86vw', ['class' => 'media__img', 'attrs' => 'data-parallax="12"']) ?>
                    </figure>
                    <figure class="about__img about__img--small media" data-reveal="clip" data-float="-60">
                        <?= img('about/parmigiano', 'Forme di Parmigiano Reggiano in stagionatura in un magazzino', '(min-width: 1000px) 18vw, 40vw', ['class' => 'media__img']) ?>
                    </figure>
                    <div class="about__since" data-reveal="fade" data-float="40">
                        <span>Dal</span>
                        <strong><?= e($site['since']) ?></strong>
                    </div>
                </div>

                <div class="about__body">
                    <p class="eyebrow" data-reveal>La nostra storia</p>
                    <h2 class="title" data-split>Tre generazioni, una sola <span class="nowrap"><em>ossessione</em>:</span> <span class="nowrap">la materia prima.</span></h2>
                    <p class="lead" data-reveal>
                        Tutto è cominciato nel <?= e($site['since']) ?> con sei tavoli, una stufa a legna e le ricette che Ada Bellini
                        custodiva a memoria. Quasi quattro decenni dopo, suo nipote Lorenzo cucina nello stesso
                        palazzo, con lo stesso ostinato rispetto per il prodotto.
                    </p>
                    <p data-reveal>
                        Culatello stagionato nelle nebbie della Bassa, Parmigiano sceso dalle colline, pasta tirata all’alba:
                        qui nulla è fatto in fretta e nulla è lasciato al caso. <?= e($site['name']) ?> è una casa di gesti silenziosi,
                        dove ogni serata si compone come una partitura.
                    </p>

                    <ul class="features" data-reveal-group>
                        <li class="feature" data-spotlight>
                            <span class="feature__icon"><?= icon('hand-heart-line') ?></span>
                            <h3 class="feature__title">La sfoglia all’alba</h3>
                            <p class="feature__text">Ogni pasta è tirata, tagliata e ripiena a mano ogni mattina.</p>
                        </li>
                        <li class="feature" data-spotlight>
                            <span class="feature__icon"><?= icon('seedling-line') ?></span>
                            <h3 class="feature__title">La Food Valley, senza filtri</h3>
                            <p class="feature__text">Quattordici produttori che conosciamo per nome, tutti nel raggio di trenta chilometri.</p>
                        </li>
                        <li class="feature" data-spotlight>
                            <span class="feature__icon"><?= icon('goblet-line') ?></span>
                            <h3 class="feature__title">Una cantina che ha memoria</h3>
                            <p class="feature__text">240 etichette, dai vignaioli del Lambrusco alle grandi annate.</p>
                        </li>
                    </ul>

                    <div class="signature-line" data-reveal>
                        <span class="signature-line__name"><?= e($site['chef']) ?></span>
                        <span class="signature-line__role">Chef, terza generazione</span>
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
                        <h2 class="title" data-split>Un menù scritto dalle <em>stagioni</em></h2>
                    </div>
                    <p class="section__intro" data-reveal>
                        Breve, preciso e riscritto quattro volte l’anno. Ogni piatto si può gustare alla nostra tavola,
                        oppure alla tua, con consegna a domicilio e asporto.
                    </p>
                </header>

                <div class="menu__bar" data-reveal>
                    <div class="tabs" role="tablist" aria-label="Categorie del menù" data-filter-tabs="menu">
                        <span class="tabs__pill" data-filter-pill aria-hidden="true"></span>
                        <?php $first = true; foreach ($menu as $slug => $category): ?>
                        <button class="tabs__btn<?= $first ? ' is-active' : '' ?>" type="button" role="tab"
                                aria-selected="<?= $first ? 'true' : 'false' ?>" data-filter="<?= e($slug) ?>" data-filter-title="<?= e($category['motto']) ?>">
                            <?= e($category['label']) ?><sup><?= count($category['items']) ?></sup>
                        </button>
                        <?php $first = false; endforeach; ?>
                    </div>
                    <p class="menu__category" aria-hidden="true"><span data-filter-heading="menu"><?= e(reset($menu)['motto']) ?></span></p>
                </div>

                <div class="menu__grid" data-filter-grid="menu" aria-live="polite">
                    <?php foreach ($menu as $slug => $category): foreach ($category['items'] as $item): ?>
                    <article class="dish" data-category="<?= e($slug) ?>" data-dish="<?= e($item['id']) ?>">
                        <div class="dish__media" data-tilt>
                            <?= img('menu/' . $item['id'], $item['name'], '(min-width: 1200px) 300px, (min-width: 900px) 30vw, (min-width: 640px) 45vw, 120px', ['class' => 'dish__img']) ?>
                            <span class="dish__glare" aria-hidden="true"></span>
                            <?php if ($item['tag']): ?><span class="dish__tag"><?= e($item['tag']) ?></span><?php endif; ?>
                            <span class="dish__qty" data-dish-qty hidden>0</span>
                            <button class="dish__add" type="button" data-add="<?= e($item['id']) ?>" aria-label="Aggiungi <?= e($item['name']) ?> all’ordine">
                                <?= icon('add-line') ?><span>Aggiungi all’ordine</span>
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
                        <p><strong>Menù degustazione · sette portate · <?= e(price(95)) ?></strong><span>Abbinamento vini del nostro sommelier, <?= e(price(55)) ?>. Servito per l’intero tavolo.</span></p>
                    </div>
                    <div class="menu__note-item">
                        <?= icon('information-line') ?>
                        <p><strong>Allergie e intolleranze</strong><span>Segnalale al momento della prenotazione: la cucina adatta ogni piatto che può.</span></p>
                    </div>
                    <a class="link-arrow" href="#reservation"><span>Prenota un tavolo</span><?= icon('arrow-right-line') ?></a>
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
                        <p class="eyebrow" data-reveal>Galleria</p>
                        <h2 class="title" data-split>Una serata, <em>scatto dopo scatto</em></h2>
                    </div>
                    <div class="gallery__controls" data-reveal>
                        <p class="gallery__fraction"><span data-gallery-current>01</span><i></i><span><?= sprintf('%02d', count($gallery)) ?></span></p>
                        <button class="icon-btn icon-btn--line" type="button" data-gallery-prev aria-label="Fotografie precedenti"><?= icon('arrow-left-line') ?></button>
                        <button class="icon-btn icon-btn--line" type="button" data-gallery-next aria-label="Fotografie successive"><?= icon('arrow-right-line') ?></button>
                    </div>
                </header>
            </div>

            <div class="gallery__viewport" data-reveal="fade">
                <div class="swiper gallery__swiper" data-gallery data-cursor="Scorri">
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
                        <?= img('signature/tortelli', 'Tortelli d’erbetta con burro fuso e Parmigiano su un piatto color antracite', '(min-width: 1000px) 44vw, 90vw', ['class' => 'media__img', 'attrs' => 'data-parallax="10"']) ?>
                    </figure>
                    <figure class="signature__chef media" data-reveal="clip" data-float="-50">
                        <?= img('signature/chef', 'Lo chef rifinisce un piatto con le pinzette', '(min-width: 1000px) 16vw, 36vw', ['class' => 'media__img']) ?>
                    </figure>
                    <div class="seal signature__seal" data-float="30" aria-hidden="true">
                        <?= seal('seal-special', 'Piatto dello chef · Dal ' . $site['since'] . ' · Piatto dello chef · Dal ' . $site['since'] . ' ·') ?>
                        <?= brand_mark('seal__mark') ?>
                    </div>
                </div>

                <div class="signature__body">
                    <p class="eyebrow" data-reveal>Il piatto dello chef</p>
                    <h2 class="title" data-split>Tortelli d’erbetta, il piatto che ha fatto <em>la nostra storia</em></h2>
                    <p class="lead" data-reveal>
                        Sfoglia all’uovo sottile come carta, un ripieno di ricotta fresca ed erbette dell’orto, poi soltanto
                        burro fuso di montagna e una nevicata di Parmigiano trentasei mesi. A Parma si servono
                        la notte di San Giovanni; qui, ogni sera.
                    </p>

                    <dl class="specs" data-reveal-group>
                        <div class="specs__item"><dt>Sfoglia</dt><dd>30 tuorli per chilo di farina, tirata a mano</dd></div>
                        <div class="specs__item"><dt>Abbinamento</dt><dd>Malvasia di Candia, Colli di Parma</dd></div>
                        <div class="specs__item"><dt>In carta</dt><dd>Immutati dal <?= e($site['since']) ?></dd></div>
                    </dl>

                    <blockquote class="quote" data-reveal>
                        <?= icon('double-quotes-l', 'quote__mark') ?>
                        <p>Mia nonna ne chiudeva duecento ogni mattina. Non ho mai cambiato la sua ricetta: ho solo cercato di meritarla.</p>
                        <cite><?= e($site['chef']) ?>, chef</cite>
                    </blockquote>

                    <div class="signature__actions" data-reveal>
                        <button class="btn btn--primary btn--lg" type="button" data-add="<?= e($special['id']) ?>" data-magnetic>
                            <span class="btn__label" data-text="Aggiungi all’ordine · <?= e(price((float) $special['price'])) ?>"><span>Aggiungi all’ordine · <?= e(price((float) $special['price'])) ?></span></span>
                            <?= icon('add-line', 'btn__icon') ?>
                        </button>
                        <a class="link-arrow" href="#reservation"><span>Assaggiali alla nostra tavola</span><?= icon('arrow-right-line') ?></a>
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
                    <p class="eyebrow" data-reveal>Prenotazione</p>
                    <h2 class="title" data-split>Il tuo tavolo ti <em>aspetta</em></h2>
                    <p class="lead" data-reveal>
                        Scegli la sala a volta oppure, nelle sere più calde, la terrazza nel cortile.
                        Ti confermeremo il tavolo via email entro un’ora.
                    </p>

                    <div class="reservation__visual media" data-reveal="clip" data-seating-visual="Indoor">
                        <?= img('reserve/indoor-room', 'La sala a volta, con i tavoli apparecchiati per la cena', '(min-width: 1000px) 34vw, 90vw', ['class' => 'media__img reservation__photo', 'attrs' => 'data-seating-photo="Indoor"']) ?>
                        <?= img('reserve/terrace-b', 'La terrazza al tramonto sotto fili di luci', '(min-width: 1000px) 34vw, 90vw', ['class' => 'media__img reservation__photo', 'attrs' => 'data-seating-photo="Terrace"']) ?>
                        <p class="reservation__caption">
                            <span data-seating-caption="Indoor">Sala delle Volte · 42 coperti</span>
                            <span data-seating-caption="Terrace">La Corte · aperta da maggio a settembre</span>
                        </p>
                    </div>

                    <ul class="facts" data-reveal-group>
                        <li><?= icon('time-line') ?><span>Cena da martedì a sabato · Pranzo da venerdì a domenica</span></li>
                        <li><?= icon('group-line') ?><span>Più di otto ospiti? Chiamaci al <a href="tel:<?= e($phoneHref) ?>"><?= $phoneText ?></a></span></li>
                        <li><?= icon('shield-check-line') ?><span>Cancellazione gratuita fino a 24 ore prima</span></li>
                    </ul>
                </div>

                <form class="form panel" id="reservation-form" action="#reservation" method="post" novalidate data-reservation data-reveal>
                    <div class="form__head">
                        <h3 class="form__title">Prenota un tavolo</h3>
                        <p class="form__hint">Basta meno di un minuto.</p>
                    </div>

                    <div class="form__grid">
                        <div class="field field--full" data-field>
                            <label class="field__label" for="res-name">Nome</label>
                            <div class="field__control">
                                <?= icon('user-line') ?>
                                <input class="field__input" id="res-name" name="name" type="text" autocomplete="name" placeholder="Nome e cognome" required minlength="2" maxlength="80">
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-phone">Telefono</label>
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
                                <input class="field__input" id="res-email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="nome@email.it" required maxlength="120">
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <span class="field__label" id="res-guests-label">Numero di ospiti</span>
                            <div class="field__control field__control--stepper" role="group" aria-labelledby="res-guests-label">
                                <?= icon('group-line') ?>
                                <output class="stepper__value" for="res-guests" data-guests-label>2 ospiti</output>
                                <input id="res-guests" name="guests" type="hidden" value="2" data-guests>
                                <button class="stepper__btn" type="button" data-guests-step="-1" aria-label="Un ospite in meno"><?= icon('subtract-line') ?></button>
                                <button class="stepper__btn" type="button" data-guests-step="1" aria-label="Un ospite in più"><?= icon('add-line') ?></button>
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field role="radiogroup" aria-labelledby="res-seating-label">
                            <span class="field__label" id="res-seating-label">Tavolo</span>
                            <div class="segmented">
                                <label class="segmented__option">
                                    <input type="radio" name="seating" value="Indoor" checked>
                                    <span><?= icon('home-4-line') ?>Sala</span>
                                </label>
                                <label class="segmented__option">
                                    <input type="radio" name="seating" value="Terrace">
                                    <span><?= icon('sun-line') ?>Terrazza</span>
                                </label>
                                <span class="segmented__pill" aria-hidden="true"></span>
                            </div>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-date">Data</label>
                            <div class="field__control">
                                <?= icon('calendar-line') ?>
                                <input class="field__input" id="res-date" name="date" type="date" min="<?= e($today) ?>" required>
                            </div>
                            <p class="field__error" data-error></p>
                        </div>

                        <div class="field" data-field>
                            <label class="field__label" for="res-time">Orario</label>
                            <div class="field__control field__control--select">
                                <?= icon('time-line') ?>
                                <select class="field__input" id="res-time" name="time" required>
                                    <option value="" selected disabled hidden>Scegli un orario</option>
                                    <?php foreach ($slots as $service => $times): ?>
                                    <optgroup label="<?= e($service) ?>">
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
                            <label class="field__label" for="res-comments">Note <span class="field__optional">Facoltativo</span></label>
                            <div class="field__control field__control--area">
                                <textarea class="field__input" id="res-comments" name="comments" rows="3" maxlength="400" placeholder="Un’occasione da festeggiare, un’allergia, un tavolo preferito…"></textarea>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn--primary btn--lg btn--block" type="submit" data-submit>
                        <span class="btn__label" data-text="Prenota"><span>Prenota</span></span>
                        <?= icon('arrow-right-line', 'btn__icon') ?>
                        <span class="btn__spinner" aria-hidden="true"></span>
                    </button>
                    <p class="form__note"><?= icon('shield-check-line') ?>Nessun pagamento richiesto. I tuoi dati servono solo a gestire la prenotazione.</p>
                </form>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════════════
             ONLINE ORDER (front-end simulation)
             ══════════════════════════════════════════════════════════════════ -->
        <section class="section section--alt order" id="delivery" data-section="delivery">
            <div class="container order__grid">

                <div class="order__intro">
                    <p class="eyebrow" data-reveal>Ordina online</p>
                    <h2 class="title" data-split><?= e($site['name']) ?>, alla <em>tua</em> tavola</h2>
                    <p class="lead" data-reveal>
                        La stessa cucina, gli stessi ingredienti: cucinati al momento, confezionati con cura e portati
                        a casa tua, oppure pronti da ritirare in pochi minuti.
                    </p>

                    <div class="segmented segmented--lg" data-reveal role="radiogroup" aria-label="Come preferisci ricevere l’ordine?">
                        <label class="segmented__option">
                            <input type="radio" name="order-mode" value="delivery" data-order-mode checked>
                            <span><?= icon('e-bike-2-line') ?>Consegna</span>
                        </label>
                        <label class="segmented__option">
                            <input type="radio" name="order-mode" value="takeaway" data-order-mode>
                            <span><?= icon('takeaway-line') ?>Asporto</span>
                        </label>
                        <span class="segmented__pill" aria-hidden="true"></span>
                    </div>

                    <ul class="order__facts" data-reveal-group>
                        <li class="order__fact">
                            <?= icon('timer-line') ?>
                            <strong data-mode-text="eta"><?= $order['deliveryEta'][0] ?>–<?= $order['deliveryEta'][1] ?> min</strong>
                            <span data-mode-text="etaLabel">Consegna stimata</span>
                        </li>
                        <li class="order__fact">
                            <?= icon('wallet-3-line') ?>
                            <strong data-mode-text="fee"><?= e(price($order['deliveryFee'])) ?></strong>
                            <span data-mode-text="feeLabel">Costo di consegna · gratis oltre <?= e(price((float) $order['freeFrom'])) ?></span>
                        </li>
                        <li class="order__fact">
                            <?= icon('map-pin-line') ?>
                            <strong data-mode-text="zone">Entro 5 km</strong>
                            <span data-mode-text="zoneLabel">Parma centro e Oltretorrente</span>
                        </li>
                    </ul>

                    <ol class="steps" data-reveal-group>
                        <li><span>01</span>Scegli i piatti</li>
                        <li><span>02</span>Controlla il carrello</li>
                        <li><span>03</span>Noi cuciniamo, tu gusti</li>
                    </ol>
                </div>

                <div class="board panel" data-reveal>
                    <div class="board__head">
                        <h3 class="board__title">Componi il tuo ordine</h3>
                        <div class="chips" role="tablist" aria-label="Categorie dell’ordine" data-filter-tabs="order">
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
                                <button class="qty__btn qty__btn--dec" type="button" data-dec aria-label="Diminuisci la quantità di <?= e($item['name']) ?>"><?= icon('subtract-line') ?></button>
                                <output class="qty__value" aria-live="polite">0</output>
                                <button class="qty__btn qty__btn--inc" type="button" data-inc aria-label="Aumenta la quantità di <?= e($item['name']) ?>"><?= icon('add-line') ?></button>
                            </div>
                        </li>
                        <?php endforeach; endforeach; ?>
                    </ul>

                    <div class="board__foot">
                        <p class="board__sum">
                            <span data-cart-summary>Il carrello è vuoto</span>
                            <strong data-cart-total>0,00&nbsp;€</strong>
                        </p>
                        <button class="btn btn--primary" type="button" data-cart-open>
                            <span class="btn__label" data-text="Vedi il carrello"><span>Vedi il carrello</span></span>
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
                    <p class="eyebrow" data-reveal>Le recensioni</p>
                    <h2 class="title" data-split>Parole dai <em>nostri tavoli</em></h2>
                </div>
                <div class="rating" data-reveal>
                    <span class="rating__score" data-count="<?= e($site['rating']['score']) ?>" data-decimals="1"><?= e(number($site['rating']['score'], 1)) ?></span>
                    <div class="rating__meta">
                        <span class="stars" role="img" aria-label="<?= e(number($site['rating']['score'], 1)) ?> su 5"><?= str_repeat(icon('star-fill'), 5) ?></span>
                        <span>Media di <?= e(number($site['rating']['count'])) ?> ospiti verificati</span>
                    </div>
                    <div class="rating__nav">
                        <button class="icon-btn icon-btn--line" type="button" data-reviews-prev aria-label="Recensioni precedenti"><?= icon('arrow-left-line') ?></button>
                        <button class="icon-btn icon-btn--line" type="button" data-reviews-next aria-label="Recensioni successive"><?= icon('arrow-right-line') ?></button>
                    </div>
                </div>
            </div>

            <div class="reviews__viewport" data-reveal="fade">
                <div class="swiper reviews__swiper" data-reviews data-cursor="Scorri">
                    <div class="swiper-wrapper">
                        <?php foreach ($reviews as $review): $loved = $dishes[$review['dish']] ?? null; ?>
                        <article class="swiper-slide review" data-spotlight>
                            <header class="review__top">
                                <span class="stars" role="img" aria-label="5 su 5"><?= str_repeat(icon('star-fill'), 5) ?></span>
                                <span class="review__occasion"><?= e($review['occasion']) ?></span>
                            </header>
                            <blockquote class="review__text"><p>«<?= e($review['text']) ?>»</p></blockquote>
                            <footer class="review__foot">
                                <span class="avatar" aria-hidden="true"><?= e(initials($review['name'])) ?></span>
                                <p class="review__author"><strong><?= e($review['name']) ?></strong><span><?= e($review['from']) ?></span></p>
                                <?php if ($loved): ?>
                                <p class="review__dish">
                                    <img src="<?= e($loved['image']) ?>" width="44" height="44" alt="" loading="lazy" decoding="async">
                                    <span>Ha amato<strong><?= e($loved['name']) ?></strong></span>
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
                    <p class="eyebrow" data-reveal>Buono a sapersi</p>
                    <h2 class="title" data-split>Domande e <em>risposte</em></h2>
                    <p class="lead" data-reveal>Tutto quello che può esserti utile sapere prima della tua serata da noi.</p>
                    <a class="link-arrow" href="tel:<?= e($phoneHref) ?>" data-reveal><span>Altre domande? Chiamaci al <?= $phoneText ?></span><?= icon('arrow-right-up-line') ?></a>
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
                    <p class="eyebrow" data-reveal>Contatti</p>
                    <h2 class="title" data-split>La strada <em>fino a noi</em></h2>

                    <ul class="contact__list" data-reveal-group>
                        <li class="contact__item">
                            <span class="contact__icon"><?= icon('map-pin-line') ?></span>
                            <div>
                                <span class="contact__label">Indirizzo</span>
                                <address><?= e($site['street']) ?><br><?= e($site['zip']) ?> <?= e($site['city']) ?>, <?= e($site['country']) ?></address>
                            </div>
                        </li>
                        <li class="contact__item">
                            <span class="contact__icon"><?= icon('phone-line') ?></span>
                            <div>
                                <span class="contact__label">Telefono</span>
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
                            <h3 class="hours__title">Orari di apertura</h3>
                            <span class="status" data-open-status hidden><i></i><span data-open-label>Aperto ora</span></span>
                        </div>
                        <dl class="hours__list">
                            <?php foreach (opening_rows($hours) as $row): ?>
                            <div class="hours__row" data-days="<?= e(implode(',', $row['days'])) ?>">
                                <dt><?= e($row['from'] === $row['to'] ? $row['from'] : $row['from'] . ' — ' . $row['to']) ?></dt>
                                <dd>
                                    <?php if (!$row['services']): ?>
                                    <span class="hours__closed">Chiuso</span>
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
                            <span class="btn__label" data-text="Chiamaci"><span>Chiamaci</span></span>
                        </a>
                        <a class="btn btn--glass" href="<?= e($site['maps_url']) ?>" target="_blank" rel="noopener" data-magnetic>
                            <?= icon('direction-line', 'btn__icon btn__icon--lead') ?>
                            <span class="btn__label" data-text="Come arrivare"><span>Come arrivare</span></span>
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
                        <p><strong><?= e($site['name']) ?> <?= e($site['descriptor']) ?></strong><span><?= e($site['street']) ?> · a 2 min da Piazza Duomo</span></p>
                    </div>
                    <div class="map__legend" aria-hidden="true"><?= icon('walk-line') ?><span>6 min da Piazza Garibaldi</span></div>
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
            <h2 class="footer__headline" data-split>Apparecchiamo il <em>tuo</em> tavolo?</h2>
            <a class="btn btn--primary btn--lg" href="#reservation" data-magnetic data-reveal>
                <span class="btn__label" data-text="Prenota un tavolo"><span>Prenota un tavolo</span></span>
                <?= icon('arrow-right-line', 'btn__icon') ?>
            </a>
        </div>

        <div class="container footer__grid">
            <div class="footer__brand">
                <a class="brand" href="#home" aria-label="<?= e($site['name']) ?> — torna all’inizio">
                    <?= brand_mark() ?>
                    <span class="brand__text">
                        <span class="brand__name"><?= e($site['name']) ?></span>
                        <span class="brand__sub"><?= e($site['descriptor']) ?> · <?= e($site['city']) ?></span>
                    </span>
                </a>
                <p>Alta cucina italiana sotto volte seicentesche, nel cuore di Parma. A conduzione familiare dal <?= e($site['since']) ?>.</p>
                <ul class="socials">
                    <?php foreach ($site['social'] as $network => $profile): ?>
                    <li><a class="icon-btn icon-btn--line" href="<?= e($profile['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($site['name']) ?> su <?= e($profile['label']) ?>"><?= icon($network . '-line') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <nav class="footer__col" aria-label="Navigazione a piè di pagina">
                <h3 class="footer__title">Esplora</h3>
                <ul class="footer__links">
                    <?php foreach ($nav as $label => $href): ?>
                    <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="footer__col">
                <h3 class="footer__title">Dove siamo</h3>
                <address class="footer__address">
                    <?= e($site['street']) ?><br>
                    <?= e($site['zip']) ?> <?= e($site['city']) ?>, <?= e($site['country']) ?>
                </address>
                <ul class="footer__links">
                    <li><a href="tel:<?= e($phoneHref) ?>"><?= $phoneText ?></a></li>
                    <li><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></li>
                </ul>
                <p class="footer__hours">Mar — Sab · cena<br>Ven — Dom · pranzo</p>
            </div>

            <div class="footer__col footer__col--news">
                <h3 class="footer__title">La lettera</h3>
                <p>Menù di stagione, serate al tartufo e qualche segreto di cantina. Quattro lettere l’anno, mai di più.</p>
                <form class="newsletter" action="#" method="post" novalidate data-newsletter>
                    <label class="sr-only" for="newsletter-email">Indirizzo email</label>
                    <input class="newsletter__input" id="newsletter-email" name="email" type="email" autocomplete="email" placeholder="Il tuo indirizzo email" required>
                    <button class="newsletter__btn" type="submit" aria-label="Iscriviti alla newsletter"><?= icon('arrow-right-line') ?></button>
                    <p class="newsletter__msg" data-newsletter-msg role="status"></p>
                </form>
            </div>
        </div>

        <p class="footer__wordmark" aria-hidden="true" data-wordmark><?= e($site['name']) ?></p>

        <div class="container footer__bottom">
            <p>© <?= $year ?> <?= e($site['name']) ?> <?= e($site['descriptor']) ?> · <?= e($site['vat']) ?></p>
            <ul class="footer__legal">
                <li><button type="button" data-modal-open="legal" data-legal-target="legal-notice">Note legali</button></li>
                <li><button type="button" data-modal-open="legal" data-legal-target="legal-privacy">Privacy</button></li>
                <li><button type="button" data-modal-open="legal" data-legal-target="legal-cookies">Cookie</button></li>
            </ul>
            <a class="to-top" href="#home"><span>Torna su</span><?= icon('arrow-up-line') ?></a>
        </div>
    </footer>


    <!-- Quick actions bar (small screens) -->
    <nav class="dock" data-dock aria-label="Azioni rapide">
        <a class="dock__item" href="tel:<?= e($phoneHref) ?>"><?= icon('phone-line') ?><span>Chiama</span></a>
        <a class="dock__cta" href="#reservation">Prenota un tavolo</a>
        <button class="dock__item" type="button" data-cart-open data-cart-target>
            <?= icon('shopping-bag-line') ?><span>Carrello</span>
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
                    <p class="eyebrow">Il tuo ordine</p>
                    <h2 class="drawer__title" id="cart-title">Carrello <sup data-cart-count>0</sup></h2>
                </div>
                <button class="icon-btn icon-btn--line" type="button" data-overlay-close aria-label="Chiudi il carrello"><?= icon('close-line') ?></button>
            </header>

            <div class="drawer__mode">
                <div class="segmented" role="radiogroup" aria-label="Come preferisci ricevere l’ordine?">
                    <label class="segmented__option">
                        <input type="radio" name="cart-mode" value="delivery" data-order-mode checked>
                        <span><?= icon('e-bike-2-line') ?>Consegna</span>
                    </label>
                    <label class="segmented__option">
                        <input type="radio" name="cart-mode" value="takeaway" data-order-mode>
                        <span><?= icon('takeaway-line') ?>Asporto</span>
                    </label>
                    <span class="segmented__pill" aria-hidden="true"></span>
                </div>
            </div>

            <div class="drawer__body" data-lenis-prevent>
                <ul class="cart-list" data-cart-list></ul>
                <div class="cart-empty" data-cart-empty>
                    <span class="cart-empty__icon"><?= icon('shopping-bag-line') ?></span>
                    <p class="cart-empty__title">Il carrello è vuoto</p>
                    <p>Aggiungi qualche piatto: ti aspetterà qui.</p>
                    <a class="btn btn--glass" href="#delivery" data-overlay-close>
                        <span class="btn__label" data-text="Sfoglia il menù"><span>Sfoglia il menù</span></span>
                    </a>
                </div>
            </div>

            <footer class="drawer__foot" data-cart-foot>
                <div class="free" data-free>
                    <p data-free-text></p>
                    <span class="free__bar"><span data-free-bar></span></span>
                </div>
                <dl class="totals">
                    <div><dt>Subtotale</dt><dd data-cart-subtotal>0,00&nbsp;€</dd></div>
                    <div><dt data-fee-label>Consegna</dt><dd data-cart-fee>0,00&nbsp;€</dd></div>
                    <div class="totals__total"><dt>Totale</dt><dd data-cart-grand>0,00&nbsp;€</dd></div>
                </dl>
                <button class="btn btn--primary btn--lg btn--block" type="button" data-checkout>
                    <span class="btn__label" data-text="Conferma l’ordine"><span>Conferma l’ordine</span></span>
                    <?= icon('arrow-right-line', 'btn__icon') ?>
                    <span class="btn__spinner" aria-hidden="true"></span>
                </button>
                <p class="drawer__note"><?= icon('shield-check-line') ?>Paghi alla consegna o al ritiro, con carta o contanti.</p>
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
            <button class="icon-btn icon-btn--line modal__close" type="button" data-overlay-close aria-label="Chiudi"><?= icon('close-line') ?></button>
            <svg class="tick" viewBox="0 0 72 72" aria-hidden="true" focusable="false">
                <circle cx="36" cy="36" r="33" pathLength="1"/>
                <path d="M23 37.5l9 9 17.5-19" pathLength="1"/>
            </svg>
            <p class="eyebrow eyebrow--center"><span>Grazie<span data-res-name></span></span></p>
            <h2 class="modal__title" id="modal-reservation-title">La tua richiesta di prenotazione è stata ricevuta.</h2>
            <p class="modal__text">Ti confermeremo il tavolo via email entro un’ora. Non vediamo l’ora di accoglierti.</p>
            <ul class="summary" data-res-summary>
                <li><?= icon('calendar-line') ?><span data-res="date"></span></li>
                <li><?= icon('time-line') ?><span data-res="time"></span></li>
                <li><?= icon('group-line') ?><span data-res="guests"></span></li>
                <li><?= icon('map-pin-line') ?><span data-res="seating"></span></li>
            </ul>
            <button class="btn btn--primary" type="button" data-overlay-close>
                <span class="btn__label" data-text="Perfetto"><span>Perfetto</span></span>
            </button>
        </div>
    </div>

    <!-- Order confirmed -->
    <div class="modal" id="modal-order" data-overlay="order" role="dialog" aria-modal="true" aria-labelledby="modal-order-title" aria-hidden="true" inert>
        <div class="modal__backdrop" data-overlay-close></div>
        <div class="modal__dialog">
            <button class="icon-btn icon-btn--line modal__close" type="button" data-overlay-close aria-label="Chiudi"><?= icon('close-line') ?></button>
            <svg class="tick" viewBox="0 0 72 72" aria-hidden="true" focusable="false">
                <circle cx="36" cy="36" r="33" pathLength="1"/>
                <path d="M23 37.5l9 9 17.5-19" pathLength="1"/>
            </svg>
            <p class="eyebrow eyebrow--center"><span>Ordine <span data-order="ref"></span></span></p>
            <h2 class="modal__title" id="modal-order-title">Il tuo ordine è confermato.</h2>
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
            <button class="icon-btn icon-btn--line modal__close" type="button" data-overlay-close aria-label="Chiudi"><?= icon('close-line') ?></button>
            <p class="eyebrow">Informazioni</p>
            <h2 class="modal__title" id="modal-legal-title">Note legali</h2>
            <div class="modal__scroll prose" data-lenis-prevent tabindex="0">
                <h3 id="legal-notice">Editore</h3>
                <p><?= e($site['name']) ?> <?= e($site['descriptor']) ?> · <?= e($site['street']) ?>, <?= e($site['zip']) ?> <?= e($site['city']) ?> · <?= e($site['vat']) ?> · <?= e($site['email']) ?>.</p>
                <p>Questo sito è un prototipo di design. Il ristorante, il menù, i prezzi, le recensioni e i recapiti sono di fantasia; prenotazioni, ordini e iscrizioni alla newsletter sono simulati nel tuo browser e non vengono né salvati né trasmessi.</p>
                <h3 id="legal-privacy">Privacy</h3>
                <p>Non viene raccolto alcun dato personale. Le informazioni inserite nei moduli non lasciano mai il tuo dispositivo e scompaiono alla chiusura della pagina. Caratteri, script e immagini sono serviti esclusivamente da questo sito: non viene effettuata alcuna richiesta a terze parti.</p>
                <h3 id="legal-cookies">Cookie</h3>
                <p>Questo sito non utilizza cookie, tracciatori né strumenti di analisi di alcun tipo. Non c’è nulla da accettare.</p>
                <h3>Crediti</h3>
                <p>Caratteri: Cormorant Garamond e Manrope (SIL Open Font License). Icone: Remix Icon. Animazioni: GSAP, Lenis e Swiper. Fotografie: i piatti, le bevande e la sala a volta sono immagini originali generate per questo prototipo; le altre fotografie sono di autori di Unsplash (Unsplash License).</p>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════════════════════════════════════════════════
         LIGHTBOX
         ══════════════════════════════════════════════════════════════════════ -->
    <div class="lightbox" id="lightbox" data-overlay="lightbox" role="dialog" aria-modal="true" aria-label="Galleria fotografica" aria-hidden="true" inert>
        <div class="lightbox__backdrop" data-overlay-close></div>
        <figure class="lightbox__figure" data-lightbox-figure>
            <img class="lightbox__img" data-lightbox-img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
            <figcaption class="lightbox__caption">
                <span class="lightbox__count" data-lightbox-count></span>
                <span class="lightbox__label" data-lightbox-label></span>
            </figcaption>
        </figure>
        <button class="icon-btn icon-btn--line lightbox__close" type="button" data-overlay-close aria-label="Chiudi la galleria"><?= icon('close-line') ?></button>
        <button class="icon-btn icon-btn--line lightbox__nav lightbox__nav--prev" type="button" data-lightbox-prev aria-label="Fotografia precedente"><?= icon('arrow-left-line') ?></button>
        <button class="icon-btn icon-btn--line lightbox__nav lightbox__nav--next" type="button" data-lightbox-next aria-label="Fotografia successiva"><?= icon('arrow-right-line') ?></button>
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
                    <button class="qty__btn qty__btn--dec" type="button" data-dec aria-label="Diminuisci la quantità"><?= icon('subtract-line') ?></button>
                    <output class="qty__value">1</output>
                    <button class="qty__btn qty__btn--inc" type="button" data-inc aria-label="Aumenta la quantità"><?= icon('add-line') ?></button>
                </div>
            </div>
            <div class="cart-item__side">
                <strong class="cart-item__total"></strong>
                <button class="cart-item__remove" type="button" data-remove aria-label="Rimuovi dal carrello"><?= icon('delete-bin-line') ?></button>
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
