<?php 
$pageTitle = 'Approvisionnements';
ob_start();
?>

<div class="card mb-6">
    <div class="card-body py-3">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Du</label>
                <input type="date" name="date_debut" value="<?= $filters['date_debut'] ?? '' ?>" class="input py-1.5 w-40">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Au</label>
                <input type="date" name="date_fin" value="<?= $filters['date_fin'] ?? '' ?>" class="input py-1.5 w-40">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Statut</label>
                <select name="statut" class="input py-1.5 w-40">
                    <option value="">Tous</option>
                    <option value="valide" <?= ($filters['statut'] ?? '') === 'valide' ? 'selected' : '' ?>>Validé</option>
                    <option value="annule" <?= ($filters['statut'] ?? '') === 'annule' ? 'selected' : '' ?>>Annulé</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2 w-full lg:w-auto lg:ml-auto">
                <button type="submit" class="btn-primary py-1.5 px-4 mr-2">Filtrer</button>
                <a href="<?= url('approvisionnements') ?>" class="btn-secondary py-1.5 px-4">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Liste des approvisionnements</h2>
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            <a href="?<?= http_build_query(array_merge($_GET, ['print' => '1'])) ?>" target="_blank" class="btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exporter Excel
            </a>
            <?php if (can('approvisionnements.creer')): ?>
            <a href="<?= url('approvisionnements/create') ?>" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvel approvisionnement
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Nouvel Approvisionnement -->
    <div 
        x-data="approModal"
        x-show="isOpen"
        @open-modal-appro.window="open()"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="isOpen = false"></div>
            <div class="relative w-full max-w-6xl bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-xl font-bold">Nouvel Approvisionnement (Plein contre Vide)</h3>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="label">Date</label>
                            <input type="date" x-model="form.date" class="input">
                        </div>
                        <div>
                            <label class="label">Fournisseur</label>
                            <input type="text" x-model="form.fournisseur" class="input">
                        </div>
                    </div>

                    <table class="table w-full mb-4">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>NP</th>
                                <th>Achat</th>
                                <th>Unite</th>
                                <th>Caisses</th>
                                <th>Type</th>
                                <th>Prix Caisse</th>
                                <th>Sous-total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(ligne, index) in form.lignes" :key="index">
                                <tr>
                                    <td>
                                        <select x-model="ligne.produit_id" class="input w-full" required @change="calculerTotal()">
                                            <option value="">-- Sélectionner --</option>
                                            <template x-for="p in produits" :key="p.id">
                                                <option :value="p.id" x-text="p.nom"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td>
                                        <span x-text="getProduit(ligne.produit_id)?.caisses_par_palette || '-'"></span>
                                    </td>
                                    <td>
                                        <input type="number" x-model.number="ligne.quantite_achat" class="input w-24" min="1" @input="calculerTotal()">
                                    </td>
                                    <td>
                                        <select x-model="ligne.unite_achat" class="input w-28" @change="calculerTotal()">
                                            <option value="caisse">Caisse</option>
                                            <option value="palette">Palette</option>
                                        </select>
                                    </td>
                                    <td class="font-bold">
                                        <span x-text="getQuantiteCaisses(ligne)"></span>
                                    </td>
                                    <td>
                                        <select x-model="ligne.type_achat" class="input w-24" @change="calculerTotal()">
                                            <option value="deposer">Déposer</option>
                                            <option value="enlever">Enlever</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span x-text="getProduit(ligne.produit_id) ? App.formatMoneyConverted(getPrixCaisse(getProduit(ligne.produit_id), ligne.type_achat), window.BASE_DEVISE, window.DEVISE) : '-'"></span>
                                    </td>
                                    <td class="font-bold">
                                        <span x-text="getProduit(ligne.produit_id) ? App.formatMoneyConverted(getQuantiteCaisses(ligne) * getPrixCaisse(getProduit(ligne.produit_id), ligne.type_achat), window.BASE_DEVISE, window.DEVISE) : '-'"></span>
                                    </td>
                                    <td>
                                        <button @click="form.lignes.splice(index, 1); calculerTotal()" class="text-red-500" x-show="form.lignes.length > 1">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <button @click="form.lignes.push({ produit_id: '', quantite_achat: 1, unite_achat: 'caisse', type_achat: 'deposer' })" class="btn-secondary btn-sm mb-6">+ Ajouter un produit</button>

                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg flex justify-between items-center mb-6">
                        <span class="text-lg font-bold text-gray-700 dark:text-gray-300">TOTAL À PAYER :</span>
                        <span class="text-2xl font-black text-primary-600">
                            <span id="display-total-global">-</span>
                        </span>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button @click="isOpen = false" class="btn-secondary">Annuler</button>
                        <button @click="save()" class="btn-primary" :disabled="loading">
                            <span x-show="!loading">Enregistrer l'achat</span>
                            <span x-show="loading">Traitement...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function getPrixCaisse(produit, typeAchat) {
    if (!produit) return 0;
    if (typeAchat === 'enlever' && produit.prix_achat_enlever > 0) {
        return produit.prix_achat_enlever;
    }
    if (typeAchat === 'deposer' && produit.prix_achat_deposer > 0) {
        return produit.prix_achat_deposer;
    }
    return produit.prix_achat_caisse || (produit.prix_achat_unitaire * produit.bouteilles_par_caisses);
}

