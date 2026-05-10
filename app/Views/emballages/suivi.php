<?php 
$pageTitle = 'Suivi emballages';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Suivi des emballages</h1>
        <p class="text-gray-500 dark:text-gray-400">Retours clients et dettes d'emballages en cours</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="<?= url('emballages') ?>" class="btn btn-secondary">Retour au tableau de bord</a>
        <a href="<?= url('retours-emballages') ?>" class="btn btn-primary">Nouveau retour</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <p class="stat-label">Retours</p>
        <p class="stat-value text-primary-600"><?= (int) ($statsRetours['resume']['nb_retours'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1">sur la période courante</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Caisses retournées</p>
        <p class="stat-value text-blue-600"><?= number_format((float) ($statsRetours['resume']['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsRetours['resume']['total_bouteilles'] ?? 0), 0, ',', ' ') ?> bouteilles</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dettes en cours</p>
        <p class="stat-value text-orange-600"><?= number_format((float) ($clientsEmballage['total_dette'] ?? 0), 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1">Depuis les ventes et retours</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dettes manuelles</p>
        <p class="stat-value text-green-600"><?= (int) ($statsDettes['nb_dettes'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsDettes['caisses_restantes'] ?? 0), 0, ',', ' ') ?> caisses restantes</p>
    </div>
</div>

<div class="card mb-8">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-bold">Détail des dettes d'emballages par client et produit</h3>
        <span class="text-xs text-gray-400">Période courante</span>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Produit</th>
                        <th class="text-right">Vendu</th>
                        <th class="text-right">Reçu</th>
                        <th class="text-right">Retourné</th>
                        <th class="text-right">Dette</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientsEmballage['lignes'] ?? [])): ?>
                        <tr><td colspan="6" class="text-center p-4 text-gray-500">Aucune dette d'emballage en cours</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($clientsEmballage['lignes'], 0, 15) as $ligne): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($ligne['client_nom']) ?></div>
                                    <div class="text-[10px] text-gray-400"><?= htmlspecialchars($ligne['zone_nom'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($ligne['produit_nom']) ?></div>
                                    <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></div>
                                </td>
                                <td class="text-right"><?= number_format((int) $ligne['caisses_vendues'], 0, ',', ' ') ?> cs</td>
                                <td class="text-right"><?= number_format((int) $ligne['caisses_vides_recues'], 0, ',', ' ') ?> cs</td>
                                <td class="text-right"><?= number_format((int) $ligne['caisses_retournees'], 0, ',', ' ') ?> cs</td>
                                <td class="text-right font-bold text-red-600"><?= number_format((int) $ligne['dette_caisses'], 0, ',', ' ') ?> cs</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Retours récents</h3>
            <span class="text-xs text-gray-400">Historique</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Produit</th>
                            <th class="text-right">Caisses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($retoursRecents)): ?>
                            <tr><td colspan="4" class="text-center p-4 text-gray-500">Aucun retour</td></tr>
                        <?php else: ?>
                            <?php foreach ($retoursRecents as $r): ?>
                                <?php
                                    $btlParCaisse = (int) ($r['bouteilles_par_caisses'] ?? 24);
                                    if ($btlParCaisse <= 0) {
                                        $btlParCaisse = 24;
                                    }
                                    $caisses = $r['quantite'] / $btlParCaisse;
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($r['date_retour'])) ?></td>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($r['client_nom']) ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($r['emplacement_nom']) ?></div>
                                    </td>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($r['produit_nom']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= (int) $btlParCaisse ?> btl/cs</div>
                                    </td>
                                    <td class="text-right font-bold text-blue-700"><?= number_format($caisses, 1, ',', ' ') ?> cs</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Dettes d'emballages en cours</h3>
            <span class="text-xs text-gray-400">Remboursement</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Approvisionnement</th>
                            <th class="text-right">Reste</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dettesEnCours)): ?>
                            <tr><td colspan="4" class="text-center p-4 text-gray-500">Aucune dette en cours</td></tr>
                        <?php else: ?>
                            <?php foreach ($dettesEnCours as $dette): ?>
                                <?php $reste = (int) (($dette['quantite_dette_caisses'] ?? 0) - ($dette['quantite_remboursee'] ?? 0)); ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($dette['produit_nom'] ?? 'N/A') ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($dette['produit_code'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($dette['numero_bon'] ?? 'N/A') ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($dette['fournisseur'] ?? '') ?></div>
                                    </td>
                                    <td class="text-right font-bold text-red-600"><?= number_format($reste, 0, ',', ' ') ?> cs</td>
                                    <td class="text-center">
                                        <button onclick="openRemboursementModal(<?= (int) $dette['id'] ?>, <?= (int) $reste ?>)" class="btn btn-sm btn-primary">
                                            Rembourser
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
