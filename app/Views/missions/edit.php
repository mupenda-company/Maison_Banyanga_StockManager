<?php 
$pageTitle = 'Modifier mission';
$isRestourne = (($mission['type_mission'] ?? 'vente') === 'ristourne');
$initialChargements = [];
$missionCaissesInitiales = 0;

foreach (($mission['chargements'] ?? []) as $chargement) {
    $stockDepartCaisses = (int) ($chargement['caisses_deja_dans_vehicule'] ?? 0);
    $quantiteCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
    $missionCaissesInitiales += $quantiteCaisses;
    $initialChargements[] = [
        'produit_id' => (string) ($chargement['produit_id'] ?? ''),
        'produit_nom' => $chargement['produit_nom'] ?? '',
        'produit_code' => $chargement['produit_code'] ?? '',
        'quantite_caisses' => max(0, $quantiteCaisses),
        'stock_depart_caisses' => (float) $stockDepartCaisses,
        'auto_vehicle_stock' => false,
    ];
}

if (empty($initialChargements)) {
    $initialChargements[] = [
        'produit_id' => '',
        'produit_nom' => '',
        'produit_code' => '',
        'quantite_caisses' => 0,
        'stock_depart_caisses' => 0,
        'auto_vehicle_stock' => false,
    ];
}

ob_start();
?>

