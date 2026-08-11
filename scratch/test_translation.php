<?php

require_once __DIR__ . '/../src/shared/bootstrap.php';

use Pit\Cuixa\Backend\Api\AdminProducts;

$sampleMenuData = [
    'badge' => 'De lunes a viernes',
    'includes' => 'Incluye pan, bebida y postre',
    'sections' => [
        [
            'title_es' => 'Primeros Platos',
            'items_es' => [
                'Ensalada mixta',
                ['name_es' => 'Sopa de picadillo', 'price' => 4.5]
            ]
        ],
        [
            'title_es' => 'Segundos Platos',
            'items_es' => [
                ['name_es' => 'Pollo asado', 'price' => 8.0]
            ]
        ]
    ]
];

// Mock DeepLService
class MockDeepLService extends \Pit\Cuixa\Backend\Services\DeepLService {
    public function __construct() {}
    public function translate(string|array $texts, string $targetLang, string $sourceLang = 'ES'): array {
        $inputTexts = is_array($texts) ? array_values($texts) : [$texts];
        return array_map(fn($t) => "[{$targetLang}] " . $t, $inputTexts);
    }
}

$mockDeepl = new MockDeepLService();
$result = AdminProducts::translateMenuData($sampleMenuData, $mockDeepl);

echo "ORIGINAL:\n" . json_encode($sampleMenuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
echo "RESULTADO:\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
