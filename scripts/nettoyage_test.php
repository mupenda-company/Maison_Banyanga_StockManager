<?php
/**
 * Script de nettoyage des données de test de simulation
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();

echo "--- DÉBUT DU NETTOYAGE ---\n";

try {
    $db->beginTransaction();

    // 1. Identifier les ventes de test (FAC-20260428...)
    $ventes = $db->fetchAll("SELECT id FROM ventes WHERE numero_facture LIKE 'FAC-20260428%'");
    $venteIds = array_column($ventes, 'id');

    if (!empty($venteIds)) {
        $idsStr = implode(',', $venteIds);
        
        // Supprimer les mouvements de stock liés
        $db->query("DELETE FROM mouvements_stock WHERE reference_type = 'vente' AND reference_id IN ($idsStr)");
        echo "Mouvements de stock supprimés.\n";

        // Supprimer les détails de vente
        $db->query("DELETE FROM vente_details WHERE vente_id IN ($idsStr)");
        echo "Détails de vente supprimés.\n";

        // Supprimer les ventes
        $db->query("DELETE FROM ventes WHERE id IN ($idsStr)");
        echo "Ventes de test supprimées ($idsStr).\n";
    }

    // 2. Supprimer les ristournes calculées pour ce test
    $db->query("DELETE FROM ristournes WHERE periode_debut >= '2026-04-01' AND periode_fin <= '2026-04-30'");
    echo "Ristournes de test supprimées.\n";

    // 3. Réinitialiser le stock forcé (optionnel, mais propre)
    // On laisse le stock tel quel ou on peut le remettre à zéro si besoin.
    
    $db->commit();
    echo "--- NETTOYAGE RÉUSSI ---\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "ERREUR LORS DU NETTOYAGE: " . $e->getMessage() . "\n";
}
