<?php
/**
 * Script de simulation de ventes pour tester les ristournes
 */

require_once __DIR__ . '/../config/config.php';

// Initialisation des modèles
$db = Database::getInstance();
$venteModel = new Vente();
$clientModel = new Client();
$produitModel = new Produit();
$emplacementModel = new Emplacement();
$stockModel = new Stock();

echo "--- DÉBUT DE LA SIMULATION ---\n";

// 1. Récupérer un client de test (le premier actif)
$client = $db->fetch("SELECT * FROM clients WHERE actif = 1 LIMIT 1");
if (!$client) {
    echo "ERREUR: Aucun client actif trouvé.\n";
    exit;
}
echo "Client sélectionné: {$client['nom']}\n";

// 2. Récupérer l'entrepôt principal
$entrepot = $emplacementModel->getPrincipal();
echo "Emplacement de vente: {$entrepot['nom']}\n";

// 3. Récupérer un produit et forcer son stock pour le test
$produit = $db->fetch("SELECT * FROM produits WHERE actif = 1 LIMIT 1");
if (!$produit) {
    echo "ERREUR: Aucun produit actif trouvé.\n";
    exit;
}
echo "Produit sélectionné: {$produit['nom']} ({$produit['bouteilles_par_caisses']} btl/cs)\n";

// On force un stock de 2000 bouteilles pour être sûr que la vente passe
$stockModel->updateOrCreate($produit['id'], $entrepot['id'], [
    'quantite_pleine' => 2000,
    'caisses_pleine' => 2000 / $produit['bouteilles_par_caisses']
]);
echo "Stock forcé à 2000 bouteilles pour le test.\n";

// 4. Créer deux ventes pour atteindre un volume significatif (ex: 150 caisses au total)
$nbCaissesVente1 = 80;
$nbCaissesVente2 = 70;

$ventes = [
    [
        'caisses' => $nbCaissesVente1,
        'btl' => $nbCaissesVente1 * $produit['bouteilles_par_caisses'],
        'prix' => $produit['prix_vente_unitaire']
    ],
    [
        'caisses' => $nbCaissesVente2,
        'btl' => $nbCaissesVente2 * $produit['bouteilles_par_caisses'],
        'prix' => $produit['prix_vente_unitaire']
    ]
];

foreach ($ventes as $index => $v) {
    $data = [
        'numero_facture' => $venteModel->generateNumeroFacture(),
        'client_id' => $client['id'],
        'date_vente' => date('Y-m-d H:i:s'),
        'emplacement_id' => $entrepot['id'],
        'total_ht' => $v['btl'] * $v['prix'],
        'total_tva' => 0,
        'total_ttc' => $v['btl'] * $v['prix'],
        'statut' => 'validee',
        'created_by' => 1 // Admin
    ];

    $details = [
        [
            'produit_id' => $produit['id'],
            'quantite' => $v['btl'],
            'prix_unitaire' => $v['prix'],
            'sous_total' => $v['btl'] * $v['prix']
        ]
    ];

    $result = $venteModel->createWithDetails($data, $details);
    if ($result['success']) {
        echo "Vente " . ($index + 1) . " créée: {$data['numero_facture']} ({$v['caisses']} caisses)\n";
    } else {
        echo "ERREUR Vente " . ($index + 1) . ": {$result['message']}\n";
    }
}

echo "--- FIN DE LA SIMULATION ---\n";
echo "Vous pouvez maintenant aller dans 'Ristournes' et cliquer sur 'Calculer le mois'.\n";
