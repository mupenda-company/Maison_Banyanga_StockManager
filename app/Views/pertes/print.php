<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pertes</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Pertes et casses'); ?>
    <div class="info-bar">
        <div><strong>Du:</strong> <?= !empty($filters['date_debut']) ? date('d/m/Y', strtotime($filters['date_debut'])) : 'Debut' ?></div>
        <div><strong>Au:</strong> <?= !empty($filters['date_fin']) ? date('d/m/Y', strtotime($filters['date_fin'])) : 'Fin' ?></div>
        <div><strong>Type:</strong> <?= htmlspecialchars($filters['type_perte'] ?? 'Tous') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $totalCaisses = 0;
    $totalValeur = 0;
    foreach ($pertes as $perte) {
        $totalCaisses += (float) ($perte['quantite'] ?? 0);
        $totalValeur += (float) ($perte['valeur_perte'] ?? 0);
    }
    ?>
    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Declarations</div><div class="summary-value"><?= count($pertes) ?></div></div>
        <div class="summary-item"><div class="summary-label">Caisses perdues</div><div class="summary-value warn"><?= number_format($totalCaisses, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Valeur</div><div class="summary-value warn"><?= format_money_dual($totalValeur) ?></div></div>
        <div class="summary-item"><div class="summary-label">Mois courant</div><div class="summary-value"><?= number_format((float) ($stats['total_caisses'] ?? 0), 2, ',', ' ') ?> cs</div></div>
    </div>

    <div class="section-title">Detail des pertes</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Date</th>
                <th style="width: 21%">Produit</th>
                <th style="width: 10%">Stock</th>
                <th style="width: 11%">Type</th>
                <th style="width: 10%">Quantite</th>
                <th style="width: 12%">Valeur</th>
                <th style="width: 14%">Agent</th>
                <th style="width: 12%">Emplacement</th>
                <th style="width: 18%">Motif</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pertes)): ?>
                <tr><td colspan="9" class="center">Aucune perte enregistree</td></tr>
            <?php else: foreach ($pertes as $perte): ?>
                <tr>
                    <td><?= !empty($perte['date_perte']) ? date('d/m/Y', strtotime($perte['date_perte'])) : '' ?></td>
                    <td><strong><?= htmlspecialchars($perte['produit_nom'] ?? '') ?></strong><br><span class="muted"><?= htmlspecialchars($perte['produit_code'] ?? '') ?></span></td>
                    <td class="center"><?= htmlspecialchars($perte['type_stock'] ?? '-') ?></td>
                    <td class="center"><?= htmlspecialchars($perte['type_perte'] ?? '-') ?></td>
                    <td class="num"><?= number_format((float) ($perte['quantite'] ?? 0), 2, ',', ' ') ?> cs</td>
                    <td class="num"><?= format_money_dual($perte['valeur_perte'] ?? 0) ?></td>
                    <td><?= htmlspecialchars(trim(($perte['agent_prenom'] ?? '') . ' ' . ($perte['agent_nom'] ?? '')) ?: '-') ?></td>
                    <td><?= htmlspecialchars($perte['emplacement_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($perte['motif'] ?? '-') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4">TOTAL</td>
                <td class="num"><?= number_format($totalCaisses, 2, ',', ' ') ?> cs</td>
                <td class="num"><?= format_money_dual($totalValeur) ?></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
