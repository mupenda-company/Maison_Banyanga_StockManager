<?php
$pageTitle = 'Ventes par agent';
$ventesParAgent = $ventesParAgent ?? [];
$dateDebut = $dateDebut ?? date('Y-m-d');
$dateFin = $dateFin ?? $dateDebut;
$totalCa = (float) ($totalCa ?? 0);
$totalVentes = (int) ($totalVentes ?? 0);
$nbAgents = (int) ($nbAgents ?? 0);

$dateDebutLabel = !empty($dateDebut) ? date('d/m/Y', strtotime($dateDebut)) : 'N/A';
$dateFinLabel = !empty($dateFin) ? date('d/m/Y', strtotime($dateFin)) : 'N/A';
$periodeLabel = $dateDebut === $dateFin
    ? $dateDebutLabel
    : ($dateDebutLabel . ' au ' . $dateFinLabel);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventes par agent - <?= htmlspecialchars($periodeLabel) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }
        .report-sheet {
            width: 186mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .section-title {
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
        }
        .table-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #111827;
            color: #ffffff;
        }
        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        th {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
        }
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .num {
            text-align: right;
            white-space: nowrap;
        }
        .agent-block {
            margin-top: 18px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .agent-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .agent-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            font-size: 11px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            padding: 10px 12px;
        }
        .summary-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .summary-value {
            margin-top: 4px;
            font-size: 16px;
            font-weight: 800;
        }
        .footer {
            margin-top: 18px;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        @media print {
            body {
                background: #ffffff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .report-sheet {
                width: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
        }
        @media screen and (max-width: 900px) {
            .report-sheet {
                width: auto;
                margin: 12px;
                padding: 16px;
            }
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .agent-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="bg-white">
    <div class="report-sheet">
        <div class="flex items-start justify-between gap-4 border-b-2 border-gray-900 pb-4">
            <div>
                <h1 class="text-2xl font-bold uppercase text-gray-900">Ventes par agent</h1>
                <p class="text-sm text-gray-600 mt-1">Rapport journalier imprimable</p>
                <p class="text-sm text-gray-600">Période : <span class="font-semibold"><?= htmlspecialchars($periodeLabel) ?></span></p>
            </div>
            <div class="text-right text-xs text-gray-600">
                <p class="uppercase font-semibold tracking-wide text-gray-500">Imprimé le</p>
                <p class="font-semibold text-gray-900"><?= date('d/m/Y H:i') ?></p>
            </div>
        </div>

        <div class="no-print mt-4 mb-4 flex flex-wrap items-end justify-between gap-3">
            <form method="GET" action="<?= url('rapports/ventes-par-agent') ?>" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Date début</label>
                    <input type="date" name="date_debut" value="<?= htmlspecialchars($dateDebut) ?>" class="px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Date fin</label>
                    <input type="date" name="date_fin" value="<?= htmlspecialchars($dateFin) ?>" class="px-3 py-2 border rounded-lg text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold">Filtrer</button>
            </form>

            <div class="flex flex-col items-end gap-2">
                <p class="text-xs text-gray-500 max-w-xs text-right">
                    Ce bouton imprime <span class="font-semibold">la page du rapport affiché</span> avec les filtres de dates choisis.
                </p>
                <div class="flex gap-2">
                <a href="<?= url('rapports') ?>" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 text-sm font-semibold">Retour aux rapports</a>
                    <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-semibold">Imprimer le rapport affiché</button>
                </div>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Ventes validées</div>
                <div class="summary-value text-blue-700"><?= number_format($totalVentes, 0, ',', ' ') ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Agents concernés</div>
                <div class="summary-value text-purple-700"><?= number_format($nbAgents, 0, ',', ' ') ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Chiffre d'affaires TTC</div>
                <div class="summary-value text-green-700"><?= format_money_converted($totalCa) ?></div>
            </div>
        </div>

        <?php if (empty($ventesParAgent)): ?>
            <div class="p-10 text-center border rounded-xl bg-gray-50 text-gray-500">
                Aucune vente trouvée sur cette période.
            </div>
        <?php else: ?>
            <?php foreach ($ventesParAgent as $agent): ?>
                <div class="agent-block">
                    <div class="agent-header">
                        <div>
                            <div class="section-title">Agent</div>
                            <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($agent['agent_nom']) ?></h2>
                            <?php if (!empty($agent['agent_role'])): ?>
                                <p class="text-sm text-gray-500">Rôle : <?= htmlspecialchars($agent['agent_role']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <div class="agent-badge"><?= number_format(count($agent['ventes']), 0, ',', ' ') ?> vente(s)</div>
                            <p class="mt-2 text-sm text-gray-500">Total agent</p>
                            <p class="text-lg font-bold text-green-700"><?= format_money_converted($agent['total_ca'] ?? 0) ?></p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Facture</th>
                                    <th>Client</th>
                                    <th>Emplacement</th>
                                    <th class="num">Total TTC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agent['ventes'] as $vente): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></td>
                                        <td class="font-semibold"><?= htmlspecialchars($vente['numero_facture']) ?></td>
                                        <td>
                                            <div class="font-medium"><?= htmlspecialchars($vente['client_nom'] ?? 'N/A') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($vente['emplacement_nom'] ?? 'N/A') ?></td>
                                        <td class="num font-bold text-gray-900"><?= format_money_converted($vente['total_ttc'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right font-bold text-gray-700">Sous-total <?= htmlspecialchars($agent['agent_nom']) ?></td>
                                    <td class="num font-bold text-green-700"><?= format_money_converted($agent['total_ca'] ?? 0) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="mt-8 pt-4 border-t-2 border-gray-200 grid grid-cols-3 gap-4 text-center text-sm">
            <div>
                <p class="text-gray-500 uppercase font-semibold tracking-wider text-xs">Total ventes</p>
                <p class="font-bold text-gray-900"><?= number_format($totalVentes, 0, ',', ' ') ?></p>
            </div>
            <div>
                <p class="text-gray-500 uppercase font-semibold tracking-wider text-xs">Agents</p>
                <p class="font-bold text-gray-900"><?= number_format($nbAgents, 0, ',', ' ') ?></p>
            </div>
            <div>
                <p class="text-gray-500 uppercase font-semibold tracking-wider text-xs">CA total</p>
                <p class="font-bold text-green-700"><?= format_money_converted($totalCa) ?></p>
            </div>
        </div>

        <div class="footer">
            Rapport imprimable des ventes par agent — <?= htmlspecialchars($periodeLabel) ?>
        </div>
    </div>
</body>
</html>
