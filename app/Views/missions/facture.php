<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $isRistourne = (($mission['type_mission'] ?? 'vente') === 'ristourne'); ?>
    <title><?= $isRistourne ? 'Facture de ristourne' : 'Facture de mission' ?> <?= htmlspecialchars($mission['numero_mission'] ?? '') ?> - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
        }
        .max-w-4xl { max-width: 56rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .space-y-5 > * + * { margin-top: 1.25rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .p-3 { padding: 0.75rem; }
        .p-2 { padding: 0.5rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-3 { margin-top: 0.75rem; }
        .mt-6 { margin-top: 1.5rem; }
        .pt-2 { padding-top: 0.5rem; }
        .pt-4 { padding-top: 1rem; }
        .pb-4 { padding-bottom: 1rem; }
        .border { border: 1px solid #dbe2ea; }
        .border-t { border-top: 1px solid #dbe2ea; }
        .border-b { border-bottom: 1px solid #dbe2ea; }
        .border-gray-200 { border-color: #e2e8f0; }
        .border-gray-400 { border-color: #94a3b8; }
        .border-red-200 { border-color: #fecaca; }
        .border-green-200 { border-color: #bbf7d0; }
        .rounded { border-radius: 0.375rem; }
        .rounded-lg { border-radius: 0.75rem; }
        .bg-white { background: #ffffff; }
        .bg-gray-50 { background: #f8fafc; }
        .bg-red-50 { background: #fef2f2; }
        .bg-green-50 { background: #f0fdf4; }
        .text-gray-500 { color: #64748b; }
        .text-gray-600 { color: #475569; }
        .text-gray-700 { color: #334155; }
        .text-gray-900 { color: #0f172a; }
        .text-blue-600 { color: #2563eb; }
        .text-red-600 { color: #dc2626; }
        .text-green-700 { color: #15803d; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }
        .uppercase { text-transform: uppercase; }
        .tracking-wider { letter-spacing: 0.08em; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-xs { font-size: 0.75rem; }
        .text-sm { font-size: 0.875rem; }
        .text-base { font-size: 1rem; }
        .text-lg { font-size: 1.125rem; }
        .text-xl { font-size: 1.25rem; }
        .leading-tight { line-height: 1.2; }
        .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .whitespace-nowrap { white-space: nowrap; }
        .min-w-0 { min-width: 0; }
        .w-full { width: 100%; }
        .flex { display: flex; }
        .grid { display: grid; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .items-end { align-items: flex-end; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .max-w-4xl { width: min(56rem, calc(100vw - 16px)); }
        .compact-table th,
        .compact-table td {
            padding-top: 0.35rem;
            padding-bottom: 0.35rem;
            line-height: 1.15rem;
        }
        .shadow-sm {
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .mission-page { break-after: page; page-break-after: always; }
        .mission-page:last-child { break-after: auto; page-break-after: auto; }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: #ffffff;
            }
            .no-print { display: none !important; }
            .shadow-sm { box-shadow: none; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-4 text-sm">
    <?php
        $vehicule = $mission['vehicule'] ?? [];
        $companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
        $companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
        $chargements = $mission['chargements'] ?? [];
        $chargementsPages = array_chunk($chargements, 20);
        $pagesCount = count($chargementsPages);

        $totalCaissesChargees = 0;
        $totalCaissesVendues = 0;
        $totalCaissesRestantes = 0;
        $totalCaissesRetournees = 0;
        $totalValeurEstimee = 0;

        foreach ($chargements as $chargement) {
            $btlParCaisse = (int) ($chargement['bouteilles_par_caisses'] ?? 24);
            $quantiteChargee = (int) ($chargement['quantite_chargee'] ?? 0);
            $quantiteVendue = (int) ($chargement['quantite_vendue'] ?? 0);
            $quantiteRetournee = (int) ($chargement['quantite_retournee'] ?? 0);
            $prixCaisse = (float) ($chargement['prix_caisse'] ?? 0);
            $prixBouteille = $btlParCaisse > 0 && $prixCaisse > 0 ? $prixCaisse / $btlParCaisse : (float) ($chargement['prix_vente_unitaire'] ?? 0);
            $caissesDejaDansVehicule = (int) ($chargement['caisses_deja_dans_vehicule'] ?? 0);
            $caissesChargees = (int) ($chargement['caisses_total'] ?? max(0, (int) ($chargement['quantite_caisses'] ?? 0)));
            $caissesVendues = $btlParCaisse > 0 ? round($quantiteVendue / $btlParCaisse, 0) : 0;
            $caissesRestantes = max(0, $caissesChargees - $caissesVendues);
            $caissesRetournees = $btlParCaisse > 0 ? round($quantiteRetournee / $btlParCaisse, 0) : 0;
            $valeurEstimee = $caissesRestantes * $prixCaisse;

            $totalCaissesChargees += $caissesChargees;
            $totalCaissesVendues += $caissesVendues;
            $totalCaissesRestantes += $caissesRestantes;
            $totalCaissesRetournees += $caissesRetournees;
            $totalValeurEstimee += $valeurEstimee;
        }

        $montantAttendu = (float) ($mission['montant_attendu'] ?? 0);
        $montantEncaisse = (float) ($mission['montant_encaisse'] ?? 0);
        $montantEcart = round($montantEncaisse - $montantAttendu, 2);
        $caissesVidesAttendues = (int) ($mission['caisses_vides_attendues'] ?? 0);
        $caissesVidesRetournees = (int) ($mission['caisses_vides_retournees'] ?? 0);
        $caissesVidesEcart = $caissesVidesAttendues - $caissesVidesRetournees;
        $justificationCloture = trim((string) ($mission['justification_cloture'] ?? ''));
        $hasDiscrepancy = abs($montantEcart) > 0.01 || $caissesVidesEcart !== 0;

        $renderMissionFooter = function () use ($totalCaissesChargees, $totalCaissesVendues, $totalCaissesRestantes, $totalValeurEstimee) {
            ?>
            <div class="mt-3 border-t border-gray-200 pt-2 text-sm">
                <div class="grid grid-cols-5 gap-2 items-center rounded-md bg-gray-50 px-3 py-2">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">Total</div>
                    <div class="text-right font-bold whitespace-nowrap"><?= number_format($totalCaissesChargees, 0, ',', ' ') ?> cs</div>
                    <div class="text-right font-bold whitespace-nowrap"><?= number_format($totalCaissesVendues, 0, ',', ' ') ?> cs</div>
                    <div class="text-right font-bold whitespace-nowrap"><?= number_format($totalCaissesRestantes, 0, ',', ' ') ?> cs</div>
                    <div class="text-right font-bold whitespace-nowrap text-primary-600"><?= format_money_converted($totalValeurEstimee) ?></div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mt-6 pt-4 border-t">
                <div class="text-center">
                    <div class="border-t border-gray-400 pt-2 mt-8">
                        <p class="text-sm text-gray-600">Le Magasinier</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="border-t border-gray-400 pt-2 mt-8">
                        <p class="text-sm text-gray-600">Le Caissier / La Caissière</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="border-t border-gray-400 pt-2 mt-8">
                        <p class="text-sm text-gray-600">Le Responsable</p>
                    </div>
                </div>
            </div>
            <?php
        };
    ?>
    <div class="max-w-4xl mx-auto space-y-5">
        <div class="grid grid-cols-3 gap-4 items-start mb-6 border-b pb-4 bg-white rounded-lg p-4 shadow-sm">
            <div>
                <?php if ($companyLogo): ?>
                <img src="<?= $companyLogo ?>" alt="Logo" class="h-12 mb-1 object-contain">
                <?php endif; ?>
                <h1 class="text-xl font-bold text-gray-900 leading-tight"><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></h1>
                <div class="text-xs text-gray-600 space-y-0.5">
                    <?php if (!empty($params['adresse'])): ?><p><?= htmlspecialchars($params['adresse']) ?></p><?php endif; ?>
                    <p class="flex flex-wrap gap-x-3 gap-y-0.5">
                        <?php if (!empty($companyContact)): ?><span>Contact: <?= htmlspecialchars($companyContact) ?></span><?php endif; ?>
                        <?php if (!empty($params['email_contact'])): ?><span>Email: <?= htmlspecialchars($params['email_contact']) ?></span><?php endif; ?>
                        <?php if (!empty($params['rccm'])): ?><span>RCCM: <?= htmlspecialchars($params['rccm']) ?></span><?php endif; ?>
                        <?php if (!empty($params['id_nat'])): ?><span>ID NAT: <?= htmlspecialchars($params['id_nat']) ?></span><?php endif; ?>
                        <?php if (!empty($params['nif'])): ?><span>NIF: <?= htmlspecialchars($params['nif']) ?></span><?php endif; ?>
                        <?php if (!empty($params['numero_compte'])): ?><span>N° compte: <?= htmlspecialchars($params['numero_compte']) ?></span><?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="text-center leading-tight self-center">
                <h2 class="text-lg font-bold text-blue-600 uppercase"><?= $isRistourne ? 'Facture de ristourne' : 'Facture de fin de mission' ?></h2>
                <p class="text-sm font-semibold mt-1"><?= htmlspecialchars($mission['numero_mission'] ?? '') ?></p>
                <p class="text-xs text-gray-600 mt-1 flex flex-col items-center gap-0.5">
                    <span>Départ: <?= !empty($mission['date_depart']) ? date('d/m/Y H:i', strtotime($mission['date_depart'])) : '-' ?></span>
                    <span>Retour: <?= !empty($mission['date_retour']) ? date('d/m/Y H:i', strtotime($mission['date_retour'])) : '-' ?></span>
                </p>
            </div>
            <div class="text-right text-xs text-gray-600 self-center">
                <p class="uppercase font-semibold tracking-wide text-gray-500">Mission</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['numero_mission'] ?? '') ?></p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 mb-4 text-sm">
            <div class="p-2 border rounded-lg flex items-center justify-between gap-2 min-w-0">
                <span class="text-gray-500 uppercase text-xs">Véhicule</span>
                <span class="font-semibold text-right truncate"><?= htmlspecialchars(trim(($mission['immatriculation'] ?? 'N/A') . (!empty($vehicule['marque']) || !empty($vehicule['modele']) ? ' - ' . trim(($vehicule['marque'] ?? '') . ' ' . ($vehicule['modele'] ?? '')) : ''))) ?></span>
            </div>
            <div class="p-2 border rounded-lg flex items-center justify-between gap-2 min-w-0">
                <span class="text-gray-500 uppercase text-xs">Agent</span>
                <span class="font-semibold text-right truncate"><?= htmlspecialchars($mission['agent_nom'] ?? trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? ''))) ?></span>
            </div>
            <div class="p-2 border rounded-lg flex items-center justify-between gap-2 min-w-0">
                <span class="text-gray-500 uppercase text-xs">Zone / statut</span>
                <span class="font-semibold text-right truncate"><?= htmlspecialchars(trim(($mission['zone_nom'] ?? 'N/A') . ' - ' . ($mission['statut'] ?? 'N/A'))) ?></span>
            </div>
        </div>

        <?php if ($isRistourne): ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-sm">
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Nb ristournes</p>
                <p class="text-base font-bold whitespace-nowrap"><?= count($mission['ristournes'] ?? []) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Caisses livrées</p>
                <p class="text-base font-bold whitespace-nowrap"><?= number_format((int)($mission['total_caisses'] ?? 0), 0, ',', ' ') ?> cs</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Montant ristourne</p>
                <p class="text-base font-bold whitespace-nowrap"><?= format_money_converted($mission['montant_ristourne_initial'] ?? 0) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Montant livré</p>
                <p class="text-base font-bold text-green-700 whitespace-nowrap"><?= format_money_converted($mission['montant_livre'] ?? 0) ?></p>
            </div>
        </div>

        <?php if (!empty($mission['ristournes'])): ?>
        <div class="mb-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Détail par client</h3>
            <table class="w-full border-collapse text-xs compact-table">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-1.5 font-semibold text-gray-500 uppercase">Client</th>
                        <th class="text-left py-1.5 font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="text-right py-1.5 font-semibold text-gray-500 uppercase">Cs prévues</th>
                        <th class="text-right py-1.5 font-semibold text-gray-500 uppercase">Cs livrées</th>
                        <th class="text-right py-1.5 font-semibold text-gray-500 uppercase">Montant ristourne</th>
                        <th class="text-right py-1.5 font-semibold text-gray-500 uppercase">Complément ajouté</th>
                        <th class="text-right py-1.5 font-semibold text-gray-500 uppercase">Montant récolté</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                        $totalCsPrevues = 0; $totalCsLivrees = 0;
                        $totalMontantRist = 0; $totalComplementAjoute = 0; $totalMontantRecolte = 0;
                        foreach ($mission['ristournes'] as $mr):
                            $btlPerCase = (int)($mr['bouteilles_par_caisses'] ?? 24);
                            if ($btlPerCase <= 0) $btlPerCase = 24;
                            $csPrev = (int)($mr['caisses_prevues'] ?? 0);
                            $csLiv = (int)($mr['caisses_livrees'] ?? 0);
                            $montRist = (float)($mr['montant_ristourne'] ?? 0);
                            $complement = (float)($mr['proposition_montant'] ?? 0);
                            $complementConfirme = !empty($mr['complement_confirme']);
                            // Montant récolté : si complément confirmé → (caisses_livrees * prix_caisse - montant_ristourne)
                            // Si pas de complément → montant_ristourne (le montant qui est rentré)
                            $prixCaisseMr = (float)($mr['prix_vente_caisses'] ?? 0);
                            if ($prixCaisseMr <= 0) $prixCaisseMr = (float)($mr['prix_vente_unitaire'] ?? 0) * $btlPerCase;
                            $montantRecolte = 0;
                            if ($complementConfirme && $csLiv > 0) {
                                $montantRecolte = max(0, round($csLiv * $prixCaisseMr - $montRist, 2));
                            }
                            $totalCsPrevues += $csPrev;
                            $totalCsLivrees += $csLiv;
                            $totalMontantRist += $montRist;
                            $totalComplementAjoute += ($complementConfirme && $complement > 0) ? $complement : 0;
                            $totalMontantRecolte += $montantRecolte;
                    ?>
                    <tr>
                        <td class="py-1.5 font-medium leading-tight"><?= htmlspecialchars($mr['client_nom'] ?? 'N/A') ?><?= !empty($mr['numero_client']) ? ' (' . htmlspecialchars($mr['numero_client']) . ')' : '' ?></td>
                        <td class="py-1.5 leading-tight"><?= htmlspecialchars($mr['produit_nom'] ?? 'N/A') ?></td>
                        <td class="py-1.5 text-right whitespace-nowrap"><?= $csPrev ?> cs</td>
                        <td class="py-1.5 text-right whitespace-nowrap font-medium"><?= $csLiv ?> cs</td>
                        <td class="py-1.5 text-right whitespace-nowrap"><?= format_money_converted($montRist) ?></td>
                        <td class="py-1.5 text-right whitespace-nowrap<?= $complementConfirme && $complement > 0 ? ' font-semibold text-red-600' : '' ?>">
                            <?php if ($complementConfirme && $complement > 0): ?>
                                <?= format_money_converted($complement) ?> ✓
                            <?php elseif ($complement > 0): ?>
                                <?= format_money_converted($complement) ?> <span class="text-orange-500 italic">(en attente)</span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-1.5 text-right whitespace-nowrap font-medium">
                            <?php if ($complementConfirme && $montantRecolte > 0): ?>
                                <span class="text-green-700"><?= format_money_converted($montantRecolte) ?></span>
                            <?php elseif (!$complementConfirme && $csLiv > 0): ?>
                                <span class="text-gray-500 italic" title="Montant ristourne rentré (pas de complément)"><?= format_money_converted($montRist) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-t-2 border-gray-200">
                    <tr>
                        <td class="py-2 font-bold" colspan="2">Total</td>
                        <td class="py-2 text-right font-bold whitespace-nowrap"><?= $totalCsPrevues ?> cs</td>
                        <td class="py-2 text-right font-bold whitespace-nowrap"><?= $totalCsLivrees ?> cs</td>
                        <td class="py-2 text-right font-bold whitespace-nowrap"><?= format_money_converted($totalMontantRist) ?></td>
                        <td class="py-2 text-right font-bold whitespace-nowrap text-red-600"><?= $totalComplementAjoute > 0 ? format_money_converted($totalComplementAjoute) : '—' ?></td>
                        <td class="py-2 text-right font-bold whitespace-nowrap text-green-700"><?= $totalMontantRecolte > 0 ? format_money_converted($totalMontantRecolte) : '—' ?></td>
                    </tr>
                </tfoot>
            </table>
            <?php if ($totalMontantRecolte > 0): ?>
            <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded text-sm">
                <p class="font-semibold text-green-800">Total récolté pour les compléments : <?= format_money_converted($totalMontantRecolte) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4 text-sm">
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Montant attendu</p>
                <p class="text-base font-bold whitespace-nowrap"><?= format_money_converted($montantAttendu) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Montant encaissé</p>
                <p class="text-base font-bold text-green-700 whitespace-nowrap"><?= format_money_converted($montantEncaisse) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Écart caisse</p>
                <p class="text-base font-bold whitespace-nowrap <?= abs($montantEcart) > 0.01 ? 'text-red-600' : 'text-green-700' ?>"><?= format_money_converted($montantEcart) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Caisses vides attendues</p>
                <p class="text-base font-bold whitespace-nowrap"><?= number_format($caissesVidesAttendues, 0, ',', ' ') ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Caisses vides retournées</p>
                <p class="text-base font-bold whitespace-nowrap"><?= number_format($caissesVidesRetournees, 0, ',', ' ') ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Écart vides</p>
                <p class="text-base font-bold whitespace-nowrap <?= $caissesVidesEcart !== 0 ? 'text-red-600' : 'text-green-700' ?>"><?= number_format($caissesVidesEcart, 0, ',', ' ') ?></p>
            </div>
        </div>

        <div class="mb-6 p-4 rounded-lg border <?= $hasDiscrepancy ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' ?>">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold <?= $hasDiscrepancy ? 'text-red-700' : 'text-green-700' ?>">
                        <?= $hasDiscrepancy ? 'Des écarts ont été constatés.' : 'Aucun écart détecté sur la clôture.' ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                        <?= $hasDiscrepancy
                            ? 'La mission a été clôturée avec justification. Vérifiez le détail des montants et des emballages ci-dessus.'
                            : 'Les montants et les caisses vides sont cohérents.' ?>
                    </p>
                </div>
                <div class="text-sm text-right whitespace-nowrap">
                    <p class="text-gray-500 uppercase text-[11px]">Justification</p>
                    <p class="font-medium <?= $justificationCloture !== '' ? 'text-gray-900' : 'text-gray-500 italic' ?>">
                        <?= $justificationCloture !== '' ? htmlspecialchars($justificationCloture) : 'Aucune justification enregistrée' ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($chargements)): ?>
        <div class="mission-page">
            <div class="mb-6 p-6 text-center text-gray-500 border rounded-lg">Aucun produit chargé</div>
            <?php $renderMissionFooter(); ?>
        </div>
        <?php else: ?>
        <?php foreach ($chargementsPages as $pageIndex => $pageChargements): ?>
        <div class="mission-page <?= $pageIndex === $pagesCount - 1 ? '' : '' ?>">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= $isRistourne ? 'Bilan des livraisons' : 'Bilan des chargements' ?></h3>
                <?php if ($pagesCount > 1): ?>
                <p class="text-xs text-gray-500">Page <?= $pageIndex + 1 ?> / <?= $pagesCount ?></p>
                <?php endif; ?>
            </div>
            <table class="w-full border-collapse text-sm compact-table">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="text-center py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Chargé</th>
                        <th class="text-center py-1.5 text-[11px] font-semibold text-gray-500 uppercase"><?= $isRistourne ? 'Livré' : 'Vendu' ?></th>
                        <th class="text-center py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Restant</th>
                        <th class="text-right py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Valeur estimée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($pageChargements as $chargement): ?>
                    <?php
                        $btlParCaisse = (int) ($chargement['bouteilles_par_caisses'] ?? 24);
                        $quantiteVendue = (int) ($chargement['quantite_vendue'] ?? 0);
                        $prixCaisse = (float) ($chargement['prix_caisse'] ?? 0);
                        $caissesChargees = (int) ($chargement['caisses_total'] ?? max(0, (int) ($chargement['quantite_caisses'] ?? 0)));
                        $caissesVendues = $btlParCaisse > 0 ? round($quantiteVendue / $btlParCaisse, 0) : 0;
                        $caissesRestantes = max(0, $caissesChargees - $caissesVendues);
                        $valeurEstimee = $caissesRestantes * $prixCaisse;
                    ?>
                    <tr>
                        <td class="py-1.5 font-medium leading-tight"><?= htmlspecialchars($chargement['produit_nom'] ?? '') ?></td>
                        <td class="py-1.5 text-center whitespace-nowrap"><?= number_format($caissesChargees, 0, ',', ' ') ?> cs</td>
                        <td class="py-1.5 text-center whitespace-nowrap"><?= number_format($caissesVendues, 0, ',', ' ') ?> cs</td>
                        <td class="py-1.5 text-center whitespace-nowrap"><?= number_format($caissesRestantes, 0, ',', ' ') ?> cs</td>
                        <td class="py-1.5 text-right whitespace-nowrap"><?= format_money_converted($valeurEstimee) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($pageIndex === $pagesCount - 1): ?>
                <?php $renderMissionFooter(); ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="no-print mt-8 flex justify-center space-x-4">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Imprimer</button>
            <a href="<?= url('missions/' . $mission['id']) ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Retour</a>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
