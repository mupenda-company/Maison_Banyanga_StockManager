<?php 
$pageTitle = 'Détail client';
$paramsModel = new Parametre();
$appParams = $paramsModel->getPersonnalisation();
ob_start();
?>

<div x-data="clientPage" class="space-y-6">
    <!-- Fil d'Ariane -->
    <div class="mb-6">
        <a href="<?= url('clients') ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour aux clients
        </a>
    </div>

    <form method="GET" class="card">
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="label">Date début</label>
                <input type="date" name="date_debut" class="input" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
            </div>
            <div>
                <label class="label">Date fin</label>
                <input type="date" name="date_fin" class="input" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="<?= url('clients/' . $client['id']) ?>" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Infos client -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($client['nom']) ?></h2>
                    <button @click="openEdit()" class="btn btn-sm btn-secondary">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier
                    </button>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">N° client</p>
                            <p class="font-medium"><?= htmlspecialchars($client['numero_client'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Téléphone</p>
                            <p class="font-medium"><?= htmlspecialchars($client['telephone'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Zone</p>
                            <p class="font-medium"><?= htmlspecialchars($client['zone_nom'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Adresse</p>
                            <p class="font-medium"><?= htmlspecialchars($client['adresse'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="stat-card">
                    <p class="stat-label">Caisses achetées</p>
                    <p class="stat-value"><?= number_format((int)($kpis['caisses_achetees'] ?? 0), 0, '.', ' ') ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Chiffre d'affaires</p>
                    <p class="stat-value"><?= format_money_converted($kpis['ca_total'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Caisses retournées</p>
                    <p class="stat-value"><?= number_format((int)($kpis['caisses_retournees'] ?? 0), 0, '.', ' ') ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Ristourne</p>
                    <p class="stat-value"><?= number_format((float)($kpis['taux_ristourne'] ?? 5), 2, '.', ' ') ?>%</p>
                </div>
            </div>
            
            <!-- Historique des achats -->
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Historique des achats</h3>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($achats)): ?>
                    <div class="p-6 text-center text-gray-500">Aucun achat enregistré</div>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>N° Facture</th>
                                    <th class="text-right">Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($achats as $achat): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($achat['date_vente'])) ?></td>
                                    <td><?= htmlspecialchars($achat['numero_facture']) ?></td>
                                    <td class="text-right font-medium"><?= format_money_converted($achat['total_ttc'] ?? 0) ?></td>
                                    <td>
                                        <span class="<?= $achat['statut'] === 'validee' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($achat['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-sm text-gray-500">Montant ristourne estimé</p>
                    <p class="text-3xl font-bold text-primary-600">
                        <?= format_money_converted($kpis['montant_ristourne'] ?? 0) ?>
                    </p>
                </div>
            </div>

            <!-- Ristourne -->
            <div class="card">
                <div class="card-header"><h3 class="font-semibold">Ristourne en attente</h3></div>
                <div class="card-body">
                    <?php if ($ristourne): ?>
                    <div class="space-y-4 text-center">
                        <p class="text-2xl font-bold text-green-600"><?= format_money_converted($ristourne['montant_accumule'] ?? 0) ?></p>
                        <button @click="payerRistourne(<?= $ristourne['id'] ?>)" class="btn btn-primary w-full" :disabled="loading">Payer</button>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 text-center">Taux client: <?= number_format((float)($kpis['taux_ristourne'] ?? 5), 2, '.', ' ') ?>%</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'édition -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="closeEdit()"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold mb-6">Modifier le client</h3>
                <form @submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="label">Nom</label>
                            <input type="text" x-model="form.nom" class="input" required>
                        </div>
                        <div>
                            <label class="label">Téléphone</label>
                            <input type="tel" x-model="form.telephone" class="input">
                        </div>
                        <div>
                            <label class="label">Adresse</label>
                            <textarea x-model="form.adresse" class="input" rows="2"></textarea>
                        </div>
                        <div>
                            <label class="label">Taux ristourne (%)</label>
                            <input type="number" x-model.number="form.taux_ristourne" class="input" step="0.01" min="0">
                        </div>
                        <div>
                            <label class="label">Zone</label>
                            <select x-model="form.zone_id" class="input" required>
                                <?php 
                                $zoneModel = new Zone();
                                foreach($zoneModel->all() as $z): ?>
                                    <option value="<?= $z['id'] ?>" <?= $z['id'] == $client['zone_id'] ? 'selected' : '' ?>><?= htmlspecialchars($z['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="closeEdit()" class="btn btn-secondary">Annuler</button>
                        <button type="submit" class="btn btn-primary" :disabled="loading">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    if (Alpine.data('clientPage')) return;
    
    Alpine.data('clientPage', () => ({
        isModalOpen: false,
        loading: false,
        form: {
            id: '<?= $client['id'] ?>',
            nom: '<?= addslashes($client['nom']) ?>',
            telephone: '<?= addslashes($client['telephone'] ?? '') ?>',
            adresse: '<?= addslashes($client['adresse'] ?? '') ?>',
            zone_id: '<?= $client['zone_id'] ?>',
            taux_ristourne: '<?= addslashes($client['taux_ristourne'] ?? 5) ?>'
        },
        openEdit() { this.isModalOpen = true; },
        closeEdit() { this.isModalOpen = false; },
        async save() {
            if (this.loading) return;
            const ok = await App.confirm({
                title: 'Modifier le client ?',
                message: 'Confirmer la modification de ce client ?',
                confirmText: 'Modifier',
                cancelText: 'Annuler',
                type: 'info'
            });
            if (!ok) return;
            this.loading = true;
            try {
                await App.api('/api/clients', 'POST', this.form);
                App.notify('Client mis à jour', 'success');
                setTimeout(() => location.reload(), 500);
            } catch (e) { App.notify(e.message, 'error'); }
            finally { this.loading = false; }
        },
        async payerRistourne(id) {
            if (this.loading) return;
            const ok = await App.confirm({
                title: 'Payer la ristourne ?',
                message: 'Payer cette ristourne ?',
                confirmText: 'Payer',
                cancelText: 'Annuler',
                type: 'warning'
            });
            if (!ok) return;
            this.loading = true;
            try {
                await App.api(`/api/ristournes/${id}/payer`, 'POST');
                App.notify('Ristourne payée', 'success');
                setTimeout(() => location.reload(), 500);
            } catch (e) { App.notify(e.message, 'error'); }
            finally { this.loading = false; }
        }
    }));
});
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
