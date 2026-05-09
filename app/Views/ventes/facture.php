<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($vente['numero_facture']) ?> - <?= htmlspecialchars($params['nom_entreprise']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: 105mm 148mm; /* Format A6 */
            margin: 0;
        }
        body {
            width: 100%;
            margin: 0;
            padding: 5mm;
            font-family: 'Courier New', Courier, monospace; /* Style ticket */
            font-size: 12px;
            line-height: 1.2;
        }
        .ticket-header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
        .ticket-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .total-row { font-weight: bold; font-size: 14px; margin-top: 5px; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white">
    <?php
        $companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
        $companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
    ?>
    <div class="max-w-[100mm] mx-auto">
        <!-- En-tête -->
        <div class="ticket-header">
            <?php if ($companyLogo): ?>
            <img src="<?= $companyLogo ?>" alt="Logo" class="h-14 mx-auto mb-2 object-contain">
            <?php endif; ?>
            <h1 class="text-lg font-bold uppercase"><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></h1>
            <?php if (!empty($params['adresse'])): ?><p class="text-[10px]"><?= htmlspecialchars($params['adresse']) ?></p><?php endif; ?>
            <?php if (!empty($companyContact)): ?><p class="text-[10px]">Contact: <?= htmlspecialchars($companyContact) ?></p><?php endif; ?>
            <?php if (!empty($params['email_contact'])): ?><p class="text-[10px]">Email: <?= htmlspecialchars($params['email_contact']) ?></p><?php endif; ?>
            <?php if (!empty($params['rccm'])): ?><p class="text-[10px]">RCCM: <?= htmlspecialchars($params['rccm']) ?></p><?php endif; ?>
            <?php if (!empty($params['id_nat'])): ?><p class="text-[10px]">ID NAT: <?= htmlspecialchars($params['id_nat']) ?></p><?php endif; ?>
            <?php if (!empty($params['nif'])): ?><p class="text-[10px]">NIF: <?= htmlspecialchars($params['nif']) ?></p><?php endif; ?>
            <?php if (!empty($params['numero_compte'])): ?><p class="text-[10px]">N° compte: <?= htmlspecialchars($params['numero_compte']) ?></p><?php endif; ?>
            <div class="divider"></div>
            <p class="font-bold">FACTURE: <?= htmlspecialchars($vente['numero_facture']) ?></p>
            <p class="text-[10px]"><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></p>
        </div>
        
        <!-- Client -->
        <div class="mb-3 text-[11px]">
            <p><strong>Client:</strong> <?= htmlspecialchars($vente['client_nom']) ?></p>
            <?php if (!empty($vente['client_telephone'])): ?><p><strong>Numéro:</strong> <?= htmlspecialchars($vente['client_telephone']) ?></p><?php endif; ?>
            <?php if ($vente['zone_nom']): ?><p><strong>Zone:</strong> <?= htmlspecialchars($vente['zone_nom']) ?></p><?php endif; ?>
            <?php if (!empty($ristourneInfo)): ?>
                <p><strong>Produits cumulés (période):</strong> <?= number_format((int)($ristourneInfo['total_caisses'] ?? 0), 0, '.', ' ') ?> cs</p>
                <p><strong>CA (période):</strong> <?= format_money_converted($ristourneInfo['ca_total'] ?? 0) ?></p>
            <?php elseif (isset($totalCaissesClient)): ?>
                <p><strong>Produits cumulés:</strong> <?= number_format((int)$totalCaissesClient, 0, '.', ' ') ?> cs</p>
            <?php endif; ?>
            <?php if (!empty($ristourneInfo)): ?>
                <p><strong>Ristourne:</strong> <?= number_format((float)($ristourneInfo['taux_applique'] ?? 0), 2, '.', ' ') ?>% (<?= format_money_converted($ristourneInfo['montant_ristourne'] ?? 0) ?>)</p>
            <?php endif; ?>
        </div>
        <div class="divider"></div>
        
        <!-- Détails Produits (Empilés) -->
        <div class="mb-3">
            <?php foreach ($vente['details'] as $detail): 
                $btlParCaisse = (int)($detail['bouteilles_par_caisses'] ?? 24);
                $caisses = intdiv((int)$detail['quantite'], $btlParCaisse);
                $caissesVidesRecues = (int)($detail['caisses_vides_recues'] ?? 0);
                $detteCaisses = max(0, $caisses - $caissesVidesRecues);
                $prixCaisse = $detail['prix_unitaire'] * $btlParCaisse;
            ?>
            <div class="mb-2">
                <p class="font-bold"><?= htmlspecialchars($detail['produit_nom']) ?></p>
                <div class="ticket-row text-[11px]">
                    <span><?= number_format($caisses, 0, '.', ' ') ?> cs x <?= format_money_converted($prixCaisse) ?></span>
                    <span class="font-bold"><?= format_money_converted($detail['sous_total']) ?></span>
                </div>
                <div class="ticket-row text-[10px]">
                    <span>Emballages reçus: <?= number_format($caissesVidesRecues, 0, '.', ' ') ?> cs</span>
                    <span>Dette: <?= number_format($detteCaisses, 0, '.', ' ') ?> cs</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="divider"></div>
        
        <!-- Totaux -->
        <div class="space-y-1">
            <div class="ticket-row text-[11px]">
                <span>Total HT:</span>
                <span><?= format_money_converted($vente['total_ht'] ?? 0) ?></span>
            </div>
            <div class="ticket-row text-[11px]">
                <span>TVA (<?= number_format($vente['total_tva'] / ($vente['total_ht'] ?: 1) * 100, 0) ?>%):</span>
                <span><?= format_money_converted($vente['total_tva'] ?? 0) ?></span>
            </div>
            <div class="ticket-row total-row border-t border-black pt-1">
                <span>TOTAL TTC:</span>
                <span><?= format_money_converted($vente['total_ttc'] ?? 0) ?></span>
            </div>
        </div>

        <?php
            $totalCaissesVidesRecues = 0;
            $totalDetteCaisses = 0;
            foreach ($vente['details'] as $detail) {
                $btlParCaisse = (int)($detail['bouteilles_par_caisses'] ?? 24);
                $caisses = intdiv((int)$detail['quantite'], $btlParCaisse);
                $caissesVidesRecues = (int)($detail['caisses_vides_recues'] ?? 0);
                $totalCaissesVidesRecues += $caissesVidesRecues;
                $totalDetteCaisses += max(0, $caisses - $caissesVidesRecues);
            }
        ?>
        <div class="divider"></div>
        <div class="space-y-1 text-[11px]">
            <div class="ticket-row">
                <span>Emballages reçus:</span>
                <span><?= number_format($totalCaissesVidesRecues, 0, '.', ' ') ?> cs</span>
            </div>
            <div class="ticket-row">
                <span>Emballages dus:</span>
                <span><?= number_format($totalDetteCaisses, 0, '.', ' ') ?> cs</span>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="text-center text-[10px] mt-4">
            <p>Merci pour votre confiance !</p>
            <p>Vendeur: <?= htmlspecialchars(($vente['created_by_prenom'] ?? '') . ' ' . ($vente['created_by_nom'] ?? '')) ?></p>
        </div>
        
        <!-- Boutons d'action -->
        <div class="no-print mt-8 flex flex-col gap-2">
            <button onclick="window.print()" class="w-full py-2 bg-blue-600 text-white rounded font-bold">
                IMPRIMER
            </button>
            <a href="<?= url('ventes/' . $vente['id']) ?>" class="w-full py-2 bg-gray-200 text-center text-gray-700 rounded">
                RETOUR
            </a>
        </div>
    </div>

    <script>
        // Lancer l'impression automatiquement au chargement
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
