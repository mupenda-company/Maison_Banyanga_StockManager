<?php 
$pageTitle = 'Calculer les ristournes';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('ristournes') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux ristournes
    </a>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="text-lg font-semibold">Calcul des ristournes</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Date début</label>
                <input type="date" name="periode_debut" class="input" value="<?= htmlspecialchars($periodeDebut) ?>">
            </div>
            <div>
                <label class="label">Date fin</label>
                <input type="date" name="periode_fin" class="input" value="<?= htmlspecialchars($periodeFin) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Calculer</button>
        </form>
    </div>
</div>

<!-- Paliers actifs -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="font-semibold">Paliers de ristourne actifs</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($paliers as $palier): ?>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                <p class="font-semibold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($palier['nom']) ?></p>
                <p class="text-sm text-gray-500">≥ <?= format_money_converted($palier['ca_min'] ?? 0) ?></p>
                <p class="text-2xl font-bold text-primary-600"><?= $palier['taux_ristourne'] ?>%</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Résultats -->
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-semibold">Résultats du calcul</h3>
        <?php if (!empty($ristournes)): ?>
        <button onclick="validerToutes()" class="btn btn-primary">
            Valider toutes les ristournes
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ristournes)): ?>
        <div class="p-12 text-center text-gray-500">
            Aucun client éligible pour cette période
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Total achats</th>
                        <th>Palier</th>
                        <th>Taux</th>
                        <th>Ristourne</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $totalRistournes = 0; ?>
                    <?php foreach ($ristournes as $r): ?>
                    <?php $totalRistournes += $r['montant_ristourne']; ?>
                    <tr>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($r['client_nom']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($r['zone_nom'] ?? '') ?></div>
                        </td>
                        <td class="font-medium"><?= format_money_converted($r['ca_total'] ?? 0) ?></td>
                        <td>
                            <span class="badge-info"><?= htmlspecialchars($r['palier_nom'] ?? 'Aucun') ?></span>
                        </td>
                        <td><?= $r['taux_applique'] ?>%</td>
                        <td class="font-bold text-green-600"><?= format_money_converted($r['montant_ristourne'] ?? 0) ?></td>
                        <td>
                            <?php if ($r['montant_ristourne'] > 0): ?>
                            <button onclick="validerRistourne(<?= $r['client_id'] ?>, <?= $r['montant_ristourne'] ?>, <?= $r['taux_applique'] ?>, '<?= $periodeDebut ?>', '<?= $periodeFin ?>')" 
                                class="btn btn-sm btn-primary">
                                Valider
                            </button>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700/50 font-bold">
                    <tr>
                        <td colspan="4">TOTAL</td>
                        <td class="text-green-600"><?= format_money_converted($totalRistournes ?? 0) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const periodeDebut = '<?= $periodeDebut ?>';
const periodeFin = '<?= $periodeFin ?>';
const ristournes = <?= json_encode($ristournes) ?>;

async function validerRistourne(clientId, montant, taux, dateDebut, dateFin) {
    const montantLabel = App.formatMoneyConverted(montant, window.BASE_DEVISE, window.DEVISE);
    const ok = await App.confirm({
        title: 'Valider la ristourne ?',
        message: `Confirmer la ristourne de ${montantLabel} pour ce client ?`,
        confirmText: 'Valider',
        cancelText: 'Annuler',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const result = await App.api('/api/ristournes', 'POST', {
            client_id: clientId,
            periode_debut: dateDebut,
            periode_fin: dateFin,
            ristournes: [{
                client_id: clientId,
                ca_total: montant / (taux / 100),
                palier_id: null,
                taux_applique: taux,
                montant_ristourne: montant
            }]
        });
        App.notify(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}

async function validerToutes() {
    const eligibles = ristournes.filter(r => r.montant_ristourne > 0);
    if (eligibles.length === 0) {
        App.notify('Aucune ristourne à valider', 'warning');
        return;
    }
    
    const total = eligibles.reduce((sum, r) => sum + r.montant_ristourne, 0);
    const totalLabel = App.formatMoneyConverted(total, window.BASE_DEVISE, window.DEVISE);
    const ok = await App.confirm({
        title: 'Valider toutes les ristournes ?',
        message: `Valider ${eligibles.length} ristourne(s) pour un total de ${totalLabel} ?`,
        confirmText: 'Valider tout',
        cancelText: 'Annuler',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const data = {
            periode_debut: periodeDebut,
            periode_fin: periodeFin,
            ristournes: eligibles.map(r => ({
                client_id: r.client_id,
                ca_total: r.ca_total,
                palier_id: r.palier_id,
                taux_applique: r.taux_applique,
                montant_ristourne: r.montant_ristourne
            }))
        };
        await App.api('/api/ristournes', 'POST', data);
        App.notify('Toutes les ristournes ont été validées', 'success');
        setTimeout(() => location.href = '<?= url('ristournes') ?>', 1500);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
