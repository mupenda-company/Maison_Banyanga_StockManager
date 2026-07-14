<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste détaillée des emprunts et prêts</title>
    <?= print_report_css(true) ?>
    <style>
        .operation-block { margin-top: 12px; }
        .operation-block .info-bar { margin-bottom: 6px; flex-wrap: wrap; }
        .operation-block .info-bar div { min-width: 16%; }
    </style>
</head>
<body>
<div class="page">
    <?php
    $periode = '';
    if (!empty($filters['date_debut']) || !empty($filters['date_fin'])) {
        $periode = 'Du ' . ($filters['date_debut'] ?: 'début') . ' au ' . ($filters['date_fin'] ?: 'jour');
    }
    print_report_header('Liste détaillée des emprunts et prêts', $periode);

    $totalQuantite = array_sum(array_column($emprunts, 'quantite_empruntee'));
    $totalReste = array_sum(array_column($emprunts, 'reste_caisses'));
    ?>

    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Opérations</div><div class="summary-value"><?= count($emprunts) ?></div></div>
        <div class="summary-item"><div class="summary-label">Quantité totale</div><div class="summary-value"><?= number_format($totalQuantite, 0, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Reste total</div><div class="summary-value <?= $totalReste <= 0 ? 'ok' : 'warn' ?>"><?= number_format($totalReste, 0, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Impression</div><div class="summary-value"><?= date('d/m/Y H:i') ?></div></div>
    </div>

    <?php if (empty($emprunts)): ?>
    <div class="section-title center">Aucune opération trouvée</div>
    <?php endif; ?>

    <?php foreach ($emprunts as $emprunt): ?>
    <?php
    $lignes = $emprunt['lignes'] ?? [];
    $partenaire = ($emprunt['source_type'] ?? 'client') === 'client'
        ? ($emprunt['client_nom'] ?? 'Client')
        : ($emprunt['source_nom'] ?? 'Externe');
    $estPret = ($emprunt['direction'] ?? 'recu') === 'donne';
    $libelleQuantite = $estPret ? 'Prêté' : 'Emprunté';
    ?>
    <div class="operation-block">
        <div class="section-title">
            Opération <?= htmlspecialchars($emprunt['operation_ref_label'] ?? '') ?>
            — <?= !empty($emprunt['date_emprunt']) ? date('d/m/Y', strtotime($emprunt['date_emprunt'])) : '' ?>
        </div>
        <div class="info-bar">
            <div><strong>Sens :</strong> <?= $estPret ? 'Prêter' : 'Emprunter' ?></div>
            <div><strong>Partenaire :</strong> <?= htmlspecialchars($partenaire) ?></div>
            <div><strong>Type :</strong> <?= ($emprunt['type_stock'] ?? 'vide') === 'plein' ? 'Produits pleins' : 'Emballages vides' ?></div>
            <div><strong>Emplacement :</strong> <?= htmlspecialchars($emprunt['emplacement_nom'] ?? '') ?></div>
            <div><strong>Statut :</strong> <?= ($emprunt['statut'] ?? 'en_cours') === 'solde' ? 'Soldé' : 'En cours' ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Code</th>
                    <th class="num"><?= $libelleQuantite ?> (cs)</th>
                    <th class="num">Utilisé (cs)</th>
                    <th class="num">Retourné (cs)</th>
                    <th class="num">Reste (cs)</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></td>
                    <td class="num"><?= number_format((int) ($ligne['quantite_empruntee'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format((int) ($ligne['quantite_utilisee'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format((int) ($ligne['quantite_retournee'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num <?= (int) ($ligne['reste_caisses'] ?? 0) <= 0 ? 'ok' : 'warn' ?>"><?= number_format((int) ($ligne['reste_caisses'] ?? 0), 0, ',', ' ') ?></td>
                    <td><?= ($ligne['statut'] ?? 'en_cours') === 'solde' ? 'Soldé' : 'En cours' ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if (count($lignes) > 1): ?>
                <tr class="total-row">
                    <td colspan="2">TOTAL DE L’OPÉRATION</td>
                    <td class="num"><?= number_format((int) ($emprunt['quantite_empruntee'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format((int) ($emprunt['quantite_utilisee'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num"><?= number_format((int) ($emprunt['quantite_retournee'] ?? 0), 0, ',', ' ') ?></td>
                    <td class="num <?= (int) ($emprunt['reste_caisses'] ?? 0) <= 0 ? 'ok' : 'warn' ?>"><?= number_format((int) ($emprunt['reste_caisses'] ?? 0), 0, ',', ' ') ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <?php print_report_scripts(); ?>
</div>
</body>
</html>
