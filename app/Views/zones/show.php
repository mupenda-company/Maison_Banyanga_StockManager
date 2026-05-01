<?php 
$pageTitle = 'Détail zone';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('zones') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux zones
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Infos zone -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-lg font-semibold"><?= htmlspecialchars($zone['nom']) ?></h2>
                <?php if ($zone['actif']): ?>
                <span class="badge-success">Active</span>
                <?php else: ?>
                <span class="badge-secondary">Inactive</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($zone['description'])): ?>
                <p class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($zone['description']) ?></p>
                <?php else: ?>
                <p class="text-gray-500 italic">Aucune description</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Clients de la zone -->
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold">Clients de cette zone</h3>
                <span class="text-sm text-gray-500"><?= count($clients) ?> client(s)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($clients)): ?>
                <div class="p-6 text-center text-gray-500">Aucun client dans cette zone</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>CA du mois</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('clients/' . $client['id']) ?>" class="text-primary-600 hover:underline font-medium">
                                        <?= htmlspecialchars($client['nom']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($client['telephone'] ?? '-') ?></td>
                                <td class="font-medium text-green-600">
                                    <?= format_money_converted($client['ca_mois'] ?? 0) ?>
                                </td>
                                <td>
                                    <?php if ($client['actif']): ?>
                                    <span class="badge-success">Actif</span>
                                    <?php else: ?>
                                    <span class="badge-secondary">Inactif</span>
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
        
        <!-- Dernières ventes -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Dernières ventes dans cette zone</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ventes)): ?>
                <div class="p-6 text-center text-gray-500">Aucune vente récente</div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N° Facture</th>
                                <th>Client</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventes as $vente): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($vente['date_vente'])) ?></td>
                                <td>
                                    <a href="<?= url('ventes/' . $vente['id']) ?>" class="text-primary-600 hover:underline">
                                        <?= htmlspecialchars($vente['numero_facture']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($vente['client_nom']) ?></td>
                                <td class="font-medium"><?= format_money_converted($vente['total_ttc'] ?? 0) ?></td>
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
        <!-- Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Statistiques du mois</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Chiffre d'affaires</p>
                    <p class="text-2xl font-bold text-primary-600">
                        <?= format_money_converted($stats['ca_mois'] ?? 0) ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Nombre de ventes</p>
                    <p class="text-2xl font-bold"><?= $stats['nb_ventes'] ?? 0 ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Bouteilles vendues</p>
                    <p class="text-2xl font-bold text-green-600"><?= $stats['total_bouteilles'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <!-- Top clients -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Top clients du mois</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topClients)): ?>
                <div class="p-6 text-center text-gray-500">Aucune donnée</div>
                <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($topClients as $i => $client): ?>
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-600 text-sm font-bold flex items-center justify-center">
                                <?= $i + 1 ?>
                            </span>
                            <span class="font-medium"><?= htmlspecialchars($client['nom']) ?></span>
                        </div>
                        <span class="font-bold text-green-600">
                            <?= format_money_converted($client['ca'] ?? 0) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
