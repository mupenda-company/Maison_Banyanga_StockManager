<?php 
$pageTitle = 'Missions';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Missions</h1>
        <p class="text-gray-500 dark:text-gray-400">Gestion des missions de livraison</p>
    </div>
    <a href="<?= url('missions/create') ?>" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nouvelle mission
    </a>
</div>

<!-- Filtres -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Statut</label>
                <select name="statut" class="input">
                    <option value="">Tous</option>
                    <option value="en_cours" <?= ($filters['statut'] ?? '') == 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="terminee" <?= ($filters['statut'] ?? '') == 'terminee' ? 'selected' : '' ?>>Terminées</option>
                </select>
            </div>
            <div>
                <label class="label">Véhicule</label>
                <select name="vehicule_id" class="input">
                    <option value="">Tous</option>
                    <?php foreach ($vehicules as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= ($filters['vehicule_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['immatriculation']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Date</label>
                <input type="date" name="date" class="input" value="<?= $filters['date'] ?? '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="<?= url('missions') ?>" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<!-- Liste des missions -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($missions)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <p class="text-gray-500">Aucune mission trouvée</p>
            <a href="<?= url('missions/create') ?>" class="btn btn-primary mt-4">Créer une mission</a>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>N° Mission</th>
                        <th>Date</th>
                        <th>Véhicule</th>
                        <th>Agent</th>
                        <th>Zone</th>
                        <th>Chargement</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missions as $mission): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($mission['numero_mission']) ?></td>
                        <td>
                            <div><?= date('d/m/Y', strtotime($mission['date_depart'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('H:i', strtotime($mission['date_depart'])) ?></div>
                        </td>
                        <td>
                            <div class="font-medium"><?= htmlspecialchars($mission['immatriculation']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($mission['vehicule_type'] ?? '') ?></div>
                        </td>
                        <td><?= htmlspecialchars($mission['agent_nom'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($mission['zone_nom'] ?? 'N/A') ?></td>
                        <td>
                            <div class="font-medium"><?= number_format((int) ($mission['total_caisses'] ?? 0), 0, '.', ' ') ?> caisses</div>
                            <div class="text-xs text-gray-500"><?= $mission['nb_clients'] ?? 0 ?> client(s)</div>
                        </td>
                        <td>
                            <?php if ($mission['statut'] === 'en_cours'): ?>
                            <span class="badge-warning">En cours</span>
                            <?php else: ?>
                            <span class="badge-success">Terminée</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <a href="<?= url('missions/' . $mission['id']) ?>" class="text-primary-600 hover:text-primary-700" title="Voir">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="<?= url('missions/' . $mission['id'] . ($mission['statut'] === 'terminee' ? '/facture' : '/print')) ?>" target="_blank" class="text-gray-600 hover:text-gray-700" title="<?= $mission['statut'] === 'terminee' ? 'Imprimer facture' : 'Imprimer bon' ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                                <?php if ($mission['statut'] === 'en_cours'): ?>
                                <button onclick="terminerMission(<?= $mission['id'] ?>)" class="text-green-600 hover:text-green-700" title="Terminer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function terminerMission(id) {
    window.location.href = '<?= url('missions') ?>/' + id + '?terminer=1';
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
