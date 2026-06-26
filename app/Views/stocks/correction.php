<?php
$pageTitle = 'Correction des écarts de stock';
ob_start();
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <a href="<?= url('stocks') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2 mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux stocks
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Correction des écarts</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Alignez le stock système sur le comptage physique, avec traçabilité complète.</p>
    </div>
    <a href="<?= url('stocks/ajustements') ?>" class="btn btn-secondary">Historique des corrections</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="label">Produit</label>
                <select name="produit_id" class="input w-full">
                    <option value="">Tous les produits</option>
                    <?php foreach ($produits as $produit): ?>
                    <option value="<?= (int) $produit['id'] ?>" <?= ($filters['produit_id'] ?? '') == $produit['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($produit['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Emplacement</label>
                <select name="emplacement_id" class="input w-full">
                    <option value="">Tous les emplacements</option>
                    <?php foreach ($emplacements as $emp): ?>
                    <option value="<?= (int) $emp['id'] ?>" <?= ($filters['emplacement_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nom']) ?> (<?= ucfirst($emp['type']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="<?= url('stocks/correction') ?>" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>
</div>

<div class="rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 p-4 mb-6 text-sm text-blue-800 dark:text-blue-200">
    <strong>Règle importante :</strong> les emballages/vides ne sont corrigés que dans l’entrepôt. Si l’écart se trouve sur un véhicule, le bouton de correction des vides est bloqué pour éviter de fausser les missions.
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Emplacement</th>
                        <th class="text-right">Système plein</th>
                        <th class="text-right">Physique plein</th>
                        <th class="text-right">Écart plein</th>
                        <th class="text-right">Système vide</th>
                        <th class="text-right">Physique vide</th>
                        <th class="text-right">Écart vide</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ecarts)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500 dark:text-gray-400">Aucun écart trouvé. Tous les stocks sont alignés.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ecarts as $row): ?>
                    <?php
                        $ecartPlein = (float) ($row['ecart_caisses_pleine'] ?? 0);
                        $ecartVide = (float) ($row['ecart_caisses_vide'] ?? 0);
                        $isMobile = ($row['emplacement_type'] ?? '') === 'mobile';
                    ?>
                    <tr>
                        <td>
                            <div class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($row['produit_nom']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($row['produit_code']) ?></div>
                        </td>
                        <td>
                            <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($row['emplacement_nom']) ?></div>
                            <div class="text-xs text-gray-500">
                                <?= $isMobile ? 'Mobile' : 'Fixe' ?>
                                <?php if (!empty($row['vehicule_immatriculation'])): ?> • <?= htmlspecialchars($row['vehicule_immatriculation']) ?><?php endif; ?>
                            </div>
                        </td>
                        <td class="text-right font-bold text-green-600"><?= number_format((float) $row['caisses_pleine_systeme'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold text-blue-600"><?= number_format((float) $row['caisses_pleine_physique_calc'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold <?= abs($ecartPlein) > 0.0001 ? 'text-red-600' : 'text-green-600' ?>"><?= ($ecartPlein > 0 ? '+' : '') . number_format($ecartPlein, 2, ',', ' ') ?></td>
                        <td class="text-right font-bold text-gray-600 dark:text-gray-300"><?= number_format((float) $row['caisses_vide_systeme'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold text-purple-600"><?= number_format((float) $row['caisses_vide_physique_calc'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold <?= abs($ecartVide) > 0.0001 ? 'text-red-600' : 'text-green-600' ?>"><?= ($ecartVide > 0 ? '+' : '') . number_format($ecartVide, 2, ',', ' ') ?></td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                onclick="openCorrectionModal(<?= (int) $row['produit_id'] ?>, <?= (int) $row['emplacement_id'] ?>, '<?= htmlspecialchars($row['produit_nom'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['emplacement_nom'], ENT_QUOTES) ?>', <?= $ecartPlein ?>, <?= $ecartVide ?>, <?= $isMobile ? 'true' : 'false' ?>)">
                                Corriger
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="correctionModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 no-print">
    <div class="absolute inset-0 bg-black/50" onclick="closeCorrectionModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Corriger l’écart</h3>
        <p id="correctionTitle" class="text-sm text-gray-500 dark:text-gray-400 mb-4"></p>
        <form id="correctionForm" onsubmit="saveCorrection(event)">
            <input type="hidden" id="correctionProduitId">
            <input type="hidden" id="correctionEmplacementId">
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" id="corrigerPlein" class="rounded">
                    Corriger les caisses pleines <span id="labelEcartPlein" class="font-bold"></span>
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" id="corrigerVide" class="rounded">
                    Corriger les emballages/vides <span id="labelEcartVide" class="font-bold"></span>
                </label>
                <p id="mobileWarning" class="hidden text-xs text-orange-600 dark:text-orange-400">Correction des emballages bloquée pour un véhicule. Faites cette correction dans l’entrepôt.</p>
                <div>
                    <label class="label">Motif obligatoire</label>
                    <textarea id="correctionMotif" class="input" rows="3" required placeholder="Ex: correction après inventaire physique validé"></textarea>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="closeCorrectionModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Valider la correction</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCorrectionModal(produitId, emplacementId, produit, emplacement, ecartPlein, ecartVide, isMobile) {
    document.getElementById('correctionProduitId').value = produitId;
    document.getElementById('correctionEmplacementId').value = emplacementId;
    document.getElementById('correctionTitle').textContent = produit + ' • ' + emplacement;
    document.getElementById('labelEcartPlein').textContent = '(écart: ' + (ecartPlein > 0 ? '+' : '') + ecartPlein + ' cs)';
    document.getElementById('labelEcartVide').textContent = '(écart: ' + (ecartVide > 0 ? '+' : '') + ecartVide + ' cs)';
    document.getElementById('corrigerPlein').checked = Math.abs(ecartPlein) > 0.0001;
    document.getElementById('corrigerPlein').disabled = Math.abs(ecartPlein) <= 0.0001;
    document.getElementById('corrigerVide').checked = !isMobile && Math.abs(ecartVide) > 0.0001;
    document.getElementById('corrigerVide').disabled = isMobile || Math.abs(ecartVide) <= 0.0001;
    document.getElementById('mobileWarning').classList.toggle('hidden', !isMobile || Math.abs(ecartVide) <= 0.0001);
    document.getElementById('correctionMotif').value = '';
    document.getElementById('correctionModal').classList.remove('hidden');
    document.getElementById('correctionModal').classList.add('flex');
}
function closeCorrectionModal() {
    document.getElementById('correctionModal').classList.add('hidden');
    document.getElementById('correctionModal').classList.remove('flex');
}
async function saveCorrection(event) {
    event.preventDefault();
    try {
        await App.api('/api/stocks/correction', 'POST', {
            produit_id: document.getElementById('correctionProduitId').value,
            emplacement_id: document.getElementById('correctionEmplacementId').value,
            corriger_plein: document.getElementById('corrigerPlein').checked,
            corriger_vide: document.getElementById('corrigerVide').checked,
            motif: document.getElementById('correctionMotif').value
        });
        App.notify('Écart corrigé avec succès', 'success');
        setTimeout(() => location.reload(), 700);
    } catch (e) {
        App.notify(e.message || 'Correction impossible', 'error');
    }
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
