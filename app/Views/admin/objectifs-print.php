<?php
require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php';

$periodeLabel = $periodeLabel ?? date('m/Y');
$typeLabel = $typeLabel ?? 'Vente';
$rows = $rows ?? [];
$summary = $summary ?? [];
$realiseLabel = ($typeObjectif ?? 'vente') === 'approvisionnement' ? 'Approvisionne' : 'Vendu';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Objectifs <?= htmlspecialchars($typeLabel) ?> - <?= htmlspecialchars($periodeLabel) ?></title>
    <?= print_report_css(true) ?>
</head>
<body>
<div class="page">
    <?php print_report_header('Objectifs mensuels par produit', $typeLabel . ' - ' . $periodeLabel); ?>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-label">Objectif total</div>
            <div class="summary-value"><?= number_format((int) ($summary['objectif_total'] ?? 0), 0, ',', ' ') ?> cs</div>
        </div>
        <div class="summary-item">
            <div class="summary-label"><?= htmlspecialchars($realiseLabel) ?> total</div>
            <div class="summary-value"><?= number_format((int) ($summary['realise_total'] ?? 0), 0, ',', ' ') ?> cs</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Reste total</div>
            <div class="summary-value"><?= number_format((int) ($summary['reste_total'] ?? 0), 0, ',', ' ') ?> cs</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Progression globale</div>
            <div class="summary-value"><?= number_format((float) ($summary['progression'] ?? 0), 1, ',', ' ') ?> %</div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 12%">Code</th>
            <th style="width: 28%">Produit</th>
            <th class="num">Objectif (cs)</th>
            <th class="num"><?= htmlspecialchars($realiseLabel) ?> (cs)</th>
            <th class="num">Reste (cs)</th>
            <th class="num">Surplus (cs)</th>
            <th class="num">Progression</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row):
            $objectif = (int) ($row['objectif_caisses'] ?? 0);
            $realise = (int) ($row['realise_caisses'] ?? 0);
            $reste = (int) ($row['reste_caisses'] ?? max(0, $objectif - $realise));
            $surplus = $objectif > 0 ? max(0, $realise - $objectif) : 0;
            $progression = $objectif > 0 ? min(100, ($realise / $objectif) * 100) : 0;
        ?>
            <tr>
                <td><?= htmlspecialchars($row['code'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['nom'] ?? '') ?></td>
                <td class="num"><?= number_format($objectif, 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($realise, 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($reste, 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($surplus, 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($progression, 1, ',', ' ') ?> %</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="2">TOTAL</td>
            <td class="num"><?= number_format((int) ($summary['objectif_total'] ?? 0), 0, ',', ' ') ?></td>
            <td class="num"><?= number_format((int) ($summary['realise_total'] ?? 0), 0, ',', ' ') ?></td>
            <td class="num"><?= number_format((int) ($summary['reste_total'] ?? 0), 0, ',', ' ') ?></td>
            <td></td>
            <td class="num"><?= number_format((float) ($summary['progression'] ?? 0), 1, ',', ' ') ?> %</td>
        </tr>
        </tfoot>
    </table>

    <?php print_report_scripts(); ?>
</div>
</body>
</html>
