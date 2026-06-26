<?php 
$pageTitle = 'Modifier une perte';
ob_start();

$quantiteCaisses = (float)($perte['quantite'] ?? 0);
$typeStock = $perte['type_stock'] ?? 'plein';
?>

<div class="mb-6">
    <a href="<?= url('pertes') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux pertes
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Modifier la perte #<?= (int)($perte['id'] ?? 0) ?></h2>
        </div>
        <div class="card-body">
            <form x-data="perteEditForm" @submit.prevent="save">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Produit *</label>
                        <select x-model="form.produit_id" @change="updatePrix" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($produits as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" 
                                    data-prix="<?= (float)($p['prix_vente_caisses'] ?? 0) ?>" 
                                    data-btl="<?= (int)($p['bouteilles_par_caisses'] ?? 24) ?>">
                                <?= htmlspecialchars($p['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Type de stock *</label>
                        <select x-model="form.type_stock" class="input" required @change="syncValeurProposee()">
                            <option value="plein">Stock PLEIN</option>
                            <option value="vide">Stock VIDE</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Emplacement *</label>
                        <select x-model="form.emplacement_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($emplacements as $e): ?>
                            <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Agent responsable *</label>
                        <select x-model="form.agent_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?= (int)$agent['id'] ?>"><?= htmlspecialchars(trim(($agent['prenom'] ?? '') . ' ' . ($agent['nom'] ?? ''))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Catégorie de perte *</label>
                        <select x-model="form.type_perte" class="input" required>
                            <option value="casse">Casse</option>
                            <option value="dommage">Dommage</option>
                            <option value="vol">Vol</option>
                            <option value="expiration">Expiration</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Quantité *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model="form.unite_perte" class="input" required>
                                <option value="caisse">Caisse</option>
                                <option value="bouteille">Bouteille</option>
                            </select>
                            <input type="number" x-model.number="form.quantite_saisie" class="input" min="0.01" step="0.01" required @input="syncValeurProposee()">
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1" x-text="quantiteConvertie"></p>
                    </div>
                    <div>
                        <label class="label">Date de la perte</label>
                        <input type="date" x-model="form.date_perte" class="input" required>
                    </div>
                    <div class="col-span-2">
                        <label class="label">Motif / Description</label>
                        <textarea x-model="form.motif" class="input" rows="2" placeholder="Décrivez les circonstances de la perte..."></textarea>
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="label">Montant de la perte *</label>
                    <input type="number" x-model.number="form.valeur_perte" class="input" min="0" step="0.01" required>
                    <p class="text-xs text-gray-500 mt-2">
                        Proposition: <span x-text="App.formatMoneyConverted(valeurProposee, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1 italic" x-show="form.type_stock === 'vide'">* La valeur des vides est souvent symbolique ou nulle.</p>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <a href="<?= url('pertes') ?>" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-danger" :disabled="loading">
                        <span x-show="!loading">Modifier la perte</span>
                        <span x-show="loading">Modification...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('perteEditForm', () => ({
        loading: false,
        form: {
            produit_id: '<?= (int)($perte['produit_id'] ?? 0) ?>',
            emplacement_id: '<?= (int)($perte['emplacement_id'] ?? 0) ?>',
            agent_id: '<?= (int)($perte['agent_id'] ?? 0) ?>',
            type_perte: <?= json_encode($perte['type_perte'] ?? 'casse') ?>,
            type_stock: <?= json_encode($typeStock) ?>,
            unite_perte: 'caisse',
            quantite_saisie: <?= json_encode($quantiteCaisses) ?>,
            date_perte: <?= json_encode(!empty($perte['date_perte']) ? date('Y-m-d', strtotime($perte['date_perte'])) : date('Y-m-d')) ?>,
            motif: <?= json_encode($perte['motif'] ?? '') ?>,
            valeur_perte: <?= json_encode((float)($perte['valeur_perte'] ?? 0)) ?>
        },
        prixCaisse: 0,
        btlParCaisse: <?= (int)($perte['bouteilles_par_caisses'] ?? 24) ?>,
        valeurInitiale: <?= json_encode((float)($perte['valeur_perte'] ?? 0)) ?>,

        init() {
            this.updatePrix(false);
        },
        
        get valeurProposee() {
            if (this.form.type_stock === 'vide') return 0;
            return this.caissesCalculees * (parseFloat(this.prixCaisse) || 0);
        },

        get caissesCalculees() {
            const qte = parseFloat(this.form.quantite_saisie) || 0;
            if (this.form.unite_perte === 'bouteille') {
                return qte / (parseInt(this.btlParCaisse) || 24);
            }
            return qte;
        },

        get quantiteConvertie() {
            const caisses = this.caissesCalculees;
            const rounded = Math.abs(caisses - Math.round(caisses)) < 0.0001 ? Math.round(caisses) : caisses.toFixed(2);
            return `${rounded} caisse(s) seront déduites du stock après restauration de l'ancien impact.`;
        },
        
        updatePrix(sync = true) {
            const select = document.querySelector('select[x-model="form.produit_id"]');
            if (!select || select.selectedIndex < 0) return;
            const option = select.options[select.selectedIndex];
            this.prixCaisse = parseFloat(option.dataset.prix || 0);
            this.btlParCaisse = parseInt(option.dataset.btl || 24);
            if (sync) this.syncValeurProposee();
        },

        syncValeurProposee() {
            if (!this.form.valeur_perte || parseFloat(this.form.valeur_perte) === 0) {
                this.form.valeur_perte = this.valeurProposee;
            }
        },
        
        async save() {
            const ok = await App.confirm({
                title: 'Modifier la perte ?',
                message: 'Cette modification va restaurer l’ancien stock puis appliquer le nouveau stock. Continuer ?',
                confirmText: 'Modifier',
                cancelText: 'Annuler',
                type: 'warning'
            });
            if (!ok) return;

            this.loading = true;
            try {
                const data = {
                    ...this.form,
                    quantite: this.form.quantite_saisie,
                    unite_perte: this.form.unite_perte,
                    valeur_perte: parseFloat(this.form.valeur_perte || 0)
                };
                
                await App.api('/api/pertes/<?= (int)($perte['id'] ?? 0) ?>', 'PUT', data);
                App.notify('Perte modifiée avec succès', 'success');
                setTimeout(() => location.href = '<?= url('pertes') ?>', 800);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
