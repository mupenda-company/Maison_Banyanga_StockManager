<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mouvements de stock</title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Historique des mouvements de stock'); ?>
    <div class="info-bar">
        <div><strong>Du:</strong> <?= !empty($filters['date_debut']) ? date('d/m/Y', strtotime($filters['date_debut'])) : 'Debut' ?></div>
        <div><strong>Au:</strong> <?= !empty($filters['date_fin']) ? date('d/m/Y', strtotime($filters['date_fin'])) : 'Fin' ?></div>
        <div><strong>Type:</strong> <?= htmlspecialchars($filters['type'] ?? 'Tous') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $entrees = 0;
    $sorties = 0;
    $transferts = 0;
    foreach ($mouvements as $mvt) {
        if (($mvt['type_mouvement'] ?? '') === 'entree') $entrees++;
        elseif (($mvt['type_mouvement'] ?? '') === 'sortie') $sorties++;
        elseif (($mvt['type_mouvement'] ?? '') === 'transfert') $transferts++;
    }
    ?>
    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Mouvements</div><div class="summary-value"><?= count($mouvements) ?></div></div>
        <div class="summary-item"><div class="summary-label">Entrees</div><div class="summary-value ok"><?= $entrees ?></div></div>
        <div class="summary-item"><div class="summary-label">Sorties</div><div class="summary-value warn"><?= $sorties ?></div></div>
        <div class="summary-item"><div class="summary-label">Transferts</div><div class="summary-value"><?= $transferts ?></div></div>
    </div>

    <div class="section-title">Detail des mouvements</div>
    <table>
        <thead>
            <tr>
                <th style="width: 12%">Date</th>
                <th style="width: 16%">Type</th>
                <th style="width: 22%">Produit</th>
                <th style="width: 24%">Emplacement</th>
                <th style="width: 10%">Quantite</th>
                <th style="width: 10%">Par</th>
                <th style="width: 16%">Motif</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mouvements)): ?>
                <tr><td colspan="7" class="center">Aucun mouvement trouve</td></tr>
            <?php else: foreach ($mouvements as $mvt):
                $bouteillesParCaisse = (int) ($mvt['bouteilles_par_caisses'] ?? 24);
                $caisses = isset($mvt['quantite_caisses_reference']) && $mvt['quantite_caisses_reference'] !== null
                    ? (float) $mvt['quantite_caisses_reference']
                    : ((float) ($mvt['quantite'] ?? 0) / max(1, $bouteillesParCaisse));
                $emplacement = $mvt['emplacement_source'] ?? ($mvt['emplacement_nom'] ?? '-');
                if (($mvt['type_mouvement'] ?? '') === 'transfert' && !empty($mvt['emplacement_dest'])) {
                    $emplacement .= ' -> ' . $mvt['emplacement_dest'];
                }
            ?>
                <tr>
                    <td><?= !empty($mvt['created_at']) ? date('d/m/Y H:i', strtotime($mvt['created_at'])) : '' ?></td>
                    <td><?= htmlspecialchars(ucfirst($mvt['type_mouvement'] ?? '')) ?></td>
                    <td><strong><?= htmlspecialchars($mvt['produit_nom'] ?? '') ?></strong><br><span class="muted"><?= htmlspecialchars($mvt['produit_code'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($emplacement) ?></td>
                    <td class="num"><?= number_format(abs($caisses), 2, ',', ' ') ?> cs</td>
                    <td><?= htmlspecialchars($mvt['user_nom'] ?? 'Systeme') ?></td>
                    <td><?= htmlspecialchars($mvt['motif'] ?? '-') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
