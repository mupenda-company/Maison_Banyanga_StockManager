<?php
require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php';
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'actifs' => 0, 'non_actifs' => 0];
$categorie = $filters['activite'] ?? 'tous';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des clients</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Liste des clients', 'Categorie: ' . $categorie); ?>
    <div class="info-bar">
        <div><strong>Recherche:</strong> <?= htmlspecialchars($filters['q'] ?? 'Tous') ?></div>
        <div><strong>Zone:</strong> <?= htmlspecialchars($filters['zone_id'] ?? 'Toutes') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <div class="summary-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="summary-item"><div class="summary-label">Total clients</div><div class="summary-value"><?= (int) $stats['total'] ?></div></div>
        <div class="summary-item"><div class="summary-label">Clients actifs</div><div class="summary-value ok"><?= (int) $stats['actifs'] ?></div></div>
        <div class="summary-item"><div class="summary-label">Clients non actifs</div><div class="summary-value warn"><?= (int) $stats['non_actifs'] ?></div></div>
    </div>

    <div class="section-title">Clients</div>
    <table>
        <thead>
            <tr>
                <th style="width: 24%;">Nom</th>
                <th style="width: 12%;">Numero</th>
                <th style="width: 13%;">Telephone</th>
                <th style="width: 18%;">Zone</th>
                <th class="num" style="width: 10%;">Ventes</th>
                <th class="num" style="width: 13%;">CA</th>
                <th style="width: 10%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr><td colspan="7" class="center muted">Aucun client trouve</td></tr>
            <?php endif; ?>
            <?php foreach ($clients as $client): ?>
            <?php $isActive = (int) ($client['nb_ventes_validees'] ?? 0) > 0; ?>
            <tr>
                <td><strong><?= htmlspecialchars($client['nom'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($client['numero_client'] ?? '-') ?></td>
                <td><?= htmlspecialchars($client['telephone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($client['zone_nom'] ?? '-') ?></td>
                <td class="num"><?= number_format((int) ($client['nb_ventes_validees'] ?? 0), 0, ',', ' ') ?></td>
                <td class="num"><?= format_money_converted($client['ca_total'] ?? 0) ?></td>
                <td class="center <?= $isActive ? 'ok' : 'warn' ?>"><?= $isActive ? 'Actif' : 'Non actif' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php print_report_scripts(); ?>
</div>
</body>
</html>
