<?php require_once ROOT_PATH . '/app/Views/layouts/print_helpers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Manquants</title>
    <?= print_report_css(true) ?>
    <style>
        table {
            table-layout: fixed;
            width: 100%;
        }

        th, td {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
            font-size: 10px;
            padding: 4px;
        }

        .num {
            text-align: right;
            font-size: 9px;
            line-height: 1.2;
        }

        @media print {
            th, td {
                font-size: 8.5px !important;
                padding: 3px !important;
            }

            .num {
                font-size: 8px !important;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <?php print_report_header('Manquants agents'); ?>
    <div class="info-bar">
        <div><strong>Du:</strong> <?= !empty($filters['date_debut']) ? date('d/m/Y', strtotime($filters['date_debut'])) : 'Debut' ?></div>
        <div><strong>Au:</strong> <?= !empty($filters['date_fin']) ? date('d/m/Y', strtotime($filters['date_fin'])) : 'Fin' ?></div>
        <div><strong>Statut:</strong> <?= htmlspecialchars($filters['statut'] ?? 'Tous') ?></div>
        <div><strong>Impression:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php
    $totalMontant = 0;
    $totalPaye = 0;
    $totalReste = 0;
    $totalCaisses = 0;
    $totalResteCaisses = 0;
    $totalEmballages = 0;
    $totalResteEmballages = 0;

    foreach ($manquants as $row) {
        $quantiteCaisses = (float) ($row['quantite_caisses'] ?? 0);
        $quantiteCaissesReglee = (float) ($row['quantite_caisses_reglee'] ?? 0);
        $resteCaisses = isset($row['reste_caisses'])
            ? (float) $row['reste_caisses']
            : max(0, $quantiteCaisses - $quantiteCaissesReglee);

        $quantiteEmballages = (float) ($row['quantite_emballages'] ?? 0);
        $quantiteEmballagesReglee = (float) ($row['quantite_emballages_reglee'] ?? 0);
        $resteEmballages = isset($row['reste_emballages'])
            ? (float) $row['reste_emballages']
            : max(0, $quantiteEmballages - $quantiteEmballagesReglee);

        $totalMontant += (float) ($row['montant'] ?? 0);
        $totalPaye += (float) ($row['montant_paye'] ?? 0);
        $totalReste += (float) ($row['reste_montant'] ?? 0);
        $totalCaisses += $quantiteCaisses;
        $totalResteCaisses += $resteCaisses;
        $totalEmballages += $quantiteEmballages;
        $totalResteEmballages += $resteEmballages;
    }
    ?>

    <div class="summary-grid">
        <div class="summary-item"><div class="summary-label">Declarations</div><div class="summary-value"><?= count($manquants) ?></div></div>
        <div class="summary-item"><div class="summary-label">Reste caisses</div><div class="summary-value"><?= number_format((float) $totalResteCaisses, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Reste emballages</div><div class="summary-value"><?= number_format((float) $totalResteEmballages, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Emballages dus</div><div class="summary-value warn"><?= number_format((float) $totalEmballages, 2, ',', ' ') ?> cs</div></div>
        <div class="summary-item"><div class="summary-label">Montant</div><div class="summary-value"><?= format_money_dual($totalMontant) ?></div></div>
        <div class="summary-item"><div class="summary-label">Reste</div><div class="summary-value warn"><?= format_money_dual($totalReste) ?></div></div>
    </div>

    <div class="section-title">Detail des manquants</div>
    <table>
        <thead>
            <tr>
                <th style="width: 8%">Date</th>
                <th style="width: 13%">Agent</th>
                <th style="width: 12%">Produit</th>
                <th style="width: 8%">Caisses dues</th>
                <th style="width: 8%">Reste caisses</th>
                <th style="width: 8%">Emb. dus</th>
                <th style="width: 8%">Reste emb.</th>
                <th style="width: 10%">Montant</th>
                <th style="width: 10%">Paye</th>
                <th style="width: 10%">Reste</th>
                <th style="width: 5%">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($manquants)): ?>
                <tr><td colspan="11" class="center">Aucun manquant trouve</td></tr>
            <?php else: foreach ($manquants as $row): ?>
                <?php
                    $quantiteCaisses = (float) ($row['quantite_caisses'] ?? 0);
                    $quantiteCaissesReglee = (float) ($row['quantite_caisses_reglee'] ?? 0);
                    $resteCaisses = isset($row['reste_caisses'])
                        ? (float) $row['reste_caisses']
                        : max(0, $quantiteCaisses - $quantiteCaissesReglee);

                    $quantiteEmballages = (float) ($row['quantite_emballages'] ?? 0);
                    $quantiteEmballagesReglee = (float) ($row['quantite_emballages_reglee'] ?? 0);
                    $resteEmballages = isset($row['reste_emballages'])
                        ? (float) $row['reste_emballages']
                        : max(0, $quantiteEmballages - $quantiteEmballagesReglee);

                    $resteMontant = (float) ($row['reste_montant'] ?? 0);
                    $statut = $row['statut_effectif'] ?? ($row['statut'] ?? 'ouvert');
                    $statutLabel = $statut === 'paye' ? 'Paye' : ($statut === 'partiel' ? 'Partiel' : 'Ouvert');
                ?>
                <tr>
                    <td><?= !empty($row['date_manquant']) ? date('d/m/Y', strtotime($row['date_manquant'])) : '' ?></td>
                    <td><?= htmlspecialchars($row['agent_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['produit_nom'] ?: '-') ?></td>
                    <td class="num"><?= number_format($quantiteCaisses, 2, ',', ' ') ?></td>
                    <td class="num <?= $resteCaisses > 0 ? 'warn' : 'ok' ?>"><?= number_format($resteCaisses, 2, ',', ' ') ?></td>
                    <td class="num"><?= number_format($quantiteEmballages, 2, ',', ' ') ?></td>
                    <td class="num <?= $resteEmballages > 0 ? 'warn' : 'ok' ?>"><?= number_format($resteEmballages, 2, ',', ' ') ?></td>
                    <td class="num"><?= format_money_dual($row['montant'] ?? 0) ?></td>
                    <td class="num"><?= format_money_dual($row['montant_paye'] ?? 0) ?></td>
                    <td class="num <?= $resteMontant > 0 ? 'warn' : 'ok' ?>"><?= format_money_dual($resteMontant) ?></td>
                    <td class="center"><?= htmlspecialchars($statutLabel) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="num"><?= number_format((float) $totalCaisses, 2, ',', ' ') ?></td>
                <td class="num"><?= number_format((float) $totalResteCaisses, 2, ',', ' ') ?></td>
                <td class="num"><?= number_format((float) $totalEmballages, 2, ',', ' ') ?></td>
                <td class="num"><?= number_format((float) $totalResteEmballages, 2, ',', ' ') ?></td>
                <td class="num"><?= format_money_dual($totalMontant) ?></td>
                <td class="num"><?= format_money_dual($totalPaye) ?></td>
                <td class="num"><?= format_money_dual($totalReste) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php print_report_scripts(); ?>
</div>
</body>
</html>
