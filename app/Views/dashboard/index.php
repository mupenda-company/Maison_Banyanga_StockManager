<?php 
$pageTitle = 'Tableau de bord';
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Ventes du jour -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Ventes aujourd'hui</p>
                <p class="text-lg md:text-xl font-bold text-green-600 dark:text-green-400 truncate" title="<?= format_money_converted($statsToday['total_ttc'] ?? 0) ?>">
                    <?= format_money_converted($statsToday['total_ttc'] ?? 0) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= $statsToday['nb_ventes'] ?? 0 ?> vente(s)
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <?= number_format((int) round($statsToday['caisses_vendues'] ?? 0), 0, '.', ' ') ?> caisse(s) vendue(s)
        </p>
    </div>
    
    <!-- Ventes du mois -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
                <p class="stat-label">Ventes ce mois</p>
                <p class="text-lg md:text-xl font-bold text-blue-600 dark:text-blue-400 truncate" title="<?= format_money_converted($statsMonth['total_ttc'] ?? 0) ?>">
                    <?= format_money_converted($statsMonth['total_ttc'] ?? 0) ?>
                </p>
            </div>
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= $statsMonth['nb_ventes'] ?? 0 ?> vente(s)
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <?= number_format((int) round($statsMonth['caisses_vendues'] ?? 0), 0, '.', ' ') ?> caisse(s) vendue(s)
        </p>
    </div>
    
    <!-- Missions en cours -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label">Missions en cours</p>
                <p class="stat-value text-yellow-600 dark:text-yellow-400">
                    <?= count($missionsEnCours) ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            Véhicules en déplacement
        </p>
    </div>
    
    <!-- Alertes -->
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label">Alertes stock</p>
                <p class="stat-value text-red-600 dark:text-red-400">
                    <?= $nbProduitsAlerte ?? count($produitsAlerte) ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            Produits sous le seuil
        </p>
    </div>
</div>

<!-- Stock global -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="stat-card">
        <p class="stat-label">Stock plein total</p>
        <p class="stat-value text-green-600 dark:text-green-400">
            <?= number_format((int) ($stockTotaux['caisses_pleine'] ?? 0), 0, ',', ' ') ?> cs
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= number_format((int) ($stockTotaux['pleines'] ?? 0), 0, ',', ' ') ?> bouteille(s)
        </p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Stock emballage total</p>
        <p class="stat-value text-gray-700 dark:text-gray-200">
            <?= number_format((int) ($stockTotaux['caisses_vide'] ?? 0), 0, ',', ' ') ?> cs
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <?= number_format((int) ($stockTotaux['vides'] ?? 0), 0, ',', ' ') ?> bouteille(s) vides
        </p>
    </div>
</div>

<?php
    $objectifDefaultSummary = [
        'objectif_total' => 0,
        'realise_total' => 0,
        'vendu_total' => 0,
        'approvisionnement_total' => 0,
        'reste_total' => 0,
        'progression' => 0,
        'nb_produits' => 0,
    ];

    $objectifCards = [
        [
            'title' => 'Objectif vente',
            'description' => 'Suivi des caisses a vendre pour le mois en cours.',
            'realise_label' => 'Vendu ce mois',
            'reste_label' => 'Reste a vendre',
            'link' => url('admin/objectifs?type=vente'),
            'summary' => $objectifVenteMois['summary'] ?? ($objectifMois['summary'] ?? $objectifDefaultSummary),
            'accent' => 'text-green-600',
            'fallback_key' => 'vendu_total',
        ],
        [
            'title' => 'Objectif approvisionnement',
            'description' => 'Suivi des caisses a approvisionner pour le mois en cours.',
            'realise_label' => 'Approvisionne ce mois',
            'reste_label' => 'Reste a approvisionner',
            'link' => url('admin/objectifs?type=approvisionnement'),
            'summary' => $objectifApprovisionnementMois['summary'] ?? $objectifDefaultSummary,
            'accent' => 'text-blue-600',
            'fallback_key' => 'approvisionnement_total',
        ],
    ];

    $objectifSummary = array_merge($objectifDefaultSummary, $objectifCards[0]['summary'] ?? []);
?>

<?php
    $objectifApproSummary = array_merge($objectifDefaultSummary, $objectifCards[1]['summary'] ?? []);
    $approRealiseTotal = (int) ($objectifApproSummary['realise_total'] ?? 0);
    if ($approRealiseTotal === 0) {
        $approRealiseTotal = (int) ($objectifApproSummary['approvisionnement_total'] ?? 0);
    }
?>

