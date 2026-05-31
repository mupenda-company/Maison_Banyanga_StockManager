<?php
require_once dirname(__DIR__) . '/config/config.php';

try {
    $model = new Approvisionnement();
    $result = $model->getWithDetails(17);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} catch (Throwable $e) {
    echo "❌ Erreur: " . $e->getMessage();
    echo "<br>Fichier: " . $e->getFile();
    echo "<br>Ligne: " . $e->getLine();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}