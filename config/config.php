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

// Configuration de l'application
define('APP_NAME', 'Bralima Logistique');
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
date_default_timezone_set('Africa/Kinshasa');

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/app/Models/',
        ROOT_PATH . '/app/Controllers/',
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
