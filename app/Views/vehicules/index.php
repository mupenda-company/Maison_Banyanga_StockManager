<?php 
$pageTitle = 'Véhicules';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Véhicules</h1>
        <p class="text-gray-500 dark:text-gray-400">Gestion du parc automobile</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= url('vehicules/inventaire') ?>" class="btn btn-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0a2 2 0 002 2h4a2 2 0 002-2m-6 0a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Inventaire véhicules
        </a>
        <?php if (can('vehicules.manage')): ?>
        <button onclick="openModal()" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau véhicule
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Liste des véhicules -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($vehicules)): ?>
    <div class="col-span-full">
        <div class="card">
            <div class="card-body p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <p class="text-gray-500">Aucun véhicule enregistré</p>
                <button onclick="openModal()" class="btn btn-primary mt-4">Ajouter un véhicule</button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($vehicules as $vehicule): ?>
    <div class="card hover:shadow-lg transition-shadow">
        <div class="card-body">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white">
                        <?= htmlspecialchars($vehicule['immatriculation']) ?>
                    </h3>
                    <p class="text-sm text-gray-500">
                        <?= htmlspecialchars($vehicule['marque'] ?? '') ?> <?= htmlspecialchars($vehicule['modele'] ?? '') ?>
                    </p>
                </div>
                <?php if ($vehicule['actif']): ?>
                <?php if ($vehicule['en_mission']): ?>
                <span class="badge-warning">En mission</span>
                <?php else: ?>
                <span class="badge-success">Disponible</span>
                <?php endif; ?>
                <?php else: ?>
                <span class="badge-secondary">Inactif</span>
                <?php endif; ?>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Agent responsable</span>
                    <span class="font-medium"><?= htmlspecialchars($vehicule['agent_nom'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Capacité</span>
                    <span class="font-medium"><?= $vehicule['capacite'] ?? 0 ?> caisses</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Stock actuel</span>
                    <span class="font-medium <?= ($vehicule['stock_actuel'] ?? 0) > 0 ? 'text-green-600' : 'text-gray-400' ?>">
                        <?= $vehicule['stock_actuel'] ?? 0 ?> caisses
                    </span>
                </div>
            </div>
            
            <div class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= url('vehicules/' . $vehicule['id']) ?>" class="btn btn-sm btn-secondary flex-1">
                    Voir détails
                </a>
                <?php if (can('vehicules.manage')): ?>
                <button onclick="editVehicule(<?= htmlspecialchars(json_encode($vehicule)) ?>)" class="btn btn-sm btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <?php endif; ?>
                <?php if (can('admin.view')): ?>
                <button onclick="deleteVehicule(<?= $vehicule['id'] ?>, '<?= htmlspecialchars($vehicule['immatriculation']) ?>')" class="btn btn-sm btn-danger">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal suppression (confirmation) -->
<div x-cloak x-data="{ show: false, id: null, immat: '' }" 
     @open-delete-modal.window="show = true; id = $event.detail.id; immat = $event.detail.immat"
     x-show="show" 
     class="fixed inset-0 z-[60] overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 modal-overlay" @click="show = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Supprimer le véhicule ?</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Êtes-vous sûr de vouloir supprimer le véhicule <span class="font-bold text-gray-900 dark:text-white" x-text="immat"></span> ? Cette action est irréversible.
                </p>
                <div class="flex gap-3 justify-center">
                    <button @click="show = false" class="btn btn-secondary px-6">Annuler</button>
                    <button @click="confirmDelete(id)" class="btn btn-danger px-6">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal création/édition -->
<div x-cloak x-data="vehiculeModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 modal-overlay" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6" 
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold" x-text="editMode ? 'Modifier le véhicule' : 'Nouveau véhicule'"></h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="save">
                <div class="space-y-4">
                    <div>
                        <label class="label">Immatriculation *</label>
                        <input type="text" x-model="form.immatriculation" class="input" required placeholder="CG-1234-A">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Marque</label>
                            <input type="text" x-model="form.marque" class="input" placeholder="Toyota">
                        </div>
                        <div>
                            <label class="label">Modèle</label>
                            <input type="text" x-model="form.modele" class="input" placeholder="Hilux">
                        </div>
                    </div>
                    <div>
                        <label class="label">Agent responsable *</label>
                        <select x-model="form.agent_responsable_id" class="input" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($agents ?? [] as $agent): ?>
                            <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Capacité (caisses)</label>
                        <input type="number" x-model="form.capacite" class="input" min="0">
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
    Alpine.data('vehiculeModal', () => ({
        isOpen: false,
        loading: false,
        editMode: false,
        editId: null,
        form: {
            immatriculation: '',
            marque: '',
            modele: '',
            agent_responsable_id: '',
            capacite: 0
        },
        
        open() {
            this.editMode = false;
            this.editId = null;
            this.form = { immatriculation: '', marque: '', modele: '', agent_responsable_id: '', capacite: 0 };
            this.isOpen = true;
        },
        
        close() { this.isOpen = false; },
        
        edit(vehicule) {
            this.editMode = true;
            this.editId = vehicule.id;
            this.form = {
                immatriculation: vehicule.immatriculation,
                marque: vehicule.marque || '',
                modele: vehicule.modele || '',
                agent_responsable_id: vehicule.agent_responsable_id || '',
                capacite: vehicule.capacite || 0
            };
            this.isOpen = true;
        },
        
        async save() {
            this.loading = true;
            try {
                const ok = await App.confirm({
                    title: this.editMode ? 'Modifier le véhicule ?' : 'Créer le véhicule ?',
                    message: this.editMode ? 'Confirmer la modification de ce véhicule ?' : 'Confirmer la création de ce véhicule ?',
                    confirmText: this.editMode ? 'Modifier' : 'Créer',
                    cancelText: 'Annuler',
                    type: 'info'
                });
                if (!ok) return;

                let result;
                if (this.editMode && this.editId) {
                    result = await App.api(`/api/vehicules/${this.editId}`, 'PUT', this.form);
                } else {
                    result = await App.api('/api/vehicules', 'POST', this.form);
                }
                App.notify(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e?.message || 'Erreur', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openModal() {
    Alpine.evaluate(document.querySelector('[x-data="vehiculeModal"]'), 'open()');
}

function editVehicule(vehicule) {
    const modal = Alpine.$data(document.querySelector('[x-data="vehiculeModal"]'));
    modal.edit(vehicule);
}

function deleteVehicule(id, immat) {
    window.dispatchEvent(new CustomEvent('open-delete-modal', { detail: { id, immat } }));
}

async function confirmDelete(id) {
    try {
        const result = await App.api(`/api/vehicules/${id}`, 'DELETE');
        App.notify(result?.message || 'Véhicule désactivé avec succès', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        App.notify(e?.message || 'Erreur', 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
