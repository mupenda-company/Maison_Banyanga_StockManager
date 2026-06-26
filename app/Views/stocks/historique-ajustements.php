<?php
$pageTitle = 'Historique des corrections de stock';
ob_start();
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <a href="<?= url('stocks/correction') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2 mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux corrections
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Historique des corrections</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Traçabilité complète des ajustements système/physique.</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="label">Produit</label>
                <select name="produit_id" class="input w-full">
                    <option value="">Tous les produits</option>
                    <?php foreach ($produits as $produit): ?>
                    <option value="<?= (int) $produit['id'] ?>" <?= ($filters['produit_id'] ?? '') == $produit['id'] ? 'selected' : '' ?>><?= htmlspecialchars($produit['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Emplacement</label>
                <select name="emplacement_id" class="input w-full">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($emplacements as $emp): ?>
                    <option value="<?= (int) $emp['id'] ?>" <?= ($filters['emplacement_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['nom']) ?> (<?= ucfirst($emp['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary">Filtrer</button>
                <a href="<?= url('stocks/ajustements') ?>" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th>Emplacement</th>
                        <th class="text-right">Ancien plein</th>
                        <th class="text-right">Physique plein</th>
                        <th class="text-right">Écart plein</th>
                        <th class="text-right">Ancien vide</th>
                        <th class="text-right">Physique vide</th>
                        <th class="text-right">Écart vide</th>
                        <th>Motif</th>
                        <th>Par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ajustements)): ?>
                    <tr><td colspan="11" class="text-center py-8 text-gray-500 dark:text-gray-400">Aucune correction enregistrée.</td></tr>
                    <?php else: foreach ($ajustements as $a): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                        <td><strong><?= htmlspecialchars($a['produit_nom']) ?></strong><br><span class="text-xs text-gray-500"><?= htmlspecialchars($a['produit_code']) ?></span></td>
                        <td><?= htmlspecialchars($a['emplacement_nom']) ?><br><span class="text-xs text-gray-500"><?= ucfirst($a['emplacement_type']) ?></span></td>
                        <td class="text-right"><?= number_format((float)$a['ancien_systeme_plein'], 2, ',', ' ') ?></td>
                        <td class="text-right text-blue-600 font-bold"><?= number_format((float)$a['physique_plein'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold <?= ((float)$a['ecart_plein']) == 0 ? 'text-green-600' : 'text-red-600' ?>"><?= ((float)$a['ecart_plein'] > 0 ? '+' : '') . number_format((float)$a['ecart_plein'], 2, ',', ' ') ?></td>
                        <td class="text-right"><?= number_format((float)$a['ancien_systeme_vide'], 2, ',', ' ') ?></td>
                        <td class="text-right text-purple-600 font-bold"><?= number_format((float)$a['physique_vide'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold <?= ((float)$a['ecart_vide']) == 0 ? 'text-green-600' : 'text-red-600' ?>"><?= ((float)$a['ecart_vide'] > 0 ? '+' : '') . number_format((float)$a['ecart_vide'], 2, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($a['motif'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($a['user_nom'] ?? 'Système') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
