<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ristournes</title>
    <?= print_report_css(true) ?>
    <style>
        .ristourne-table th,
        .ristourne-table td {
            font-size: 8px;
            padding: 3px 4px;
            line-height: 1.2;
        }
        .ristourne-table .num {
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: normal;
        }
        .ristourne-table .obs-cell,
        .ristourne-table .sign-cell {
            min-height: 24px;
        }
    </style>
</head>
<body>
<div class="page">
    <?php print_report_header('Ristournes'); ?>
    <div class="info-bar">
        <div><strong>Mois:</strong> <?= htmlspecialchars($filters['mois'] ?? date('n')) ?></div>
        <div><strong>Annee:</strong> <?= htmlspecialchars($filters['annee'] ?? date('Y')) ?></div>
        <div><strong>Client:</strong> <?= htmlspecialchars($filters['client_id'] ?? 'Tous') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $totalCa = 0;
    $totalRistourne = 0;
    $payees = 0;
    foreach ($ristournes as $row) {
        $totalCa += (float) ($row['ca_total'] ?? 0);
        $totalRistourne += (float) ($row['montant_ristourne'] ?? 0);
        if (($row['statut'] ?? '') === 'payee') {
            $payees++;
        }
    }
    ?>
    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Clients</div><div class="summary-value"><?= count($ristournes) ?></div></div>
        <div class="summary-item"><div class="summary-label">Chiffre affaires</div><div class="summary-value"><?= format_money_converted($totalCa) ?></div></div>
        <div class="summary-item"><div class="summary-label">Ristournes</div><div class="summary-value ok"><?= format_money_converted($totalRistourne) ?></div></div>
        <div class="summary-item"><div class="summary-label">Payees</div><div class="summary-value"><?= $payees ?></div></div>
    </div>

    <?php
    $report = $report ?? ['produits' => [], 'rows' => $ristournes];
    $produitsRistourne = $report['produits'] ?? [];
    $rowsRistourne = $report['rows'] ?? [];
    ?>
    <div class="section-title">Liste de livraison des ristournes</div>
    <?php
    $nbProduits = count($produitsRistourne);
    $productWidth = $nbProduits > 0 ? max(4, min(7, floor(18 / $nbProduits))) : 0;
    ?>
    <table class="ristourne-table">
        <colgroup>
            <col style="width: 8%">
            <col style="width: 13%">
            <col style="width: 6%">
            <col style="width: 10%">
            <col style="width: 9%">
            <?php foreach ($produitsRistourne as $_): ?>
                <col style="width: <?= $productWidth ?>%">
            <?php endforeach; ?>
            <col style="width: 9%">
            <col style="width: 9%">
            <col style="width: 12%">
            <col style="width: 12%">
        </colgroup>
        <thead>
            <tr>
                <th>Zone</th>
                <th>Client</th>
                <th class="num">Total colis</th>
                <th class="num">Chiffre affaires</th>
                <th class="num">Ristourne</th>
                <?php foreach ($produitsRistourne as $produit): ?>
                    <th class="num"><?= htmlspecialchars($produit['nom'] ?? '') ?></th>
                <?php endforeach; ?>
                <th class="num">Montant restant</th>
                <th class="num">Montant a<br>completer</th>
                <th>Observation</th>
                <th>Signature client</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rowsRistourne)): ?>
                <tr><td colspan="<?= 9 + count($produitsRistourne) ?>" class="center">Aucune ristourne calculee pour cette periode</td></tr>
            <?php else: foreach ($rowsRistourne as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['zone_nom'] ?? '') ?></td>
                    <td><strong><?= htmlspecialchars($row['client_nom'] ?? '') ?></strong></td>
                    <td class="num"><?= number_format((int) ($row['total_caisses'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num"><?= format_money_converted($row['ca_total'] ?? 0) ?></td>
                    <td class="num"><?= format_money_converted($row['montant_ristourne'] ?? 0) ?></td>
                    <?php foreach ($produitsRistourne as $produit): ?>
                        <td class="num"><?= number_format((int) ($row['produits'][(int) $produit['id']]['caisses'] ?? 0), 0, ',', ' ') ?></td>
                    <?php endforeach; ?>
                    <td class="num"><?= format_money_converted($row['montant_restant'] ?? 0) ?></td>
                    <td class="num"><?= format_money_converted($row['montant_a_completer'] ?? 0) ?></td>
                    <td class="obs-cell"></td>
                    <td class="sign-cell"></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="num"><?= format_money_converted($totalCa) ?></td>
                <td class="num"><?= format_money_converted($totalRistourne) ?></td>
                <td colspan="<?= 4 + count($produitsRistourne) ?>"></td>
            </tr>
        </tfoot>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
