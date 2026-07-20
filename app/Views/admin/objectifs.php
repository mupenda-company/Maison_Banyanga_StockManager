<?php
$pageTitle = 'Objectifs mensuels';
$periode = $periode ?? date('Y-m');
$typeObjectif = ($typeObjectif ?? 'vente') === 'approvisionnement' ? 'approvisionnement' : 'vente';
$produits = $produits ?? [];
$objectifsParProduit = $objectifsParProduit ?? [];
$summary = $summary ?? [
    'objectif_total' => 0,
    'realise_total' => 0,
    'vendu_total' => 0,
    'approvisionnement_total' => 0,
    'reste_total' => 0,
    'progression' => 0,
    'nb_produits' => 0,
];

$periodeLabel = date('m/Y', strtotime($periode . '-01'));
$typeLabel = $typeObjectif === 'approvisionnement' ? 'Approvisionnement' : 'Vente';
$actionLabel = $typeObjectif === 'approvisionnement' ? 'approvisionner' : 'vendre';
$realiseLabel = $typeObjectif === 'approvisionnement' ? 'Approvisionne ce mois' : 'Vendu ce mois';
$resteLabel = $typeObjectif === 'approvisionnement' ? 'Reste a approvisionner' : 'Reste a vendre';
$realiseTotal = (int) ($summary['realise_total'] ?? ($typeObjectif === 'approvisionnement' ? ($summary['approvisionnement_total'] ?? 0) : ($summary['vendu_total'] ?? 0)));
ob_start();
?>
<div class="max-w-7xl mx-auto" x-data="objectifsComponent()">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Objectifs mensuels</h1>
            <p class="text-gray-500 dark:text-gray-400">
                Definissez les objectifs de vente ou d'approvisionnement par produit. Les mouvements valides du mois reduisent automatiquement le reste a atteindre.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if (can('objectifs.imprimer')): ?><a href="<?= url('admin/objectifs/print') . '?' . http_build_query(['periode' => $periode, 'type' => $typeObjectif]) ?>"
               target="_blank" class="btn btn-secondary">Imprimer</a>
            <?php endif; ?>
            <?php if (can('objectifs.exporter')): ?><a href="<?= url('admin/objectifs/export') . '?' . http_build_query(['periode' => $periode, 'type' => $typeObjectif]) ?>"
               class="btn btn-secondary">Exporter Excel</a><?php endif; ?>
            <a href="<?= url('admin/settings') ?>" class="btn btn-secondary">
                Retour aux parametres
            </a>
            <?php if (can('objectifs.gerer')): ?><button type="button" @click="saveObjectifs()" class="btn btn-primary" :disabled="saving">
                <span x-text="saving ? 'Enregistrement...' : 'Enregistrer les objectifs'"></span>
            </button><?php endif; ?>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" action="<?= url('admin/objectifs') ?>" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="label">Periode</label>
                    <input type="month" name="periode" value="<?= htmlspecialchars($periode) ?>" class="input">
                </div>
                <div>
                    <label class="label">Type d'objectif</label>
                    <select name="type" class="input">
                        <option value="vente" <?= $typeObjectif === 'vente' ? 'selected' : '' ?>>Vente</option>
                        <option value="approvisionnement" <?= $typeObjectif === 'approvisionnement' ? 'selected' : '' ?>>Approvisionnement</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Charger</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="stat-card">
            <div class="stat-label">Periode</div>
            <div class="stat-value text-primary-600"><?= htmlspecialchars($periodeLabel) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Objectif total</div>
            <div class="stat-value text-blue-600"><?= number_format((int) ($summary['objectif_total'] ?? 0), 0, ',', ' ') ?> cs</div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><?= htmlspecialchars($realiseLabel) ?></div>
            <div class="stat-value text-green-600"><?= number_format($realiseTotal, 0, ',', ' ') ?> cs</div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><?= htmlspecialchars($resteLabel) ?></div>
            <div class="stat-value text-orange-600"><?= number_format((int) ($summary['reste_total'] ?? 0), 0, ',', ' ') ?> cs</div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between gap-4 mb-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Avancement global</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        <?= number_format((float) ($summary['progression'] ?? 0), 1, ',', ' ') ?> %
                    </p>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <p><?= number_format((int) ($summary['nb_produits'] ?? 0), 0, ',', ' ') ?> produit(s) suivis</p>
                    <p><?= htmlspecialchars($typeLabel) ?> : <?= htmlspecialchars($periodeLabel) ?></p>
                </div>
            </div>
            <div class="w-full h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full bg-primary-600" style="width: <?= min(100, (float) ($summary['progression'] ?? 0)) ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Le tableau ci-dessous montre l'objectif defini par produit, les caisses deja <?= htmlspecialchars($typeObjectif === 'approvisionnement' ? 'approvisionnees' : 'vendues') ?> et le reste a <?= htmlspecialchars($actionLabel) ?>.
            </p>
        </div>
    </div>

    <form x-ref="form" @submit.prevent="saveObjectifs()" class="card">
        <div class="card-header flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Objectifs par produit</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Les <?= htmlspecialchars(strtolower($typeLabel)) ?>s valides du mois s'imputent automatiquement sur ces objectifs.</p>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Mois : <span class="font-semibold"><?= htmlspecialchars($periodeLabel) ?></span>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($produits)): ?>
                <div class="p-10 text-center text-gray-500 dark:text-gray-400">
                    Aucun produit actif disponible.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-right">Objectif (cs)</th>
                                <th class="text-right"><?= htmlspecialchars($typeObjectif === 'approvisionnement' ? 'Approvisionne' : 'Vendu') ?> (cs)</th>
                                <th class="text-right">Reste (cs)</th>
                                <th class="text-right">Surplus (cs)</th>
                                <th>Progression</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $index => $produit):
                                $row = $objectifsParProduit[(int) $produit['id']] ?? [];
                                $objectif = (int) ($row['objectif_caisses'] ?? 0);
                                $realise = (int) ($row['realise_caisses'] ?? ($typeObjectif === 'approvisionnement' ? ($row['approvisionnement_caisses'] ?? 0) : ($row['ventes_caisses'] ?? 0)));
                                $reste = (int) ($row['reste_caisses'] ?? max(0, $objectif - $realise));
                                $surplus = $objectif > 0 ? max(0, $realise - $objectif) : 0;
                                $progress = $objectif > 0 ? min(100, ($realise / $objectif) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($produit['nom']) ?></div>
                                    <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($produit['code']) ?></div>
                                </td>
                                <td class="text-right">
                                    <input type="hidden" name="produit_id[]" value="<?= (int) $produit['id'] ?>">
                                    <input
                                        type="number"
                                        name="objectif_caisses[]"
                                        min="0"
                                        step="1"
                                        value="<?= $objectif ?>"
                                        class="input w-32 text-right ml-auto"
                                        placeholder="0"
                                    >
                                </td>
                                <td class="text-right font-semibold text-green-600">
                                    <?= number_format($realise, 0, ',', ' ') ?>
                                </td>
                                <td class="text-right font-semibold text-orange-600">
                                    <?= number_format($reste, 0, ',', ' ') ?>
                                </td>
                                <td class="text-right font-semibold <?= $surplus > 0 ? 'text-green-700' : 'text-gray-400' ?>">
                                    <?= number_format($surplus, 0, ',', ' ') ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                            <div class="bg-primary-600 h-2 rounded-full" style="width: <?= min(100, $progress) ?>%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 w-14 text-right">
                                            <?= number_format((float) $progress, 0, ',', ' ') ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex items-center justify-between gap-4 p-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Conseil : tu peux ajuster les objectifs chaque debut de mois.
            </p>
            <button type="submit" class="btn btn-primary" :disabled="saving">
                <span x-text="saving ? 'Enregistrement...' : 'Enregistrer les objectifs'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function objectifsComponent() {
    return {
        periode: '<?= htmlspecialchars($periode) ?>',
        type: '<?= htmlspecialchars($typeObjectif) ?>',
        saving: false,
        async saveObjectifs() {
            this.saving = true;
            try {
                const formData = new FormData(this.$refs.form);
                const produitIds = formData.getAll('produit_id[]');
                const objectifsCaisses = formData.getAll('objectif_caisses[]');

                const objectifs = produitIds.map((produitId, index) => ({
                    produit_id: produitId,
                    objectif_caisses: objectifsCaisses[index] ?? 0
                }));

                const result = await App.api('/api/admin/objectifs', 'POST', {
                    periode: this.periode,
                    type: this.type,
                    objectifs
                });

                App.notify(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
