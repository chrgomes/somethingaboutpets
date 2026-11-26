<?php
declare(strict_types=1);

/*
 * Loaded at the top of every PHP page. Sets up config, errors, session, database and helpers.
 */

define('APP_ROOT', dirname(__DIR__));

function config_all(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $candidates = [
        getenv('SAP_CONFIG') ?: null,
        dirname(APP_ROOT) . '/sap-config.php',   // one level above public_html (recommended)
        APP_ROOT . '/config/config.php',
    ];
    foreach ($candidates as $file) {
        if ($file && is_file($file)) {
            $config = require $file;
            return $config;
        }
    }
    $config = require APP_ROOT . '/config/config.sample.php';
    $config['_sample'] = true;
    return $config;
}

/** Read a config value with dot notation, e.g. cfg('db.dsn'). */
function cfg(string $key, $default = null)
{
    $value = config_all();
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

date_default_timezone_set((string) cfg('app.timezone', 'Pacific/Auckland'));

if (cfg('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('sap_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (cfg('app.base_path') ?: '') . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/booking.php';
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/pets.php';
require_once __DIR__ . '/layout.php';
