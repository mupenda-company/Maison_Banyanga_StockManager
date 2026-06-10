<?php
$pageTitle = 'Manquants agents';
$printMode = !empty($print_mode);
$query = array_filter($filters, fn($v) => $v !== null && $v !== '');
$periodeLabel = date('d/m/Y', strtotime($filters['date_debut'])) . ' au ' . date('d/m/Y', strtotime($filters['date_fin']));
$totalMontant = array_sum(array_map(fn($m) => (float) ($m['montant'] ?? 0), $manquants));
$totalPaye = array_sum(array_map(fn($m) => (float) ($m['montant_paye'] ?? 0), $manquants));
$totalReste = array_sum(array_map(fn($m) => (float) ($m['reste_montant'] ?? 0), $manquants));
$customStyle = "
@media print {
    @page { size: A4 portrait; margin: 10mm; }
    aside, header, .no-print, .fixed, button, .btn { display: none !important; }
    main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    body { background: #fff !important; }
    .report-sheet { box-shadow: none !important; padding: 0 !important; }
    .print-table th, .print-table td { border: 1px solid #d1d5db !important; padding: 6px !important; font-size: 10px !important; }
}
.report-sheet { background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(15,23,42,.08); }
.print-table { width: 100%; border-collapse: collapse; }
.print-table th { background: #111827; color: white; text-align: left; font-size: 11px; text-transform: uppercase; }
.print-table td { border-bottom: 1px solid #e5e7eb; padding: 8px; }
";
ob_start();
?>
<div x-data="manquantsPage()" class="report-sheet p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manquants agents</h1>
            <p class="text-sm text-gray-500">Rapport détaillé, paiements et restes à payer par agent.</p>
        </div>
        <div class="flex gap-2">
            <?php if (can('pertes.creer')): ?><a href="<?= url('manquants/create') ?>" class="btn btn-primary">Enregistrer un manquant</a><?php endif; ?>
            <a href="?<?= http_build_query(array_merge($query, ['export' => 1])) ?>" class="btn btn-secondary">Exporter CSV</a>
            <button type="button" onclick="window.open('?<?= http_build_query(array_merge($query, ['print' => 1])) ?>','_blank')" class="btn btn-secondary">Imprimer</button>
        </div>
    </div>

    <div class="hidden print:block mb-6 border-b-2 border-gray-900 pb-4">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold uppercase"><?= htmlspecialchars((new Parametre())->get('nom_entreprise', APP_NAME)) ?></h1>
                <p class="text-sm text-gray-600">Rapport professionnel des manquants agents</p>
                <p class="text-sm text-gray-600">Période : <strong><?= htmlspecialchars($periodeLabel) ?></strong></p>
            </div>
            <div class="text-right text-xs text-gray-600">
                <p>Imprimé le <?= date('d/m/Y H:i') ?></p>
                <p>Utilisateur : <?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="card mb-6 no-print">
        <div class="card-body">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div><label class="label">Agent</label><select name="agent_id" class="input">
                        <option value="">Tous</option><?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>" <?= ($filters['agent_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></option><?php endforeach; ?>
                    </select></div>
                <div><label class="label">Produit</label><select name="produit_id" class="input">
                        <option value="">Tous</option><?php foreach ($produits as $p): ?><option value="<?= $p['id'] ?>" <?= ($filters['produit_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?>
                    </select></div>
                <div><label class="label">Statut</label><select name="statut" class="input">
                        <option value="">Tous</option>
                        <option value="ouvert" <?= ($filters['statut'] ?? '') === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                        <option value="partiel" <?= ($filters['statut'] ?? '') === 'partiel' ? 'selected' : '' ?>>Partiel</option>
                        <option value="paye" <?= ($filters['statut'] ?? '') === 'paye' ? 'selected' : '' ?>>Payé</option>
                    </select></div>
                <div><label class="label">Date début</label><input type="date" name="date_debut" value="<?= htmlspecialchars($filters['date_debut']) ?>" class="input"></div>
                <div><label class="label">Date fin</label><input type="date" name="date_fin" value="<?= htmlspecialchars($filters['date_fin']) ?>" class="input"></div>
                <button class="btn btn-primary">Filtrer</button><a href="<?= url('manquants') ?>" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <p class="stat-label">Manquants</p>
            <p class="stat-value"><?= count($manquants) ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Montant total</p>
            <p class="stat-value text-red-600"><?= format_money_converted($totalMontant) ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Déjà payé</p>
            <p class="stat-value text-green-600"><?= format_money_converted($totalPaye) ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Reste à payer</p>
            <p class="stat-value text-orange-600"><?= format_money_converted($totalReste) ?></p>
        </div>
    </div>

    <?php if (!empty($resume)): ?>
        <div class="mb-6">
            <h2 class="text-sm font-bold uppercase text-gray-500 mb-2">Résumé par agent</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($resume as $r): ?>
                    <div class="rounded-lg border border-gray-200 p-3">
                        <p class="font-bold"><?= htmlspecialchars($r['agent_nom']) ?></p>
                        <p class="text-sm text-gray-500"><?= (int) $r['nombre'] ?> cas · <?= number_format((float) $r['total_caisses'], 2, ',', ' ') ?> cs</p>
                        <p class="text-sm">Payé: <strong class="text-green-600"><?= format_money_converted($r['total_paye']) ?></strong></p>
                        <p class="text-sm">Reste: <strong class="text-orange-600"><?= format_money_converted($r['total_reste']) ?></strong></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agent</th>
                    <th>Produit</th>
                    <th class="text-right">Caisses</th>
                    <th class="text-right">Montant</th>
                    <th class="text-right">Payé</th>
                    <th class="text-right">Reste</th>
                    <th>Statut</th>
                    <th>Motif</th>
                    <th class="no-print"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$manquants): ?><tr>
                        <td colspan="10" class="text-center py-8 text-gray-500">Aucun manquant trouvé.</td>
                    </tr><?php endif; ?>
                <?php foreach ($manquants as $m): ?>
                    <?php $reste = (float) ($m['reste_montant'] ?? 0);
                    $statut = $m['statut'] === 'regle' ? 'paye' : $m['statut']; ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($m['date_manquant'])) ?></td>
                        <td class="font-semibold"><?= htmlspecialchars($m['agent_nom']) ?></td>
                        <td><?= htmlspecialchars($m['produit_nom'] ?: '-') ?></td>
                        <td class="text-right font-bold"><?= number_format((float) $m['quantite_caisses'], 2, ',', ' ') ?></td>
                        <td class="text-right font-bold"><?= format_money_converted($m['montant']) ?></td>
                        <td class="text-right text-green-700 font-bold"><?= format_money_converted($m['montant_paye'] ?? 0) ?></td>
                        <td class="text-right text-orange-700 font-bold"><?= format_money_converted($reste) ?></td>
                        <td><?= $statut === 'paye' ? 'Payé' : ($statut === 'partiel' ? 'Partiel' : 'Ouvert') ?></td>
                        <td><?= htmlspecialchars($m['motif'] ?: '-') ?></td>
                        <td class="no-print text-right whitespace-nowrap">
                            <?php if (can('pertes.creer') && $reste > 0.01): ?>
                                <button type="button" @click="openPayment(<?= (int) $m['id'] ?>, '<?= htmlspecialchars($m['agent_nom'], ENT_QUOTES) ?>', <?= (float) $reste ?>)" class="text-green-600 hover:text-green-800 mr-3" title="Régler">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                            <?php if (can('pertes.creer')): ?>
                                <button type="button" @click="removeManquant(<?= (int) $m['id'] ?>)" class="text-red-600 hover:text-red-800" title="Supprimer">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div x-show="payment.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 no-print" style="display:none">
        <div class="absolute inset-0 bg-black/50" @click="payment.open=false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-1">Régler un manquant</h3>
            <p class="text-sm text-gray-500 mb-4" x-text="payment.agent + ' · reste: ' + App.formatMoneyConverted(payment.reste, (window.BASE_DEVISE || 'CDF'), window.DEVISE)"></p>
            <div class="space-y-3 mb-4">
                <div><label class="label">Montant payé</label><input type="number" min="0.01" step="0.01" x-model.number="payment.montant" class="input"></div>
                <div><label class="label">Date paiement</label><input type="date" x-model="payment.date_paiement" class="input"></div>
                <div><label class="label">Note</label><textarea x-model="payment.note" class="input" rows="2"></textarea></div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="payment.open=false">Annuler</button>
                <button type="button" class="btn btn-primary" @click="savePayment()" :disabled="loading">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
    function manquantsPage() {
        return {
            loading: false,
            payment: {
                open: false,
                id: null,
                agent: '',
                reste: 0,
                montant: 0,
                date_paiement: new Date().toISOString().slice(0, 10),
                note: ''
            },
            openPayment(id, agent, reste) {
                this.payment = {
                    open: true,
                    id,
                    agent,
                    reste,
                    montant: reste,
                    date_paiement: new Date().toISOString().slice(0, 10),
                    note: ''
                };
            },
            async savePayment() {
                this.loading = true;
                try {
                    await App.api('/api/manquants/' + this.payment.id + '/paiement', 'POST', {
                        montant: this.payment.montant,
                        date_paiement: this.payment.date_paiement,
                        note: this.payment.note
                    });
                    App.notify('Paiement enregistré', 'success');
                    location.reload();
                } catch (e) {
                    App.notify(e.message || 'Erreur lors du paiement', 'error');
                } finally {
                    this.loading = false;
                }
            },
            async removeManquant(id) {
                const ok = await App.confirm({
                    title: 'Supprimer ?',
                    message: 'Supprimer ce manquant ?',
                    confirmText: 'Supprimer',
                    cancelText: 'Annuler',
                    type: 'danger'
                });
                if (!ok) return;
                await App.api('/api/manquants/' + id, 'DELETE');
                location.reload();
            }
        }
    }
</script>
<?php if ($printMode): ?><script>
        window.addEventListener('load', () => window.print());
    </script><?php endif; ?>
<?php $content = ob_get_clean();
require ROOT_PATH . '/app/Views/layouts/app.php'; ?>