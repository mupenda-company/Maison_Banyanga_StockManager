<?php 
$pageTitle = 'Mouvements de stock';
$printMode = isset($print_mode) ? (bool) $print_mode : false;
$baseQuery = [];
if (!empty($filters['produit_id'])) {
    $baseQuery['produit_id'] = $filters['produit_id'];
}
if (!empty($filters['emplacement_id'])) {
    $baseQuery['emplacement_id'] = $filters['emplacement_id'];
}
$type = $filters['type'] ?? ($filters['type_mouvement'] ?? null);
if (!empty($type)) {
    $baseQuery['type'] = $type;
}
if (!empty($filters['date'])) {
    $baseQuery['date'] = $filters['date'];
}
if (!empty($filters['date_debut'])) {
    $baseQuery['date_debut'] = $filters['date_debut'];
}
if (!empty($filters['date_fin'])) {
    $baseQuery['date_fin'] = $filters['date_fin'];
}
$printUrl = '?' . http_build_query(array_merge($baseQuery, ['print' => 1]));
$exportUrl = '?' . http_build_query(array_merge($baseQuery, ['export' => 'excel']));
ob_start();
?>

<div class="mb-6 no-print">
    <a href="<?= url('stocks') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux stocks
    </a>
</div>

<!-- Filtres -->
<div class="card mb-6 no-print">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4 items-end">
            <div>
                <label class="label">Produit</label>
                <select name="produit_id" class="input">
                    <option value="">Tous les produits</option>
                    <?php foreach ($produits as $produit): ?>
                    <option value="<?= $produit['id'] ?>" <?= ($filters['produit_id'] ?? '') == $produit['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($produit['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Emplacement</label>
                <select name="emplacement_id" class="input">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($emplacements as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($filters['emplacement_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Type</label>
                <select name="type" class="input">
                    <option value="">Tous</option>
                    <option value="entree" <?= ($filters['type'] ?? '') == 'entree' ? 'selected' : '' ?>>Entrées</option>
                    <option value="sortie" <?= ($filters['type'] ?? '') == 'sortie' ? 'selected' : '' ?>>Sorties</option>
                    <option value="transfert" <?= ($filters['type'] ?? '') == 'transfert' ? 'selected' : '' ?>>Transferts</option>
                    <option value="inventaire" <?= ($filters['type'] ?? '') == 'inventaire' ? 'selected' : '' ?>>Ajustements / inventaire</option>
                </select>
            </div>
            <div><label class="label">Date début</label><input type="date" name="date_debut" class="input w-full" value="<?= $filters['date_debut'] ?? '' ?>"></div>
            <div><label class="label">Date fin</label><input type="date" name="date_fin" class="input w-full" value="<?= $filters['date_fin'] ?? '' ?>"></div>
            <div class="flex flex-col sm:flex-row gap-2 xl:justify-end">
                <button type="submit" class="btn btn-primary w-full sm:w-auto">Filtrer</button>
                <a href="<?= url('stocks/mouvements') ?>" class="btn btn-secondary w-full sm:w-auto">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<!-- En-tête d'impression -->
<div class="print-only mb-6 border-b-2 border-gray-800 pb-4">
    <div class="flex justify-between items-end">
        <div>
            <?php $nomEntreprise = (new Parametre())->get('nom_entreprise', APP_NAME); ?>
            <h1 class="text-2xl font-bold uppercase"><?= htmlspecialchars($nomEntreprise) ?></h1>
            <p class="text-sm"><?= htmlspecialchars($pageTitle) ?></p>
        </div>
        <div class="text-right text-xs">
            <p>Imprimé le : <?= date('d/m/Y H:i') ?></p>
            <p>Utilisateur : <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?></p>
        </div>
    </div>
</div>

<!-- Liste des mouvements -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h2 class="text-lg font-semibold">Historique des mouvements</h2>
        <div class="flex gap-2 no-print">
            <button type="button" onclick="(function(){var url='<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>';var w=window.open(url,'_blank');if(!w){window.location.href=url;}})()" class="btn btn-sm btn-secondary mr-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
            <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-secondary mr-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exporter
            </a>
            <button onclick="openTransfertModal()" class="btn btn-sm btn-primary mr-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Transfert
            </button>
            <button onclick="openAjustementModal()" class="btn btn-sm btn-secondary">
                Ajustement
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($mouvements)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            <p class="text-gray-500">Aucun mouvement trouvé</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Produit</th>
                        <th>Emplacement</th>
                        <th class="text-right">Quantité (Caisses)</th>
                        <th class="no-print">Référence</th>
                        <th>Par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mouvements as $mvt): 
                        // Conversion en caisses pour l'affichage
                        $bouteillesParCaisse = (int)($mvt['bouteilles_par_caisses'] ?? 24);
                        $caisses = isset($mvt['quantite_caisses_reference']) && $mvt['quantite_caisses_reference'] !== null
                            ? (float) $mvt['quantite_caisses_reference']
                            : ((float) $mvt['quantite'] / max(1, $bouteillesParCaisse));
                    ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($mvt['created_at'])) ?></td>
                        <td>
                            <?php
                                $isSaleMovement = ($mvt['reference_type'] ?? null) === 'vente';
                                if ($isSaleMovement && $mvt['type_mouvement'] === 'sortie') {
                                    $typeLabel = 'Sortie caisses pleines';
                                    $typeClass = 'badge-danger';
                                } elseif ($isSaleMovement && $mvt['type_mouvement'] === 'entree') {
                                    $typeLabel = 'Entrée emballages vides';
                                    $typeClass = 'badge-success';
                                } elseif ($mvt['type_mouvement'] === 'entree') {
                                    $typeLabel = 'Entrée';
                                    $typeClass = 'badge-success';
                                } elseif ($mvt['type_mouvement'] === 'sortie') {
                                    $typeLabel = 'Sortie';
                                    $typeClass = 'badge-danger';
                                } elseif ($mvt['type_mouvement'] === 'inventaire') {
                                    $typeLabel = (($mvt['reference_type'] ?? '') === 'ajustement_stock') ? 'Correction écart' : 'Inventaire';
                                    $typeClass = 'badge-warning';
                                } else {
                                    $typeLabel = 'Transfert';
                                    $typeClass = 'badge-info';
                                }
                            ?>
                            <span class="<?= $typeClass ?>"><?= htmlspecialchars($typeLabel) ?></span>
                            <?php if (!empty($mvt['motif'])): ?>
                            <div class="text-[10px] text-gray-500 mt-1 leading-tight">
                                <?= htmlspecialchars($mvt['motif']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($mvt['produit_nom']) ?></div>
                            <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($mvt['produit_code']) ?></div>
                        </td>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($mvt['emplacement_source'] ?? '-') ?></div>
                            <?php if ($mvt['type_mouvement'] === 'transfert'): ?>
                                <?php if (!empty($mvt['emplacement_dest'])): ?>
                                <div class="text-[10px] text-gray-500">
                                    <span class="opacity-70">Vers:</span>
                                    <?= htmlspecialchars($mvt['emplacement_dest']) ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-bold <?= $mvt['quantite'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= number_format(abs($caisses), 2, '.', ' ') ?> cs
                            <div class="text-[10px] font-normal opacity-50"><?= abs($mvt['quantite']) ?> btl</div>
                        </td>
                        <td class="no-print text-sm">
                            <?php
                            $refType = $mvt['reference_type'] ?? null;
                            $refId = $mvt['reference_id'] ?? null;
                            $refRoutes = [
                                'approvisionnement' => 'approvisionnements',
                                'approvisionnements' => 'approvisionnements',
                                'mission' => 'missions',
                                'missions' => 'missions',
                                'vente' => 'ventes',
                                'ventes' => 'ventes',
                            ];
                            $route = (!empty($refType) && isset($refRoutes[$refType])) ? $refRoutes[$refType] : null;
                            ?>
                            <?php if ($route && !empty($refId)): ?>
                                <a href="<?= url($route . '/' . $refId) ?>" class="text-primary-600 hover:underline flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <?= htmlspecialchars($mvt['reference_numero'] ?? 'Voir') ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="text-sm font-medium"><?= htmlspecialchars($mvt['user_nom'] ?? 'Système') ?></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (!$printMode && $pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center no-print">
            <p class="text-sm text-gray-500">
                Page <?= $pagination['current_page'] ?> sur <?= $pagination['last_page'] ?>
            </p>
            <div class="flex gap-2">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $pagination['current_page'] - 1])) ?>" class="btn btn-sm btn-secondary">Précédent</a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $pagination['current_page'] + 1])) ?>" class="btn btn-sm btn-primary">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    /* Supprime les en-têtes et pieds de page du navigateur (URL, Titre, etc.) */
    @page { 
        margin: 0.5cm; 
    }
    html, body { height: auto !important; }
    .h-screen { height: auto !important; }
    .overflow-hidden, .overflow-y-auto { overflow: visible !important; }
    body { 
        margin: 0;
        padding: 0;
        background: white !important; 
    }
    
    .no-print { display: none !important; }
    .card { border: none !important; shadow: none !important; margin-bottom: 0 !important; }
    .card-header { display: none !important; }
    .card-header h2 { margin-bottom: 10px !important; }
    
    .table-container { overflow: visible !important; }
    .table { width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; }
    thead { display: table-header-group !important; }
    tfoot { display: table-footer-group !important; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    .table th, .table td { border: 1px solid #ddd !important; padding: 8px !important; font-size: 10pt !important; white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
    .badge-success, .badge-danger, .badge-info { 
        border: 1px solid #ccc !important; 
        color: black !important; 
        background: transparent !important;
        padding: 2px 5px !important;
    }
}
</style>

<!-- Modal Transfert -->
<div x-data="transfertModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 modal-overlay" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Transfert de stock</h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="saveTransfert">
                <div class="space-y-4">
                    <div>
                        <label class="label">Produit</label>
                        <select x-model="form.produit_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($produits as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Source</label>
                        <select x-model="form.emplacement_source" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($emplacements as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Destination</label>
                        <select x-model="form.emplacement_dest" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($emplacements as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Quantité (Caisses)</label>
                        <input type="number" x-model.number="form.caisses" class="input" min="1" step="0.01" required>
                        <p class="text-[10px] text-gray-500 mt-1">Saisissez le nombre de caisses à transférer.</p>
                    </div>
                    <div>
                        <label class="label">Motif</label>
                        <input type="text" x-model="form.motif" class="input" placeholder="Raison du transfert">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Transférer</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajustement -->
<div x-data="ajustementModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 modal-overlay" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Ajustement de stock</h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="saveAjustement">
                <div class="space-y-4">
                    <div>
                        <label class="label">Produit</label>
                        <select x-model="form.produit_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($produits as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Emplacement</label>
                        <select x-model="form.emplacement_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($emplacements as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Quantité réelle (Caisses)</label>
                        <input type="number" x-model.number="form.caisses_reelle" class="input" min="0" step="0.01" required>
                        <p class="text-[10px] text-gray-500 mt-1">Saisissez le stock physique constaté en caisses.</p>
                    </div>
                    <div>
                        <label class="label">Motif *</label>
                        <textarea x-model="form.motif" class="input" rows="2" required placeholder="Raison de l'ajustement"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Valider</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transfertModal', () => ({
        isOpen: false,
        loading: false,
        form: { produit_id: '', emplacement_source: '', emplacement_dest: '', caisses: 1, motif: '' },
        open() { 
            this.form = { produit_id: '', emplacement_source: '', emplacement_dest: '', caisses: 1, motif: '' };
            this.isOpen = true; 
        },
        close() { this.isOpen = false; },
        async saveTransfert() {
            this.loading = true;
            try {
                // On récupère le produit pour connaître le nombre de btl par caisse
                const products = <?= json_encode($produits) ?>;
                const product = products.find(p => p.id == this.form.produit_id);
                const btlParCaisse = product ? (parseInt(product.bouteilles_par_caisses) || 24) : 24;
                
                const data = {
                    ...this.form,
                    quantite: this.form.caisses * btlParCaisse // On envoie toujours des bouteilles à l'API
                };

                const result = await App.api('/api/stocks/transfert', 'POST', data);
                App.notify(result.message, 'success');
                this.close();
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
    
    Alpine.data('ajustementModal', () => ({
        isOpen: false,
        loading: false,
        form: { produit_id: '', emplacement_id: '', caisses_reelle: 0, motif: '' },
        open() { 
            this.form = { produit_id: '', emplacement_id: '', caisses_reelle: 0, motif: '' };
            this.isOpen = true; 
        },
        close() { this.isOpen = false; },
        async saveAjustement() {
            this.loading = true;
            try {
                const products = <?= json_encode($produits) ?>;
                const product = products.find(p => p.id == this.form.produit_id);
                const btlParCaisse = product ? (parseInt(product.bouteilles_par_caisses) || 24) : 24;

                const data = {
                    ...this.form,
                    quantite_reelle: this.form.caisses_reelle * btlParCaisse
                };

                const result = await App.api('/api/stocks/ajustement', 'POST', data);
                App.notify(result.message, 'success');
                this.close();
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openTransfertModal() {
    Alpine.evaluate(document.querySelector('[x-data="transfertModal"]'), 'open()');
}

function openAjustementModal() {
    Alpine.evaluate(document.querySelector('[x-data="ajustementModal"]'), 'open()');
}
</script>

<?php 
if ($printMode):
?>
<script>
    window.addEventListener('load', function () {
        window.print();
    });
    window.addEventListener('afterprint', function () {
        if (window.opener) {
            window.close();
        }
    });
</script>
<?php
endif;
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
