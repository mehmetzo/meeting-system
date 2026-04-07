<?php
header('Content-Type: text/html; charset=UTF-8');

// ============================================================
// Dijital Toplantı Sistemi — Veritabanı Bağlantısı
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'meeting_db');
define('DB_USER', getenv('DB_USER') ?: 'meeting_user');
define('DB_PASS', getenv('DB_PASS') ?: 'Meeting@2024!');

/**
 * Singleton PDO bağlantısı döndürür.
 * Türkçe karakter için tüm charset ayarları INIT komutunda zorlanır.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );

            $initCommand = implode('; ', [
                "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                "SET CHARACTER SET utf8mb4",
                "SET character_set_client     = utf8mb4",
                "SET character_set_connection = utf8mb4",
                "SET character_set_results    = utf8mb4",
                "SET collation_connection      = utf8mb4_unicode_ci",
                "SET time_zone                 = '+03:00'",
            ]);

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE   => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES     => false,
                PDO::MYSQL_ATTR_INIT_COMMAND   => $initCommand,
                PDO::MYSQL_ATTR_FOUND_ROWS     => true,
            ]);

        } catch (PDOException $e) {
            error_log('[DB] Bağlantı hatası: ' . $e->getMessage());
            http_response_code(500);
            die('Veritabanı bağlantı hatası. Lütfen daha sonra tekrar deneyin.');
        }
    }

    return $pdo;
}

/**
 * Ayar değeri okur; bulunamazsa $default döner.
 */
function getSetting(string $key, string $default = ''): string
{
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return ($row !== false) ? (string) $row['setting_value'] : $default;
    } catch (Exception $e) {
        error_log('[getSetting] ' . $e->getMessage());
        return $default;
    }
}

/**
 * Ayar değeri yazar (INSERT … ON DUPLICATE KEY UPDATE).
 */
function setSetting(string $key, string $value): bool
{
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        error_log('[setSetting] ' . $e->getMessage());
        return false;
    }
}