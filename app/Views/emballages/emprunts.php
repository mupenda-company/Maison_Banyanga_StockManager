<?php
$pageTitle = 'Emprunts / prets';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Emprunts / prets</h1>
        <p class="text-gray-500 dark:text-gray-400">Produits pleins et emballages vides empruntes ou pretes avec un client, distributeur ou personne externe</p>
    </div>
    <?php if (can('emballages.gerer')): ?>
    <button type="button" onclick="openEmpruntModal()" class="btn btn-primary">Nouvelle operation</button>
    <?php endif; ?>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Sens</label>
                <select name="direction" class="input">
                    <option value="">Tous</option>
                    <option value="recu" <?= ($filters['direction'] ?? '') === 'recu' ? 'selected' : '' ?>>On emprunte a</option>
                    <option value="donne" <?= ($filters['direction'] ?? '') === 'donne' ? 'selected' : '' ?>>On prete a</option>
                </select>
            </div>
            <div>
                <label class="label">Type</label>
                <select name="type_stock" class="input">
                    <option value="">Tous</option>
                    <option value="vide" <?= ($filters['type_stock'] ?? '') === 'vide' ? 'selected' : '' ?>>Emballages vides</option>
                    <option value="plein" <?= ($filters['type_stock'] ?? '') === 'plein' ? 'selected' : '' ?>>Produits pleins</option>
                </select>
            </div>
            <div>
                <label class="label">Partenaire</label>
                <select name="source_type" class="input">
                    <option value="">Tous</option>
                    <option value="client" <?= ($filters['source_type'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                    <option value="externe" <?= ($filters['source_type'] ?? '') === 'externe' ? 'selected' : '' ?>>Externe</option>
                </select>
            </div>
            <div>
                <label class="label">Statut</label>
                <select name="statut" class="input">
                    <option value="">Tous</option>
                    <option value="en_cours" <?= ($filters['statut'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="solde" <?= ($filters['statut'] ?? '') === 'solde' ? 'selected' : '' ?>>Solde</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="<?= url('emballages/emprunts') ?>" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sens</th>
                        <th>Type</th>
                        <th>Partenaire</th>
                        <th>Produit</th>
                        <th class="text-right">Quantite</th>
                        <th class="text-right">Utilise</th>
                        <th class="text-right">Reste</th>
                        <th>Emplacement</th>
                        <th>Statut</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($emprunts)): ?>
                    <tr><td colspan="11" class="text-center p-8 text-gray-500">Aucune operation enregistree</td></tr>
                    <?php else: ?>
                        <?php foreach ($emprunts as $emprunt): ?>
                        <?php $isPlein = ($emprunt['type_stock'] ?? 'vide') === 'plein'; ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                            <td>
                                <?php if (($emprunt['direction'] ?? 'recu') === 'donne'): ?>
                                <span class="badge-danger">On prete</span>
                                <?php else: ?>
                                <span class="badge-success">On emprunte</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="<?= $isPlein ? 'badge-info' : 'badge-warning' ?>"><?= $isPlein ? 'Produits pleins' : 'Emballages vides' ?></span></td>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($emprunt['source_type'] === 'client' ? ($emprunt['client_nom'] ?? 'Client') : ($emprunt['source_nom'] ?? 'Externe')) ?></div>
                                <div class="text-[10px] text-gray-500"><?= $emprunt['source_type'] === 'client' ? 'Client' : htmlspecialchars($emprunt['source_contact'] ?? 'Externe') ?></div>
                            </td>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($emprunt['produit_nom']) ?></div>
                                <div class="text-[10px] text-gray-500"><?= htmlspecialchars($emprunt['produit_code']) ?></div>
                            </td>
                            <td class="text-right font-bold"><?= number_format((int) $emprunt['quantite_empruntee'], 0, ',', ' ') ?> cs</td>
                            <td class="text-right"><?= number_format((int) $emprunt['quantite_utilisee'], 0, ',', ' ') ?> cs</td>
                            <td class="text-right font-bold text-orange-600"><?= number_format((int) $emprunt['reste_caisses'], 0, ',', ' ') ?> cs</td>
                            <td><?= htmlspecialchars($emprunt['emplacement_nom']) ?></td>
                            <td><?= $emprunt['statut'] === 'solde' ? '<span class="badge-success">Solde</span>' : '<span class="badge-warning">En cours</span>' ?></td>
                            <td class="text-right">
                                <?php if (can('emballages.gerer') && $emprunt['statut'] === 'en_cours' && (int) $emprunt['reste_caisses'] > 0): ?>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openRemboursementModal(<?= (int) $emprunt['id'] ?>, <?= (int) $emprunt['reste_caisses'] ?>, '<?= htmlspecialchars($emprunt['source_type'] === 'client' ? ($emprunt['client_nom'] ?? 'Client') : ($emprunt['source_nom'] ?? 'Externe'), ENT_QUOTES, 'UTF-8') ?>')">
                                    <?= ($emprunt['direction'] ?? 'recu') === 'donne' ? 'Retour recu' : 'Rembourser' ?>
                                </button>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (can('emballages.gerer')): ?>
<div x-data="remboursementModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Traiter l'operation</h3>
            <p class="text-sm text-gray-500 mb-6" x-text="sourceLabel"></p>
            <form @submit.prevent="save">
                <div class="space-y-4">
                    <div>
                        <label class="label">Caisses a remettre</label>
                        <input type="number" x-model.number="form.quantite_caisses" class="input" min="1" step="1" :max="resteCaisses" required>
                        <p class="text-xs text-gray-500 mt-1">Reste a remettre: <span x-text="resteCaisses"></span> cs</p>
                    </div>
                    <div>
                        <label class="label">Emplacement utilise</label>
                        <select x-model="form.emplacement_id" class="input" required>
                            <?php foreach ($emplacements as $emplacement): ?>
                            <option value="<?= (int) $emplacement['id'] ?>"><?= htmlspecialchars($emplacement['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div x-data="empruntModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Nouvelle operation</h3>
            <form @submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Sens</label>
                        <select x-model="form.direction" class="input" required>
                            <option value="recu">On emprunte a</option>
                            <option value="donne">On prete a</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Type</label>
                        <select x-model="form.type_stock" class="input" required>
                            <option value="vide">Emballages vides</option>
                            <option value="plein">Produits pleins</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Partenaire</label>
                        <select x-model="form.source_type" class="input" required>
                            <option value="client">Client</option>
                            <option value="externe">Distributeur / personne externe</option>
                        </select>
                    </div>
                    <div x-show="form.source_type === 'client'">
                        <label class="label">Client</label>
                        <select x-model="form.client_id" class="input" :required="form.source_type === 'client'">
                            <option value="">Selectionner</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id'] ?>"><?= htmlspecialchars($client['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div x-show="form.source_type === 'externe'">
                        <label class="label">Nom externe</label>
                        <input type="text" x-model="form.source_nom" class="input" :required="form.source_type === 'externe'">
                    </div>
                    <div>
                        <label class="label">Contact</label>
                        <input type="text" x-model="form.source_contact" class="input">
                    </div>
                    <div>
                        <label class="label">Produit</label>
                        <select x-model="form.produit_id" class="input" required>
                            <option value="">Selectionner</option>
                            <?php foreach ($produits as $produit): ?>
                            <option value="<?= (int) $produit['id'] ?>"><?= htmlspecialchars($produit['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Quantite (cs)</label>
                        <input type="number" x-model.number="form.quantite_empruntee" class="input" min="1" step="1" required>
                    </div>
                    <div>
                        <label class="label">Emplacement</label>
                        <select x-model="form.emplacement_id" class="input" required>
                            <?php foreach ($emplacements as $emplacement): ?>
                            <option value="<?= (int) $emplacement['id'] ?>"><?= htmlspecialchars($emplacement['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Date</label>
                        <input type="date" x-model="form.date_emprunt" class="input" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Notes</label>
                        <textarea x-model="form.notes" class="input" rows="2"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="close()" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('remboursementModal', () => ({
        isOpen: false,
        loading: false,
        empruntId: null,
        resteCaisses: 0,
        sourceLabel: '',
        form: { quantite_caisses: 1, emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>' },
        open(id, reste, sourceLabel) {
            this.empruntId = id;
            this.resteCaisses = parseInt(reste || 0, 10) || 0;
            this.sourceLabel = sourceLabel;
            this.form = { quantite_caisses: this.resteCaisses, emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>' };
            this.isOpen = true;
        },
        close() { this.isOpen = false; },
        async save() {
            this.loading = true;
            try {
                const result = await App.api(`/api/emballages/emprunts/${this.empruntId}/rembourser`, 'POST', this.form);
                App.notify(result.message || 'Operation enregistree', 'success');
                setTimeout(() => location.reload(), 800);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));

    Alpine.data('empruntModal', () => ({
        isOpen: false,
        loading: false,
        form: {},
        reset() {
            this.form = {
                direction: 'recu',
                type_stock: 'vide',
                source_type: 'client',
                client_id: '',
                source_nom: '',
                source_contact: '',
                produit_id: '',
                quantite_empruntee: 1,
                emplacement_id: '<?= $emplacements[0]['id'] ?? '' ?>',
                date_emprunt: new Date().toISOString().split('T')[0],
                notes: ''
            };
        },
        open() { this.reset(); this.isOpen = true; },
        close() { this.isOpen = false; },
        async save() {
            this.loading = true;
            try {
                const result = await App.api('/api/emballages/emprunts', 'POST', this.form);
                App.notify(result.message || 'Operation enregistree', 'success');
                setTimeout(() => location.reload(), 800);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));
});

function openEmpruntModal() {
    Alpine.$data(document.querySelector('[x-data="empruntModal"]')).open();
}

function openRemboursementModal(id, reste, sourceLabel) {
    Alpine.$data(document.querySelector('[x-data="remboursementModal"]')).open(id, reste, sourceLabel);
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>