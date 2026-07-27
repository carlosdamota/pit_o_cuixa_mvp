<?php
/**
 * Pit o Cuixa — HTTP Response Helpers
 *
 * Utility functions for JSON and CSV responses with
 * a uniform error envelope.
 *
 * @package Pit\Cuixa\Backend\Http
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Http;

class Response
{
    /**
     * Send a JSON response.
     *
     * @param  mixed $data   Any JSON-serializable value
     * @param  int   $code   HTTP status code (default 200)
     */
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Send a JSON error response using the uniform envelope.
     *
     * @param  string $message  Human-readable error message
     * @param  int    $code     HTTP status code (default 400)
     */
    public static function error(string $message, int $code = 400): void
    {
        self::json([
            'error'   => true,
            'message' => $message,
            'code'    => $code,
        ], $code);
    }

    /**
     * Send a CSV response as a file download.
     *
     * @param  array<int, array<string, mixed>> $rows    Array of associative arrays
     * @param  string                           $filename  Download filename
     */
    public static function csv(array $rows, string $filename = 'export.csv'): void
    {
        // Sanitize filename to prevent header injection
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $filename = basename($filename); // Extra safety: strip any path components

        http_response_code(200);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // BOM for UTF-8 Excel compatibility
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'wb');

        if ($output === false) {
            self::error('Cannot open output stream', 500);
            return;
        }

        // Write header row
        if ($rows !== []) {
            fputcsv($output, array_keys($rows[0]));
        }

        // Write data rows
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
    }

    /**
     * Send a plain text response.
     *
     * @param  string $text  Response body
     * @param  int    $code  HTTP status code
     * @param  string $contentType  Content-Type header
     */
    public static function text(string $text, int $code = 200, string $contentType = 'text/plain; charset=utf-8'): void
    {
        http_response_code($code);
        header('Content-Type: ' . $contentType);
        echo $text;
    }

    /**
     * Send an HTML response.
     *
     * @param  string $html  HTML content
     * @param  int    $code  HTTP status code
     */
    public static function html(string $html, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    /**
     * Send an XML response.
     *
     * @param  string $xml   XML content
     * @param  int    $code  HTTP status code
     */
    public static function xml(string $xml, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
    }

    /**
     * Send a redirect.
     *
     * @param string $url  Destination URL
     * @param int    $code HTTP redirect code (default 302)
     */
    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header('Location: ' . $url);
    }
}
