<?php
/**
 * Pit o Cuixa — DeepL Translation Service
 *
 * Lightweight REST client for DeepL API v2 using native PHP cURL.
 * Supports single strings and array batch translation without external SDKs.
 *
 * @package Pit\Cuixa\Backend\Services
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Services;

class DeepLService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (getenv('DEEPL_API_KEY') ?: '');
        // DeepL Free keys end with ':fx'
        $isFree = str_ends_with($this->apiKey, ':fx');
        $this->baseUrl = $isFree 
            ? 'https://api-free.deepl.com/v2/translate' 
            : 'https://api.deepl.com/v2/translate';
    }

    /**
     * Translate text or array of texts from source language (or auto) to target language.
     *
     * @param string|array<int, string> $texts String or array of strings to translate.
     * @param string $targetLang Target language ISO code ('en', 'uk').
     * @param string $sourceLang Source language code (default: 'ES').
     * @return array<int, string> Array of translated texts.
     * @throws \RuntimeException If API call fails.
     */
    public function translate(string|array $texts, string $targetLang, string $sourceLang = 'ES'): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('DEEPL_API_KEY environment variable is not configured.');
        }

        $inputTexts = is_array($texts) ? array_values($texts) : [$texts];

        if (empty($inputTexts)) {
            return [];
        }

        // Map locale codes to DeepL expected ISO targets
        $targetCode = match (strtolower($targetLang)) {
            'en' => 'EN-US',
            'uk', 'ukr' => 'UK',
            default => strtoupper($targetLang),
        };

        $payload = [
            'text'        => $inputTexts,
            'target_lang' => $targetCode,
            'source_lang' => strtoupper($sourceLang),
        ];

        $ch = curl_init($this->baseUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: DeepL-Auth-Key ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("DeepL API HTTP Error {$httpCode}: " . $response);
        }

        $data = json_decode((string)$response, true);

        if (!isset($data['translations']) || !is_array($data['translations'])) {
            throw new \RuntimeException('Invalid response structure from DeepL API.');
        }

        return array_column($data['translations'], 'text');
    }

    /**
     * Convenient helper to translate a single string.
     */
    public function translateSingle(string $text, string $targetLang, string $sourceLang = 'ES'): string
    {
        if (trim($text) === '') {
            return '';
        }
        $results = $this->translate([$text], $targetLang, $sourceLang);
        return $results[0] ?? $text;
    }
}
