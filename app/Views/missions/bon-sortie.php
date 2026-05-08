<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de sortie <?= htmlspecialchars($mission['numero_mission'] ?? '') ?> - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-4 text-sm">
    <?php $vehicule = $mission['vehicule'] ?? []; ?>
    <?php
        $companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
        $companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
    ?>
    <div class="max-w-4xl mx-auto">
        <!-- En-tête -->
        <div class="flex justify-between items-start mb-5 border-b pb-4 gap-4">
            <div>
                <?php if ($companyLogo): ?>
                <img src="<?= $companyLogo ?>" alt="Logo" class="h-14 mb-2 object-contain">
                <?php endif; ?>
                <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></h1>
                <?php if (!empty($params['adresse'])): ?><p class="text-xs text-gray-600"><?= htmlspecialchars($params['adresse']) ?></p><?php endif; ?>
                <?php if (!empty($companyContact)): ?><p class="text-xs text-gray-600">Contact: <?= htmlspecialchars($companyContact) ?></p><?php endif; ?>
                <?php if (!empty($params['email_contact'])): ?><p class="text-xs text-gray-600">Email: <?= htmlspecialchars($params['email_contact']) ?></p><?php endif; ?>
                <?php if (!empty($params['rccm'])): ?><p class="text-xs text-gray-600">RCCM: <?= htmlspecialchars($params['rccm']) ?></p><?php endif; ?>
                <?php if (!empty($params['id_nat'])): ?><p class="text-xs text-gray-600">ID NAT: <?= htmlspecialchars($params['id_nat']) ?></p><?php endif; ?>
                <?php if (!empty($params['nif'])): ?><p class="text-xs text-gray-600">NIF: <?= htmlspecialchars($params['nif']) ?></p><?php endif; ?>
                <?php if (!empty($params['numero_compte'])): ?><p class="text-xs text-gray-600">N° compte: <?= htmlspecialchars($params['numero_compte']) ?></p><?php endif; ?>
            </div>
            <div class="text-right">
                <h2 class="text-lg font-bold text-blue-600">BON DE SORTIE</h2>
                <p class="text-base font-semibold mt-1"><?= htmlspecialchars($mission['numero_mission']) ?></p>
                <p class="text-xs text-gray-600 mt-2">
                    Date: <?= date('d/m/Y H:i', strtotime($mission['date_depart'])) ?>
                </p>
            </div>
        </div>
        
        <!-- Véhicule et agent -->
        <div class="grid grid-cols-2 gap-5 mb-5 text-xs">
            <div>
                <h3 class="font-semibold text-gray-500 uppercase tracking-wider mb-1">Véhicule</h3>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['immatriculation']) ?></p>
                <?php if (!empty($vehicule['marque']) || !empty($vehicule['modele'])): ?>
                <p class="text-gray-600"><?= htmlspecialchars(trim(($vehicule['marque'] ?? '') . ' ' . ($vehicule['modele'] ?? ''))) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3 class="font-semibold text-gray-500 uppercase tracking-wider mb-1">Agent responsable</h3>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['agent_nom'] ?? trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? ''))) ?></p>
            </div>
        </div>
        
        <?php if ($mission['zone_nom']): ?>
        <div class="mb-5 text-xs">
            <h3 class="font-semibold text-gray-500 uppercase tracking-wider mb-1">Zone de destination</h3>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['zone_nom']) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Chargement -->
        <div class="mb-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Produits chargés</h3>
            <?php if (empty($mission['chargements'])): ?>
            <div class="p-4 text-center text-gray-500 border rounded-lg">Aucun chargement</div>
            <?php else: ?>
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-2 font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="text-right py-2 font-semibold text-gray-500 uppercase">Stock départ</th>
                        <th class="text-right py-2 font-semibold text-gray-500 uppercase">Ajout mission</th>
                        <th class="text-right py-2 font-semibold text-gray-500 uppercase">Total réel</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $totalCaisses = 0; ?>
                    <?php foreach ($mission['chargements'] as $chargement): ?>
                    <?php
                        $btlParCaisse = (int)($chargement['bouteilles_par_caisses'] ?? 24);
                        if ($btlParCaisse <= 0) {
                            $btlParCaisse = 24;
                        }

                        $stockDepartCaisses = (int) ($chargement['caisses_deja_dans_vehicule'] ?? 0);
                        $ajoutMissionCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
                        $totalReelCaisses = (int) ($chargement['caisses_total'] ?? ($stockDepartCaisses + $ajoutMissionCaisses));
                        $totalCaisses += $totalReelCaisses;
                    ?>
                    <tr>
                        <td class="py-2 font-medium"><?= htmlspecialchars($chargement['produit_nom']) ?></td>
                        <td class="py-2 text-right font-medium"><?= number_format($stockDepartCaisses, 1, ',', ' ') ?> cs</td>
                        <td class="py-2 text-right font-medium"><?= number_format($ajoutMissionCaisses, 1, ',', ' ') ?> cs</td>
                        <td class="py-2 text-right font-bold text-blue-600"><?= number_format($totalReelCaisses, 1, ',', ' ') ?> cs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-t-2 border-gray-200">
                    <tr>
                        <td class="py-2 font-bold">Total</td>
                        <td colspan="2"></td>
                        <td class="py-2 text-right font-bold"><?= number_format($totalCaisses, 1, ',', ' ') ?> cs</td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Notes -->
        <?php if ($mission['notes']): ?>
        <div class="mb-5 p-3 bg-gray-50 rounded text-xs">
            <h4 class="font-semibold text-gray-500 uppercase tracking-wider mb-1">Notes</h4>
            <p class="text-gray-600"><?= htmlspecialchars($mission['notes']) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-6 mt-10 pt-6 border-t text-xs">
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-10">
                    <p class="text-gray-600">Le Magasinier</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-10">
                    <p class="text-gray-600">Le Chauffeur</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-10">
                    <p class="text-gray-600">Le Responsable</p>
                </div>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="no-print mt-6 flex justify-center space-x-4">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
            <a href="<?= url('missions/' . $mission['id']) ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Retour
            </a>
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
