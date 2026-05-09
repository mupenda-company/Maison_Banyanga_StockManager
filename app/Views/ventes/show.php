<?php 
$pageTitle = 'Détail vente';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('ventes') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux ventes
    </a>
</div>

<!-- Facture -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-lg font-semibold">Facture N° <?= htmlspecialchars($vente['numero_facture']) ?></h2>
                <div class="flex gap-2">
                    <a href="<?= url('ventes/' . $vente['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimer
                    </a>
                    <?php if ($vente['statut'] === 'validee' && in_array($_SESSION['user_role'], ['admin', 'magasinier'])): ?>
                    <button onclick="annulerVente()" class="btn btn-sm btn-danger">Annuler</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Client</p>
                            <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($vente['client_nom']) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($vente['client_telephone'] ?? '') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Date</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($vente['emplacement_nom'] ?? 'N/A') ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-right">Quantité (Caisses)</th>
                                <th class="text-right">Emballages reçus</th>
                                <th class="text-right">Dette</th>
                                <th class="text-right">Prix par Caisse</th>
                                <th class="text-right">Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vente['details'] as $detail): 
                                $btlParCaisse = (int)($detail['bouteilles_par_caisses'] ?? 24);
                                $caisses = intdiv((int)$detail['quantite'], $btlParCaisse);
                                $caissesVidesRecues = (int)($detail['caisses_vides_recues'] ?? 0);
                                $detteCaisses = max(0, $caisses - $caissesVidesRecues);
                                $prixCaisse = $detail['prix_unitaire'] * $btlParCaisse;
                            ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($detail['produit_nom']) ?></div>
                                    <div class="text-[10px] text-gray-500 font-mono"><?= htmlspecialchars($detail['produit_code']) ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="font-bold text-gray-900 dark:text-white"><?= number_format($caisses, 0, '.', ' ') ?> cs</div>
                                    <div class="text-[10px] text-gray-400 italic"><?= number_format($detail['quantite']) ?> btl</div>
                                </td>
                                <td class="text-right font-medium">
                                    <?= number_format($caissesVidesRecues, 0, '.', ' ') ?> cs
                                </td>
                                <td class="text-right font-bold <?= $detteCaisses > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= number_format($detteCaisses, 0, '.', ' ') ?> cs
                                </td>
                                <td class="text-right font-medium">
                                    <?= format_money_converted($prixCaisse) ?>
                                </td>
                                <td class="text-right font-bold text-primary-600">
                                    <?= format_money_converted($detail['sous_total']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <td colspan="5" class="text-right font-medium text-gray-600 dark:text-gray-400">Total HT</td>
                                <td class="text-right font-bold text-gray-900 dark:text-white"><?= format_money_converted($vente['total_ht'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-right font-medium text-gray-600 dark:text-gray-400">TVA (<?= number_format($vente['total_tva'] / ($vente['total_ht'] ?: 1) * 100, 0) ?>%)</td>
                                <td class="text-right font-bold text-gray-900 dark:text-white"><?= format_money_converted($vente['total_tva'] ?? 0) ?></td>
                            </tr>
                            <tr class="bg-primary-50 dark:bg-primary-900/20">
                                <td colspan="5" class="text-right font-bold text-lg text-primary-900 dark:text-primary-100 uppercase tracking-wider">Total TTC</td>
                                <td class="text-right font-bold text-xl text-primary-600 dark:text-primary-400"><?= format_money_converted($vente['total_ttc'] ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php
                    $totalCaissesVidesRecues = 0;
                    $totalDetteCaisses = 0;
                    foreach ($vente['details'] as $detail) {
                        $btlParCaisse = (int)($detail['bouteilles_par_caisses'] ?? 24);
                        $caisses = intdiv((int)$detail['quantite'], $btlParCaisse);
                        $caissesVidesRecues = (int)($detail['caisses_vides_recues'] ?? 0);
                        $totalCaissesVidesRecues += $caissesVidesRecues;
                        $totalDetteCaisses += max(0, $caisses - $caissesVidesRecues);
                    }
                ?>
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
                        <p class="text-xs uppercase text-gray-500">Emballages reçus</p>
                        <p class="text-2xl font-bold text-blue-600"><?= number_format($totalCaissesVidesRecues, 0, '.', ' ') ?> cs</p>
                    </div>
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4">
                        <p class="text-xs uppercase text-gray-500">Emballages dus</p>
                        <p class="text-2xl font-bold text-red-600"><?= number_format($totalDetteCaisses, 0, '.', ' ') ?> cs</p>
                    </div>
                </div>
                
                <?php if (!empty($vente['notes'])): ?>
                <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
                    <p class="text-gray-900 dark:text-white"><?= htmlspecialchars($vente['notes']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Statut -->
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Statut</p>
                <?php if ($vente['statut'] === 'validee'): ?>
                <span class="badge-success text-lg">Validée</span>
                <?php elseif ($vente['statut'] === 'annulee'): ?>
                <span class="badge-danger text-lg">Annulée</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informations -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Informations</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Créé par</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= htmlspecialchars(($vente['created_by_prenom'] ?? '') . ' ' . ($vente['created_by_nom'] ?? 'Système')) ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Emplacement</p>
                    <p class="font-medium"><?= htmlspecialchars($vente['emplacement_nom'] ?? 'N/A') ?></p>
                </div>
                <?php if (!empty($vente['annule_at'])): ?>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Annulée le</p>
                    <p class="font-medium text-red-600">
                        <?= date('d/m/Y H:i', strtotime($vente['annule_at'])) ?>
                    </p>
                    <p class="text-sm text-gray-500">par <?= htmlspecialchars($vente['annule_by_nom'] ?? 'N/A') ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function annulerVente() {
    const ok = await App.confirm({
        title: 'Annuler la vente ?',
        message: 'Êtes-vous sûr de vouloir annuler cette vente ? Le stock sera rétabli.',
        confirmText: 'Annuler',
        cancelText: 'Retour',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const result = await App.api(`/api/ventes/<?= $vente['id'] ?>/annuler`, 'POST');
        App.notify(result.message, 'success');
        setTimeout(() => location.href = '<?= url('ventes') ?>', 1500);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
