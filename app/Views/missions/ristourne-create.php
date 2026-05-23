<?php 
$pageTitle = 'Nouvelle mission de ristourne';
ob_start();
?>

<div class="max-w-6xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Créer une mission de ristourne</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Livrer les ristournes de tous les clients en une seule mission. Sélectionnez les ristournes à livrer et le produit pour chacune.
            </p>
        </div>
        <div class="card-body">
            <form
                x-data="{
                    numeroMission: '<?= htmlspecialchars($numero_mission, ENT_QUOTES, 'UTF-8') ?>',
                    vehicule_id: '',
                    zone_id: '',
                    date_depart: '<?= date('Y-m-d\TH:i') ?>',
                    notes: '',
                    loading: false,
                    selectAll: false,
                    vehicules: <?= htmlspecialchars(json_encode($vehicules ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    ristournes: <?= htmlspecialchars(json_encode($ristournes ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    produits: <?= htmlspecialchars(json_encode($produits ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    zones: <?= htmlspecialchars(json_encode($zones ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    globalProduit: '',
                    selected: {},
                    produitParRistourne: {},
                    proposalMontant: {},
                    init() {
                        this.ristournes.forEach(r => {
                            this.selected[r.id] = false;
                            this.produitParRistourne[r.id] = '';
                            this.proposalMontant[r.id] = 0;
                        });
                    },
                    toggleAll() {
                        const val = this.selectAll;
                        this.ristournes.forEach(r => {
                            this.selected[r.id] = val;
                        });
                        // Propager produit global aux sélectionnés
                        this.applyGlobalToSelected();
                    },
                    getSelectedRistournes() {
                        return this.ristournes.filter(r => this.selected[r.id]);
                    },
                    getPrixCaisse(produitId) {
                        const p = this.produits.find(item => String(item.id) === String(produitId));
                        if (!p) return 0;
                        const bouteilles = parseInt(p.bouteilles_par_caisses || 24, 10) || 24;
                        const prixCaisse = parseFloat(p.prix_vente_caisses || 0);
                        if (prixCaisse > 0) return prixCaisse;
                        return (parseFloat(p.prix_vente_unitaire || 0) || 0) * bouteilles;
                    },
                    getCaissesLivrables(ristourneId) {
                        const r = this.ristournes.find(item => item.id === ristourneId);
                        if (!r) return 0;
                        const produitId = this.produitParRistourne[ristourneId];
                        if (!produitId) return 0;
                        const prix = this.getPrixCaisse(produitId);
                        const montant = parseFloat(r.montant_ristourne || 0);
                        if (prix <= 0) return 0;
                        return Math.floor(montant / prix);
                    },
                    getManquePourAtteindre(ristourneId) {
                        const r = this.ristournes.find(item => item.id === ristourneId);
                        if (!r) return 0;
                        const montant = parseFloat(r.montant_ristourne || 0);
                        const produitId = this.produitParRistourne[ristourneId];
                        if (!produitId) return 0;
                        const prix = this.getPrixCaisse(produitId) || 0;
                        if (prix <= 0) return 0;
                        const caisses = this.getCaissesLivrables(ristourneId);
                        const besoin = Math.max(prix * (caisses + 1) - montant, 0);
                        return besoin;
                    },
                    anyManqueSelected() {
                        return this.getSelectedRistournes().some(r => this.getManquePourAtteindre(r.id) > 0);
                    },
                    getProduitName(produitId) {
                        if (!produitId) return '';
                        const p = this.produits.find(item => String(item.id) === String(produitId));
                        return p ? (p.nom + (p.code ? ' (' + p.code + ')' : '')) : '';
                    },
                    applyGlobalToSelected() {
                        if (!this.globalProduit) return;
                        this.getSelectedRistournes().forEach(r => {
                            this.produitParRistourne[r.id] = this.globalProduit;
                        });
                    },
                    applyGlobalProduit() {
                        if (!this.globalProduit) return;
                        this.getSelectedRistournes().forEach(r => {
                            this.produitParRistourne[r.id] = this.globalProduit;
                        });
                    },
                    proposeMontant(ristourneId) {
                        const manque = this.getManquePourAtteindre(ristourneId) || 0;
                        this.proposalMontant[ristourneId] = Math.max(0, parseFloat(manque.toFixed ? manque.toFixed(2) : manque));
                    },
                    getTotalMontantRistourne() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + parseFloat(r.montant_ristourne || 0), 0);
                    },
                    getTotalCaisses() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + this.getCaissesLivrables(r.id), 0);
                    },
                    getTotalMontantLivre() {
                        return this.getSelectedRistournes().reduce((sum, r) => {
                            return sum + this.getCaissesLivrables(r.id) * this.getPrixCaisse(this.produitParRistourne[r.id]);
                        }, 0);
                    },
                    
                    proposeAllMissing() {
                        this.getSelectedRistournes().forEach(r => {
                            if (this.getManquePourAtteindre(r.id) > 0) this.proposeMontant(r.id);
                        });
                    },
                    async submit() {
                        if (!this.vehicule_id) {
                            App.notify('Sélectionnez un véhicule', 'error');
                            return;
                        }
                        // Ensure global product is applied to selected ristournes before validation
                        this.applyGlobalToSelected();
                        const selectedRistournes = this.getSelectedRistournes();
                        if (selectedRistournes.length === 0) {
                            App.notify('Sélectionnez au moins une ristourne avec un produit', 'error');
                            return;
                        }
                        // Vérifier que chaque sélectionnée a un produit
                        const sansProduit = this.ristournes.filter(r => this.selected[r.id] && !this.produitParRistourne[r.id]);
                        if (sansProduit.length > 0) {
                            App.notify('Chaque ristourne sélectionnée doit avoir un produit choisi', 'error');
                            return;
                        }

                        this.loading = true;
                        try {
                            const ristournesPayload = selectedRistournes.map(r => ({
                                ristourne_id: parseInt(r.id),
                                produit_id: parseInt(this.produitParRistourne[r.id]),
                                proposition_montant: parseFloat(this.proposalMontant[r.id] || 0)
                            }));

                            await App.api('/api/missions/ristourne', 'POST', {
                                vehicule_id: parseInt(this.vehicule_id),
                                zone_id: this.zone_id ? parseInt(this.zone_id) : null,
                                date_depart: this.date_depart,
                                notes: this.notes,
                                ristournes: ristournesPayload
                            });

                            App.notify('Mission de ristourne créée avec succès (' + selectedRistournes.length + ' ristournes)');
                            window.location.href = (window.BASE_URL || '') + '/missions';
                        } catch (e) {
                            App.notify(e.message, 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
            >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">N° Mission</label>
                        <input type="text" class="input bg-gray-50" x-model="numeroMission" readonly>
                    </div>
                    <div>
                        <label class="label">Date de départ *</label>
                        <input type="datetime-local" class="input" x-model="date_depart" required>
                    </div>
                    <div>
                        <label class="label">Zone</label>
                        <select class="input" x-model="zone_id">
                            <option value="">Sélectionner</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="label">Véhicule *</label>
                    <select class="input" x-model="vehicule_id" required>
                        <option value="">Sélectionner un véhicule disponible</option>
                        <template x-for="vehicule in vehicules" :key="vehicule.id">
                            <option :value="vehicule.id" x-text="vehicule.immatriculation + ' - ' + (vehicule.agent_nom || 'Sans agent')"></option>
                        </template>
                    </select>
                </div>

                <!-- Produit global pour toutes les ristournes sélectionnées -->
                <div class="mb-6">
                    <label class="label">Produit à livrer pour les ristournes sélectionnées</label>
                    <div class="flex gap-2">
                        <select class="input" x-model="globalProduit" @change="applyGlobalToSelected()">
                            <option value="">Sélectionner un produit (optionnel)</option>
                            <template x-for="produit in produits" :key="produit.id">
                                <option :value="produit.id" x-text="produit.nom + ' (' + produit.code + ')'">
                                </option>
                            </template>
                        </select>
                        <button type="button" @click="applyGlobalProduit()" class="btn btn-secondary">Appliquer aux sélectionnés</button>
                    </div>
                </div>

                <!-- Résumé global -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 rounded-lg border bg-blue-50 border-blue-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Total ristournes</p>
                        <p class="text-xl font-bold text-blue-700" x-text="App.formatMoneyConverted(getTotalMontantRistourne(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-green-50 border-green-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Total caisses</p>
                        <p class="text-xl font-bold text-green-700" x-text="getTotalCaisses() + ' cs'"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-purple-50 border-purple-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Montant livré</p>
                        <p class="text-xl font-bold text-purple-700" x-text="App.formatMoneyConverted(getTotalMontantLivre(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    
                </div>

                <div x-show="anyManqueSelected()" class="mb-4 p-3 rounded border bg-amber-50 border-amber-200 flex items-center justify-between">
                    <div class="text-sm text-amber-800">Certaines ristournes sélectionnées ont un manque pour atteindre la caisse suivante. Vous pouvez proposer le montant manquant pour chaque ristourne.</div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="proposeAllMissing()" class="btn-secondary">Proposer tout</button>
                    </div>
                </div>

                <!-- Tableau des ristournes -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Ristournes disponibles</h3>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300">
                            Tout sélectionner
                        </label>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800">
                                    <th class="p-3 text-left w-10">
                                        <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300">
                                    </th>
                                    <th class="p-3 text-left">Client</th>
                                    <th class="p-3 text-left">Montant ristourne</th>
                                    <th class="p-3 text-left">Période</th>
                                    <th class="p-3 text-left">Produit à livrer</th>
                                    <th class="p-3 text-right">Caisses</th>
                                    <th class="p-3 text-right">Montant livré</th>
                                    <th class="p-3 text-right">Manque</th>
                                    <th class="p-3 text-right">Proposer</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="ristourne in ristournes" :key="ristourne.id">
                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="selected[ristourne.id] ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                        <td class="p-3">
                                            <input type="checkbox" x-model="selected[ristourne.id]" @change="if(globalProduit && selected[ristourne.id]) produitParRistourne[ristourne.id]=globalProduit" class="rounded border-gray-300">
                                        </td>
                                        <td class="p-3 font-medium" x-text="ristourne.client_nom + (ristourne.numero_client ? ' (' + ristourne.numero_client + ')' : '')"></td>
                                        <td class="p-3" x-text="App.formatMoneyConverted(parseFloat(ristourne.montant_ristourne || 0), window.BASE_DEVISE, window.DEVISE)"></td>
                                        <td class="p-3 text-xs text-gray-500" x-text="(ristourne.periode_debut || '') + ' → ' + (ristourne.periode_fin || '')"></td>
                                        <td class="p-3">
                                            <div x-text="produitParRistourne[ristourne.id] ? getProduitName(produitParRistourne[ristourne.id]) : (globalProduit ? getProduitName(globalProduit) : '—')"></div>
                                        </td>
                                        <td class="p-3 text-right font-semibold" x-text="selected[ristourne.id] && produitParRistourne[ristourne.id] ? getCaissesLivrables(ristourne.id) + ' cs' : '—'"></td>
                                        <td class="p-3 text-right" x-text="selected[ristourne.id] && produitParRistourne[ristourne.id] ? App.formatMoneyConverted(getCaissesLivrables(ristourne.id) * getPrixCaisse(produitParRistourne[ristourne.id]), window.BASE_DEVISE, window.DEVISE) : '—'"></td>
                                        <td class="p-3 text-right text-red-600" x-text="selected[ristourne.id] && produitParRistourne[ristourne.id] ? App.formatMoneyConverted(getManquePourAtteindre(ristourne.id), window.BASE_DEVISE, window.DEVISE) : '—'"></td>
                                        <td class="p-3 text-right">
                                            <template x-if="selected[ristourne.id] && produitParRistourne[ristourne.id]">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" @click="proposeMontant(ristourne.id)" class="btn-secondary btn-sm">Proposer</button>
                                                    <input type="number" step="0.01" class="input w-28" x-model.number="proposalMontant[ristourne.id]">
                                                </div>
                                            </template>
                                            <span x-show="!selected[ristourne.id] || !produitParRistourne[ristourne.id]">—</span>
                                        </td>
                                        
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <span x-text="getSelectedRistournes().length"></span> ristourne(s) sélectionnée(s) sur <span x-text="ristournes.length"></span>
                    </p>
                </div>

                <div class="mb-6">
                    <label class="label">Notes</label>
                    <textarea class="input" rows="3" x-model="notes" placeholder="Instructions particulières..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="<?= url('missions') ?>" class="btn btn-secondary">Annuler</a>
                    <button type="button" @click="submit()" class="btn btn-primary" :disabled="loading || getSelectedRistournes().length === 0">
                        <span x-show="!loading">Créer la mission (<span x-text="getSelectedRistournes().length"></span> ristournes)</span>
                        <span x-show="loading">Création...</span>
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
