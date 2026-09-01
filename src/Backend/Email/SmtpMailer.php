<?php
/**
 * Pit o Cuixa — Minimal SMTP Mailer
 *
 * Sends email via fsockopen to a local SMTP server (localhost:25).
 * No auth, no composer, no vendor — pure PHP.
 * Designed for dinahosting shared hosting where mail() doesn't work.
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

    public function __construct(string $host = 'localhost', int $port = 25, int $timeout = 10)
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
     * @return array{ok: bool, error?: string}
     */
    public function send(string $from, string $to, string $subject, string $body): array
    {
        $errno  = 0;
        $errstr = '';
        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if ($fp === false) {
            return ['ok' => false, "error" => "Connection failed: {$errstr} ({$errno})"];
        }

        // Set timeout for reads
        stream_set_timeout($fp, $this->timeout);

        // Read server greeting
        $this->read($fp);

        // EHLO
        $this->write($fp, 'EHLO pitocuixa.es');
        $this->read($fp);

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

    /**
     * Read SMTP response line(s) from the server.
     */
    private function read($fp): string
    {
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // SMTP multi-line responses have '-' on lines 1..N-1, ' ' on last
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Write a command to the SMTP server.
     */
    private function write($fp, string $command): void
    {
        fwrite($fp, $command . "\r\n");
    }
}
