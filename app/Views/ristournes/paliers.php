<?php 
$pageTitle = 'Paliers de ristourne';
$devise = get_devise();
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Paliers de Ristourne</h1>
        <p class="text-gray-500 dark:text-gray-400">Définissez les remises par volume de caisses vendues</p>
    </div>
    
    <div class="flex gap-3">
        <a href="<?= url('ristournes') ?>" class="btn btn-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour
        </a>
        <button onclick="openPalierModal()" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau palier
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom du Palier</th>
                        <th class="text-right">CA Min</th>
                        <th class="text-right">CA Max</th>
                        <th class="text-right">Taux Ristourne</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paliers)): ?>
                        <tr><td colspan="5" class="text-center p-8 text-gray-500">Aucun palier configuré.</td></tr>
                    <?php else: ?>
                        <?php foreach($paliers as $p): ?>
                            <tr>
                                <td class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($p['nom']) ?></td>
                                <td class="text-right"><?= format_money_converted($p['ca_min'] ?? 0) ?></td>
                                <td class="text-right"><?= $p['ca_max'] ? format_money_converted($p['ca_max']) : '∞' ?></td>
                                <td class="text-right font-black text-primary-600"><?= number_format($p['taux_ristourne'], 2) ?> %</td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="editPalier(<?= htmlspecialchars(json_encode($p)) ?>)" class="text-blue-500 hover:text-blue-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button onclick="deletePalier(<?= $p['id'] ?>)" class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Palier -->
<div id="palierModal" 
     x-cloak 
     x-data="palierModal()" 
     @open-palier-modal.window="open($event.detail)"
     x-show="isOpen" 
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 modal-overlay" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6 shadow-xl"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-lg font-bold mb-4" x-text="form.id ? 'Modifier le palier' : 'Nouveau palier'"></h3>
            <div class="space-y-4">
                <div>
                    <label class="label">Nom du palier</label>
                    <input type="text" x-model="form.nom" class="input" placeholder="ex: Bronze, Argent...">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">CA Min (<?= $devise ?>)</label>
                        <input type="number" x-model="form.ca_min" class="input">
                    </div>
                    <div>
                        <label class="label">CA Max (<?= $devise ?>)</label>
                        <input type="number" x-model="form.ca_max" class="input" placeholder="Laisser vide pour ∞">
                    </div>
                </div>
                <div>
                    <label class="label">Taux de ristourne (%)</label>
                    <input type="number" step="0.01" x-model="form.taux_ristourne" class="input">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button @click="close()" class="btn btn-secondary">Annuler</button>
                <button @click="save()" class="btn btn-primary" :disabled="loading">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
function palierModal() {
    return {
        isOpen: false,
        loading: false,
        form: { id: null, nom: '', ca_min: 0, ca_max: '', taux_ristourne: 0 },
        open(data = null) {
            const devise = window.DEVISE || 'CDF';
            if (data) {
                const baseDevise = window.BASE_DEVISE || 'CDF';
                const caMin = App.convertMoney(parseFloat(data.ca_min || 0), baseDevise, devise);
                const caMax = (data.ca_max === null || data.ca_max === undefined || data.ca_max === '')
                    ? ''
                    : App.convertMoney(parseFloat(data.ca_max || 0), baseDevise, devise);
                this.form = { ...data, ca_min: caMin, ca_max: caMax };
            } else {
                this.form = { id: null, nom: '', ca_min: 0, ca_max: '', taux_ristourne: 0 };
            }
            this.isOpen = true;
        },
        close() {
            this.isOpen = false;
        },
        async save() {
            this.loading = true;
            try {
                await App.api('/api/ristournes/paliers', 'POST', this.form);
                App.notify('Palier enregistré', 'success');
                setTimeout(() => location.reload(), 500);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}

function openPalierModal() {
    window.dispatchEvent(new CustomEvent('open-palier-modal'));
}

function editPalier(data) {
    window.dispatchEvent(new CustomEvent('open-palier-modal', { detail: data }));
}

async function deletePalier(id) {
    const ok = await App.confirm({
        title: 'Supprimer le palier ?',
        message: 'Supprimer ce palier ?',
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
        type: 'danger'
    });
    if (!ok) return;
    try {
        await App.api(`/api/ristournes/paliers/${id}`, 'DELETE');
        App.notify('Palier supprimé', 'success');
        setTimeout(() => location.reload(), 500);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
