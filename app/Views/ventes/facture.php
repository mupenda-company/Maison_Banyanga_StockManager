<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($vente['numero_facture']) ?> - <?= htmlspecialchars($params['nom_entreprise']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }
        .invoice-sheet {
            width: 186mm;
            min-height: 265mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid #111827;
        }
        .company-block {
            flex: 1;
        }
        .invoice-meta {
            width: 72mm;
            text-align: right;
        }
        .section {
            margin-top: 14px;
        }
        .section-title {
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            background: #f9fafb;
        }
        .table-wrap {
            border: 1px solid #d1d5db;
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
        .num {
            text-align: right;
            white-space: nowrap;
        }
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px 12px;
        }
        .summary-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .summary-value {
            margin-top: 3px;
            font-size: 14px;
            font-weight: 700;
        }
        .totals {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            align-items: start;
        }
        .total-box {
            border: 1px solid #111827;
            border-radius: 12px;
            padding: 12px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }
        .total-grand {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #111827;
            font-size: 15px;
            font-weight: 800;
        }
        .footer {
            margin-top: 16px;
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
            .invoice-sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .no-print { display: none !important; }
        }
        @media screen and (max-width: 900px) {
            .invoice-sheet {
                width: auto;
                min-height: auto;
                margin: 12px;
                padding: 16px;
            }
            .invoice-header,
            .info-grid,
            .totals {
                grid-template-columns: 1fr;
                display: grid;
            }
            .invoice-header {
                display: grid;
            }
            .invoice-meta {
                width: auto;
                text-align: left;
            }
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-white">
    <?php
        $companyLogo = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';
        $companyContact = $params['contact'] ?? ($params['telephone'] ?? '');
        $companyName = $params['nom_entreprise'] ?? APP_NAME;

        $totalCaisses = 0;
        $totalEmballagesRecus = 0;
        $totalDetteEmballages = 0;
        foreach ($vente['details'] as $detail) {
            $btlParCaisse = (int) ($detail['bouteilles_par_caisses'] ?? 24);
            $caisses = intdiv((int) $detail['quantite'], $btlParCaisse);
            $caissesVidesRecues = (int) ($detail['caisses_vides_recues'] ?? 0);
            $totalCaisses += $caisses;
            $totalEmballagesRecus += $caissesVidesRecues;
            $totalDetteEmballages += max(0, $caisses - $caissesVidesRecues);
        }

        $totalHt = (float) ($vente['total_ht'] ?? 0);
        $totalTva = (float) ($vente['total_tva'] ?? 0);
        $totalTtc = (float) ($vente['total_ttc'] ?? 0);
        $tvaTaux = $totalHt > 0 ? round(($totalTva / $totalHt) * 100) : 0;
    ?>
    <div class="invoice-sheet">
        <!-- En-tête -->
        <div class="invoice-header">
            <div class="company-block">
                <?php if ($companyLogo): ?>
                    <img src="<?= $companyLogo ?>" alt="Logo" class="h-16 mb-3 object-contain">
                <?php endif; ?>
                <h1 class="text-2xl font-bold uppercase leading-tight"><?= htmlspecialchars($companyName) ?></h1>
                <?php if (!empty($params['adresse'])): ?><p><?= htmlspecialchars($params['adresse']) ?></p><?php endif; ?>
                <?php if (!empty($companyContact)): ?><p>Contact: <?= htmlspecialchars($companyContact) ?></p><?php endif; ?>
                <?php if (!empty($params['email_contact'])): ?><p>Email: <?= htmlspecialchars($params['email_contact']) ?></p><?php endif; ?>
                <?php if (!empty($params['rccm'])): ?><p>RCCM: <?= htmlspecialchars($params['rccm']) ?></p><?php endif; ?>
                <?php if (!empty($params['id_nat'])): ?><p>ID NAT: <?= htmlspecialchars($params['id_nat']) ?></p><?php endif; ?>
                <?php if (!empty($params['nif'])): ?><p>NIF: <?= htmlspecialchars($params['nif']) ?></p><?php endif; ?>
                <?php if (!empty($params['numero_compte'])): ?><p>N° compte: <?= htmlspecialchars($params['numero_compte']) ?></p><?php endif; ?>
            </div>

            <div class="invoice-meta">
                <div class="card" style="background:#111827;color:#fff;border-color:#111827;">
                    <div class="summary-label" style="color:#cbd5e1;">Facture</div>
                    <div class="summary-value" style="font-size:22px; margin-top:4px;"><?= htmlspecialchars($vente['numero_facture']) ?></div>
                    <div style="margin-top:8px; font-size:11px; color:#e5e7eb;">
                        <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Client -->
        <div class="section info-grid">
            <div class="card">
                <div class="section-title">Client</div>
                <p><strong>Nom:</strong> <?= htmlspecialchars($vente['client_nom']) ?></p>
                <?php if (!empty($vente['client_telephone'])): ?><p><strong>Téléphone:</strong> <?= htmlspecialchars($vente['client_telephone']) ?></p><?php endif; ?>
                <?php if (!empty($vente['client_numero'])): ?><p><strong>N° client:</strong> <?= htmlspecialchars($vente['client_numero']) ?></p><?php endif; ?>
                <?php if (!empty($vente['zone_nom'])): ?><p><strong>Zone:</strong> <?= htmlspecialchars($vente['zone_nom']) ?></p><?php endif; ?>
            </div>

            <div class="card">
                <div class="section-title">Résumé</div>
                <?php if (!empty($ristourneInfo)): ?>
                    <p><strong>Produits cumulés (période):</strong> <?= number_format((int) ($ristourneInfo['total_caisses'] ?? 0), 0, '.', ' ') ?> cs</p>
                    <p><strong>Ristourne:</strong> <?= number_format((float) ($ristourneInfo['taux_applique'] ?? 0), 2, '.', ' ') ?>% (<?= format_money_converted($ristourneInfo['montant_ristourne'] ?? 0) ?>)</p>
                <?php elseif (isset($totalCaissesClient)): ?>
                    <p><strong>Produits cumulés:</strong> <?= number_format((int) $totalCaissesClient, 0, '.', ' ') ?> cs</p>
                <?php endif; ?>
                <p><strong>Vendeur:</strong> <?= htmlspecialchars(trim(($vente['created_by_prenom'] ?? '') . ' ' . ($vente['created_by_nom'] ?? ''))) ?></p>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Détails produits</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="num">Caisses</th>
                            <th class="num">Emballages reçus</th>
                            <th class="num">Dette</th>
                            <th class="num">Prix caisse</th>
                            <th class="num">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vente['details'] as $detail): 
                            $btlParCaisse = (int) ($detail['bouteilles_par_caisses'] ?? 24);
                            $caisses = intdiv((int) $detail['quantite'], $btlParCaisse);
                            $caissesVidesRecues = (int) ($detail['caisses_vides_recues'] ?? 0);
                            $detteCaisses = max(0, $caisses - $caissesVidesRecues);
                            $prixCaisse = (float) $detail['prix_unitaire'] * $btlParCaisse;
                        ?>
                        <tr>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($detail['produit_nom']) ?></div>
                                <?php if (!empty($detail['produit_code'])): ?>
                                    <div style="font-size:10px; color:#6b7280; font-family: monospace;"><?= htmlspecialchars($detail['produit_code']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <div class="font-medium"><?= number_format($caisses, 0, '.', ' ') ?> cs</div>
                                <div style="font-size:10px; color:#6b7280;">= <?= number_format((int) $detail['quantite'], 0, '.', ' ') ?> btl</div>
                            </td>
                            <td class="num"><?= number_format($caissesVidesRecues, 0, '.', ' ') ?> cs</td>
                            <td class="num" style="font-weight:700; color:<?= $detteCaisses > 0 ? '#dc2626' : '#16a34a' ?>;">
                                <?= number_format($detteCaisses, 0, '.', ' ') ?> cs
                            </td>
                            <td class="num"><?= format_money_converted($prixCaisse) ?></td>
                            <td class="num" style="font-weight:700; color:#2563eb;">
                                <?= format_money_converted($detail['sous_total']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Totaux -->
        <div class="section totals">
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Caisses vendues</div>
                    <div class="summary-value"><?= number_format($totalCaisses, 0, '.', ' ') ?> cs</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Emballages reçus</div>
                    <div class="summary-value"><?= number_format($totalEmballagesRecus, 0, '.', ' ') ?> cs</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Dette emballages</div>
                    <div class="summary-value" style="color: <?= $totalDetteEmballages > 0 ? '#dc2626' : '#16a34a' ?>;">
                        <?= number_format($totalDetteEmballages, 0, '.', ' ') ?> cs
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">TVA</div>
                    <div class="summary-value"><?= number_format($tvaTaux, 0, '.', ' ') ?>%</div>
                </div>
            </div>

            <div class="total-box">
                <div class="total-line">
                    <span>Total HT</span>
                    <span><?= format_money_converted($totalHt) ?></span>
                </div>
                <div class="total-line">
                    <span>TVA (<?= number_format($tvaTaux, 0, '.', ' ') ?>%)</span>
                    <span><?= format_money_converted($totalTva) ?></span>
                </div>
                <div class="total-line">
                    <span>Emballages reçus</span>
                    <span><?= number_format($totalEmballagesRecus, 0, '.', ' ') ?> cs</span>
                </div>
                <div class="total-line">
                    <span>Dette emballages</span>
                    <span><?= number_format($totalDetteEmballages, 0, '.', ' ') ?> cs</span>
                </div>
                <div class="total-line total-grand">
                    <span>TOTAL TTC</span>
                    <span><?= format_money_converted($totalTtc) ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Merci pour votre confiance !</p>
            <p>Vendeur: <?= htmlspecialchars(trim(($vente['created_by_prenom'] ?? '') . ' ' . ($vente['created_by_nom'] ?? ''))) ?></p>
        </div>
        
        <!-- Boutons d'action -->
        <div class="no-print mt-8 flex flex-col gap-2">
            <button onclick="window.print()" class="w-full py-2 bg-blue-600 text-white rounded font-bold">
                IMPRIMER
            </button>
            <a href="<?= url('ventes/' . $vente['id']) ?>" class="w-full py-2 bg-gray-200 text-center text-gray-700 rounded">
                RETOUR
            </a>
        </div>
    </div>

    <script>
        // Lancer l'impression automatiquement au chargement
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
