<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inventaire complet</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Inventaire complet du stock'); ?>
    <div class="info-bar">
        <div><strong>Emplacement:</strong> <?= htmlspecialchars($filters['emplacement_id'] ?? 'Tous') ?></div>
        <div><strong>Categorie:</strong> <?= htmlspecialchars($filters['categorie'] ?? 'Toutes') ?></div>
        <div><strong>Date stock:</strong> <?= !empty($filters['date_stock']) ? date('d/m/Y', strtotime($filters['date_stock'])) : 'Actuel' ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Produits</div><div class="summary-value"><?= (int) ($totaux['nb_produits'] ?? 0) ?></div></div>
        <div class="summary-item"><div class="summary-label">Caisses pleines</div><div class="summary-value"><?= number_format((float) ($totaux['caisses_pleine'] ?? 0), 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Caisses vides</div><div class="summary-value"><?= number_format((float) ($totaux['caisses_vide'] ?? 0), 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Valeur estimee</div><div class="summary-value"><?= format_money_converted($totaux['valeur'] ?? 0) ?></div></div>
    </div>

    <?php $pivot = $stockPivot ?? ['vehicles' => [], 'rows' => [], 'totals' => []]; ?>
    <div class="section-title">Inventaire par produit, entrepot et vehicule</div>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class="num">Entrepot</th>
                <?php foreach (($pivot['vehicles'] ?? []) as $vehicle): ?>
                    <th class="num"><?= htmlspecialchars($vehicle) ?></th>
                <?php endforeach; ?>
                <th class="num">Emballages</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pivot['rows'])): ?>
                <tr><td colspan="<?= 4 + count($pivot['vehicles'] ?? []) ?>" class="center">Aucun enregistrement trouve</td></tr>
            <?php else: foreach ($pivot['rows'] as $item): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item['produit'] ?? '') ?></strong></td>
                    <td class="num"><?= number_format((float) ($item['entrepot'] ?? 0), 2, ',', ' ') ?></td>
                    <?php foreach (($pivot['vehicles'] ?? []) as $vehicle): ?>
                        <td class="num"><?= number_format((float) ($item['vehicles'][$vehicle] ?? 0), 2, ',', ' ') ?></td>
                    <?php endforeach; ?>
                    <td class="num"><?= number_format((float) ($item['emballages'] ?? 0), 2, ',', ' ') ?></td>
                    <td class="num"><?= number_format((float) ($item['total'] ?? 0), 2, ',', ' ') ?></td>
                </tr>
            <?php endforeach; ?>
                <tr style="font-weight:700;background:#f3f4f6;">
                    <td>TOTAL</td>
                    <td class="num"><?= number_format((float) ($pivot['totals']['entrepot'] ?? 0), 2, ',', ' ') ?></td>
                    <?php foreach (($pivot['vehicles'] ?? []) as $vehicle): ?>
                        <td class="num"><?= number_format((float) ($pivot['totals']['vehicles'][$vehicle] ?? 0), 2, ',', ' ') ?></td>
                    <?php endforeach; ?>
                    <td class="num"><?= number_format((float) ($pivot['totals']['emballages'] ?? 0), 2, ',', ' ') ?></td>
                    <td class="num"><?= number_format((float) ($pivot['totals']['total'] ?? 0), 2, ',', ' ') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
