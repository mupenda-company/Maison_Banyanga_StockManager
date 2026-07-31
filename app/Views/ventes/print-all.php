<?php
$totalCaisses = array_sum(array_column($ventes ?? [], 'total_caisses'));
$totalMontant = array_sum(array_column($ventes ?? [], 'total_ttc'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des ventes</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Poppins', Arial, sans-serif; color: #111827; font-size: 11px; }
        h1 { margin: 0 0 4px; text-align: center; font-size: 20px; }
        .periode { text-align: center; color: #4b5563; margin-bottom: 16px; }
        .resume { display: flex; justify-content: space-between; border: 1px solid #d1d5db; padding: 9px 12px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 7px; }
        th { background: #e5e7eb; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .actions { margin: 18px 0; text-align: center; }
        .actions button { padding: 9px 18px; cursor: pointer; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?> — Rapport des ventes</h1>
    <div class="periode">Du <?= date('d/m/Y', strtotime($dateDebut)) ?> au <?= date('d/m/Y', strtotime($dateFin)) ?></div>

    <div class="resume">
        <strong><?= count($ventes ?? []) ?> vente(s)</strong>
        <strong><?= number_format((float) $totalCaisses, 0, ',', ' ') ?> caisse(s)</strong>
        <strong><?= format_money_converted($totalMontant) ?></strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Facture</th>
                <th>Client</th>
                <th>Emplacement</th>
                <th class="num">Caisses</th>
                <th class="num">Montant TTC</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ventes)): ?>
                <tr><td colspan="6" style="text-align:center">Aucune vente pour cette période.</td></tr>
            <?php else: ?>
                <?php foreach ($ventes as $vente): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></td>
                        <td><?= htmlspecialchars($vente['numero_facture']) ?></td>
                        <td><?= htmlspecialchars($vente['client_nom']) ?></td>
                        <td><?= htmlspecialchars($vente['emplacement_nom'] ?? 'N/A') ?></td>
                        <td class="num"><?= number_format((float) $vente['total_caisses'], 0, ',', ' ') ?></td>
                        <td class="num"><?= format_money_converted($vente['total_ttc']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="num"><?= number_format((float) $totalCaisses, 0, ',', ' ') ?></td>
                <td class="num"><?= format_money_converted($totalMontant) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="actions"><button type="button" onclick="window.print()">Imprimer</button></div>
</body>
</html>
