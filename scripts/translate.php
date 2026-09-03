<?php
/**
 * Pit o Cuixa — DeepL Batch Translator CLI Script
 *
 * Manual CLI entry point to translate categories and products into EN, UK.
 *
 * Usage: php scripts/translate.php
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../src/shared/bootstrap.php';

use Pit\Cuixa\Backend\Services\MenuTranslator;

function status(string $label, string $message): void
{
    $green  = "\033[32m";
    $yellow = "\033[33m";
    $red    = "\033[31m";
    $reset  = "\033[0m";
    $color  = match ($label) {
        '✓' => $green,
        '!' => $yellow,
        '✗' => $red,
        default => '',
    };
    echo "{$color}[{$label}]{$reset} {$message}\n";
}

echo "\n";
echo "  ╔══════════════════════════════════════════════╗\n";
echo "  ║   Pit o Cuixa — Fast DeepL Batch Translator  ║\n";
echo "  ║   Automated Translation (CA, EN, UK)         ║\n";
echo "  ╚══════════════════════════════════════════════╝\n\n";

$apiKey = getenv('DEEPL_API_KEY');

if (empty($apiKey)) {
    status('✗', 'DEEPL_API_KEY is not defined in your .env file.');
    echo "\n  Please add your DeepL API key to your local .env file:\n";
    echo "  DEEPL_API_KEY=your_key_here:fx\n\n";
    exit(1);
}

try {
    $translator = new MenuTranslator($apiKey);
    status('!', 'Checking categories and products for missing translations...');
    $stats = $translator->translateMissing();

    status('✓', "Translation complete: {$stats['categories']} category fields and {$stats['products']} product fields translated.");
} catch (\Throwable $e) {
    status('✗', 'Translation error: ' . $e->getMessage());
    exit(1);
}

echo "\n";
