<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Depenses</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Liste des depenses'); ?>
    <div class="info-bar">
        <div><strong>Du:</strong> <?= !empty($filters['date_debut']) ? date('d/m/Y', strtotime($filters['date_debut'])) : 'Debut' ?></div>
        <div><strong>Au:</strong> <?= !empty($filters['date_fin']) ? date('d/m/Y', strtotime($filters['date_fin'])) : 'Fin' ?></div>
        <div><strong>Categorie:</strong> <?= htmlspecialchars($filters['categorie'] ?? 'Toutes') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $grandTotal = 0;
    foreach ($depenses as $depense) {
        $grandTotal += (float) ($depense['montant'] ?? 0);
    }
    ?>
    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Depenses</div><div class="summary-value"><?= count($depenses) ?></div></div>
        <div class="summary-item"><div class="summary-label">Total periode</div><div class="summary-value warn"><?= format_money_dual($grandTotal) ?></div></div>
        <div class="summary-item"><div class="summary-label">Total stats</div><div class="summary-value"><?= format_money_dual($stats['total_depenses'] ?? $grandTotal) ?></div></div>
        <div class="summary-item"><div class="summary-label">Genere le</div><div class="summary-value"><?= date('d/m/Y') ?></div></div>
    </div>

    <div class="section-title">Detail des depenses</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Date</th>
                <th style="width: 16%">Categorie</th>
                <th style="width: 36%">Description</th>
                <th style="width: 14%">Montant</th>
                <th style="width: 18%">Enregistre par</th>
                <th style="width: 10%">Devise</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($depenses)): ?>
                <tr><td colspan="6" class="center">Aucune depense trouvee</td></tr>
            <?php else: foreach ($depenses as $depense): ?>
                <tr>
                    <td><?= !empty($depense['date_depense']) ? date('d/m/Y', strtotime($depense['date_depense'])) : '' ?></td>
                    <td><?= htmlspecialchars($depense['categorie'] ?? '') ?></td>
                    <td><?= htmlspecialchars($depense['description'] ?? '') ?></td>
                    <td class="num"><?= format_money_dual($depense['montant'] ?? 0) ?></td>
                    <td><?= htmlspecialchars(trim(($depense['created_by_prenom'] ?? '') . ' ' . ($depense['created_by_nom'] ?? '')) ?: '-') ?></td>
                    <td class="center"><?= htmlspecialchars($depense['devise'] ?? get_base_devise()) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="num"><?= format_money_dual($grandTotal) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
