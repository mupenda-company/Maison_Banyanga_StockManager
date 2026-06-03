<?php
/**
 * Configuration principale de l'application Bralima
 */

// Détection local vs serveur — une seule fois, proprement
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'])
           || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');

// Configuration de la base de données
if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bralima_logistique');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'maisonba_bralima_logistique');
    define('DB_USER', 'maisonba_NelsonMupenda');
    define('DB_PASS', '?D@WiIAJU!HF^S{d');
}
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'Logistique');
define('APP_VERSION', '1.0.0');

// URL de base — logique claire et unique
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($isLocal) {
    // Local : toujours HTTP (jamais HTTPS), garder le sous-dossier
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
    define('APP_URL', 'http://' . $host . $basePath);
    define('APP_DEBUG', true);
} else {
    // Serveur : toujours HTTPS, public/ est la racine
    define('APP_URL', 'https://' . $host);
    define('APP_DEBUG', false);
}

define('BASE_PATH', $isLocal ? dirname($_SERVER['SCRIPT_NAME'] ?? '') : '');

// Configuration des chemins
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Configuration des rôles
define('ROLE_ADMIN', 'admin');
define('ROLE_MAGASINIER', 'magasinier');
define('ROLE_VENDEUR', 'vendeur');

// Configuration des seuils d'alerte par défaut
define('DEFAULT_ALERT_THRESHOLD', 10);

// Fuseau horaire
$timezone = getenv('APP_TIMEZONE') ?: ini_get('date.timezone') ?: 'Africa/Kigali';
date_default_timezone_set($timezone);

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/app/Models/',
        ROOT_PATH . '/app/Controllers/',
        ROOT_PATH . '/app/Controllers/Api/',
        ROOT_PATH . '/app/Core/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Fonctions d'aide
function asset($path) {
    return APP_URL . '/' . ltrim($path, '/');
}

function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}

function can($permissionCode) {
    if (!isset($_SESSION['user_id'])) return false;
    return in_array($permissionCode, $_SESSION['user_permissions'] ?? []);
}

function getDefaultRoute() {
    $perms = $_SESSION['user_permissions'] ?? [];
    $routeMap = [
        'dashboard.voir'           => '/',
        'ventes.voir'              => 'ventes',
        'clients.voir'             => 'clients',
        'produits.voir'            => 'produits',
        'stock.voir'               => 'stocks',
        'approvisionnements.voir'  => 'approvisionnements',
        'missions.voir'            => 'missions',
        'vehicules.voir'           => 'vehicules',
        'emballages.voir'          => 'emballages',
        'pertes.voir'              => 'pertes',
        'depenses.voir'            => 'depenses',
        'finance.voir'             => 'finance',
        'rapports.voir'            => 'rapports',
        'admin.voir'               => 'admin',
    ];
    foreach ($routeMap as $perm => $route) {
        if (in_array($perm, $perms)) return $route;
    }
    return 'ventes';
}

function dd($data) {
    if (APP_DEBUG) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

require_once ROOT_PATH . '/app/helpers.php';