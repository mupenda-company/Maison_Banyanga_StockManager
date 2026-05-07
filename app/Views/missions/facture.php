<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture mission <?= htmlspecialchars($mission['numero_mission'] ?? '') ?> - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .mission-page { break-after: page; page-break-after: always; }
        .mission-page:last-child { break-after: auto; page-break-after: auto; }
        .compact-table th,
        .compact-table td {
            padding-top: 0.35rem;
            padding-bottom: 0.35rem;
            line-height: 1.15rem;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
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
        $totalCaissesRetournees = 0;
        $totalRendu = 0;

        foreach ($chargements as $chargement) {
            $btlParCaisse = (int) ($chargement['bouteilles_par_caisses'] ?? 24);
            $quantiteChargee = (int) ($chargement['quantite_chargee'] ?? 0);
            $quantiteVendue = (int) ($chargement['quantite_vendue'] ?? 0);
            $quantiteRetournee = (int) ($chargement['quantite_retournee'] ?? 0);
            $prixCaisse = (float) ($chargement['prix_caisse'] ?? 0);
            $prixBouteille = $btlParCaisse > 0 && $prixCaisse > 0 ? $prixCaisse / $btlParCaisse : (float) ($chargement['prix_vente_unitaire'] ?? 0);

            $totalCaissesChargees += $btlParCaisse > 0 ? ($quantiteChargee / $btlParCaisse) : 0;
            $totalCaissesVendues += $btlParCaisse > 0 ? ($quantiteVendue / $btlParCaisse) : 0;
            $totalCaissesRetournees += $btlParCaisse > 0 ? ($quantiteRetournee / $btlParCaisse) : 0;
            $totalRendu += $quantiteRetournee * $prixBouteille;
        }

        $renderMissionFooter = function () use ($mission, $totalCaissesChargees, $totalCaissesVendues, $totalCaissesRetournees, $totalRendu) {
            ?>
            <div class="p-3 border rounded-lg bg-gray-50 text-sm mt-3">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <div class="flex items-center justify-between gap-3 rounded border border-gray-200 bg-white px-3 py-2">
                        <p class="text-gray-500 uppercase text-[11px] leading-tight">Total chargés</p>
                        <p class="font-bold whitespace-nowrap"><?= number_format($totalCaissesChargees, 1, ',', ' ') ?> cs</p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded border border-gray-200 bg-white px-3 py-2">
                        <p class="text-gray-500 uppercase text-[11px] leading-tight">Total vendus</p>
                        <p class="font-bold whitespace-nowrap"><?= number_format($totalCaissesVendues, 1, ',', ' ') ?> cs</p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded border border-gray-200 bg-white px-3 py-2">
                        <p class="text-gray-500 uppercase text-[11px] leading-tight">Total retournés</p>
                        <p class="font-bold whitespace-nowrap"><?= number_format($totalCaissesRetournees, 1, ',', ' ') ?> cs</p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded border border-gray-200 bg-white px-3 py-2">
                        <p class="text-gray-500 uppercase text-[11px] leading-tight">Valeur totale</p>
                        <p class="font-bold text-base whitespace-nowrap"><?= format_money_converted($totalRendu) ?></p>
                    </div>
                </div>
            </div>

            <?php if (!empty($mission['notes'])): ?>
            <div class="mt-3 p-3 bg-gray-50 rounded-lg border text-sm">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Notes</h4>
                <p class="text-gray-700"><?= htmlspecialchars($mission['notes']) ?></p>
            </div>
            <?php endif; ?>

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
        <div class="flex justify-between items-start gap-4 mb-6 border-b pb-4">
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
            <div class="text-right leading-tight">
                <h2 class="text-lg font-bold text-blue-600 uppercase">Facture de fin de mission</h2>
                <p class="text-sm font-semibold mt-1"><?= htmlspecialchars($mission['numero_mission'] ?? '') ?></p>
                <p class="text-xs text-gray-600 mt-1 flex flex-col items-end gap-0.5">
                    <span>Départ: <?= !empty($mission['date_depart']) ? date('d/m/Y H:i', strtotime($mission['date_depart'])) : '-' ?></span>
                    <span>Retour: <?= !empty($mission['date_retour']) ? date('d/m/Y H:i', strtotime($mission['date_retour'])) : '-' ?></span>
                </p>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6 text-sm">
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Montant attendu</p>
                <p class="text-base font-bold whitespace-nowrap"><?= format_money_converted($mission['montant_attendu'] ?? 0) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Montant rendu</p>
                <p class="text-base font-bold text-green-700 whitespace-nowrap"><?= format_money_converted($mission['montant_encaisse'] ?? 0) ?></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border flex items-center justify-between gap-3">
                <p class="text-gray-500 uppercase text-xs">Caisses vides</p>
                <p class="text-base font-bold whitespace-nowrap"><?= number_format((int) ($mission['caisses_vides_retournees'] ?? 0), 0, ',', ' ') ?></p>
            </div>
        </div>

        <?php if (empty($chargements)): ?>
        <div class="mission-page">
            <div class="mb-6 p-6 text-center text-gray-500 border rounded-lg">Aucun produit retourné</div>
            <?php $renderMissionFooter(); ?>
        </div>
        <?php else: ?>
        <?php foreach ($chargementsPages as $pageIndex => $pageChargements): ?>
        <div class="mission-page <?= $pageIndex === $pagesCount - 1 ? '' : '' ?>">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Produits retournés</h3>
                <?php if ($pagesCount > 1): ?>
                <p class="text-xs text-gray-500">Page <?= $pageIndex + 1 ?> / <?= $pagesCount ?></p>
                <?php endif; ?>
            </div>
            <table class="w-full border-collapse text-sm compact-table">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="text-center py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Chargé</th>
                        <th class="text-center py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Vendu</th>
                        <th class="text-center py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Retourné</th>
                        <th class="text-right py-1.5 text-[11px] font-semibold text-gray-500 uppercase">Valeur estimée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($pageChargements as $chargement): ?>
                    <?php
                        $btlParCaisse = (int) ($chargement['bouteilles_par_caisses'] ?? 24);
                        $quantiteChargee = (int) ($chargement['quantite_chargee'] ?? 0);
                        $quantiteVendue = (int) ($chargement['quantite_vendue'] ?? 0);
                        $quantiteRetournee = (int) ($chargement['quantite_retournee'] ?? 0);
                        $prixCaisse = (float) ($chargement['prix_caisse'] ?? 0);
                        $prixBouteille = $btlParCaisse > 0 && $prixCaisse > 0 ? $prixCaisse / $btlParCaisse : (float) ($chargement['prix_vente_unitaire'] ?? 0);
                        $caissesChargees = $btlParCaisse > 0 ? ($quantiteChargee / $btlParCaisse) : 0;
                        $caissesVendues = $btlParCaisse > 0 ? ($quantiteVendue / $btlParCaisse) : 0;
                        $caissesRetournees = $btlParCaisse > 0 ? ($quantiteRetournee / $btlParCaisse) : 0;
                        $valeurRetour = $quantiteRetournee * $prixBouteille;
                    ?>
                    <tr>
                        <td class="py-1.5 font-medium leading-tight"><?= htmlspecialchars($chargement['produit_nom'] ?? '') ?></td>
                        <td class="py-1.5 text-center whitespace-nowrap"><?= number_format($caissesChargees, 1, ',', ' ') ?> cs</td>
                        <td class="py-1.5 text-center whitespace-nowrap"><?= number_format($caissesVendues, 1, ',', ' ') ?> cs</td>
                        <td class="py-1.5 text-center font-semibold whitespace-nowrap"><?= number_format($caissesRetournees, 1, ',', ' ') ?> cs</td>
                        <td class="py-1.5 text-right whitespace-nowrap"><?= format_money_converted($valeurRetour) ?></td>
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
