<?php

header('Content-Type: application/json; charset=utf-8');

// Ensure bootstrap (autoloader, Config, DB connection, LANG) is loaded.
// Reached through the router (public/index.php) this is already done, so the
// guard keeps the require idempotent. It also makes this file safe to run
// standalone (e.g. direct CLI) where bootstrap has not been loaded yet.
if (!defined('LANG')) {
    require_once __DIR__ . '/../../shared/bootstrap.php';
}

// ==========================================
// 1. GET USER MESSAGE
// ==========================================
// Accept the message as a GET query param (provisional GET endpoint) or,
// falling back, from a JSON POST body. Validate the type: untrusted input
// arrives as JSON and could be an array/object, which would make trim() throw.
$rawMessage = is_string($_GET['message'] ?? null) ? trim($_GET['message']) : '';
if ($rawMessage === '') {
    $data = json_decode(file_get_contents('php://input'), true);
    $msg  = is_array($data) ? ($data['message'] ?? null) : null;
    $rawMessage = is_string($msg) ? trim($msg) : '';
}
$userMessage = mb_strtolower($rawMessage, 'UTF-8');

if ($userMessage === '') {
    echo json_encode([
        'reply' => "Please type a question. / Por favor, escribe una pregunta."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================
// 2. LANGUAGE DETECTION
// ==========================================
// The chatbot speaks the SAME locales as the website: ca, es, en, uk.
function detectLanguage(string $msg): string {
    // Ukrainian script (Cyrillic) is unambiguous.
    if (preg_match('/[\x{0400}-\x{04FF}]/u', $msg)) return 'uk';
    // Catalan keywords (checked before Spanish; 'hola' is shared, default 'es' still catches Spanish).
    if (preg_match('/\b(horari|horaris|obert|oberta|tancat|tancada|adreça|telèfon|lloc|on és|on esteu|esteu|ubicats|on sou|repartiment|repart|menú|carta|hola|gràcies|ajuda|faq|pregunta|dubte|al·lèrgia|targeta|pagament|reserva|reservar|quan|obren)\b/u', $msg)) return 'ca';
    // English keywords.
    if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|hours|open|opening|closed|menu|food|chicken|price|prices|address|location|phone|delivery|where|when|thanks|thank you|help|what can you|question|questions|allergy|allergies|gluten|celiac|payment|pay|reserve|booking|order|vegetarian|card|faq)\b/u', $msg)) return 'en';
    // Spanish keywords (and default).
    if (preg_match('/\b(hola|buenos|buenas|horario|horarios|abre|abren|abierto|cerrado|hora|dias|días|cuando|donde|dónde|direccion|dirección|ubicacion|ubicación|telefono|teléfono|contacto|llamar|reparto|domicilio|envio|envío|portes|zonas|menu|precio|precios|pollo|combos|croquetas|bebida|bebidas|ensalada|comida|gracias|ayuda|pregunta|preguntas|duda|dudas|alergia|gluten|pago|pagar|reserva|reservar|tarjeta)\b/u', $msg)) return 'es';
    return 'es';
}

// ==========================================
// 3. INTENT DETECTION
// ==========================================
function detectIntent(string $msg, string $lang): ?string {
    $keywords = [
        'hours' => [
            'ca'  => ['horari', 'horaris', 'obert', 'oberta', 'tancat', 'tancada', 'hora', 'obren', 'quan'],
            'es'  => ['horario', 'horarios', 'abre', 'abren', 'abierto', 'cerrado', 'hora', 'dias', 'días', 'cuando'],
            'en'  => ['hours', 'open', 'opening', 'close', 'closed', 'time', 'when'],
            'uk'  => ['графік', 'розклад', 'години', 'відчинені', 'працюєте', 'відкрито', 'закрито', 'коли'],
        ],
        'location' => [
            'ca'  => ['on és', 'on esteu', 'esteu', 'ubicats', 'adreça', 'direcció', 'ubicació', 'telèfon', 'contacte', 'trucar', 'lloc'],
            'es'  => ['donde', 'dónde', 'direccion', 'dirección', 'ubicacion', 'ubicación', 'telefono', 'teléfono', 'contacto', 'llamar'],
            'en'  => ['where', 'address', 'location', 'phone', 'call', 'contact', 'number'],
            'uk'  => ['де', 'адреса', 'розташування', 'телефон', 'подзвонити', 'контакт', 'номер'],
        ],
        'delivery' => [
            'ca'  => ['repartiment', 'repart', 'domicili', 'enviament', 'portes', 'zones', 'lliurament'],
            'es'  => ['reparto', 'domicilio', 'envio', 'envío', 'portes', 'zonas', 'entrega'],
            'en'  => ['delivery', 'shipping', 'area', 'zones', 'deliver'],
            'uk'  => ['доставка', 'доставляєте', 'зони', 'доставки'],
        ],
        'menu' => [
            'ca'  => ['carta', 'menú', 'menús', 'plats', 'principals', 'entrants', 'pica-pica', 'beguda', 'begudes', 'postres', 'altres', 'preu', 'preus', 'pollastre', 'combos', 'croquetes', 'canelons', 'amanida', 'fideuà', 'paella', 'menjar'],
            'es'  => ['menu', 'menú', 'menús', 'carta', 'platos', 'principal', 'entrantes', 'picoteo', 'bebida', 'bebidas', 'postres', 'otros', 'precio', 'precios', 'pollo', 'combos', 'croquetas', 'paella', 'ensalada', 'comida'],
            'en'  => ['menu', 'menus', 'carta', 'main dishes', 'mains', 'starters', 'drinks', 'desserts', 'others', 'food', 'price', 'prices', 'chicken', 'combos', 'paella', 'drink', 'salad', 'what do you have'],
            'uk'  => ['меню', 'основні', 'закуски', 'клювання', 'напої', 'десерти', 'інші', 'страви', 'ціни', 'курка', 'комбо', 'крокети', 'напій', 'салат', 'їжа'],
        ],
        'greeting' => [
            'ca'  => ['hola', 'bon dia', 'bona tarda', 'bona nit', 'hey'],
            'es'  => ['hola', 'buenos', 'buenas', 'hey'],
            'en'  => ['hello', 'hi', 'hey', 'good morning', 'good afternoon'],
            'uk'  => ['привіт', 'вітаю', 'добрий день', 'доброго дня'],
        ],
        'thanks' => [
            'ca'  => ['gràcies', 'merci', 'moltes gràcies'],
            'es'  => ['gracias', 'merci', 'thanks', 'thank'],
            'en'  => ['thanks', 'thank you', 'thank', 'thx'],
            'uk'  => ['дякую', 'спасибі'],
        ],
        'faq' => [
            'ca'  => ['faq', 'pregunta', 'preguntes', 'dubte', 'dubtes', 'al·lèrgia', 'al·lèrgies', 'gluten', 'celíac', 'celiac', 'pagament', 'pagar', 'reserva', 'reservar', 'encarregar', 'demanar', 'vegan', 'vegetarià', 'targeta', 'tarjeta'],
            'es'  => ['faq', 'pregunta', 'preguntas', 'duda', 'dudas', 'alergia', 'alergias', 'gluten', 'celiac', 'celíac', 'pago', 'pagos', 'pagar', 'reserva', 'reservar', 'encargar', 'pedir', 'vegano', 'vegetariano', 'tarjeta'],
            'en'  => ['faq', 'question', 'questions', 'doubt', 'allergy', 'allergies', 'gluten', 'celiac', 'payment', 'payments', 'pay', 'reserve', 'booking', 'order', 'vegetarian', 'card'],
            'uk'  => ['faq', 'питання', 'запитання', 'алергія', 'алергії', 'глютен', 'целіакія', 'оплата', 'платіж', 'картка', 'бронювання', 'замовлення', 'веган', 'вегетаріанський'],
        ],
        'help' => [
            'ca'  => ['ajuda', 'ajudar', 'opcions', 'què pots'],
            'es'  => ['ayuda', 'help', 'ayudar', 'opciones', 'qué puedes'],
            'en'  => ['help', 'options', 'what can you', 'commands'],
            'uk'  => ['допомога', 'допоможіть', 'опції', 'що ви можете'],
        ],
    ];

    $bestIntent = null;
    $bestScore = 0;

    foreach ($keywords as $intent => $langs) {
        $words = $langs[$lang] ?? $langs['en'] ?? [];
        $score = 0;
        foreach ($words as $word) {
            if (mb_strpos($msg, $word) !== false) {
                $score++;
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestIntent = $intent;
        }
    }

    return $bestScore > 0 ? $bestIntent : null;
}

// ==========================================
// 3b. FAQ-vs-CATALOG ROUTING
// ==========================================
// A QUESTION (starts with an interrogative word, or contains '?') that also
// mentions a FAQ-domain topic must be routed to the FAQ source rather than the
// broad `menu` intent. Example: "qué incluye el pollo a l'ast" contains
// "incluy" → answered by the specific FAQ item, not the full carta dump.
function routeFaqVsCatalog(string $msg, string $lang): ?string
{
    $interrogatives = [
        'qué', 'que', 'cómo', 'como', 'cuándo', 'cuando', 'dónde', 'donde',
        'por qué', 'por que', 'cuál', 'cual',
        'what', 'how', 'when', 'where', 'why', 'which',
        'як', 'що', 'де', 'коли', 'чи',
    ];

    $isQuestion = str_contains($msg, '?');
    if (!$isQuestion) {
        foreach ($interrogatives as $word) {
            if (str_starts_with($msg, $word)) {
                $isQuestion = true;
                break;
            }
        }
    }

    if (!$isQuestion) {
        return null;
    }

    // Language-tolerant FAQ-domain terms. If the question mentions any of these,
    // the FAQ source (which holds the real, curated answers) is preferred.
    $faqTerms = [
        'incluy', 'inclou', 'includes',
        'contiene', 'contingut', 'contains',
        'proced', 'procede',
        'garant', 'guarantee',
        'alerg', 'алерг',
        'gluten',
        'celíac', 'celiac', 'целіак',
        'repart', 'repartiment', 'delivery', 'доставк',
        'horari', 'horario', 'hours',
        'ubic', 'адреса', 'location',
        'reserv', 'брон',
        'pag', 'pago', 'pagar', 'payment', 'оплат',
        'tarjet', 'targeta', 'tarjeta', 'card', 'картк',
        'vegan', 'веган',
        'vegetari', 'вегетар',
        'opcions', 'opciones', 'options',
        'instal·lacions', 'instalaciones', 'instal',
    ];

    foreach ($faqTerms as $term) {
        if (mb_strpos($msg, $term) !== false) {
            return 'faq';
        }
    }

    return null;
}

// ==========================================
// 4. MAIN LOGIC
// ==========================================
$lang   = detectLanguage($userMessage);
$intent = detectIntent($userMessage, $lang);

// Part A — a question about a FAQ-domain topic is upgraded to the `faq` intent
// so it reaches buildFaqReply() instead of the broad `menu` carta. We only keep
// the upgrade when a concrete FAQ item actually matches this question: if the
// forced FAQ routing has no real answer, we fall back to the original intent
// (catalog / hours / location / delivery) so well-handled replies are never
// downgraded to generic FAQ suggestions (e.g. a "qué horario" question).
$faqOverride = routeFaqVsCatalog($userMessage, $lang);
if ($faqOverride !== null && buildFaqReply($lang, $userMessage, true) !== null) {
    $intent = $faqOverride;
}

// Answer using the REAL site i18n for the detected language when possible.
// Files are pure `return [...]` arrays — safe to require repeatedly.
$enStrings = require __DIR__ . '/../../shared/i18n/en.php';
$i18nFile  = __DIR__ . '/../../shared/i18n/' . $lang . '.php';
if ($lang !== 'en' && is_file($i18nFile)) {
    // Detected locale wins; English fills any missing key (never an empty string).
    $siteI18n = array_merge($enStrings, require $i18nFile);
} else {
    $siteI18n = $enStrings;
}

$phone = class_exists('Config') ? Config::phone() : '+34 977 64 20 10';

// ==========================================
// 5. ALL RESPONSES (buckets: ca, es, en, uk)
// ==========================================
$responses = [

    'hours' => [
        'ca' => "🕒 **Horari d'obertura:**\n" .
                $siteI18n['home.info.hours'],

        'es' => "🕒 **Horario de apertura:**\n" .
                $siteI18n['home.info.hours'],

        'en' => "🕒 **Opening Hours:**\n" .
                $siteI18n['home.info.hours'],

        'uk' => "🕒 **Графік роботи:**\n" .
                $siteI18n['home.info.hours'],
    ],

    'location' => [
        'ca' => "📍 **Ubicació i contacte:**\n" .
                "- **Adreça:** " . $siteI18n['home.info.address'] . "\n" .
                "- **Telèfon:** " . $phone,

        'es' => "📍 **Ubicación y Contacto:**\n" .
                "- **Dirección:** " . $siteI18n['home.info.address'] . "\n" .
                "- **Teléfono:** " . $phone,

        'en' => "📍 **Location & Contact:**\n" .
                "- **Address:** " . $siteI18n['home.info.address'] . "\n" .
                "- **Phone:** " . $phone,

        'uk' => "📍 **Розташування та контакти:**\n" .
                "- **Адреса:** " . $siteI18n['home.info.address'] . "\n" .
                "- **Телефон:** " . $phone,
    ],

    'delivery' => [
        'ca' => "🚚 **" . $siteI18n['menu.map.title'] . ":**\n" .
                $siteI18n['menu.map.delivery_note'],

        'es' => "🚚 **" . $siteI18n['menu.map.title'] . ":**\n" .
                $siteI18n['menu.map.delivery_note'],

        'en' => "🚚 **" . $siteI18n['menu.map.title'] . ":**\n" .
                $siteI18n['menu.map.delivery_note'],

        'uk' => "🚚 **" . $siteI18n['menu.map.title'] . ":**\n" .
                $siteI18n['menu.map.delivery_note'],
    ],

    'greeting' => [
        'ca' => "👋 Hola! Benvingut/da a **Pit o Cuixa**.\n\nPots preguntar-me per la carta, els horaris, el repartiment o la ubicació.",
        'es' => "👋 ¡Hola! Bienvenido a **Pit o Cuixa**.\n\nPuedes preguntarme por el menú, horarios, reparto o ubicación.",
        'en' => "👋 Hello! Welcome to **Pit o Cuixa**.\n\nYou can ask me about the menu, opening hours, delivery or location.",
        'uk' => "👋 Привіт! Ласкаво просимо до **Pit o Cuixa**.\n\nВи можете запитати мене про меню, графік роботи, доставку чи розташування.",
    ],

    'thanks' => [
        'ca' => "De res! 😊 Si necessites qualsevol altra cosa, aquí estic.\n\nTambé pots trucar al **+34 977 64 20 10**.",
        'es' => "¡De nada! 😊 Si necesitas algo más, aquí estoy.\n\nTambién puedes llamar al **+34 977 64 20 10**.",
        'en' => "You're welcome! 😊 If you need anything else, just ask.\n\nYou can also call **+34 977 64 20 10**.",
        'uk' => "Будь ласка! 😊 Якщо вам потрібно ще щось, я тут.\n\nТакож можете зателефонувати нам: **+34 977 64 20 10**.",
    ],

    'help' => [
        'ca' => "Et puc ajudar amb:\n• Horaris\n• Ubicació i telèfon\n• Zones de repartiment\n• Carta i preus\n\nEscriu una d'aquestes paraules o truca al **+34 977 64 20 10**.",
        'es' => "Puedo ayudarte con:\n• Horarios\n• Ubicación y teléfono\n• Zonas de reparto\n• Menú y precios\n\nEscribe una de estas palabras o llama al **+34 977 64 20 10**.",
        'en' => "I can help you with:\n• Opening hours\n• Location & phone\n• Delivery areas\n• Menu & prices\n\nJust type one of these or call **+34 977 64 20 10**.",
        'uk' => "Я можу допомогти вам з:\n• Графіком роботи\n• Розташуванням і телефоном\n• Зонами доставки\n• Меню та цінами\n\nНапишіть одне з цих питань або зателефонуйте: **+34 977 64 20 10**.",
    ],
];

// Fallback reply per site locale (defined here so both the FAQ block and the
// main logic below can reuse it).
$fallbacks = [
    'ca' => "Ho sento, no he entès la teva pregunta.\n\nEt puc ajudar amb: **horaris**, **ubicació**, **repartiment** o **carta**.\n\nTambé pots trucar al **+34 977 64 20 10**.",
    'es' => "Lo siento, no he entendido tu pregunta.\n\nPuedo ayudarte con: **horarios**, **ubicación**, **reparto** o **menú**.\n\nTambién puedes llamar al **+34 977 64 20 10**.",
    'en' => "Sorry, I didn't understand your question.\n\nI can help you with: **hours**, **location**, **delivery** or **menu**.\n\nYou can also call **+34 977 64 20 10**.",
    'uk' => "Вибачте, я не зрозумів вашого питання.\n\nЯ можу допомогти з: **графіком**, **розташуванням**, **доставкою** або **меню**.\n\nТакож можете зателефонувати нам: **+34 977 64 20 10**.",
];

// FAQ reply: compose from the SAME i18n source the /faq page renders,
// so the chatbot never drifts from the site's real FAQ content.
// Returns a specific FAQ answer, or the FAQ index, or null when $specificOnly
// and there is no real FAQ overlap.
function buildFaqReply(string $faqLang, string $userMessage, bool $specificOnly = false): ?string
{
    $faqLocaleMap = [
        'ca' => 'ca',
        'es' => 'es',
        'en' => 'en',
        'uk' => 'uk',
    ];

    $faqLocale = $faqLocaleMap[$faqLang] ?? 'en';
    $faqFile   = __DIR__ . '/../../shared/i18n/' . $faqLocale . '.php';

    if (!is_file($faqFile)) {
        return null;
    }

    $faqStrings = require $faqFile;
    $faqItems   = $faqStrings['faq.items'] ?? [];

    if ($faqItems === []) {
        return null;
    }

    // Try to match the user's question to a specific FAQ item.
    $bestIndex = null;
    $bestScore = 0;
    $msgWords  = preg_split('/\s+/u', mb_strtolower($userMessage, 'UTF-8')) ?: [];

    foreach ($faqItems as $index => $item) {
        $qWords = preg_split('/\s+/u', mb_strtolower($item['q'], 'UTF-8')) ?: [];
        $score  = count(array_intersect($msgWords, $qWords));

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestIndex = $index;
        }
    }

    // A real overlap (>= 2 shared words) is a targeted match; otherwise list the FAQ index.
    if ($bestIndex !== null && $bestScore >= 2) {
        $item  = $faqItems[$bestIndex];
        return "**" . $item['q'] . "**\n\n" . $item['a'];
    }

    if ($specificOnly) {
        return null;
    }

    $faqTitle = $faqStrings['faq.title'] ?? 'FAQ';
    $lines    = ["❓ **" . $faqTitle . ":**\n"];
    foreach ($faqItems as $index => $item) {
        $lines[] = ($index + 1) . ". **" . $item['q'] . "**";
    }
    $faqPrompts = [
        'ca' => "\nEscriu la pregunta que t'interessi i et respondré amb més detall. 😊",
        'es' => "\nEscribe la pregunta que te interese y te respondo con más detalle. 😊",
        'en' => "\nType the question you're interested in and I'll answer in more detail. 😊",
        'uk' => "\nНапишіть питання, яке вас цікавить, і я відповім детальніше. 😊",
    ];
    $lines[] = $faqPrompts[$faqLocale] ?? $faqPrompts['en'];

    return implode("\n", $lines);
}

// ── Section-aware menu reply (from the DATABASE) ──────────────────────
// Replaces the hardcoded $responses['menu'] string. Returns only the
// requested sections, or the full menu when the request is generic.
function buildMenuReply(string $lang, string $userMessage): string
{
    $groups = \Pit\Cuixa\Backend\Api\Menu::groups($lang);

    // Map each section slug to its localized aliases (matched case-insensitively).
    // NOTE: singular "menú"/"menu" and "carta" are intentionally EXCLUDED from
    // the specific triggers — they are the generic "show me everything" words.
    // Only the plural / Ukrainian forms target the Menú section specifically, so
    // "menús" → Menú only, while "muéstrame la carta" → full menu.
    $sectionAliases = [
        'menus'    => ['menús', 'menus', 'меню'],
        'platos'   => ['platos', 'principal', 'principals', 'plats', 'main dishes', 'mains'],
        'entrantes' => ['entrantes', 'picoteo', 'entrants', 'pica-pica', 'starters'],
        'bebidas'  => ['bebidas', 'begudes', 'drinks'],
        'postres'  => ['postres', 'desserts'],
        'otros'    => ['otros', 'altres', 'others'],
    ];

    $msg     = $userMessage;
    $matched = [];

    foreach ($groups as $group) {
        $slug    = (string) ($group['slug'] ?? '');
        $isMatch = $slug !== '' && mb_strpos($msg, $slug) !== false;

        if (!$isMatch) {
            foreach ($sectionAliases[$slug] ?? [] as $alias) {
                if (mb_strpos($msg, $alias) !== false) {
                    $isMatch = true;
                    break;
                }
            }
        }

        if ($isMatch) {
            $matched[] = $group;
        }
    }

    // Generic browse ("carta", "menú", "muéstrame la carta", or any request that
    // names no specific section) → return the full menu.
    $sections = $matched !== [] ? $matched : $groups;

    $lines = [];
    foreach ($sections as $group) {
        $sectionName = (string) ($group['name'] ?? '');
        $lines[]     = "🍗 **" . $sectionName . "**";

        foreach ($group['items'] ?? [] as $item) {
            $name  = (string) ($item['name'] ?? '');
            $price = isset($item['price']) ? number_format((float) $item['price'], 2, '.', '') : '';
            $desc  = (string) ($item['description'] ?? '');

            $line = "- " . $name . ": **" . $price . "€**";
            if ($desc !== '') {
                $line .= " — " . $desc;
            }
            $lines[] = $line;
        }

        $lines[] = "";
    }

    return trim(implode("\n", $lines));
}

// ── Main logic ──────────────────────────────────────────────────────────
if ($intent === 'faq') {
    // Explicit FAQ request: always answer from the real FAQ source.
    $reply = buildFaqReply($lang, $userMessage) ?: ($fallbacks[$lang] ?? $fallbacks['en']);
} elseif ($intent === 'menu') {
    // Section-aware reply built from the live database catalog.
    $reply = buildMenuReply($lang, $userMessage);
} elseif ($intent && isset($responses[$intent][$lang])) {
    $reply = $responses[$intent][$lang];
} elseif ($intent && isset($responses[$intent]['en'])) {
    $reply = $responses[$intent]['en'];
} else {
    // No intent: before giving up, try a FAQ-specific match (real overlap
    // only, never the index) so natural questions still get real answers.
    $reply = buildFaqReply($lang, $userMessage, true);
    if ($reply === null) {
        $reply = $fallbacks[$lang] ?? $fallbacks['en'];
    }
}

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);