<?php 
$pageTitle = 'Dépenses';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dépenses</h1>
        <p class="text-gray-500 dark:text-gray-400">Gestion des dépenses opérationnelles</p>
    </div>
    <?php if (can('depenses.creer')): ?>
    <a href="<?= url('depenses/create') ?>" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Ajouter une dépense
    </a>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="stat-card">
        <p class="stat-label">Total dépenses</p>
        <p class="stat-value text-red-600"><?= format_money_converted($stats['total_depenses'] ?? 0) ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Nombre de dépenses</p>
        <p class="stat-value"><?= $stats['nb_depenses'] ?? 0 ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Catégories</p>
        <p class="stat-value"><?= count($parCategorie) ?></p>
    </div>
</div>

<!-- Dépenses par catégorie -->
<?php if (!empty($parCategorie)): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php 
    $catColors = [
        'Transport' => 'blue',
        'Carburant' => 'yellow',
        'Maintenance' => 'orange',
        'Restauration' => 'green',
        'Autres' => 'gray'
    ];
    foreach ($parCategorie as $cat): 
        $color = $catColors[$cat['categorie']] ?? 'gray';
    ?>
    <div class="bg-<?= $color ?>-50 dark:bg-<?= $color ?>-900/20 rounded-lg border border-<?= $color ?>-200 dark:border-<?= $color ?>-800 p-4">
        <p class="font-semibold text-<?= $color ?>-700 dark:text-<?= $color ?>-300"><?= htmlspecialchars($cat['categorie']) ?></p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= $cat['nb'] ?? 0 ?> dépense(s)</p>
        <p class="text-sm font-bold text-<?= $color ?>-600 dark:text-<?= $color ?>-400 mt-1"><?= format_money_converted($cat['total'] ?? 0) ?></p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filtres -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Catégorie</label>
                <select name="categorie" class="input">
                    <option value="">Toutes</option>
                    <option value="Transport" <?= ($filters['categorie'] ?? '') == 'Transport' ? 'selected' : '' ?>>Transport</option>
                    <option value="Carburant" <?= ($filters['categorie'] ?? '') == 'Carburant' ? 'selected' : '' ?>>Carburant</option>
                    <option value="Maintenance" <?= ($filters['categorie'] ?? '') == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    <option value="Restauration" <?= ($filters['categorie'] ?? '') == 'Restauration' ? 'selected' : '' ?>>Restauration</option>
                    <option value="Autres" <?= ($filters['categorie'] ?? '') == 'Autres' ? 'selected' : '' ?>>Autres</option>
                </select>
            </div>
            <div>
                <label class="label">Date début</label>
                <input type="date" name="date_debut" class="input" value="<?= $filters['date_debut'] ?? '' ?>">
            </div>
            <div>
                <label class="label">Date fin</label>
                <input type="date" name="date_fin" class="input" value="<?= $filters['date_fin'] ?? '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="<?= url('depenses') ?>" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Date</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Catégorie</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Description</th>
                        <th class="text-right py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Montant</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Enregistré par</th>
                        <th class="text-center py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($depenses)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">Aucune dépense enregistrée</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($depenses as $d): ?>
                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="py-3 px-4 text-gray-900 dark:text-white"><?= date('d/m/Y', strtotime($d['date_depense'])) ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $catBadge = [
                                'Transport' => 'blue',
                                'Carburant' => 'yellow',
                                'Maintenance' => 'orange',
                                'Restauration' => 'green',
                                'Autres' => 'gray'
                            ];
                            $badgeColor = $catBadge[$d['categorie']] ?? 'gray';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $badgeColor ?>-100 text-<?= $badgeColor ?>-800 dark:bg-<?= $badgeColor ?>-900/50 dark:text-<?= $badgeColor ?>-300">
                                <?= htmlspecialchars($d['categorie']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-900 dark:text-white"><?= htmlspecialchars($d['description']) ?></td>
                        <td class="py-3 px-4 text-right font-semibold text-red-600 dark:text-red-400"><?= format_money_converted($d['montant']) ?></td>
                        <td class="py-3 px-4 text-gray-600 dark:text-gray-400"><?= htmlspecialchars(($d['created_by_prenom'] ?? '') . ' ' . ($d['created_by_nom'] ?? '')) ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php if (can('depenses.supprimer')): ?>
                            <button onclick="deleteDepense(<?= $d['id'] ?>)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function deleteDepense(id) {
    if (!confirm('Supprimer cette dépense ?')) return;
    try {
        const res = await fetch(BASE_URL + '/api/depenses/' + id, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Erreur lors de la suppression');
        }
    } catch (e) {
        alert('Erreur réseau');
    }
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
