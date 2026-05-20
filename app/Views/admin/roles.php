<?php
$pageTitle = 'Gestion des rôles et permissions';
$rolesJson = json_encode($roles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$permsGroupedJson = json_encode($permissionsGrouped, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
ob_start();
?>

<div class="space-y-6" x-data="rolesManager">
    <!-- Liste des rôles -->
    <div class="card">
        <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rôles</h2>
            <button @click="openModal()" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau rôle
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rôle</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Type</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="roles.length === 0">
                            <tr><td colspan="5" class="text-center py-8 text-gray-500">Aucun rôle trouvé</td></tr>
                        </template>
                        <template x-for="role in roles" :key="role.id">
                            <tr>
                                <td>
                                    <span class="font-medium" x-text="role.nom"></span>
                                </td>
                                <td class="text-sm text-gray-500" x-text="role.description || '-'"></td>
                                <td>
                                    <span class="badge badge-info" x-text="role.permissions?.length || 0 + ' permission(s)'"></span>
                                </td>
                                <td>
                                    <span class="badge" :class="role.is_system ? 'badge-warning' : 'badge-success'"
                                          x-text="role.is_system ? 'Système' : 'Personnalisé'"></span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end space-x-2">
                                        <button @click="editRole(role)" class="btn btn-sm btn-secondary" title="Modifier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <template x-if="!role.is_system">
                                            <button @click="deleteRole(role)" class="btn btn-sm btn-danger" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal création/édition de rôle -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal()"></div>
            <div class="modal-content relative w-full max-w-3xl">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editMode ? 'Modifier le rôle' : 'Nouveau rôle'"></h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveRole()">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Nom du rôle *</label>
                                    <input type="text" x-model="form.nom" class="input" required>
                                </div>
                                <div>
                                    <label class="label">Description</label>
                                    <input type="text" x-model="form.description" class="input">
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="label text-base font-semibold">Permissions</label>
                                <p class="text-xs text-gray-500 mb-3">Cochez les permissions à attribuer à ce rôle.</p>

                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <template x-for="[module, perms] in Object.entries(permissionsGrouped)" :key="module">
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="font-medium text-gray-900 dark:text-white capitalize" x-text="module"></h4>
                                                <button type="button" @click="toggleModule(module, perms)"
                                                        class="text-xs text-primary-600 hover:text-primary-800">
                                                    Tout cocher/décocher
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                <template x-for="perm in perms" :key="perm.id">
                                                    <label class="flex items-center space-x-2 cursor-pointer">
                                                        <input type="checkbox" :value="perm.id" x-model.number="form.permissionIds"
                                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="perm.action"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 mt-6">
                                <button type="button" @click="closeModal()" class="btn btn-secondary">Annuler</button>
                                <button type="submit" class="btn btn-primary" :disabled="loading" x-text="loading ? 'Enregistrement...' : 'Enregistrer'"></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rolesManager', () => ({
        roles: <?= $rolesJson ?>,
        permissionsGrouped: <?= $permsGroupedJson ?>,
        modalOpen: false,
        editMode: false,
        editId: null,
        loading: false,
        form: {
            nom: '',
            description: '',
            permissionIds: []
        },

        openModal() {
            this.editMode = false;
            this.editId = null;
            this.form = { nom: '', description: '', permissionIds: [] };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        editRole(role) {
            this.editMode = true;
            this.editId = role.id;
            this.form = {
                nom: role.nom,
                description: role.description || '',
                permissionIds: (role.permissions || []).map(p => p.id)
            };
            this.modalOpen = true;
        },

        toggleModule(module, perms) {
            const ids = perms.map(p => p.id);
            const allChecked = ids.every(id => this.form.permissionIds.includes(id));
            if (allChecked) {
                this.form.permissionIds = this.form.permissionIds.filter(id => !ids.includes(id));
            } else {
                ids.forEach(id => {
                    if (!this.form.permissionIds.includes(id)) {
                        this.form.permissionIds.push(id);
                    }
                });
            }
        },

        async saveRole() {
            this.loading = true;
            try {
                const url = this.editMode ? '/api/admin/roles/' + this.editId : '/api/admin/roles';
                const method = this.editMode ? 'PUT' : 'POST';
                const result = await App.api(url, method, this.form);

                if (this.editMode) {
                    App.notify('Rôle mis à jour - rechargement...', 'success');
                    // Reload to refresh sidebar/buttons with updated permissions
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.roles.push(result.data);
                    App.notify('Rôle créé', 'success');
                }
                this.closeModal();
            } catch (e) {
                App.notify(e.message || 'Erreur', 'error');
            } finally {
                this.loading = false;
            }
        },

        async deleteRole(role) {
            const ok = await App.confirm({
                title: 'Supprimer le rôle ?',
                message: 'Supprimer le rôle "' + role.nom + '" ? Les utilisateurs associés perdront ce rôle.',
                confirmText: 'Supprimer',
                cancelText: 'Annuler',
                type: 'danger'
            });
            if (!ok) return;

            try {
                await App.api('/api/admin/roles/' + role.id, 'DELETE');
                this.roles = this.roles.filter(r => r.id !== role.id);
                App.notify('Rôle supprimé', 'success');
            } catch (e) {
                App.notify(e.message, 'error');
            }
        }
    }));
});
</script>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
