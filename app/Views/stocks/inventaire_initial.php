<?php 
$emballageMode = !empty($emballageMode);
$pageTitle = $emballageMode ? 'Stock initial emballages' : 'Inventaire initial';
$returnUrl = $emballageMode ? url('emballages') : url('stocks');
ob_start();
?>

<div class="max-w-6xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= $emballageMode ? 'Stock initial emballages du dépôt principal' : 'Inventaire initial du dépôt principal' ?></h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <?= $emballageMode ? 'Enregistrez ici la base de départ des emballages vides avant les approvisionnements et les ventes.' : 'Enregistrez ici la base de départ des produits pleins. Les emballages vides se saisissent dans le tableau de bord Emballages.' ?>
            </p>
        </div>
        <div class="card-body">
            <form
                x-data="inventoryInitialForm()"
                @submit.prevent="save()"
            >
                <div class="mb-4 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="font-semibold text-blue-800 dark:text-blue-200">Emplacement</p>
                            <p class="text-sm text-blue-700 dark:text-blue-300"><?= htmlspecialchars($emplacement['nom'] ?? 'Entrepôt principal') ?></p>
                        </div>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            Tout est saisi en <span class="font-bold">caisses entières</span>.
                        </div>
                    </div>
                </div>

                <input type="hidden" x-model="emplacement_id">

                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Btl/Caisse</th>
                                <?php if (!$emballageMode): ?><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien plein</th><?php endif; ?>
                                <?php if (!$emballageMode): ?><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau plein</th><?php endif; ?>
                                <?php if (!$emballageMode): ?><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ecart plein</th><?php endif; ?>
                                <?php if ($emballageMode): ?><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien emballages</th><?php endif; ?>
                                <?php if ($emballageMode): ?><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau emballages</th><?php endif; ?>
                                <?php if ($emballageMode): ?><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ecart emballages</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(ligne, index) in lignes" :key="ligne.produit_id">
                                <tr x-bind:class="hasEcart(ligne) ? 'bg-yellow-50 dark:bg-yellow-900/10' : ''">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white" x-text="ligne.produit_nom"></div>
                                        <div class="text-xs text-gray-500" x-text="ligne.produit_code"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300" x-text="ligne.bouteilles_par_caisses"></td>
                                    <?php if (!$emballageMode): ?><td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400" x-text="ligne.ancien_caisses_pleine"></td><?php endif; ?>
                                    <?php if (!$emballageMode): ?><td class="px-4 py-3 text-right"><input type="number" class="input w-24 text-right" min="0" step="1" x-model.number="ligne.caisses_pleine" @input="sanitizeLine(ligne)"></td><?php endif; ?>
                                    <?php if (!$emballageMode): ?><td class="px-4 py-3 text-right font-semibold" x-bind:class="lineEcartPleine(ligne) > 0 ? 'text-green-600' : (lineEcartPleine(ligne) < 0 ? 'text-red-600' : 'text-gray-400')" x-text="lineEcartPleine(ligne) > 0 ? '+' + lineEcartPleine(ligne) : lineEcartPleine(ligne)"></td><?php endif; ?>
                                    <?php if ($emballageMode): ?><td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400" x-text="ligne.ancien_caisses_vide"></td><?php endif; ?>
                                    <?php if ($emballageMode): ?><td class="px-4 py-3 text-right"><input type="number" class="input w-24 text-right" min="0" step="1" x-model.number="ligne.caisses_vide" @input="sanitizeLine(ligne)"></td><?php endif; ?>
                                    <?php if ($emballageMode): ?><td class="px-4 py-3 text-right font-semibold" x-bind:class="lineEcartVide(ligne) > 0 ? 'text-green-600' : (lineEcartVide(ligne) < 0 ? 'text-red-600' : 'text-gray-400')" x-text="lineEcartVide(ligne) > 0 ? '+' + lineEcartVide(ligne) : lineEcartVide(ligne)"></td><?php endif; ?>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Resume des differences avant reprise de base -->
                <div x-show="hasAnyEcart()" x-transition class="mt-4 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <span class="font-semibold text-yellow-700 dark:text-yellow-300">Differences detectees</span>
                        <span class="text-sm text-yellow-600 dark:text-yellow-400" x-text="ecartCount() + ' produit(s) avec ecart(s)'"></span>
                    </div>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">Ces differences seront enregistrees comme nouvelle base. Aucun ecart ne sera cree.</p>
                    <label class="label">Motif de l'inventaire *</label>
                    <textarea x-model="motif_ecart" class="input" rows="2"
                    placeholder="Expliquez la raison du changement d'inventaire"
                    required></textarea>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-500">
                        <span class="font-medium text-gray-700 dark:text-gray-200" x-text="totalProduits"></span> produit(s) prepare(s)
                        <span x-show="hasAnyEcart()" class="ml-2 text-yellow-600 font-medium" x-text="'(' + ecartCount() + ' ecart(s))'"></span>
                    </div>
                    <div class="flex gap-3">
                        <a href="<?= $returnUrl ?>" class="btn-secondary">Annuler</a>
                        <button type="submit" class="btn-primary" :disabled="loading">
                            <span x-show="!loading">Enregistrer l'inventaire</span>
                            <span x-show="loading">Enregistrement...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inventoryInitialForm', () => ({
        loading: false,
        emplacement_id: '<?= (int) ($emplacement['id'] ?? 0) ?>',
        produits: <?= json_encode($produits) ?>,
        stocks: <?= json_encode($stocks) ?>,
        lignes: [],
        totalProduits: 0,
        motif_ecart: '',

        init() {
            this.lignes = (this.produits || []).map((p) => {
                const existing = this.stocks[p.id] || {};
                const ancienPleine = parseInt(existing.caisses_pleine || 0) || 0;
                const ancienVide = parseInt(existing.caisses_vide || 0) || 0;
                return {
                    produit_id: p.id,
                    produit_nom: p.nom,
                    produit_code: p.code,
                    bouteilles_par_caisses: parseInt(p.bouteilles_par_caisses) || 24,
                    caisses_pleine: ancienPleine,
                    caisses_vide: ancienVide,
                    ancien_caisses_pleine: ancienPleine,
                    ancien_caisses_vide: ancienVide,
                    has_existing_stock: !!existing.id
                };
            });
            this.totalProduits = this.lignes.length;
        },

        sanitizeLine(ligne) {
            ligne.caisses_pleine = Math.max(0, Math.round(parseInt(ligne.caisses_pleine || 0) || 0));
            ligne.caisses_vide = Math.max(0, Math.round(parseInt(ligne.caisses_vide || 0) || 0));
        },

        lineEcartPleine(ligne) {
            return (parseInt(ligne.caisses_pleine || 0) || 0) - (parseInt(ligne.ancien_caisses_pleine || 0) || 0);
        },

        lineEcartVide(ligne) {
            return (parseInt(ligne.caisses_vide || 0) || 0) - (parseInt(ligne.ancien_caisses_vide || 0) || 0);
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

        async save() {
            this.loading = true;
            try {
                const lignes = this.lignes
                    .map((ligne) => ({
                        produit_id: parseInt(ligne.produit_id),
                        caisses_pleine: Math.max(0, Math.round(parseInt(ligne.caisses_pleine || 0) || 0)),
                        caisses_vide: Math.max(0, Math.round(parseInt(ligne.caisses_vide || 0) || 0)),
                        ancien_caisses_pleine: Math.max(0, Math.round(parseInt(ligne.ancien_caisses_pleine || 0) || 0)),
                        ancien_caisses_vide: Math.max(0, Math.round(parseInt(ligne.ancien_caisses_vide || 0) || 0)),
                        has_existing_stock: !!ligne.has_existing_stock
                    }))
                    .filter((ligne) => ligne.produit_id && (ligne.has_existing_stock || ligne.caisses_pleine > 0 || ligne.caisses_vide > 0));

                if (lignes.length === 0) {
                    throw new Error('Saisissez au moins une quantite');
                }

                if (this.hasAnyEcart() && !this.motif_ecart.trim()) {
                    throw new Error('Veuillez indiquer le motif de l\'inventaire');
                }

                const result = await App.api('/api/stocks/inventaire-initial', 'POST', {
                    emplacement_id: parseInt(this.emplacement_id),
                    lignes: lignes,
                    motif_ecart: this.motif_ecart.trim(),
                    mode: '<?= $emballageMode ? 'emballage' : 'stock' ?>'
                });

                App.notify(result.message || 'Inventaire initial enregistré avec succès', 'success');
                setTimeout(() => window.location.href = '<?= $returnUrl ?>', 900);
            } catch (e) {
                App.notify(e.message || 'Erreur lors de l\'enregistrement', 'error');
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