<div class="card mb-8">
    <div class="card-header flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Objectif approvisionnement du mois</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Suivi des caisses a approvisionner pour le mois en cours.</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase text-gray-500">Progression</p>
            <p class="text-2xl font-bold text-primary-600"><?= number_format((float) ($objectifApproSummary['progression'] ?? 0), 1, ',', ' ') ?>%</p>
        </div>
    </div>
    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <p class="stat-label">Objectif total</p>
                <p class="stat-value text-blue-600"><?= number_format((int) ($objectifApproSummary['objectif_total'] ?? 0), 0, ',', ' ') ?> cs</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Approvisionne ce mois</p>
                <p class="stat-value text-blue-600"><?= number_format($approRealiseTotal, 0, ',', ' ') ?> cs</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Reste a approvisionner</p>
                <p class="stat-value text-orange-600"><?= number_format((int) ($objectifApproSummary['reste_total'] ?? 0), 0, ',', ' ') ?> cs</p>
            </div>
        </div>

        <div>
            <div class="w-full h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full bg-primary-600" style="width: <?= min(100, (float) ($objectifApproSummary['progression'] ?? 0)) ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <?= (int) ($objectifApproSummary['nb_produits'] ?? 0) ?> produit(s) suivis pour le mois en cours.
            </p>
        </div>

        <?php if (can('objectifs.voir')): ?>
        <div class="flex justify-end">
            <a href="<?= url('admin/objectifs?type=approvisionnement') ?>" class="btn btn-secondary">Gérer les objectifs</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-8">
    <div class="card-header flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Objectif vente du mois</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Suivi des caisses à vendre pour le mois en cours.</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase text-gray-500">Progression</p>
            <p class="text-2xl font-bold text-primary-600"><?= number_format((float) ($objectifSummary['progression'] ?? 0), 1, ',', ' ') ?>%</p>
        </div>
    </div>
    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <p class="stat-label">Objectif total</p>
                <p class="stat-value text-blue-600"><?= number_format((int) ($objectifSummary['objectif_total'] ?? 0), 0, ',', ' ') ?> cs</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Vendu ce mois</p>
                <p class="stat-value text-green-600"><?= number_format((int) ($objectifSummary['vendu_total'] ?? 0), 0, ',', ' ') ?> cs</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Reste à vendre</p>
                <p class="stat-value text-orange-600"><?= number_format((int) ($objectifSummary['reste_total'] ?? 0), 0, ',', ' ') ?> cs</p>
            </div>
        </div>

        <div>
            <div class="w-full h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full bg-primary-600" style="width: <?= min(100, (float) ($objectifSummary['progression'] ?? 0)) ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <?= (int) ($objectifSummary['nb_produits'] ?? 0) ?> produit(s) suivis pour le mois en cours.
            </p>
        </div>

        <?php if (can('objectifs.voir')): ?>
        <div class="flex justify-end">
            <a href="<?= url('admin/objectifs?type=vente') ?>" class="btn btn-secondary">Gérer les objectifs</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-8">
    <div class="card-header flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Clients du mois</h2>
        <a href="<?= url('clients') ?>" class="text-sm text-primary-600 hover:text-primary-700">Voir les clients</a>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <p class="stat-label">Clients servis</p>
                <p class="stat-value text-primary-600 dark:text-primary-400">
                    <?= $clientsStats['clients_count'] ?? 0 ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Clients distincts sur le mois</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Dernier client</p>
                <p class="font-semibold text-gray-900 dark:text-white truncate" title="<?= htmlspecialchars($clientsStats['last_client'] ?? 'Aucun client') ?>">
                    <?= htmlspecialchars($clientsStats['last_client'] ?? 'Aucun client') ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 truncate">
                    <?= htmlspecialchars($clientsStats['last_zone'] ?? 'N/A') ?>
                </p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Contact</p>
                <p class="font-semibold text-gray-900 dark:text-white truncate" title="<?= htmlspecialchars($clientsStats['last_phone'] ?? '') ?>">
                    <?= htmlspecialchars($clientsStats['last_phone'] ?: 'Non renseigné') ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 truncate" title="<?= htmlspecialchars($clientsStats['last_address'] ?? '') ?>">
                    <?= htmlspecialchars($clientsStats['last_address'] ?: 'Adresse non renseignée') ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Produits en alerte -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Produits en alerte</h2>
            <a href="<?= url('stocks') ?>" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($produitsAlerte)): ?>
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>Aucun produit en alerte</p>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Stock</th>
                            <th>Seuil</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produitsAlerte as $produit): ?>
                        <tr>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($produit['nom']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($produit['code']) ?></div>
                            </td>
                            <td><?= $produit['stock_plein'] ?></td>
                            <td><?= $produit['seuil_alerte'] ?></td>
                            <td>
                                <?php if ($produit['stock_plein'] <= 0): ?>
                                <span class="badge-danger">Rupture</span>
                                <?php else: ?>
                                <span class="badge-warning">Critique</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Missions en cours -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Missions en cours</h2>
            <a href="<?= url('missions') ?>" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($missionsEnCours)): ?>
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p>Aucune mission en cours</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($missionsEnCours as $mission): ?>
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($mission['numero_mission']) ?>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Véhicule: <?= htmlspecialchars($mission['immatriculation']) ?> 
                                - <?= htmlspecialchars($mission['agent_nom'] ?? 'N/A') ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= date('d/m/Y H:i', strtotime($mission['date_depart'])) ?>
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($mission['zone_nom'] ?? 'Non définie') ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top ventes du mois -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Top ventes du mois</h2>
            <a href="<?= url('ventes/stats') ?>" class="text-sm text-primary-600 hover:text-primary-700">Statistiques</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ventesParProduit)): ?>
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                Aucune vente ce mois
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($ventesParProduit, 0, 5) as $vente): ?>
                        <tr>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($vente['nom']) ?></div>
                            </td>
                            <td>
                                <?php
                                    $totalCaissesVente = (float)($vente['total_caisses'] ?? 0);
                                    $btlParCaisseVente = 24;
                                    $totalBouteillesVente = round($totalCaissesVente * $btlParCaisseVente);
                                    $caissesPleinesVente = intdiv($totalBouteillesVente, $btlParCaisseVente);
                                    $bouteillesResteVente = $totalBouteillesVente % $btlParCaisseVente;
                                    if ($caissesPleinesVente > 0 && $bouteillesResteVente > 0) {
                                        echo $caissesPleinesVente . ' cs + ' . $bouteillesResteVente . ' btl';
                                    } elseif ($caissesPleinesVente > 0) {
                                        echo $caissesPleinesVente . ' cs';
                                    } else {
                                        echo $totalBouteillesVente . ' btl';
                                    }
                                ?>
                            </td>
                            <td class="font-medium">
                                <?= format_money_converted($vente['total_vente'] ?? 0) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Derniers mouvements -->
    <div class="card flex flex-col h-auto">
        <div class="card-header flex items-center justify-between shrink-0">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Derniers mouvements</h2>
            <a href="<?= url('stocks/mouvements') ?>" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
        </div>
        <div class="card-body p-0 flex-grow">
            <?php if (empty($derniersMouvements)): ?>
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                Aucun mouvement récent
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($derniersMouvements as $mvt): ?>
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <?php
                                $isSaleMovement = ($mvt['reference_type'] ?? null) === 'vente';
                                if ($isSaleMovement && $mvt['type_mouvement'] === 'sortie') {
                                    $movementLabel = 'plein';
                                    $movementClass = 'bg-red-100 text-red-600';
                                } elseif ($isSaleMovement && $mvt['type_mouvement'] === 'entree') {
                                    $movementLabel = 'vide';
                                    $movementClass = 'bg-green-100 text-green-600';
                                } elseif ($mvt['type_mouvement'] === 'entree') {
                                    $movementLabel = 'Entrée';
                                    $movementClass = 'bg-green-100 text-green-600';
                                } elseif ($mvt['type_mouvement'] === 'sortie') {
                                    $movementLabel = 'Sortie';
                                    $movementClass = 'bg-red-100 text-red-600';
                                } else {
                                    $movementLabel = 'Transfert';
                                    $movementClass = 'bg-blue-100 text-blue-600';
                                }
                            ?>
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 <?= $movementClass ?>">
                                <?php if ($mvt['type_mouvement'] === 'entree'): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                <?php elseif ($mvt['type_mouvement'] === 'sortie'): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                <?php else: ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate">
                                    <?= htmlspecialchars($movementLabel) ?> — <?= htmlspecialchars($mvt['produit_nom']) ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    <?= htmlspecialchars($mvt['emplacement_nom']) ?>
                                </p>
                                <?php if (!empty($mvt['motif'])): ?>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-1">
                                    <?= htmlspecialchars($mvt['motif']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold
                                <?= $mvt['quantite'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?php 
                                    $caisses = isset($mvt['quantite_caisses_reference']) && $mvt['quantite_caisses_reference'] !== null
                                        ? (float) $mvt['quantite_caisses_reference']
                                        : ($mvt['quantite'] / ($mvt['bouteilles_par_caisses'] ?: 24));
                                    echo ($mvt['quantite'] > 0 ? '+' : '') . number_format($caisses, 1, '.', '') . ' cs';
                                ?>
                            </p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?= date('d/m H:i', strtotime($mvt['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
