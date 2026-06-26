<?php 
$pageTitle = 'Inventaire complet du stock';
$printMode = isset($print_mode) ? (bool) $print_mode : false;
$customStyle = $printMode ? "@media print {
    @page { margin: 10mm; }
    html, body { height: auto !important; }
    .h-screen { height: auto !important; }
    .overflow-hidden, .overflow-y-auto { overflow: visible !important; }
    .table-container { overflow: visible !important; }
    table { table-layout: fixed !important; width: 100% !important; }
    thead { display: table-header-group !important; }
    tfoot { display: table-footer-group !important; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    .table th, .table td { white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
    th, td { padding: 4px 6px !important; font-size: 10pt !important; }
}" : null;
$baseQuery = [];
if (!empty($filters['produit_id'])) {
    $baseQuery['produit_id'] = $filters['produit_id'];
}
if (!empty($filters['emplacement_id'])) {
    $baseQuery['emplacement_id'] = $filters['emplacement_id'];
}
if (!empty($filters['categorie'])) {
    $baseQuery['categorie'] = $filters['categorie'];
}
if (!empty($filters['date_stock'])) { $baseQuery['date_stock'] = $filters['date_stock']; }
$printUrl = '?' . http_build_query(array_merge($baseQuery, ['print' => 1]));
$exportUrl = '?' . http_build_query(array_merge($baseQuery, ['export' => 'excel']));
ob_start();
?>

<?php if ($printMode): ?>
    <?php $nomEntreprise = (new Parametre())->get('nom_entreprise', APP_NAME); ?>
    <div class="print-header print-only">
        <h1><?= htmlspecialchars($nomEntreprise) ?></h1>
        <p><?= htmlspecialchars($pageTitle) ?> — <?= date('d/m/Y H:i') ?></p>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 no-print">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $pageTitle ?></h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Consultez l'état global de vos stocks sur tous les emplacements.</p>
    </div>
    <div class="flex items-center space-x-2 no-print">
        <button type="button" onclick="(function(){var url='<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>';var w=window.open(url,'_blank');if(!w){window.location.href=url;}})()" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimer
        </button>
        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exporter
        </a>
    </div>
</div>

<div class="mb-6">
    <a href="<?= url('stocks') ?>" class="flex items-center text-primary-600 hover:text-primary-700 font-medium no-print">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7"/>
        </svg>
        Retour aux stocks
    </a>
</div>

<!-- Filtres -->
<div class="card mb-6 no-print">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
            <div>
                <label class="label">Produit</label>
                <select name="produit_id" class="input w-full">
                    <option value="">Tous les produits</option>
                    <?php foreach ($produits as $produit): ?>
                    <option value="<?= (int) $produit['id'] ?>" <?= ($filters['produit_id'] ?? '') == $produit['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($produit['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="label">Emplacement</label>
                <select name="emplacement_id" class="input w-full">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($emplacements as $emp): ?>
                    <option value="<?= (int) $emp['id'] ?>" <?= ($filters['emplacement_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nom']) ?> (<?= ucfirst($emp['type']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="label">Catégorie</label>
                <select name="categorie" class="input w-full">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): 
                        $catName = is_array($cat) ? $cat['categorie'] : $cat;
                    ?>
                    <option value="<?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['categorie'] ?? '') == $catName ? 'selected' : '' ?>>
                        <?= htmlspecialchars($catName) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="label">Stock à la date</label>
                <input type="date" name="date_stock" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($filters['date_stock'] ?? '') ?>" class="input w-full">
            </div>

            <div class="flex flex-col sm:flex-row gap-2 xl:justify-end">
                <button type="submit" class="btn btn-primary w-full sm:w-auto">Filtrer</button>
                <a href="<?= url('stocks/inventaire') ?>" class="btn btn-secondary w-full sm:w-auto">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<!-- Résumé -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6 no-print">
    <div class="stat-card">
        <p class="stat-label text-xs uppercase text-gray-500">Total produits</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white"><?= $totaux['nb_produits'] ?></p>
    </div>
    <div class="stat-card border-l-4 border-green-500">
        <p class="stat-label text-xs uppercase text-green-600">Plein système</p>
        <p class="text-xl font-bold text-green-600"><?= number_format($totaux['caisses_pleine'], 0, ',', ' ') ?> cs</p>
    </div>
    <div class="stat-card border-l-4 border-blue-500">
        <p class="stat-label text-xs uppercase text-blue-600">Plein physique</p>
        <p class="text-xl font-bold text-blue-600"><?= number_format($totaux['caisses_pleine_physique'] ?? $totaux['caisses_pleine'], 0, ',', ' ') ?> cs</p>
    </div>
    <div class="stat-card border-l-4 <?= ((int)($totaux['ecart_caisses_pleine'] ?? 0) === 0 && (int)($totaux['ecart_caisses_vide'] ?? 0) === 0) ? 'border-green-500' : 'border-red-500' ?>">
        <p class="stat-label text-xs uppercase <?= ((int)($totaux['ecart_caisses_pleine'] ?? 0) === 0 && (int)($totaux['ecart_caisses_vide'] ?? 0) === 0) ? 'text-green-600' : 'text-red-600' ?>">Écart global</p>
        <p class="text-xl font-bold <?= ((int)($totaux['ecart_caisses_pleine'] ?? 0) === 0 && (int)($totaux['ecart_caisses_vide'] ?? 0) === 0) ? 'text-green-600' : 'text-red-600' ?>">
            P: <?= number_format($totaux['ecart_caisses_pleine'] ?? 0, 0, ',', ' ') ?> / V: <?= number_format($totaux['ecart_caisses_vide'] ?? 0, 0, ',', ' ') ?>
        </p>
    </div>
    <div class="stat-card border-l-4 border-gray-400">
        <p class="stat-label text-xs uppercase text-gray-600 dark:text-gray-400">Vides système/physique</p>
        <p class="text-xl font-bold text-gray-600 dark:text-gray-400">
            <?= number_format($totaux['caisses_vide'], 0, ',', ' ') ?> / <?= number_format($totaux['caisses_vide_physique'] ?? $totaux['caisses_vide'], 0, ',', ' ') ?> cs
        </p>
    </div>
    <div class="stat-card border-l-4 border-primary-500">
        <p class="stat-label text-xs uppercase text-primary-600">Valeur estimée</p>
        <p class="text-xl font-bold text-primary-600">
            <?= format_money_converted($totaux['valeur'] ?? 0) ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Produit</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Emplacement</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Plein système</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Plein physique</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Écart plein</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Vide système</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Vide physique</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Écart vide</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase">Alignement</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($inventaire)): ?>
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun enregistrement trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($inventaire as $item): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 text-left">
                                <div class="font-bold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($item['produit_nom']) ?></div>
                                <div class="text-[10px] text-gray-400 font-mono italic"><?= htmlspecialchars($item['produit_code']) ?> • <?= htmlspecialchars($item['categorie']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-left">
                                <div class="font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($item['emplacement_nom']) ?></div>
                                <div class="text-[10px] text-gray-500">
                                    <?= ucfirst($item['emplacement_type']) ?>
                                    <?php if ($item['vehicule']): ?>
                                    • <?= htmlspecialchars($item['vehicule']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php
                                $systemePlein = (float)($item['caisses_pleine'] ?? 0);
                                $systemeVide = (float)($item['caisses_vide'] ?? 0);
                                $physiquePlein = (float)($item['caisses_pleine_physique_calc'] ?? $item['caisses_pleine_physique'] ?? $systemePlein);
                                $physiqueVide = (float)($item['caisses_vide_physique_calc'] ?? $item['caisses_vide_physique'] ?? $systemeVide);
                                $ecartPlein = (float)($item['ecart_caisses_pleine'] ?? ($physiquePlein - $systemePlein));
                                $ecartVide = (float)($item['ecart_caisses_vide'] ?? ($physiqueVide - $systemeVide));
                                $hasEcart = abs($ecartPlein) > 0.0001 || abs($ecartVide) > 0.0001;
                            ?>
                            <td class="px-4 py-3 text-right font-bold text-green-600">
                                <?= number_format($systemePlein, 2, '.', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-blue-600">
                                <?= number_format($physiquePlein, 2, '.', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold <?= $ecartPlein == 0 ? 'text-green-600' : ($ecartPlein < 0 ? 'text-red-600' : 'text-orange-600') ?>">
                                <?= ($ecartPlein > 0 ? '+' : '') . number_format($ecartPlein, 2, '.', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-400">
                                <?= number_format($systemeVide, 2, '.', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-purple-600">
                                <?= number_format($physiqueVide, 2, '.', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold <?= $ecartVide == 0 ? 'text-green-600' : ($ecartVide < 0 ? 'text-red-600' : 'text-orange-600') ?>">
                                <?= ($ecartVide > 0 ? '+' : '') . number_format($ecartVide, 2, '.', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($hasEcart): ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800">
                                    ÉCART
                                </span>
                                <?php else: ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/40 dark:text-green-400 dark:border-green-800">
                                    ALIGNÉ
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center no-print">
                                <?php if ($hasEcart && can('stock.gerer')): ?>
                                <a href="<?= url('stocks/correction') ?>?produit_id=<?= (int)$item['produit_id'] ?>&emplacement_id=<?= (int)$item['emplacement_id'] ?>" class="btn btn-sm btn-primary">Corriger</a>
                                <?php else: ?>
                                <span class="text-xs text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!$printMode && $pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between no-print">
            <p class="text-sm text-gray-500">
                Page <span class="font-bold text-gray-900 dark:text-white"><?= $pagination['current_page'] ?></span> sur <?= $pagination['last_page'] ?>
            </p>
            <div class="flex space-x-1">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $pagination['current_page'] - 1])) ?>" class="btn-secondary btn-sm">Précédent</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['last_page'], $start + 4);
                if ($end - $start < 4) $start = max(1, $end - 4);
                
                for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $i])) ?>" 
                   class="btn-sm px-3 <?= $i == $pagination['current_page'] ? 'btn-primary font-bold' : 'btn-secondary font-normal' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $pagination['current_page'] + 1])) ?>" class="btn-secondary btn-sm">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
if ($printMode):
?>
<script>
    window.addEventListener('load', function () {
        window.print();
    });
    window.addEventListener('afterprint', function () {
        if (window.opener) {
            window.close();
        }
    });
</script>
<?php
endif;
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/app.php';
?>
