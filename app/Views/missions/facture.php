<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture mission <?= htmlspecialchars($mission['numero_mission'] ?? '') ?> - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-8">
    <?php
        $vehicule = $mission['vehicule'] ?? [];
        $companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
        $companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
    ?>
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-start mb-8 border-b pb-6">
            <div>
                <?php if ($companyLogo): ?>
                <img src="<?= $companyLogo ?>" alt="Logo" class="h-16 mb-2 object-contain">
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></h1>
                <?php if (!empty($params['adresse'])): ?><p class="text-sm text-gray-600"><?= htmlspecialchars($params['adresse']) ?></p><?php endif; ?>
                <?php if (!empty($companyContact)): ?><p class="text-sm text-gray-600">Contact: <?= htmlspecialchars($companyContact) ?></p><?php endif; ?>
                <?php if (!empty($params['email_contact'])): ?><p class="text-sm text-gray-600">Email: <?= htmlspecialchars($params['email_contact']) ?></p><?php endif; ?>
                <?php if (!empty($params['rccm'])): ?><p class="text-sm text-gray-600">RCCM: <?= htmlspecialchars($params['rccm']) ?></p><?php endif; ?>
                <?php if (!empty($params['id_nat'])): ?><p class="text-sm text-gray-600">ID NAT: <?= htmlspecialchars($params['id_nat']) ?></p><?php endif; ?>
                <?php if (!empty($params['nif'])): ?><p class="text-sm text-gray-600">NIF: <?= htmlspecialchars($params['nif']) ?></p><?php endif; ?>
                <?php if (!empty($params['numero_compte'])): ?><p class="text-sm text-gray-600">N° compte: <?= htmlspecialchars($params['numero_compte']) ?></p><?php endif; ?>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-blue-600 uppercase">Facture de fin de mission</h2>
                <p class="text-lg font-semibold mt-1"><?= htmlspecialchars($mission['numero_mission'] ?? '') ?></p>
                <p class="text-sm text-gray-600 mt-2">Départ: <?= !empty($mission['date_depart']) ? date('d/m/Y H:i', strtotime($mission['date_depart'])) : '-' ?></p>
                <p class="text-sm text-gray-600">Retour: <?= !empty($mission['date_retour']) ? date('d/m/Y H:i', strtotime($mission['date_retour'])) : '-' ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 text-sm">
            <div class="p-4 border rounded-lg">
                <p class="text-gray-500 uppercase text-xs mb-1">Véhicule</p>
                <p class="font-semibold"><?= htmlspecialchars($mission['immatriculation'] ?? 'N/A') ?></p>
                <?php if (!empty($vehicule['marque']) || !empty($vehicule['modele'])): ?>
                <p class="text-gray-600"><?= htmlspecialchars(trim(($vehicule['marque'] ?? '') . ' ' . ($vehicule['modele'] ?? ''))) ?></p>
                <?php endif; ?>
            </div>
            <div class="p-4 border rounded-lg">
                <p class="text-gray-500 uppercase text-xs mb-1">Agent</p>
                <p class="font-semibold"><?= htmlspecialchars($mission['agent_nom'] ?? trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? ''))) ?></p>
            </div>
            <div class="p-4 border rounded-lg">
                <p class="text-gray-500 uppercase text-xs mb-1">Zone</p>
                <p class="font-semibold"><?= htmlspecialchars($mission['zone_nom'] ?? 'N/A') ?></p>
                <p class="text-gray-600">Statut: <?= htmlspecialchars($mission['statut'] ?? 'N/A') ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 text-sm">
            <div class="p-4 bg-gray-50 rounded-lg border">
                <p class="text-gray-500 uppercase text-xs mb-1">Montant attendu</p>
                <p class="text-lg font-bold"><?= format_money_converted($mission['montant_attendu'] ?? 0) ?></p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg border">
                <p class="text-gray-500 uppercase text-xs mb-1">Montant rendu à la fin</p>
                <p class="text-lg font-bold text-green-700"><?= format_money_converted($mission['montant_encaisse'] ?? 0) ?></p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg border">
                <p class="text-gray-500 uppercase text-xs mb-1">Caisses vides retournées</p>
                <p class="text-lg font-bold"><?= number_format((int) ($mission['caisses_vides_retournees'] ?? 0), 0, ',', ' ') ?></p>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Produits retournés</h3>
            <?php if (empty($mission['chargements'])): ?>
            <div class="p-6 text-center text-gray-500 border rounded-lg">Aucun produit retourné</div>
            <?php else: ?>
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-2 text-xs font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="text-center py-2 text-xs font-semibold text-gray-500 uppercase">Chargé</th>
                        <th class="text-center py-2 text-xs font-semibold text-gray-500 uppercase">Vendu</th>
                        <th class="text-center py-2 text-xs font-semibold text-gray-500 uppercase">Retourné</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500 uppercase">Valeur estimée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $totalRetourne = 0; $totalRendu = 0; ?>
                    <?php foreach ($mission['chargements'] as $chargement): ?>
                    <?php
                        $btlParCaisse = (int) ($chargement['bouteilles_par_caisses'] ?? 24);
                        $quantiteChargee = (int) ($chargement['quantite_chargee'] ?? 0);
                        $quantiteVendue = (int) ($chargement['quantite_vendue'] ?? 0);
                        $quantiteRetournee = (int) ($chargement['quantite_retournee'] ?? 0);
                        $prixCaisse = (float) ($chargement['prix_caisse'] ?? 0);
                        $prixBouteille = $btlParCaisse > 0 && $prixCaisse > 0 ? $prixCaisse / $btlParCaisse : (float) ($chargement['prix_vente_unitaire'] ?? 0);
                        $valeurRetour = $quantiteRetournee * $prixBouteille;
                        $totalRetourne += $quantiteRetournee;
                        $totalRendu += $valeurRetour;
                    ?>
                    <tr>
                        <td class="py-2 font-medium"><?= htmlspecialchars($chargement['produit_nom'] ?? '') ?></td>
                        <td class="py-2 text-center"><?= number_format($quantiteChargee, 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-center"><?= number_format($quantiteVendue, 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-center font-semibold"><?= number_format($quantiteRetournee, 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-right"><?= format_money_converted($valeurRetour) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-t-2 border-gray-200">
                    <tr>
                        <td class="py-2 font-bold">Total</td>
                        <td class="py-2 text-center font-bold"><?= number_format((int) ($mission['total_bouteilles'] ?? 0), 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-center font-bold"><?= number_format((int) ($mission['ventes']['quantite_bouteilles'] ?? 0), 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-center font-bold"><?= number_format($totalRetourne, 0, ',', ' ') ?> btl</td>
                        <td class="py-2 text-right font-bold"><?= format_money_converted($totalRendu) ?></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($mission['notes'])): ?>
        <div class="mb-8 p-4 bg-gray-50 rounded-lg border">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Notes</h4>
            <p class="text-sm text-gray-700"><?= htmlspecialchars($mission['notes']) ?></p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-3 gap-8 mt-16 pt-8 border-t">
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-12">
                    <p class="text-sm text-gray-600">Le Magasinier</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-12">
                    <p class="text-sm text-gray-600">Le Caissier / La Caissière</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-12">
                    <p class="text-sm text-gray-600">Le Responsable</p>
                </div>
            </div>
        </div>

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
