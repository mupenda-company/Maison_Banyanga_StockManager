<?php 
$pageTitle = 'Zones';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Zones</h1>
        <p class="text-gray-500 dark:text-gray-400">Gestion des zones de livraison</p>
    </div>
    <button onclick="openModal()" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nouvelle zone
    </button>
</div>

<!-- Liste des zones -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($zones)): ?>
    <div class="col-span-full">
        <div class="card">
            <div class="card-body p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-gray-500">Aucune zone enregistrée</p>
                <button onclick="openModal()" class="btn btn-primary mt-4">Ajouter une zone</button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($zones as $zone): ?>
    <div class="card hover:shadow-lg transition-shadow">
        <div class="card-body">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white">
                        <?= htmlspecialchars($zone['nom']) ?>
                    </h3>
                    <?php if (!empty($zone['description'])): ?>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($zone['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($zone['actif']): ?>
                <span class="badge-success">Active</span>
                <?php else: ?>
                <span class="badge-secondary">Inactive</span>
                <?php endif; ?>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Clients</span>
                    <span class="font-medium"><?= $zone['nb_clients'] ?? 0 ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">CA du mois</span>
                    <span class="font-medium text-green-600">
                        <?= format_money_converted($zone['ca_mois'] ?? 0) ?>
                    </span>
                </div>
            </div>
            
            <div class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= url('zones/' . $zone['id']) ?>" class="btn btn-sm btn-secondary flex-1">
                    Voir détails
                </a>
                <button onclick="editZone(<?= $zone['id'] ?>, '<?= htmlspecialchars($zone['nom']) ?>', '<?= htmlspecialchars($zone['description'] ?? '') ?>')" 
                    class="btn btn-sm btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="deleteZone(<?= $zone['id'] ?>)" 
                    class="btn btn-sm btn-danger">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal création/édition -->
<div x-data="zoneModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold" x-text="editMode ? 'Modifier la zone' : 'Nouvelle zone'"></h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="save">
                <div class="space-y-4">
                    <div>
                        <label class="label">Nom *</label>
                        <input type="text" x-model="form.nom" class="input" required>
                    </div>
                    <div>
                        <label class="label">Description</label>
                        <textarea x-model="form.description" class="input" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Enregistrer</span>
                        <span x-show="loading">En cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('zoneModal', () => ({
        isOpen: false,
        loading: false,
        editMode: false,
        editId: null,
        form: { nom: '', description: '' },
        
        open() {
            this.editMode = false;
            this.editId = null;
            this.form = { nom: '', description: '' };
            this.isOpen = true;
        },
        
        close() { this.isOpen = false; },
        
        edit(id, nom, description) {
            this.editMode = true;
            this.editId = id;
            this.form = { nom: nom, description: description };
            this.isOpen = true;
        },
        
        async save() {
            const ok = await App.confirm({
                title: this.editMode ? 'Modifier la zone ?' : 'Créer la zone ?',
                message: this.editMode ? 'Confirmer la modification de cette zone ?' : 'Confirmer la création de cette zone ?',
                confirmText: this.editMode ? 'Modifier' : 'Créer',
                cancelText: 'Annuler',
                type: 'info'
            });
            if (!ok) return;

            this.loading = true;
            try {
                let result;
                if (this.editMode && this.editId) {
                    result = await App.api(`/api/zones/${this.editId}`, 'PUT', this.form);
                } else {
                    result = await App.api('/api/zones', 'POST', this.form);
                }
                App.notify(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openModal() {
    Alpine.evaluate(document.querySelector('[x-data="zoneModal"]'), 'open()');
}

function editZone(id, nom, description) {
    const modal = Alpine.$data(document.querySelector('[x-data="zoneModal"]'));
    modal.edit(id, nom, description);
}

async function deleteZone(id) {
    const ok = await App.confirm({
        title: 'Supprimer la zone ?',
        message: 'Êtes-vous sûr de vouloir supprimer cette zone ? Elle sera désactivée.',
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
        type: 'danger'
    });
    if (!ok) return;
    
    try {
        const result = await App.api(`/api/zones/${id}`, 'DELETE');
        App.notify(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        App.notify(e.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
