<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ristournes</title>
    <?= print_report_css(true) ?>
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

    <div class="section-title">Detail des ristournes</div>
    <table>
        <thead>
            <tr>
                <th style="width: 28%">Client</th>
                <th style="width: 14%">Periode</th>
                <th style="width: 18%">Chiffre affaires</th>
                <th style="width: 10%">Taux</th>
                <th style="width: 18%">Ristourne</th>
                <th style="width: 12%">Statut</th>
                <th style="width: 12%">Paiement</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ristournes)): ?>
                <tr><td colspan="7" class="center">Aucune ristourne calculee pour cette periode</td></tr>
            <?php else: foreach ($ristournes as $row): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['client_nom'] ?? '') ?></strong></td>
                    <td class="center"><?= !empty($row['periode_debut']) ? date('m/Y', strtotime($row['periode_debut'])) : '' ?></td>
                    <td class="num"><?= format_money_converted($row['ca_total'] ?? 0) ?></td>
                    <td class="num"><?= number_format((float) ($row['taux_applique'] ?? 0), 2, ',', ' ') ?>%</td>
                    <td class="num"><?= format_money_converted($row['montant_ristourne'] ?? 0) ?></td>
                    <td class="center"><?= htmlspecialchars($row['statut'] ?? '') ?></td>
                    <td class="center"><?= !empty($row['date_paiement']) ? date('d/m/Y', strtotime($row['date_paiement'])) : '-' ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td class="num"><?= format_money_converted($totalCa) ?></td>
                <td></td>
                <td class="num"><?= format_money_converted($totalRistourne) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
