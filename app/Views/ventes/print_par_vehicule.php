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
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .page-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .page-header h1 {
            font-size: 16px;
            margin-bottom: 3px;
        }
        .page-header p {
            font-size: 10px;
            color: #666;
        }
        .info-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f5f5f5;
            border: 1px solid #ccc;
        }
        .info-bar div {
            font-size: 10px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 15px;
            padding: 4px 8px;
            background: #e0e0e0;
        }
        .summary {
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #999;
            margin-bottom: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 12px;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #999;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th, td {
            border: 1px solid #999;
            padding: 4px 6px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
        }
        td.num {
            text-align: right;
        }
        tfoot td {
            background: #e8e8e8;
            font-weight: bold;
        }
        .client-row td {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }
        .debt-warn {
            color: #c62828;
            font-weight: bold;
        }
        .no-debt {
            color: #2e7d32;
        }
        @media print {
            body {
                padding: 0;
                font-size: 10px;
            }
            .no-print {
                display: none !important;
            }
            .page-header {
                position: running(header);
            }
            @page {
                size: A4 landscape;
                margin: 15mm 10mm;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1><?= htmlspecialchars($params['nom_entreprise'] ?? 'Bralima Logistique') ?></h1>
        <p><?= htmlspecialchars($params['adresse'] ?? '') ?> | <?= htmlspecialchars($params['telephone'] ?? '') ?> | <?= htmlspecialchars($params['email'] ?? '') ?></p>
    </div>

    <div class="info-bar">
        <div><strong>Véhicule:</strong> <?= htmlspecialchars($vehicule['immatriculation']) ?></div>
        <div><strong>Période:</strong> Du <?= date('d/m/Y', strtotime($dateDebut)) ?> au <?= date('d/m/Y', strtotime($dateFin)) ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $totalVentes = count($ventes);
    $totalTTC = array_sum(array_column($ventes, 'total_ttc'));
    $totalCaissesVendues = 0;
    $totalCaissesRecues = 0;
    $totalDette = 0;
    
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
            $totalCaissesVendues += $detail['quantite_caisses'];
            $totalCaissesRecues += $detail['caisses_vides_recues'];
            $totalDette += $detail['dette_caisses'];
        }
    }
    ?>

    <div class="section-title">Résumé général</div>
    <div class="summary">
        <div class="summary-row"><span>Nombre de ventes:</span><span><?= $totalVentes ?></span></div>
        <div class="summary-row"><span>Total TTC:</span><span><?= format_money_converted($totalTTC) ?></span></div>
        <div class="summary-row"><span>Total caisses vendues:</span><span><?= number_format($totalCaissesVendues, 1) ?></span></div>
        <div class="summary-row"><span>Total caisses reçues:</span><span><?= number_format($totalCaissesRecues, 1) ?></span></div>
        <div class="summary-row total"><span>Dette totale emballages:</span><span class="<?= $totalDette > 0 ? 'debt-warn' : 'no-debt' ?>"><?= number_format($totalDette, 1) ?> caisses</span></div>
    </div>

    <div class="section-title">Détail des ventes par client</div>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Tél</th>
                <th>Zone</th>
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
            <?php $rowNum = 0; ?>
            <?php foreach ($clientsData as $clientId => $clientData): ?>
                <?php $firstVente = true; ?>
                <?php foreach ($clientData['ventes'] as $vente): ?>
                    <?php $firstDetail = true; ?>
                    <?php foreach ($vente['details'] as $detail): ?>
                    <tr>
                        <?php if ($firstVente && $firstDetail): ?>
                        <td rowspan="<?= count(array_merge(...array_map(function($v) { return $v['details']; }, $clientData['ventes']))) ?>"><?= htmlspecialchars($clientData['nom']) ?></td>
                        <td rowspan="<?= count(array_merge(...array_map(function($v) { return $v['details']; }, $clientData['ventes']))) ?>"><?= htmlspecialchars($clientData['telephone'] ?? 'N/A') ?></td>
                        <td rowspan="<?= count(array_merge(...array_map(function($v) { return $v['details']; }, $clientData['ventes']))) ?>"><?= htmlspecialchars($clientData['zone'] ?? 'N/A') ?></td>
                        <?php $firstVente = false; ?>
                        <?php endif; ?>
                        <?php if ($firstDetail): ?>
                        <td rowspan="<?= count($vente['details']) ?>"><?= htmlspecialchars($vente['numero_facture']) ?></td>
                        <td rowspan="<?= count($vente['details']) ?>"><?= date('d/m/Y', strtotime($vente['date_vente'])) ?></td>
                        <?php $firstDetail = false; ?>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($detail['produit_nom']) ?></td>
                        <td class="num"><?= number_format($detail['quantite_caisses'], 1) ?></td>
                        <td class="num"><?= number_format($detail['caisses_vides_recues'], 1) ?></td>
                        <td class="num <?= $detail['dette_caisses'] > 0 ? 'debt-warn' : 'no-debt' ?>"><?= number_format($detail['dette_caisses'], 1) ?></td>
                        <td class="num"><?= format_money_converted($detail['sous_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <!-- Ligne total client -->
                <tr class="client-row">
                    <td colspan="3">Total <?= htmlspecialchars($clientData['nom']) ?></td>
                    <td colspan="3"></td>
                    <td class="num"><?= number_format($clientData['total_caisses_vendues'], 1) ?></td>
                    <td class="num"><?= number_format($clientData['total_caisses_recues'], 1) ?></td>
                    <td class="num <?= $clientData['total_dette'] > 0 ? 'debt-warn' : 'no-debt' ?>"><?= number_format($clientData['total_dette'], 1) ?></td>
                    <td class="num"><?= format_money_converted($clientData['total_ttc']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #ccc; font-weight: bold; font-size: 11px;">
                <td colspan="6">TOTAL GÉNÉRAL</td>
                <td class="num"><?= number_format($totalCaissesVendues, 1) ?></td>
                <td class="num"><?= number_format($totalCaissesRecues, 1) ?></td>
                <td class="num <?= $totalDette > 0 ? 'debt-warn' : 'no-debt' ?>"><?= number_format($totalDette, 1) ?></td>
                <td class="num"><?= format_money_converted($totalTTC) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 13px; cursor: pointer;">
            Imprimer
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; font-size: 13px; cursor: pointer; margin-left: 8px;">
            Fermer
        </button>
    </div>
</body>
</html>
