<?php $pageTitle = 'Enregistrer un manquant'; 
ob_start(); 
?>
<div class="mb-6">
    <a href="<?= url('manquants') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Retour aux manquants
    </a>
</div>
<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header"><h2 class="text-lg font-semibold">Nouveau manquant agent</h2></div>
        <div class="card-body">
            <form x-data="manquantForm" @submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="label">Agent *</label><select x-model="form.agent_id" class="input" required><option value="">Sélectionner</option><?php foreach($agents as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></option><?php endforeach; ?></select></div>
                <div><label class="label">Produit concerné</label><select x-model="form.produit_id" class="input"><option value="">Aucun / montant seul</option><?php foreach($produits as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?></select></div>
                <div><label class="label">Quantité manquante (caisses) *</label><input type="number" min="0" step="0.01" x-model.number="form.quantite_caisses" class="input" required></div>
                <div><label class="label">Montant à payer</label><input type="number" min="0" step="0.01" x-model.number="form.montant" class="input"></div>
                <div><label class="label">Montant déjà payé</label><input type="number" min="0" step="0.01" x-model.number="form.montant_paye" class="input"></div>
                <div><label class="label">Date *</label><input type="date" x-model="form.date_manquant" class="input" required></div>
                <div class="md:col-span-2"><label class="label">Motif / observation</label><textarea x-model="form.motif" class="input" rows="3"></textarea></div>
                <div class="md:col-span-2 flex justify-end gap-2"><a href="<?= url('manquants') ?>" class="btn btn-secondary">Annuler</a><button class="btn btn-primary" :disabled="loading">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('alpine:init', () => Alpine.data('manquantForm', () => ({
    loading: false,
    form: { agent_id: '', produit_id: '', quantite_caisses: 0, montant: 0, montant_paye: 0, date_manquant: new Date().toISOString().slice(0,10), motif: '' },
    async save() {
        this.loading = true;
        try {
            await App.api('/api/manquants', 'POST', this.form);
            App.notify('Manquant enregistré', 'success');
            setTimeout(() => location.href = '<?= url('manquants') ?>', 600);
        } catch (e) {
            App.notify(e.message, 'error');
            this.loading = false;
        }
    }
})));
</script>
<?php $content = ob_get_clean(); 
require ROOT_PATH . '/app/Views/layouts/app.php'; 
?>