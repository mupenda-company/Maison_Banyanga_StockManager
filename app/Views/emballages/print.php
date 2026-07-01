<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord emballages</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1 { margin: 0; font-size: 22px; }
        .muted { color: #6b7280; font-size: 12px; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 18px 0; }
        .box { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; }
        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; }
        .value { font-weight: 700; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 12px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; text-align: left; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
        @media print { button { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Imprimer</button>
    <h1>Tableau de bord emballages</h1>
    <p class="muted">Periode: <?= htmlspecialchars($dateDebut ?? '') ?> au <?= htmlspecialchars($dateFin ?? '') ?> - <?= date('d/m/Y H:i') ?></p>

    <div class="summary">
        <div class="box"><div class="label">Disponibles</div><div class="value"><?= number_format((int) ($stockEmballages['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</div></div>
        <div class="box"><div class="label">Entrepot</div><div class="value"><?= number_format((int) ($stockEmballages['fixe_caisses'] ?? 0), 0, ',', ' ') ?> cs</div></div>
        <div class="box"><div class="label">Vehicules</div><div class="value"><?= number_format((int) ($stockEmballages['mobile_caisses'] ?? 0), 0, ',', ' ') ?> cs</div></div>
        <div class="box"><div class="label">Operations ouvertes</div><div class="value"><?= number_format((int) ($resumeEmprunts['nb_en_cours'] ?? 0), 0, ',', ' ') ?></div></div>
    </div>

    <h2>Stock par produit</h2>
    <table>
        <thead><tr><th>Produit</th><th class="right">Total</th><th class="right">Entrepot</th><th class="right">Vehicules</th></tr></thead>
        <tbody>
            <?php foreach (($stockEmballages['par_produit'] ?? []) as $ligne): ?>
            <tr>
                <td><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></td>
                <td class="right"><?= number_format((int) ($ligne['total_caisses'] ?? 0), 0, ',', ' ') ?></td>
                <td class="right"><?= number_format((int) ($ligne['fixe_caisses'] ?? 0), 0, ',', ' ') ?></td>
                <td class="right"><?= number_format((int) ($ligne['mobile_caisses'] ?? 0), 0, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Dettes clients</h2>
    <table>
        <thead><tr><th>Client</th><th>Produit</th><th class="right">Dette</th></tr></thead>
        <tbody>
            <?php foreach (($clientsEmballage['lignes'] ?? []) as $ligne): ?>
            <tr>
                <td><?= htmlspecialchars($ligne['client_nom'] ?? '') ?></td>
                <td><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></td>
                <td class="right"><?= number_format((int) ($ligne['dette_caisses'] ?? 0), 0, ',', ' ') ?> cs</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
