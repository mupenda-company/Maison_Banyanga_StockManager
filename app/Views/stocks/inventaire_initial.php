<?php 
$pageTitle = 'Inventaire initial';
ob_start();
?>

<div class="max-w-6xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Inventaire initial du dépôt principal</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Enregistrez ici la base de départ des produits et des caisses vides avant les approvisionnements et les ventes.
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
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses pleines</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Caisses vides</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock plein (btl)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock vide (btl)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(ligne, index) in lignes" :key="ligne.produit_id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white" x-text="ligne.produit_nom"></div>
                                        <div class="text-xs text-gray-500" x-text="ligne.produit_code"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300" x-text="ligne.bouteilles_par_caisses"></td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" class="input w-28 text-right" min="0" step="1" x-model.number="ligne.caisses_pleine" @input="sanitizeLine(ligne)">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" class="input w-28 text-right" min="0" step="1" x-model.number="ligne.caisses_vide" @input="sanitizeLine(ligne)">
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium" x-text="(ligne.caisses_pleine || 0) * (ligne.bouteilles_par_caisses || 24)"></td>
                                    <td class="px-4 py-3 text-right font-medium" x-text="(ligne.caisses_vide || 0) * (ligne.bouteilles_par_caisses || 24)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-500">
                        <span class="font-medium text-gray-700 dark:text-gray-200" x-text="totalProduits"></span> produit(s) préparé(s)
                    </div>
                    <div class="flex gap-3">
                        <a href="<?= url('stocks') ?>" class="btn-secondary">Annuler</a>
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

        init() {
            this.lignes = (this.produits || []).map((p) => {
                const existing = this.stocks[p.id] || {};
                return {
                    produit_id: p.id,
                    produit_nom: p.nom,
                    produit_code: p.code,
                    bouteilles_par_caisses: parseInt(p.bouteilles_par_caisses) || 24,
                    caisses_pleine: parseInt(existing.caisses_pleine || 0) || 0,
                    caisses_vide: parseInt(existing.caisses_vide || 0) || 0,
                    has_existing_stock: !!existing.id
                };
            });
            this.totalProduits = this.lignes.length;
        },

        sanitizeLine(ligne) {
            ligne.caisses_pleine = Math.max(0, Math.round(parseInt(ligne.caisses_pleine || 0) || 0));
            ligne.caisses_vide = Math.max(0, Math.round(parseInt(ligne.caisses_vide || 0) || 0));
        },

        async save() {
            this.loading = true;
            try {
                const lignes = this.lignes
                    .map((ligne) => ({
                        produit_id: parseInt(ligne.produit_id),
                        caisses_pleine: Math.max(0, Math.round(parseInt(ligne.caisses_pleine || 0) || 0)),
                        caisses_vide: Math.max(0, Math.round(parseInt(ligne.caisses_vide || 0) || 0)),
                        has_existing_stock: !!ligne.has_existing_stock
                    }))
                    .filter((ligne) => ligne.produit_id && (ligne.has_existing_stock || ligne.caisses_pleine > 0 || ligne.caisses_vide > 0));

                if (lignes.length === 0) {
                    throw new Error('Saisissez au moins un produit avec une quantité');
                }

                const result = await App.api('/api/stocks/inventaire-initial', 'POST', {
                    emplacement_id: parseInt(this.emplacement_id),
                    lignes: lignes
                });

                App.notify(result.message || 'Inventaire initial enregistré avec succès', 'success');
                setTimeout(() => window.location.href = '<?= url('stocks') ?>', 900);
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
