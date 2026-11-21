<?php
/**
 * Database Configuration and Connection Handler
 * Smart Egg Incubator IoT System
 * 
 * Uses PHP Data Objects (PDO) with prepared statements.
 */

declare(strict_types=1);

// Prevent direct execution outside of PHP context
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'smart_egg_incubator');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtain a persistent PDO database connection.
 * 
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In API context, return structured JSON error instead of leaking stack traces
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/') || 
                str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error'   => 'Database connection error: ' . $e->getMessage()
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }
            throw $e;
        }
    }

    return $pdo;
}

