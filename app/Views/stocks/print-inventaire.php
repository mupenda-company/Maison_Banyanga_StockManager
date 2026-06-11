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

    <div class="section-title">Detail inventaire</div>
    <table>
        <thead>
            <tr>
                <th style="width: 24%">Produit</th>
                <th style="width: 16%">Categorie</th>
                <th style="width: 24%">Emplacement</th>
                <th style="width: 10%">Type</th>
                <th style="width: 12%">Plein</th>
                <th style="width: 12%">Vide</th>
                <th style="width: 8%">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inventaire)): ?>
                <tr><td colspan="7" class="center">Aucun enregistrement trouve</td></tr>
            <?php else: foreach ($inventaire as $item):
                $critique = (float) ($item['quantite_pleine'] ?? 0) <= (float) ($item['seuil_alerte'] ?? 0);
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item['produit_nom'] ?? '') ?></strong><br><span class="muted"><?= htmlspecialchars($item['produit_code'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($item['categorie'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['emplacement_nom'] ?? '') ?><?php if (!empty($item['vehicule'])): ?><br><span class="muted"><?= htmlspecialchars($item['vehicule']) ?></span><?php endif; ?></td>
                    <td class="center"><?= htmlspecialchars(ucfirst($item['emplacement_type'] ?? '')) ?></td>
                    <td class="num"><?= number_format((float) ($item['caisses_pleine'] ?? 0), 2, ',', ' ') ?> cs</td>
                    <td class="num"><?= number_format((float) ($item['caisses_vide'] ?? 0), 2, ',', ' ') ?> cs</td>
                    <td class="center <?= $critique ? 'warn' : 'ok' ?>"><?= $critique ? 'CRIT' : 'OK' ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
