<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Stock</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Etat du stock'); ?>
    <div class="info-bar">
        <div><strong>Produit:</strong> <?= htmlspecialchars($filters['produit_id'] ?? 'Tous') ?></div>
        <div><strong>Emplacement:</strong> <?= htmlspecialchars($filters['emplacement_id'] ?? 'Tous') ?></div>
        <div><strong>Date stock:</strong> <?= !empty($filters['date_stock']) ? date('d/m/Y', strtotime($filters['date_stock'])) : 'Actuel' ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $totalPlein = 0;
    $totalVide = 0;
    $nbCritique = 0;
    foreach ($stocks as $stock) {
        $totalPlein += (float) ($stock['caisses_pleine'] ?? 0);
        $totalVide += (float) ($stock['caisses_vide'] ?? 0);
        if ((float) ($stock['caisses_pleine'] ?? 0) <= (float) ($stock['seuil_alerte'] ?? 0)) {
            $nbCritique++;
        }
    }
    ?>
    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Lignes</div><div class="summary-value"><?= count($stocks) ?></div></div>
        <div class="summary-item"><div class="summary-label">Caisses pleines</div><div class="summary-value"><?= number_format($totalPlein, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Caisses vides</div><div class="summary-value"><?= number_format($totalVide, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Critiques</div><div class="summary-value warn"><?= $nbCritique ?></div></div>
    </div>

    <div class="section-title">Detail par produit et emplacement</div>
    <table>
        <thead>
            <tr>
                <th style="width: 26%">Produit</th>
                <th style="width: 24%">Emplacement</th>
                <th style="width: 12%">Type</th>
                <th style="width: 14%">Plein</th>
                <th style="width: 14%">Vide</th>
                <th style="width: 10%">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stocks)): ?>
                <tr><td colspan="6" class="center">Aucun stock trouve</td></tr>
            <?php else: foreach ($stocks as $stock):
                $critique = (float) ($stock['caisses_pleine'] ?? 0) <= (float) ($stock['seuil_alerte'] ?? 0);
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($stock['produit_nom'] ?? '') ?></strong><br><span class="muted"><?= htmlspecialchars($stock['produit_code'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($stock['emplacement_nom'] ?? '') ?><?php if (!empty($stock['vehicule_immatriculation'])): ?><br><span class="muted"><?= htmlspecialchars($stock['vehicule_immatriculation']) ?></span><?php endif; ?></td>
                    <td class="center"><?= htmlspecialchars(ucfirst($stock['emplacement_type'] ?? '')) ?></td>
                    <td class="num"><?= number_format((float) ($stock['caisses_pleine'] ?? 0), 2, ',', ' ') ?> cs</td>
                    <td class="num"><?= number_format((float) ($stock['caisses_vide'] ?? 0), 2, ',', ' ') ?> cs</td>
                    <td class="center <?= $critique ? 'warn' : 'ok' ?>"><?= $critique ? 'CRITIQUE' : 'OK' ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
