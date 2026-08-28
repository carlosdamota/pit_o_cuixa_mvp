<?php
/**
 * Pit o Cuixa — Settings Repository
 *
 * Key/value store for admin toggles and app configuration.
 * Table auto-created via ensureSchema() for self-healing on existing DBs.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class Settings
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Ensure the settings table exists and has default values.
     * Idempotent — safe to call on every request.
     */
    public static function ensureSchema(): void
    {
        $pdo = Connection::get();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                key   TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );

        // Self-seed defaults (INSERT OR IGNORE skips keys that already exist,
        // so admin-edited values are never overwritten).
        $defaults = [
            'menu_slider_enabled' => '0',
            'company_address'     => '',
            'company_phone'       => \Config::phone(),
            'company_whatsapp'    => '',
        ];

        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)'
        );

        foreach ($defaults as $key => $value) {
            $stmt->execute([':key' => $key, ':value' => $value]);
        }
    }

    /**
     * Company address shown in the public footer.
     * Empty string when unset — templates fall back to the i18n string.
     */
    public static function companyAddress(): string
    {
        return trim(self::get('company_address', ''));
    }

    /**
     * Primary company phone (display format, e.g. "+34 977 64 20 10").
     * Falls back to the canonical Config phone when unset.
     */
    public static function companyPhone(): string
    {
        $phone = trim(self::get('company_phone', ''));

        return $phone !== '' ? $phone : \Config::phone();
    }

    /**
     * Secondary mobile for the WhatsApp button. Optional — falls back to
     * the primary phone so the floating button always works.
     */
    public static function companyWhatsapp(): string
    {
        $whatsapp = trim(self::get('company_whatsapp', ''));

        return $whatsapp !== '' ? $whatsapp : self::companyPhone();
    }

    /**
     * Get a setting value by key.
     *
     * @param  string $key     Setting key
     * @param  string $default Fallback value if key not found
     * @return string
     */
    public static function get(string $key, string $default = ''): string
    {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();

        return $row !== false ? (string) $row['value'] : $default;
    }

    /**
     * Get all settings as key-value array.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $pdo  = Connection::get();
        $stmt = $pdo->query('SELECT key, value FROM settings ORDER BY key');
        $rows = $stmt->fetchAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[(string) $row['key']] = (string) $row['value'];
        }

        return $map;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key   Setting key
     * @param string $value Setting value
     */
    public static function set(string $key, string $value): void
    {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (key, value) VALUES (:key, :value)
             ON CONFLICT(key) DO UPDATE SET value = :value2'
        );
        $stmt->execute([
            ':key'    => $key,
            ':value'  => $value,
            ':value2' => $value,
        ]);
    }
}