<div class="max-w-6xl mx-auto">
    <div class="card">
        <div class="card-header flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Modifier <?= $isRestourne ? 'une mission de ristourne' : 'une mission de vente' ?>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ajustez le véhicule, la date, les notes et les chargements avant validation.
                </p>
            </div>
            <a href="<?= url('missions/' . $mission['id']) ?>" class="btn btn-secondary">Retour</a>
        </div>
        <div class="card-body">
            <?php
            $alpineState = [
                'missionVehiculeId' => (int) ($mission['vehicule_id'] ?? 0),
                'missionCaissesInitiales' => (int) $missionCaissesInitiales,
                'vehicule_id' => (int) ($mission['vehicule_id'] ?? 0),
                'zone_id' => (string) ($mission['zone_id'] ?? ''),
                'date_depart' => date('Y-m-d\TH:i', strtotime($mission['date_depart'] ?? 'now')),
                'notes' => (string) ($mission['notes'] ?? ''),
                'chargements' => $initialChargements,
                'vehicules' => $vehicules,
                'produits' => $produits,
                'loading' => false,
                'loadingVehiculeStock' => false,
            ];
            ?>
            <script>
                window.missionEditInitialState = <?= json_encode($alpineState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                window.missionEditForm = function () {
                    return {
                        ...window.missionEditInitialState,
                        parseQty(value) {
                            const n = parseFloat(value);
                            return Number.isFinite(n) ? n : 0;
                        },
                        getProduit(produitId) {
                            return this.produits.find(p => String(p.id) === String(produitId)) || null;
                        },
                        getCurrentStockCaisses(chargement) {
                            const stockDepart = this.parseQty(chargement?.stock_depart_caisses);
                            if (stockDepart > 0) {
                                return stockDepart;
                            }

                            const produit = this.getProduit(chargement?.produit_id);
                            return produit ? this.parseQty(produit.stock_caisses_pleine) : 0;
                        },
                        getRequestedCaisses(chargement) {
                            return Math.max(0, Math.round(this.parseQty(chargement?.quantite_caisses)));
                        },
                        getFinalCaisses(chargement) {
                            return this.getRequestedCaisses(chargement);
                        },
                        getAdjustmentCaisses(chargement) {
                            return this.getFinalCaisses(chargement) - this.getCurrentStockCaisses(chargement);
                        },
                        getSelectedVehicule() {
                            return this.vehicules.find(v => String(v.id) === String(this.vehicule_id)) || null;
                        },
                        getSelectedVehiculeStockCaisses() {
                            const selectedVehicule = this.getSelectedVehicule();
                            if (!selectedVehicule) {
                                return 0;
                            }

                            return Math.round(parseFloat(selectedVehicule.stock_caisses_pleine || 0)) + Math.round(parseFloat(selectedVehicule.stock_caisses_vide || 0));
                        },
                        getTotalAdjustmentsCaisses() {
                            return this.chargements.reduce((total, chargement) => total + this.getAdjustmentCaisses(chargement), 0);
                        },
                        getVehiculeStockApresModificationCaisses() {
                            return Math.max(0, this.chargements.reduce((total, chargement) => total + this.getFinalCaisses(chargement), 0));
                        },
                        newChargement() {
                            return { produit_id: '', produit_nom: '', produit_code: '', quantite_caisses: 0, stock_depart_caisses: 0, auto_vehicle_stock: false };
                        },
                        async loadVehiculeStock() {
                            if (!this.vehicule_id) {
                                return;
                            }

                            this.loadingVehiculeStock = true;
                            try {
                                const response = await App.api('/api/vehicules/' + this.vehicule_id);
                                const vehicule = response.data || response;

                                this.vehicules = this.vehicules.map((v) => {
                                    if (String(v.id) !== String(this.vehicule_id)) {
                                        return v;
                                    }

                                    return {
                                        ...v,
                                        ...vehicule,
                                        stock_caisses_pleine: vehicule.stock_caisses_pleine ?? v.stock_caisses_pleine,
                                        stock_caisses_vide: vehicule.stock_caisses_vide ?? v.stock_caisses_vide,
                                    };
                                });
                            } catch (e) {
                                App.notify('Impossible de charger le stock du véhicule', 'error');
                            } finally {
                                this.loadingVehiculeStock = false;
                            }
                        },
                        addChargement() {
                            this.chargements.push(this.newChargement());
                        },
                        removeChargement(index) {
                            if (!this.chargements[index]?.auto_vehicle_stock || this.chargements.length > 1) {
                                this.chargements.splice(index, 1);
                            }
                            if (this.chargements.length === 0) {
                                this.chargements = [this.newChargement()];
                            }
                        },
                        async submit() {
                            this.loading = true;
                            try {
                                const chargementsValides = this.chargements.filter(c => c.produit_id && parseInt(c.quantite_caisses || 0) > 0).map(c => ({
                                    produit_id: parseInt(c.produit_id),
                                    quantite_caisses: parseInt(c.quantite_caisses || 0),
                                    stock_depart_caisses: parseInt(c.stock_depart_caisses || 0)
                                }));

                                const selectedVehicule = this.vehicules.find(v => String(v.id) === String(this.vehicule_id));
                                const capaciteVehicule = parseInt(selectedVehicule?.capacite || 0, 10);
                                const totalMissionCaisses = chargementsValides.reduce((total, ligne) => total + Math.max(0, parseInt(ligne.quantite_caisses || 0, 10)), 0);

                                if (capaciteVehicule > 0 && totalMissionCaisses > capaciteVehicule) {
                                    throw new Error(`La mission dépasse la capacité du véhicule. Capacité: ${capaciteVehicule} caisses, stock final demandé: ${totalMissionCaisses} caisses.`);
                                }

                                if (chargementsValides.length === 0) {
                                    throw new Error('Ajoutez au moins un produit avec une quantité finale supérieure à 0');
                                }

                                if (!this.vehicule_id) {
                                    throw new Error('Sélectionnez un véhicule');
                                }

                                await App.api('/api/missions/<?= (int) $mission['id'] ?>', 'PUT', {
                                    vehicule_id: parseInt(this.vehicule_id),
                                    zone_id: this.zone_id ? parseInt(this.zone_id) : null,
                                    date_depart: this.date_depart,
                                    notes: this.notes,
                                    chargements: chargementsValides
                                });

                                App.notify('Mission modifiée avec succès');
                                window.location.href = (window.BASE_URL || '') + '/missions/<?= (int) $mission['id'] ?>';
                            } catch (e) {
                                App.notify(e.message, 'error');
                            } finally {
                                this.loading = false;
                            }
                        }
                    };
                };
            </script>
            <form x-data="missionEditForm()" x-init="$nextTick(() => { vehicule_id = String(vehicule_id || ''); if (vehicule_id) { loadVehiculeStock(); } })" @submit.prevent="submit()">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">Véhicule *</label>
                        <select x-model="vehicule_id" @change="loadVehiculeStock()" class="input" required>
                            <option value="">Sélectionner un véhicule</option>
                            <template x-for="v in vehicules" :key="v.id">
                                <option :value="String(v.id)" :selected="String(v.id) === String(vehicule_id)" x-text="v.immatriculation + ' - ' + (v.agent_nom || 'Sans agent')"></option>
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

                <div class="mb-6 p-4 rounded-lg border border-blue-100 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Stock du véhicule sélectionné</p>
                        <button type="button" @click="loadVehiculeStock()" class="btn btn-sm btn-secondary" :disabled="loadingVehiculeStock || !vehicule_id">
                            <span x-show="!loadingVehiculeStock">Recharger stock du véhicule</span>
                            <span x-show="loadingVehiculeStock">Chargement...</span>
                        </button>
                    </div>
                    <template x-for="v in vehicules" :key="v.id + '-summary'">
                        <div x-show="String(v.id) === String(vehicule_id)" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700">
                                <p class="text-xs uppercase text-gray-500">Caisses pleines</p>
                                <p class="text-lg font-bold text-green-700" x-text="Math.round(parseFloat(v.stock_caisses_pleine || 0)) + ' cs'"></p>
                            </div>
                            <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700">
                                <p class="text-xs uppercase text-gray-500">Caisses vides</p>
                                <p class="text-lg font-bold text-gray-700" x-text="Math.round(parseFloat(v.stock_caisses_vide || 0)) + ' cs'"></p>
                            </div>
                            <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700">
                                <p class="text-xs uppercase text-gray-500">Stock final mission</p>
                                <p class="text-lg font-bold text-primary-700" x-text="Math.round(getVehiculeStockApresModificationCaisses()) + ' cs'"></p>
                            </div>
                        </div>
                    </template>
                </div>

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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock véhicule</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Quantité à charger</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(chargement, index) in chargements" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <template x-if="!chargement.auto_vehicle_stock">
                                                <select x-model="chargement.produit_id" x-init="$nextTick(() => { if (chargement.produit_id !== null && chargement.produit_id !== undefined && chargement.produit_id !== '') { $el.value = String(chargement.produit_id); } })" class="input w-full" required>
                                                    <option value="">Sélectionner un produit</option>
                                                    <template x-for="p in produits" :key="p.id">
                                                        <option :value="String(p.id)" x-text="p.nom + ' (' + p.code + ')' "></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="chargement.auto_vehicle_stock">
                                                <div class="input w-full bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between gap-3">
                                                    <span class="font-medium" x-text="chargement.produit_nom || 'Produit du véhicule'"></span>
                                                    <span class="text-xs text-gray-500" x-text="chargement.produit_code || ''"></span>
                                                </div>
                                                <p class="text-xs text-emerald-600 font-medium mt-1">Ligne issue du stock du véhicule</p>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <template x-if="chargement.auto_vehicle_stock">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-green-700" x-text="Math.round(getCurrentStockCaisses(chargement)) + ' cs'"></span>
                                                </div>
                                            </template>
                                            <template x-if="!chargement.auto_vehicle_stock">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-primary-600" x-text="Math.round(getCurrentStockCaisses(chargement)) + ' cs'"></span>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="chargement.quantite_caisses"
                                                   @input="chargement.quantite_caisses = Math.max(0, Math.round(chargement.quantite_caisses || 0));"
                                                   class="input w-28" min="0" step="1" placeholder="Caisses finales">
                                            <p class="text-xs text-gray-500 mt-2">
                                                Stock avant mission + ajustement = total final :
                                                <span class="font-semibold text-emerald-700"
                                                      x-text="Math.round(getCurrentStockCaisses(chargement)) + ' cs + ' + (getAdjustmentCaisses(chargement) > 0 ? '+' : '') + Math.round(getAdjustmentCaisses(chargement)) + ' cs = ' + Math.round(getFinalCaisses(chargement)) + ' cs'"></span>
                                            </p>
                                        </td>
                                        <td class="px-4 py-2">
                                            <button type="button" @click="removeChargement(index)" class="text-red-500 hover:text-red-700">
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

                <div class="mb-6">
                    <label class="label">Notes</label>
                    <textarea x-model="notes" class="input" rows="2" placeholder="Instructions particulières..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="<?= url('missions/' . $mission['id']) ?>" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-sm btn-primary" :disabled="loading">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-show="!loading">Enregistrer les modifications</span>
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
