<?php 
$pageTitle = 'Inventaire des véhicules';
$printUrl = url('vehicules/inventaire') . '?print=1';
ob_start();
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 no-print">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventaire des véhicules</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Consultez, corrigez et initialisez le stock présent dans chaque véhicule.</p>
    </div>
    <div class="flex items-center gap-2 no-print">
        <?php if (isset($can_edit_inventory) && $can_edit_inventory): ?>
        <button type="button" onclick="document.getElementById('modal-transfert').style.display='flex'" class="btn btn-warning btn-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Transférer
        </button>
        <?php endif; ?>
        <a href="<?= url('vehicules') ?>" class="btn btn-secondary btn-sm">
            Retour aux véhicules
        </a>
        <button type="button" onclick="(function(){var url='<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>';var w=window.open(url,'_blank');if(!w){window.location.href=url;}})()" class="btn btn-primary btn-sm">
            Imprimer
        </button>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 no-print">
    <div class="stat-card">
        <p class="stat-label text-xs uppercase text-gray-500">Véhicules</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white"><?= (int) ($totaux['vehicules'] ?? 0) ?></p>
    </div>
    <div class="stat-card border-l-4 border-green-500">
        <p class="stat-label text-xs uppercase text-green-600">Disponibles</p>
        <p class="text-xl font-bold text-green-600"><?= (int) ($totaux['disponibles'] ?? 0) ?></p>
    </div>
    <div class="stat-card border-l-4 border-yellow-500">
        <p class="stat-label text-xs uppercase text-yellow-600">En mission</p>
        <p class="text-xl font-bold text-yellow-600"><?= (int) ($totaux['en_mission'] ?? 0) ?></p>
    </div>
    <div class="stat-card border-l-4 border-primary-500">
        <p class="stat-label text-xs uppercase text-primary-600">Occupation moyenne</p>
        <p class="text-xl font-bold text-primary-600"><?= number_format((float) ($totaux['occupation_moyenne'] ?? 0), 1, ',', ' ') ?>%</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 no-print">
    <div class="stat-card border-l-4 border-green-500">
        <p class="stat-label text-xs uppercase text-green-600">Caisses pleines</p>
        <p class="text-xl font-bold text-green-600"><?= number_format((int) ($totaux['caisses_pleine'] ?? 0), 0, ',', ' ') ?></p>
    </div>
    <div class="stat-card border-l-4 border-gray-400">
        <p class="stat-label text-xs uppercase text-gray-600 dark:text-gray-400">Caisses vides</p>
        <p class="text-xl font-bold text-gray-600 dark:text-gray-400"><?= number_format((int) ($totaux['caisses_vide'] ?? 0), 0, ',', ' ') ?></p>
    </div>
    <div class="stat-card border-l-4 border-blue-500">
        <p class="stat-label text-xs uppercase text-blue-600">Capacité totale</p>
        <p class="text-xl font-bold text-blue-600"><?= number_format((int) ($totaux['capacite'] ?? 0), 0, ',', ' ') ?></p>
    </div>
    <div class="stat-card border-l-4 border-purple-500">
        <p class="stat-label text-xs uppercase text-purple-600">Total physique</p>
        <p class="text-xl font-bold text-purple-600">
            <?= number_format((int) ($totaux['caisses_pleine'] ?? 0) + (int) ($totaux['caisses_vide'] ?? 0), 0, ',', ' ') ?>
        </p>
    </div>
</div>

