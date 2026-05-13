<?php 
$pageTitle = 'Inventaire des véhicules';
$printMode = isset($print_mode) ? (bool) $print_mode : false;
$printUrl = url('vehicules/inventaire') . '?print=1';
$customStyle = $printMode ? "@media print {
    @page { size: landscape; margin: 8mm; }
    html, body { height: auto !important; }
    .no-print, button, .btn, .sidebar, .fixed, .notifications-container { display: none !important; }
    details { display: block !important; }
    details summary { display: none !important; }
    .table-container { overflow: visible !important; }
    table { table-layout: fixed !important; width: 100% !important; }
    thead { display: table-header-group !important; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    th, td { padding: 4px 6px !important; font-size: 9pt !important; white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
}" : null;
ob_start();
?>

<?php if ($printMode): ?>
    <?php $nomEntreprise = (new Parametre())->get('nom_entreprise', APP_NAME); ?>
    <div class="print-header print-only">
        <h1><?= htmlspecialchars($nomEntreprise) ?></h1>
        <p><?= htmlspecialchars($pageTitle) ?> — <?= date('d/m/Y H:i') ?></p>
    </div>
<?php endif; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 no-print">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventaire des véhicules</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Consultez, corrigez et initialisez le stock présent dans chaque véhicule.</p>
    </div>
    <div class="flex items-center gap-2 no-print">
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
        <p class="stat-label text-xs uppercase text-purple-600">Stock total</p>
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
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses pleines</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses vides</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(ligne, index) in lignes" :key="ligne.produit_id">
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="ligne.produit_nom"></div>
                                    <div class="text-xs text-gray-500 font-mono" x-text="ligne.produit_code"></div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300" x-text="ligne.bouteilles_par_caisses"></td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" class="input w-28 text-right" min="0" step="1" x-model.number="ligne.caisses_pleine" @input="sanitizeLine(ligne)">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" class="input w-28 text-right" min="0" step="1" x-model.number="ligne.caisses_vide" @input="sanitizeLine(ligne)">
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-primary-600" x-text="lineTotal(ligne) + ' cs'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
                <div class="text-sm text-gray-500">
                    <span class="font-medium text-gray-700 dark:text-gray-200" x-text="lignes.length"></span> produit(s) dans l’inventaire
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
                        <?php if (!$printMode): ?>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($vehicules)): ?>
                    <tr>
                        <td colspan="<?= $printMode ? 9 : 10 ?>" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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
                            <?php if (!$printMode): ?>
                            <td class="px-4 py-3 text-center">
                                <a href="<?= url('vehicules/' . (int) $vehicule['id']) ?>" class="text-primary-600 hover:text-primary-700 font-medium">Voir</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="bg-gray-50/70 dark:bg-gray-800/40">
                            <td colspan="<?= $printMode ? 9 : 10 ?>" class="px-4 pb-4">
                                <details class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800" <?= $printMode ? 'open' : '' ?>>
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
                return {
                    produit_id: p.id,
                    produit_nom: p.nom,
                    produit_code: p.code,
                    bouteilles_par_caisses: parseInt(p.bouteilles_par_caisses) || 24,
                    caisses_pleine: Math.max(0, Math.round(this.parseQty(stock.caisses_pleine || 0))),
                    caisses_vide: Math.max(0, Math.round(this.parseQty(stock.caisses_vide || 0)))
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

            const lignes = this.lignes.map((ligne) => ({
                produit_id: parseInt(ligne.produit_id, 10),
                caisses_pleine: Math.max(0, Math.round(this.parseQty(ligne.caisses_pleine || 0))),
                caisses_vide: Math.max(0, Math.round(this.parseQty(ligne.caisses_vide || 0))),
                has_existing_stock: true
            }));

            try {
                this.loading = true;
                const ok = await App.confirm({
                    title: 'Initialiser le stock du véhicule ?',
                    message: `Confirmer l\'enregistrement de l\'inventaire pour ${vehicule.immatriculation || 'ce véhicule'} ?`,
                    confirmText: 'Enregistrer',
                    cancelText: 'Annuler',
                    type: 'info'
                });

                if (!ok) {
                    return;
                }

                await App.api('/api/stocks/inventaire-initial', 'POST', {
                    emplacement_id: parseInt(vehicule.emplacement_id, 10),
                    lignes: lignes
                });

                App.notify('Inventaire du véhicule enregistré avec succès', 'success');
                window.location.reload();
            } catch (e) {
                App.notify(e.message || 'Erreur lors de l\'enregistrement', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php if ($printMode): ?>
<script>
window.addEventListener('load', () => {
    setTimeout(() => window.print(), 250);
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
