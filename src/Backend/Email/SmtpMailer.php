<?php
/**
 * Pit o Cuixa — Minimal SMTP Mailer
 *
 * Sends email via fsockopen with STARTTLS and AUTH LOGIN.
 * No composer, no vendor — pure PHP.
 *
 * @package Pit\Cuixa\Backend\Email
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Email;

class SmtpMailer
{
    private string $host;
    private int    $port;
    private int    $timeout;

    public function __construct(string $host, int $port = 587, int $timeout = 10)
    {
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
    }

    /**
     * Send a plain-text email.
     *
     * @param string $from    Sender email address
     * @param string $to      Recipient email address
     * @param string $subject Email subject
     * @param string $body    Email body (plain text)
     * @param string $user    SMTP username (email)
     * @param string $pass    SMTP password (app password)
     * @return array{ok: bool, error?: string}
     */
    public function send(string $from, string $to, string $subject, string $body, string $user, string $pass): array
    {
        $errno  = 0;
        $errstr = '';
        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if ($fp === false) {
            return ['ok' => false, 'error' => "Connection failed: {$errstr} ({$errno})"];
        }

        stream_set_timeout($fp, $this->timeout);

        // Read server greeting
        $this->read($fp);

        // EHLO
        $this->write($fp, 'EHLO pitocuixa.es');
        $this->read($fp);

        // STARTTLS
        $this->write($fp, 'STARTTLS');
        $resp = $this->read($fp);
        if (strpos($resp, '220') !== 0) {
            fclose($fp);
            return ['ok' => false, 'error' => "STARTTLS failed: {$resp}"];
        }

        if (!stream_socket_enable_crypto($fp, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT, true)) {
            fclose($fp);
            return ['ok' => false, 'error' => 'TLS handshake failed'];
        }

        // Re-EHLO after STARTTLS
        $this->write($fp, 'EHLO pitocuixa.es');
        $this->read($fp);

        // AUTH LOGIN
        $this->write($fp, 'AUTH LOGIN');
        $resp = $this->read($fp);
        if (strpos($resp, '334') !== 0) {
            fclose($fp);
            return ['ok' => false, 'error' => "AUTH LOGIN failed: {$resp}"];
        }

        // Username (base64)
        $this->write($fp, base64_encode($user));
        $resp = $this->read($fp);
        if (strpos($resp, '334') !== 0) {
            fclose($fp);
            return ['ok' => false, 'error' => "AUTH username rejected: {$resp}"];
        }

        // Password (base64)
        $this->write($fp, base64_encode($pass));
        $resp = $this->read($fp);
        if (strpos($resp, '235') !== 0) {
            fclose($fp);
            return ['ok' => false, 'error' => "AUTH password rejected: {$resp}"];
        }

        // MAIL FROM
        $this->write($fp, "MAIL FROM:<{$from}>");
        $this->read($fp);

        // RCPT TO
        $this->write($fp, "RCPT TO:<{$to}>");
        $this->read($fp);

        // DATA
        $this->write($fp, 'DATA');
        $this->read($fp);

        // Headers + body
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B');
        $data  = "From: {$from}\r\n";
        $data .= "To: {$to}\r\n";
        $data .= "Subject: {$encodedSubject}\r\n";
        $data .= "Date: " . date('r') . "\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "\r\n";
        $data .= $body . "\r\n";
        $data .= ".\r\n";

        $this->write($fp, $data);
        $this->read($fp);

        // QUIT
        $this->write($fp, 'QUIT');
        $this->read($fp);

        fclose($fp);

        return ['ok' => true];
    }

    private function read($fp): string
    {
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    private function write($fp, string $command): void
    {
        fwrite($fp, $command . "\r\n");
    }
}
