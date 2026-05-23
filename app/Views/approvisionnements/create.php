<?php 
$pageTitle = 'Nouvel approvisionnement';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <script>
        function getPrixCaisse(produit, typeAchat) {
            if (!produit) return 0;
            // Now `prix_achat_enlever` and `prix_achat_deposer` are stored per case.
            if (typeAchat === 'enlever' && produit.prix_achat_enlever > 0) {
                return produit.prix_achat_enlever;
            }
            if (typeAchat === 'deposer' && produit.prix_achat_deposer > 0) {
                return produit.prix_achat_deposer;
            }
            // Fallback: if only unit price exists, compute case price
            return produit.prix_achat_caisse || (produit.prix_achat_unitaire * produit.bouteilles_par_caisses);
        }
    </script>
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nouvel approvisionnement Bralima</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                L'achat de pleins déduit automatiquement les caisses vides du stock. Si le vide est insuffisant, une dette d'emballage est créée.
            </p>
        </div>
        <div class="card-body">
            <form 
                x-data="{
                    date: '<?= date('Y-m-d') ?>',
                    fournisseur: 'Bralima',
                    notes: '',
                    lignes: [{ produit_id: '', quantite_caisses: 0, type_achat: 'deposer' }],
                    produits: [],
                    loading: false,
                    total: 0
                }"
                x-init="
                    App.api('/api/produits').then(r => { 
                        produits = Array.isArray(r) ? r : (r.data || []);
                        if (produits.length === 0) {
                            App.notify('Attention: Aucun produit actif trouvé. Créez des produits d\'abord.', 'warning');
                        }
                    }).catch(e => {
                        App.notify('Erreur lors du chargement des produits', 'error');
                    });
                    $watch('lignes', () => {
                        total = 0;
                        lignes.forEach(l => {
                            const p = produits.find(p => p.id == l.produit_id);
                            if (p) {
                                const prixCaisse = getPrixCaisse(p, l.type_achat);
                                total += l.quantite_caisses * prixCaisse;
                            }
                        });
                    })
                "
                @submit.prevent="async () => {
                    loading = true;
                    try {
                        const details = lignes.filter(l => l.produit_id && l.quantite_caisses > 0).map(l => {
                            const p = produits.find(p => p.id == l.produit_id);
                            return {
                                produit_id: parseInt(l.produit_id),
                                quantite_caisses: parseInt(l.quantite_caisses),
                                type_achat: l.type_achat
                            };
                        });
                        
                        if (details.length === 0) {
                            throw new Error('Ajoutez au moins un produit');
                        }
                        
                        await App.api('/api/approvisionnements', 'POST', {
                            date_approvisionnement: date,
                            fournisseur: fournisseur,
                            notes: notes,
                            details: details
                        });
                        
                        App.notify('Approvisionnement enregistré avec succès');
                        window.location.href = '/approvisionnements';
                    } catch (e) {
                        App.notify(e.message, 'error');
                    } finally {
                        loading = false;
                    }
                }"
            >
                <!-- Informations générales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">Date *</label>
                        <input type="date" x-model="date" class="input" required>
                    </div>
                    <div>
                        <label class="label">Fournisseur</label>
                        <input type="text" x-model="fournisseur" class="input" placeholder="Bralima">
                    </div>
                    <div>
                        <label class="label">N° Bon</label>
                        <input type="text" value="<?= htmlspecialchars($numero_bon) ?>" class="input bg-gray-50" readonly>
                    </div>
                </div>
                
                <!-- Lignes de produits -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="label mb-0">Produits</label>
                        <button 
                            type="button"
                            @click="lignes.push({ produit_id: '', quantite_caisses: 0, type_achat: 'deposer' })"
                            class="btn-secondary btn-sm"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter une ligne
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Btl/Caisse</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type d'achat</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix Caisse</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nb Caisses</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sous-total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(ligne, index) in lignes" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <select x-model="ligne.produit_id" class="input w-full" required>
                                                <option value="">Sélectionner</option>
                                                <template x-for="p in produits" :key="p.id">
                                                    <option :value="p.id" x-text="p.nom + ' (' + p.code + ')'"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="produits.find(p => p.id == ligne.produit_id)?.bouteilles_par_caisses || '-'"></span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <select x-model="ligne.type_achat" class="input w-28" @change="">
                                                <option value="deposer">Déposer</option>
                                                <option value="enlever">Enlever</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="App.formatMoneyConverted(getPrixCaisse(produits.find(p => p.id == ligne.produit_id), ligne.type_achat) || 0, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.quantite_caisses" class="input w-24" min="1" required>
                                        </td>
                                        <td class="px-4 py-2 text-sm font-medium">
                                            <span x-text="App.formatMoneyConverted((ligne.quantite_caisses || 0) * getPrixCaisse(produits.find(p => p.id == ligne.produit_id), ligne.type_achat) || 0, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
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
                                        <span x-text="App.formatMoneyConverted(total, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
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
                    <a href="<?= url('approvisionnements') ?>" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span x-show="!loading">Enregistrer</span>
                        <span x-show="loading">Enregistrement...</span>
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
