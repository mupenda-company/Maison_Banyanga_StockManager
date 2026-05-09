<?php 
$pageTitle = 'Détail produit';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('produits') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux produits
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Infos produit -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-lg font-semibold"><?= htmlspecialchars($produit['nom']) ?></h2>
                <button onclick="openEditModal()" class="btn btn-sm btn-secondary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Modifier
                </button>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Code</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($produit['code']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Catégorie</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($produit['categorie'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Unité de base</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($produit['unite_base']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bouteilles/Caisse</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= $produit['bouteilles_par_caisses'] ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Prix achat unitaire</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= format_money_converted($produit['prix_achat_unitaire'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Prix vente unitaire</p>
                        <p class="font-medium text-green-600"><?= format_money_converted($produit['prix_vente_unitaire'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Prix vente caisse</p>
                        <p class="font-medium text-green-600"><?= format_money_converted($produit['prix_vente_caisses'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Seuil d'alerte</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= $produit['seuil_alerte'] ?></p>
                    </div>
                </div>
                
                <?php if (!empty($produit['description'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Description</p>
                    <p class="text-gray-900 dark:text-white"><?= htmlspecialchars($produit['description']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Stock par emplacement -->
    <div class="space-y-6">
        <?php $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24); if ($btlParCaisse <= 0) $btlParCaisse = 24; ?>
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Stock par emplacement</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stocks)): ?>
                <div class="p-6 text-center text-gray-500">
                    Aucun stock enregistré
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($stocks as $stock): ?>
                    <?php
                        $caissesPleines = isset($stock['caisses_pleine'])
                            ? (int) round($stock['caisses_pleine'])
                            : (int) round(($stock['quantite_pleine'] ?? 0) / $btlParCaisse);
                        $caissesVides = isset($stock['caisses_vide'])
                            ? (int) round($stock['caisses_vide'])
                            : (int) round(($stock['quantite_vide'] ?? 0) / $btlParCaisse);
                    ?>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($stock['emplacement_nom']) ?>
                            </p>
                            <span class="text-xs px-2 py-1 rounded-full 
                                <?= $stock['emplacement_type'] === 'depot' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' ?>">
                                <?= $stock['emplacement_type'] ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Caisses pleines:</span>
                                <span class="font-medium text-green-600"><?= $caissesPleines ?></span>
                            </div>
                            <div>
                                <span class="text-gray-500">Caisses vides:</span>
                                <span class="font-medium text-gray-400"><?= $caissesVides ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Total stock -->
        <div class="card bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800">
            <div class="card-body text-center">
                <p class="text-sm text-primary-600 dark:text-primary-400">Stock total</p>
                <p class="text-3xl font-bold text-primary-700 dark:text-primary-300">
                    <?= array_sum(array_map(static function ($stock) use ($btlParCaisse) {
                        if (isset($stock['caisses_pleine'])) {
                            return (int) round($stock['caisses_pleine']);
                        }

                        return (int) round(($stock['quantite_pleine'] ?? 0) / $btlParCaisse);
                    }, $stocks)) ?>
                </p>
                <p class="text-sm text-primary-600 dark:text-primary-400">caisses pleines</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal édition -->
<div x-data="editModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Modifier le produit</h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="save">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Code</label>
                        <input type="text" x-model="form.code" class="input" required>
                    </div>
                    <div>
                        <label class="label">Nom</label>
                        <input type="text" x-model="form.nom" class="input" required>
                    </div>
                    <div>
                        <label class="label">Prix achat unitaire</label>
                        <input type="number" step="0.01" x-model="form.prix_achat_unitaire" class="input" required>
                    </div>
                    <div>
                        <label class="label">Prix vente unitaire</label>
                        <input type="number" step="0.01" x-model="form.prix_vente_unitaire" class="input" required>
                    </div>
                    <div>
                        <label class="label">Prix vente caisse</label>
                        <input type="number" step="0.01" x-model="form.prix_vente_caisses" class="input">
                    </div>
                    <div>
                        <label class="label">Seuil d'alerte</label>
                        <input type="number" x-model="form.seuil_alerte" class="input">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="label">Description</label>
                    <textarea x-model="form.description" class="input" rows="3"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Enregistrer</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('editModal', () => ({
        isOpen: false,
        loading: false,
        baseForm: null,
        form: {
            code: '<?= addslashes($produit['code']) ?>',
            nom: '<?= addslashes($produit['nom']) ?>',
            description: '<?= addslashes($produit['description'] ?? '') ?>',
            prix_achat_unitaire: <?= $produit['prix_achat_unitaire'] ?>,
            prix_vente_unitaire: <?= $produit['prix_vente_unitaire'] ?>,
            prix_vente_caisses: <?= $produit['prix_vente_caisses'] ?? 0 ?>,
            seuil_alerte: <?= $produit['seuil_alerte'] ?>
        },

        init() {
            this.baseForm = { ...this.form };
        },
        
        open() {
            const devise = window.DEVISE || 'CDF';
            const baseDevise = window.BASE_DEVISE || 'CDF';
            const base = this.baseForm || this.form;

            this.form = {
                ...base,
                prix_achat_unitaire: App.convertMoney(parseFloat(base.prix_achat_unitaire || 0), baseDevise, devise),
                prix_vente_unitaire: App.convertMoney(parseFloat(base.prix_vente_unitaire || 0), baseDevise, devise),
                prix_vente_caisses: App.convertMoney(parseFloat(base.prix_vente_caisses || 0), baseDevise, devise)
            };
            this.isOpen = true;
        },
        
        close() {
            this.isOpen = false;
        },
        
        async save() {
            this.loading = true;
            try {
                const devise = window.DEVISE || 'CDF';
                const baseDevise = window.BASE_DEVISE || 'CDF';
                const payload = { ...this.form };
                payload.prix_achat_unitaire = App.convertMoney(parseFloat(payload.prix_achat_unitaire || 0), devise, baseDevise);
                payload.prix_vente_unitaire = App.convertMoney(parseFloat(payload.prix_vente_unitaire || 0), devise, baseDevise);
                payload.prix_vente_caisses = App.convertMoney(parseFloat(payload.prix_vente_caisses || 0), devise, baseDevise);

                const result = await App.api('/api/produits/<?= $produit['id'] ?>', 'PUT', payload);
                App.notify(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openEditModal() {
    Alpine.evaluate(document.querySelector('[x-data="editModal"]'), 'open()');
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