function getQuantiteCaissesFromLigne(ligne, produit) {
    const quantite = parseInt(ligne.quantite_achat || 0);
    if (!produit || quantite <= 0) return 0;
    if (ligne.unite_achat === 'palette') {
        return quantite * (parseInt(produit.caisses_par_palette || 0) || 0);
    }
    return quantite;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('approModal', () => ({
        isOpen: false,
        loading: false,
        produits: <?= json_encode((new Produit())->getActive()) ?>,
        total: 0,
        form: {
            date: '<?= date('Y-m-d') ?>',
            fournisseur: 'Bralima',
            notes: '',
            lignes: [{ produit_id: '', quantite_achat: 1, unite_achat: 'caisse', type_achat: 'deposer' }]
        },

        init() {
            this.calculerTotal();
            this.$watch('form.lignes', () => this.calculerTotal(), { deep: true });
        },

        open() {
            this.isOpen = true;
        },

        getProduit(id) {
            return this.produits.find(p => p.id == id);
        },

        getQuantiteCaisses(ligne) {
            return getQuantiteCaissesFromLigne(ligne, this.getProduit(ligne.produit_id));
        },

        getTotal() {
            let sum = 0;
            this.form.lignes.forEach(l => {
                const p = this.getProduit(l.produit_id);
                if (p) {
                    const prixCaisse = getPrixCaisse(p, l.type_achat);
                    sum += this.getQuantiteCaisses(l) * prixCaisse;
                }
            });
            return sum;
        },

        calculerTotal() {
            let sum = 0;
            this.form.lignes.forEach(l => {
                const p = this.getProduit(l.produit_id);
                if (p) {
                    const prixCaisse = getPrixCaisse(p, l.type_achat);
                    sum += this.getQuantiteCaisses(l) * prixCaisse;
                }
            });
            const el = document.getElementById('display-total-global');
            if (el) el.innerText = App.formatMoneyConverted(sum, window.BASE_DEVISE, window.DEVISE);
            this.total = sum;
        },

        async save() {
            const validLignes = this.form.lignes
                .filter(l => l.produit_id && this.getQuantiteCaisses(l) > 0)
                .map(l => ({
                    produit_id: parseInt(l.produit_id),
                    quantite_caisses: this.getQuantiteCaisses(l),
                    type_achat: l.type_achat
                }));
            if (validLignes.length === 0) {
                App.notify('Sélectionnez au moins un produit avec une quantité', 'error');
                return;
            }

            this.loading = true;
            try {
                await App.api('/api/approvisionnements', 'POST', {
                    date_approvisionnement: this.form.date,
                    fournisseur: this.form.fournisseur,
                    notes: this.form.notes,
                    details: validLignes
                });
                App.notify('Approvisionnement enregistré avec succès');
                location.reload();
            } catch (e) {
                App.notify(e.message || 'Erreur lors de l\'enregistrement', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">N° Bon</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Fournisseur</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Qte achetee</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total HT</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($approvisionnements['data'])): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun approvisionnement trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($approvisionnements['data'] as $appro): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-left">
                                <span class="font-mono text-sm font-bold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($appro['numero_bon']) ?></span>
                            </td>
                            <td class="px-4 py-3 text-left text-sm text-gray-600 dark:text-gray-400">
                                <?= date('d/m/Y', strtotime($appro['date_approvisionnement'])) ?>
                            </td>
                            <td class="px-4 py-3 text-left text-sm text-gray-600 dark:text-gray-400">
                                <?= htmlspecialchars($appro['fournisseur'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-primary-600">
                                <?= number_format((int) ($appro['total_quantite_caisses'] ?? 0), 0, ',', ' ') ?> cs
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                <?= format_money_converted($appro['total_ht'] ?? 0) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($appro['statut'] === 'valide'): ?>
                                <span class="badge-success">Validé</span>
                                <?php elseif ($appro['statut'] === 'en_attente'): ?>
                                <span class="badge-warning">En attente</span>
                                <?php else: ?>
                                <span class="badge-danger">Annulé</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="<?= url('approvisionnements/' . $appro['id']) ?>" class="btn-secondary btn-sm" title="Voir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <?php if ($appro['statut'] === 'valide'): ?>
                                    <a href="<?= url('approvisionnements/' . $appro['id'] . '/edit') ?>"
                                    class="btn-primary btn-sm"
                                    title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 9.586-9.586z"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($appro['statut'] === 'valide' && can('approvisionnements.supprimer')): ?>
                                    <button 
                                        @click="annulerApprovisionnement(<?= $appro['id'] ?>, '<?= htmlspecialchars($appro['numero_bon']) ?>')"
                                        class="btn-danger btn-sm"
                                        title="Annuler"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Affichage de <?= (($approvisionnements['current_page'] - 1) * $approvisionnements['per_page']) + 1 ?> 
                à <?= min($approvisionnements['current_page'] * $approvisionnements['per_page'], $approvisionnements['total']) ?> 
                sur <?= $approvisionnements['total'] ?> résultats
            </p>
            <div class="flex space-x-1">
                <?php if ($approvisionnements['current_page'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $approvisionnements['current_page'] - 1])) ?>" class="btn-secondary btn-sm">Précédent</a>
                <?php endif; ?>

                <?php 
                for ($i = 1; $i <= $approvisionnements['last_page']; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="btn-sm px-3 <?= $i == $approvisionnements['current_page'] ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($approvisionnements['current_page'] < $approvisionnements['last_page']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $approvisionnements['current_page'] + 1])) ?>" class="btn-secondary btn-sm">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function annulerApprovisionnement(id, numero) {
    const ok = await App.confirm({
        title: 'Annuler l\'approvisionnement ?',
        message: 'Annuler l\'approvisionnement "' + numero + '" ? Le stock sera reversé.',
        confirmText: 'Annuler',
        cancelText: 'Retour',
        type: 'warning'
    });
    if (!ok) return;

    try {
        await App.api('/api/approvisionnements/' + id + '/annuler', 'POST');
        App.notify('Approvisionnement annulé');
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
