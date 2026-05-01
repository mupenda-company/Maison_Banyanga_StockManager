<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de sortie <?= htmlspecialchars($mission['numero_mission'] ?? '') ?> - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-8">
    <?php $vehicule = $mission['vehicule'] ?? []; ?>
    <div class="max-w-3xl mx-auto">
        <!-- En-tête -->
        <div class="flex justify-between items-start mb-8 border-b pb-6">
            <div>
                <?php if (!empty($params['logo'])): ?>
                <img src="<?= asset('uploads/' . $params['logo']) ?>" alt="Logo" class="h-16 mb-2">
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></h1>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-primary-600">BON DE SORTIE</h2>
                <p class="text-lg font-semibold mt-1"><?= htmlspecialchars($mission['numero_mission']) ?></p>
                <p class="text-sm text-gray-600 mt-2">
                    Date: <?= date('d/m/Y H:i', strtotime($mission['date_depart'])) ?>
                </p>
            </div>
        </div>
        
        <!-- Véhicule et agent -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Véhicule</h3>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['immatriculation']) ?></p>
                <?php if (!empty($vehicule['marque']) || !empty($vehicule['modele'])): ?>
                <p class="text-sm text-gray-600"><?= htmlspecialchars(trim(($vehicule['marque'] ?? '') . ' ' . ($vehicule['modele'] ?? ''))) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Agent responsable</h3>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['agent_nom'] ?? trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? ''))) ?></p>
            </div>
        </div>
        
        <?php if ($mission['zone_nom']): ?>
        <div class="mb-8">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Zone de destination</h3>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($mission['zone_nom']) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Chargement -->
        <div class="mb-8">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Produits chargés</h3>
            <?php if (empty($mission['chargements'])): ?>
            <div class="p-6 text-center text-gray-500">Aucun chargement</div>
            <?php else: ?>
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-2 text-xs font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="text-center py-2 text-xs font-semibold text-gray-500 uppercase">Quantité</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500 uppercase">Caisses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $totalBtl = 0; $totalCaisses = 0; ?>
                    <?php foreach ($mission['chargements'] as $chargement): ?>
                    <?php
                        $btlParCaisse = (int)($chargement['bouteilles_par_caisses'] ?? 24);
                        $quantite = (float)($chargement['quantite_chargee'] ?? 0);
                        $caisses = $btlParCaisse > 0 ? ($quantite / $btlParCaisse) : 0;
                        $totalBtl += $quantite;
                        $totalCaisses += $caisses;
                    ?>
                    <tr>
                        <td class="py-2 font-medium"><?= htmlspecialchars($chargement['produit_nom']) ?></td>
                        <td class="py-2 text-center"><?= number_format($quantite, 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-right"><?= number_format($caisses, 1, '.', ' ') ?> caisses</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-t-2 border-gray-200">
                    <tr>
                        <td class="py-2 font-bold">Total</td>
                        <td class="py-2 text-center font-bold">
                            <?= number_format($totalBtl, 0, ',', ' ') ?> btl
                        </td>
                        <td class="py-2 text-right font-bold">
                            <?= number_format($totalCaisses, 1, '.', ' ') ?> caisses
                        </td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Notes -->
        <?php if ($mission['notes']): ?>
        <div class="mb-8 p-4 bg-gray-50 rounded">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Notes</h4>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($mission['notes']) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-8 mt-16 pt-8 border-t">
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-12">
                    <p class="text-sm text-gray-600">Le Magasinier</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-12">
                    <p class="text-sm text-gray-600">Le Chauffeur</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-12">
                    <p class="text-sm text-gray-600">Le Responsable</p>
                </div>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="no-print mt-8 flex justify-center space-x-4">
            <button onclick="window.print()" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
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
</body>
</html>
