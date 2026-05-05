<?php 
$pageTitle = 'Détail mission';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('missions') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux missions
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Infos mission -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-lg font-semibold">Mission N° <?= htmlspecialchars($mission['numero_mission']) ?></h2>
                <div class="flex gap-2">
                    <a href="<?= url('missions/' . $mission['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimer
                    </a>
                    <?php if ($mission['statut'] === 'en_cours'): ?>
                    <button onclick="terminerMission()" class="btn btn-sm btn-primary">Terminer</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Date départ</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= date('d/m/Y H:i', strtotime($mission['date_depart'])) ?>
                        </p>
                    </div>
                    <?php if (!empty($mission['date_retour'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Date retour</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= date('d/m/Y H:i', strtotime($mission['date_retour'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Véhicule</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars($mission['immatriculation']) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Agent</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars($mission['agent_nom'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Zone</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars($mission['zone_nom'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Statut</p>
                        <?php if ($mission['statut'] === 'en_cours'): ?>
                        <span class="badge-warning">En cours</span>
                        <?php else: ?>
                        <span class="badge-success">Terminée</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($mission['notes'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Notes</p>
                    <p class="text-gray-900 dark:text-white"><?= htmlspecialchars($mission['notes']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chargement -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Chargement</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mission['chargements'])): ?>
                <div class="p-6 text-center text-gray-500">Aucun chargement</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-right">Caisses</th>
                                <th class="text-right">Prix caisse</th>
                                <th class="text-right">Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mission['chargements'] as $item): ?>
                            <?php
                                $btlParCaisse = (int)($item['bouteilles_par_caisses'] ?? 24);
                                if ($btlParCaisse <= 0) {
                                    $btlParCaisse = 24;
                                }
                                $prixCaisse = $item['prix_vente_caisses'] ?: ($item['prix_vente_unitaire'] * $btlParCaisse);
                            ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($item['produit_nom']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($item['produit_code']) ?></div>
                                </td>
                                <td class="text-right"><?= number_format((int) ($item['quantite_caisses'] ?? 0), 0, '.', ' ') ?> cs</td>
                                <td class="text-right"><?= format_money_converted($prixCaisse) ?></td>
                                <td class="text-right font-medium"><?= format_money_converted($item['sous_total'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <td colspan="3" class="text-right font-bold">Total caisses</td>
                                <td class="text-right font-bold text-primary-600">
                                    <?= format_money_converted($mission['total_chargement'] ?? 0) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Clients servis -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Clients servis</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mission['clients'])): ?>
                <div class="p-6 text-center text-gray-500">Aucune vente enregistrée</div>
                <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($mission['clients'] as $client): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($client['nom']) ?>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?= htmlspecialchars($client['adresse'] ?? '') ?> - <?= htmlspecialchars($client['telephone'] ?? '') ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium"><?= number_format((int) round(($client['quantite'] ?? 0) / max((int) ($client['bouteilles_par_caisses'] ?? 24), 1)), 0, '.', ' ') ?> caisses</p>
                                <p class="text-sm text-gray-500"><?= format_money_converted($client['montant'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Résumé -->
        <div class="card">
            <div class="card-body text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total caisses</p>
                <p class="text-3xl font-bold text-primary-600">
                    <?= $mission['total_caisses'] ?? 0 ?>
                </p>
                <p class="text-sm text-gray-500 mt-2"><?= count($mission['clients'] ?? []) ?> client(s) servis</p>
            </div>
        </div>
        
        <!-- Ventes réalisées -->
        <?php if ($mission['statut'] === 'terminee'): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Ventes réalisées</h3>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Vendu</p>
                        <p class="text-xl font-bold text-green-600">
                            <?= $mission['ventes']['quantite'] ?? 0 ?> bouteilles
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Chiffre d'affaires</p>
                        <p class="text-xl font-bold text-primary-600">
                            <?= format_money_converted($mission['ventes']['total'] ?? 0) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Retours vides</p>
                        <p class="text-xl font-bold text-gray-400">
                            <?= $mission['ventes']['retours'] ?? 0 ?> bouteilles
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Créé par -->
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-gray-500 dark:text-gray-400">Créé par</p>
                <p class="font-medium"><?= htmlspecialchars($mission['created_by_nom'] ?? 'Système') ?></p>
                <p class="text-sm text-gray-500">
                    <?= date('d/m/Y H:i', strtotime($mission['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Terminer Mission -->
    <div 
        x-data="{
            isOpen: false,
            loading: false,
            retours: {},
            vides_retournes: {},
            montant_encaisse: 0,
            chargements: <?= htmlspecialchars(json_encode($mission['chargements'] ?? []), ENT_QUOTES, 'UTF-8') ?>,
            
            initData() {
                if (this.chargements && Array.isArray(this.chargements)) {
                    this.chargements.forEach(c => {
                        this.retours[c.produit_id] = 0;
                        this.vides_retournes[c.produit_id] = 0;
                    });
                }
            },
            
            getTotalAttendu() {
                let total = 0;
                if (!this.chargements) return 0;
                this.chargements.forEach(c => {
                    const vendus = c.quantite_chargee - (this.retours[c.produit_id] || 0);
                    if (vendus > 0) {
                        const btlPerCs = parseInt(c.bouteilles_par_caisses) || 24;
                        const prixCaisse = parseFloat(c.prix_vente_caisses) || (parseFloat(c.prix_vente_unitaire) * btlPerCs);
                        total += (vendus / btlPerCs) * prixCaisse;
                    }
                });
                return total;
            },

            async submit() {
                const ok = await App.confirm({
                    title: 'Clôturer la mission ?',
                    message: 'Confirmer la clôture de la mission ?',
                    confirmText: 'Clôturer',
                    cancelText: 'Annuler',
                    type: 'warning'
                });
                if (!ok) return;
                this.loading = true;
                try {
                    await App.api('/api/missions/<?= $mission['id'] ?>/terminer', 'POST', {
                        retours: this.retours,
                        vides_retournes: this.vides_retournes,
                        montant_encaisse: App.convertMoney(this.montant_encaisse, window.DEVISE, window.BASE_DEVISE)
                    });
                    App.notify('Mission clôturée avec succès');
                    location.reload();
                } catch (e) {
                    App.notify(e.message, 'error');
                } finally {
                    this.loading = false;
                }
            }
        }"
        x-init="initData()"
        x-show="isOpen"
        @open-modal-terminer.window="isOpen = true"
        class="fixed inset-0 z-50 overflow-y-auto"
        x-cloak
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="isOpen = false" x-show="isOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
            
            <div class="modal-content relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-lg shadow-xl" 
                 x-show="isOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="card-header flex items-center justify-between border-b p-4">
                    <h3 class="text-lg font-semibold">Clôture de la mission N° <?= htmlspecialchars($mission['numero_mission']) ?></h3>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6">
                    <form @submit.prevent="submit()">
                        <div class="space-y-6">
                            <!-- Tableau des retours -->
                            <div class="overflow-x-auto">
                                <table class="table w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Chargé</th>
                                            <th>Retour Pleins (btl)</th>
                                            <th>Retour Vides (caisses)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(c, index) in chargements" :key="index + '-' + c.produit_id">
                                            <tr>
                                                <td x-text="c.produit_nom"></td>
                                                <td x-text="c.quantite_chargee + ' btl'"></td>
                                                <td>
                                                    <input type="number" x-model.number="retours[c.produit_id]" class="input py-1 w-24" :max="c.quantite_chargee" min="0">
                                                </td>
                                                <td>
                                                    <input type="number" x-model.number="vides_retournes[c.produit_id]" class="input py-1 w-24" min="0">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Section Financière -->
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="label">Montant encaissé par l'agent (<span x-text="window.DEVISE"></span>)</label>
                                    <input type="number" x-model.number="montant_encaisse" class="input text-xl font-bold text-green-600" step="0.01" required>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 uppercase">Total attendu (Ventes)</p>
                                    <p class="text-2xl font-bold text-primary-600" x-text="App.formatMoneyConverted(getTotalAttendu(), window.BASE_DEVISE, window.DEVISE)"></p>
                                    <p class="text-xs mt-1" :class="montant_encaisse >= App.convertMoney(getTotalAttendu(), window.BASE_DEVISE, window.DEVISE) ? 'text-green-500' : 'text-red-500'">
                                        Ecart: <span x-text="App.formatMoney(montant_encaisse - App.convertMoney(getTotalAttendu(), window.BASE_DEVISE, window.DEVISE), window.DEVISE)"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-8">
                            <button type="button" @click="isOpen = false" class="btn btn-secondary">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                <span x-show="!loading">Valider le retour</span>
                                <span x-show="loading">Traitement...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function terminerMission() {
    window.dispatchEvent(new CustomEvent('open-modal-terminer'));
}

<?php if (isset($_GET['terminer']) && (string)$_GET['terminer'] === '1' && ($mission['statut'] ?? null) === 'en_cours'): ?>
window.addEventListener('load', () => {
    setTimeout(() => {
        try { terminerMission(); } catch (_) {}
    }, 50);
});
<?php endif; ?>
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
