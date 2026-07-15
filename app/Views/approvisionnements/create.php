<?php
$pageTitle = 'Nouvel approvisionnement';
ob_start();
$produitsApiUrl = url('api/produits');
$approvisionnementsApiUrl = url('api/approvisionnements');
$approvisionnementsUrl = url('approvisionnements');
?>

<div class="max-w-6xl mx-auto">
    <script>
        function getPrixCaisse(produit, typeAchat) {
            if (!produit) return 0;
            if (typeAchat === 'enlever' && produit.prix_achat_enlever > 0) {
                return parseFloat(produit.prix_achat_enlever);
            }
            if (typeAchat === 'deposer' && produit.prix_achat_deposer > 0) {
                return parseFloat(produit.prix_achat_deposer);
            }
            return parseFloat(produit.prix_achat_caisse || 0) || (parseFloat(produit.prix_achat_unitaire || 0) * parseInt(produit.bouteilles_par_caisses || 1));
        }

        function getQuantiteCaisses(ligne, produit) {
            const quantite = parseInt(ligne.quantite_achat || 0);
            if (!produit || quantite <= 0) return 0;
            if (ligne.unite_achat === 'palette') {
                return quantite * (parseInt(produit.caisses_par_palette || 0) || 0);
            }
            return quantite;
        }

        function getPrixLigne(ligne, produit, typeAchat) {
            if ((ligne.type_chargement || 'vente') === 'emballage') {
                return App.convertMoney(parseFloat(ligne.prix_emballage_usd || 0), 'USD', (window.BASE_DEVISE || 'CDF'));
            }
            return getPrixCaisse(produit, typeAchat);
        }
    </script>

    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nouvel approvisionnement BdGL</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Choisissez des produits pleins ou des emballages vides. Le prix des emballages est saisi en USD puis converti dans la devise de base du système.
            </p>
        </div>
        <div class="card-body">
            <form
                x-data="{
                    date: '<?= date('Y-m-d') ?>',
                    fournisseur: 'BdGL',
                    type_achat: 'enlever',
                    notes: '',
                    lignes: [{ produit_id: '', type_chargement: 'vente', quantite_achat: 0, unite_achat: 'caisse', prix_emballage_usd: '' }],
                    produits: [],
                    loading: false,
                    total: 0,
                    recalc() {
                        this.total = 0;
                        this.lignes.forEach(l => {
                            const p = this.produits.find(p => p.id == l.produit_id);
                            if (p) {
                                this.total += getQuantiteCaisses(l, p) * getPrixLigne(l, p, this.type_achat);
                            }
                        });
                    }
                }"
                x-init="
                    App.api('<?= $produitsApiUrl ?>').then(r => {
                        produits = Array.isArray(r) ? r : (r.data || []);
                        if (produits.length === 0) {
                            App.notify('Attention: aucun produit actif trouve.', 'warning');
                        }
                    }).catch(() => App.notify('Erreur lors du chargement des produits', 'error'));
                    $watch('lignes', () => recalc(), { deep: true });
                "
                @submit.prevent="async () => {
                    loading = true;
                    try {
                        const details = lignes.filter(l => {
                            const p = produits.find(p => p.id == l.produit_id);
                            return p && getQuantiteCaisses(l, p) > 0;
                        }).map(l => {
                            const p = produits.find(p => p.id == l.produit_id);
                            return {
                                produit_id: parseInt(l.produit_id),
                                quantite_caisses: getQuantiteCaisses(l, p),
                                type_chargement: l.type_chargement || 'vente',
                                prix_emballage_usd: (l.type_chargement || 'vente') === 'emballage' ? parseFloat(l.prix_emballage_usd || 0) : 0,
                                type_achat: type_achat
                            };
                        });

                        if (details.length === 0) {
                            throw new Error('Ajoutez au moins un produit');
                        }

                        await App.api('<?= $approvisionnementsApiUrl ?>', 'POST', {
                            date_approvisionnement: date,
                            fournisseur: fournisseur,
                            notes: notes,
                            details: details
                        });

                        App.notify('Approvisionnement enregistre avec succes');
                        window.location.href = '<?= $approvisionnementsUrl ?>';
                    } catch (e) {
                        App.notify(e.message, 'error');
                    } finally {
                        loading = false;
                    }
                }"
            >
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="label">Date *</label>
                        <input type="date" x-model="date" class="input" required>
                    </div>
                    <div>
                        <label class="label">Fournisseur</label>
                        <input type="text" x-model="fournisseur" class="input" placeholder="Bralima">
                    </div>
                    <div>
                        <label class="label">N Bon</label>
                        <input type="text" value="<?= htmlspecialchars($numero_bon) ?>" class="input bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="label">Prix d'achat *</label>
                        <select x-model="type_achat" class="input" @change="recalc()" required>
                            <option value="deposer">Prix achat a deposer</option>
                            <option value="enlever">Prix achat a enlever</option>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="label mb-0">Produits</label>
                        <button
                            type="button"
                            @click="lignes.push({ produit_id: '', type_chargement: 'vente', quantite_achat: 0, unite_achat: 'caisse', prix_emballage_usd: '' })"
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
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produit</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nature</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Btl/Caisse</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">NP</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Achat</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Unite</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix emballage USD/cs</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix caisse</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sous-total</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(ligne, index) in lignes" :key="index">
                                    <tr>
                                        <td class="px-3 py-2 min-w-64">
                                            <select x-model="ligne.produit_id" class="input w-full" required>
                                                <option value="">Selectionner</option>
                                                <template x-for="p in produits" :key="p.id">
                                                    <option :value="p.id" x-text="p.nom + ' (' + p.code + ')'"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2 min-w-40">
                                            <select x-model="ligne.type_chargement" class="input" @change="if (ligne.type_chargement === 'emballage') ligne.unite_achat = 'caisse'; recalc()">
                                                <option value="vente">Produits pleins</option>
                                                <option value="emballage">Emballages vides</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="produits.find(p => p.id == ligne.produit_id)?.bouteilles_par_caisses || '-'"></span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="produits.find(p => p.id == ligne.produit_id)?.caisses_par_palette || '-'"></span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" x-model.number="ligne.quantite_achat" class="input w-24" min="1" required>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select x-model="ligne.unite_achat" class="input w-28" :disabled="ligne.type_chargement === 'emballage'">
                                                <option value="caisse">Caisse</option>
                                                <option value="palette">Palette</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input x-show="ligne.type_chargement === 'emballage'" type="number" x-model.number="ligne.prix_emballage_usd"
                                                   class="input w-32" min="0.01" step="0.01" :required="ligne.type_chargement === 'emballage'"
                                                   :placeholder="App.convertMoney(parseFloat(produits.find(p => p.id == ligne.produit_id)?.prix_emballage || 0), (window.BASE_DEVISE || 'CDF'), 'USD').toFixed(2)">
                                            <span x-show="ligne.type_chargement !== 'emballage'">—</span>
                                        </td>
                                        <td class="px-3 py-2 text-sm font-semibold">
                                            <span x-text="getQuantiteCaisses(ligne, produits.find(p => p.id == ligne.produit_id))"></span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="App.formatMoneyConverted(getPrixLigne(ligne, produits.find(p => p.id == ligne.produit_id), type_achat) || 0, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
                                        </td>
                                        <td class="px-3 py-2 text-sm font-medium">
                                            <span x-text="App.formatMoneyConverted(getQuantiteCaisses(ligne, produits.find(p => p.id == ligne.produit_id)) * getPrixLigne(ligne, produits.find(p => p.id == ligne.produit_id), type_achat) || 0, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
                                        </td>
                                        <td class="px-3 py-2">
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
                                    <td colspan="9" class="px-3 py-3 text-right font-medium text-gray-900 dark:text-white">Total HT:</td>
                                    <td class="px-3 py-3 font-bold text-gray-900 dark:text-white">
                                        <span x-text="App.formatMoneyConverted(total, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="label">Notes</label>
                    <textarea x-model="notes" class="input" rows="2" placeholder="Observations..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="<?= $approvisionnementsUrl ?>" class="btn-secondary">Annuler</a>
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
