<?php 
$pageTitle = 'Modifier vente';
$autoriserInterchangeEmballages = !empty($autoriser_interchange_emballages);
$venteEstMobile = !empty($vente['mission_id']);
$origineVente = is_array($origine_vente) ? $origine_vente : [];

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Modifier la vente <?= htmlspecialchars($vente['numero_facture']) ?></h2>
        </div>
        <div class="card-body">
            <form 
                x-data="venteForm()"
                @submit.prevent="saveVente"
            >
                <!-- Informations gÃ©nÃ©rales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">Client *</label>
                        <input type="search" x-model="clientSearch" class="input mb-2" placeholder="Rechercher un client...">
                        <select x-model.number="client_id" class="input" required>
                            <option value="">Sélectionner un client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= (int)$client['id'] ?>">
                                    <?= htmlspecialchars($client['nom'] . (!empty($client['zone_nom']) ? ' (' . $client['zone_nom'] . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Point de vente *</label>
                        <select x-model.number="emplacement_id" x-init="$el.value = emplacement_id" class="input" required <?= $venteEstMobile ? 'disabled' : '' ?>>
                            <?php foreach ($emplacements as $emp): ?>
                            <option value="<?= (int)$emp['id'] ?>"><?= htmlspecialchars($emp['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($venteEstMobile && !empty($origineVente)): ?>
                            <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                Facture mobile issue du véhicule
                                <strong><?= htmlspecialchars($origineVente['vehicule_immatriculation'] ?? 'N/A') ?></strong>
                                <?php if (!empty($origineVente['numero_mission'])): ?>
                                    / Mission <?= htmlspecialchars($origineVente['numero_mission']) ?>
                                <?php endif; ?>.
                                Le point de vente est verrouillé pour garder le bon suivi du stock.
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="label">N° Facture</label>
                        <input type="text" value="<?= htmlspecialchars($vente['numero_facture']) ?>" class="input bg-gray-50" readonly>
                    </div>
                </div>
                
                <!-- Lignes de produits -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="label mb-0">Produits</label>
                        <button 
                            type="button"
                            @click="lignes.push({ produit_id: '', caisses: 0, caisses_vides_recues: 0, prix_caisse: 0 })"
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
                                    <?php if (!$autoriserInterchangeEmballages): ?><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Emballages reçus</th><?php endif; ?>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sous-total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(ligne, index) in lignes" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <select x-model.number="ligne.produit_id" class="input w-full" @change="onProduitChange(ligne)" required>
                                                <option value="">Sélectionner</option>
                                                <?php foreach ($produits as $produit): ?>
                                                    <option value="<?= (int)$produit['id'] ?>">
                                                        <?= htmlspecialchars($produit['nom']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="font-semibold" x-text="stockCaisses(ligne.produit_id)"></span>
                                            <?php if ($venteEstMobile): ?>
                                                <span class="block text-[10px] text-blue-600 dark:text-blue-400">Véhicule</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.prix_caisse" class="input w-32" step="0.01" min="0" @input="calculateTotals()">
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.caisses" class="input w-24" min="1" step="1" required @input="ligne.caisses = Math.round(ligne.caisses || 0); calculateTotals()">
                                        </td>
                                        <?php if (!$autoriserInterchangeEmballages): ?>
                                        <td class="px-4 py-2">
                                            <input type="number" x-model.number="ligne.caisses_vides_recues" class="input w-28" min="0" step="1" @input="ligne.caisses_vides_recues = Math.max(0, Math.round(ligne.caisses_vides_recues || 0))">
                                        </td>
                                        <?php endif; ?>
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
                                    <td colspan="<?= $autoriserInterchangeEmballages ? '4' : '5' ?>" class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                        Total HT:
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                        <span x-text="App.formatMoney(totalHt, (window.DEVISE || 'CDF'))"></span>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="<?= $autoriserInterchangeEmballages ? '4' : '5' ?>" class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        TVA (<?= $tva ?>%):
                                    </td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">
                                        <span x-text="App.formatMoney(totalTva, (window.DEVISE || 'CDF'))"></span>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr class="bg-primary-50 dark:bg-primary-900/50">
                                    <td colspan="<?= $autoriserInterchangeEmballages ? '4' : '5' ?>" class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
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

                <?php if ($autoriserInterchangeEmballages): ?>
                <!-- Emballages reçus -->
                <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                        <label class="label mb-0">Emballages recus</label>
                        <div class="text-sm font-semibold" :class="totalEmballagesRecus() <= totalCaissesVendues() ? 'text-gray-700 dark:text-gray-200' : 'text-red-600'">
                            <span x-text="'Vendu: ' + totalCaissesVendues() + ' cs'"></span>
                            <span class="mx-2">|</span>
                            <span x-text="'Recu: ' + totalEmballagesRecus() + ' cs'"></span>
                            <span class="mx-2">|</span>
                            <span x-text="'Dette: ' + Math.max(0, totalCaissesVendues() - totalEmballagesRecus()) + ' cs'"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <template x-for="p in produits" :key="'emb-' + p.id">
                            <label class="block">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300" x-text="p.nom"></span>
                                <input type="number" min="0" step="1" class="input mt-1" x-model.number="emballages_recus[p.id]" @input="emballages_recus[p.id] = Math.max(0, Math.round(emballages_recus[p.id] || 0))">
                            </label>
                        </template>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Billetage -->
                <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="label mb-0">Billetage</label>
                        <div class="text-sm font-semibold" :class="Math.abs(totalBilletage() - totalTtc) <= 0.01 ? 'text-green-600' : 'text-red-600'" x-text="'Ecart: ' + App.formatMoneyConverted(totalBilletage() - totalTtc, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 mb-2">CDF</p>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="coupure in coupures.CDF" :key="'cdf-' + coupure">
                                    <label class="flex items-center gap-2 text-sm">
                                        <span class="w-16" x-text="coupure"></span>
                                        <input type="number" min="0" step="1" class="input" x-model.number="billetage.CDF[coupure]">
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 mb-2">USD</p>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="coupure in coupures.USD" :key="'usd-' + coupure">
                                    <label class="flex items-center gap-2 text-sm">
                                        <span class="w-16" x-text="coupure"></span>
                                        <input type="number" min="0" step="1" class="input" x-model.number="billetage.USD[coupure]">
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-3" x-text="'Total billetage: ' + App.formatMoneyConverted(totalBilletage(), (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></p>
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
                        <span x-show="!loading">Modifier la vente</span>
                        <span x-show="loading">Modification...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('venteForm', () => ({
        client_id: <?= (int)($vente['client_id'] ?? 0) ?>,
        emplacement_id: <?= (int)($vente['emplacement_id'] ?? 0) ?>,
        clientSearch: '',
        notes: <?= json_encode($vente['notes'] ?? '') ?>,
        lignes: <?= json_encode(array_map(function ($d) {
            $btl = max(1, (int)($d['bouteilles_par_caisses'] ?? 24));
            return [
                'produit_id' => (int)$d['produit_id'],
                'caisses' => (int)($d['quantite_caisses'] ?? intdiv((int)$d['quantite'], $btl)),
                'caisses_vides_recues' => (int)($d['caisses_vides_recues'] ?? 0),
                'prix_caisse' => (float)($d['prix_caisse'] ?? (($d['prix_unitaire'] ?? 0) * $btl)),
            ];
        }, 
        $vente['details'] ?? [])) ?>,
        clients: <?= json_encode($clients) ?>,
        produits: <?= json_encode($produits) ?>,
        tva: <?= $tva ?>,
        loading: false,
        totalHt: 0,
        totalTva: 0,
        totalTtc: 0,
        coupures: { CDF: [50000, 20000, 10000, 5000, 1000, 500, 100], USD: [100, 50, 20, 10, 5, 1] },
        billetage: { CDF: {}, USD: {} },
        emballages_recus: <?= json_encode(array_reduce($vente['emballages_recus'] ?? [], function ($carry, $item) {
            $carry[(int)$item['produit_id']] = (int)$item['caisses_recues'];
            return $carry;
        }, [])) ?>,
        autoriserInterchange: <?= $autoriserInterchangeEmballages ? 'true' : 'false' ?>,

        init() {
            this.client_id = Number(this.client_id);
            this.emplacement_id = Number(this.emplacement_id);

            this.lignes = this.lignes.map(l => ({
                ...l,
                produit_id: Number(l.produit_id),
                caisses: Number(l.caisses || 0),
                caisses_vides_recues: Number(l.caisses_vides_recues || 0),
                prix_caisse: Number(l.prix_caisse || 0)
            }));

            this.calculateTotals();

            this.$watch('lignes', () => {
                this.calculateTotals();
            }, { deep: true });
        },

        stockCaisses(produitId) {
            const p = (this.produits || []).find(p => p.id == produitId);
            if (!p) return 0;

            if (p.caisses_pleine !== undefined && p.caisses_pleine !== null) {
                return Math.round(parseFloat(p.caisses_pleine) || 0);
            }

            const btl = parseInt(p.bouteilles_par_caisses) || 24;
            return Math.round((parseFloat(p.stock_plein) || 0) / btl);
        },

        allEmballagesRecusZero() {
            return this.totalCaissesVendues() > 0 && this.totalEmballagesRecus() === 0;
        },

        totalCaissesVendues() {
            return (this.lignes || []).reduce((sum, ligne) => {
                return sum + (ligne.produit_id ? Math.max(0, Math.round(parseFloat(ligne.caisses) || 0)) : 0);
            }, 0);
        },

        totalEmballagesRecus() {
            if (!this.autoriserInterchange) {
                return (this.lignes || []).reduce((sum, ligne) => sum + Math.max(0, Math.round(parseFloat(ligne.caisses_vides_recues) || 0)), 0);
            }
            return Object.values(this.emballages_recus || {}).reduce((sum, value) => {
                return sum + Math.max(0, Math.round(parseFloat(value) || 0));
            }, 0);
        },

        getEmballagesRecusPayload() {
            if (!this.autoriserInterchange) return [];
            return Object.entries(this.emballages_recus || {})
                .map(([produitId, caisses]) => ({
                    produit_id: parseInt(produitId),
                    caisses_recues: Math.max(0, Math.round(parseFloat(caisses) || 0))
                }))
                .filter(ligne => ligne.produit_id > 0 && ligne.caisses_recues > 0);
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

        totalBilletage() {
            let total = 0;
            Object.entries(this.billetage.CDF || {}).forEach(([coupure, quantite]) => {
                total += (parseFloat(coupure) || 0) * (parseInt(quantite) || 0);
            });
            Object.entries(this.billetage.USD || {}).forEach(([coupure, quantite]) => {
                total += App.convertMoney((parseFloat(coupure) || 0) * (parseInt(quantite) || 0), 'USD', (window.BASE_DEVISE || 'CDF'));
            });
            return total;
        },

        hasBilletage() {
            return this.totalBilletage() > 0;
        },
        async saveVente() {
            this.loading = true;
            try {
                if (!this.client_id) throw new Error('SÃ©lectionnez un client');

                const detailsLignes = this.lignes.filter(l => l.produit_id && l.caisses > 0);
                if (detailsLignes.length === 0) throw new Error('Ajoutez au moins un produit');

                if (this.totalEmballagesRecus() > this.totalCaissesVendues()) {
                    throw new Error('Le total des emballages reçus ne peut pas dépasser le total des caisses vendues.');
                }
                if (!this.autoriserInterchange) {
                    const ligneInvalide = detailsLignes.find(l => Math.max(0, Math.round(parseFloat(l.caisses_vides_recues) || 0)) > Math.max(0, Math.round(parseFloat(l.caisses) || 0)));
                    if (ligneInvalide) throw new Error('Les emballages reçus ne peuvent pas dépasser les caisses vendues sur une ligne.');
                }

                if (this.hasBilletage() && Math.abs(this.totalBilletage() - this.totalTtc) > 0.01) {
                    throw new Error('Le billetage ne correspond pas au total TTC.');
                }

                if (this.allEmballagesRecusZero() && !window.confirm('Aucun emballage vide nâ€™a Ã©tÃ© dÃ©clarÃ© pour cette vente. Confirmez-vous cette saisie ?')) {
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
                    return {
                        produit_id: parseInt(l.produit_id),
                        quantite: caisses * btlParCaisse,
                        quantite_caisses: caisses,
                        caisses_vides_recues: this.autoriserInterchange ? 0 : Math.max(0, Math.round(parseFloat(l.caisses_vides_recues) || 0)),
                        prix_unitaire: prixCaisseBase / btlParCaisse
                    };
                });
                const emballagesRecus = this.getEmballagesRecusPayload();
                
                await App.api('/api/ventes/<?= (int)$vente['id'] ?>', 'PUT', {
                    client_id: parseInt(this.client_id),
                    emplacement_id: parseInt(this.emplacement_id),
                    notes: this.notes,
                    details: details,
                    emballages_recus: this.autoriserInterchange ? emballagesRecus : [],
                    billetage: this.billetage
                });
                
                App.notify('Vente modifiée avec succès', 'success');
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

