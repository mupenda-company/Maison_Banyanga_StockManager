<?php 
$pageTitle = 'Dettes fournisseurs';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('approvisionnements') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux approvisionnements
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="stat-card">
        <p class="stat-label">Nombre de dettes</p>
        <p class="stat-value text-red-600"><?= number_format($total['nb_dettes'] ?? 0, 0, ',', ' ') ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Total caisses dues</p>
        <p class="stat-value text-yellow-600"><?= number_format($total['total_caisses'] ?? 0, 0, ',', ' ') ?> cs</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dette moyenne</p>
        <p class="stat-value text-red-600"><?= ($total['nb_dettes'] ?? 0) > 0 ? number_format(($total['total_caisses'] ?? 0) / max(($total['nb_dettes'] ?? 1), 1), 1, ',', ' ') : '0' ?> cs</p>
    </div>
</div>

<!-- Liste des dettes -->
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-semibold">Dettes en cours</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($dettes)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-500">Aucune dette en cours</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Approvisionnement</th>
                        <th>Date</th>
                        <th>Quantité due</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dettes as $dette): ?>
                    <?php $reste = (int)($dette['quantite_dette_caisses'] - ($dette['quantite_remboursee'] ?? 0)); ?>
                    <tr>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($dette['produit_nom'] ?? 'N/A') ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($dette['produit_code'] ?? '') ?></div>
                        </td>
                        <td>
                            <a href="<?= url('approvisionnements/' . $dette['approvisionnement_id']) ?>" class="text-primary-600 hover:underline">
                                <?= htmlspecialchars($dette['numero_bon'] ?? $dette['approvisionnement_id']) ?>
                            </a>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($dette['fournisseur'] ?? '') ?></div>
                        </td>
                        <td><?= !empty($dette['date_approvisionnement']) ? date('d/m/Y', strtotime($dette['date_approvisionnement'])) : '-' ?></td>
                        <td class="font-bold text-red-600"><?= number_format($reste, 0, ',', ' ') ?> cs</td>
                        <td>
                            <?php if (($dette['statut'] ?? '') === 'solde'): ?>
                            <span class="badge-success">Soldée</span>
                            <?php else: ?>
                            <span class="badge-warning">En cours</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="openRemboursementModal(<?= (int)$dette['id'] ?>, <?= (int)$reste ?>)" 
                                class="btn btn-sm btn-primary">
                                Rembourser
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal remboursement -->
<div x-data="remboursementModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Rembourser la dette</h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="rembourser">
                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm text-gray-500">Reste à rembourser</p>
                        <p class="text-2xl font-bold text-red-600" x-text="(resteDu || 0).toLocaleString() + ' cs'"></p>
                    </div>
                    <div>
                        <label class="label">Quantité à rembourser (cs)</label>
                        <input type="number" x-model.number="form.quantite" class="input" min="1" :max="resteDu" required>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Rembourser</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('remboursementModal', () => ({
        isOpen: false,
        loading: false,
        detteId: null,
        resteDu: 0,
        form: {
            quantite: 0
        },
        
        open(id, montant) {
            this.detteId = id;
            this.resteDu = montant;
            this.form.quantite = montant;
            this.isOpen = true;
        },
        
        close() {
            this.isOpen = false;
        },
        
        async rembourser() {
            this.loading = true;
            try {
                const result = await App.api(`/api/dettes/${this.detteId}/rembourser`, 'POST', this.form);
                App.notify(result.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openRemboursementModal(id, montant) {
    const modal = Alpine.$data(document.querySelector('[x-data="remboursementModal"]'));
    modal.open(id, montant);
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
