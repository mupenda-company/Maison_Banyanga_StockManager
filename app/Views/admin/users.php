<?php 
$pageTitle = 'Gestion des utilisateurs';
$usersJson = json_encode($users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
ob_start();
?>

<div class="card" x-data="usersManager">
    <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Liste des utilisateurs</h2>
        <button @click="openModal()" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel utilisateur
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="users.length === 0">
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Aucun utilisateur trouvé
                            </td>
                        </tr>
                    </template>
                    <template x-for="user in users" :key="user.id">
                        <tr>
                            <td>
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-medium" x-text="(user.prenom?.charAt(0) + user.nom?.charAt(0)).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <div class="font-medium" x-text="user.prenom + ' ' + user.nom"></div>
                                        <div class="text-xs text-gray-500" x-text="'@' + user.username"></div>
                                    </div>
                                </div>
                            </td>
                            <td x-text="user.email"></td>
                            <td>
                                <span class="badge" 
                                      :class="user.role === 'admin' ? 'badge-danger' : (user.role === 'magasinier' ? 'badge-warning' : 'badge-info')"
                                      x-text="user.role.charAt(0).toUpperCase() + user.role.slice(1)">
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="user.actif ? 'badge-success' : 'badge-danger'" 
                                      x-text="user.actif ? 'Actif' : 'Inactif'"></span>
                            </td>
                            <td class="text-sm" x-text="user.derniere_connexion ? new Date(user.derniere_connexion).toLocaleString('fr-FR') : 'Jamais'"></td>
                            <td>
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="editUser(user)" class="btn btn-sm btn-secondary" title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="resetPassword(user)" class="btn btn-sm btn-warning" title="Réinitialiser mot de passe">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.878L11 17l-3-1-1 3-3-1 1-3-3-1 1-3 3.12.12A6 6 0 1121 9z"/>
                                        </svg>
                                    </button>
                                    <template x-if="user.id != <?= $_SESSION['user_id'] ?>">
                                        <button @click="toggleUser(user)" 
                                                :class="user.actif ? 'btn-danger' : 'btn-success'"
                                                class="btn btn-sm" 
                                                :title="user.actif ? 'Désactiver' : 'Activer'">
                                            <svg x-show="user.actif" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            <svg x-show="!user.actif" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
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

    <!-- Modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal()"></div>
            <div class="modal-content relative w-full max-w-lg">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editMode ? 'Modifier' : 'Nouvel utilisateur'"></h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveUser()">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Prénom *</label>
                                    <input type="text" x-model="form.prenom" class="input" required>
                                </div>
                                <div>
                                    <label class="label">Nom *</label>
                                    <input type="text" x-model="form.nom" class="input" required>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="label">Nom d'utilisateur *</label>
                                <input type="text" x-model="form.username" class="input" required>
                            </div>
                            <div class="mt-4">
                                <label class="label">Email *</label>
                                <input type="email" x-model="form.email" class="input" required>
                            </div>
                            <div class="mt-4">
                                <label class="label">Mot de passe <span x-show="editMode">(laisser vide pour ne pas modifier)</span><span x-show="!editMode">*</span></label>
                                <input type="password" x-model="form.password" class="input" :required="!editMode">
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="label">Rôle *</label>
                                    <select x-model="form.role" class="input" required>
                                        <option value="vendeur">Vendeur</option>
                                        <option value="magasinier">Magasinier</option>
                                        <option value="admin">Administrateur</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Statut</label>
                                    <select x-model.number="form.actif" class="input">
                                        <option value="1">Actif</option>
                                        <option value="0">Inactif</option>
                                    </select>
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
    Alpine.data('usersManager', () => ({
        users: <?= $usersJson ?>,
        modalOpen: false,
        editMode: false,
        editId: null,
        loading: false,
        form: {
            username: '', email: '', password: '', nom: '', prenom: '', role: 'vendeur', actif: 1
        },
        
        openModal() {
            this.editMode = false;
            this.editId = null;
            this.form = { username: '', email: '', password: '', nom: '', prenom: '', role: 'vendeur', actif: 1 };
            this.modalOpen = true;
        },
        
        closeModal() {
            this.modalOpen = false;
        },
        editUser(user) {
            this.editMode = true;
            this.editId = user.id;
            this.form = { ...user, password: '' };
            this.modalOpen = true;
        },
        
        async saveUser() {
            this.loading = true;
            try {
                const url = this.editMode ? '/api/admin/users/' + this.editId : '/api/admin/users';
                const method = this.editMode ? 'PUT' : 'POST';
                
                // On crée une copie propre des données pour l'envoi
                const formData = { ...this.form };
                
                const result = await App.api(url, method, formData);
                
                if (this.editMode) {
                    const idx = this.users.findIndex(u => u.id === this.editId);
                    if (idx !== -1) {
                        this.users[idx] = { ...result };
                    }
                    App.notify('Utilisateur mis à jour', 'success');
                } else {
                    // S'assurer que les données reçues sont ajoutées au tableau
                    if (result && result.id) {
                        this.users.push({ ...result });
                    } else {
                        throw new Error('Données reçues invalides');
                    }
                    App.notify('Utilisateur créé', 'success');
                }
                this.closeModal();
            } catch (e) {
                if (e.errors) {
                    // Afficher chaque erreur de validation
                    Object.values(e.errors).forEach(messages => {
                        messages.forEach(msg => App.notify(msg, 'error'));
                    });
                } else {
                    App.notify(e.message || 'Erreur', 'error');
                }
            } finally {
                this.loading = false;
            }
        },
        
        async toggleUser(user) {
            const action = user.actif ? 'désactiver' : 'activer';
            const ok = await App.confirm({
                title: (action.charAt(0).toUpperCase() + action.slice(1)) + ' le compte ?',
                message: (action.charAt(0).toUpperCase() + action.slice(1)) + ' ce compte ?',
                confirmText: action.charAt(0).toUpperCase() + action.slice(1),
                cancelText: 'Annuler',
                type: 'warning'
            });
            if (!ok) return;
            
            try {
                const result = await App.api('/api/admin/users/' + user.id, 'PUT', { actif: user.actif ? 0 : 1 });
                user.actif = user.actif ? 0 : 1;
                App.notify('Utilisateur ' + (user.actif ? 'activé' : 'désactivé'), 'success');
            } catch (e) {
                App.notify(e.message, 'error');
            }
        },
        
        async resetPassword(user) {
            const ok = await App.confirm({
                title: 'Réinitialiser le mot de passe ?',
                message: 'Réinitialiser le mot de passe de ' + user.prenom + ' ?',
                confirmText: 'Réinitialiser',
                cancelText: 'Annuler',
                type: 'warning'
            });
            if (!ok) return;
            try {
                const result = await App.api('/api/admin/users/' + user.id + '/reset-password', 'POST');
                App.notify('Mot de passe réinitialisé. Nouveau mot de passe: ' + result.data.password, 'success');
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
?>
