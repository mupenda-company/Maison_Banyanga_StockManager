<?php
$printMode = $printMode ?? false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synthèse des missions par agent - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { size: A4; margin: 5mm; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        .agent-section { break-inside: avoid; page-break-inside: avoid; }
        .mission-block { break-inside: avoid; page-break-inside: avoid; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: #fff; font-size: 11px; }
            .no-print { display: none !important; }
            .compact-print { margin: 0 !important; padding: 2px 4px !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-2 text-xs">
    <?php
        $companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
        $companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
    ?>

    <div class="max-w-4xl mx-auto">
        <!-- En-tête -->
        <div class="grid grid-cols-3 items-start mb-2 border-b pb-2 gap-2">
            <div>
                <?php if ($companyLogo): ?>
                <img src="<?= $companyLogo ?>" alt="Logo" class="h-10 mb-1 object-contain">
                <?php endif; ?>
                <h1 class="text-base font-bold text-gray-900"><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></h1>
                <?php if (!empty($params['adresse'])): ?><p class="text-xs text-gray-600"><?= htmlspecialchars($params['adresse']) ?></p><?php endif; ?>
                <?php if (!empty($companyContact)): ?><p class="text-xs text-gray-600">Contact: <?= htmlspecialchars($companyContact) ?></p><?php endif; ?>
                <?php if (!empty($params['rccm'])): ?><p class="text-xs text-gray-600">RCCM: <?= htmlspecialchars($params['rccm']) ?></p><?php endif; ?>
                <?php if (!empty($params['id_nat'])): ?><p class="text-xs text-gray-600">ID NAT: <?= htmlspecialchars($params['id_nat']) ?></p><?php endif; ?>
                <?php if (!empty($params['nif'])): ?><p class="text-xs text-gray-600">NIF: <?= htmlspecialchars($params['nif']) ?></p><?php endif; ?>
                    
                    Du <?= date('d/m/Y', strtotime($dateDebut)) ?> au <?= date('d/m/Y', strtotime($dateFin)) ?>
                </p>
                <?php if ($statut): ?>
                <p class="text-xs text-gray-500 mt-1">
                    Statut : <?= $statut === 'en_cours' ? 'En cours' : 'Terminées' ?>
                </p>
                <?php else: ?>
                <p class="text-xs text-gray-500 mt-1">Toutes les missions</p>
                <?php endif; ?>
                    
            </div>
        </div>

        <!-- Filtres -->
        <div class="no-print mb-6 p-4 bg-gray-50 rounded-lg border">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="print" value="1">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Date début</label>
                    <input type="date" name="date_debut" class="border rounded px-2 py-1 text-sm" value="<?= htmlspecialchars($dateDebut) ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Date fin</label>
                    <input type="date" name="date_fin" class="border rounded px-2 py-1 text-sm" value="<?= htmlspecialchars($dateFin) ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Statut</label>
                    <select name="statut" class="border rounded px-2 py-1 text-sm">
                        <option value="">Tous</option>
                        <option value="terminee" <?= $statut === 'terminee' ? 'selected' : '' ?>>Terminées</option>
                        <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                    Imprimer
                </button>
            </form>
        </div>

        <?php if (empty($synthese)): ?>
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Aucune mission trouvée pour cette période.</p>
        </div>
        <?php else: ?>

        <!-- Résumé global -->
        <div class="mb-2 p-2 bg-gray-50 rounded border">
            <div class="grid grid-cols-5 gap-2 text-center">
                <div>
                    <p class="text-lg font-bold text-gray-900"><?= $totauxGeneraux['nb_missions'] ?></p>
                    <p class="text-[10px] text-gray-500">Missions</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-900"><?= number_format($totauxGeneraux['total_caisses'], 0, ',', ' ') ?></p>
                    <p class="text-[10px] text-gray-500">Caisses</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-900"><?= $totauxGeneraux['total_clients'] ?></p>
                    <p class="text-[10px] text-gray-500">Clients</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-blue-600"><?= format_money_converted($totauxGeneraux['total_attendu']) ?></p>
                    <p class="text-[10px] text-gray-500">Attendu</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-green-700"><?= format_money_converted($totauxGeneraux['total_encaisse']) ?></p>
                    <p class="text-[10px] text-gray-500">Encaissé</p>
                </div>
            </div>
        </div>

        <!-- Tableau récapitulatif par agent -->
        <div class="mb-2">
            <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Récapitulatif par agent</h3>
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-1 font-semibold text-gray-500 uppercase text-[10px]">Agent</th>
                        <th class="text-center py-1 font-semibold text-gray-500 uppercase text-[10px]">Mis.</th>
                        <th class="text-center py-1 font-semibold text-gray-500 uppercase text-[10px]">Caisses</th>
                        <th class="text-center py-1 font-semibold text-gray-500 uppercase text-[10px]">Cli.</th>
                        <th class="text-right py-1 font-semibold text-gray-500 uppercase text-[10px]">Attendu</th>
                        <th class="text-right py-1 font-semibold text-gray-500 uppercase text-[10px]">Encaissé</th>
                        <th class="text-right py-1 font-semibold text-gray-500 uppercase text-[10px]">Écart</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($synthese as $s): ?>
                    <?php $ecart = round($s['total_encaisse'] - $s['total_attendu'], 2); ?>
                    <tr>
                        <td class="py-1 font-medium"><?= htmlspecialchars($s['agent']) ?></td>
                        <td class="py-1 text-center"><?= $s['nb_missions'] ?></td>
                        <td class="py-1 text-center"><?= number_format($s['total_caisses'], 0, ',', ' ') ?></td>
                        <td class="py-1 text-center"><?= $s['total_clients'] ?></td>
                        <td class="py-1 text-right"><?= format_money_converted($s['total_attendu']) ?></td>
                        <td class="py-1 text-right font-semibold"><?= format_money_converted($s['total_encaisse']) ?></td>
                        <td class="py-1 text-right font-semibold <?= abs($ecart) > 0.01 ? 'text-red-600' : 'text-green-700' ?>"><?= format_money_converted($ecart) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-t-2 border-gray-200">
                    <?php $ecartGlobal = round($totauxGeneraux['total_encaisse'] - $totauxGeneraux['total_attendu'], 2); ?>
                    <tr>
                        <td class="py-1 font-bold">Total</td>
                        <td class="py-1 text-center font-bold"><?= $totauxGeneraux['nb_missions'] ?></td>
                        <td class="py-1 text-center font-bold"><?= number_format($totauxGeneraux['total_caisses'], 0, ',', ' ') ?></td>
                        <td class="py-1 text-center font-bold"><?= $totauxGeneraux['total_clients'] ?></td>
                        <td class="py-1 text-right font-bold"><?= format_money_converted($totauxGeneraux['total_attendu']) ?></td>
                        <td class="py-1 text-right font-bold"><?= format_money_converted($totauxGeneraux['total_encaisse']) ?></td>
                        <td class="py-1 text-right font-bold <?= abs($ecartGlobal) > 0.01 ? 'text-red-600' : 'text-green-700' ?>"><?= format_money_converted($ecartGlobal) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Détail par agent -->
        <?php foreach ($synthese as $s): ?>
        <div class="agent-section">
            <div class="mb-2 border-b border-blue-200 pb-1">
                <h3 class="text-sm font-bold text-gray-900"><?= htmlspecialchars($s['agent']) ?></h3>
                <p class="text-xs text-gray-500">
                    <?= $s['nb_missions'] ?> mission(s) · <?= number_format($s['total_caisses'], 0, ',', ' ') ?> caisses · <?= $s['total_clients'] ?> client(s)
                    · Attendu : <?= format_money_converted($s['total_attendu']) ?>
                    · Encaissé : <?= format_money_converted($s['total_encaisse']) ?>
                </p>
            </div>

            <?php foreach ($s['missions'] as $mission): ?>
            <?php
                $isRestourne = ($mission['type_mission'] ?? 'vente') === 'ristourne';
                $montantAttendu = (float)($mission['montant_attendu'] ?? 0);
                $montantEncaisse = (float)($mission['montant_encaisse'] ?? 0);
                $ecartMission = round($montantEncaisse - $montantAttendu, 2);
            ?>
            <div class="mb-2 border rounded p-2 mission-block">
                <!-- Mission header -->
                <div class="flex items-center justify-between mb-1">
                    <div>
                        <span class="font-bold text-gray-900"><?= htmlspecialchars($mission['numero_mission']) ?></span>
                        <span class="ml-2 text-xs <?= $isRestourne ? 'text-purple-600' : 'text-blue-600' ?>"><?= $isRestourne ? 'Ristourne' : 'Vente' ?></span>
                        <span class="ml-2 text-xs <?= $mission['statut'] === 'en_cours' ? 'text-yellow-600' : 'text-green-600' ?> font-semibold"><?= $mission['statut'] === 'en_cours' ? 'En cours' : 'Terminée' ?></span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <?= date('d/m/Y H:i', strtotime($mission['date_depart'])) ?>
                        <?php if (!empty($mission['date_retour'])): ?>
                        → <?= date('d/m/Y H:i', strtotime($mission['date_retour'])) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-1 mb-1 text-[11px]">
                    <div>
                        <span class="text-gray-500">Véhicule :</span>
                        <span class="font-medium"><?= htmlspecialchars($mission['immatriculation'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Zone :</span>
                        <span class="font-medium"><?= htmlspecialchars($mission['zone_nom'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Clients :</span>
                        <span class="font-medium"><?= $mission['nb_clients'] ?? 0 ?></span>
                    </div>
                </div>

                <?php if (!$isRestourne): ?>
                <!-- Chargements -->
                <?php if (!empty($mission['chargements'])): ?>
                <table class="w-full text-[11px] mb-1">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Produit</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Chargé</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Vendu</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Reste</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Vides</th>
                            <th class="text-right py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Valeur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                            $totCsChargees = 0; $totCsVendues = 0; $totCsRestantes = 0; $totVides = 0; $totValeur = 0;
                        ?>
                        <?php foreach ($mission['chargements'] as $ch): ?>
                        <?php
                            $btlParCaisse = max(1, (int)($ch['bouteilles_par_caisses'] ?? 24));
                            $csChargees = (int)($ch['caisses_total'] ?? max(0, (int)($ch['quantite_caisses'] ?? 0)));
                            $csVendues = (int)($ch['caisses_vendues_auto'] ?? $ch['caisses_vendues'] ?? 0);
                            $csRestantes = max(0, $csChargees - $csVendues);
                            $videsRecues = (int)($ch['caisses_vides_recues_auto'] ?? $ch['caisses_vides_recues'] ?? 0);
                            $prixCaisse = (float)($ch['prix_vente_caisses'] ?? 0) ?: ((float)($ch['prix_vente_unitaire'] ?? 0) * $btlParCaisse);
                            $valeur = $csRestantes * $prixCaisse;
                            $totCsChargees += $csChargees; $totCsVendues += $csVendues;
                            $totCsRestantes += $csRestantes; $totVides += $videsRecues; $totValeur += $valeur;
                        ?>
                        <tr>
                            <td class="py-1 font-medium"><?= htmlspecialchars($ch['produit_nom'] ?? '') ?></td>
                            <td class="py-1 text-center"><?= number_format($csChargees, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($csVendues, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($csRestantes, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($videsRecues, 0, ',', ' ') ?></td>
                            <td class="py-1 text-right"><?= format_money_converted($valeur) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-gray-300">
                        <tr class="font-bold">
                            <td class="py-1">Total</td>
                            <td class="py-1 text-center"><?= number_format($totCsChargees, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($totCsVendues, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($totCsRestantes, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($totVides, 0, ',', ' ') ?></td>
                            <td class="py-1 text-right"><?= format_money_converted($totValeur) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>

                <!-- Ventes par produit -->
                <?php if (!empty($mission['ventes_par_produit'])): ?>
                <table class="w-full text-[11px] mb-1">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Produit</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Btl.</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Cs vendues</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Vides</th>
                            <th class="text-right py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                            $totBtl = 0; $totCsV = 0; $totVidesV = 0; $totMontantV = 0;
                        ?>
                        <?php foreach ($mission['ventes_par_produit'] as $vp): ?>
                        <?php
                            $totBtl += (int)($vp['bouteilles_vendues'] ?? 0);
                            $totCsV += (int)($vp['caisses_vendues'] ?? 0);
                            $totVidesV += (int)($vp['caisses_vides_recues'] ?? 0);
                            $totMontantV += (float)($vp['montant'] ?? 0);
                        ?>
                        <tr>
                            <td class="py-1 font-medium"><?= htmlspecialchars($vp['produit_nom'] ?? '') ?></td>
                            <td class="py-1 text-center"><?= number_format((int)($vp['bouteilles_vendues'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format((int)($vp['caisses_vendues'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format((int)($vp['caisses_vides_recues'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="py-1 text-right"><?= format_money_converted((float)($vp['montant'] ?? 0)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-gray-300">
                        <tr class="font-bold">
                            <td class="py-1">Total ventes</td>
                            <td class="py-1 text-center"><?= number_format($totBtl, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($totCsV, 0, ',', ' ') ?></td>
                            <td class="py-1 text-center"><?= number_format($totVidesV, 0, ',', ' ') ?></td>
                            <td class="py-1 text-right"><?= format_money_converted($totMontantV) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>

                <!-- Clients de la mission -->
                <?php if (!empty($mission['clients'])): ?>
                <table class="w-full text-[11px] mb-1">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Client</th>
                            <th class="text-center py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Caisses</th>
                            <th class="text-right py-0.5 font-semibold text-gray-500 uppercase text-[9px]">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $totCliCs = 0; $totCliMontant = 0; ?>
                        <?php foreach ($mission['clients'] as $cli): ?>
                        <?php
                            $totCliCs += (int)($cli['quantite_caisses'] ?? 0);
                            $totCliMontant += (float)($cli['montant'] ?? 0);
                        ?>
                        <tr>
                            <td class="py-1 font-medium"><?= htmlspecialchars($cli['nom'] ?? '') ?></td>
                            <td class="py-1 text-center"><?= number_format((int)($cli['quantite_caisses'] ?? 0), 0, ',', ' ') ?></td>
                            <td class="py-1 text-right"><?= format_money_converted((float)($cli['montant'] ?? 0)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-gray-300">
                        <tr class="font-bold">
                            <td class="py-1">Total clients</td>
                            <td class="py-1 text-center"><?= number_format($totCliCs, 0, ',', ' ') ?></td>
                            <td class="py-1 text-right"><?= format_money_converted($totCliMontant) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
                <?php else: ?>
                <!-- Mission ristourne -->
                <div class="grid grid-cols-3 gap-2 text-xs mb-2">
                    <div>
                        <span class="text-gray-500">Montant ristourne :</span>
                        <span class="font-medium"><?= format_money_converted((float)($mission['montant_ristourne_initial'] ?? 0)) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Montant livré :</span>
                        <span class="font-medium"><?= format_money_converted((float)($mission['montant_livre'] ?? 0)) ?></span>
                    </div>
                    
                </div>
                <?php endif; ?>

                <!-- Bilan financier de la mission -->
                <div class="grid grid-cols-3 gap-1 p-1 rounded text-[11px] <?= abs($ecartMission) > 0.01 ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' ?>">
                    <div>
                        <span class="text-gray-500">Attendu :</span>
                        <span class="font-bold"><?= format_money_converted($montantAttendu) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Encaissé :</span>
                        <span class="font-bold"><?= format_money_converted($montantEncaisse) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Écart :</span>
                        <span class="font-bold <?= abs($ecartMission) > 0.01 ? 'text-red-600' : 'text-green-700' ?>"><?= format_money_converted($ecartMission) ?></span>
                    </div>
                </div>
                <?php if (!empty($mission['justification_cloture'])): ?>
                <p class="text-xs text-gray-500 mt-1 italic">Justification : <?= htmlspecialchars($mission['justification_cloture']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <!-- Sous-total agent -->
            <?php $ecartAgent = round($s['total_encaisse'] - $s['total_attendu'], 2); ?>
            <div class="p-2 bg-blue-50 rounded border border-blue-200 mb-2">
                <div class="grid grid-cols-5 gap-2 text-xs items-center">
                    <div class="font-bold">Sous-total <?= htmlspecialchars($s['agent']) ?></div>
                    <div class="text-center font-bold"><?= number_format($s['total_caisses'], 0, ',', ' ') ?> cs</div>
                    <div class="text-center font-bold"><?= $s['total_clients'] ?> clients</div>
                    <div class="text-right font-bold"><?= format_money_converted($s['total_encaisse']) ?></div>
                    <div class="text-right font-bold <?= abs($ecartAgent) > 0.01 ? 'text-red-600' : 'text-green-700' ?>">
                        Écart : <?= format_money_converted($ecartAgent) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-4 mt-4 pt-3 border-t text-xs">
            <div class="text-center">
                <div class="border-t border-gray-400 pt-1 mt-6">
                    <p class="text-gray-600">Le Magasinier</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-1 mt-6">
                    <p class="text-gray-600">Le Caissier / La Caissière</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-1 mt-6">
                    <p class="text-gray-600">Le Responsable</p>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Boutons d'action -->
        <div class="no-print mt-8 flex justify-center space-x-4">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
            <a href="<?= url('missions') ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Retour aux missions
            </a>
        </div>
    </div>

    <?php if ($printMode): ?>
    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
    <?php endif; ?>
</body>
</html>
