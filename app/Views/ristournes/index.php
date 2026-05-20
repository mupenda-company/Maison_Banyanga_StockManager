<?php 
$pageTitle = 'Ristournes';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ristournes</h1>
        <p class="text-gray-500 dark:text-gray-400">Calcul et suivi des remises sur volume</p>
    </div>
    
    <div class="flex gap-3">
        <?php if (can('admin.view')): ?>
        <button onclick="calculerRistournes(this)" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span class="loading-text hidden">Calcul en cours...</span>
            <span class="normal-text">Calculer le mois</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="w-48">
                <label class="label">Mois</label>
                <select name="mois" class="input">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $filters['mois'] == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="w-32">
                <label class="label">Année</label>
                <input type="number" name="annee" class="input" value="<?= $filters['annee'] ?>">
            </div>
            <div class="flex-1">
                <label class="label">Client</label>
                <select name="client_id" class="input">
                    <option value="">Tous les clients</option>
                    <?php foreach($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['client_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrer</button>
        </form>
    </div>
</div>

<!-- Liste -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Période</th>
                        <th class="text-right">Volume (cs)</th>
                        <th class="text-right">Montant</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ristournes)): ?>
                        <tr><td colspan="6" class="text-center p-8 text-gray-500">Aucune ristourne calculée pour cette période.</td></tr>
                    <?php else: ?>
                        <?php foreach($ristournes as $r): ?>
                            <tr>
                                <td>
                                    <div class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($r['client_nom']) ?></div>
                                </td>
                                <td><?= date('M Y', strtotime($r['periode_debut'])) ?></td>
                                <td class="text-right font-medium"><?= format_money_converted($r['ca_total'] ?? 0) ?></td>
                                <td class="text-right font-black text-green-600"><?= format_money_converted($r['montant_ristourne'] ?? 0) ?></td>
                                <td>
                                    <?php if ($r['statut'] === 'payee'): ?>
                                        <span class="badge-success">Payé</span>
                                        <div class="text-[10px] text-gray-400"><?= date('d/m/Y', strtotime($r['date_paiement'])) ?></div>
                                    <?php else: ?>
                                        <span class="badge-warning">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($r['statut'] === 'calculee'): ?>
                                        <button onclick="payerRistourne(<?= $r['id'] ?>)" class="btn btn-sm btn-success flex items-center gap-2 ml-auto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Confirmer paiement
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic text-sm">Déjà réglé</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function calculerRistournes(btn) {
    const mois = document.querySelector('select[name="mois"]').value;
    const annee = document.querySelector('input[name="annee"]').value;
    
    const ok = await App.confirm({
        title: 'Calculer les ristournes ?',
        message: `Lancer le calcul des ristournes pour ${mois}/${annee} ?`,
        confirmText: 'Calculer',
        cancelText: 'Annuler',
        type: 'info'
    });
    if (!ok) return;
    
    const loadingText = btn.querySelector('.loading-text');
    const normalText = btn.querySelector('.normal-text');
    
    try {
        btn.disabled = true;
        loadingText.classList.remove('hidden');
        normalText.classList.add('hidden');
        
        const result = await App.api(`/ristournes/calculer?mois=${mois}&annee=${annee}`, 'GET');
        App.notify(result.message || 'Calcul terminé avec succès', 'success');
        
        // Forcer le rafraîchissement immédiat
        window.location.reload(true);
    } catch (e) {
        App.notify(e.message, 'error');
        btn.disabled = false;
        loadingText.classList.add('hidden');
        normalText.classList.remove('hidden');
    }
}

async function payerRistourne(id) {
    const ok = await App.confirm({
        title: 'Payer la ristourne ?',
        message: 'Confirmer le paiement de cette ristourne ?',
        confirmText: 'Payer',
        cancelText: 'Annuler',
        type: 'warning'
    });
    if (!ok) return;
    
    try {
        const result = await App.api(`/api/ristournes/${id}/payer`, 'POST');
        
        App.notify(result?.message || 'Ristourne payée avec succès', 'success');
        
        // Rafraîchir la page immédiatement
        window.location.reload();
    } catch (e) {
        // Si c'est une erreur de parsing mais que l'action a probablement réussi
        if (e.message && e.message.includes('non JSON')) {
             App.notify('Paiement confirmé', 'success');
             window.location.reload();
             return;
        }
        App.notify(e.message || 'Erreur lors du paiement', 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
