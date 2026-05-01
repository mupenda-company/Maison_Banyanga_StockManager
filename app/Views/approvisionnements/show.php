<?php 
$pageTitle = 'Détail approvisionnement';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('approvisionnements') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux approvisionnements
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Infos approvisionnement -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-lg font-semibold">Approvisionnement N° <?= htmlspecialchars($approvisionnement['numero_bon'] ?? $approvisionnement['id']) ?></h2>
                <div class="flex gap-2">
                    <?php if ($approvisionnement['statut'] === 'en_attente'): ?>
                    <button onclick="valider()" class="btn btn-sm btn-primary">Valider la réception</button>
                    <button onclick="annuler()" class="btn btn-sm btn-danger">Annuler</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Date</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= date('d/m/Y', strtotime($approvisionnement['date_approvisionnement'])) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Fournisseur</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars($approvisionnement['fournisseur'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Emplacement</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <?= htmlspecialchars($approvisionnement['emplacement_nom'] ?? 'Entrepôt Principal') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Statut</p>
                        <?php if ($approvisionnement['statut'] === 'valide'): ?>
                        <span class="badge-success">Validé</span>
                        <?php elseif ($approvisionnement['statut'] === 'en_attente'): ?>
                        <span class="badge-warning">En attente</span>
                        <?php else: ?>
                        <span class="badge-danger">Annulé</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($approvisionnement['notes'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Notes</p>
                    <p class="text-gray-900 dark:text-white"><?= htmlspecialchars($approvisionnement['notes']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Détails des produits -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Produits reçus</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-right">Caisses</th>
                                <th class="text-right">Bouteilles</th>
                                <th class="text-right">Prix Caisse</th>
                                <th class="text-right">Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvisionnement['details'] as $detail): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($detail['produit_nom']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($detail['produit_code']) ?></div>
                                </td>
                                <td class="text-right"><?= $detail['quantite_caisses'] ?></td>
                                <td class="text-right"><?= ($detail['quantite_caisses'] * ($detail['bouteilles_par_caisses'] ?? 24)) ?></td>
                                <td class="text-right"><?= format_money_converted($detail['prix_unitaire'] * ($detail['bouteilles_par_caisses'] ?? 24)) ?></td>
                                <td class="text-right font-medium"><?= format_money_converted($detail['sous_total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                            <?php 
                                $totalGeneral = 0;
                                foreach ($approvisionnement['details'] as $d) {
                                    $totalGeneral += $d['sous_total'];
                                }
                            ?>
                            <tr>
                                <td colspan="4" class="text-right font-bold">Total Général</td>
                                <td class="text-right font-bold text-primary-600">
                                    <?= format_money_converted($totalGeneral) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Résumé -->
        <div class="card">
            <div class="card-body text-center">
                <?php 
                    $totalCaisses = 0;
                    foreach ($approvisionnement['details'] as $d) {
                        $totalCaisses += $d['quantite_caisses'];
                    }
                ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Caisses</p>
                <p class="text-3xl font-bold text-primary-600">
                    <?= $totalCaisses ?>
                </p>
                <p class="text-sm text-gray-500">caisses pleines</p>
            </div>
        </div>
        
        <!-- Paiement -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold">Informations</h3>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Valeur totale du stock</p>
                        <p class="text-xl font-bold"><?= format_money_converted($totalGeneral) ?></p>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Enregistré par</p>
                        <p class="font-medium"><?= htmlspecialchars($approvisionnement['utilisateur_nom'] ?? $approvisionnement['created_by_nom'] ?? 'Système') ?></p>
                        <p class="text-xs text-gray-500">
                            <?= date('d/m/Y à H:i', strtotime($approvisionnement['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function valider() {
    const ok = await App.confirm({
        title: 'Valider la réception ?',
        message: 'Valider la réception de cet approvisionnement ? Le stock sera mis à jour.',
        confirmText: 'Valider',
        cancelText: 'Annuler',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const result = await App.api('/api/approvisionnements/<?= $approvisionnement['id'] ?>/valider', 'POST');
        App.notify(result.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}

async function annuler() {
    const ok = await App.confirm({
        title: 'Annuler l\'approvisionnement ?',
        message: 'Annuler cet approvisionnement ?',
        confirmText: 'Annuler',
        cancelText: 'Retour',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const result = await App.api('/api/approvisionnements/<?= $approvisionnement['id'] ?>/annuler', 'POST');
        App.notify(result.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
