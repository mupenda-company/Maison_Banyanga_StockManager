<?php
$pageTitle = 'Tableau de bord emballages';
$stockEmballages = $stockEmballages ?? ['total_caisses' => 0, 'fixe_caisses' => 0, 'mobile_caisses' => 0, 'par_produit' => [], 'par_emplacement' => []];
$resumeEmprunts = $resumeEmprunts ?? ['nb_en_cours' => 0, 'recu_vide' => 0, 'donne_vide' => 0, 'recu_plein' => 0, 'donne_plein' => 0];
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de bord emballages</h1>
        <p class="text-gray-500 dark:text-gray-400">Inventaire reel des emballages vides, dettes clients et emprunts / prets en cours</p>
    </div>
    <div class="flex items-center gap-3">
        <?php if (can('emballages.gerer')): ?>
        <a href="<?= url('emballages/inventaire-initial') ?>" class="btn btn-secondary">Inventaire initial</a>
        <?php endif; ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['print' => 1])) ?>" target="_blank" class="btn btn-secondary">Imprimer</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-secondary">Exporter</a>
        <a href="<?= url('emballages/emprunts') ?>" class="btn btn-primary">Emprunts / prets</a>
    </div>
</div>

<form method="GET" class="flex items-center gap-3 mb-6">
    <input type="date" name="date_debut" value="<?= htmlspecialchars($dateDebut) ?>" class="input py-1 px-2 text-sm">
    <span class="text-gray-400">au</span>
    <input type="date" name="date_fin" value="<?= htmlspecialchars($dateFin) ?>" class="input py-1 px-2 text-sm">
    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
</form>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <p class="stat-label">Emballages disponibles</p>
        <p class="stat-value text-primary-600"><?= number_format((int) $stockEmballages['total_caisses'], 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1">Total caisses vides en stock</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">En entrepot</p>
        <p class="stat-value text-green-600"><?= number_format((int) $stockEmballages['fixe_caisses'], 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1">Emplacements fixes</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dans vehicules</p>
        <p class="stat-value text-blue-600"><?= number_format((int) $stockEmballages['mobile_caisses'], 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1">Emplacements mobiles</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Operations ouvertes</p>
        <p class="stat-value text-orange-600"><?= (int) $resumeEmprunts['nb_en_cours'] ?></p>
        <p class="text-xs text-gray-400 mt-1">Emprunts / prets non soldes</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-8">
    <div class="card xl:col-span-2">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold dark:text-white">Stock emballages par produit</h3>
            <span class="text-xs text-gray-400">Caisses vides</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Entrepot</th>
                            <th class="text-right">Vehicules</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stockEmballages['par_produit'])): ?>
                            <tr><td colspan="4" class="text-center p-4 text-gray-500">Aucun produit actif</td></tr>
                        <?php else: ?>
                            <?php foreach ($stockEmballages['par_produit'] as $ligne): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($ligne['produit_nom']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></div>
                                    </td>
                                    <td class="text-right font-bold <?= (int) $ligne['total_caisses'] < 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' ?>"><?= number_format((int) $ligne['total_caisses'], 0, ',', ' ') ?> cs</td>
                                    <td class="text-right"><?= number_format((int) $ligne['fixe_caisses'], 0, ',', ' ') ?> cs</td>
                                    <td class="text-right"><?= number_format((int) $ligne['mobile_caisses'], 0, ',', ' ') ?> cs</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="font-bold dark:text-white">Emprunts / prets en cours</h3>
        </div>
        <div class="card-body space-y-4">
            <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                <span class="text-sm text-gray-500">Emballages empruntes</span>
                <span class="font-bold text-green-600"><?= number_format((int) $resumeEmprunts['recu_vide'], 0, ',', ' ') ?> cs</span>
            </div>
            <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                <span class="text-sm text-gray-500">Emballages pretes</span>
                <span class="font-bold text-red-600"><?= number_format((int) $resumeEmprunts['donne_vide'], 0, ',', ' ') ?> cs</span>
            </div>
            <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                <span class="text-sm text-gray-500">Produits pleins empruntes</span>
                <span class="font-bold text-green-600"><?= number_format((int) $resumeEmprunts['recu_plein'], 0, ',', ' ') ?> cs</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Produits pleins pretes</span>
                <span class="font-bold text-red-600"><?= number_format((int) $resumeEmprunts['donne_plein'], 0, ',', ' ') ?> cs</span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold dark:text-white">Stock emballages par emplacement</h3>
            <span class="text-xs text-gray-400">Fixe et mobile</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Emplacement</th>
                            <th>Type</th>
                            <th class="text-right">Caisses vides</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockEmballages['par_emplacement'] as $ligne): ?>
                        <tr>
                            <td class="font-medium"><?= htmlspecialchars($ligne['emplacement_nom']) ?></td>
                            <td><?= ($ligne['emplacement_type'] ?? '') === 'mobile' ? '<span class="badge-info">Vehicule</span>' : '<span class="badge-success">Entrepot</span>' ?></td>
                            <td class="text-right font-bold <?= (int) $ligne['total_caisses'] < 0 ? 'text-red-600' : '' ?>"><?= number_format((int) $ligne['total_caisses'], 0, ',', ' ') ?> cs</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold dark:text-white">Dettes d'emballages clients</h3>
            <span class="text-xs text-gray-400">Periode filtree</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Produit</th>
                            <th class="text-right">Dette</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientsEmballage['lignes'] ?? [])): ?>
                            <tr><td colspan="4" class="text-center p-4 text-gray-500">Aucune dette d'emballage sur la periode</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($clientsEmballage['lignes'], 0, 8) as $ligne): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($ligne['client_nom']) ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($ligne['zone_nom'] ?? 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($ligne['produit_nom']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></div>
                                    </td>
                                    <td class="text-right font-bold text-red-600"><?= number_format((int) $ligne['dette_caisses'], 0, ',', ' ') ?> cs</td>
                                    <td class="text-right">
                                        <?php if (can('emballages.gerer')): ?>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="openQuickRetour(<?= (int) $ligne['client_id'] ?>, <?= (int) $ligne['produit_id'] ?>, <?= (int) $ligne['bouteilles_par_caisses'] ?>, <?= (int) $ligne['dette_caisses'] ?>)">Completer</button>
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
</div>

