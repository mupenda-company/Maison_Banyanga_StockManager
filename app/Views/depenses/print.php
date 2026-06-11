<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche depense</title>
    <?= print_report_css(false) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Fiche de depense', 'DEP-' . str_pad($depense['id'], 5, '0', STR_PAD_LEFT)); ?>
    <div class="info-bar">
        <div><strong>Date:</strong> <?= !empty($depense['date_depense']) ? date('d/m/Y', strtotime($depense['date_depense'])) : '' ?></div>
        <div><strong>Categorie:</strong> <?= htmlspecialchars($depense['categorie'] ?? '') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <div class="summary-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="summary-item"><div class="summary-label">Numero</div><div class="summary-value">DEP-<?= str_pad($depense['id'], 5, '0', STR_PAD_LEFT) ?></div></div>
        <div class="summary-item"><div class="summary-label">Montant</div><div class="summary-value warn"><?= format_money_dual($depense['montant'] ?? 0) ?></div></div>
        <div class="summary-item"><div class="summary-label">Enregistre par</div><div class="summary-value"><?= htmlspecialchars(trim(($depense['created_by_prenom'] ?? '') . ' ' . ($depense['created_by_nom'] ?? '')) ?: '-') ?></div></div>
    </div>

    <div class="section-title">Description</div>
    <table>
        <tbody>
            <tr>
                <td style="height: 80px; font-size: 11px;"><?= nl2br(htmlspecialchars($depense['description'] ?? '')) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Validation</div>
    <table>
        <tbody>
            <tr>
                <td style="height: 70px; width: 50%;"><strong>Comptabilite</strong><br><br>Signature:</td>
                <td style="height: 70px; width: 50%;"><strong>Responsable</strong><br><br>Signature:</td>
            </tr>
        </tbody>
    </table>

    <?php print_report_scripts(); ?>
</div>
</body>
</html>
