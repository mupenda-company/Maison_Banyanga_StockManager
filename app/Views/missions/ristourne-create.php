<?php
$pageTitle = 'Nouvelle mission de ristourne';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Creer une mission de ristourne</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Selectionnez les clients et choisissez un ou plusieurs produits a livrer selon leur ristourne.
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
                    globalProduit: '',
                    selected: {},
                    lignesParRistourne: {},
                    init() {
                        this.ristournes.forEach(r => {
                            this.selected[r.id] = false;
                            this.lignesParRistourne[r.id] = [this.newLine()];
                        });
                    },
                    newLine(produitId = '', caisses = 0) {
                        return { produit_id: produitId, caisses: caisses };
                    },
                    getVisibleRistournes() {
                        if (!this.zone_id) return [];
                        return this.ristournes.filter(r => String(r.zone_id || '') === String(this.zone_id));
                    },
                    onZoneChange() {
                        this.selectAll = false;
                        this.ristournes.forEach(r => {
                            if (String(r.zone_id || '') !== String(this.zone_id || '')) {
                                this.selected[r.id] = false;
                            }
                        });
                    },
                    toggleAll() {
                        const val = this.selectAll;
                        this.getVisibleRistournes().forEach(r => {
                            this.selected[r.id] = val;
                        });
                        this.applyGlobalProduit();
                    },
                    getSelectedRistournes() {
                        return this.getVisibleRistournes().filter(r => this.selected[r.id]);
                    },
                    getLines(ristourneId) {
                        if (!this.lignesParRistourne[ristourneId] || this.lignesParRistourne[ristourneId].length === 0) {
                            this.lignesParRistourne[ristourneId] = [this.newLine()];
                        }
                        return this.lignesParRistourne[ristourneId];
                    },
                    addLine(ristourneId) {
                        this.getLines(ristourneId).push(this.newLine('', 1));
                    },
                    removeLine(ristourneId, index) {
                        const lines = this.getLines(ristourneId);
                        if (lines.length <= 1) return;
                        lines.splice(index, 1);
                    },
                    getProduit(produitId) {
                        return this.produits.find(item => String(item.id) === String(produitId)) || null;
                    },
                    getPrixCaisse(produitId) {
                        const p = this.getProduit(produitId);
                        if (!p) return 0;
                        const bouteilles = parseInt(p.bouteilles_par_caisses || 24, 10) || 24;
                        const prixCaisse = parseFloat(p.prix_vente_caisses || 0);
                        if (prixCaisse > 0) return prixCaisse;
                        return (parseFloat(p.prix_vente_unitaire || 0) || 0) * bouteilles;
                    },
                    getLineCaisses(ristourne, line) {
                        const prix = this.getPrixCaisse(line.produit_id);
                        if (!line.produit_id || prix <= 0) return 0;
                        const explicit = parseInt(line.caisses || 0, 10) || 0;
                        const filledLines = this.getLines(ristourne.id).filter(l => l.produit_id);
                        if (explicit > 0) return explicit;
                        if (filledLines.length <= 1) {
                            return Math.ceil((parseFloat(ristourne.montant_ristourne || 0)) / prix);
                        }
                        return 0;
                    },
                    getLineValue(ristourne, line) {
                        return this.getLineCaisses(ristourne, line) * this.getPrixCaisse(line.produit_id);
                    },
                    getTotalValeurClient(ristourne) {
                        return this.getLines(ristourne.id).reduce((sum, line) => sum + this.getLineValue(ristourne, line), 0);
                    },
                    getComplementClient(ristourne) {
                        return Math.max(0, this.getTotalValeurClient(ristourne) - parseFloat(ristourne.montant_ristourne || 0));
                    },
                    getResteClient(ristourne) {
                        return Math.max(0, parseFloat(ristourne.montant_ristourne || 0) - this.getTotalValeurClient(ristourne));
                    },
                    getProduitsChoisis() {
                        const ids = [...new Set(this.getSelectedRistournes().flatMap(r => this.getLines(r.id).map(l => l.produit_id)).filter(id => id))];
                        return ids.map(id => this.getProduit(id)).filter(Boolean);
                    },
                    applyGlobalProduit() {
                        if (!this.globalProduit) return;
                        this.getSelectedRistournes().forEach(r => {
                            const lines = this.getLines(r.id);
                            lines[0].produit_id = this.globalProduit;
                        });
                    },
                    getTotalMontantRistourne() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + parseFloat(r.montant_ristourne || 0), 0);
                    },
                    getTotalCaisses() {
                        return this.getSelectedRistournes().reduce((sum, r) => {
                            return sum + this.getLines(r.id).reduce((lineSum, line) => lineSum + this.getLineCaisses(r, line), 0);
                        }, 0);
                    },
                    getTotalMontantLivre() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + this.getTotalValeurClient(r), 0);
                    },
                    getTotalComplement() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + this.getComplementClient(r), 0);
                    },
                    getTotalRestes() {
                        return this.getSelectedRistournes().reduce((sum, r) => sum + this.getResteClient(r), 0);
                    },
                    autoCaisses(ristourne, line) {
                        const prix = this.getPrixCaisse(line.produit_id);
                        if (!line.produit_id || prix <= 0) return;
                        line.caisses = Math.ceil(parseFloat(ristourne.montant_ristourne || 0) / prix);
                    },
                    async submit() {
                        if (!this.vehicule_id) {
                            App.notify('Selectionnez un vehicule', 'error');
                            return;
                        }
                        if (!this.zone_id) {
                            App.notify('Selectionnez une zone', 'error');
                            return;
                        }
                        this.applyGlobalProduit();
                        const selectedRistournes = this.getSelectedRistournes();
                        if (selectedRistournes.length === 0) {
                            App.notify('Selectionnez au moins une ristourne', 'error');
                            return;
                        }

                        const payload = [];
                        for (const r of selectedRistournes) {
                            const validLines = this.getLines(r.id).filter(line => line.produit_id);
                            if (validLines.length === 0) {
                                App.notify('Chaque client selectionne doit avoir au moins un produit', 'error');
                                return;
                            }
                            if (this.getResteClient(r) > 0) {
                                App.notify('La valeur des produits doit couvrir toute la ristourne de ' + r.client_nom, 'error');
                                return;
                            }
                            for (const line of validLines) {
                                const caisses = this.getLineCaisses(r, line);
                                if (caisses <= 0) {
                                    App.notify('Indiquez le nombre de caisses pour chaque produit quand un client a plusieurs produits', 'error');
                                    return;
                                }
                                payload.push({
                                    ristourne_id: parseInt(r.id),
                                    produit_id: parseInt(line.produit_id),
                                    caisses_prevues: caisses
                                });
                            }
                        }

                        this.loading = true;
                        try {
                            await App.api('/api/missions/ristourne', 'POST', {
                                vehicule_id: parseInt(this.vehicule_id),
                                zone_id: this.zone_id ? parseInt(this.zone_id) : null,
                                date_depart: this.date_depart,
                                ristournes: payload
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

                <div class="mb-6">
                    <label class="label">Produit rapide (optionnel)</label>
                    <div class="flex flex-col md:flex-row gap-2">
                        <select class="input" x-model="globalProduit" @change="applyGlobalProduit()">
                            <option value="">Appliquer un produit aux clients coches</option>
                            <template x-for="produit in produits" :key="produit.id">
                                <option :value="produit.id" x-text="produit.nom + (produit.code ? ' (' + produit.code + ')' : '')"></option>
                            </template>
                        </select>
                        <button type="button" @click="applyGlobalProduit()" class="btn btn-secondary whitespace-nowrap">Appliquer</button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="getProduitsChoisis().length > 0">
                        <template x-for="produit in getProduitsChoisis()" :key="produit.id">
                            <span class="inline-flex items-center rounded border border-primary-200 bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700" x-text="produit.nom + (produit.code ? ' (' + produit.code + ')' : '')"></span>
                        </template>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Une mission peut avoir un seul produit pour tout le monde, ou plusieurs produits selon les clients.</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                    <div class="p-4 rounded-lg border bg-blue-50 border-blue-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Total ristournes</p>
                        <p class="text-xl font-bold text-blue-700" x-text="App.formatMoneyConverted(getTotalMontantRistourne(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-green-50 border-green-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Total caisses</p>
                        <p class="text-xl font-bold text-green-700" x-text="getTotalCaisses() + ' cs'"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-purple-50 border-purple-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Montant prevu</p>
                        <p class="text-xl font-bold text-purple-700" x-text="App.formatMoneyConverted(getTotalMontantLivre(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-amber-50 border-amber-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Complements</p>
                        <p class="text-xl font-bold text-amber-700" x-text="App.formatMoneyConverted(getTotalComplement(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-slate-50 border-slate-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Restes</p>
                        <p class="text-xl font-bold text-slate-700" x-text="App.formatMoneyConverted(getTotalRestes(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-slate-50 border-slate-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Produits</p>
                        <p class="text-xl font-bold text-slate-700" x-text="getProduitsChoisis().length"></p>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Ristournes disponibles</h3>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300">
                            Tout selectionner
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
                                    <th class="p-3 text-right">Ristourne</th>
                                    <th class="p-3 text-left min-w-96">Produits a livrer</th>
                                    <th class="p-3 text-right">Valeur</th>
                                    <th class="p-3 text-right">Complement</th>
                                    <th class="p-3 text-right">Reste</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="ristourne in getVisibleRistournes()" :key="ristourne.id">
                                    <tr class="border-b align-top hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="selected[ristourne.id] ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                        <td class="p-3">
                                            <input type="checkbox" x-model="selected[ristourne.id]" @change="if(globalProduit && selected[ristourne.id]) getLines(ristourne.id)[0].produit_id=globalProduit" class="rounded border-gray-300">
                                        </td>
                                        <td class="p-3 font-medium">
                                            <div x-text="ristourne.client_nom + (ristourne.numero_client ? ' (' + ristourne.numero_client + ')' : '')"></div>
                                            <div class="text-xs text-gray-500" x-text="ristourne.zone_nom || ''"></div>
                                            <div class="text-xs text-gray-500" x-text="(ristourne.periode_debut || '') + ' -> ' + (ristourne.periode_fin || '')"></div>
                                        </td>
                                        <td class="p-3 text-right font-semibold" x-text="App.formatMoneyConverted(parseFloat(ristourne.montant_ristourne || 0), window.BASE_DEVISE, window.DEVISE)"></td>
                                        <td class="p-3">
                                            <div class="space-y-2">
                                                <template x-for="(line, index) in getLines(ristourne.id)" :key="index">
                                                    <div class="grid grid-cols-12 gap-2 items-center">
                                                        <select class="input col-span-6" x-model="line.produit_id" :disabled="!selected[ristourne.id]">
                                                            <option value="">Choisir un produit</option>
                                                            <template x-for="produit in produits" :key="produit.id">
                                                                <option :value="produit.id" x-text="produit.nom + (produit.code ? ' (' + produit.code + ')' : '')"></option>
                                                            </template>
                                                        </select>
                                                        <input type="number" min="0" step="1" class="input col-span-2 text-right" x-model.number="line.caisses" :disabled="!selected[ristourne.id]" placeholder="Auto">
                                                        <span class="col-span-2 text-right text-xs font-semibold" x-text="getLineCaisses(ristourne, line) + ' cs'"></span>
                                                        <button type="button" class="btn-secondary btn-sm col-span-1" @click="autoCaisses(ristourne, line)" :disabled="!selected[ristourne.id] || !line.produit_id">Auto</button>
                                                        <button type="button" class="text-red-600 col-span-1" @click="removeLine(ristourne.id, index)" x-show="getLines(ristourne.id).length > 1">x</button>
                                                    </div>
                                                </template>
                                                <button type="button" class="btn-secondary btn-sm" @click="addLine(ristourne.id)" :disabled="!selected[ristourne.id]">+ produit</button>
                                            </div>
                                        </td>
                                        <td class="p-3 text-right" x-text="selected[ristourne.id] ? App.formatMoneyConverted(getTotalValeurClient(ristourne), window.BASE_DEVISE, window.DEVISE) : '—'"></td>
                                        <td class="p-3 text-right text-red-600" x-text="selected[ristourne.id] ? App.formatMoneyConverted(getComplementClient(ristourne), window.BASE_DEVISE, window.DEVISE) : '—'"></td>
                                        <td class="p-3 text-right text-slate-600" x-text="selected[ristourne.id] ? App.formatMoneyConverted(getResteClient(ristourne), window.BASE_DEVISE, window.DEVISE) : '—'"></td>
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
                        <span x-show="!loading">Creer la mission (<span x-text="getSelectedRistournes().length"></span> clients)</span>
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