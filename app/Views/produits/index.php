<?php 
$pageTitle = 'Produits';
ob_start();
?>

<div class="card">
    <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Liste des produits</h2>
        <div class="flex items-center space-x-3">
            <!-- Filtre catégorie -->
            <select class="input w-auto" onchange="window.location.href='?categorie='+this.value">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['categorie']) ?>" <?= ($_GET['categorie'] ?? '') === $cat['categorie'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['categorie']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <?php if (can('produits.creer')): ?>
            <button onclick="openProduitModal()" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau produit
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Ordre</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix achat Déposer</th>
                        <th>Prix achat Enlever</th>
                        <th>Prix vente caisse</th>
                        <th>Stock (Caisses)</th>
                        <th>Caisses/palette</th>
                        <th>Seuil</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                    <tr>
                        <td colspan="12" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Aucun produit trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($produits as $produit): ?>
                        <tr>
                            <td>
                                <span class="font-mono text-sm"><?= htmlspecialchars($produit['code']) ?></span>
                            </td>
                            <td>
                                <span class="font-semibold text-primary-600"><?= (int) ($produit['position_affichage'] ?? 999) ?></span>
                            </td>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($produit['nom']) ?></div>
                                <div class="text-xs text-gray-500"><?= $produit['bouteilles_par_caisses'] ?> btl/caisse</div>
                            </td>
                            <td><?= htmlspecialchars($produit['categorie'] ?? '-') ?></td>
                            <td>
                                <span class="font-medium text-blue-600">
                                    <?= format_money_converted($produit['prix_achat_deposer'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="font-medium text-indigo-600">
                                    <?= format_money_converted($produit['prix_achat_enlever'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="font-medium text-green-600">
                                    <?php
                                        $btl = (int)($produit['bouteilles_par_caisses'] ?? 24);
                                        $prixCaisse = !empty($produit['prix_vente_caisses']) ? $produit['prix_vente_caisses'] : (($produit['prix_vente_unitaire'] ?? 0) * $btl);
                                        echo format_money_converted($prixCaisse);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-medium <?= $produit['stock_caisses_pleine'] <= $produit['seuil_alerte'] ? 'text-red-600' : '' ?>">
                                    <?= $produit['stock_caisses_pleine'] ?> caisses
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?= $produit['stock_plein'] ?> bouteilles
                                </div>
                            </td>
                            <td>
                                <span class="font-medium"><?= $produit['caisses_par_palette'] ?? '-' ?></span>
                            </td>
                            <td><?= $produit['seuil_alerte'] ?></td>
                            <td>
                                <?php 
                                $stockCaisses = $produit['stock_caisses_pleine'] ?? 0;
                                $seuil = $produit['seuil_alerte'] ?? 10;
                                
                                if ($stockCaisses <= 0): ?>
                                <span class="badge-danger">Rupture</span>
                                <?php elseif ($stockCaisses <= $seuil): ?>
                                <span class="badge-warning">Critique</span>
                                <?php else: ?>
                                <span class="badge-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="<?= url('produits/' . $produit['id']) ?>" class="btn btn-sm btn-secondary" title="Voir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <?php if (can('produits.creer')): ?>
                                    <button 
                                        onclick='editProduit(<?= json_encode($produit, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                        class="btn btn-sm btn-secondary"
                                        title="Modifier"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (can('produits.supprimer')): ?>
                                    <button 
                                        onclick="deleteProduit(<?= $produit['id'] ?>, '<?= htmlspecialchars($produit['nom'], ENT_QUOTES) ?>')"
                                        class="btn btn-sm btn-danger"
                                        title="Supprimer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Produit -->
<div 
    x-data="produitModal"
    x-show="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="close()"></div>
        
        <div class="modal-content relative w-full max-w-2xl">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editMode ? 'Modifier le produit' : 'Nouveau produit'"></h3>
                    <button @click="close()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="card-body">
                    <form @submit.prevent="save()">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Code *</label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="form.code" class="input flex-1" required readonly>
                                    <button type="button" @click="genererCode()" class="btn btn-secondary text-sm" title="Générer automatiquement">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                </div>
                                <p x-show="errors.code" class="text-red-500 text-xs mt-1" x-text="errors.code?.[0]"></p>
                            </div>
                            <div>
                                <label class="label">Nom *</label>
                                <input type="text" x-model="form.nom" class="input" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="label">Description</label>
                                <textarea x-model="form.description" class="input" rows="2"></textarea>
                            </div>
                            <div>
                                <label class="label">Catégorie</label>
                                <input type="text" x-model="form.categorie" class="input" list="categories-list">
                                <datalist id="categories-list">
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['categorie']) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div>
                                <label class="label">Bouteilles par caisse *</label>
                                <input type="number" x-model.number="form.bouteilles_par_caisses" class="input" required min="1">
                            </div>
                            <div>
                                <label class="label text-blue-600 font-bold">Prix d'achat à Déposer / Caisse (<span x-text="window.DEVISE"></span>) *</label>
                                <input type="number" x-model.number="form.prix_achat_deposer" class="input border-blue-300 focus:border-blue-500" required step="0.01" min="0">
                            </div>
                            <div>
                                <label class="label text-indigo-600 font-bold">Prix d'achat à Enlever / Caisse (<span x-text="window.DEVISE"></span>) *</label>
                                <input type="number" x-model.number="form.prix_achat_enlever" class="input border-indigo-300 focus:border-indigo-500" required step="0.01" min="0">
                            </div>
                            <div>
                                <label class="label text-green-600 font-bold">Prix de vente / Caisse (<span x-text="window.DEVISE"></span>) *</label>
                                <input type="number" x-model.number="form.prix_vente_caisses" class="input border-green-300 focus:border-green-500" required step="0.01" min="0">
                            </div>
                            <div>
                                <label class="label">Caisses par palette</label>
                                <input type="number" x-model.number="form.caisses_par_palette" class="input" min="0">
                            </div>
                            <div>
                                <label class="label">Seuil d'alerte (en caisses)</label>
                                <input type="number" x-model.number="form.seuil_alerte" class="input" min="0">
                            </div>
                            <div>
                                <label class="label">Position d'affichage</label>
                                <input type="number" x-model.number="form.position_affichage" class="input" min="1" step="1">
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="close()" class="btn btn-secondary">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                <span x-show="!loading">Enregistrer</span>
                                <span x-show="loading">Enregistrement...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('produitModal', () => ({
        isOpen: false,
        editMode: false,
        editId: null,
        form: {
            code: '',
            nom: '',
            description: '',
            categorie: '',
            bouteilles_par_caisses: 24,
            caisses_par_palette: 0,
            prix_achat_deposer: '',
            prix_achat_enlever: '',
            prix_vente_caisses: '',
            seuil_alerte: 10,
            position_affichage: 999
        },
        errors: {},
        loading: false,
        
        open() {
            this.editMode = false;
            this.editId = null;
            this.form = {
                code: '',
                nom: '',
                description: '',
                categorie: '',
                bouteilles_par_caisses: 24,
                caisses_par_palette: 0,
                prix_achat_deposer: '',
                prix_achat_enlever: '',
                prix_vente_caisses: '',
                seuil_alerte: 10,
                position_affichage: 999
            };
            this.errors = {};
            this.genererCode();
            this.isOpen = true;
        },
        
        async genererCode() {
            try {
                const result = await App.api('/api/produits/next-code', 'GET');
                this.form.code = result.code;
            } catch (e) {
                // Fallback: générer un code local
                const timestamp = Date.now().toString().slice(-6);
                this.form.code = 'PRD-' + timestamp;
            }
        },
        
        close() {
            this.isOpen = false;
        },
        
        edit(produit) {
            this.editMode = true;
            this.editId = produit.id;
            const devise = window.DEVISE || 'CDF';
            const baseDevise = window.BASE_DEVISE || 'CDF';
            const btl = parseInt(produit.bouteilles_par_caisses) || 24;
            const prixAchatDeposerCaisse = App.convertMoney(parseFloat(produit.prix_achat_deposer || 0), baseDevise, devise);
            const prixAchatEnleverCaisse = App.convertMoney(parseFloat(produit.prix_achat_enlever || 0), baseDevise, devise);
            const prixVenteCaisse = App.convertMoney(parseFloat(produit.prix_vente_caisses || produit.prix_vente_unitaire * btl || 0), baseDevise, devise);

            this.form = {
                ...produit,
                prix_achat_deposer: prixAchatDeposerCaisse,
                prix_achat_enlever: prixAchatEnleverCaisse,
                prix_vente_caisses: prixVenteCaisse
            };
            this.errors = {};
            this.isOpen = true;
        },
        
        async save() {
            this.loading = true;
            this.errors = {};
            
            // Validation côté client
            if (!this.form.nom || !this.form.nom.trim()) {
                this.errors.nom = ['Le nom est requis'];
                this.loading = false;
                return;
            }
            if (!this.form.prix_achat_enlever || this.form.prix_achat_enlever <= 0) {
                this.errors.prix_achat_enlever = ['Le prix d\'achat à Enlever (base) est requis'];
                this.loading = false;
                return;
            }
            if (!this.form.prix_vente_caisses || this.form.prix_vente_caisses <= 0) {
                this.errors.prix_vente_caisses = ['Le prix de vente est requis'];
                this.loading = false;
                return;
            }
            
            try {
                const ok = await App.confirm({
                    title: this.editMode ? 'Modifier le produit ?' : 'Créer le produit ?',
                    message: this.editMode ? 'Confirmer la modification de ce produit ?' : 'Confirmer la création de ce produit ?',
                    confirmText: this.editMode ? 'Modifier' : 'Créer',
                    cancelText: 'Annuler',
                    type: 'info'
                });
                if (!ok) {
                    this.loading = false;
                    return;
                }

                const url = this.editMode ? '/api/produits/' + this.editId : '/api/produits';
                const method = this.editMode ? 'PUT' : 'POST';
                const devise = window.DEVISE || 'CDF';
                const baseDevise = window.BASE_DEVISE || 'CDF';
                const btl = this.form.bouteilles_par_caisses || 1;
                const prixAchatDeposerCaisseDevise = parseFloat(this.form.prix_achat_deposer) || 0;
                const prixAchatEnleverCaisseDevise = parseFloat(this.form.prix_achat_enlever) || 0;
                const prixVenteCaisseDevise = parseFloat(this.form.prix_vente_caisses) || 0;

                const prixAchatDeposerCaisseBase = App.convertMoney(prixAchatDeposerCaisseDevise, devise, baseDevise);
                const prixAchatEnleverCaisseBase = App.convertMoney(prixAchatEnleverCaisseDevise, devise, baseDevise);
                const prixVenteCaisseBase = App.convertMoney(prixVenteCaisseDevise, devise, baseDevise);

                const payload = { ...this.form };
                // Keep the price-per-case as provided and send it to the server as source of truth.
                // Avoid computing and rounding the unit price here to prevent later mismatches.
                payload.prix_achat_deposer = parseFloat(prixAchatDeposerCaisseBase.toFixed(2));
                payload.prix_achat_enlever = parseFloat(prixAchatEnleverCaisseBase.toFixed(2));
                payload.prix_vente_caisses = parseFloat(prixVenteCaisseBase.toFixed(2));

                const result = await App.api(url, method, payload);
                
                const successMsg = (result && result.message) ? result.message : (this.editMode ? 'Produit mis à jour' : 'Produit créé avec succès');
                App.notify(successMsg);
                
                this.close();
                setTimeout(() => window.location.reload(), 500);
            } catch (e) {
                this.loading = false;
                
                let message = 'Une erreur est survenue';
                if (e && typeof e === 'object' && e.message) {
                    message = e.message;
                }
                
                if (e && e.errors) {
                    this.errors = e.errors;
                }
                
                App.notify(message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openProduitModal() {
    const modal = Alpine.$data(document.querySelector('[x-data="produitModal"]'));
    if (modal) modal.open();
}

function editProduit(produit) {
    const modal = Alpine.$data(document.querySelector('[x-data="produitModal"]'));
    if (modal) modal.edit(produit);
}

async function deleteProduit(id, name) {
    const ok = await App.confirm({
        title: 'Supprimer le produit ?',
        message: 'Supprimer le produit "' + name + '" ?',
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
        type: 'danger'
    });
    if (!ok) return;
    try {
        await App.api('/api/produits/' + id, 'DELETE');
        App.notify('Produit supprimé');
        window.location.reload();
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
