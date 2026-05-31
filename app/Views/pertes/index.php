<?php 
$pageTitle = 'Pertes';
$printMode = isset($print_mode) ? (bool) $print_mode : false;
$baseQuery = [];
foreach (['type', 'date_debut', 'date_fin', 'produit_id', 'emplacement_id'] as $key) {
    if (!empty($_GET[$key])) {
        $baseQuery[$key] = $_GET[$key];
    }
}
$printUrl = '?' . http_build_query(array_merge($baseQuery, ['print' => 1]));
$exportUrl = '?' . http_build_query(array_merge($baseQuery, ['export' => 'excel']));
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6 no-print">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pertes</h1>
        <p class="text-gray-500 dark:text-gray-400">Gestion des pertes et casses</p>
    </div>
    <div class="flex gap-2">
    <button type="button" onclick="window.open('<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>','_blank')" class="btn btn-secondary">Imprimer</button>
    <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Exporter Excel</a>
    <?php if (can('pertes.creer')): ?>
    <a href="<?= url('pertes/create') ?>" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Déclarer une perte
    </a>
    <?php endif; ?>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 no-print">
    <div class="stat-card">
        <p class="stat-label">Pertes ce mois</p>
        <p class="stat-value text-red-600"><?= number_format($stats['total_caisses'] ?? 0, 1, '.', ' ') ?> cs</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Valeur estimée</p>
        <p class="stat-value text-red-600"><?= format_money_converted($stats['total_valeur'] ?? 0) ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Nombre de déclarations</p>
        <p class="stat-value"><?= $stats['nb_pertes'] ?? 0 ?></p>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-6 no-print">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Type</label>
                <select name="type" class="input">
                    <option value="">Tous</option>
                    <option value="casse" <?= ($filters['type'] ?? '') == 'casse' ? 'selected' : '' ?>>Casse</option>
                    <option value="perte" <?= ($filters['type'] ?? '') == 'perte' ? 'selected' : '' ?>>Perte</option>
                    <option value="vol" <?= ($filters['type'] ?? '') == 'vol' ? 'selected' : '' ?>>Vol</option>
                    <option value="peremption" <?= ($filters['type'] ?? '') == 'peremption' ? 'selected' : '' ?>>Péremption</option>
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
            <a href="<?= url('pertes') ?>" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<?php if ($printMode): ?>
<div class="print-only mb-6 border-b-2 border-gray-800 pb-4">
    <h1 class="text-2xl font-bold uppercase"><?= htmlspecialchars((new Parametre())->get('nom_entreprise', APP_NAME)) ?></h1>
    <p class="text-sm">Pertes - imprime le <?= date('d/m/Y H:i') ?></p>
</div>
<?php endif; ?>

<!-- Liste des pertes -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($pertes)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-gray-500">Aucune perte enregistrée</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th>Type Stock</th>
                        <th>Catégorie</th>
                        <th class="text-right">Quantité (cs)</th>
                        <th class="text-right">Valeur</th>
                        <th>Emplacement</th>
                        <th>Motif</th>
                        <th class="text-right no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pertes as $perte):
                        $caisses = (float)$perte['quantite'];
                        $btlParCaisse = (int)($perte['bouteilles_par_caisses'] ?? 24);
                        $totalBouteilles = round($caisses * $btlParCaisse);
                        $caissesPleines = intdiv($totalBouteilles, $btlParCaisse);
                        $bouteillesReste = $totalBouteilles % $btlParCaisse;

                        if ($caissesPleines > 0 && $bouteillesReste > 0) {
                            $quantiteTop = $caissesPleines . ' cs';
                            $quantiteBottom = $bouteillesReste . ' btl';
                        } elseif ($caissesPleines > 0) {
                            $quantiteTop = $caissesPleines . ' cs';
                            $quantiteBottom = '';
                        } else {
                            $quantiteTop = '';
                            $quantiteBottom = $totalBouteilles . ' btl';
                        }
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($perte['date_perte'])) ?></td>
                        <td>
                            <div class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($perte['produit_nom']) ?></div>
                            <div class="text-[10px] font-mono text-gray-500"><?= htmlspecialchars($perte['produit_code']) ?></div>
                        </td>
                        <td>
                            <?php if ($perte['type_stock'] === 'vide'): ?>
                            <span class="px-2 py-1 text-[10px] font-bold bg-gray-100 text-gray-600 rounded uppercase">Vide</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-[10px] font-bold bg-blue-100 text-blue-600 rounded uppercase">Plein</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($perte['type_perte'] === 'casse'): ?>
                            <span class="badge-danger">Casse</span>
                            <?php elseif ($perte['type_perte'] === 'vol'): ?>
                            <span class="badge-warning">Vol</span>
                            <?php elseif ($perte['type_perte'] === 'peremption'): ?>
                            <span class="badge-info">Péremption</span>
                            <?php else: ?>
                            <span class="badge-secondary">Perte</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($printMode): ?>
                                <div class="font-bold text-red-600">
                                    <?php if ($quantiteTop && $quantiteBottom): ?>
                                        <?= $quantiteTop ?> + <?= $quantiteBottom ?>
                                    <?php else: ?>
                                        <?= $quantiteTop ?: $quantiteBottom ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="font-black text-red-600"><?= $quantiteTop ?></div>
                                <div class="text-sm text-red-500"><?= $quantiteBottom ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-bold"><?= format_money_converted($perte['valeur_perte'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($perte['emplacement_nom'] ?? '-') ?></td>
                        <td class="max-w-xs truncate text-sm"><?= htmlspecialchars($perte['motif'] ?? '-') ?></td>
                        <td class="text-right">
                            <?php if (can('pertes.voir')): ?>
                            <button onclick="supprimerPerte(<?= $perte['id'] ?>)" class="text-red-500 hover:text-red-700 transition-colors" title="Supprimer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function supprimerPerte(id) {
    const ok = await App.confirm({
        title: 'Supprimer la perte ?',
        message: 'Êtes-vous sûr de vouloir supprimer cette perte ? Le stock sera automatiquement restauré.',
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
        type: 'danger'
    });
    if (!ok) return;
    
    try {
        const result = await App.api(`/api/pertes/${id}`, 'DELETE');
        const message = (result && result.message) ? result.message : 'Perte supprimée avec succès';
        App.notify(message, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        App.notify(e.message || 'Une erreur est survenue lors de la suppression', 'error');
    }
}
</script>

<?php if ($printMode): ?>
<style>
@media print {
    @page { margin: 10mm; }
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table th, .table td { border: 1px solid #ddd !important; padding: 6px !important; font-size: 10pt !important; }
}
</style>
<script>
window.addEventListener('load', function () { window.print(); });
window.addEventListener('afterprint', function () { if (window.opener) window.close(); });
</script>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
