<?php 
$pageTitle = 'Gestion des stocks';
ob_start();
?>

<!-- Filtres -->
<div class="card mb-6">
    <div class="card-body py-3">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Produit</label>
                <select name="produit_id" class="input py-1.5 w-48">
                    <option value="">Tous les produits</option>
                    <?php foreach ($produits as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($filters['produit_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Emplacement</label>
                <select name="emplacement_id" class="input py-1.5 w-48">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($emplacements as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($filters['emplacement_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Statut</label>
                <select name="statut" class="input py-1.5 w-40">
                    <option value="">Tous</option>
                    <option value="ok" <?= ($filters['statut'] ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                    <option value="critique" <?= ($filters['statut'] ?? '') === 'critique' ? 'selected' : '' ?>>CRITIQUE</option>
                </select>
            </div>
            <div class="flex gap-2 ml-auto">
                <button type="submit" class="btn-primary py-1.5 px-4 mr-2">Filtrer</button>
                <a href="<?= url('stocks') ?>" class="btn-secondary py-1.5 px-4">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<!-- Résumé par emplacement -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php foreach ($emplacements as $emp): 
        $caisses = (int) round($emp['total_caisses_pleine'] ?? 0);
    ?>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label"><?= htmlspecialchars($emp['nom']) ?></p>
                <p class="stat-value text-2xl font-bold text-gray-900 dark:text-white">
                    <?= number_format($caisses, 0, '.', ' ') ?> <span class="text-sm font-normal text-gray-500">cs</span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-full flex items-center justify-center
                <?= $emp['type'] === 'fixe' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/50' : 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50' ?>">
                <?php if ($emp['type'] === 'fixe'): ?>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <?php else: ?>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            <span class="badge-<?= $emp['type'] === 'fixe' ? 'info' : 'warning' ?>">
                <?= $emp['type'] === 'fixe' ? 'Fixe' : 'Mobile' ?>
            </span>
        </p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Actions -->
<div class="flex flex-wrap gap-3 mb-6">
    <a href="<?= url('stocks/inventaire') ?>" class="btn-secondary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2-2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Inventaire complet
    </a>
    <a href="<?= url('stocks/inventaire-initial') ?>" class="btn-secondary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/>
        </svg>
        Inventaire initial
    </a>
    <a href="<?= url('stocks/mouvements') ?>" class="btn-secondary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Historique mouvements
    </a>
    <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_MAGASINIER])): ?>
    <button onclick="openTransfertModal()" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        Transfert
    </button>
    <?php endif; ?>
</div>

<!-- Tableau des stocks -->
<div class="card">
    <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Détail par produit et emplacement</h2>
        <div class="flex items-center space-x-2">
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-secondary btn-sm py-1.5">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exporter Excel
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Produit</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Emplacement</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Stock Plein</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Stock Vide</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($stocks)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun stock enregistré
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($stocks as $stock): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-left max-w-[150px]">
                                <div class="font-bold text-gray-700 dark:text-gray-300 truncate" title="<?= htmlspecialchars($stock['produit_nom']) ?>"><?= htmlspecialchars($stock['produit_nom']) ?></div>
                                <div class="text-[10px] text-gray-400 font-mono italic"><?= htmlspecialchars($stock['produit_code']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-left max-w-[150px]">
                                <div class="font-medium text-gray-800 dark:text-gray-200 truncate" title="<?= htmlspecialchars($stock['emplacement_nom']) ?>"><?= htmlspecialchars($stock['emplacement_nom']) ?></div>
                                <?php if ($stock['vehicule_immatriculation']): ?>
                                <div class="text-[10px] text-gray-500 font-bold"><?= htmlspecialchars($stock['vehicule_immatriculation']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge-<?= $stock['emplacement_type'] === 'fixe' ? 'info' : 'warning' ?> text-[10px]">
                                    <?= $stock['emplacement_type'] === 'fixe' ? 'Fixe' : 'Mobile' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-bold text-green-600 truncate"><?= number_format((int) round($stock['caisses_pleine']), 0, '.', ' ') ?> <span class="text-[10px] font-normal">cs</span></div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-bold text-gray-600 dark:text-gray-400 truncate"><?= number_format((int) round($stock['caisses_vide']), 0, '.', ' ') ?> <span class="text-[10px] font-normal">cs</span></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center">
                                    <?php if ($stock['quantite_pleine'] <= ($stock['seuil_alerte'] ?? 0)): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800">CRITIQUE</span>
                                    <?php else: ?>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/40 dark:text-green-400 dark:border-green-800">OK</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Page <span class="font-bold text-gray-900 dark:text-white"><?= $pagination['current_page'] ?></span> sur <?= $pagination['last_page'] ?>
                <span class="mx-1">•</span> Total: <?= $pagination['total'] ?>
            </p>
            <div class="flex space-x-1">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="btn btn-secondary btn-sm">Précédent</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['last_page'], $start + 4);
                if ($end - $start < 4) $start = max(1, $end - 4);
                
                for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="btn-sm px-3 <?= $i == $pagination['current_page'] ? 'btn-primary font-bold' : 'btn-secondary font-normal' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="btn btn-secondary btn-sm">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Transfert -->
<div 
    x-data="transfertModal"
    x-show="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="close()"></div>
        
        <div class="modal-content relative w-full max-w-lg">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transfert de stock</h3>
                    <button @click="close()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="card-body">
                    <form @submit.prevent="save()">
                        <div class="space-y-4">
                            <div>
                                <label class="label">Produit *</label>
                                <select x-model="form.produit_id" class="input" required>
                                    <option value="">Sélectionner</option>
                                    <?php 
                                    // Utilisation des produits déjà chargés en PHP pour le contrôleur
                                    $produitModel = new Produit();
                                    $allProduits = $produitModel->getActive();
                                    foreach ($allProduits as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (<?= htmlspecialchars($p['code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Source *</label>
                                    <select x-model="form.emplacement_source" class="input" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($emplacements as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Destination *</label>
                                    <select x-model="form.emplacement_dest" class="input" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($emplacements as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="label">Quantité (Caisses) *</label>
                                <input type="number" x-model.number="form.caisses" class="input" min="0.01" step="0.01" required>
                                <p class="text-[10px] text-gray-500 mt-1">Le transfert sera converti en bouteilles selon le produit.</p>
                            </div>
                            <div>
                                <label class="label">Motif</label>
                                <input type="text" x-model="form.motif" class="input" placeholder="Raison du transfert">
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="close()" class="btn btn-secondary">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                Effectuer le transfert
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transfertModal', () => ({
        isOpen: false,
        produits: <?= json_encode($allProduits) ?>,
        form: {
            produit_id: '',
            emplacement_source: '',
            emplacement_dest: '',
            caisses: 1,
            motif: ''
        },
        loading: false,
        
        open() {
            this.form = {
                produit_id: '',
                emplacement_source: '',
                emplacement_dest: '',
                caisses: 1,
                motif: ''
            };
            this.isOpen = true;
        },
        
        close() {
            this.isOpen = false;
        },
        
        async save() {
            if (this.form.emplacement_source === this.form.emplacement_dest) {
                App.notify('Les emplacements doivent être différents', 'error');
                return;
            }

            this.loading = true;
            try {
                const product = this.produits.find(p => p.id == this.form.produit_id);
                const btlParCaisse = product ? (parseInt(product.bouteilles_par_caisses) || 24) : 24;
                
                const data = {
                    produit_id: this.form.produit_id,
                    emplacement_source: this.form.emplacement_source,
                    emplacement_dest: this.form.emplacement_dest,
                    quantite: this.form.caisses * btlParCaisse,
                    motif: this.form.motif
                };

                await App.api('/api/stocks/transfert', 'POST', data);
                App.notify('Transfert effectué avec succès');
                this.close();
                window.location.reload();
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openTransfertModal() {
    const modalEl = document.querySelector('[x-data="transfertModal"]');
    if (modalEl) {
        modalEl.classList.add('opacity-50', 'pointer-events-none');
        const modal = Alpine.$data(modalEl);
        if (modal) {
            modal.open();
            // Retirer l'opacité après l'ouverture (ou gérer via Alpine)
            setTimeout(() => modalEl.classList.remove('opacity-50', 'pointer-events-none'), 100);
        }
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
