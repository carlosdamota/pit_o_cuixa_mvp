<?php

header('Content-Type: application/json; charset=utf-8');

// ==========================================
// 1. GET USER MESSAGE
// ==========================================
// Accept the message as a GET query param (provisional GET endpoint) or,
// falling back, from a JSON POST body.
$rawMessage = trim($_GET['message'] ?? '');
if ($rawMessage === '') {
    $data = json_decode(file_get_contents('php://input'), true);
    $rawMessage = trim($data['message'] ?? '');
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
            'ca'  => ['carta', 'menú', 'preu', 'preus', 'pollastre', 'combos', 'croquetes', 'canelons', 'beguda', 'begudes', 'amanida', 'fideuà', 'paella', 'menjar'],
            'es'  => ['menu', 'menú', 'carta', 'precio', 'precios', 'pollo', 'combos', 'croquetas', 'paella', 'bebida', 'bebidas', 'ensalada', 'comida'],
            'en'  => ['menu', 'food', 'price', 'prices', 'chicken', 'combos', 'paella', 'drink', 'drinks', 'salad', 'what do you have'],
            'uk'  => ['меню', 'страви', 'ціни', 'курка', 'комбо', 'крокети', 'напій', 'салат', 'їжа'],
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
// 4. MAIN LOGIC
// ==========================================
$lang   = detectLanguage($userMessage);
$intent = detectIntent($userMessage, $lang);

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

    'menu' => [
        'ca' => "🍗 **Carta completa – Pit o Cuixa**\n\n" .
                "**Combos de Pollastre a l'ast:**\n" .
                "- Pollastre sencer: **16,90€**\n" .
                "- Pollastre + Patates + Allioli: **22,90€**\n" .
                "- Pollastre + Amanida + Allioli: **22,90€**\n" .
                "- Pollastre + Patates + 1 Plat Preparat (o 6 Croquetes) + Allioli: **27,90€**\n" .
                "- Pollastre + 6 Canelons + Patates + Allioli: **28,50€**\n" .
                "- Pollastre + Patates + 12 Croquetes + Allioli: **31,90€**\n" .
                "- Pollastre + Patates + 6 Croquetes + Amanida Russa + Allioli: **31,90€**\n" .
                "- Pollastre + Patates + 2 Plats Preparats + Allioli: **31,90€**\n\n" .
                "🍽️ **Menú Diari (Dilluns a Divendres):**\n" .
                "- 2 Plats Preparats + Beguda: **12,50€**\n\n" .
                "🥟 **Croquetes (6 unitats):**\n" .
                "- Pollastre Rostit / Bolets i Tòfona / Formatge Cabrales: **7,90€**\n\n" .
                "🍟 **Patates:**\n" .
                "- Casolanes: **5,80€** | Especials: **7,00€** | Al Forn: **2,00€**\n\n" .
                "🍗 **Plats Preparats:**\n" .
                "- Aletes (8 ud): **8,00€** | Aletes BBQ (8 ud): **7,50€**\n" .
                "- Nuggets (8 ud): **6,50€** | Chicken Bites (12 ud): **6,00€**\n" .
                "- Chicken Fingers: **7,80€** | Canelons: **8,00€**\n" .
                "- Callos: **8,00€** | Fideuà: **7,50€** | Pollastre al Curri: **6,50€**\n" .
                "- Albergínia Farcida: **5,50€** | Pasta Bolonyesa: **5,50€**\n\n" .
                "🥗 **Amanides:**\n" .
                "- Cèsar: **7,90€** | Russa amb Tonyina: **6,90€** | Pasta amb Feta: **5,95€**\n\n" .
                "🥤 **Begudes:**\n" .
                "- Coca-Cola / Coca-Cola Zero / Nestea: **2,00€**\n" .
                "- Aquarius: **2,10€** | Estrella Galicia: **2,00€**\n" .
                "- Aigua 1,5L: **3,00€**\n" .
                "- Vi Negre de la Casa: **6,00€** | Verdejo: **7,00€** | Lambrusco: **7,00€**\n" .
                "- Ampolla Peñalosa: **8,00€**",

        'es' => "🍗 **Carta completa – Pit o Cuixa**\n\n" .
                "**Combos Pollo a l'ast:**\n" .
                "- Pollo entero: **16,90€**\n" .
                "- Pollo + Patatas + Alioli: **22,90€**\n" .
                "- Pollo + Ensalada + Alioli: **22,90€**\n" .
                "- Pollo + Patatas + 1 Plato Preparado (o 6 Croquetas) + Alioli: **27,90€**\n" .
                "- Pollo + 6 Canelones + Patatas + Alioli: **28,50€**\n" .
                "- Pollo + Patatas + 12 Croquetas + Alioli: **31,90€**\n" .
                "- Pollo + Patatas + 6 Croquetas + Ensaladilla Rusa + Alioli: **31,90€**\n" .
                "- Pollo + Patatas + 2 Platos Preparados + Alioli: **31,90€**\n\n" .
                "🍽️ **Menú Diario (Lunes a Viernes):**\n" .
                "- 2 Platos Preparados + Bebida: **12,50€**\n\n" .
                "🥟 **Croquetas (6 unidades):**\n" .
                "- Pollo Asado / Setas y Trufa / Queso Cabrales: **7,90€**\n\n" .
                "🍟 **Patatas:**\n" .
                "- Caseras: **5,80€** | Especiales: **7,00€** | Al Horno: **2,00€**\n\n" .
                "🍗 **Comidas Preparadas:**\n" .
                "- Alitas (8 ud): **8,00€** | Alitas BBQ (8 ud): **7,50€**\n" .
                "- Nuggets (8 ud): **6,50€** | Chicken Bites (12 ud): **6,00€**\n" .
                "- Chicken Fingers: **7,80€** | Canelones: **8,00€**\n" .
                "- Callos: **8,00€** | Fideuà: **7,50€** | Pollo al Curry: **6,50€**\n" .
                "- Berenjena Rellena: **5,50€** | Pasta Boloñesa: **5,50€**\n\n" .
                "🥗 **Ensaladas:**\n" .
                "- César: **7,90€** | Rusa con Atún: **6,90€** | Pasta con Feta: **5,95€**\n\n" .
                "🥤 **Bebidas:**\n" .
                "- Coca-Cola / Coca-Cola Zero / Nestea: **2,00€**\n" .
                "- Aquarius: **2,10€** | Estrella Galicia: **2,00€**\n" .
                "- Agua 1,5L: **3,00€**\n" .
                "- Vino Tinto de la Casa: **6,00€** | Verdejo: **7,00€** | Lambrusco: **7,00€**\n" .
                "- Botella Peñalosa: **8,00€**",

        'en' => "🍗 **Full Menu – Pit o Cuixa**\n\n" .
                "**Rotisserie Chicken Combos:**\n" .
                "- Whole Rotisserie Chicken: **€16.90**\n" .
                "- Chicken + Fries + Alioli: **€22.90**\n" .
                "- Chicken + Salad + Alioli: **€22.90**\n" .
                "- Chicken + Fries + 1 Prepared Dish or 6 Croquettes + Alioli: **€27.90**\n" .
                "- Chicken + 6 Cannelloni + Fries + Alioli: **€28.50**\n" .
                "- Chicken + Fries + 12 Croquettes + Alioli: **€31.90**\n" .
                "- Chicken + Fries + 6 Croquettes + Russian Salad + Alioli: **€31.90**\n" .
                "- Chicken + Fries + 2 Prepared Dishes + Alioli: **€31.90**\n\n" .
                "🍽️ **Daily Menu (Monday–Friday):**\n" .
                "- 2 Prepared Dishes + Drink: **€12.50**\n\n" .
                "🥟 **Croquettes (6 pieces):**\n" .
                "- Roast Chicken / Mushroom & Truffle / Cabrales Cheese: **€7.90**\n\n" .
                "🍟 **Potatoes:**\n" .
                "- Homemade Fries: **€5.80** | Special Fries: **€7.00** | Baked Potato: **€2.00**\n\n" .
                "🍗 **Prepared Foods:**\n" .
                "- Chicken Wings (8): **€8.00** | BBQ Wings (8): **€7.50**\n" .
                "- Chicken Nuggets (8): **€6.50** | Chicken Bites (12): **€6.00**\n" .
                "- Chicken Fingers: **€7.80** | Cannelloni: **€8.00**\n" .
                "- Callos: **€8.00** | Fideuà: **€7.50** | Chicken Curry Rice: **€6.50**\n" .
                "- Stuffed Eggplant: **€5.50** | Pasta Bolognese: **€5.50**\n\n" .
                "🥗 **Salads:**\n" .
                "- Caesar Salad: **€7.90** | Russian Salad with Tuna: **€6.90** | Pasta Salad with Feta: **€5.95**\n\n" .
                "🥤 **Drinks:**\n" .
                "- Coca-Cola / Coca-Cola Zero / Nestea: **€2.00**\n" .
                "- Aquarius: **€2.10** | Estrella Galicia (Can): **€2.00**\n" .
                "- Water (1.5 L): **€3.00**\n" .
                "- House Red Wine: **€6.00** | Verdejo: **€7.00** | Lambrusco: **€7.00**\n" .
                "- Peñalosa Bottle: **€8.00**",

        'uk' => "🍗 **Повне меню – Pit o Cuixa**\n\n" .
                "**Комбінації з куркою на рожні:**\n" .
                "- Ціла курка: **16,90€**\n" .
                "- Курка + Картопля + Айолі: **22,90€**\n" .
                "- Курка + Салат + Айолі: **22,90€**\n" .
                "- Курка + Картопля + 1 Готова страва (або 6 Крокетів) + Айолі: **27,90€**\n" .
                "- Курка + 6 Канолонів + Картопля + Айолі: **28,50€**\n" .
                "- Курка + Картопля + 12 Крокетів + Айолі: **31,90€**\n" .
                "- Курка + Картопля + 6 Крокетів + Російський салат + Айолі: **31,90€**\n" .
                "- Курка + Картопля + 2 Готові страви + Айолі: **31,90€**\n\n" .
                "🍽️ **Щоденне меню (понеділок–п’ятниця):**\n" .
                "- 2 Готові страви + Напій: **12,50€**\n\n" .
                "🥟 **Крокети (6 штук):**\n" .
                "- Смажена курка / Гриби та трюфель / Сир Кабралес: **7,90€**\n\n" .
                "🍟 **Картопля:**\n" .
                "- Домашня: **5,80€** | Спеціальна: **7,00€** | Запечена: **2,00€**\n\n" .
                "🍗 **Готові страви:**\n" .
                "- Крильця (8 шт): **8,00€** | Крильця BBQ (8 шт): **7,50€**\n" .
                "- Нагетси (8 шт): **6,50€** | Chicken Bites (12 шт): **6,00€**\n" .
                "- Chicken Fingers: **7,80€** | Канолони: **8,00€**\n" .
                "- Кальос: **8,00€** | Фідеуа: **7,50€** | Курка з каррі: **6,50€**\n" .
                "- Фаршировані баклажани: **5,50€** | Паста болоньєзе: **5,50€**\n\n" .
                "🥗 **Салати:**\n" .
                "- Цезар: **7,90€** | Російський з тунцем: **6,90€** | Паста з фетою: **5,95€**\n\n" .
                "🥤 **Напої:**\n" .
                "- Coca-Cola / Coca-Cola Zero / Nestea: **2,00€**\n" .
                "- Aquarius: **2,10€** | Estrella Galicia: **2,00€**\n" .
                "- Вода 1,5 л: **3,00€**\n" .
                "- Домашнє червоне вино: **6,00€** | Verdejo: **7,00€** | Lambrusco: **7,00€**\n" .
                "- Пляшка Peñalosa: **8,00€**",
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

// ── Main logic ──────────────────────────────────────────────────────────
if ($intent === 'faq') {
    // Explicit FAQ request: always answer from the real FAQ source.
    $reply = buildFaqReply($lang, $userMessage) ?: ($fallbacks[$lang] ?? $fallbacks['en']);
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