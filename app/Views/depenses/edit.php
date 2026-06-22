<?php 
$pageTitle = 'Modifier une dépense';
ob_start();

$montantSaisi = (float)($depense['montant_original'] ?? 0);
$deviseSaisie = $depense['devise'] ?? get_base_devise();
if ($montantSaisi <= 0) {
    $montantSaisi = (float)($depense['montant'] ?? 0);
    $deviseSaisie = get_base_devise();
}
?>

<div class="mb-6">
    <a href="<?= url('depenses') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour aux dépenses
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Modifier la dépense</h2>
        </div>
        <div class="card-body">
            <form x-data="depenseEditForm" @submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Catégorie *</label>
                        <select x-model="form.categorie" class="input" required>
                            <option value="Transport">Transport</option>
                            <option value="Carburant">Carburant</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Restauration">Restauration</option>
                            <option value="Promotion">Promotion</option>
                            <option value="Autres">Autres</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Montant *</label>
                        <input type="number" x-model.number="form.montant" class="input" min="0" step="0.01" required placeholder="0.00">
                    </div>
                    <div>
                        <label class="label">Devise *</label>
                        <select x-model="form.devise" class="input" required>
                            <option value="CDF">CDF - Franc congolais</option>
                            <option value="USD">USD - Dollar</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Date *</label>
                        <input type="date" x-model="form.date_depense" class="input" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Description *</label>
                        <input type="text" x-model="form.description" class="input" required placeholder="Description de la dépense">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="<?= url('depenses') ?>" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Enregistrer les modifications</span>
                        <span x-show="loading">Modification...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('depenseEditForm', () => ({
        form: {
            categorie: <?= json_encode($depense['categorie'] ?? 'Transport') ?>,
            description: <?= json_encode($depense['description'] ?? '') ?>,
            montant: <?= json_encode($montantSaisi) ?>,
            devise: <?= json_encode($deviseSaisie) ?>,
            date_depense: <?= json_encode($depense['date_depense'] ?? date('Y-m-d')) ?>
        },
        loading: false,

        async save() {
            this.loading = true;
            try {
                const res = await fetch(BASE_URL + '/api/depenses/<?= (int)$depense['id'] ?>', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = BASE_URL + '/depenses';
                } else {
                    alert(data.message || 'Erreur lors de la modification');
                }
            } catch (e) {
                alert('Erreur réseau');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
