<?php
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'actifs' => 0, 'non_actifs' => 0];
$categorie = $filters['activite'] ?? 'tous';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color: #6b7280; font-size: 12px; }
        .summary { display: flex; gap: 12px; margin: 18px 0; }
        .box { border: 1px solid #d1d5db; padding: 10px 12px; border-radius: 6px; }
        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; }
        .value { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; text-align: left; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
        @media print { button { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>Liste des clients</h1>
    <p class="muted">Categorie: <?= htmlspecialchars($categorie) ?> - Genere le <?= date('d/m/Y H:i') ?></p>

    <div class="summary">
        <div class="box"><div class="label">Total</div><div class="value"><?= (int) $stats['total'] ?></div></div>
        <div class="box"><div class="label">Actifs</div><div class="value"><?= (int) $stats['actifs'] ?></div></div>
        <div class="box"><div class="label">Non actifs</div><div class="value"><?= (int) $stats['non_actifs'] ?></div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Numero</th>
                <th>Telephone</th>
                <th>Zone</th>
                <th class="right">Ventes</th>
                <th class="right">CA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><?= htmlspecialchars($client['nom'] ?? '') ?></td>
                <td><?= htmlspecialchars($client['numero_client'] ?? '-') ?></td>
                <td><?= htmlspecialchars($client['telephone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($client['zone_nom'] ?? '-') ?></td>
                <td class="right"><?= number_format((int) ($client['nb_ventes_validees'] ?? 0), 0, ',', ' ') ?></td>
                <td class="right"><?= format_money_converted($client['ca_total'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
