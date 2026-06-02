<?php 
$pageTitle = 'Déclarer une perte';
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

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Déclarer une perte</h2>
        </div>
        <div class="card-body">
            <form x-data="perteForm" @submit.prevent="save">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Produit *</label>
                        <select x-model="form.produit_id" @change="updatePrix" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($produits as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-prix="<?= $p['prix_vente_caisses'] ?>" 
                                    data-btl="<?= $p['bouteilles_par_caisses'] ?? 24 ?>">
                                <?= htmlspecialchars($p['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Type de Stock *</label>
                        <select x-model="form.type_stock" class="input" required>
                            <option value="plein">Stock PLEIN (Bouteilles pleines)</option>
                            <option value="vide">Stock VIDE (Bouteilles vides)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Emplacement *</label>
                        <select x-model="form.emplacement_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($emplacements as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Agent responsable *</label>
                        <select x-model="form.agent_id" class="input" required>
                            <option value="">Selectionner</option>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?= $agent['id'] ?>"><?= htmlspecialchars(trim(($agent['prenom'] ?? '') . ' ' . ($agent['nom'] ?? ''))) ?></option>
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
                        <label class="label">Quantité (Caisses) *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model="form.unite_perte" class="input" required>
                                <option value="caisse">Caisse</option>
                                <option value="bouteille">Bouteille</option>
                            </select>
                            <input type="number" x-model.number="form.quantite_saisie" class="input" min="0.01" step="0.01" required>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1" x-text="quantiteConvertie"></p>
                    </div>
                    <div>
                        <label class="label">Date de la perte</label>
                        <input type="date" x-model="form.date_perte" class="input">
                    </div>
                    <div class="col-span-2">
                        <label class="label">Motif / Description</label>
                        <textarea x-model="form.motif" class="input" rows="2" placeholder="Décrivez les circonstances de la perte..."></textarea>
                    </div>
                </div>
                
                <!-- Valeur de la perte -->
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
                        <span x-show="!loading">Enregistrer la perte</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('perteForm', () => ({
        loading: false,
        form: {
            produit_id: '',
            emplacement_id: '',
            agent_id: '',
            type_perte: 'casse',
            type_stock: 'plein',
            caisses: 1,
            unite_perte: 'caisse',
            quantite_saisie: 1,
            date_perte: new Date().toISOString().split('T')[0],
            motif: '',
            valeur_perte: 0
        },
        prixCaisse: 0,
        btlParCaisse: 24,
        
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
            return `${rounded} caisse(s) seront deduites du stock.`;
        },
        
        updatePrix() {
            const select = document.querySelector('select[x-model="form.produit_id"]');
            const option = select.options[select.selectedIndex];
            this.prixCaisse = parseFloat(option.dataset.prix || 0);
            this.btlParCaisse = parseInt(option.dataset.btl || 24);
            if (!this.form.valeur_perte || this.form.valeur_perte === 0) {
                this.form.valeur_perte = this.valeurProposee;
            }
        },
        
        async save() {
            this.loading = true;
            try {
                const data = {
                    ...this.form,
                    quantite: this.form.quantite_saisie,
                    unite_perte: this.form.unite_perte,
                    valeur_perte: parseFloat(this.form.valeur_perte || 0)
                };
                
                await App.api('/api/pertes', 'POST', data);
                App.notify('Perte enregistrée avec succès', 'success');
                setTimeout(() => location.href = '<?= url('pertes') ?>', 1000);
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