<div class="card">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-bold dark:text-white">Retours recents</h3>
        <a href="<?= url('emballages/suivi') ?>" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
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
                        <th>Receptionne a</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retoursRecents)): ?>
                        <tr><td colspan="5" class="text-center p-4 text-gray-500">Aucun retour recent</td></tr>
                    <?php else: ?>
                        <?php foreach ($retoursRecents as $r): ?>
                            <?php
                                $btlParCaisse = (int) ($r['bouteilles_par_caisses'] ?? 24);
                                if ($btlParCaisse <= 0) { $btlParCaisse = 24; }
                                $caisses = $r['quantite'] / $btlParCaisse;
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($r['date_retour'])) ?></td>
                                <td class="font-medium"><?= htmlspecialchars($r['client_nom']) ?></td>
                                <td><?= htmlspecialchars($r['produit_nom']) ?></td>
                                <td class="text-right font-bold text-blue-700"><?= number_format($caisses, 1, ',', ' ') ?> cs</td>
                                <td><?= htmlspecialchars($r['emplacement_nom']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (can('emballages.gerer')): ?>
<div x-data="quickRetourModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Completer un retour</h3>
            <form @submit.prevent="save">
                <div class="space-y-4">
                    <div>
                        <label class="label">Caisses retournees</label>
                        <input type="number" x-model.number="form.caisses" class="input" min="0.01" step="0.01" :max="maxCaisses" required>
                        <p class="text-xs text-gray-500 mt-1">Dette restante: <span x-text="maxCaisses"></span> cs</p>
                    </div>
                    <div>
                        <label class="label">Receptionne a</label>
                        <select x-model="form.emplacement_id" class="input" required>
                            <?php foreach ($emplacements as $e): ?>
                            <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('quickRetourModal', () => ({
        isOpen: false,
        loading: false,
        bouteillesParCaisse: 24,
        maxCaisses: 0,
        form: { client_id: '', produit_id: '', caisses: 1, emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>' },
        open(clientId, produitId, bouteillesParCaisse, detteCaisses) {
            this.bouteillesParCaisse = parseInt(bouteillesParCaisse || 24, 10) || 24;
            this.maxCaisses = parseInt(detteCaisses || 0, 10) || 0;
            this.form = { client_id: clientId, produit_id: produitId, caisses: this.maxCaisses, emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>' };
            this.isOpen = true;
        },
        close() { this.isOpen = false; },
        async save() {
            this.loading = true;
            try {
                await App.api('/api/retours-emballages', 'POST', {
                    client_id: this.form.client_id,
                    produit_id: this.form.produit_id,
                    quantite: Math.round((parseFloat(this.form.caisses) || 0) * this.bouteillesParCaisse),
                    emplacement_id: this.form.emplacement_id
                });
                App.notify('Retour enregistre', 'success');
                setTimeout(() => location.reload(), 700);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openQuickRetour(clientId, produitId, bouteillesParCaisse, detteCaisses) {
    const modal = document.querySelector('[x-data="quickRetourModal"]');
    Alpine.$data(modal).open(clientId, produitId, bouteillesParCaisse, detteCaisses);
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
