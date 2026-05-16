<?php 
$pageTitle = 'Ventes';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ventes</h1>
        <p class="text-gray-500 dark:text-gray-400">Gestion des ventes et factures</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= url('ventes/par-vehicule') ?>" class="btn btn-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Par véhicule
        </a>
        <a href="<?= url('ventes/create') ?>" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle vente
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="label">Client</label>
                <select name="client_id" class="input">
                    <option value="">Tous les clients</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>" <?= ($filters['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($client['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="label">Emplacement</label>
                <select name="emplacement_id" class="input">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($emplacements as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($filters['emplacement_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-40">
                <label class="label">Du</label>
                <input type="date" name="date_debut" class="input" value="<?= $filters['date_debut'] ?? '' ?>">
            </div>
            <div class="w-40">
                <label class="label">Au</label>
                <input type="date" name="date_fin" class="input" value="<?= $filters['date_fin'] ?? '' ?>">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary px-6">
                    Filtrer
                </button>
                <a href="<?= url('ventes') ?>" class="btn btn-secondary px-6" title="Réinitialiser">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </a>
            </div>
        </form>
    </div>
</div>

    <!-- Ventes List -->
    <div class="card">
        <div class="card-body p-0">
        <?php if (empty($ventes['data'])): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">Aucune vente trouvée</p>
            <a href="<?= url('ventes/create') ?>" class="btn btn-primary mt-4">Créer une vente</a>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Emplacement</th>
                        <th>Total TTC</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventes['data'] as $vente): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($vente['numero_facture']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></td>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($vente['client_nom']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($vente['zone_nom'] ?? '') ?></div>
                        </td>
                        <td><?= htmlspecialchars($vente['emplacement_nom'] ?? 'N/A') ?></td>
                        <td class="font-medium">
                            <?= format_money_converted($vente['total_ttc'] ?? 0) ?>
                        </td>
                        <td>
                            <?php if ($vente['statut'] === 'validee'): ?>
                            <span class="badge-success">Validée</span>
                            <?php elseif ($vente['statut'] === 'annulee'): ?>
                            <span class="badge-danger">Annulée</span>
                            <?php else: ?>
                            <span class="badge-warning"><?= htmlspecialchars($vente['statut']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <a href="<?= url('ventes/' . $vente['id']) ?>" class="text-primary-600 hover:text-primary-700" title="Voir">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="<?= url('ventes/' . $vente['id'] . '/print') ?>" target="_blank" class="text-gray-600 hover:text-gray-700" title="Imprimer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                                <?php if ($vente['statut'] === 'validee' && in_array($_SESSION['user_role'], ['admin', 'magasinier'])): ?>
                                <button onclick="annulerVente(<?= $vente['id'] ?>)" class="text-red-600 hover:text-red-700" title="Annuler">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($ventes['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Affichage <?= (($ventes['current_page'] - 1) * $ventes['per_page']) + 1 ?> à 
                    <?= min($ventes['current_page'] * $ventes['per_page'], $ventes['total']) ?> 
                    sur <?= $ventes['total'] ?> ventes
                </p>
                <div class="flex gap-2">
                    <?php if ($ventes['current_page'] > 1): ?>
                    <a href="?page=<?= $ventes['current_page'] - 1 ?>" class="btn btn-sm btn-secondary">Précédent</a>
                    <?php endif; ?>
                    <?php if ($ventes['current_page'] < $ventes['last_page']): ?>
                    <a href="?page=<?= $ventes['current_page'] + 1 ?>" class="btn btn-sm btn-primary">Suivant</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
async function annulerVente(id) {
    const ok = await App.confirm({
        title: 'Annuler la vente ?',
        message: 'Êtes-vous sûr de vouloir annuler cette vente ?',
        confirmText: 'Annuler',
        cancelText: 'Retour',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const result = await App.api(`/api/ventes/${id}/annuler`, 'POST');
        App.notify(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
