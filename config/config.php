<?php
/**
 * Configuration principale de l'application Bralima
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'bralima_logistique');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
// Configuration de la base de données on line
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'suncityc_bralima_logistique');
// define('DB_USER', 'suncityc_NelsonMupenda');
// define('DB_PASS', 'HgK9Em3H=}lJ_[jj');
// define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'SUN CITY-CE SARL');
define('APP_VERSION', '1.0.0');
// Détecter automatiquement l'URL de base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptName === '/' || $scriptName === '\\' ? '' : $scriptName;
define('APP_URL', $protocol . '://' . $host . $basePath);
define('APP_DEBUG', true);
define('BASE_PATH', $basePath);

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
$timezone = getenv('APP_TIMEZONE') ?: ini_get('date.timezone') ?: date_default_timezone_get();
date_default_timezone_set($timezone ?: 'UTC');

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

// Fonction d'aide pour les chemins
function asset($path) {
    // Le document root est le dossier public, donc pas besoin de /public/
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
        'dashboard.view' => '/',
        'ventes.view' => 'ventes',
        'clients.view' => 'clients',
        'produits.view' => 'produits',
        'stock.view' => 'stocks',
        'approvisionnements.view' => 'approvisionnements',
        'missions.view' => 'missions',
        'vehicules.view' => 'vehicules',
        'emballages.view' => 'emballages',
        'pertes.view' => 'pertes',
        'depenses.view' => 'depenses',
        'rapports.view' => 'rapports',
        'admin.view' => 'admin',
    ];
    foreach ($routeMap as $perm => $route) {
        if (in_array($perm, $perms)) return $route;
    }
    return 'ventes';
}

// Fonction de débogage
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

// Charger les fonctions utilitaires
require_once ROOT_PATH . '/app/helpers.php';