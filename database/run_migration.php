<?php
/**
 * Script pour exécuter les migrations
 */

require_once __DIR__ . '/../config/config.php';

$db = new Database();

// Ajouter le paramètre taux_change
$sql = "INSERT INTO parametres (cle, valeur, type) VALUES ('taux_change', '2800', 'number') ON DUPLICATE KEY UPDATE valeur = '2800'";

try {
    $db->query($sql);
    echo "Migration executed successfully!\n";
    echo "Paramètre taux_change ajouté avec la valeur 2800\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
