<?php
/**
 * Script de réparation ciblée du stock d'une mission
 *
 * Usage:
 *   php scripts/repair_mission_stock.php <mission_id> [--dry-run]
 */

require_once __DIR__ . '/../config/config.php';

$missionId = (int) ($argv[1] ?? 0);
$dryRun = in_array('--dry-run', $argv, true);

if ($missionId <= 0) {
    echo "Usage: php scripts/repair_mission_stock.php <mission_id> [--dry-run]\n";
    exit(1);
}

$db = Database::getInstance();
$missionModel = new Mission();
$stockModel = new Stock();
$vehiculeModel = new Vehicule();

$mission = $missionModel->getWithDetails($missionId);
if (!$mission) {
    echo "Mission introuvable: {$missionId}\n";
    exit(1);
}

$vehiculeId = (int) ($mission['vehicule_id'] ?? 0);
$vehicule = $vehiculeModel->getWithStock($vehiculeId);
if (!$vehicule) {
    echo "Véhicule introuvable pour la mission {$missionId}\n";
    exit(1);
}

$emplacementVehiculeId = (int) ($vehicule['emplacement_id'] ?? 0);
if ($emplacementVehiculeId <= 0) {
    echo "Emplacement véhicule introuvable pour la mission {$missionId}\n";
    exit(1);
}

$desiredByProduct = [];
foreach (($mission['chargements'] ?? []) as $chargement) {
    $produitId = (int) ($chargement['produit_id'] ?? 0);
    if ($produitId <= 0) {
        continue;
    }

    $stockDepartCaisses = (int) ($chargement['caisses_deja_dans_vehicule'] ?? 0);
    $deltaCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
    $caissesFinales = (int) ($chargement['caisses_total'] ?? ($stockDepartCaisses + $deltaCaisses));

    $desiredByProduct[$produitId] = max(0, $caissesFinales);
}

$currentStocks = $stockModel->getByEmplacement($emplacementVehiculeId);
$currentByProduct = [];
foreach ($currentStocks as $row) {
    $currentByProduct[(int) ($row['produit_id'] ?? 0)] = $row;
}

// Les produits présents dans le véhicule mais absents de la mission doivent être retirés.
foreach ($currentByProduct as $produitId => $row) {
    if (!array_key_exists($produitId, $desiredByProduct)) {
        $desiredByProduct[$produitId] = 0;
    }
}

ksort($desiredByProduct);

$changes = [];

try {
    $db->beginTransaction();

    foreach ($desiredByProduct as $produitId => $desiredCaisses) {
        $currentRow = $currentByProduct[$produitId] ?? null;
        $currentCaisses = (int) round((float) ($currentRow['caisses_pleine'] ?? 0));
        $deltaCaisses = (int) $desiredCaisses - $currentCaisses;

        if ($deltaCaisses === 0) {
            continue;
        }

        $product = (new Produit())->find($produitId);
        $productName = $product['nom'] ?? ('Produit #' . $produitId);

        $changes[] = [
            'produit_id' => $produitId,
            'produit_nom' => $productName,
            'actuel' => $currentCaisses,
            'souhaite' => (int) $desiredCaisses,
            'delta' => $deltaCaisses,
        ];

        if (!$dryRun) {
            $stockModel->updateOrCreate($produitId, $emplacementVehiculeId, [
                'caisses_pleine' => $deltaCaisses,
            ]);
        }
    }

    if ($dryRun) {
        $db->rollBack();
    } else {
        $db->commit();
    }

    echo "Mission: {$missionId} - " . ($dryRun ? "APERÇU" : "RÉPARATION APPLIQUÉE") . "\n";
    echo "Véhicule: " . ($vehicule['immatriculation'] ?? $vehiculeId) . "\n";
    echo str_repeat('-', 72) . "\n";

    if (empty($changes)) {
        echo "Aucun écart détecté.\n";
        exit(0);
    }

    foreach ($changes as $change) {
        echo sprintf(
            "%s | actuel: %d cs | souhaité: %d cs | delta: %+d cs\n",
            $change['produit_nom'],
            $change['actuel'],
            $change['souhaite'],
            $change['delta']
        );
    }

    echo str_repeat('-', 72) . "\n";
    echo $dryRun
        ? "Aucune écriture n'a été faite (dry-run).\n"
        : "Le stock du véhicule a été réaligné sur la mission.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
