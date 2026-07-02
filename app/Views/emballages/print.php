<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord emballages</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Tableau de bord emballages', ($dateDebut ?? '') . ' au ' . ($dateFin ?? '')); ?>
    <div class="info-bar">
        <div><strong>Du:</strong> <?= htmlspecialchars($dateDebut ?? '') ?></div>
        <div><strong>Au:</strong> <?= htmlspecialchars($dateFin ?? '') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Disponibles</div><div class="summary-value"><?= number_format((int) ($stockEmballages['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Entrepot</div><div class="summary-value ok"><?= number_format((int) ($stockEmballages['fixe_caisses'] ?? 0), 0, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Vehicules</div><div class="summary-value"><?= number_format((int) ($stockEmballages['mobile_caisses'] ?? 0), 0, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Operations ouvertes</div><div class="summary-value warn"><?= number_format((int) ($resumeEmprunts['nb_en_cours'] ?? 0), 0, ',', ' ') ?></div></div>
    </div>

    <div class="section-title">Stock emballages par produit</div>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class="num">Total</th>
                <th class="num">Entrepot</th>
                <th class="num">Vehicules</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($stockEmballages['par_produit'] ?? []) as $ligne): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></strong><div class="muted"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></div></td>
                <td class="num"><?= number_format((int) ($ligne['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</td>
                <td class="num"><?= number_format((int) ($ligne['fixe_caisses'] ?? 0), 0, ',', ' ') ?> cs</td>
                <td class="num"><?= number_format((int) ($ligne['mobile_caisses'] ?? 0), 0, ',', ' ') ?> cs</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="section-title">Dettes clients</div>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Zone</th>
                <th>Produit</th>
                <th class="num">Dette</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clientsEmballage['lignes'] ?? [])): ?>
                <tr><td colspan="4" class="center muted">Aucune dette sur la periode</td></tr>
            <?php endif; ?>
            <?php foreach (($clientsEmballage['lignes'] ?? []) as $ligne): ?>
            <tr>
                <td><?= htmlspecialchars($ligne['client_nom'] ?? '') ?></td>
                <td><?= htmlspecialchars($ligne['zone_nom'] ?? '') ?></td>
                <td><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></td>
                <td class="num warn"><?= number_format((int) ($ligne['dette_caisses'] ?? 0), 0, ',', ' ') ?> cs</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php print_report_scripts(); ?>
</div>
</body>
</html>
