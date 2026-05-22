<?php 
$pageTitle = 'Retours Emballages';
$paramsModel = new Parametre();
$appParams = $paramsModel->getPersonnalisation();
$devise = $appParams['devise'] ?? 'CDF';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Retours d'emballages</h1>
        <p class="text-gray-500 dark:text-gray-400">Suivi des caisses vides retournées par les clients</p>
    </div>
    <?php if (can('emballages.gerer')): ?>
    <button onclick="openRetourModal()" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 8.959 8.959 0 01-9 9 9 9 0 01-9-9z"/>
        </svg>
        Nouveau retour
    </button>
    <?php endif; ?>
</div>

<!-- Liste des retours -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Produit</th>
                        <th class="text-right">Quantité (Caisses)</th>
                        <th>Réceptionné à</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retours)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">Aucun retour enregistré</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($retours as $r): 
                            $btlParCaisse = (int) ($r['bouteilles_par_caisses'] ?? 24);
                            if ($btlParCaisse <= 0) {
                                $btlParCaisse = 24;
                            }
                            $caisses = $r['quantite'] / $btlParCaisse;
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['date_retour'])) ?></td>
                            <td class="font-bold text-gray-900"><?= htmlspecialchars($r['client_nom']) ?></td>
                            <td><?= htmlspecialchars($r['produit_nom']) ?></td>
                            <td class="text-right">
                                <div class="text-lg font-black text-blue-700"><?= number_format($caisses, 1, '.', ' ') ?> cs</div>
                                <div class="text-[10px] text-gray-400 font-medium"><?= number_format($r['quantite']) ?> bouteilles</div>
                            </td>
                            <td><?= htmlspecialchars($r['emplacement_nom']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nouveau Retour -->
<div x-data="retourModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Enregistrer un retour de vides</h3>
            
            <form @submit.prevent="save">
                <div class="space-y-4">
                    <div>
                        <label class="label">Client *</label>
                        <select x-model="form.client_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Produit *</label>
                        <select x-model="form.produit_id" class="input" @change="updateProduit()" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($produits as $p): ?>
                            <option value="<?= $p['id'] ?>" data-bouteilles="<?= (int) ($p['bouteilles_par_caisses'] ?? 24) ?>">
                                <?= htmlspecialchars($p['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1" x-show="bouteillesParCaisse > 0">
                            1 caisse = <span x-text="bouteillesParCaisse"></span> bouteilles
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Caisses *</label>
                            <input type="number" x-model.number="form.caisses" @input="syncQuantite()" class="input" min="0.01" step="0.01" required>
                        </div>
                        <div>
                            <label class="label">Emplacement *</label>
                            <select x-model="form.emplacement_id" class="input" required>
                                <?php foreach ($emplacements as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Valider le retour</span>
                        <span x-show="loading">Enregistrement...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('retourModal', () => ({
        isOpen: false,
        loading: false,
        bouteillesParCaisse: 24,
        form: {
            client_id: '',
            produit_id: '',
            caisses: 1,
            emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>'
        },
        
        open() { this.isOpen = true; },
        close() { this.isOpen = false; },

        updateProduit() {
            const select = this.$root.querySelector('select[x-model="form.produit_id"]');
            const option = select?.options[select.selectedIndex];
            const bouteilles = parseInt(option?.dataset?.bouteilles || '24', 10);
            this.bouteillesParCaisse = bouteilles > 0 ? bouteilles : 24;
            this.syncQuantite();
        },

        syncQuantite() {
            const caisses = parseFloat(this.form.caisses || 0);
            this.form.quantite = Math.round(caisses * this.bouteillesParCaisse);
        },
        
        async save() {
            this.loading = true;
            try {
                // Conversion caisses -> bouteilles selon le produit sélectionné
                const data = {
                    client_id: this.form.client_id,
                    produit_id: this.form.produit_id,
                    quantite: Math.round(this.form.caisses * this.bouteillesParCaisse),
                    emplacement_id: this.form.emplacement_id
                };

                await App.api('/api/retours-emballages', 'POST', data);
                App.notify('Retour enregistré et stock mis à jour', 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openRetourModal() {
    Alpine.evaluate(document.querySelector('[x-data="retourModal"]'), 'open()');
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
