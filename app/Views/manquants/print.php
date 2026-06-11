<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Manquants</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Manquants agents'); ?>
    <div class="info-bar">
        <div><strong>Du:</strong> <?= !empty($filters['date_debut']) ? date('d/m/Y', strtotime($filters['date_debut'])) : 'Debut' ?></div>
        <div><strong>Au:</strong> <?= !empty($filters['date_fin']) ? date('d/m/Y', strtotime($filters['date_fin'])) : 'Fin' ?></div>
        <div><strong>Statut:</strong> <?= htmlspecialchars($filters['statut'] ?? 'Tous') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $totalMontant = 0;
    $totalPaye = 0;
    $totalReste = 0;
    $totalCaisses = 0;
    foreach ($manquants as $row) {
        $totalMontant += (float) ($row['montant'] ?? 0);
        $totalPaye += (float) ($row['montant_paye'] ?? 0);
        $totalReste += (float) ($row['reste_montant'] ?? 0);
        $totalCaisses += (float) ($row['quantite_caisses'] ?? 0);
    }
    ?>
    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Declarations</div><div class="summary-value"><?= count($manquants) ?></div></div>
        <div class="summary-item"><div class="summary-label">Caisses</div><div class="summary-value"><?= number_format($totalCaisses, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Montant</div><div class="summary-value"><?= format_money_dual($totalMontant) ?></div></div>
        <div class="summary-item"><div class="summary-label">Reste</div><div class="summary-value warn"><?= format_money_dual($totalReste) ?></div></div>
    </div>

    <div class="section-title">Detail des manquants</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Date</th>
                <th style="width: 18%">Agent</th>
                <th style="width: 18%">Produit</th>
                <th style="width: 9%">Caisses</th>
                <th style="width: 12%">Montant</th>
                <th style="width: 12%">Paye</th>
                <th style="width: 12%">Reste</th>
                <th style="width: 9%">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($manquants)): ?>
                <tr><td colspan="8" class="center">Aucun manquant trouve</td></tr>
            <?php else: foreach ($manquants as $row): ?>
                <tr>
                    <td><?= !empty($row['date_manquant']) ? date('d/m/Y', strtotime($row['date_manquant'])) : '' ?></td>
                    <td><?= htmlspecialchars($row['agent_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['produit_nom'] ?: '-') ?></td>
                    <td class="num"><?= number_format((float) ($row['quantite_caisses'] ?? 0), 2, ',', ' ') ?></td>
                    <td class="num"><?= format_money_dual($row['montant'] ?? 0) ?></td>
                    <td class="num"><?= format_money_dual($row['montant_paye'] ?? 0) ?></td>
                    <td class="num <?= ((float) ($row['reste_montant'] ?? 0)) > 0 ? 'warn' : 'ok' ?>"><?= format_money_dual($row['reste_montant'] ?? 0) ?></td>
                    <td class="center"><?= htmlspecialchars($row['statut'] ?? '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="num"><?= number_format($totalCaisses, 2, ',', ' ') ?></td>
                <td class="num"><?= format_money_dual($totalMontant) ?></td>
                <td class="num"><?= format_money_dual($totalPaye) ?></td>
                <td class="num"><?= format_money_dual($totalReste) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
