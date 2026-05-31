<?php
require_once dirname(__DIR__) . '/app/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    echo "✅ Connexion OK - DB: " . DB_NAME;
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}

echo "<br>APP_URL: " . APP_URL;
echo "<br>BASE_URL calculé: " . rtrim(preg_replace('#/public$#', '', rtrim(APP_URL, '/')), '/');