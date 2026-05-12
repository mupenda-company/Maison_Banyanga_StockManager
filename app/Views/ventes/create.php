<?php 
$pageTitle = 'Nouvelle vente';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Enregistrer une vente</h2>
        </div>
        <div class="card-body">
            <form 
                x-data="venteForm()"
                @submit.prevent="saveVente"
            >
                <!-- Informations générales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">Client *</label>
                        <input type="search" x-model="clientSearch" class="input mb-2" placeholder="Rechercher un client...">
                        <select x-model="client_id" class="input" required>
                            <option value="">Sélectionner un client</option>
                            <template x-for="c in filteredClients()" :key="c.id">
                                <option :value="c.id" x-text="c.nom + (c.zone_nom ? ' (' + c.zone_nom + ')' : '')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Point de vente *</label>
                        <select x-model="emplacement_id" class="input" required>
                            <?php foreach ($emplacements as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">N° Facture</label>
                        <input type="text" value="<?= htmlspecialchars($numero_facture) ?>" class="input bg-gray-50" readonly>
                    </div>
                </div>
                
                <!-- Lignes de produits -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="label mb-0">Produits</label>
                        <button 
                            type="button"
                            @click="lignes.push({ produit_id: '', caisses: 0, caisses_vides_recues: null, prix_caisse: 0 })"
                            class="btn-secondary btn-sm"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock (cs)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix/Caisse</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Emballages reçus</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sous-total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(ligne, index) in lignes" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <select x-model="ligne.produit_id" class="input w-full" @change="onProduitChange(ligne)" required>
                                                <option value="">Sélectionner</option>
                                                <template x-for="p in produits" :key="p.id">
                                                    <option :value="p.id" x-text="p.nom"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <span x-text="ligne.produit_id ? Math.round((produits.find(p => p.id == ligne.produit_id)?.stock_plein || 0) / (produits.find(p => p.id == ligne.produit_id)?.bouteilles_par_caisses || 24)) : '0'"></span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.prix_caisse" class="input w-32" step="0.01" min="0" @input="calculateTotals()">
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.caisses" class="input w-24" min="1" step="1" required @input="ligne.caisses = Math.round(ligne.caisses || 0); calculateTotals()">
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.caisses_vides_recues" class="input w-28" min="0" step="1" required placeholder="0" @input="ligne.caisses_vides_recues = ligne.caisses_vides_recues === null || ligne.caisses_vides_recues === '' ? null : Math.max(0, Math.round(ligne.caisses_vides_recues || 0)); if (ligne.caisses && ligne.caisses_vides_recues !== null && ligne.caisses_vides_recues > ligne.caisses) ligne.caisses_vides_recues = ligne.caisses">
                                            <p class="text-[10px] text-gray-500 mt-1" x-show="ligne.produit_id && ligne.caisses > 0">
                                                Dette: <span x-text="Math.max(0, (Math.round(ligne.caisses || 0) - Math.round(ligne.caisses_vides_recues || 0))) + ' cs'"></span>
                                            </p>
                                            <p class="text-[10px] text-amber-600 mt-1">Indiquez explicitement <span class="font-medium">0</span> si aucun emballage vide n’a été reçu.</p>
                                        </td>
                                        <td class="px-4 py-2 text-sm font-medium">
                                            <span x-text="App.formatMoney((ligne.caisses * (ligne.prix_caisse || 0)), (window.DEVISE || 'CDF'))"></span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <button 
                                                type="button"
                                                @click="lignes.splice(index, 1)"
                                                class="text-red-500 hover:text-red-700"
                                                x-show="lignes.length > 1"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                        Total HT:
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                        <span x-text="App.formatMoney(totalHt, (window.DEVISE || 'CDF'))"></span>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        TVA (<?= $tva ?>%):
                                    </td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">
                                        <span x-text="App.formatMoney(totalTva, (window.DEVISE || 'CDF'))"></span>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr class="bg-primary-50 dark:bg-primary-900/50">
                                    <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                        Total TTC:
                                    </td>
                                    <td class="px-4 py-3 font-bold text-primary-600 dark:text-primary-400 text-lg">
                                        <span x-text="App.formatMoney(totalTtc, (window.DEVISE || 'CDF'))"></span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="mb-6">
                    <label class="label">Notes</label>
                    <textarea x-model="notes" class="input" rows="2" placeholder="Observations..."></textarea>
                </div>
                
                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="<?= url('ventes') ?>" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span x-show="!loading">Enregistrer la vente</span>
                        <span x-show="loading">Enregistrement...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('venteForm', () => ({
        client_id: '',
        clientSearch: '',
        emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>',
        notes: '',
        lignes: [{ produit_id: '', caisses: 0, caisses_vides_recues: null, prix_caisse: 0 }],
        clients: <?= json_encode($clients) ?>,
        produits: <?= json_encode($produits) ?>,
        tva: <?= $tva ?>,
        loading: false,
        totalHt: 0,
        totalTva: 0,
        totalTtc: 0,

        init() {
            this.calculateTotals();
            this.$watch('lignes', () => {
                this.calculateTotals();
            }, { deep: true });
        },

        hasMissingEmballagesRecus() {
            return (this.lignes || []).some((ligne) => {
                if (!ligne.produit_id || (Math.round(ligne.caisses || 0) <= 0)) {
                    return false;
                }

                return ligne.caisses_vides_recues === null
                    || ligne.caisses_vides_recues === undefined
                    || String(ligne.caisses_vides_recues).trim() === '';
            });
        },

        allEmballagesRecusZero() {
            const lignesValides = (this.lignes || []).filter((ligne) => ligne.produit_id && Math.round(ligne.caisses || 0) > 0);
            return lignesValides.length > 0 && lignesValides.every((ligne) => Math.max(0, Math.round(ligne.caisses_vides_recues || 0)) === 0);
        },

        filteredClients() {
            const term = (this.clientSearch || '').trim().toLowerCase();
            const allClients = Array.isArray(this.clients) ? this.clients : [];

            if (!term) {
                return allClients;
            }

            const filtered = allClients.filter((client) => {
                const haystack = [client.nom, client.telephone, client.zone_nom, client.email, client.adresse]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();
                return haystack.includes(term);
            });

            const selected = allClients.find((client) => client.id == this.client_id);
            if (selected && !filtered.some((client) => client.id == selected.id)) {
                filtered.unshift(selected);
            }

            return filtered;
        },

        onProduitChange(ligne) {
            const devise = window.DEVISE || 'CDF';
            const baseDevise = window.BASE_DEVISE || 'CDF';
            const p = this.produits.find(p => p.id == ligne.produit_id);
            const baseAmount = parseFloat(p?.prix_vente_caisses || 0);
            ligne.prix_caisse = App.convertMoney(baseAmount, baseDevise, devise);
            this.calculateTotals();
        },

        calculateTotals() {
            let ht = 0;
            this.lignes.forEach(l => {
                const q = parseFloat(l.caisses) || 0;
                const p = parseFloat(l.prix_caisse) || 0;
                if (l.produit_id && q > 0) {
                    ht += q * p;
                }
            });
            this.totalHt = ht;
            this.totalTva = this.totalHt * (this.tva / 100);
            this.totalTtc = this.totalHt + this.totalTva;
        },

        async saveVente() {
            this.loading = true;
            try {
                if (!this.client_id) throw new Error('Sélectionnez un client');

                const detailsLignes = this.lignes.filter(l => l.produit_id && l.caisses > 0);
                if (detailsLignes.length === 0) throw new Error('Ajoutez au moins un produit');

                if (this.hasMissingEmballagesRecus()) {
                    throw new Error('Veuillez renseigner les emballages reçus pour chaque ligne de vente. Indiquez 0 si aucun emballage vide n’a été récupéré.');
                }

                if (this.allEmballagesRecusZero() && !window.confirm('Aucun emballage vide n’a été déclaré pour cette vente. Confirmez-vous cette saisie ?')) {
                    return;
                }

                const details = detailsLignes.map(l => {
                    const p = this.produits.find(p => p.id == l.produit_id);
                    const btlParCaisse = parseInt(p.bouteilles_par_caisses) || 24;
                    const devise = window.DEVISE || 'CDF';
                    const baseDevise = window.BASE_DEVISE || 'CDF';
                    const prixCaisseDevise = (parseFloat(l.prix_caisse) || 0);
                    const prixCaisseBase = App.convertMoney(prixCaisseDevise, devise, baseDevise);
                    const caisses = Math.max(0, Math.round(parseFloat(l.caisses) || 0));
                    const caissesVidesRecues = Math.max(0, Math.min(caisses, Math.round(parseFloat(l.caisses_vides_recues) || 0)));
                    return {
                        produit_id: parseInt(l.produit_id),
                        quantite: caisses * btlParCaisse,
                        quantite_caisses: caisses,
                        caisses_vides_recues: caissesVidesRecues,
                        prix_unitaire: prixCaisseBase / btlParCaisse
                    };
                });
                
                await App.api('/api/ventes', 'POST', {
                    client_id: parseInt(this.client_id),
                    emplacement_id: parseInt(this.emplacement_id),
                    notes: this.notes,
                    details: details
                });
                
                App.notify('Vente enregistrée avec succès', 'success');
                setTimeout(() => window.location.href = '<?= url('ventes') ?>', 1000);
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
