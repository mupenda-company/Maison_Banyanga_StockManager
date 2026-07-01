<?php 
$pageTitle = 'Clients';
$stats = $stats ?? ['total' => 0, 'actifs' => 0, 'non_actifs' => 0];
$activite = $activite ?? 'tous';
ob_start();
?>

<div x-data="clientList" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="stat-card">
            <p class="stat-label">Total clients</p>
            <p class="stat-value text-primary-600"><?= number_format((int) $stats['total'], 0, ',', ' ') ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Clients actifs</p>
            <p class="stat-value text-green-600"><?= number_format((int) $stats['actifs'], 0, ',', ' ') ?></p>
            <p class="text-xs text-gray-400 mt-1">Au moins une vente validee</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Clients non actifs</p>
            <p class="stat-value text-orange-600"><?= number_format((int) $stats['non_actifs'], 0, ',', ' ') ?></p>
            <p class="text-xs text-gray-400 mt-1">Aucune vente validee</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Liste des clients</h2>
            <form method="get" class="flex flex-col sm:flex-row sm:items-center gap-3">
                <input
                    type="search"
                    name="q"
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    placeholder="Rechercher un client..."
                    class="input w-full sm:w-64"
                >
                <select name="zone_id" class="input w-auto">
                    <option value="">Toutes les zones</option>
                    <?php foreach ($zones as $zone): ?>
                    <option value="<?= $zone['id'] ?>" <?= ($selectedZoneId ?? '') == $zone['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($zone['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="activite" class="input w-auto">
                    <option value="tous" <?= $activite === 'tous' ? 'selected' : '' ?>>Tous</option>
                    <option value="actif" <?= $activite === 'actif' ? 'selected' : '' ?>>Actifs</option>
                    <option value="non_actif" <?= $activite === 'non_actif' ? 'selected' : '' ?>>Non actifs</option>
                </select>
                <button type="submit" class="btn btn-secondary">Rechercher</button>
                <a href="?<?= http_build_query(array_merge($_GET, ['print' => 1])) ?>" target="_blank" class="btn btn-secondary">Imprimer</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-secondary">Exporter</a>
                <?php if (can('clients.creer')): ?>
                <button type="button" @click="openModal()" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau client
                </button>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>N° client</th>
                            <th>Téléphone</th>
                            <th>Zone</th>
                            <th>Ristourne</th>
                            <th>Adresse</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Aucun client trouvé
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($client['nom']) ?></div>
                                    <?php if ($client['email']): ?>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($client['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($client['numero_client'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($client['telephone'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-info"><?= htmlspecialchars($client['zone_nom'] ?? 'Non définie') ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= ((float)($client['taux_ristourne'] ?? 5)) > 5 ? 'badge-warning' : 'badge-info' ?>">
                                        <?= number_format((float)($client['taux_ristourne'] ?? 5), 2, '.', ' ') ?>%
                                    </span>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($client['adresse'] ?? '-') ?></td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="<?= url('clients/' . $client['id']) ?>" class="text-blue-500 hover:text-blue-700" title="Voir détails">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <button @click="editClient(<?= htmlspecialchars(json_encode($client)) ?>)" class="text-primary-600 hover:text-primary-700" title="Modifier" <?php if (!can('clients.modifier')): ?>style="display:none"<?php endif; ?>>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <?php if (can('clients.supprimer')): ?>
                                        <button @click="deleteClient(<?= $client['id'] ?>)" class="text-red-500 hover:text-red-700" title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        <?php endif; ?>
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

    <!-- Modal Client -->
    <div x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="close()"></div>
            
            <div class="modal-content relative w-full max-w-lg">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editMode ? 'Modifier le client' : 'Nouveau client'"></h3>
                        <button @click="close()" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="save()">
                            <div class="space-y-4">
                                <div>
                                    <label class="label">Nom *</label>
                                    <input type="text" x-model="form.nom" class="input" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="label">Téléphone</label>
                                        <input type="tel" x-model="form.telephone" class="input">
                                    </div>
                                    <div>
                                        <label class="label">N° client</label>
                                        <input type="text" x-model="form.numero_client" class="input" placeholder="Numéro client">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="label">Email</label>
                                        <input type="email" x-model="form.email" class="input">
                                    </div>
                                    <div>
                                        <label class="label">Taux ristourne (%)</label>
                                        <input type="number" x-model.number="form.taux_ristourne" class="input" step="0.01" min="0">
                                    </div>
                                </div>
                                <div>
                                    <label class="label">Zone *</label>
                                    <select x-model="form.zone_id" class="input" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($zones as $zone): ?>
                                        <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Adresse</label>
                                    <textarea x-model="form.adresse" class="input" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3 mt-6">
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
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    if (Alpine.data('clientList')) return;
    
    Alpine.data('clientList', () => ({
        isOpen: false,
        editMode: false,
        editId: null,
        loading: false,
        form: { nom: '', telephone: '', numero_client: '', adresse: '', zone_id: '', email: '', taux_ristourne: 5 },
        
        openModal() {
            this.editMode = false;
            this.editId = null;
            this.form = { nom: '', telephone: '', numero_client: '', adresse: '', zone_id: '', email: '', taux_ristourne: 5 };
            this.isOpen = true;
        },
        
        close() { this.isOpen = false; },
        
        editClient(client) {
            this.editMode = true;
            this.editId = client.id;
            this.form = { ...client };
            this.isOpen = true;
        },
        
        async save() {
            const ok = await App.confirm({
                title: this.editMode ? 'Modifier le client ?' : 'Créer le client ?',
                message: this.editMode ? 'Confirmer la modification de ce client ?' : 'Confirmer la création de ce client ?',
                confirmText: this.editMode ? 'Modifier' : 'Créer',
                cancelText: 'Annuler',
                type: 'info'
            });
            if (!ok) return;

            this.loading = true;
            try {
                const url = this.editMode ? '/api/clients' : '/api/clients';
                // L'API utilise l'ID dans le body si présent pour la mise à jour
                const data = this.editMode ? { ...this.form, id: this.editId } : this.form;
                await App.api(url, 'POST', data);
                App.notify(this.editMode ? 'Client mis à jour' : 'Client créé');
                setTimeout(() => location.reload(), 500);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async deleteClient(id) {
            const ok = await App.confirm({
                title: 'Supprimer le client ?',
                message: 'Supprimer ce client ?',
                confirmText: 'Supprimer',
                cancelText: 'Annuler',
                type: 'danger'
            });
            if (!ok) return;
            try {
                await App.api(`/api/clients/${id}`, 'DELETE');
                App.notify('Client supprimé');
                setTimeout(() => location.reload(), 500);
            } catch (e) { App.notify(e.message, 'error'); }
        }
    }));
});
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
