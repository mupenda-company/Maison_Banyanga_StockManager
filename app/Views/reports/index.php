<?php 
$pageTitle = 'Rapports et Statistiques';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rapports et Statistiques</h1>
        <p class="text-gray-500 dark:text-gray-400">Analyse des performances de vente et stocks</p>
    </div>
    
    <form method="GET" class="flex items-center gap-3">
        <input type="date" name="date_debut" value="<?= $dateDebut ?>" class="input py-1 px-2 text-sm">
        <span class="text-gray-400">au</span>
        <input type="date" name="date_fin" value="<?= $dateFin ?>" class="input py-1 px-2 text-sm">
        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
    </form>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <p class="stat-label">Chiffre d'Affaires</p>
        <p class="stat-value text-primary-600"><?= format_money_converted($statsVentes['ca_total'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $statsVentes['nb_ventes'] ?> vente(s) validée(s)</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">TVA Collectée</p>
        <p class="stat-value text-blue-600"><?= format_money_converted($statsVentes['tva_total'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1">Sur les ventes validées</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Ventes Annulées</p>
        <p class="stat-value text-red-600"><?= $statsVentes['nb_annulees'] ?></p>
        <p class="text-xs text-gray-400 mt-1">Nombre d'opérations</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Performance Zone</p>
        <p class="stat-value text-green-600"><?= count($ventesParZone) ?></p>
        <p class="text-xs text-gray-400 mt-1">Zone(s) active(s)</p>
    </div>
</div>

<div class="card mb-8">
    <div class="card-body flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport journalier par agent</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Imprime les ventes de tous les agents pour une journée donnée.</p>
        </div>
        <a href="<?= url('rapports/ventes-par-agent?date_debut=' . $dateDebut . '&date_fin=' . $dateFin) ?>" class="btn btn-primary">
            Ouvrir le rapport imprimable
        </a>
    </div>
</div>

<!-- KPI Emballages -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <p class="stat-label">Retours Emballages</p>
        <p class="stat-value text-primary-600"><?= (int) ($statsEmballages['resume']['nb_retours'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= (int) ($statsEmballages['resume']['nb_clients'] ?? 0) ?> client(s) concernés</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Caisses Retournées</p>
        <p class="stat-value text-blue-600"><?= number_format((float) ($statsEmballages['resume']['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsEmballages['resume']['total_bouteilles'] ?? 0), 0, ',', ' ') ?> bouteilles</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dettes d'Emballages</p>
        <p class="stat-value text-orange-600"><?= (int) ($statsDettes['nb_dettes'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsDettes['caisses_restantes'] ?? 0), 0, ',', ' ') ?> caisses restantes</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dettes Soldées</p>
        <p class="stat-value text-green-600"><?= (int) ($statsDettes['nb_soldees'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsDettes['caisses_remboursees'] ?? 0), 0, ',', ' ') ?> caisses remboursées</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Top Produits -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Top 5 Produits (par CA)</h3>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-right">Caisses</th>
                            <th class="text-right">Chiffre d'Affaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProduits)): ?>
                            <tr><td colspan="3" class="text-center p-4 text-gray-500">Aucune donnée</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProduits as $p): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($p['nom']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($p['code']) ?></div>
                                    </td>
                                    <td class="text-right font-bold"><?= number_format($p['total_caisses'], 1, '.', ' ') ?> cs</td>
                                    <td class="text-right text-primary-600 font-bold"><?= format_money_converted($p['total_ca'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ventes par Zone -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Performance par Zone</h3>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th class="text-center">Ventes</th>
                            <th class="text-right">Chiffre d'Affaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventesParZone)): ?>
                            <tr><td colspan="3" class="text-center p-4 text-gray-500">Aucune donnée</td></tr>
                        <?php else: ?>
                            <?php foreach ($ventesParZone as $z): ?>
                                <tr>
                                    <td class="font-medium"><?= htmlspecialchars($z['zone_nom']) ?></td>
                                    <td class="text-center">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold"><?= $z['nb_ventes'] ?></span>
                                    </td>
                                    <td class="text-right text-green-600 font-bold"><?= format_money_converted($z['total_ca'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
    <!-- Top produits retournés -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Top produits retournés</h3>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4V7m-9 10h12M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Retours</th>
                            <th class="text-right">Caisses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statsEmballages['top_produits'] ?? [])): ?>
                            <tr><td colspan="3" class="text-center p-4 text-gray-500">Aucune donnée</td></tr>
                        <?php else: ?>
                            <?php foreach ($statsEmballages['top_produits'] as $p): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($p['nom']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($p['code']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold"><?= (int) $p['nb_retours'] ?></span>
                                    </td>
                                    <td class="text-right font-bold text-blue-700"><?= number_format((float) $p['total_caisses'], 0, ',', ' ') ?> cs</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Résumé dettes emballages -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Résumé des dettes d'emballages</h3>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V5m0 12v-1m8-5a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
        </div>
        <div class="card-body space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                <span class="text-sm text-gray-500">Caisses dues</span>
                <span class="font-bold text-orange-600"><?= number_format((float) ($statsDettes['caisses_dettes'] ?? 0), 0, ',', ' ') ?> cs</span>
            </div>
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                <span class="text-sm text-gray-500">Caisses remboursées</span>
                <span class="font-bold text-green-600"><?= number_format((float) ($statsDettes['caisses_remboursees'] ?? 0), 0, ',', ' ') ?> cs</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Caisses restantes</span>
                <span class="font-bold text-red-600"><?= number_format((float) ($statsDettes['caisses_restantes'] ?? 0), 0, ',', ' ') ?> cs</span>
            </div>
        </div>
    </div>
</div>

<!-- <div class="mt-8 flex justify-end">
    <button onclick="window.print()" class="btn btn-secondary bg-white">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Imprimer le rapport
    </button>
</div> -->

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