<?php if (!empty($can_edit_inventory)): ?>
<div class="card mb-6 no-print">
    <div class="card-header flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Initialiser le stock d’un véhicule</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Choisissez un véhicule, vérifiez les produits présents et enregistrez l’inventaire total.</p>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Les quantités sont saisies en <span class="font-semibold">caisses</span>.
        </div>
    </div>
    <div class="card-body">
        <div x-data="vehicleInventoryForm()" x-init="init()">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="label">Véhicule</label>
                    <select x-model="vehicule_id" @change="reloadLines()" class="input" required>
                        <template x-for="v in vehicules" :key="v.id">
                            <option :value="String(v.id)" x-text="v.immatriculation + ' - ' + (v.agent_nom || 'Sans agent')"></option>
                        </template>
                    </select>
                </div>
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Stock véhicule sélectionné</p>
                    <p class="text-lg font-bold text-primary-700" x-text="selectedTotal() + ' cs'"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="selectedVehicleLabel()"></p>
                </div>
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Occupation</p>
                    <p class="text-lg font-bold text-green-700" x-text="selectedOccupation() + ' %'"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="selectedCapacity() + ' caisses de capacité'"></p>
                </div>
            </div>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produit</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Btl/Caisse</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien plein</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau plein</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Écart plein</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien vide</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau vide</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Écart vide</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(ligne, index) in lignes" :key="ligne.produit_id">
                            <tr x-bind:class="hasEcart(ligne) ? 'bg-yellow-50 dark:bg-yellow-900/10' : ''">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="ligne.produit_nom"></div>
                                    <div class="text-xs text-gray-500 font-mono" x-text="ligne.produit_code"></div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300" x-text="ligne.bouteilles_par_caisses"></td>
                                <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400" x-text="ligne.ancien_caisses_pleine"></td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" class="input w-24 text-right" min="0" step="1" x-model.number="ligne.caisses_pleine" @input="sanitizeLine(ligne)">
                                </td>
                                <td class="px-4 py-3 text-right font-semibold" x-bind:class="lineEcartPleine(ligne) > 0 ? 'text-green-600' : (lineEcartPleine(ligne) < 0 ? 'text-red-600' : 'text-gray-400')" x-text="lineEcartPleine(ligne) > 0 ? '+' + lineEcartPleine(ligne) : lineEcartPleine(ligne)"></td>
                                <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400" x-text="ligne.ancien_caisses_vide"></td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" class="input w-24 text-right" min="0" step="1" x-model.number="ligne.caisses_vide" @input="sanitizeLine(ligne)">
                                </td>
                                <td class="px-4 py-3 text-right font-semibold" x-bind:class="lineEcartVide(ligne) > 0 ? 'text-green-600' : (lineEcartVide(ligne) < 0 ? 'text-red-600' : 'text-gray-400')" x-text="lineEcartVide(ligne) > 0 ? '+' + lineEcartVide(ligne) : lineEcartVide(ligne)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Motif d'ecart -->
            <div x-show="hasAnyEcart()" x-transition class="mt-4 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <span class="font-semibold text-yellow-700 dark:text-yellow-300">Ecarts detectes</span>
                    <span class="text-sm text-yellow-600 dark:text-yellow-400" x-text="ecartCount() + ' produit(s) avec ecart(s)'"></span>
                </div>
                <label class="label">Motif de l'ecart *</label>
                <textarea x-model="motif_ecart" class="input" rows="2" placeholder="Expliquez la raison des differences constatees (ex: casse, vol, erreur de comptage...)" :required="hasAnyEcart()"></textarea>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
                <div class="text-sm text-gray-500">
                    <span class="font-medium text-gray-700 dark:text-gray-200" x-text="lignes.length"></span> produit(s) dans l’inventaire
                    <span x-show="hasAnyEcart()" class="ml-2 text-yellow-600 font-medium" x-text="'(' + ecartCount() + ' ecart(s))'"></span>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="reloadLines()" class="btn-secondary">Recharger depuis le véhicule</button>
                    <button type="button" @click="save()" class="btn-primary" :disabled="loading">
                        <span x-show="!loading">Enregistrer l’inventaire</span>
                        <span x-show="loading">Enregistrement...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Véhicule</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Agent</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase">Emplacement</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Capacité</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Pleine</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Vide</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase">Occupation</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($vehicules)): ?>
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun véhicule trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($vehicules as $vehicule): ?>
                        <?php
                            $stockPleines = (int) ($vehicule['stock_caisses_pleine'] ?? 0);
                            $stockVides = (int) ($vehicule['stock_caisses_vide'] ?? 0);
                            $stockTotal = $stockPleines + $stockVides;
                            $capacite = (int) ($vehicule['capacite'] ?? 0);
                            $occupation = (float) ($vehicule['occupation_pourcentage'] ?? 0);
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors align-top">
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($vehicule['immatriculation'] ?? '') ?>
                                </div>
                                <div class="text-[10px] text-gray-500">
                                    <?= htmlspecialchars(($vehicule['marque'] ?? '') . ' ' . ($vehicule['modele'] ?? '')) ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <?= htmlspecialchars(trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? '')) ?: 'N/A') ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <div><?= htmlspecialchars($vehicule['emplacement_nom'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ((int) ($vehicule['en_mission'] ?? 0) > 0): ?>
                                <span class="badge-warning">En mission</span>
                                <?php else: ?>
                                <span class="badge-success">Disponible</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">
                                <?= number_format($capacite, 0, ',', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">
                                <?= number_format($stockPleines, 0, ',', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-400">
                                <?= number_format($stockVides, 0, ',', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-primary-600">
                                <?= number_format($stockTotal, 0, ',', ' ') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold <?= $occupation >= 90 ? 'text-red-600' : ($occupation >= 75 ? 'text-yellow-600' : 'text-green-600') ?>">
                                <?= number_format($occupation, 1, ',', ' ') ?>%
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="<?= url('vehicules/' . (int) $vehicule['id']) ?>" class="text-primary-600 hover:text-primary-700 font-medium">Voir</a>
                            </td>
                        </tr>
                        <tr class="bg-gray-50/70 dark:bg-gray-800/40">
                            <td colspan="10" class="px-4 pb-4">
                                <details class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Détail du stock par produit
                                    </summary>
                                    <div class="px-4 pb-4">
                                        <?php if (empty($vehicule['stock'])): ?>
                                            <p class="text-sm text-gray-500">Aucun stock dans ce véhicule.</p>
                                        <?php else: ?>
                                            <div class="overflow-x-auto">
                                                <table class="table min-w-full">
                                                    <thead>
                                                        <tr>
                                                            <th>Produit</th>
                                                            <th class="text-right">Caisses pleines</th>
                                                            <th class="text-right">Caisses vides</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($vehicule['stock'] as $ligne): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ligne['produit_nom'] ?? '') ?></div>
                                                                <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></div>
                                                            </td>
                                                            <td class="text-right font-bold text-green-600"><?= number_format((int) round((float) ($ligne['caisses_pleine'] ?? 0)), 0, ',', ' ') ?></td>
                                                            <td class="text-right font-bold text-gray-600 dark:text-gray-400"><?= number_format((int) round((float) ($ligne['caisses_vide'] ?? 0)), 0, ',', ' ') ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('vehicleInventoryForm', () => ({
        loading: false,
        vehicule_id: <?= json_encode((string) ($vehicules[0]['id'] ?? '')) ?>,
        vehicules: <?= json_encode($vehicules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        produits: <?= json_encode($produits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        lignes: [],
        motif_ecart: '',

        init() {
            if (!this.vehicule_id && this.vehicules.length > 0) {
                this.vehicule_id = String(this.vehicules[0].id);
            }
            this.reloadLines();
        },

        parseQty(value) {
            const n = parseFloat(value);
            return Number.isFinite(n) ? n : 0;
        },

        getSelectedVehicule() {
            return this.vehicules.find(v => String(v.id) === String(this.vehicule_id)) || null;
        },

        getStockForProduct(produitId) {
            const vehicule = this.getSelectedVehicule();
            const stock = Array.isArray(vehicule?.stock) ? vehicule.stock : [];
            return stock.find(item => String(item.produit_id) === String(produitId)) || {};
        },

        reloadLines() {
            this.lignes = (this.produits || []).map((p) => {
                const stock = this.getStockForProduct(p.id);
                const ancienPleine = Math.max(0, Math.round(this.parseQty(stock.caisses_pleine || 0)));
                const ancienVide = Math.max(0, Math.round(this.parseQty(stock.caisses_vide || 0)));
                return {
                    produit_id: p.id,
                    produit_nom: p.nom,
                    produit_code: p.code,
                    bouteilles_par_caisses: parseInt(p.bouteilles_par_caisses) || 24,
                    caisses_pleine: ancienPleine,
                    caisses_vide: ancienVide,
                    ancien_caisses_pleine: ancienPleine,
                    ancien_caisses_vide: ancienVide
                };
            });
        },

        sanitizeLine(ligne) {
            ligne.caisses_pleine = Math.max(0, Math.round(this.parseQty(ligne.caisses_pleine || 0)));
            ligne.caisses_vide = Math.max(0, Math.round(this.parseQty(ligne.caisses_vide || 0)));
        },

        lineTotal(ligne) {
            return Math.max(0, Math.round(this.parseQty(ligne.caisses_pleine || 0) + this.parseQty(ligne.caisses_vide || 0)));
        },

        lineEcartPleine(ligne) {
            return this.parseQty(ligne.caisses_pleine || 0) - this.parseQty(ligne.ancien_caisses_pleine || 0);
        },

        lineEcartVide(ligne) {
            return this.parseQty(ligne.caisses_vide || 0) - this.parseQty(ligne.ancien_caisses_vide || 0);
        },

        hasEcart(ligne) {
            return this.lineEcartPleine(ligne) !== 0 || this.lineEcartVide(ligne) !== 0;
        },

        hasAnyEcart() {
            return this.lignes.some(l => this.hasEcart(l));
        },

        ecartCount() {
            return this.lignes.filter(l => this.hasEcart(l)).length;
        },

        selectedCapacity() {
            const vehicule = this.getSelectedVehicule();
            return Math.max(0, Math.round(this.parseQty(vehicule?.capacite || 0)));
        },

        selectedTotal() {
            return this.lignes.reduce((total, ligne) => total + this.lineTotal(ligne), 0);
        },

        selectedOccupation() {
            const capacity = this.selectedCapacity();
            if (capacity <= 0) {
                return 0;
            }
            return Math.round((this.selectedTotal() / capacity) * 1000) / 10;
        },

        selectedVehicleLabel() {
            const vehicule = this.getSelectedVehicule();
            if (!vehicule) {
                return 'Aucun véhicule sélectionné';
            }
            return `${vehicule.immatriculation || ''} • ${vehicule.emplacement_nom || ''}`.trim();
        },

        async save() {
            const vehicule = this.getSelectedVehicule();
            if (!vehicule) {
                App.notify('Sélectionnez un véhicule', 'error');
                return;
            }

            // Vérifier motif si écarts
            if (this.hasAnyEcart() && !this.motif_ecart.trim()) {
                App.notify('Veuillez indiquer le motif des écarts constatés', 'error');
                return;
            }

            const lignes = this.lignes.map((ligne) => ({
                produit_id: parseInt(ligne.produit_id, 10),
                caisses_pleine: Math.max(0, Math.round(this.parseQty(ligne.caisses_pleine || 0))),
                caisses_vide: Math.max(0, Math.round(this.parseQty(ligne.caisses_vide || 0))),
                ancien_caisses_pleine: Math.max(0, Math.round(this.parseQty(ligne.ancien_caisses_pleine || 0))),
                ancien_caisses_vide: Math.max(0, Math.round(this.parseQty(ligne.ancien_caisses_vide || 0))),
                has_existing_stock: true
            }));

            try {
                this.loading = true;
                const ecartMsg = this.hasAnyEcart() ? ` (${this.ecartCount()} écart(s) détecté(s))` : '';
                const ok = await App.confirm({
                    title: 'Enregistrer l\'inventaire du véhicule ?',
                    message: `Confirmer l\'enregistrement de l\'inventaire pour ${vehicule.immatriculation || 'ce véhicule'}${ecartMsg} ?`,
                    confirmText: 'Enregistrer',
                    cancelText: 'Annuler',
                    type: this.hasAnyEcart() ? 'warning' : 'info'
                });

                if (!ok) {
                    return;
                }

                const result = await App.api('/api/vehicules/' + parseInt(this.vehicule_id, 10) + '/inventaire', 'POST', {
                    lignes: lignes,
                    motif_ecart: this.motif_ecart.trim()
                });

                App.notify(result.message || 'Inventaire du vehicule enregistre avec succes', 'success');                window.location.reload();
            } catch (e) {
                App.notify(e.message || 'Erreur lors de l\'enregistrement', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

document.addEventListener('alpine:init', () => {
    Alpine.data('transfertForm', () => ({
        isOpen: true,
        loading: false,
        vehicules: <?= json_encode($vehicules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        produits: <?= json_encode($produits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        stockEntrepot: <?= json_encode($stock_entrepot ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        form: {
            source_type: 'entrepot',
            vehicule_source_id: '',
            vehicule_dest_id: '',
            produit_id: '',
            caisses_pleine: 0,
            caisses_vide: 0,
            motif: ''
        },

        vehiculesEnMission() {
            return this.vehicules.filter(v => (v.en_mission ?? 0) > 0);
        },

        sourceStock() {
            if (this.form.source_type === 'entrepot') {
                return this.stockEntrepot;
            }
            const v = this.vehicules.find(v => String(v.id) === String(this.form.vehicule_source_id));
            if (!v || !v.stock) return [];
            return v.stock;
        },

        selectedProduitStock() {
            const stock = this.sourceStock();
            const ligne = stock.find(s => String(s.produit_id) === String(this.form.produit_id));
            return ligne || null;
        },

        maxCaissesPleine() {
            const s = this.selectedProduitStock();
            return s ? Math.max(0, Math.round(parseFloat(s.caisses_pleine || 0))) : 0;
        },

        maxCaissesVide() {
            const s = this.selectedProduitStock();
            return s ? Math.max(0, Math.round(parseFloat(s.caisses_vide || 0))) : 0;
        },

        destLabel() {
            const v = this.vehicules.find(v => String(v.id) === String(this.form.vehicule_dest_id));
            return v ? v.immatriculation : '';
        },

        sourceLabel() {
            if (this.form.source_type === 'entrepot') return 'Entrepôt';
            const v = this.vehicules.find(v => String(v.id) === String(this.form.vehicule_source_id));
            return v ? v.immatriculation : '';
        },

        async save() {
            if (this.form.source_type === 'vehicule' && !this.form.vehicule_source_id) {
                App.notify('Veuillez sélectionner le véhicule source', 'error');
                return;
            }
            if (!this.form.vehicule_dest_id || !this.form.produit_id) {
                App.notify('Veuillez remplir tous les champs obligatoires', 'error');
                return;
            }
            if (this.form.caisses_pleine <= 0 && this.form.caisses_vide <= 0) {
                App.notify('La quantité à transférer doit être supérieure à 0', 'error');
                return;
            }
            if (this.form.source_type === 'vehicule' && this.form.vehicule_source_id === this.form.vehicule_dest_id) {
                App.notify('Les véhicules source et destination doivent être différents', 'error');
                return;
            }

            this.loading = true;
            try {
                const payload = {
                    source_type: this.form.source_type,
                    vehicule_dest_id: parseInt(this.form.vehicule_dest_id),
                    produit_id: parseInt(this.form.produit_id),
                    caisses_pleine: parseInt(this.form.caisses_pleine) || 0,
                    caisses_vide: parseInt(this.form.caisses_vide) || 0,
                    motif: this.form.motif
                };
                if (this.form.source_type === 'vehicule') {
                    payload.vehicule_source_id = parseInt(this.form.vehicule_source_id);
                }
                const result = await App.api('/api/vehicules/transfert', 'POST', payload);
                App.notify(result.message || 'Transfert effectué', 'success');
                this.isOpen = false;
                window.location.reload();
            } catch (e) {
                App.notify(e.message || 'Erreur lors du transfert', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<!-- Modal Transfert entre véhicules -->
<?php if (isset($can_edit_inventory) && $can_edit_inventory): ?>
<div id="modal-transfert" x-data="transfertForm" x-init="$watch('isOpen', v => { if(!v) $el.style.display='none'; else $el.style.display='flex'; })" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="isOpen = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 z-10">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transfert entre véhicules</h3>
                <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="save()">
                <div class="space-y-4">
                    <div>
                        <label class="label">Source *</label>
                        <div class="flex gap-3 mb-2">
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" x-model="form.source_type" value="entrepot" class="text-primary-600" @change="form.vehicule_source_id = ''; form.produit_id = ''; form.caisses_pleine = 0; form.caisses_vide = 0;">
                                Entrepôt
                            </label>
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" x-model="form.source_type" value="vehicule" class="text-primary-600" @change="form.produit_id = ''; form.caisses_pleine = 0; form.caisses_vide = 0;">
                                Véhicule
                            </label>
                        </div>
                        <template x-if="form.source_type === 'vehicule'">
                            <select x-model="form.vehicule_source_id" class="input" required @change="form.produit_id = ''; form.caisses_pleine = 0; form.caisses_vide = 0;">
                                <option value="">Sélectionner le véhicule source</option>
                                <template x-for="v in vehiculesEnMission()" :key="v.id">
                                    <option :value="String(v.id)" x-text="v.immatriculation + ' - ' + (v.agent_nom || v.agent_prenom ? (v.agent_prenom || '') + ' ' + (v.agent_nom || '') : 'Sans agent')"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="form.source_type === 'entrepot'">
                            <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-600 dark:text-gray-300">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Entrepôt principal
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="label">Véhicule destination *</label>
                        <select x-model="form.vehicule_dest_id" class="input" required>
                            <option value="">Sélectionner le véhicule destination</option>
                            <template x-for="v in vehiculesEnMission()" :key="v.id">
                                <option :value="String(v.id)" x-text="v.immatriculation + ' - ' + (v.agent_nom || v.agent_prenom ? (v.agent_prenom || '') + ' ' + (v.agent_nom || '') : 'Sans agent')"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="label">Produit *</label>
                        <select x-model="form.produit_id" class="input" required @change="form.caisses_pleine = 0; form.caisses_vide = 0;">
                            <option value="">Sélectionner un produit</option>
                            <template x-for="s in sourceStock()" :key="s.produit_id">
                                <option :value="String(s.produit_id)" x-text="s.produit_nom + ' (' + (s.caisses_pleine || 0) + ' cs pleines, ' + (s.caisses_vide || 0) + ' cs vides)'"></option>
                            </template>
                        </select>
                    </div>

                    <template x-if="form.produit_id && selectedProduitStock()">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-sm">
                            <p class="font-semibold text-blue-700 dark:text-blue-300">Stock disponible dans <span x-text="sourceLabel()"></span></p>
                            <p class="mt-1">Caisses pleines: <b x-text="maxCaissesPleine()"></b> | Caisses vides: <b x-text="maxCaissesVide()"></b></p>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Caisses pleines à transférer</label>
                            <input type="number" x-model.number="form.caisses_pleine" class="input" min="0" :max="maxCaissesPleine()" step="1">
                            <p class="text-[10px] text-gray-500 mt-1">Max: <span x-text="maxCaissesPleine()"></span></p>
                        </div>
                        <div>
                            <label class="label">Caisses vides à transférer</label>
                            <input type="number" x-model.number="form.caisses_vide" class="input" min="0" :max="maxCaissesVide()" step="1">
                            <p class="text-[10px] text-gray-500 mt-1">Max: <span x-text="maxCaissesVide()"></span></p>
                        </div>
                    </div>

                    <div>
                        <label class="label">Motif</label>
                        <input type="text" x-model="form.motif" class="input" placeholder="Raison du transfert (optionnel)">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="isOpen = false" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-warning" :disabled="loading">
                        <span x-show="!loading">Transférer</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
