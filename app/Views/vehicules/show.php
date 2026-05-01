<?php 
$pageTitle = 'Détail véhicule';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('vehicules') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux véhicules
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Infos véhicule -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-lg font-semibold"><?= htmlspecialchars($vehicule['immatriculation']) ?></h2>
                <div class="flex gap-2">
                    <?php if ($vehicule['actif']): ?>
                    <?php if ($vehicule['en_mission']): ?>
                    <span class="badge-warning">En mission</span>
                    <?php else: ?>
                    <span class="badge-success">Disponible</span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="badge-secondary">Inactif</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Marque</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($vehicule['marque'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Modèle</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($vehicule['modele'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Capacité</p>
                        <p class="font-medium text-gray-900 dark:text-white"><?= $vehicule['capacite'] ?? 0 ?> caisses</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Agent responsable</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? 'N/A')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stock actuel -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Stock actuel dans le véhicule</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($vehicule['stock'])): ?>
                <div class="p-6 text-center text-gray-500">Aucun stock</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-right">Caisses Pleines</th>
                                <th class="text-right">Caisses Vides</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicule['stock'] as $item): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($item['produit_nom']) ?></td>
                                <td class="text-right font-bold text-green-600"><?= $item['caisses_pleine'] ?></td>
                                <td class="text-right font-bold text-orange-600"><?= $item['caisses_vide'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <td class="font-bold">Total</td>
                                <td class="text-right font-bold text-green-600">
                                    <?= array_sum(array_column($vehicule['stock'], 'caisses_pleine')) ?> caisses
                                </td>
                                <td class="text-right font-bold text-orange-600">
                                    <?= array_sum(array_column($vehicule['stock'], 'caisses_vide')) ?> caisses
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Dernières missions -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Dernières missions</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($missions)): ?>
                <div class="p-6 text-center text-gray-500">Aucune mission enregistrée</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N° Mission</th>
                                <th>Date</th>
                                <th>Zone</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($missions as $mission): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($mission['numero_mission']) ?></td>
                                <td><?= date('d/m/Y', strtotime($mission['date_depart'])) ?></td>
                                <td><?= htmlspecialchars($mission['zone_nom'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($mission['statut'] === 'en_cours'): ?>
                                    <span class="badge-warning">En cours</span>
                                    <?php else: ?>
                                    <span class="badge-success">Terminée</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= url('missions/' . $mission['id']) ?>" class="text-primary-600 hover:text-primary-700">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                <p class="text-sm text-gray-500 dark:text-gray-400">Stock total</p>
                <p class="text-3xl font-bold text-primary-600">
                    <?= array_sum(array_column($vehicule['stock'] ?? [], 'caisses_pleine')) ?>
                </p>
                <p class="text-sm text-gray-500">caisses</p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Statistiques du mois</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Missions effectuées</p>
                    <p class="text-xl font-bold"><?= $stats['nb_missions'] ?? 0 ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Caisses livrées</p>
                    <p class="text-xl font-bold text-green-600"><?= $stats['total_livre'] ?? 0 ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Chiffre d'affaires</p>
                    <p class="text-xl font-bold text-primary-600">
                        <?= format_money_converted($stats['total_ca'] ?? 0) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
