<?php 
$pageTitle = 'Statistiques des pertes';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('pertes') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux pertes
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
        <div class="stat-label">Total pertes</div>
        <div class="stat-value text-red-600"><?= $stats['total_quantite'] ?? 0 ?></div>
        <div class="text-sm text-gray-500">bouteilles</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Valeur totale</div>
        <div class="stat-value text-red-600"><?= format_money_converted($stats['total_valeur'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nombre de déclarations</div>
        <div class="stat-value"><?= $stats['nb_pertes'] ?? 0 ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Moyenne par incident</div>
        <div class="stat-value"><?= round(($stats['total_quantite'] ?? 0) / max($stats['nb_pertes'] ?? 1, 1), 1) ?></div>
        <div class="text-sm text-gray-500">bouteilles</div>
    </div>
</div>

<!-- Par type -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Pertes par type</h2>
        </div>
        <div class="card-body p-0">
            <?php if (empty($parType)): ?>
            <div class="p-6 text-center text-gray-500">Aucune donnée</div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Valeur</th>
                            <th>Part</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = $stats['total_quantite'] ?? 0;
                        foreach ($parType as $type): 
                            $part = $total > 0 ? ($type['quantite'] / $total) * 100 : 0;
                        ?>
                        <tr>
                            <td class="font-medium capitalize"><?= htmlspecialchars($type['type_perte']) ?></td>
                            <td><?= $type['quantite'] ?></td>
                            <td><?= format_money_converted($type['valeur'] ?? 0) ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-red-600 h-2 rounded-full" style="width: <?= min($part, 100) ?>%"></div>
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
    
    <!-- Par produit -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Pertes par produit</h2>
        </div>
        <div class="card-body p-0">
            <?php if (empty($parProduit)): ?>
            <div class="p-6 text-center text-gray-500">Aucune donnée</div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parProduit as $p): ?>
                        <tr>
                            <td class="font-medium"><?= htmlspecialchars($p['produit_nom']) ?></td>
                            <td><?= $p['quantite'] ?></td>
                            <td><?= format_money_converted($p['valeur'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
