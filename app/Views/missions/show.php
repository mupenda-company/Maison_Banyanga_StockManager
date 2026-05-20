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
                    <?php if ($mission['statut'] === 'en_cours' && can('missions.update')): ?>
                    <a href="<?= url('missions/' . $mission['id'] . '/edit') ?>" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier
                    </a>
                    <?php endif; ?>
                    <a href="<?= url('missions/' . $mission['id'] . ($mission['statut'] === 'terminee' ? '/facture' : '/print')) ?>" target="_blank" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <?= $isRestourne ? 'Bon de ristourne' : ($mission['statut'] === 'terminee' ? 'Facture de mission' : 'Bon de sortie') ?>
                    </a>
                    <?php if ($mission['statut'] === 'en_cours' && can('missions.manage')): ?>
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
                                <th class="text-right">Stock initial</th>
                                <th class="text-right">Variation mission</th>
                                <th class="text-right">Stock final</th>
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
                                $totalReelCaisses = (int) ($item['caisses_total'] ?? max(0, (int) ($item['quantite_caisses'] ?? 0)));
                                $ajustementMissionCaisses = $totalReelCaisses - $stockDepartCaisses;
                            ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($item['produit_nom']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($item['produit_code']) ?></div>
                                </td>
                                <td class="text-right"><?= number_format($stockDepartCaisses, 0, '.', ' ') ?> cs</td>
                                <td class="text-right<?= $ajustementMissionCaisses < 0 ? ' text-red-600 font-semibold' : '' ?>"><?= number_format($ajustementMissionCaisses, 0, '.', ' ') ?> cs</td>
                                <td class="text-right font-semibold text-primary-600"><?= number_format($totalReelCaisses, 0, '.', ' ') ?> cs</td>
                                <td class="text-right"><?= format_money_converted($prixCaisse) ?></td>
                                <td class="text-right font-medium"><?= format_money_converted($item['sous_total'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <td colspan="3" class="text-right font-bold">Total stock final</td>
                                <td class="text-right font-bold text-primary-600">
                                    <?= number_format((int) ($mission['total_caisses'] ?? 0), 0, '.', ' ') ?> cs
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
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?= $isRestourne ? 'Caisses livrées' : 'Caisses pleines restantes' ?>
                </p>
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
            justification_cloture: '',
            isRestourne: <?= $isRestourne ? 'true' : 'false' ?>,
            async submit() {
                const ok = await App.confirm({
                    title: 'Clôturer la mission ?',
                    message: 'Confirmer la clôture automatique de la mission ?',
                    confirmText: 'Clôturer',
                    cancelText: 'Annuler',
                    type: 'warning'
                });
                if (!ok) return;
                this.loading = true;
                try {
                    const payload = {
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
                        <h3 class="text-lg font-semibold">Clôture automatique de la mission N° <?= htmlspecialchars($mission['numero_mission']) ?></h3>
                        <p class="text-xs text-gray-500 mt-1">Les retours pleins et les caisses vides sont calculés automatiquement à partir des ventes validées.</p>
                    </div>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-gray-500 rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit()">
                        <div class="space-y-6">
                            <div class="rounded-xl border bg-blue-50 border-blue-200 p-4">
                                <p class="text-sm font-semibold text-blue-800">Résumé automatique</p>
                                <p class="text-xs text-blue-700 mt-1">Aucune saisie de retour n’est nécessaire. Le système complète la clôture à partir des ventes validées de la mission.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 border rounded-lg bg-white dark:bg-gray-800">
                                    <p class="text-[11px] uppercase text-gray-500">Total chargé</p>
                                    <p class="text-lg font-bold text-primary-600"><?= (int) ($mission['total_caisses'] ?? 0) ?> cs</p>
                                </div>
                                <div class="p-4 border rounded-lg bg-white dark:bg-gray-800">
                                    <p class="text-[11px] uppercase text-gray-500">Total vendu</p>
                                    <p class="text-lg font-bold text-green-600"><?= (int) ($mission['caisses_vendues_total'] ?? 0) ?> cs</p>
                                </div>
                                <div class="p-4 border rounded-lg bg-white dark:bg-gray-800">
                                    <p class="text-[11px] uppercase text-gray-500">Retours pleins automatiques</p>
                                    <p class="text-lg font-bold text-orange-500"><?= max(0, (int) ($mission['total_caisses'] ?? 0) - (int) ($mission['caisses_vendues_total'] ?? 0)) ?> cs</p>
                                </div>
                                <div class="p-4 border rounded-lg bg-white dark:bg-gray-800">
                                    <p class="text-[11px] uppercase text-gray-500">Caisses vides retournées</p>
                                    <p class="text-lg font-bold text-purple-600"><?= (int) ($mission['caisses_vides_recues_total'] ?? 0) ?> cs</p>
                                </div>
                                <div class="p-4 border rounded-lg bg-white dark:bg-gray-800 md:col-span-2">
                                    <p class="text-[11px] uppercase text-gray-500">Montant attendu</p>
                                    <p class="text-lg font-bold text-primary-600"><?= number_format((float) ($mission['montant_attendu'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars($mission['devise_base'] ?? 'CDF') ?></p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Détail automatique par gamme</p>
                                    <p class="text-xs text-gray-500 mt-1">Les quantités de retour sont calculées automatiquement à partir des ventes validées, sans saisie manuelle.</p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-white dark:bg-gray-800">
                                            <tr class="text-left text-[11px] uppercase tracking-wide text-gray-500">
                                                <th class="px-4 py-3">Produit</th>
                                                <th class="px-4 py-3 text-right">Parti avec</th>
                                                <th class="px-4 py-3 text-right">Vendu</th>
                                                <th class="px-4 py-3 text-right">À remettre plein</th>
                                                <th class="px-4 py-3 text-right">À remettre vide</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                            <?php foreach ($mission['chargements'] as $item): ?>
                                            <?php
                                                $partiAvec = (int) ($item['caisses_total'] ?? 0);
                                                $venduAuto = (int) ($item['caisses_vendues_auto'] ?? $item['caisses_vendues'] ?? 0);
                                                $aRemettrePlein = (int) ($item['caisses_a_remettre_pleines'] ?? max(0, $partiAvec - $venduAuto));
                                                $aRemettreVide = (int) ($item['caisses_a_remettre_vides'] ?? 0);
                                            ?>
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['produit_nom']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($item['produit_code']) ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white"><?= number_format($partiAvec, 0, '.', ' ') ?> cs</td>
                                                <td class="px-4 py-3 text-right font-medium text-green-600"><?= number_format($venduAuto, 0, '.', ' ') ?> cs</td>
                                                <td class="px-4 py-3 text-right font-medium text-orange-600"><?= number_format($aRemettrePlein, 0, '.', ' ') ?> cs</td>
                                                <td class="px-4 py-3 text-right font-medium text-purple-600"><?= number_format($aRemettreVide, 0, '.', ' ') ?> cs</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <label class="label">Justification de clôture</label>
                                <textarea x-model="justification_cloture" class="input" rows="4" placeholder="Facultatif"></textarea>
                                <p class="text-xs text-gray-500 mt-2">À utiliser seulement si vous souhaitez ajouter une note interne.</p>
                            </div>

                            <?php if ($isRestourne): ?>
                            <div class="rounded-xl border p-4 bg-blue-50 border-blue-200">
                                <p class="text-sm font-semibold text-blue-800">Clôture de mission de ristourne</p>
                                <p class="text-sm text-blue-700 mt-1">La mission a déjà été préparée avec le produit et le montant livré. La clôture se limite à finaliser le statut de mission.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-end space-x-3 mt-8">
                            <button type="button" @click="isOpen = false" class="btn btn-secondary">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                <span x-show="!loading" x-text="isRestourne ? 'Clôturer la mission' : 'Clôturer automatiquement'"></span>
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
