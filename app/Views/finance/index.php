<?php 
$pageTitle = 'Finance';
$queryParams = [
    'date_debut' => $dateDebut,
    'date_fin' => $dateFin,
];
$printUrl = url('finance/print') . '?' . http_build_query($queryParams);
$exportUrl = url('finance/export') . '?' . http_build_query($queryParams);
ob_start();
?>

<!-- Filtre période -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="<?= url('finance') ?>" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="label">Date début</label>
            <input type="date" name="date_debut" value="<?= htmlspecialchars($dateDebut) ?>" class="input">
        </div>
        <div>
            <label class="label">Date fin</label>
            <input type="date" name="date_fin" value="<?= htmlspecialchars($dateFin) ?>" class="input">
        </div>
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Filtrer
        </button>
        <a href="<?= url('finance') ?>" class="btn btn-secondary">Réinitialiser</a>
        <a href="<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimer
        </a>
        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </a>
    </form>
</div>

<!-- Cartes résumé -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <!-- Chiffre d'affaires -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Chiffre d'affaires TTC</p>
                <p class="text-lg md:text-xl font-bold text-green-600 dark:text-green-400 truncate" title="<?= format_money_dual($caTotal) ?>">
                    <?= format_money_dual($caTotal) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= $statsVentes['nb_ventes'] ?? 0 ?> vente(s) · <?= number_format((int) round($statsVentes['caisses_vendues'] ?? 0), 0, '.', ' ') ?> caisse(s)
        </p>
    </div>

    <!-- Solde net -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Solde net</p>
                <p class="text-lg md:text-xl font-bold truncate <?= $benefice >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' ?>" title="<?= format_money_dual($benefice) ?>">
                    <?= format_money_dual($benefice) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            CA - Pertes - Dépenses
        </p>
    </div>

    <!-- Pertes -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Valeur des pertes</p>
                <p class="text-lg md:text-xl font-bold text-red-600 dark:text-red-400 truncate" title="<?= format_money_dual($pertesValeur) ?>">
                    <?= format_money_dual($pertesValeur) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?php
                $totalCaissesPertes = (float)($statsPertes['total_caisses'] ?? 0);
                $btlParCaissePertes = 24;
                $totalBouteillesPertes = round($totalCaissesPertes * $btlParCaissePertes);
                $caissesPleinesPertes = intdiv($totalBouteillesPertes, $btlParCaissePertes);
                $bouteillesRestePertes = $totalBouteillesPertes % $btlParCaissePertes;
                if ($caissesPleinesPertes > 0 && $bouteillesRestePertes > 0) {
                    $pertesQuantite = $caissesPleinesPertes . ' cs + ' . $bouteillesRestePertes . ' btl';
                } elseif ($caissesPleinesPertes > 0) {
                    $pertesQuantite = $caissesPleinesPertes . ' cs';
                } else {
                    $pertesQuantite = $totalBouteillesPertes . ' btl';
                }
            ?>
            <?= $statsPertes['nb_pertes'] ?? 0 ?> perte(s) · <?= $pertesQuantite ?>
        </p>
    </div>

    <!-- Dépenses -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Dépenses</p>
                <p class="text-lg md:text-xl font-bold text-amber-600 dark:text-amber-400 truncate" title="<?= format_money_dual($totalDepenses) ?>">
                    <?= format_money_dual($totalDepenses) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= $statsDepenses['nb_depenses'] ?? 0 ?> dépense(s)
        </p>
    </div>

    <!-- TVA collectée -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">TVA collectée</p>
                <p class="text-lg md:text-xl font-bold text-purple-600 dark:text-purple-400 truncate" title="<?= format_money_dual($tvaCollectee) ?>">
                    <?= format_money_dual($tvaCollectee) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            HT: <?= format_money_dual($statsVentes['total_ht'] ?? 0) ?>
        </p>
    </div>

    <!-- Récolté ce mois (local) -->
    <!-- <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Récolte locale</p>
                <p class="text-lg md:text-xl font-bold text-teal-600 dark:text-teal-400 truncate" title="<?= format_money_dual($totalRecolteLocale) ?>">
                    <?= format_money_dual($totalRecolteLocale) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Deduction locale ristournes
        </p>
    </div> -->
</div>

<!-- Deuxième rangée : Dettes + Panier -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

    <!-- Dettes emballages -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Dettes emballages</p>
                <p class="text-lg md:text-xl font-bold text-red-700 dark:text-red-300 truncate">
                    <?= number_format((int)($dettesAppro['total_dettes'] ?? 0), 0, '.', ' ') ?> caisse(s)
                </p>
            </div>
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-700 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= $dettesAppro['nb_dettes'] ?? 0 ?> dette(s) en cours
        </p>
    </div>

    <!-- Panier moyen -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Panier moyen</p>
                <p class="text-lg md:text-xl font-bold text-teal-600 dark:text-teal-400 truncate">
                    <?= format_money_dual($statsVentes['moyenne_vente'] ?? 0) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Total TTC / Nombre de ventes
        </p>
    </div>
</div>

