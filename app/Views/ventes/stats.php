<?php 
$pageTitle = 'Statistiques des ventes';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('ventes') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux ventes
    </a>
</div>

<!-- Filtres -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Date début</label>
                <input type="date" name="date_debut" class="input" value="<?= $dateDebut ?>">
            </div>
            <div>
                <label class="label">Date fin</label>
                <input type="date" name="date_fin" class="input" value="<?= $dateFin ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <div class="stat-label">Total TTC</div>
        <div class="stat-value text-green-600">
            <?= format_money_converted($stats['total_ttc'] ?? 0) ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total HT</div>
        <div class="stat-value text-blue-600">
            <?= format_money_converted($stats['total_ht'] ?? 0) ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nombre de ventes</div>
        <div class="stat-value text-purple-600">
            <?= $stats['nb_ventes'] ?? 0 ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Panier moyen</div>
        <div class="stat-value text-yellow-600">
            <?= format_money_converted(($stats['total_ttc'] ?? 0) / max($stats['nb_ventes'] ?? 1, 1)) ?>
        </div>
    </div>
</div>

<!-- Ventes par produit -->
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-semibold">Ventes par produit</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ventesParProduit)): ?>
        <div class="p-12 text-center text-gray-500">
            Aucune vente sur cette période
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité vendue</th>
                        <th>Chiffre d'affaires</th>
                        <th>Part</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalCA = array_sum(array_column($ventesParProduit, 'total_vente'));
                    foreach ($ventesParProduit as $vente): 
                        $part = $totalCA > 0 ? ($vente['total_vente'] / $totalCA) * 100 : 0;
                    ?>
                    <tr>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($vente['nom']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($vente['code']) ?></div>
                        </td>
                        <td><?= $vente['quantite_vendue'] ?></td>
                        <td class="font-medium"><?= format_money_converted($vente['total_vente'] ?? 0) ?></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: <?= min($part, 100) ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-500"><?= number_format($part, 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
