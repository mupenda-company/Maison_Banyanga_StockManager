<?php
require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php';
$company = print_report_company();
$companyName = $company['name'] ?? APP_NAME;
$partnerName = ($operation['source_type'] ?? 'client') === 'client'
    ? ($operation['client_nom'] ?? 'Client')
    : ($operation['source_nom'] ?? 'Partenaire externe');
$isLoan = ($operation['direction'] ?? 'recu') === 'donne';
$isRepayment = ($mode ?? 'operation') === 'remboursement';

if ($isRepayment) {
    $title = $isLoan ? 'Bon d’entrée — Retour de prêt' : 'Bon de sortie — Remboursement d’emprunt';
    $leftRole = 'Celui qui remet';
    $rightRole = 'Celui qui reçoit';
    $leftName = $isLoan ? $partnerName : $companyName;
    $rightName = $isLoan ? $companyName : $partnerName;
} else {
    $title = $isLoan ? 'Bon de sortie — Prêt' : 'Bon d’entrée — Emprunt';
    $leftRole = 'Le prêteur';
    $rightRole = 'L’emprunteur';
    $leftName = $isLoan ? $companyName : $partnerName;
    $rightName = $isLoan ? $partnerName : $companyName;
}
$authorName = trim(($auteur['prenom'] ?? '') . ' ' . ($auteur['nom'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <?= print_report_css(false) ?>
    <style>
        .document-title { font-size: 15px; text-align: center; font-weight: bold; text-transform: uppercase; margin: 14px 0 10px; }
        .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 35px; margin-top: 32px; }
        .signature-box { min-height: 125px; border: 1px solid #999; padding: 10px; text-align: center; }
        .signature-role { font-weight: bold; text-transform: uppercase; margin-bottom: 6px; }
        .signature-name { font-size: 11px; }
        .signature-space { margin-top: 52px; border-top: 1px solid #777; padding-top: 5px; }
    </style>
</head>
<body>
<div class="page">
    <?php print_report_header($title, $operation['operation_ref_label'] ?? ''); ?>
    <div class="document-title"><?= htmlspecialchars($title) ?></div>

    <div class="info-bar">
        <div><strong>Référence :</strong> <?= htmlspecialchars($operation['operation_ref_label'] ?? '') ?></div>
        <div><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($isRepayment ? ($operation['created_at'] ?? 'now') : ($operation['date_emprunt'] ?? 'now'))) ?></div>
        <div><strong>Partenaire :</strong> <?= htmlspecialchars($partnerName) ?></div>
    </div>
    <div class="info-bar">
        <div><strong>Nature :</strong> <?= ($operation['type_stock'] ?? 'vide') === 'plein' ? 'Produits pleins' : 'Emballages vides' ?></div>
        <div><strong>Emplacement :</strong> <?= htmlspecialchars($operation['emplacement_nom'] ?? '') ?></div>
        <div><strong>Enregistré par :</strong> <?= htmlspecialchars($authorName ?: '—') ?></div>
    </div>

    <table>
        <thead>
            <tr><th>Produit</th><th>Code</th><th class="num">Quantité (caisses)</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $ligne): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></td>
                <td class="num"><?= number_format((float) ($isRepayment ? ($ligne['quantite_caisses'] ?? 0) : ($ligne['quantite_empruntee'] ?? 0)), 2, ',', ' ') ?> cs</td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($lignes) > 1): ?>
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td class="num"><?= number_format(array_sum(array_map(function ($ligne) { return (float) ($ligne['quantite_empruntee'] ?? 0); }, $lignes)), 2, ',', ' ') ?> cs</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature-grid">
        <div class="signature-box">
            <div class="signature-role"><?= htmlspecialchars($leftRole) ?></div>
            <div class="signature-name"><?= htmlspecialchars($leftName) ?></div>
            <div class="signature-space">Nom, signature et date</div>
        </div>
        <div class="signature-box">
            <div class="signature-role"><?= htmlspecialchars($rightRole) ?></div>
            <div class="signature-name"><?= htmlspecialchars($rightName) ?></div>
            <div class="signature-space">Nom, signature et date</div>
        </div>
    </div>

    <?php print_report_scripts(); ?>
</div>
</body>
</html>
