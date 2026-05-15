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
                    chargements: [{ produit_id: '', quantite_caisses: 0, stock_depart_caisses: 0, auto_vehicle_stock: false }],
                    vehicules: [],
                    produits: [],
                    zones: [],
                    loading: false,
                    loadingVehiculeStock: false,
                    getProduit(produitId) {
                        return this.produits.find(p => String(p.id) === String(produitId)) || null;
                    },
                    newChargement() {
                        return { produit_id: '', quantite_caisses: 0, stock_depart_caisses: 0, auto_vehicle_stock: false };
                    },
                    async loadVehiculeStock() {
                        if (!this.vehicule_id) {
                            this.chargements = [this.newChargement()];
                            return;
                        }

                        this.loadingVehiculeStock = true;
                        try {
                            const response = await App.api('/api/vehicules/' + this.vehicule_id);
                            const vehicule = response.data || response;
                            const stockVehicule = Array.isArray(vehicule.stock) ? vehicule.stock : [];

                            this.chargements = stockVehicule
                                .filter((item) => parseFloat(item.caisses_pleine || 0) > 0 || parseFloat(item.quantite_pleine || 0) > 0)
                                .map((item) => ({
                                    produit_id: String(item.produit_id),
                                    produit_nom: item.produit_nom || '',
                                    produit_code: item.produit_code || '',
                                    quantite_caisses: parseFloat(item.caisses_pleine || 0),
                                    stock_depart_caisses: parseFloat(item.caisses_pleine || 0),
                                    auto_vehicle_stock: true
                                }));

                            if (this.chargements.length === 0) {
                                this.chargements = [this.newChargement()];
                            }
                        } catch (e) {
                            App.notify('Impossible de charger le stock du véhicule', 'error');
                            this.chargements = [this.newChargement()];
                        } finally {
                            this.loadingVehiculeStock = false;
                        }
                    },
                    addChargement() {
                        this.chargements.push(this.newChargement());
                    },
                    removeChargement(index) {
                        if (!this.chargements[index]?.auto_vehicle_stock) {
                            this.chargements.splice(index, 1);
                        }
                    }
                }"
                x-init="
                    App.api('/api/vehicules?disponibles=true').then(r => { vehicules = r.data || r; });
                    App.api('/api/produits?actifs=true&with_stock=true').then(r => { produits = r.data || r; });
                "
                @submit.prevent="async () => {
                    loading = true;
                    try {
                        const chargementsValides = chargements.filter(c => c.produit_id && (
                            parseInt(c.quantite_caisses || 0) > 0 ||
                            parseFloat(c.stock_depart_caisses || 0) > 0
                        )).map(c => ({
                            produit_id: parseInt(c.produit_id),
                            quantite_caisses: parseInt(c.quantite_caisses || 0),
                            stock_depart_caisses: parseInt(c.stock_depart_caisses || 0)
                        }));

                        const selectedVehicule = vehicules.find(v => String(v.id) === String(vehicule_id));
                        const capaciteVehicule = parseInt(selectedVehicule?.capacite || 0);
                        const totalMissionCaisses = chargementsValides.reduce((total, ligne) => total + Math.max(0, parseInt(ligne.quantite_caisses || 0)), 0);

                        if (capaciteVehicule > 0 && totalMissionCaisses > capaciteVehicule) {
                            throw new Error(`La mission dépasse la capacité du véhicule. Capacité: ${capaciteVehicule} caisses, stock final demandé: ${totalMissionCaisses} caisses.`);
                        }
                        
                        if (chargementsValides.length === 0) {
                            throw new Error('Ajoutez au moins un produit présent dans le véhicule ou une quantité à charger');
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
                        <select x-model="vehicule_id" @change="loadVehiculeStock()" class="input" required>
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

                <template x-if="vehicule_id">
                    <div class="mb-6 p-4 rounded-lg border border-blue-100 bg-blue-50">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Stock du véhicule sélectionné</p>
                            <p class="text-xs text-gray-500" x-show="loadingVehiculeStock">Chargement...</p>
                        </div>
                        <template x-for="v in vehicules" :key="v.id + '-summary'">
                            <div x-show="String(v.id) === String(vehicule_id)" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div class="p-3 bg-white rounded-lg border">
                                    <p class="text-xs uppercase text-gray-500">Caisses pleines déjà dans le véhicule</p>
                                    <p class="text-lg font-bold text-green-700" x-text="Math.round(parseFloat(v.stock_caisses_pleine || 0)) + ' cs'"></p>
                                </div>
                                <div class="p-3 bg-white rounded-lg border">
                                    <p class="text-xs uppercase text-gray-500">Caisses vides déjà dans le véhicule</p>
                                    <p class="text-lg font-bold text-gray-700" x-text="Math.round(parseFloat(v.stock_caisses_vide || 0)) + ' cs'"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                
                <!-- Chargement -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="label mb-0">Chargement des produits</label>
                        <button type="button" @click="addChargement()" class="btn-secondary btn-sm">
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses finales</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(chargement, index) in chargements" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="space-y-1">
                                                <template x-if="chargement.auto_vehicle_stock">
                                                    <div>
                                                        <div class="input w-full bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between gap-3">
                                                            <span class="font-medium" x-text="chargement.produit_nom || 'Produit du véhicule'"></span>
                                                            <span class="text-xs text-gray-500" x-text="chargement.produit_code || ''"></span>
                                                        </div>
                                                        <p class="text-xs text-emerald-600 font-medium mt-1">Ligne issue du stock du véhicule</p>
                                                    </div>
                                                </template>
                                                <template x-if="!chargement.auto_vehicle_stock">
                                                    <select x-model="chargement.produit_id" class="input w-full" required>
                                                        <option value="">Sélectionner un produit</option>
                                                        <template x-for="p in produits" :key="p.id">
                                                            <option :value="p.id" x-text="p.nom + ' (' + p.code + ')' "></option>
                                                        </template>
                                                    </select>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <template x-if="chargement.auto_vehicle_stock">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-green-700" x-text="Math.round(parseFloat(chargement.stock_depart_caisses || 0)) + ' cs'"></span>
                                                </div>
                                            </template>
                                            <template x-if="!chargement.auto_vehicle_stock">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-primary-600" x-text="Math.round(parseFloat(getProduit(chargement.produit_id).stock_caisses_pleine || 0)) + ' cs'"></span>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="chargement.quantite_caisses"
                                                   @input="chargement.quantite_caisses = Math.max(0, Math.round(chargement.quantite_caisses || 0));"
                                                   class="input w-28" min="0" step="1" placeholder="Caisses finales">
                                        </td>
                                        <td class="px-4 py-2">
                                            <button type="button" @click="chargements.splice(index, 1)" class="text-red-500 hover:text-red-700" x-show="!chargement.auto_vehicle_stock && chargements.length > 1">
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
