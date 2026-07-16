<?php
$pageTitle = 'Emprunts / prets';
$activeFilters = array_filter($filters ?? [], function ($value) {
    return $value !== null && $value !== '';
});
$printUrl = '?' . http_build_query(array_merge($activeFilters, ['print' => 1]));
$exportUrl = '?' . http_build_query(array_merge($activeFilters, ['export' => 'excel']));
ob_start();
?>

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Emprunts / prets</h1>
        <p class="text-gray-500 dark:text-gray-400">Produits pleins et emballages vides empruntes ou pretes avec un client, distributeur ou personne externe</p>
    </div>
    <div class="flex flex-wrap gap-2 lg:justify-end">
        <a href="<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-secondary">Imprimer</a>
        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Exporter Excel</a>
        <?php if (can('emballages.gerer')): ?>
        <button type="button" onclick="openEmpruntModal()" class="btn btn-primary">Nouvelle operation</button>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="label">Sens</label>
                <select name="direction" class="input">
                    <option value="">Tous</option>
                    <option value="recu" <?= ($filters['direction'] ?? '') === 'recu' ? 'selected' : '' ?>>Emprunter</option>
                    <option value="donne" <?= ($filters['direction'] ?? '') === 'donne' ? 'selected' : '' ?>>Prêter</option>
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
            <div>
                <label class="label">Du</label>
                <input type="date" name="date_debut" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>" class="input">
            </div>
            <div>
                <label class="label">Au</label>
                <input type="date" name="date_fin" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>" class="input">
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
                                <?php if ((int) ($emprunt['nombre_produits'] ?? 1) > 1): ?>
                                <div class="font-medium"><?= (int) $emprunt['nombre_produits'] ?> produits</div>
                                <div class="text-[10px] text-gray-500">Voir le détail</div>
                                <?php else: ?>
                                <div class="font-medium"><?= htmlspecialchars($emprunt['produit_nom']) ?></div>
                                <div class="text-[10px] text-gray-500"><?= htmlspecialchars($emprunt['produit_code']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-bold"><?= number_format((int) $emprunt['quantite_empruntee'], 0, ',', ' ') ?> cs</td>
                            <td class="text-right"><?= number_format((int) $emprunt['quantite_utilisee'], 0, ',', ' ') ?> cs</td>
                            <td class="text-right font-bold text-orange-600"><?= number_format((int) $emprunt['reste_caisses'], 0, ',', ' ') ?> cs</td>
                            <td><?= htmlspecialchars($emprunt['emplacement_nom']) ?></td>
                            <td><?= $emprunt['statut'] === 'solde' ? '<span class="badge-success">Solde</span>' : '<span class="badge-warning">En cours</span>' ?></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                <a href="<?= url('emballages/emprunts/' . (int) $emprunt['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-secondary" title="Imprimer le bon signé">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12-4h12v8H6v-8z"/></svg>
                                </a>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openDetailsModal(<?= (int) $emprunt['id'] ?>)" title="Voir le detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <?php if (can('emballages.gerer') && $emprunt['statut'] === 'en_cours' && (int) $emprunt['quantite_utilisee'] === 0 && (int) $emprunt['quantite_retournee'] === 0): ?>
                                <button type="button" class="btn btn-sm btn-primary" onclick='openEditEmpruntModal(<?= htmlspecialchars(json_encode($emprunt), ENT_QUOTES, 'UTF-8') ?>)' title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteEmprunt(<?= (int) $emprunt['id'] ?>)" title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                <?php endif; ?>
                                <?php if (can('emballages.gerer') && (int) ($emprunt['nombre_produits'] ?? 1) === 1 && $emprunt['statut'] === 'en_cours' && (int) $emprunt['reste_caisses'] > 0): ?>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openRemboursementModal(<?= (int) $emprunt['id'] ?>, <?= (int) $emprunt['reste_caisses'] ?>, '<?= htmlspecialchars($emprunt['source_type'] === 'client' ? ($emprunt['client_nom'] ?? 'Client') : ($emprunt['source_nom'] ?? 'Externe'), ENT_QUOTES, 'UTF-8') ?>')" title="<?= ($emprunt['direction'] ?? 'recu') === 'donne' ? 'Retour recu' : 'Rembourser' ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 010 8H7m-4-8l4-4m-4 4l4 4"/></svg>
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

<div x-data="detailsEmpruntModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail de l'operation</h3>
                    <p class="text-sm text-gray-500" x-text="operation.operation_ref || ''"></p>
                </div>
                <button type="button" @click="close()" class="btn btn-secondary btn-sm">Fermer</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5 text-sm">
                <div>
                    <div class="text-gray-500">Sens</div>
                    <div class="font-semibold" x-text="labelDirection(operation.direction)"></div>
                </div>
                <div>
                    <div class="text-gray-500">Type</div>
                    <div class="font-semibold" x-text="operation.type_stock === 'plein' ? 'Produits pleins' : 'Emballages vides'"></div>
                </div>
                <div>
                    <div class="text-gray-500">Date</div>
                    <div class="font-semibold" x-text="formatDate(operation.date_emprunt)"></div>
                </div>
                <div>
                    <div class="text-gray-500">Partenaire</div>
                    <div class="font-semibold" x-text="partnerName(operation)"></div>
                </div>
                <div>
                    <div class="text-gray-500">Contact</div>
                    <div class="font-semibold" x-text="operation.source_contact || '-'"></div>
                </div>
                <div>
                    <div class="text-gray-500">Emplacement</div>
                    <div class="font-semibold" x-text="operation.emplacement_nom || '-'"></div>
                </div>
            </div>
            <div class="table-container border border-gray-200 dark:border-gray-700 rounded-md">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-right">Emprunte / prete</th>
                            <th class="text-right">Utilise</th>
                            <th class="text-right">Retourne</th>
                            <th class="text-right">Reste</th>
                            <th class="text-right">Actions / bons</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="ligne in lignes" :key="ligne.id">
                            <tr>
                                <td>
                                    <div class="font-medium" x-text="ligne.produit_nom"></div>
                                    <div class="text-[10px] text-gray-500" x-text="ligne.produit_code"></div>
                                </td>
                                <td class="text-right font-semibold" x-text="formatCs(ligne.quantite_empruntee)"></td>
                                <td class="text-right" x-text="formatCs(ligne.quantite_utilisee)"></td>
                                <td class="text-right" x-text="formatCs(ligne.quantite_retournee)"></td>
                                <td class="text-right font-bold text-orange-600" x-text="formatCs(ligne.reste_caisses)"></td>
                                <td class="text-right">
                                    <div class="flex flex-col items-end gap-1">
                                    <?php if (can('emballages.gerer')): ?>
                                    <button type="button" class="btn btn-sm btn-secondary"
                                            x-show="ligne.statut === 'en_cours' && parseInt(ligne.reste_caisses || 0, 10) > 0"
                                            @click="openRemboursementModal(parseInt(ligne.id, 10), parseInt(ligne.reste_caisses, 10), partnerName(operation))">
                                        Traiter
                                    </button>
                                    <?php endif; ?>
                                    <template x-for="remboursement in (ligne.remboursements || [])" :key="remboursement.id">
                                        <a class="text-xs text-primary-600 hover:underline" target="_blank"
                                           :href="<?= json_encode(url('emballages/emprunts/remboursements/')) ?> + remboursement.id + '/print'"
                                           x-text="'Bon du ' + formatDateTime(remboursement.created_at)"></a>
                                    </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="mt-4" x-show="operation.notes">
                <div class="text-sm text-gray-500 mb-1">Notes</div>
                <div class="text-sm bg-gray-50 dark:bg-gray-900/40 rounded-md p-3" x-text="operation.notes"></div>
            </div>
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

<div x-data="editEmpruntModal" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Modifier l'operation</h3>
            <form @submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Sens</label>
                        <select x-model="form.direction" class="input" required>
                            <option value="recu">Emprunter</option>
                            <option value="donne">Prêter</option>
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
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="label mb-0">Produits de l'operation</label>
                            <button type="button" class="btn btn-secondary btn-sm" @click="form.lignes.push({ produit_id: '', quantite_empruntee: 1 })">Ajouter produit</button>
                        </div>
                        <template x-for="(ligne, index) in form.lignes" :key="index">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2 items-center">
                                <select x-model="ligne.produit_id" class="input" required>
                                    <option value="">Selectionner</option>
                                    <template x-for="produit in produits" :key="produit.id">
                                        <option :value="String(produit.id)" x-text="produit.nom"></option>
                                    </template>
                                </select>
                                <input type="number" x-model.number="ligne.quantite_empruntee" class="input" min="1" step="1" required>
                                <button type="button" class="btn btn-danger btn-sm h-10 w-11 px-0" @click="form.lignes.splice(index, 1)" :disabled="form.lignes.length === 1">X</button>
                            </div>
                        </template>
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
                        <textarea x-model="form.notes" class="input" rows="3"></textarea>
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
                            <option value="recu">Emprunter</option>
                            <option value="donne">Prêter</option>
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
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="label mb-0">Produits de l'operation</label>
                            <button type="button" class="btn btn-secondary btn-sm" @click="form.lignes.push({ produit_id: '', quantite_empruntee: 1 })">Ajouter produit</button>
                        </div>
                        <template x-for="(ligne, index) in form.lignes" :key="index">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2 items-center">
                                <select x-model="ligne.produit_id" class="input" required>
                                    <option value="">Selectionner</option>
                                    <template x-for="produit in produits" :key="produit.id">
                                        <option :value="String(produit.id)" x-text="produit.nom"></option>
                                    </template>
                                </select>
                                <input type="number" x-model.number="ligne.quantite_empruntee" class="input" min="1" step="1">
                                <button type="button" class="btn btn-danger btn-sm h-10 w-11 px-0" @click="form.lignes.splice(index, 1)" :disabled="form.lignes.length === 1">X</button>
                            </div>
                        </template>
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

<?php endif; ?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('detailsEmpruntModal', () => ({
        isOpen: false,
        loading: false,
        operation: {},
        lignes: [],
        async open(id) {
            this.loading = true;
            this.operation = {};
            this.lignes = [];
            this.isOpen = true;
            try {
                const result = await App.api('/api/emballages/emprunts/' + id, 'GET');
                this.lignes = result.data?.lignes || result.lignes || [];
                this.operation = {
                    operation_ref: result.data?.operation_ref || result.operation_ref || '',
                    ...(this.lignes[0] || {})
                };
            } catch (e) {
                this.close();
                App.notify(e.message || 'Impossible de charger le detail', 'error');
            } finally {
                this.loading = false;
            }
        },
        close() { this.isOpen = false; },
        formatCs(value) {
            return (parseInt(value || 0, 10)).toLocaleString('fr-FR') + ' cs';
        },
        formatDate(value) {
            if (!value) return '-';
            return new Date(value + 'T00:00:00').toLocaleDateString('fr-FR');
        },
        formatDateTime(value) {
            if (!value) return '-';
            return new Date(String(value).replace(' ', 'T')).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
        },
        labelDirection(direction) {
            return direction === 'donne' ? 'Prêter' : 'Emprunter';
        },
        partnerName(operation) {
            if ((operation.source_type || 'client') === 'client') {
                return operation.client_nom || 'Client';
            }
            return operation.source_nom || 'Externe';
        }
    }));

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
            const printWindow = window.open('about:blank', '_blank');
            try {
                const result = await App.api(`/api/emballages/emprunts/${this.empruntId}/rembourser`, 'POST', this.form);
                App.notify(result.message || 'Operation enregistree', 'success');
                const movementId = result.data?.mouvement_id || result.mouvement_id;
                if (printWindow && movementId) {
                    printWindow.location.href = <?= json_encode(url('emballages/emprunts/remboursements/')) ?> + movementId + '/print';
                } else if (printWindow) {
                    printWindow.close();
                }
                setTimeout(() => location.reload(), 1200);
            } catch (e) {
                if (printWindow) printWindow.close();
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    }));

    Alpine.data('editEmpruntModal', () => ({
        isOpen: false,
        loading: false,
        empruntId: null,
        produits: <?= json_encode($produits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        form: {
            direction: 'recu',
            type_stock: 'vide',
            source_type: 'client',
            client_id: '',
            source_nom: '',
            lignes: [{ produit_id: '', quantite_empruntee: 1 }],
            emplacement_id: '',
            date_emprunt: '',
            source_contact: '',
            notes: ''
        },
        async open(emprunt) {
            this.empruntId = emprunt.id;
            this.form = {
                direction: emprunt.direction || 'recu',
                type_stock: emprunt.type_stock || 'vide',
                source_type: emprunt.source_type || 'client',
                client_id: emprunt.client_id ? String(emprunt.client_id) : '',
                source_nom: emprunt.source_nom || '',
                lignes: [{ produit_id: emprunt.produit_id ? String(emprunt.produit_id) : '', quantite_empruntee: parseInt(emprunt.quantite_empruntee || 1, 10) }],
                emplacement_id: emprunt.emplacement_id ? String(emprunt.emplacement_id) : '<?= $emplacements[0]['id'] ?? '' ?>',
                date_emprunt: emprunt.date_emprunt || new Date().toISOString().split('T')[0],
                source_contact: emprunt.source_contact || '',
                notes: emprunt.notes || ''
            };
            this.isOpen = true;
            this.loading = true;
            try {
                const result = await App.api('/api/emballages/emprunts/' + emprunt.id, 'GET');
                const lignes = result.data?.lignes || result.lignes || [];
                if (lignes.length > 0) {
                    const first = lignes[0];
                    this.form.direction = first.direction || this.form.direction;
                    this.form.type_stock = first.type_stock || this.form.type_stock;
                    this.form.source_type = first.source_type || this.form.source_type;
                    this.form.client_id = first.client_id ? String(first.client_id) : '';
                    this.form.source_nom = first.source_nom || '';
                    this.form.source_contact = first.source_contact || '';
                    this.form.emplacement_id = first.emplacement_id ? String(first.emplacement_id) : this.form.emplacement_id;
                    this.form.date_emprunt = first.date_emprunt || this.form.date_emprunt;
                    this.form.notes = first.notes || '';
                    this.form.lignes = lignes.map(ligne => ({
                        produit_id: ligne.produit_id ? String(ligne.produit_id) : '',
                        quantite_empruntee: parseInt(ligne.quantite_empruntee || 1, 10)
                    }));
                }
            } catch (e) {
                App.notify(e.message || 'Impossible de charger les produits de l\'operation', 'error');
            } finally {
                this.loading = false;
            }
        },
        close() { this.isOpen = false; },
        async save() {
            this.loading = true;
            try {
                const lignes = (this.form.lignes || []).filter(l => l.produit_id && parseInt(l.quantite_empruntee || 0, 10) > 0);
                const result = await App.api('/api/emballages/emprunts/' + this.empruntId, 'PUT', { ...this.form, lignes });
                App.notify(result.message || 'Operation modifiee', 'success');
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                App.notify(e.message || 'Modification impossible', 'error');
            } finally {
                this.loading = false;
            }
        }
    }));

    Alpine.data('empruntModal', () => ({
        isOpen: false,
        loading: false,
        produits: <?= json_encode($produits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        form: {},
        reset() {
            this.form = {
                direction: 'recu',
                type_stock: 'vide',
                source_type: 'client',
                client_id: '',
                source_nom: '',
                source_contact: '',
                lignes: [{ produit_id: '', quantite_empruntee: 1 }],
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
                const lignes = (this.form.lignes || []).filter(l => l.produit_id && parseInt(l.quantite_empruntee || 0, 10) > 0);
                const payload = { ...this.form, lignes };
                const result = await App.api('/api/emballages/emprunts', 'POST', payload);
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

function openDetailsModal(id) {
    Alpine.$data(document.querySelector('[x-data="detailsEmpruntModal"]')).open(id);
}

function openEditEmpruntModal(emprunt) {
    Alpine.$data(document.querySelector('[x-data="editEmpruntModal"]')).open(emprunt);
}

async function deleteEmprunt(id) {
    const ok = await App.confirm({
        title: 'Supprimer l\'operation ?',
        message: 'Le stock sera restaure si l\'operation n\'a pas encore ete utilisee.',
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
        type: 'danger'
    });
    if (!ok) return;
    try {
        await App.api('/api/emballages/emprunts/' + id, 'DELETE');
        App.notify('Operation supprimee', 'success');
        setTimeout(() => location.reload(), 500);
    } catch (e) {
        App.notify(e.message || 'Suppression impossible', 'error');
    }
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
