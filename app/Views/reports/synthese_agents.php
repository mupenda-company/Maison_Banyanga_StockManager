<?php
$dateDebut = $dateDebut ?? date('Y-m-d');
$dateFin = $dateFin ?? $dateDebut;
$agentId = !empty($agentId) ? (int) $agentId : null;
$exportUrl = url('rapports/synthese-agents/export') . '?' . http_build_query([
    'date_debut' => $dateDebut,
    'date_fin' => $dateFin,
    'agent_id' => $agentId,
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Synthèse globale des agents</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');
        body { margin: 0; background: #f3f4f6; color: #111827; font: 13px 'Poppins', Arial, sans-serif; }
        .sheet { max-width: 1100px; margin: 20px auto; padding: 24px; background: #fff; }
        h1 { margin: 0 0 5px; }
        .period { color: #6b7280; margin-bottom: 20px; }
        .actions { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        form { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; }
        label { display: block; font-size: 11px; color: #6b7280; margin-bottom: 3px; text-transform: uppercase; }
        input, select, button, a { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; color: #111827; text-decoration: none; }
        button { cursor: pointer; background: #2563eb; color: #fff; border-color: #2563eb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 10px; }
        th { background: #111827; color: #fff; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        tfoot td { background: #e5e7eb; font-weight: bold; }
        @media print {
            body { background: #fff; }
            .sheet { max-width: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<main class="sheet">
    <h1>Synthèse globale des agents</h1>
    <div class="period">Du <?= date('d/m/Y', strtotime($dateDebut)) ?> au <?= date('d/m/Y', strtotime($dateFin)) ?></div>

    <div class="actions no-print">
        <form method="GET" action="<?= url('rapports/synthese-agents') ?>">
            <div><label>Du</label><input type="date" name="date_debut" value="<?= htmlspecialchars($dateDebut) ?>"></div>
            <div><label>Au</label><input type="date" name="date_fin" value="<?= htmlspecialchars($dateFin) ?>"></div>
            <div>
                <label>Agent</label>
                <select name="agent_id">
                    <option value="">Tous les agents</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= (int) $agent['id'] ?>" <?= $agentId === (int) $agent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim($agent['nom_complet'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Filtrer</button>
        </form>
        <div>
            <a href="<?= url('rapports') ?>">Retour</a>
            <?php if (can('rapports.exporter')): ?><a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>">Exporter Excel</a><?php endif; ?>
            <?php if (can('rapports.imprimer')): ?><button type="button" onclick="window.print()">Imprimer</button><?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Agent</th>
                <th class="num">Sorti</th>
                <th class="num">Retourné</th>
                <th class="num">Vendu</th>
                <th class="num">Montant des ventes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lignes)): ?>
                <tr><td colspan="5" style="text-align:center">Aucune activité sur cette période.</td></tr>
            <?php else: ?>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ligne['agent_nom']) ?></strong></td>
                        <td class="num"><?= number_format($ligne['sorti'], 0, ',', ' ') ?> cs</td>
                        <td class="num"><?= number_format($ligne['retourne'], 0, ',', ' ') ?> cs</td>
                        <td class="num"><?= number_format($ligne['vendu'], 0, ',', ' ') ?> cs</td>
                        <td class="num"><strong><?= format_money_converted($ligne['montant']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="num"><?= number_format($totaux['sorti'], 0, ',', ' ') ?> cs</td>
                <td class="num"><?= number_format($totaux['retourne'], 0, ',', ' ') ?> cs</td>
                <td class="num"><?= number_format($totaux['vendu'], 0, ',', ' ') ?> cs</td>
                <td class="num"><?= format_money_converted($totaux['montant']) ?></td>
            </tr>
        </tfoot>
    </table>
</main>
</body>
</html>
