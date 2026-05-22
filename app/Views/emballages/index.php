<?php 
$pageTitle = 'Tableau de bord emballages';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de bord emballages</h1>
        <p class="text-gray-500 dark:text-gray-400">Vue synthétique des retours de vides et des dettes d'emballages</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="<?= url('emballages/suivi') ?>" class="btn btn-secondary">Suivi détaillé</a>
        <?php if (can('emballages.gerer')): ?>
        <a href="<?= url('retours-emballages') ?>" class="btn btn-primary">Nouveau retour</a>
        <?php endif; ?>
    </div>
</div>

<form method="GET" class="flex items-center gap-3 mb-6">
    <input type="date" name="date_debut" value="<?= htmlspecialchars($dateDebut) ?>" class="input py-1 px-2 text-sm">
    <span class="text-gray-400">au</span>
    <input type="date" name="date_fin" value="<?= htmlspecialchars($dateFin) ?>" class="input py-1 px-2 text-sm">
    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
</form>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <p class="stat-label">Retours enregistrés</p>
        <p class="stat-value text-primary-600"><?= (int) ($statsRetours['resume']['nb_retours'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= (int) ($statsRetours['resume']['nb_clients'] ?? 0) ?> client(s)</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Caisses retournées</p>
        <p class="stat-value text-blue-600"><?= number_format((float) ($statsRetours['resume']['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsRetours['resume']['total_bouteilles'] ?? 0), 0, ',', ' ') ?> bouteilles</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dette calculée</p>
        <p class="stat-value text-orange-600"><?= number_format((float) ($clientsEmballage['total_dette'] ?? 0), 0, ',', ' ') ?> cs</p>
        <p class="text-xs text-gray-400 mt-1">Depuis les ventes et retours</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Dettes manuelles</p>
        <p class="stat-value text-green-600"><?= (int) ($statsDettes['nb_dettes'] ?? 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format((float) ($statsDettes['caisses_restantes'] ?? 0), 0, ',', ' ') ?> caisses restantes</p>
    </div>
</div>

<div class="card mb-8">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-bold">Détail des dettes d'emballages par client et produit</h3>
        <span class="text-xs text-gray-400">Période filtrée</span>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Produit</th>
                        <th class="text-right">Vendu</th>
                        <th class="text-right">Reçu</th>
                        <th class="text-right">Retourné</th>
                        <th class="text-right">Dette</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientsEmballage['lignes'] ?? [])): ?>
                        <tr><td colspan="6" class="text-center p-4 text-gray-500">Aucune dette d'emballage sur la période</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($clientsEmballage['lignes'], 0, 10) as $ligne): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($ligne['client_nom']) ?></div>
                                    <div class="text-[10px] text-gray-400"><?= htmlspecialchars($ligne['zone_nom'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($ligne['produit_nom']) ?></div>
                                    <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($ligne['produit_code'] ?? '') ?></div>
                                </td>
                                <td class="text-right"><?= number_format((int) $ligne['caisses_vendues'], 0, ',', ' ') ?> cs</td>
                                <td class="text-right"><?= number_format((int) $ligne['caisses_vides_recues'], 0, ',', ' ') ?> cs</td>
                                <td class="text-right"><?= number_format((int) $ligne['caisses_retournees'], 0, ',', ' ') ?> cs</td>
                                <td class="text-right font-bold text-red-600"><?= number_format((int) $ligne['dette_caisses'], 0, ',', ' ') ?> cs</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Top produits retournés</h3>
            <span class="text-xs text-gray-400">Période filtrée</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Retours</th>
                            <th class="text-right">Caisses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statsRetours['top_produits'] ?? [])): ?>
                            <tr><td colspan="3" class="text-center p-4 text-gray-500">Aucune donnée</td></tr>
                        <?php else: ?>
                            <?php foreach ($statsRetours['top_produits'] as $p): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($p['nom']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($p['code']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold"><?= (int) $p['nb_retours'] ?></span>
                                    </td>
                                    <td class="text-right font-bold text-blue-700"><?= number_format((float) $p['total_caisses'], 0, ',', ' ') ?> cs</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="font-bold">Dettes d'emballages en cours</h3>
            <span class="text-xs text-gray-400">Remboursements</span>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Approvisionnement</th>
                            <th class="text-right">Reste</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dettesEnCours)): ?>
                            <tr><td colspan="3" class="text-center p-4 text-gray-500">Aucune dette en cours</td></tr>
                        <?php else: ?>
                            <?php foreach ($dettesEnCours as $dette): ?>
                                <?php $reste = (int) (($dette['quantite_dette_caisses'] ?? 0) - ($dette['quantite_remboursee'] ?? 0)); ?>
                                <tr>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($dette['produit_nom'] ?? 'N/A') ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($dette['produit_code'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="font-medium"><?= htmlspecialchars($dette['numero_bon'] ?? 'N/A') ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($dette['fournisseur'] ?? '') ?></div>
                                    </td>
                                    <td class="text-right font-bold text-red-600"><?= number_format($reste, 0, ',', ' ') ?> cs</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-8">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-bold">Retours récents</h3>
        <a href="<?= url('emballages/suivi') ?>" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Produit</th>
                        <th class="text-right">Caisses</th>
                        <th>Réceptionné à</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retoursRecents)): ?>
                        <tr><td colspan="5" class="text-center p-4 text-gray-500">Aucun retour récent</td></tr>
                    <?php else: ?>
                        <?php foreach ($retoursRecents as $r): ?>
                            <?php
                                $btlParCaisse = (int) ($r['bouteilles_par_caisses'] ?? 24);
                                if ($btlParCaisse <= 0) {
                                    $btlParCaisse = 24;
                                }
                                $caisses = $r['quantite'] / $btlParCaisse;
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($r['date_retour'])) ?></td>
                                <td class="font-medium"><?= htmlspecialchars($r['client_nom']) ?></td>
                                <td><?= htmlspecialchars($r['produit_nom']) ?></td>
                                <td class="text-right font-bold text-blue-700"><?= number_format($caisses, 1, ',', ' ') ?> cs</td>
                                <td><?= htmlspecialchars($r['emplacement_nom']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
