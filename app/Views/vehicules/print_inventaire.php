<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire des véhicules</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .page-header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
        }
        .page-header h1 {
            font-size: 15px;
            margin-bottom: 2px;
        }
        .page-header p {
            font-size: 9px;
            color: #666;
        }
        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }
        .summary-item {
            flex: 1;
            min-width: 100px;
            padding: 6px 8px;
            background: #f5f5f5;
            border: 1px solid #ccc;
            text-align: center;
        }
        .summary-item .label {
            font-size: 8px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 2px;
        }
        .summary-item .value {
            font-size: 13px;
            font-weight: bold;
        }
        .vehicule-section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .vehicule-title {
            font-size: 11px;
            font-weight: bold;
            padding: 4px 8px;
            background: #e0e0e0;
            border: 1px solid #999;
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .vehicule-title .left {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .vehicule-title .right {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 10px;
        }
        .stat-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .stat-badge.dispo { background: #c8e6c9; color: #2e7d32; }
        .stat-badge.mission { background: #fff9c4; color: #f57f17; }
        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stock-table th, .stock-table td {
            border: 1px solid #999;
            padding: 3px 6px;
            font-size: 9px;
        }
        .stock-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .stock-table td.num {
            text-align: right;
        }
        .stock-table tfoot td {
            background: #e8e8e8;
            font-weight: bold;
        }
        .occ-high { color: #c62828; font-weight: bold; }
        .occ-mid { color: #f57f17; font-weight: bold; }
        .occ-low { color: #2e7d32; font-weight: bold; }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page {
                size: A4 landscape;
                margin: 12mm 8mm;
            }
            .vehicule-section {
                page-break-inside: avoid;
            }
            .stock-table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
            }
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1><?= htmlspecialchars($params['nom_entreprise'] ?? 'Bralima Logistique') ?></h1>
        <p><?= htmlspecialchars($params['adresse'] ?? '') ?> | <?= htmlspecialchars($params['telephone'] ?? '') ?> | <?= htmlspecialchars($params['email'] ?? '') ?></p>
        <p style="margin-top:4px; font-weight:bold; font-size:11px;">Inventaire des véhicules — <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Véhicules</div>
            <div class="value"><?= (int) ($totaux['vehicules'] ?? 0) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Disponibles</div>
            <div class="value" style="color:#2e7d32"><?= (int) ($totaux['disponibles'] ?? 0) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">En mission</div>
            <div class="value" style="color:#f57f17"><?= (int) ($totaux['en_mission'] ?? 0) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Caisses pleines</div>
            <div class="value" style="color:#2e7d32"><?= number_format((int) ($totaux['caisses_pleine'] ?? 0), 0, ',', ' ') ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Caisses vides</div>
            <div class="value"><?= number_format((int) ($totaux['caisses_vide'] ?? 0), 0, ',', ' ') ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Capacité totale</div>
            <div class="value"><?= number_format((int) ($totaux['capacite'] ?? 0), 0, ',', ' ') ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Occupation moyenne</div>
            <div class="value"><?= number_format((float) ($totaux['occupation_moyenne'] ?? 0), 1, ',', ' ') ?>%</div>
        </div>
    </div>

    <?php foreach ($vehicules as $vehicule):
        $stockPleines = (int) ($vehicule['stock_caisses_pleine'] ?? 0);
        $stockVides = (int) ($vehicule['stock_caisses_vide'] ?? 0);
        $stockTotal = $stockPleines + $stockVides;
        $capacite = (int) ($vehicule['capacite'] ?? 0);
        $occupation = (float) ($vehicule['occupation_pourcentage'] ?? 0);
        $enMission = (int) ($vehicule['en_mission'] ?? 0) > 0;
        $occClass = $occupation >= 90 ? 'occ-high' : ($occupation >= 75 ? 'occ-mid' : 'occ-low');
    ?>
    <div class="vehicule-section">
        <div class="vehicule-title">
            <div class="left">
                <span><?= htmlspecialchars($vehicule['immatriculation'] ?? '') ?></span>
                <span style="font-weight:normal; color:#666;"><?= htmlspecialchars(trim(($vehicule['marque'] ?? '') . ' ' . ($vehicule['modele'] ?? ''))) ?></span>
                <span style="font-weight:normal; color:#666;">Agent: <?= htmlspecialchars(trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? '')) ?: 'N/A') ?></span>
                <span style="font-weight:normal; color:#666;">Empl: <?= htmlspecialchars($vehicule['emplacement_nom'] ?? 'N/A') ?></span>
            </div>
            <div class="right">
                <span class="stat-badge <?= $enMission ? 'mission' : 'dispo' ?>"><?= $enMission ? 'En mission' : 'Disponible' ?></span>
                <span>Capacité: <b><?= number_format($capacite, 0, ',', ' ') ?></b></span>
                <span>Pleines: <b style="color:#2e7d32"><?= number_format($stockPleines, 0, ',', ' ') ?></b></span>
                <span>Vides: <b><?= number_format($stockVides, 0, ',', ' ') ?></b></span>
                <span>Total: <b><?= number_format($stockTotal, 0, ',', ' ') ?></b></span>
                <span class="<?= $occClass ?>"><?= number_format($occupation, 1, ',', ' ') ?>%</span>
            </div>
        </div>
        <?php if (!empty($vehicule['stock'])): ?>
        <table class="stock-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Code</th>
                    <th>Btl/Caisse</th>
                    <th>Caisses pleines</th>
                    <th>Caisses vides</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totPleine = 0; $totVide = 0;
                foreach ($vehicule['stock'] as $ligne): 
                    $pleine = (int) round((float) ($ligne['caisses_pleine'] ?? 0));
                    $vide = (int) round((float) ($ligne['caisses_vide'] ?? 0));
                    $totPleine += $pleine;
                    $totVide += $vide;
                ?>
                <tr>
                    <td><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></td>
                    <td style="text-align:center"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></td>
                    <td class="num"><?= (int) ($ligne['bouteilles_par_caisses'] ?? 24) ?></td>
                    <td class="num" style="color:#2e7d32; font-weight:bold"><?= number_format($pleine, 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format($vide, 0, ',', ' ') ?></td>
                    <td class="num" style="font-weight:bold"><?= number_format($pleine + $vide, 0, ',', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total véhicule</td>
                    <td class="num" style="color:#2e7d32"><?= number_format($totPleine, 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format($totVide, 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format($totPleine + $totVide, 0, ',', ' ') ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div style="padding:6px 8px; border:1px solid #999; border-top:0; color:#999; font-style:italic;">Aucun stock dans ce véhicule.</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

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
