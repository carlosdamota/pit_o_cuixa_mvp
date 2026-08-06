<?php

header('Content-Type: application/json; charset=utf-8');

// ==========================================
// 1. GET USER MESSAGE
// ==========================================
$data = json_decode(file_get_contents('php://input'), true);
$rawMessage = trim($data['message'] ?? '');
$userMessage = mb_strtolower($rawMessage, 'UTF-8');

if ($userMessage === '') {
    echo json_encode([
        'reply' => "Please type a question. / Por favor, escribe una pregunta."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================
// 2. ALL RESPONSES
// ==========================================
$responses = [

    'hours' => [
        'es' => "🕒 **Horarios de apertura:**\n" .
                "- Lunes, Martes, Jueves y Viernes: 09:00 - 15:30\n" .
                "- Miércoles: CERRADO ❌\n" .
                "- Sábados y Domingos: 09:00 - 16:30\n" .
                "- Reparto: 12:30 - 16:00",

        'en' => "🕒 **Opening Hours:**\n" .
                "- Mon, Tue, Thu, Fri: 09:00 - 15:30\n" .
                "- Wednesday: CLOSED ❌\n" .
                "- Sat & Sun: 09:00 - 16:30\n" .
                "- Delivery Hours: 12:30 - 16:00",

        'zh' => "🕒 **营业时间:**\n" .
                "- 周一、周二、周四、周五: 09:00 - 15:30\n" .
                "- 周三: 休息 ❌\n" .
                "- 周六、周日: 09:00 - 16:30\n" .
                "- 外卖派送时间: 12:30 - 16:00",

        'hi' => "🕒 **खुलने का समय:**\n" .
                "- सोमवार, मंगलवार, गुरुवार, शुक्रवार: 09:00 - 15:30\n" .
                "- बुधवार: बंद ❌\n" .
                "- शनिवार और रविवार: 09:00 - 16:30\n" .
                "- डिलीवरी का समय: 12:30 - 16:00",

        'fr' => "🕒 **Heures d'ouverture:**\n" .
                "- Lun, Mar, Jeu, Ven: 09:00 - 15:30\n" .
                "- Mercredi: FERMÉ ❌\n" .
                "- Sam & Dim: 09:00 - 16:30\n" .
                "- Livraison: 12:30 - 16:00",

        'pcm' => "🕒 **Time Wey We Dey Open:**\n" .
                 "- Mon, Tue, Thu, Fri: 09:00 - 15:30\n" .
                 "- Wednesday: WE CLOSING ❌\n" .
                 "- Sat & Sun: 09:00 - 16:30\n" .
                 "- Delivery time: 12:30 - 16:00",
    ],

    'location' => [
        'es' => "📍 **Ubicación y Contacto:**\n" .
                "- **Dirección:** Carrer Major, 25, 43800 Torredembarra, Tarragona\n" .
                "- **Teléfono:** +34 977 64 20 10",

        'en' => "📍 **Location & Contact:**\n" .
                "- **Address:** Carrer Major, 25, 43800 Torredembarra, Tarragona\n" .
                "- **Phone:** +34 977 64 20 10",

        'zh' => "📍 **地址与联系方式:**\n" .
                "- **地址:** Carrer Major, 25, 43800 Torredembarra, Tarragona\n" .
                "- **电话:** +34 977 64 20 10",

        'hi' => "📍 **पता और संपर्क:**\n" .
                "- **पता:** Carrer Major, 25, 43800 Torredembarra, Tarragona\n" .
                "- **फोन:** +34 977 64 20 10",

        'fr' => "📍 **Adresse et Contact:**\n" .
                "- **Adresse:** Carrer Major, 25, 43800 Torredembarra, Tarragona\n" .
                "- **Téléphone:** +34 977 64 20 10",

        'pcm' => "📍 **Where We Dey & How To Call Us:**\n" .
                 "- **Address:** Carrer Major, 25, 43800 Torredembarra, Tarragona\n" .
                 "- **Phone:** +34 977 64 20 10",
    ],

    'delivery' => [
        'es' => "🚚 **Zonas de Reparto y Portes:**\n" .
                "- **Zonas:** Altafulla, Creixell, La Mora, Pobla Montornés, Torredembarra, Riera de Gaià.\n" .
                "- **Gastos de envío:** Local 1,95€ | Fuera 2,95€",

        'en' => "🚚 **Delivery Areas:**\n" .
                "- Altafulla, Creixell, La Mora, Pobla Montornés, Torredembarra, Riera de Gaià.\n" .
                "- **Fee:** Local €1.95 | Outside €2.95",

        'zh' => "🚚 **配送区域:**\n" .
                "- Altafulla, Creixell, La Mora, Pobla Montornés, Torredembarra, Riera de Gaià.\n" .
                "- **配送费:** 本地 €1.95 | 其他区域 €2.95",

        'hi' => "🚚 **डिलीवरी क्षेत्र:**\n" .
                "- Altafulla, Creixell, La Mora, Pobla Montornés, Torredembarra, Riera de Gaià.\n" .
                "- **शुल्क:** स्थानीय €1.95 | बाहर €2.95",

        'fr' => "🚚 **Zones de livraison:**\n" .
                "- Altafulla, Creixell, La Mora, Pobla Montornés, Torredembarra, Riera de Gaià.\n" .
                "- **Frais:** Local 1,95€ | Extérieur 2,95€",

        'pcm' => "🚚 **Delivery Areas:**\n" .
                 "- Altafulla, Creixell, La Mora, Pobla Montornés, Torredembarra, Riera de Gaià.\n" .
                 "- **Fee:** Local €1.95 | Outside €2.95",
    ],

    'menu' => [
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

        'zh' => "🍗 **菜单 – Pit o Cuixa**\n\n" .
                "**烤鸡套餐:**\n" .
                "- 整只烤鸡: **€16.90**\n" .
                "- 烤鸡 + 薯条 + 蒜蓉酱: **€22.90**\n" .
                "- 烤鸡 + 沙拉 + 蒜蓉酱: **€22.90**\n" .
                "- 工作日套餐 (2个菜+饮料): **€12.50**\n\n" .
                "**其他热门:**\n" .
                "- 炸鸡块 / 鸡翅 / 意面等\n" .
                "- 可乐 / 啤酒: **€2.00**\n" .
                "- Peñalosa 酒: **€8.00**",

        'hi' => "🍗 **मेनू – Pit o Cuixa**\n\n" .
                "**चिकन कॉम्बो:**\n" .
                "- होल रोस्ट चिकन: **€16.90**\n" .
                "- चिकन + फ्राइज़ + अलियोली: **€22.90**\n" .
                "- डेली मेनू (2 डिश + ड्रिंक): **€12.50**\n\n" .
                "**ड्रिंक्स:**\n" .
                "- कोक / बीयर: **€2.00**\n" .
                "- Peñalosa बोतल: **€8.00**",

        'fr' => "🍗 **Menu – Pit o Cuixa**\n\n" .
                "**Combos Poulet:**\n" .
                "- Poulet entier: **16,90€**\n" .
                "- Poulet + Frites + Aioli: **22,90€**\n" .
                "- Menu du jour (2 plats + boisson): **12,50€**\n\n" .
                "**Boissons:**\n" .
                "- Coca / Bière: **2,00€**\n" .
                "- Bouteille Peñalosa: **8,00€**",

        'pcm' => "🍗 **Full Menu – Pit o Cuixa**\n\n" .
                 "**Chicken Combos:**\n" .
                 "- Whole Chicken: **€16.90**\n" .
                 "- Chicken + Chips + Alioli: **€22.90**\n" .
                 "- Daily Menu (2 dishes + drink): **€12.50**\n\n" .
                 "**Drinks:**\n" .
                 "- Coke / Beer: **€2.00**\n" .
                 "- Peñalosa Bottle: **€8.00**",
    ],

    'greeting' => [
        'es' => "👋 ¡Hola! Bienvenido a **Pit o Cuixa**.\n\nPuedes preguntarme por el menú, horarios, reparto o ubicación.",
        'en' => "👋 Hello! Welcome to **Pit o Cuixa**.\n\nYou can ask me about the menu, opening hours, delivery or location.",
        'zh' => "👋 你好！欢迎来到 **Pit o Cuixa**。\n\n你可以问我菜单、营业时间、外卖或地址。",
        'hi' => "👋 नमस्ते! **Pit o Cuixa** में आपका स्वागत है।\n\nआप मेनू, समय, डिलीवरी या पता पूछ सकते हैं।",
        'fr' => "👋 Bonjour ! Bienvenue chez **Pit o Cuixa**.\n\nVous pouvez me demander le menu, les horaires, la livraison ou l'adresse.",
        'pcm' => "👋 How far! Welcome to **Pit o Cuixa**.\n\nYou fit ask me about menu, time, delivery or location.",
    ],

    'thanks' => [
        'es' => "¡De nada! 😊 Si necesitas algo más, aquí estoy.\n\nTambién puedes llamar al **+34 977 64 20 10**.",
        'en' => "You're welcome! 😊 If you need anything else, just ask.\n\nYou can also call **+34 977 64 20 10**.",
        'zh' => "不客气！😊 还有其他问题随时问我。\n\n也可以打电话 **+34 977 64 20 10**。",
        'hi' => "आपका स्वागत है! 😊 और कुछ चाहिए तो पूछिए।\n\nआप **+34 977 64 20 10** पर कॉल भी कर सकते हैं।",
        'fr' => "De rien ! 😊 Si vous avez besoin d'autre chose, je suis là.\n\nVous pouvez aussi appeler le **+34 977 64 20 10**.",
        'pcm' => "You welcome! 😊 If you need anytin else, just ask.\n\nYou fit call **+34 977 64 20 10** too.",
    ],

    'help' => [
        'es' => "Puedo ayudarte con:\n• Horarios\n• Ubicación y teléfono\n• Zonas de reparto\n• Menú y precios\n\nEscribe una de estas palabras o llama al **+34 977 64 20 10**.",
        'en' => "I can help you with:\n• Opening hours\n• Location & phone\n• Delivery areas\n• Menu & prices\n\nJust type one of these or call **+34 977 64 20 10**.",
        'zh' => "我可以帮你查询：\n• 营业时间\n• 地址和电话\n• 配送区域\n• 菜单和价格\n\n直接问我，或拨打 **+34 977 64 20 10**。",
        'hi' => "मैं इनमें मदद कर सकता हूँ:\n• खुलने का समय\n• पता और फोन\n• डिलीवरी क्षेत्र\n• मेनू और कीमतें\n\nया कॉल करें **+34 977 64 20 10**।",
        'fr' => "Je peux vous aider avec :\n• Les horaires\n• L'adresse et le téléphone\n• Les zones de livraison\n• Le menu et les prix\n\nAppelez le **+34 977 64 20 10** si besoin.",
        'pcm' => "I fit help you with:\n• Opening time\n• Location & phone\n• Delivery areas\n• Menu & prices\n\nOr call **+34 977 64 20 10**.",
    ],
];

// ==========================================
// 3. LANGUAGE DETECTION
// ==========================================
function detectLanguage(string $msg): string {
    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $msg)) return 'zh';
    if (preg_match('/[\x{0900}-\x{097F}]/u', $msg)) return 'hi';
    if (preg_match('/\b(bonjour|salut|heures|horaire|ouvert|fermé|ferme|adresse|telephone|téléphone|poulet|prix|menu|carte)\b/u', $msg)) return 'fr';
    if (preg_match('/\b(una|wey|dey|chop|how far|abeg|correct food|wetin)\b/u', $msg)) return 'pcm';
    if (preg_match('/\b(hello|hi|hey|hours|open|opening|closed|menu|food|chicken|price|prices|address|location|phone|delivery|where|when|thanks|thank you|help)\b/u', $msg)) return 'en';
    return 'es';
}

// ==========================================
// 4. INTENT DETECTION
// ==========================================
function detectIntent(string $msg, string $lang): ?string {
    $keywords = [
        'hours' => [
            'es'  => ['horario', 'horarios', 'horari', 'abre', 'abren', 'abierto', 'obert', 'cerrado', 'tancat', 'hora', 'dias', 'días', 'cuando', 'quan'],
            'en'  => ['hours', 'open', 'opening', 'close', 'closed', 'time', 'when'],
            'zh'  => ['时间', '营业', '几点', '开门', '休息'],
            'hi'  => ['समय', 'कब', 'खुलता', 'बंद'],
            'fr'  => ['heures', 'horaire', 'ouvert', 'ferme', 'fermé', 'quand'],
            'pcm' => ['open', 'time', 'when', 'day', 'close', 'closing'],
        ],
        'location' => [
            'es'  => ['donde', 'dónde', 'direccion', 'dirección', 'adreça', 'ubicacion', 'ubicación', 'telefono', 'teléfono', 'telèfon', 'contacto', 'llamar', 'on és', 'on'],
            'en'  => ['where', 'address', 'location', 'phone', 'call', 'contact', 'number'],
            'zh'  => ['地址', '电话', '位置', '在哪', '哪里'],
            'hi'  => ['पता', 'फोन', 'कहाँ', 'लोकेशन'],
            'fr'  => ['adresse', 'emplacement', 'telephone', 'téléphone', 'appeler', 'contact'],
            'pcm' => ['where', 'location', 'place', 'phone', 'call', 'number'],
        ],
        'delivery' => [
            'es'  => ['reparto', 'domicilio', 'envio', 'envío', 'portes', 'zonas', 'repartiment'],
            'en'  => ['delivery', 'shipping', 'area', 'zones', 'deliver'],
            'zh'  => ['外卖', '配送', '送餐'],
            'hi'  => ['डिलीवरी', 'डिलिवरी'],
            'fr'  => ['livraison', 'livrer', 'zones'],
            'pcm' => ['delivery', 'deliver', 'bring'],
        ],
        'menu' => [
            'es'  => ['menu', 'menú', 'carta', 'precio', 'precios', 'pollo', 'combos', 'croquetas', 'paella', 'bebida', 'bebidas', 'ensalada', 'comida'],
            'en'  => ['menu', 'food', 'price', 'prices', 'chicken', 'combos', 'paella', 'drink', 'drinks', 'salad', 'what do you have'],
            'zh'  => ['菜单', '价格', '烤鸡', '多少钱', '吃的'],
            'hi'  => ['मेनू', 'कीमत', 'चिकन', 'खाना', 'प्राइस'],
            'fr'  => ['menu', 'carte', 'prix', 'poulet', 'combos', 'nourriture'],
            'pcm' => ['food', 'chicken', 'chop', 'price', 'how much', 'menu'],
        ],
        'greeting' => [
            'es'  => ['hola', 'buenos', 'buenas', 'hey'],
            'en'  => ['hello', 'hi', 'hey', 'good morning', 'good afternoon'],
            'zh'  => ['你好', '您好', '哈喽'],
            'hi'  => ['नमस्ते', 'हेलो', 'हाय'],
            'fr'  => ['bonjour', 'salut', 'bonsoir'],
            'pcm' => ['how far', 'hello', 'hi'],
        ],
        'thanks' => [
            'es'  => ['gracias', 'merci', 'thanks', 'thank'],
            'en'  => ['thanks', 'thank you', 'thank', 'thx'],
            'zh'  => ['谢谢', '多谢'],
            'hi'  => ['धन्यवाद', 'शुक्रिया'],
            'fr'  => ['merci', 'thanks'],
            'pcm' => ['thanks', 'thank you', 'thank'],
        ],
        'help' => [
            'es'  => ['ayuda', 'help', 'ayudar', 'opciones', 'qué puedes'],
            'en'  => ['help', 'options', 'what can you', 'commands'],
            'zh'  => ['帮助', '帮我'],
            'hi'  => ['मदद', 'हेल्प'],
            'fr'  => ['aide', 'help', 'aider'],
            'pcm' => ['help', 'abeg'],
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
// 5. MAIN LOGIC
// ==========================================
$lang   = detectLanguage($userMessage);
$intent = detectIntent($userMessage, $lang);

if ($intent && isset($responses[$intent][$lang])) {
    $reply = $responses[$intent][$lang];
} elseif ($intent && isset($responses[$intent]['en'])) {
    $reply = $responses[$intent]['en'];
} else {
    $fallbacks = [
        'es'  => "Lo siento, no he entendido tu pregunta.\n\nPuedo ayudarte con: **horarios**, **ubicación**, **reparto** o **menú**.\n\nTambién puedes llamar al **+34 977 64 20 10**.",
        'en'  => "Sorry, I didn't understand your question.\n\nI can help you with: **hours**, **location**, **delivery** or **menu**.\n\nYou can also call **+34 977 64 20 10**.",
        'zh'  => "抱歉，我没有理解你的问题。\n\n我可以帮你查询：营业时间、地址、外卖或菜单。\n\n也可以拨打 **+34 977 64 20 10**。",
        'hi'  => "माफ़ करें, मैं आपका सवाल नहीं समझ पाया।\n\nआप समय, पता, डिलीवरी या मेनू पूछ सकते हैं।\n\nया कॉल करें **+34 977 64 20 10**।",
        'fr'  => "Désolé, je n'ai pas compris votre question.\n\nJe peux vous aider avec les **horaires**, **adresse**, **livraison** ou **menu**.\n\nAppelez le **+34 977 64 20 10**.",
        'pcm' => "Sorry, I no understand your question.\n\nI fit help you with **time**, **location**, **delivery** or **menu**.\n\nYou fit call **+34 977 64 20 10**.",
    ];
    $reply = $fallbacks[$lang] ?? $fallbacks['en'];
}

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);