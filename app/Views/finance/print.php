<?php
$nomEntreprise = (new Parametre())->get('nom_entreprise', APP_NAME);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail finance</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        @page { margin: 12mm; }
        body { background: #fff; color: #111827; font-size: 12px; }
        h1 { font-size: 22px; font-weight: 800; margin: 0; }
        h2 { font-size: 16px; font-weight: 700; margin: 0 0 8px; }
        .print-container { max-width: 1100px; margin: 0 auto; padding: 16px; }
        .section { margin-top: 18px; break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-weight: 700; }
        .text-right { text-align: right; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .summary-card { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; }
        .summary-label { color: #4b5563; font-size: 11px; }
        .summary-value { font-size: 15px; font-weight: 700; margin-top: 3px; }
        .no-print { margin-bottom: 14px; text-align: center; }
        @media print {
            .no-print { display: none !important; }
            .print-container { padding: 0; max-width: none; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
            <a href="<?= htmlspecialchars(url('finance') . '?' . http_build_query(['date_debut' => $dateDebut, 'date_fin' => $dateFin]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Retour</a>
        </div>

        <header style="border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 16px;">
            <h1><?= htmlspecialchars($nomEntreprise) ?></h1>
            <p style="margin: 4px 0 0; color: #4b5563;">Detail finance du <?= date('d/m/Y', strtotime($dateDebut)) ?> au <?= date('d/m/Y', strtotime($dateFin)) ?></p>
            <p style="margin: 2px 0 0; color: #6b7280;">Imprime le <?= date('d/m/Y H:i') ?></p>
        </header>

        <section class="summary-grid">
            <div class="summary-card"><div class="summary-label">Chiffre d'affaires TTC</div><div class="summary-value"><?= format_money_converted($caTotal) ?></div></div>
            <div class="summary-card"><div class="summary-label">Solde net</div><div class="summary-value"><?= format_money_converted($benefice) ?></div></div>
            <div class="summary-card"><div class="summary-label">TVA collectee</div><div class="summary-value"><?= format_money_converted($tvaCollectee) ?></div></div>
            <div class="summary-card"><div class="summary-label">Pertes</div><div class="summary-value"><?= format_money_converted($pertesValeur) ?></div></div>
            <div class="summary-card"><div class="summary-label">Depenses</div><div class="summary-value"><?= format_money_converted($totalDepenses) ?></div></div>
            <div class="summary-card"><div class="summary-label">Recolte locale</div><div class="summary-value"><?= format_money_converted($totalRecolteLocale) ?></div></div>
        </section>

        <section class="section">
            <h2>Resume financier</h2>
            <table>
                <tbody>
                    <tr><td>Chiffre d'affaires HT</td><td class="text-right"><?= format_money_converted($statsVentes['total_ht'] ?? 0) ?></td></tr>
                    <tr><td>TVA collectee</td><td class="text-right"><?= format_money_converted($tvaCollectee) ?></td></tr>
                    <tr><td>Chiffre d'affaires TTC</td><td class="text-right"><?= format_money_converted($caTotal) ?></td></tr>
                    <tr><td>Valeur des pertes</td><td class="text-right"><?= format_money_converted($pertesValeur) ?></td></tr>
                    <tr><td>Depenses</td><td class="text-right"><?= format_money_converted($totalDepenses) ?></td></tr>
                    <tr><td><strong>Solde net</strong></td><td class="text-right"><strong><?= format_money_converted($benefice) ?></strong></td></tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Ventes par zone</h2>
            <table>
                <thead><tr><th>Zone</th><th class="text-right">Ventes</th><th class="text-right">CA</th></tr></thead>
                <tbody>
                    <?php foreach ($ventesParZone as $z): ?>
                    <tr><td><?= htmlspecialchars($z['zone_nom']) ?></td><td class="text-right"><?= $z['nb_ventes'] ?></td><td class="text-right"><?= format_money_converted($z['total_ca']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($ventesParZone)): ?><tr><td colspan="3">Aucune donnee</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Ventes par produit</h2>
            <table>
                <thead><tr><th>Produit</th><th class="text-right">Caisses</th><th class="text-right">CA</th></tr></thead>
                <tbody>
                    <?php foreach ($ventesParProduit as $p): ?>
                    <tr><td><?= htmlspecialchars($p['nom']) ?></td><td class="text-right"><?= number_format((int) round($p['total_caisses'] ?? 0), 0, '.', ' ') ?></td><td class="text-right"><?= format_money_converted($p['total_vente'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($ventesParProduit)): ?><tr><td colspan="3">Aucune donnee</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Top clients</h2>
            <table>
                <thead><tr><th>#</th><th>Client</th><th class="text-right">Ventes</th><th class="text-right">CA</th></tr></thead>
                <tbody>
                    <?php foreach ($topClients as $i => $c): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= htmlspecialchars($c['nom']) ?></td><td class="text-right"><?= $c['nb_ventes'] ?></td><td class="text-right"><?= format_money_converted($c['total_ca']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($topClients)): ?><tr><td colspan="4">Aucune donnee</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Pertes par type</h2>
            <table>
                <thead><tr><th>Type</th><th class="text-right">Nombre</th><th class="text-right">Valeur</th><th class="text-right">Caisses</th></tr></thead>
                <tbody>
                    <?php foreach ($pertesParType as $pt): ?>
                    <tr><td><?= htmlspecialchars($pt['type_perte'] ?? 'Autre') ?></td><td class="text-right"><?= $pt['nb'] ?? 0 ?></td><td class="text-right"><?= format_money_converted($pt['valeur'] ?? 0) ?></td><td class="text-right"><?= number_format((int)($pt['quantite'] ?? 0), 0, '.', ' ') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($pertesParType)): ?><tr><td colspan="4">Aucune donnee</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Depenses par categorie</h2>
            <table>
                <thead><tr><th>Categorie</th><th class="text-right">Nombre</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($depensesParCategorie as $dc): ?>
                    <tr><td><?= htmlspecialchars($dc['categorie']) ?></td><td class="text-right"><?= $dc['nb'] ?? 0 ?></td><td class="text-right"><?= format_money_converted($dc['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($depensesParCategorie)): ?><tr><td colspan="3">Aucune donnee</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
