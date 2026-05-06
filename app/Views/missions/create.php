<?php 
$pageTitle = 'Nouvelle mission';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Créer une mission de vente</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Chargement d'un véhicule pour une mission de vente mobile
            </p>
        </div>
        <div class="card-body">
            <form 
                x-data="{
                    vehicule_id: '',
                    zone_id: '',
                    date_depart: '<?= date('Y-m-d\TH:i') ?>',
                    notes: '',
                    chargements: [{ produit_id: '', quantite: 0 }],
                    vehicules: [],
                    produits: [],
                    zones: [],
                    loading: false
                }"
                x-init="
                    App.api('/api/vehicules?disponibles=true').then(r => { vehicules = r.data || r; });
                    App.api('/api/produits?actifs=true&with_stock=true').then(r => { produits = r.data || r; });
                "
                @submit.prevent="async () => {
                    loading = true;
                    try {
                        const chargementsValides = chargements.filter(c => c.produit_id && c.quantite > 0).map(c => ({
                            produit_id: parseInt(c.produit_id),
                            quantite_caisses: parseInt(c.quantite_caisses || 0),
                            quantite: parseInt(c.quantite)
                        }));
                        
                        if (chargementsValides.length === 0) {
                            throw new Error('Ajoutez au moins un produit');
                        }
                        
                        if (!vehicule_id) {
                            throw new Error('Sélectionnez un véhicule');
                        }
                        
                        await App.api('/api/missions', 'POST', {
                            vehicule_id: parseInt(vehicule_id),
                            zone_id: zone_id ? parseInt(zone_id) : null,
                            date_depart: date_depart,
                            notes: notes,
                            chargements: chargementsValides
                        });
                        
                        App.notify('Mission créée avec succès');
                        window.location.href = (window.BASE_URL || '') + '/missions';
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
                        <label class="label">Véhicule *</label>
                        <select x-model="vehicule_id" class="input" required>
                            <option value="">Sélectionner un véhicule disponible</option>
                            <template x-for="v in vehicules" :key="v.id">
                                <option :value="v.id" x-text="v.immatriculation + ' - ' + (v.agent_nom || 'Sans agent')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Zone de destination</label>
                        <select x-model="zone_id" class="input">
                            <option value="">Sélectionner</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Date de départ *</label>
                        <input type="datetime-local" x-model="date_depart" class="input" required>
                    </div>
                </div>
                
                <!-- Chargement -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="label mb-0">Chargement des produits</label>
                        <button type="button" @click="chargements.push({ produit_id: '', quantite: 0 })" class="btn-secondary btn-sm">
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock entrepôt</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Quantité à charger</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(chargement, index) in chargements" :key="index">
                                    <tr x-data="{ 
                                        get selectedProduit() { 
                                            return produits.find(p => p.id == chargement.produit_id) || null 
                                        }
                                    }">
                                        <td class="px-4 py-2">
                                            <select x-model="chargement.produit_id" class="input w-full" required>
                                                <option value="">Sélectionner un produit</option>
                                                <template x-for="p in produits" :key="p.id">
                                                    <option :value="p.id" x-text="p.nom + ' (' + p.code + ')'"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <template x-if="selectedProduit">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-primary-600" x-text="Math.round(parseFloat(selectedProduit.stock_caisses_pleine || 0)) + ' cs'"></span>
                                                    <span class="text-xs text-gray-400" x-text="'(' + selectedProduit.stock_plein + ' btl)'"></span>
                                                </div>
                                            </template>
                                            <template x-if="!selectedProduit">
                                                <span class="text-gray-400">-</span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center space-x-2">
                                                <input type="number" x-model.number="chargement.quantite_caisses" 
                                                       @input="if(selectedProduit) chargement.quantite = Math.round(chargement.quantite_caisses) * selectedProduit.bouteilles_par_caisses; chargement.quantite_caisses = Math.round(chargement.quantite_caisses || 0)"
                                                       class="input w-24" min="1" step="1" placeholder="Caisses">
                                                <span class="text-xs text-gray-500">=</span>
                                                <input type="number" x-model.number="chargement.quantite" 
                                                       @input="if(selectedProduit) { chargement.quantite = Math.round(chargement.quantite || 0); chargement.quantite_caisses = Math.round(chargement.quantite / selectedProduit.bouteilles_par_caisses) }"
                                                       class="input w-24" min="1" step="1" placeholder="Btl">
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <button type="button" @click="chargements.splice(index, 1)" class="text-red-500 hover:text-red-700" x-show="chargements.length > 1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="mb-6">
                    <label class="label">Notes</label>
                    <textarea x-model="notes" class="input" rows="2" placeholder="Instructions particulières..."></textarea>
                </div>
                
                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="<?= url('missions') ?>" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span x-show="!loading">Créer la mission</span>
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
