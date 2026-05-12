<?php
$pageTitle = 'Impression véhicule';
$vehicule = $vehicule ?? [];
$missions = $missions ?? [];
$stats = $stats ?? ['nb_missions' => 0, 'total_livre' => 0, 'total_ca' => 0];
$params = $params ?? [];
$stock = $vehicule['stock'] ?? [];
$companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
$companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail véhicule <?= htmlspecialchars($vehicule['immatriculation'] ?? '') ?> - <?= htmlspecialchars($params['nom_entreprise'] ?? APP_NAME) ?></title>
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
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-3 items-start mb-5 border-b pb-4 gap-4">
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
            <div class="text-center self-center">
                <h2 class="text-lg font-bold text-blue-600 uppercase">DÉTAIL VÉHICULE</h2>
                <p class="text-base font-semibold mt-1"><?= htmlspecialchars($vehicule['immatriculation'] ?? '') ?></p>
                <p class="text-xs text-gray-600 mt-2">Imprimé le: <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="text-right text-xs text-gray-600 self-center">
                <p class="uppercase font-semibold tracking-wide text-gray-500">Résumé</p>
                <p class="font-semibold text-gray-900"><?= (int) ($stats['nb_missions'] ?? 0) ?> mission(s)</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5 text-xs">
            <div class="p-3 rounded-lg border bg-gray-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Marque</p>
                <p class="font-bold text-gray-900 mt-1"><?= htmlspecialchars($vehicule['marque'] ?? 'N/A') ?></p>
            </div>
            <div class="p-3 rounded-lg border bg-gray-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Modèle</p>
                <p class="font-bold text-gray-900 mt-1"><?= htmlspecialchars($vehicule['modele'] ?? 'N/A') ?></p>
            </div>
            <div class="p-3 rounded-lg border bg-gray-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Capacité</p>
                <p class="font-bold text-gray-900 mt-1"><?= (int) ($vehicule['capacite'] ?? 0) ?> caisses</p>
            </div>
            <div class="p-3 rounded-lg border bg-gray-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Agent responsable</p>
                <p class="font-bold text-gray-900 mt-1"><?= htmlspecialchars(trim(($vehicule['agent_prenom'] ?? '') . ' ' . ($vehicule['agent_nom'] ?? ''))) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-5 text-xs">
            <div class="p-3 rounded-lg border bg-blue-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Missions du mois</p>
                <p class="text-2xl font-bold text-blue-700 mt-1"><?= (int) ($stats['nb_missions'] ?? 0) ?></p>
            </div>
            <div class="p-3 rounded-lg border bg-green-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Caisses livrées</p>
                <p class="text-2xl font-bold text-green-700 mt-1"><?= number_format((float) ($stats['total_livre'] ?? 0), 1, ',', ' ') ?></p>
            </div>
            <div class="p-3 rounded-lg border bg-primary-50">
                <p class="text-gray-500 uppercase font-semibold tracking-wider">Chiffre d'affaires</p>
                <p class="text-2xl font-bold text-primary-700 mt-1"><?= format_money_converted($stats['total_ca'] ?? 0) ?></p>
            </div>
        </div>

        <div class="mb-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Stock actuel dans le véhicule</h3>
            <?php if (empty($stock)): ?>
                <div class="p-4 text-center text-gray-500 border rounded-lg">Aucun stock</div>
            <?php else: ?>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left py-2 font-semibold text-gray-500 uppercase">Produit</th>
                            <th class="text-right py-2 font-semibold text-gray-500 uppercase">Caisses Pleines</th>
                            <th class="text-right py-2 font-semibold text-gray-500 uppercase">Caisses Vides</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($stock as $item): ?>
                        <tr>
                            <td class="py-2 font-medium"><?= htmlspecialchars($item['produit_nom'] ?? 'N/A') ?></td>
                            <td class="py-2 text-right font-bold text-green-600"><?= number_format((float) ($item['caisses_pleine'] ?? 0), 1, ',', ' ') ?></td>
                            <td class="py-2 text-right font-bold text-orange-600"><?= number_format((float) ($item['caisses_vide'] ?? 0), 1, ',', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200">
                        <tr>
                            <td class="py-2 font-bold">Total</td>
                            <td class="py-2 text-right font-bold text-green-600"><?= number_format(array_sum(array_column($stock, 'caisses_pleine')), 1, ',', ' ') ?> caisses</td>
                            <td class="py-2 text-right font-bold text-orange-600"><?= number_format(array_sum(array_column($stock, 'caisses_vide')), 1, ',', ' ') ?> caisses</td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>

        <div class="mb-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Dernières missions</h3>
            <?php if (empty($missions)): ?>
                <div class="p-4 text-center text-gray-500 border rounded-lg">Aucune mission enregistrée</div>
            <?php else: ?>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left py-2 font-semibold text-gray-500 uppercase">Mission</th>
                            <th class="text-left py-2 font-semibold text-gray-500 uppercase">Date</th>
                            <th class="text-left py-2 font-semibold text-gray-500 uppercase">Zone</th>
                            <th class="text-left py-2 font-semibold text-gray-500 uppercase">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($missions as $mission): ?>
                        <tr>
                            <td class="py-2 font-medium"><?= htmlspecialchars($mission['numero_mission'] ?? 'N/A') ?></td>
                            <td class="py-2"><?= !empty($mission['date_depart']) ? date('d/m/Y H:i', strtotime($mission['date_depart'])) : 'N/A' ?></td>
                            <td class="py-2"><?= htmlspecialchars($mission['zone_nom'] ?? 'N/A') ?></td>
                            <td class="py-2">
                                <?php if (($mission['statut'] ?? '') === 'en_cours'): ?>
                                    <span class="text-amber-600 font-semibold">En cours</span>
                                <?php else: ?>
                                    <span class="text-green-600 font-semibold">Terminée</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($vehicule['emplacement_nom'])): ?>
        <div class="mb-5 text-xs">
            <h3 class="font-semibold text-gray-500 uppercase tracking-wider mb-1">Emplacement</h3>
            <p class="font-semibold text-gray-900"><?= htmlspecialchars($vehicule['emplacement_nom']) ?></p>
        </div>
        <?php endif; ?>

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

        <div class="no-print mt-6 flex justify-center space-x-4">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Imprimer
            </button>
            <a href="<?= url('vehicules/' . ($vehicule['id'] ?? 0)) ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
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
<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