<!-- Graphique CA par jour -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Évolution du CA par jour</h3>
        <?php if (!empty($ventesParJour)): ?>
        <div class="space-y-2">
            <?php 
            $maxCa = max(array_column($ventesParJour, 'ca_jour')) ?: 1;
            foreach ($ventesParJour as $v): 
                $pct = round(($v['ca_jour'] / $maxCa) * 100, 1);
            ?>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-20 shrink-0"><?= date('d/m', strtotime($v['jour'])) ?></span>
                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
                    <div class="bg-primary-500 h-full rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                </div>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-28 text-right shrink-0"><?= format_money_dual($v['ca_jour']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée pour cette période</p>
        <?php endif; ?>
    </div>

    <!-- Ventes par zone -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ventes par zone</h3>
        <?php if (!empty($ventesParZone)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Zone</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Ventes</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">CA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventesParZone as $z): ?>
                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                        <td class="py-2 px-3 text-gray-900 dark:text-white"><?= htmlspecialchars($z['zone_nom']) ?></td>
                        <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-400"><?= $z['nb_ventes'] ?></td>
                        <td class="py-2 px-3 text-right font-semibold text-gray-900 dark:text-white"><?= format_money_dual($z['total_ca']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée pour cette période</p>
        <?php endif; ?>
    </div>
</div>

<!-- Ventes par produit + Top clients -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Ventes par produit -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ventes par produit</h3>
        <?php if (!empty($ventesParProduit)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Produit</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Caisses</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">CA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventesParProduit as $p): ?>
                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                        <td class="py-2 px-3 text-gray-900 dark:text-white"><?= htmlspecialchars($p['nom']) ?></td>
                        <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-400"><?= number_format((int) round($p['total_caisses'] ?? 0), 0, '.', ' ') ?></td>
                        <td class="py-2 px-3 text-right font-semibold text-gray-900 dark:text-white"><?= format_money_dual($p['total_vente'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée pour cette période</p>
        <?php endif; ?>
    </div>

    <!-- Top clients -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top clients</h3>
        <?php if (!empty($topClients)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">#</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Client</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Ventes</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">CA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topClients as $i => $c): ?>
                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                        <td class="py-2 px-3 text-gray-500 dark:text-gray-400"><?= $i + 1 ?></td>
                        <td class="py-2 px-3 text-gray-900 dark:text-white"><?= htmlspecialchars($c['nom']) ?></td>
                        <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-400"><?= $c['nb_ventes'] ?></td>
                        <td class="py-2 px-3 text-right font-semibold text-gray-900 dark:text-white"><?= format_money_dual($c['total_ca']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée pour cette période</p>
        <?php endif; ?>
    </div>
</div>

<!-- Pertes par type -->
<?php if (!empty($pertesParType)): ?>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pertes par type</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($pertesParType as $pt): ?>
        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <p class="font-semibold text-red-700 dark:text-red-300"><?= htmlspecialchars($pt['type_perte'] ?? 'Autre') ?></p>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?= $pt['nb'] ?? 0 ?> perte(s)</p>
            <p class="text-sm font-bold text-red-600 dark:text-red-400 mt-1"><?= format_money_dual($pt['valeur'] ?? 0) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?= number_format((int)($pt['quantite'] ?? 0), 0, '.', ' ') ?> caisse(s)</p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Dépenses par catégorie -->
<?php if (!empty($depensesParCategorie)): ?>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Dépenses par catégorie</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <?php
        $catColors = ['Transport' => 'blue', 'Carburant' => 'yellow', 'Maintenance' => 'orange', 'Restauration' => 'green', 'Autres' => 'gray'];
        foreach ($depensesParCategorie as $dc):
            $color = $catColors[$dc['categorie']] ?? 'gray';
        ?>
        <div class="p-4 bg-<?= $color ?>-50 dark:bg-<?= $color ?>-900/20 rounded-lg border border-<?= $color ?>-200 dark:border-<?= $color ?>-800">
            <p class="font-semibold text-<?= $color ?>-700 dark:text-<?= $color ?>-300"><?= htmlspecialchars($dc['categorie']) ?></p>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?= $dc['nb'] ?? 0 ?> dépense(s)</p>
            <p class="text-sm font-bold text-<?= $color ?>-600 dark:text-<?= $color ?>-400 mt-1"><?= format_money_dual($dc['total'] ?? 0) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Résumé financier -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Résumé financier</h3>
    <div class="space-y-3">
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-600 dark:text-gray-400">Chiffre d'affaires HT</span>
            <span class="font-semibold text-gray-900 dark:text-white"><?= format_money_dual($statsVentes['total_ht'] ?? 0) ?></span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-600 dark:text-gray-400">TVA collectée</span>
            <span class="font-semibold text-purple-600 dark:text-purple-400">+ <?= format_money_dual($tvaCollectee) ?></span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-600 dark:text-gray-400">Chiffre d'affaires TTC</span>
            <span class="font-semibold text-green-600 dark:text-green-400"><?= format_money_dual($caTotal) ?></span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-600 dark:text-gray-400">Valeur des pertes</span>
            <span class="font-semibold text-red-600 dark:text-red-400">- <?= format_money_dual($pertesValeur) ?></span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
            <span class="text-gray-600 dark:text-gray-400">Dépenses</span>
            <span class="font-semibold text-amber-600 dark:text-amber-400">- <?= format_money_dual($totalDepenses) ?></span>
        </div>
        <div class="flex justify-between items-center py-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg px-4 mt-2">
            <span class="font-bold text-gray-900 dark:text-white">Solde net</span>
            <span class="font-bold text-lg <?= $benefice >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' ?>"><?= format_money_dual($benefice) ?></span>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
