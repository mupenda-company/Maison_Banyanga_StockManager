<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des ventes par véhicule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        .info-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .info-bar div {
            font-size: 11px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px;
            background: #e0e0e0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .client-section {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        .client-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ccc;
        }
        .client-name {
            font-weight: bold;
            font-size: 13px;
        }
        .client-info {
            font-size: 11px;
            color: #666;
        }
        .debt-warning {
            color: #d32f2f;
            font-weight: bold;
            font-size: 11px;
        }
        .no-debt {
            color: #388e3c;
            font-size: 11px;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 2px solid #333;
            border-radius: 4px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($params['nom_entreprise'] ?? 'Bralima Logistique') ?></h1>
        <p><?= htmlspecialchars($params['adresse'] ?? '') ?> | <?= htmlspecialchars($params['telephone'] ?? '') ?></p>
        <p><?= htmlspecialchars($params['email'] ?? '') ?></p>
    </div>

    <div class="info-bar">
        <div><strong>Véhicule:</strong> <?= htmlspecialchars($vehicule['immatriculation']) ?></div>
        <div><strong>Période:</strong> Du <?= date('d/m/Y', strtotime($dateDebut)) ?> au <?= date('d/m/Y', strtotime($dateFin)) ?></div>
        <div><strong>Date d'impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <div class="section">
        <div class="section-title">Résumé</div>
        <?php
        $totalVentes = count($ventes);
        $totalTTC = array_sum(array_column($ventes, 'total_ttc'));
        $totalCaissesVendues = 0;
        $totalCaissesRecues = 0;
        $totalDette = 0;
        
        foreach ($ventes as $vente) {
            foreach ($vente['details'] as $detail) {
                $totalCaissesVendues += $detail['quantite_caisses'];
                $totalCaissesRecues += $detail['caisses_vides_recues'];
                $totalDette += $detail['dette_caisses'];
            }
        }
        ?>
        <div class="summary">
            <div class="summary-row">
                <span>Nombre de ventes:</span>
                <span><?= $totalVentes ?></span>
            </div>
            <div class="summary-row">
                <span>Total TTC:</span>
                <span><?= format_money_converted($totalTTC) ?></span>
            </div>
            <div class="summary-row">
                <span>Total caisses vendues:</span>
                <span><?= number_format($totalCaissesVendues, 1) ?></span>
            </div>
            <div class="summary-row">
                <span>Total caisses reçues:</span>
                <span><?= number_format($totalCaissesRecues, 1) ?></span>
            </div>
            <div class="summary-row total">
                <span>Dette totale emballages:</span>
                <span class="<?= $totalDette > 0 ? 'debt-warning' : 'no-debt' ?>"><?= number_format($totalDette, 1) ?> caisses</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Détail par client</div>
        
        <?php
        // Grouper les ventes par client
        $clientsData = [];
        foreach ($ventes as $vente) {
            $clientId = $vente['client_id'];
            if (!isset($clientsData[$clientId])) {
                $clientsData[$clientId] = [
                    'nom' => $vente['client_nom'],
                    'telephone' => $vente['client_telephone'],
                    'adresse' => $vente['client_adresse'],
                    'zone' => $vente['zone_nom'],
                    'ventes' => [],
                    'total_caisses_vendues' => 0,
                    'total_caisses_recues' => 0,
                    'total_dette' => 0,
                    'total_ttc' => 0
                ];
            }
            $clientsData[$clientId]['ventes'][] = $vente;
            $clientsData[$clientId]['total_ttc'] += $vente['total_ttc'];
            
            foreach ($vente['details'] as $detail) {
                $clientsData[$clientId]['total_caisses_vendues'] += $detail['quantite_caisses'];
                $clientsData[$clientId]['total_caisses_recues'] += $detail['caisses_vides_recues'];
                $clientsData[$clientId]['total_dette'] += $detail['dette_caisses'];
            }
        }
        
        foreach ($clientsData as $clientId => $clientData):
        ?>
        <div class="client-section">
            <div class="client-header">
                <div>
                    <div class="client-name"><?= htmlspecialchars($clientData['nom']) ?></div>
                    <div class="client-info">
                        Tél: <?= htmlspecialchars($clientData['telephone'] ?? 'N/A') ?> | 
                        Zone: <?= htmlspecialchars($clientData['zone'] ?? 'N/A') ?>
                    </div>
                </div>
                <div>
                    <?php if ($clientData['total_dette'] > 0): ?>
                    <span class="debt-warning">Dette: <?= number_format($clientData['total_dette'], 1) ?> caisses</span>
                    <?php else: ?>
                    <span class="no-debt">Aucune dette</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Produit</th>
                        <th>Caisses</th>
                        <th>Reçues</th>
                        <th>Dette</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientData['ventes'] as $vente): ?>
                        <?php foreach ($vente['details'] as $detail): ?>
                        <tr>
                            <td><?= htmlspecialchars($vente['numero_facture']) ?></td>
                            <td><?= date('d/m/Y', strtotime($vente['date_vente'])) ?></td>
                            <td><?= htmlspecialchars($detail['produit_nom']) ?></td>
                            <td><?= number_format($detail['quantite_caisses'], 1) ?></td>
                            <td><?= number_format($detail['caisses_vides_recues'], 1) ?></td>
                            <td class="<?= $detail['dette_caisses'] > 0 ? 'debt-warning' : 'no-debt' ?>">
                                <?= number_format($detail['dette_caisses'], 1) ?>
                            </td>
                            <td><?= format_money_converted($detail['sous_total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f0f0f0; font-weight: bold;">
                        <td colspan="3">Total client</td>
                        <td><?= number_format($clientData['total_caisses_vendues'], 1) ?></td>
                        <td><?= number_format($clientData['total_caisses_recues'], 1) ?></td>
                        <td class="<?= $clientData['total_dette'] > 0 ? 'debt-warning' : 'no-debt' ?>">
                            <?= number_format($clientData['total_dette'], 1) ?>
                        </td>
                        <td><?= format_money_converted($clientData['total_ttc']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Fermer
        </button>
    </div>
</body>
</html>
