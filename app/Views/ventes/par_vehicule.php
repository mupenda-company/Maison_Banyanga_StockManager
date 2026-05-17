<?php 
$pageTitle = 'Ventes par véhicule';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ventes par véhicule</h1>
        <p class="text-gray-500 dark:text-gray-400">Historique des ventes par véhicule avec clients et produits</p>
    </div>
    <div class="flex gap-2">
        <?php if ($vehiculeId): ?>
        <a href="<?= url('ventes/par-vehicule/export?vehicule_id=' . $vehiculeId . '&date_debut=' . $dateDebut . '&date_fin=' . $dateFin) ?>" class="btn btn-success">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exporter CSV
        </a>
        <a href="<?= url('ventes/par-vehicule/print?vehicule_id=' . $vehiculeId . '&date_debut=' . $dateDebut . '&date_fin=' . $dateFin) ?>" target="_blank" class="btn btn-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimer
        </a>
        <?php endif; ?>
        <a href="<?= url('ventes') ?>" class="btn btn-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="label">Véhicule</label>
                <select name="vehicule_id" class="input" required>
                    <option value="">Sélectionner un véhicule</option>
                    <?php foreach ($vehicules as $veh): ?>
                    <option value="<?= $veh['id'] ?>" <?= ($vehiculeId ?? '') == $veh['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($veh['immatriculation']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-40">
                <label class="label">Du</label>
                <input type="date" name="date_debut" class="input" value="<?= $dateDebut ?>">
            </div>
            <div class="w-40">
                <label class="label">Au</label>
                <input type="date" name="date_fin" class="input" value="<?= $dateFin ?>">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary px-6">
                    Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($vehiculeId): ?>
<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ventes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= count($ventes) ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Clients</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= count($clients) ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total TTC</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= format_money_converted(array_sum(array_column($ventes, 'total_ttc'))) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clients -->
<div class="card mb-6">
    <div class="card-header">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Clients servis</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($clients)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Aucun client trouvé pour cette période</div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th>Zone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($client['nom']) ?></td>
                        <td><?= htmlspecialchars($client['telephone'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($client['adresse'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($client['zone_nom'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Products -->
<div class="card mb-6">
    <div class="card-header">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Produits vendus</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($produits)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Aucun produit vendu pour cette période</div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Code</th>
                        <th>Caisses</th>
                        <th>Bouteilles</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $prod): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($prod['nom']) ?></td>
                        <td><?= htmlspecialchars($prod['code'] ?? 'N/A') ?></td>
                        <td><?= number_format($prod['total_caisses'], 1) ?></td>
                        <td><?= number_format($prod['total_bouteilles'], 0) ?></td>
                        <td><?= format_money_converted($prod['total_montant']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sales List -->
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Détail des ventes</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ventes)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">Aucune vente trouvée pour cette période</div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Zone</th>
                        <th>Mission</th>
                        <th>Total TTC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventes as $vente): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($vente['numero_facture']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></td>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($vente['client_nom']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($vente['client_telephone'] ?? '') ?></div>
                        </td>
                        <td><?= htmlspecialchars($vente['zone_nom'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($vente['numero_mission'] ?? 'N/A') ?></td>
                        <td class="font-medium"><?= format_money_converted($vente['total_ttc']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
