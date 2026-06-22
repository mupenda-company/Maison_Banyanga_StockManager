<?php
$editMode = !empty($editMode);
$manquant = is_array($manquant ?? null) ? $manquant : [];
$pageTitle = $editMode ? 'Modifier un manquant' : 'Enregistrer un manquant';
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('manquants') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Retour aux manquants
    </a>
</div>

<div class="max-w-3xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold"><?= $editMode ? 'Modifier le manquant agent' : 'Nouveau manquant agent' ?></h2>
            <?php if ($editMode && (($manquant['type_manquant'] ?? '') === 'mission')): ?>
                <p class="text-xs text-orange-600 mt-1">Ce manquant provient d’une mission. La modification est autorisée, mais gardez le lien mission pour le suivi.</p>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form x-data="manquantForm" @submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Agent *</label>
                    <select x-model="form.agent_id" class="input" required>
                        <option value="">Sélectionner</option>
                        <?php foreach($agents as $a): ?>
                            <option value="<?= (int) $a['id'] ?>"><?= htmlspecialchars(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="label">Produit concerné</label>
                    <select x-model="form.produit_id" class="input">
                        <option value="">Aucun / montant seul</option>
                        <?php foreach($produits as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="label">Quantité manquante (caisses) *</label>
                    <input type="number" min="0" step="0.01" x-model.number="form.quantite_caisses" class="input" required>
                    <p class="text-[10px] text-gray-500 mt-1">Caisse/produit manquant en valeur marchandise.</p>
                </div>

                <div>
                    <label class="label">Emballages manquants</label>
                    <input type="number" min="0" step="0.01" x-model.number="form.quantite_emballages" class="input">
                    <p class="text-[10px] text-gray-500 mt-1">Nombre d’emballages que l’agent doit encore ramener.</p>
                </div>

                <div class="md:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-3">Montant à payer</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Montant CDF</label>
                            <input type="number" min="0" step="0.01" x-model.number="form.montant_cdf" class="input">
                        </div>
                        <div>
                            <label class="label">Montant USD</label>
                            <input type="number" min="0" step="0.01" x-model.number="form.montant_usd" class="input">
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 rounded-lg border border-green-200 dark:border-green-800 p-4">
                    <h3 class="text-sm font-bold text-green-700 dark:text-green-300 mb-3">Montant déjà payé</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Payé CDF</label>
                            <input type="number" min="0" step="0.01" x-model.number="form.montant_paye_cdf" class="input">
                        </div>
                        <div>
                            <label class="label">Payé USD</label>
                            <input type="number" min="0" step="0.01" x-model.number="form.montant_paye_usd" class="input">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label">Date *</label>
                    <input type="date" x-model="form.date_manquant" class="input" required>
                </div>

                <div>
                    <label class="label">Type</label>
                    <select x-model="form.type_manquant" class="input">
                        <option value="manuel">Manuel</option>
                        <option value="mission">Mission</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="label">Motif / observation</label>
                    <textarea x-model="form.motif" class="input" rows="3"></textarea>
                </div>

                <div class="md:col-span-2 flex justify-end gap-2">
                    <a href="<?= url('manquants') ?>" class="btn btn-secondary">Annuler</a>
                    <button class="btn btn-primary" :disabled="loading"><?= $editMode ? 'Modifier' : 'Enregistrer' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => Alpine.data('manquantForm', () => ({
    loading: false,
    editMode: <?= $editMode ? 'true' : 'false' ?>,
    manquantId: <?= (int)($manquant['id'] ?? 0) ?>,
    form: {
        agent_id: <?= json_encode((string)($manquant['agent_id'] ?? '')) ?>,
        produit_id: <?= json_encode(!empty($manquant['produit_id']) ? (string)$manquant['produit_id'] : '') ?>,
        mission_id: <?= json_encode(!empty($manquant['mission_id']) ? (int)$manquant['mission_id'] : null) ?>,
        type_manquant: <?= json_encode($manquant['type_manquant'] ?? 'manuel') ?>,
        quantite_caisses: <?= json_encode((float)($manquant['quantite_caisses'] ?? 0)) ?>,
        quantite_emballages: <?= json_encode((float)($manquant['quantite_emballages'] ?? 0)) ?>,
        montant_cdf: <?= json_encode((float)($manquant['montant_cdf'] ?? 0)) ?>,
        montant_usd: <?= json_encode((float)($manquant['montant_usd'] ?? 0)) ?>,
        montant_paye_cdf: <?= json_encode((float)($manquant['montant_paye_cdf'] ?? 0)) ?>,
        montant_paye_usd: <?= json_encode((float)($manquant['montant_paye_usd'] ?? 0)) ?>,
        date_manquant: <?= json_encode($manquant['date_manquant'] ?? date('Y-m-d')) ?>,
        motif: <?= json_encode($manquant['motif'] ?? '') ?>
    },
    async save() {
        this.loading = true;
        try {
            const url = this.editMode ? '/api/manquants/' + this.manquantId : '/api/manquants';
            const method = this.editMode ? 'PUT' : 'POST';
            await App.api(url, method, this.form);
            App.notify(this.editMode ? 'Manquant modifié' : 'Manquant enregistré', 'success');
            setTimeout(() => location.href = '<?= url('manquants') ?>', 600);
        } catch (e) {
            App.notify(e.message, 'error');
            this.loading = false;
        }
    }
})));
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/layouts/app.php';
?>
