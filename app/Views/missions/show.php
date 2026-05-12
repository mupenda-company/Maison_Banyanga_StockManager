<?php 
$pageTitle = 'Détail mission';
$isRestourne = (($mission['type_mission'] ?? 'vente') === 'ristourne');
$firstChargement = $mission['chargements'][0] ?? [];
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
                <h2 class="text-lg font-semibold">
                    <?= $isRestourne ? 'Mission de ristourne N° ' : 'Mission N° ' ?><?= htmlspecialchars($mission['numero_mission']) ?>
                </h2>
                <div class="flex gap-2">
                    <a href="<?= url('missions/' . $mission['id'] . ($mission['statut'] === 'terminee' ? '/facture' : '/print')) ?>" target="_blank" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <?= $isRestourne ? 'Bon de ristourne' : ($mission['statut'] === 'terminee' ? 'Facture de mission' : 'Bon de sortie') ?>
                    </a>
                    <?php if ($mission['statut'] === 'en_cours'): ?>
                    <button onclick="terminerMission()" class="btn btn-sm btn-primary"><?= $isRestourne ? 'Clôturer' : 'Terminer' ?></button>
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
                    <?php if ($isRestourne): ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Client</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars($mission['client']['nom'] ?? $mission['ristourne']['client_nom'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Montant ristourne</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= format_money_converted($mission['montant_ristourne_initial'] ?? 0) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Montant livré</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= format_money_converted($mission['montant_livre'] ?? 0) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Reste administration</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= format_money_converted($mission['montant_restant_admin'] ?? 0) ?>
                        </p>
                    </div>
                    <?php endif; ?>
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
                <?php if ($isRestourne && !empty($mission['ristourne'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Ristourne liée</p>
                    <p class="text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($mission['ristourne']['client_nom'] ?? 'N/A') ?></p>
                    <p class="text-sm text-gray-500">Période: <?= htmlspecialchars($mission['ristourne']['periode_debut'] ?? '') ?> → <?= htmlspecialchars($mission['ristourne']['periode_fin'] ?? '') ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chargement -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold"><?= $isRestourne ? 'Produit livré' : 'Chargement' ?></h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mission['chargements'])): ?>
                <div class="p-6 text-center text-gray-500">Aucun chargement</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-left">Produit</th>
                                <th class="text-right">Stock départ</th>
                                <th class="text-right">Ajout mission</th>
                                <th class="text-right">Total réel</th>
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
                                $stockDepartCaisses = (int) ($item['caisses_deja_dans_vehicule'] ?? 0);
                                $ajoutMissionCaisses = (int) ($item['quantite_caisses'] ?? 0);
                                $totalReelCaisses = (int) ($item['caisses_total'] ?? ($stockDepartCaisses + $ajoutMissionCaisses));
                            ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($item['produit_nom']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($item['produit_code']) ?></div>
                                </td>
                                <td class="text-right"><?= number_format($stockDepartCaisses, 0, '.', ' ') ?> cs</td>
                                <td class="text-right"><?= number_format($ajoutMissionCaisses, 0, '.', ' ') ?> cs</td>
                                <td class="text-right font-semibold text-primary-600"><?= number_format($totalReelCaisses, 0, '.', ' ') ?> cs</td>
                                <td class="text-right"><?= format_money_converted($prixCaisse) ?></td>
                                <td class="text-right font-medium"><?= format_money_converted($item['sous_total'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <td colspan="3" class="text-right font-bold">Total caisses réelles</td>
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
                <h3 class="font-semibold"><?= $isRestourne ? 'Résumé de ristourne' : 'Clients servis' ?></h3>
            </div>
            <div class="card-body p-0">
                <?php if ($isRestourne): ?>
                <div class="p-6">
                    <div class="rounded-xl border bg-gray-50 dark:bg-gray-900/50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($mission['client']['nom'] ?? $mission['ristourne']['client_nom'] ?? 'Client') ?></p>
                                <p class="text-sm text-gray-500">Produit livré: <?= htmlspecialchars($firstChargement['produit_nom'] ?? 'N/A') ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold"><?= number_format((int) ($firstChargement['quantite_caisses'] ?? 0), 0, '.', ' ') ?> caisses</p>
                                <p class="text-sm text-gray-500"><?= format_money_converted($mission['montant_livre'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (empty($mission['clients'])): ?>
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
                                <p class="font-medium"><?= number_format((int) ($client['quantite_caisses'] ?? 0), 0, '.', ' ') ?> caisses</p>
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
                    <?= $isRestourne ? (int) ($firstChargement['quantite_caisses'] ?? 0) : ($mission['total_caisses'] ?? 0) ?>
                </p>
                <p class="text-sm text-gray-500 mt-2"><?= $isRestourne ? 'Mission de ristourne' : count($mission['clients'] ?? []) . ' client(s) servis' ?></p>
            </div>
        </div>
        
        <!-- Ventes réalisées -->
        <?php if ($mission['statut'] === 'terminee'): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold"><?= $isRestourne ? 'Ristourne réalisée' : 'Ventes réalisées' ?></h3>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    <?php if ($isRestourne): ?>
                    <div>
                        <p class="text-sm text-gray-500">Produit livré</p>
                        <p class="text-xl font-bold text-green-600">
                            <?= htmlspecialchars($firstChargement['produit_nom'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Caisses livrées</p>
                        <p class="text-xl font-bold text-primary-600">
                            <?= number_format((int) ($firstChargement['quantite_caisses'] ?? 0), 0, '.', ' ') ?> caisses
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Montant livré</p>
                        <p class="text-xl font-bold text-gray-400">
                            <?= format_money_converted($mission['montant_livre'] ?? 0) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Reste administration</p>
                        <p class="text-xl font-bold text-amber-600">
                            <?= format_money_converted($mission['montant_restant_admin'] ?? 0) ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div>
                        <p class="text-sm text-gray-500">Caisses vendues</p>
                        <p class="text-xl font-bold text-green-600">
                            <?= $mission['caisses_vendues_total'] ?? 0 ?> caisses
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Caisses vides retournées</p>
                        <p class="text-xl font-bold text-primary-600">
                            <?= $mission['retours_vides_total'] ?? 0 ?> caisses
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Montant à donner</p>
                        <p class="text-xl font-bold text-gray-400">
                            <?= format_money_converted($mission['montant_attendu'] ?? 0) ?>
                        </p>
                    </div>
                    <?php endif; ?>
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
            justification_cloture: '',
            isRestourne: <?= $isRestourne ? 'true' : 'false' ?>,
            missionSummary: {
                caissesVendues: <?= (int) ($mission['caisses_vendues_total'] ?? 0) ?>,
                caissesVidesAttendues: <?= (int) ($mission['caisses_vides_attendues'] ?? 0) ?>,
                retoursVides: <?= (int) ($mission['retours_vides_total'] ?? 0) ?>,
                montantAttendu: <?= json_encode((float) ($mission['montant_attendu'] ?? 0)) ?>,
                montantEncaisse: <?= json_encode((float) ($mission['montant_encaisse'] ?? 0)) ?>,
                montantEcart: <?= json_encode((float) ($mission['montant_ecart'] ?? 0)) ?>,
                caissesVidesEcart: <?= json_encode((int) ($mission['caisses_vides_ecart'] ?? 0)) ?>
            },
            chargements: <?= htmlspecialchars(json_encode($mission['chargements'] ?? []), ENT_QUOTES, 'UTF-8') ?>,
            
            initData() {
                if (this.chargements && Array.isArray(this.chargements)) {
                    this.chargements.forEach(c => {
                        this.retours[c.produit_id] = 0;
                        this.vides_retournes[c.produit_id] = 0;
                    });
                }
                this.montant_encaisse = this.getTotalAttendu();
            },
            
            getTotalAttendu() {
                if (this.isRestourne) {
                    return 0;
                }
                return parseFloat(this.missionSummary.montantAttendu || 0);
            },

            getMontantEcart() {
                return parseFloat(this.montant_encaisse || 0) - this.getTotalAttendu();
            },

            getTotalRetoursPleinsAttendues() {
                return (this.chargements || []).reduce((total, c) => {
                    const totalReel = parseInt(c.caisses_total || ((c.caisses_deja_dans_vehicule || 0) + (c.quantite_caisses || 0)), 10) || 0;
                    const vendues = parseInt(c.caisses_vendues || Math.floor((c.quantite_vendue || 0) / Math.max(parseInt(c.bouteilles_par_caisses || 24, 10) || 24, 1)), 10) || 0;
                    return total + Math.max(totalReel - vendues, 0);
                }, 0);
            },

            getTotalRetoursPleins() {
                return Object.values(this.retours).reduce((total, value) => total + (parseInt(value, 10) || 0), 0);
            },

            getRetoursPleinEcart() {
                return this.getTotalRetoursPleinsAttendues() - this.getTotalRetoursPleins();
            },

            getCaissesVidesAttendues() {
                return parseInt(this.missionSummary.caissesVidesAttendues || 0, 10);
            },

            getTotalVidesRetournees() {
                return Object.values(this.vides_retournes).reduce((total, value) => total + (parseInt(value, 10) || 0), 0);
            },

            getCaissesVidesEcart() {
                return this.getCaissesVidesAttendues() - this.getTotalVidesRetournees();
            },

            hasTooManyVidesRetournees() {
                return (this.chargements || []).some((c) => {
                    const maxVides = Math.max(parseInt(c.caisses_vides_recues || 0, 10) || 0, 0);
                    const saisi = parseInt(this.vides_retournes[c.produit_id] || 0, 10) || 0;
                    return saisi > maxVides;
                });
            },

            hasDiscrepancy() {
                if (this.isRestourne) {
                    return false;
                }
                return Math.abs(this.getMontantEcart()) > 0.01 || this.getRetoursPleinEcart() !== 0 || this.getCaissesVidesEcart() !== 0;
            },

            getClosureMessage() {
                if (this.isRestourne) {
                    return 'Mission de ristourne : aucune saisie de vente n’est requise pour la clôture.';
                }
                return this.hasDiscrepancy()
                    ? 'Des écarts ont été détectés : vous pouvez tout de même clôturer la mission.'
                    : 'Aucun écart détecté : la clôture peut être validée directement.';
            },

            async submit() {
                const ok = await App.confirm({
                    title: 'Clôturer la mission ?',
                    message: this.hasDiscrepancy()
                        ? 'Des écarts ont été détectés. Confirmer la clôture ?'
                        : 'Confirmer la clôture de la mission ?',
                    confirmText: 'Clôturer',
                    cancelText: 'Annuler',
                    type: 'warning'
                });
                if (!ok) return;
                this.loading = true;
                try {
                    const payload = this.isRestourne
                        ? { justification_cloture: this.justification_cloture }
                        : {
                            retours: this.retours,
                            vides_retournes: this.vides_retournes,
                            montant_encaisse: App.convertMoney(this.montant_encaisse, window.DEVISE, window.BASE_DEVISE),
                            justification_cloture: this.justification_cloture
                        };

                    await App.api('/api/missions/<?= $mission['id'] ?>/terminer', 'POST', payload);
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
            
            <div class="modal-content relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700" 
                 x-show="isOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="card-header flex items-center justify-between border-b p-4 bg-gray-50 dark:bg-gray-900/60">
                    <div>
                        <h3 class="text-lg font-semibold">Clôture de la mission N° <?= htmlspecialchars($mission['numero_mission']) ?></h3>
                        <p class="text-xs text-gray-500 mt-1">Vérifiez les montants, les caisses vides et la justification avant validation.</p>
                    </div>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-gray-500 rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6">
                    <form @submit.prevent="submit()">
                        <div class="space-y-6">
                            <div class="rounded-xl border p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4" :class="hasDiscrepancy() ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200'">
                                <div>
                                    <p class="text-sm font-semibold" :class="hasDiscrepancy() ? 'text-amber-800' : 'text-emerald-800'" x-text="hasDiscrepancy() ? 'Clôture avec écarts détectés' : 'Clôture cohérente'"></p>
                                    <p class="text-xs mt-1" :class="hasDiscrepancy() ? 'text-amber-700' : 'text-emerald-700'" x-text="getClosureMessage()"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-sm min-w-[260px]">
                                    <div class="rounded-lg bg-white/90 dark:bg-gray-800/90 border px-3 py-2">
                                        <p class="text-[11px] uppercase text-gray-500">Écart caisse</p>
                                        <p class="font-semibold" :class="Math.abs(getMontantEcart()) > 0.01 ? 'text-red-600' : 'text-green-600'" x-text="App.formatMoney(getMontantEcart(), window.DEVISE)"></p>
                                    </div>
                                    <div class="rounded-lg bg-white/90 dark:bg-gray-800/90 border px-3 py-2">
                                        <p class="text-[11px] uppercase text-gray-500">Écart vides</p>
                                        <p class="font-semibold" :class="getCaissesVidesEcart() !== 0 ? 'text-red-600' : 'text-green-600'" x-text="getCaissesVidesEcart() + ' cs'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tableau des retours -->
                            <div class="overflow-x-auto" x-show="!isRestourne">
                                <table class="table w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Total réel</th>
                                            <th>Total vendu</th>
                                            <th>Retours plein</th>
                                            <th>Retour vide</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(c, index) in chargements" :key="index + '-' + c.produit_id">
                                            <tr>
                                                <td x-text="c.produit_nom"></td>
                                                <td x-text="(c.caisses_total || ((c.caisses_deja_dans_vehicule || 0) + (c.quantite_caisses || 0))) + ' cs'" class="font-semibold text-primary-600"></td>
                                                <td x-text="(c.caisses_vendues || Math.floor((c.quantite_vendue || 0) / Math.max(parseInt(c.bouteilles_par_caisses || 24, 10) || 24, 1))) + ' cs'"></td>
                                                <td>
                                                    <input type="number" x-model.number="retours[c.produit_id]" class="input py-1 w-24" :max="Math.max((c.caisses_total || ((c.caisses_deja_dans_vehicule || 0) + (c.quantite_caisses || 0))) - (c.caisses_vendues || Math.floor((c.quantite_vendue || 0) / Math.max(parseInt(c.bouteilles_par_caisses || 24, 10) || 24, 1))), 0)" min="0">
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        <input type="number" x-model.number="vides_retournes[c.produit_id]" class="input py-1 w-24" min="0">
                                                        <p class="text-[10px] text-gray-500">Reçues: <span x-text="Math.max(parseInt(c.caisses_vides_recues || 0, 10) || 0, 0) + ' cs'"></span></p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Section Financière -->
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-6" x-show="!isRestourne">
                                <div>
                                    <label class="label">Montant encaissé réel (<span x-text="window.DEVISE"></span>)</label>
                                    <input type="number" x-model.number="montant_encaisse" class="input text-xl font-bold text-green-600" step="0.01" required>
                                    <p class="text-xs text-gray-500 mt-2">Saisir le montant réellement remis à la clôture.</p>
                                    <div class="mt-4">
                                        <label class="label">Justification de clôture</label>
                                        <textarea x-model="justification_cloture" class="input" rows="4" placeholder="Facultative, vous pouvez détailler les écarts si nécessaire."></textarea>
                                        <p class="text-xs mt-2" :class="hasDiscrepancy() ? 'text-red-500' : 'text-gray-500'">
                                            <span x-show="hasDiscrepancy()">Une justification peut être ajoutée si vous souhaitez préciser les écarts.</span>
                                            <span x-show="!hasDiscrepancy()">La justification reste facultative si tout est conforme.</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="mb-4 p-3 rounded-lg border bg-white dark:bg-gray-800 text-left">
                                        <p class="text-xs uppercase text-gray-500">Résumé de clôture</p>
                                        <p class="text-sm font-semibold mt-1" :class="hasDiscrepancy() ? 'text-red-700' : 'text-green-700'" x-text="getClosureMessage()"></p>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                                        <div class="p-3 border rounded-lg bg-white dark:bg-gray-800">
                                            <p class="text-[11px] uppercase text-gray-500">Montant attendu</p>
                                            <p class="text-lg font-bold text-primary-600" x-text="App.formatMoneyConverted(getTotalAttendu(), window.BASE_DEVISE, window.DEVISE)"></p>
                                        </div>
                                        <div class="p-3 border rounded-lg bg-white dark:bg-gray-800">
                                            <p class="text-[11px] uppercase text-gray-500">Montant encaissé</p>
                                            <p class="text-lg font-bold text-green-600" x-text="App.formatMoneyConverted(App.convertMoney(montant_encaisse, window.DEVISE, window.BASE_DEVISE), window.BASE_DEVISE, window.DEVISE)"></p>
                                        </div>
                                        <div class="p-3 border rounded-lg bg-white dark:bg-gray-800">
                                            <p class="text-[11px] uppercase text-gray-500">Écart caisse</p>
                                            <p class="text-lg font-bold" :class="Math.abs(getMontantEcart()) > 0.01 ? 'text-red-600' : 'text-green-600'" x-text="App.formatMoney(getMontantEcart(), window.DEVISE)"></p>
                                        </div>
                                        <div class="p-3 border rounded-lg bg-white dark:bg-gray-800">
                                            <p class="text-[11px] uppercase text-gray-500">Caisses vides retournées</p>
                                            <p class="text-lg font-bold text-orange-500" x-text="getTotalVidesRetournees() + ' / ' + getCaissesVidesAttendues() + ' cs'"></p>
                                            <p class="text-xs mt-1" :class="getCaissesVidesEcart() === 0 ? 'text-green-600' : 'text-red-600'">
                                                Écart: <span x-text="getCaissesVidesEcart() + ' cs'"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-3 rounded-lg" :class="hasDiscrepancy() ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'">
                                        <p class="text-sm font-semibold" :class="hasDiscrepancy() ? 'text-red-700' : 'text-green-700'">
                                            <span x-show="hasDiscrepancy()">Des écarts sont détectés : une justification est requise.</span>
                                            <span x-show="!hasDiscrepancy()">Aucun écart détecté : la clôture est cohérente.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border p-4 bg-blue-50 border-blue-200" x-show="isRestourne">
                                <p class="text-sm font-semibold text-blue-800">Clôture de mission de ristourne</p>
                                <p class="text-sm text-blue-700 mt-1">La mission a déjà été préparée avec le produit et le montant livré. La clôture se limite à finaliser le statut de mission.</p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-8">
                            <button type="button" @click="isOpen = false" class="btn btn-secondary">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                <span x-show="!loading" x-text="isRestourne ? 'Clôturer la mission' : 'Valider le retour'"></span>
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
