<?php
$pageTitle = 'Nouvelle mission de ristourne';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Creer une mission de ristourne</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Selectionnez les clients a servir et les produits a envoyer dans le vehicule. Le client choisira son produit sur terrain.
            </p>
        </div>
        <div class="card-body">
            <form
                x-data="{
                    vehicule_id: '',
                    zone_id: '',
                    date_depart: '<?= date('Y-m-d\TH:i') ?>',
                    loading: false,
                    selectAll: false,
                    vehicules: <?= htmlspecialchars(json_encode($vehicules ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    ristournes: <?= htmlspecialchars(json_encode($ristournes ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    produits: <?= htmlspecialchars(json_encode($produits ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    zones: <?= htmlspecialchars(json_encode($zones ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    selected: {},
                    chargements: [],
                    chargementsVente: [],
                    init() {
                        this.ristournes.forEach(r => { this.selected[r.id] = false; });
                        this.chargements = this.produits.map(p => ({ produit_id: p.id, quantite_caisses: 0 }));
                        this.chargementsVente = this.produits.map(p => ({ produit_id: p.id, quantite_caisses: 0 }));
                    },
                    getVisibleRistournes() {
                        if (!this.zone_id) return [];
                        return this.ristournes.filter(r => String(r.zone_id || '') === String(this.zone_id));
                    },
                    onZoneChange() {
                        this.selectAll = false;
                        this.ristournes.forEach(r => {
                            if (String(r.zone_id || '') !== String(this.zone_id || '')) this.selected[r.id] = false;
                        });
                    },
                    toggleAll() {
                        this.getVisibleRistournes().forEach(r => { this.selected[r.id] = this.selectAll; });
                    },
                    getSelectedRistournes() {
                        return this.getVisibleRistournes().filter(r => this.selected[r.id]);
                    },
                    getProduit(produitId) {
                        return this.produits.find(item => String(item.id) === String(produitId)) || null;
                    },
                    getPrixCaisse(produit) {
                        const bouteilles = parseInt(produit.bouteilles_par_caisses || 24, 10) || 24;
                        const prixCaisse = parseFloat(produit.prix_vente_caisses || 0);
                        if (prixCaisse > 0) return prixCaisse;
                        return (parseFloat(produit.prix_vente_unitaire || 0) || 0) * bouteilles;
                    },
                    getChargementsValides(source = this.chargements, type = 'ristourne') {
                        return source
                            .map(c => ({ produit_id: parseInt(c.produit_id), quantite_caisses: parseInt(c.quantite_caisses || 0, 10) || 0, type_chargement: type }))
                            .filter(c => c.produit_id > 0 && c.quantite_caisses > 0);
                    },
                    getTotalMontantRistourne() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + parseFloat(r.montant_ristourne || 0), 0);
                    },
                    getTotalCaissesChargees(type = 'ristourne') {
                        const source = type === 'vente' ? this.chargementsVente : this.chargements;
                        return this.getChargementsValides(source, type).reduce((sum, c) => sum + c.quantite_caisses, 0);
                    },
                    getTotalValeurChargee() {
                        return this.getChargementsValides(this.chargements, 'ristourne').reduce((sum, c) => {
                            const p = this.getProduit(c.produit_id);
                            return sum + (p ? c.quantite_caisses * this.getPrixCaisse(p) : 0);
                        }, 0);
                    },
                    getStockProduit(produit) {
                        return parseInt(produit.caisses_pleine ?? produit.stock_caisses ?? produit.stock ?? 0, 10) || 0;
                    },
                    async submit() {
                        if (!this.vehicule_id) { App.notify('Selectionnez un vehicule', 'error'); return; }
                        if (!this.zone_id) { App.notify('Selectionnez une zone', 'error'); return; }
                        const selectedRistournes = this.getSelectedRistournes();
                        if (selectedRistournes.length === 0) { App.notify('Selectionnez au moins une ristourne', 'error'); return; }
                        const chargements = this.getChargementsValides();
                        if (chargements.length === 0) { App.notify('Selectionnez au moins un produit a envoyer', 'error'); return; }
                        const insuffisant = chargements.find(c => {
                            const p = this.getProduit(c.produit_id);
                            return p && this.getStockProduit(p) > 0 && c.quantite_caisses > this.getStockProduit(p);
                        });
                        if (insuffisant) {
                            const p = this.getProduit(insuffisant.produit_id);
                            App.notify('Stock insuffisant pour ' + (p?.nom || 'produit'), 'error');
                            return;
                        }

                        this.loading = true;
                        try {
                            await App.api('/api/missions/ristourne', 'POST', {
                                vehicule_id: parseInt(this.vehicule_id),
                                zone_id: parseInt(this.zone_id),
                                date_depart: this.date_depart,
                                ristournes: selectedRistournes.map(r => ({ ristourne_id: parseInt(r.id) })),
                                chargements: chargements
                            });

                            App.notify('Mission de ristourne creee avec succes (' + selectedRistournes.length + ' client(s))');
                            window.location.href = (window.BASE_URL || '') + '/missions';
                        } catch (e) {
                            App.notify(e.message, 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="label">Date de depart *</label>
                        <input type="datetime-local" class="input" x-model="date_depart" required>
                    </div>
                    <div>
                        <label class="label">Zone *</label>
                        <select class="input" x-model="zone_id" @change="onZoneChange()" required>
                            <option value="">Selectionner</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="label">Vehicule *</label>
                    <select class="input" x-model="vehicule_id" required>
                        <option value="">Selectionner un vehicule disponible</option>
                        <template x-for="vehicule in vehicules" :key="vehicule.id">
                            <option :value="vehicule.id" x-text="vehicule.immatriculation + ' - ' + (vehicule.agent_nom || 'Sans agent')"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                    <div class="p-4 rounded-lg border bg-blue-50 border-blue-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Clients ristourne</p>
                        <p class="text-xl font-bold text-blue-700" x-text="getSelectedRistournes().length"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-green-50 border-green-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Caisses envoyees</p>
                        <p class="text-xl font-bold text-green-700" x-text="getTotalCaissesChargees('ristourne') + ' cs ristourne / ' + getTotalCaissesChargees('vente') + ' cs vente'"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-amber-50 border-amber-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Total ristournes</p>
                        <p class="text-xl font-bold text-amber-700" x-text="App.formatMoneyConverted(getTotalMontantRistourne(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Produits a envoyer dans le vehicule</h3>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800">
                                    <th class="p-3 text-left">Produit</th>
                                    <th class="p-3 text-right">Prix caisse</th>
                                    <th class="p-3 text-right">Stock</th>
                                    <th class="p-3 text-right w-44">Caisses ristourne</th>
                                    <th class="p-3 text-right w-44">Caisses vente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(produit, index) in produits" :key="produit.id">
                                    <tr class="border-b">
                                        <td class="p-3">
                                            <div class="font-medium" x-text="produit.nom"></div>
                                            <div class="text-xs text-gray-500" x-text="produit.code || ''"></div>
                                        </td>
                                        <td class="p-3 text-right" x-text="App.formatMoneyConverted(getPrixCaisse(produit), window.BASE_DEVISE, window.DEVISE)"></td>
                                        <td class="p-3 text-right" x-text="getStockProduit(produit) + ' cs'"></td>
                                        <td class="p-3 text-right">
                                            <input type="number" min="0" step="1" class="input text-right" x-model.number="chargements[index].quantite_caisses">
                                        </td>
                                        <td class="p-3 text-right">
                                            <input type="number" min="0" step="1" class="input text-right" x-model.number="chargementsVente[index].quantite_caisses">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Les caisses ristourne servent uniquement aux livraisons de ristourne. Les caisses vente restent separees pour les ventes terrain.</p>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Clients avec ristourne disponible</h3>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300">
                            Tout selectionner
                        </label>
                    </div>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800">
                                    <th class="p-3 text-left w-10"></th>
                                    <th class="p-3 text-left">Client</th>
                                    <th class="p-3 text-right">Ristourne</th>
                                    <th class="p-3 text-left">Periode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="!zone_id">
                                    <tr><td colspan="4" class="p-6 text-center text-gray-500">Selectionnez une zone pour afficher les clients.</td></tr>
                                </template>
                                <template x-for="ristourne in getVisibleRistournes()" :key="ristourne.id">
                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="selected[ristourne.id] ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                        <td class="p-3"><input type="checkbox" x-model="selected[ristourne.id]" class="rounded border-gray-300"></td>
                                        <td class="p-3 font-medium">
                                            <div x-text="ristourne.client_nom + (ristourne.numero_client ? ' (' + ristourne.numero_client + ')' : '')"></div>
                                            <div class="text-xs text-gray-500" x-text="ristourne.zone_nom || ''"></div>
                                        </td>
                                        <td class="p-3 text-right font-semibold" x-text="App.formatMoneyConverted(parseFloat(ristourne.montant_ristourne || 0), window.BASE_DEVISE, window.DEVISE)"></td>
                                        <td class="p-3 text-gray-500" x-text="(ristourne.periode_debut || '') + ' -> ' + (ristourne.periode_fin || '')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <span x-text="getSelectedRistournes().length"></span> client(s) selectionne(s) sur <span x-text="getVisibleRistournes().length"></span>
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="<?= url('missions') ?>" class="btn btn-secondary">Annuler</a>
                    <button type="button" @click="submit()" class="btn btn-primary" :disabled="loading || getSelectedRistournes().length === 0">
                        <span x-show="!loading">Creer la mission</span>
                        <span x-show="loading">Creation...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
