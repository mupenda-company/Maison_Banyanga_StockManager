<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($vente['numero_facture']) ?> - <?= htmlspecialchars($params['nom_entreprise']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .ticket {
            width: 74mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 4mm;
            box-sizing: border-box;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .company-info {
            font-size: 10px;
            margin-bottom: 2px;
        }
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            margin-top: 6px;
        }
        .invoice-date {
            font-size: 10px;
        }
        .section {
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
            border-bottom: 2px solid #111827;
            padding-bottom: 2px;
        }
        .info-row {
            font-size: 11px;
            margin-bottom: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 2px solid #111827;
            padding: 3px 0;
        }
        td {
            padding: 3px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .num {
            text-align: right;
        }
        .totals {
            margin-top: 10px;
            border-top: 2px solid #111827;
            padding-top: 6px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }
        .total-grand {
            font-size: 14px;
            font-weight: bold;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 2px solid #111827;
        }
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 2px solid #111827;
            padding-top: 6px;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .ticket {
                width: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .no-print { display: none !important; }
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
    <div class="ticket">
        <!-- En-tête -->
        <div class="ticket-header">
            <?php if ($companyLogo): ?>
                <img src="<?= $companyLogo ?>" alt="Logo" style="max-height: 30px; margin-bottom: 4px;">
            <?php endif; ?>
            <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
            <?php if (!empty($params['adresse'])): ?><div class="company-info"><?= htmlspecialchars($params['adresse']) ?></div><?php endif; ?>
            <?php if (!empty($companyContact)): ?><div class="company-info">Tel: <?= htmlspecialchars($companyContact) ?></div><?php endif; ?>
            <?php if (!empty($params['email_contact'])): ?><div class="company-info">Email: <?= htmlspecialchars($params['email_contact']) ?></div><?php endif; ?>
            <?php if (!empty($params['rccm'])): ?><div class="company-info">RCCM: <?= htmlspecialchars($params['rccm']) ?></div><?php endif; ?>
            <?php if (!empty($params['id_nat'])): ?><div class="company-info">ID NAT: <?= htmlspecialchars($params['id_nat']) ?></div><?php endif; ?>
            <?php if (!empty($params['nif'])): ?><div class="company-info">NIF: <?= htmlspecialchars($params['nif']) ?></div><?php endif; ?>
            <?php if (!empty($params['numero_compte'])): ?><div class="company-info">Compte: <?= htmlspecialchars($params['numero_compte']) ?></div><?php endif; ?>
            <div class="invoice-number"><?= htmlspecialchars($vente['numero_facture']) ?></div>
            <div class="invoice-date"><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></div>
        </div>
        
        <!-- Client -->
        <div class="section">
            <div class="section-title">CLIENT</div>
            <div class="info-row"><strong><?= htmlspecialchars($vente['client_nom']) ?></strong></div>
            <?php if (!empty($vente['client_telephone'])): ?><div class="info-row">Tel: <?= htmlspecialchars($vente['client_telephone']) ?></div><?php endif; ?>
            <?php if (!empty($vente['zone_nom'])): ?><div class="info-row">Zone: <?= htmlspecialchars($vente['zone_nom']) ?></div><?php endif; ?>
            <?php if (!empty($vente['client_numero'])): ?><div class="info-row">N°: <?= htmlspecialchars($vente['client_numero']) ?></div><?php endif; ?>
            <?php if (!empty($ristourneInfo)): ?>
                <div class="info-row">Cumulé: <?= number_format((int) ($ristourneInfo['total_caisses'] ?? 0), 0, '.', ' ') ?> cs</div>
                <div class="info-row">Ristourne: <?= number_format((float) ($ristourneInfo['taux_applique'] ?? 0), 2, '.', ' ') ?>% (<?= format_money_converted($ristourneInfo['montant_ristourne_net'] ?? $ristourneInfo['montant_ristourne'] ?? 0) ?>)</div>
            <?php elseif (isset($totalCaissesClient)): ?>
                <div class="info-row">Cumulé: <?= number_format((int) $totalCaissesClient, 0, '.', ' ') ?> cs</div>
            <?php endif; ?>
        </div>
        
        <!-- Produits -->
        <div class="section">
            <div class="section-title">PRODUITS</div>
            <?php foreach ($vente['details'] as $detail): 
                $btlParCaisse = (int) ($detail['bouteilles_par_caisses'] ?? 24);
                $caisses = intdiv((int) $detail['quantite'], $btlParCaisse);
                $caissesVidesRecues = (int) ($detail['caisses_vides_recues'] ?? 0);
                $detteCaisses = max(0, $caisses - $caissesVidesRecues);
                $prixCaisse = (float) $detail['prix_unitaire'] * $btlParCaisse;
            ?>
            <div style="margin-bottom: 8px;">
                <div style="font-weight: bold; font-size: 12px;"><?= htmlspecialchars($detail['produit_nom']) ?></div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 2px;">
                    <span><?= number_format($caisses, 0, '.', ' ') ?> cs</span>
                    <span><?= format_money_converted($prixCaisse) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 2px;">
                    <span>Vides: <?= number_format($caissesVidesRecues, 0, '.', ' ') ?> / Dette: <?= number_format($detteCaisses, 0, '.', ' ') ?> cs</span>
                    <span><?= format_money_converted($detail['sous_total']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Totaux -->
        <div class="totals">
            <div class="total-line">
                <span>Total caisses achetées</span>
                <span><?= number_format($totalCaisses, 0, '.', ' ') ?> cs</span>
            </div>
            <div class="total-line">
                <span>Emballages reçus</span>
                <span><?= number_format($totalEmballagesRecus, 0, '.', ' ') ?> cs</span>
            </div>
            <div class="total-line">
                <span>Dette emballages</span>
                <span><?= number_format($totalDetteEmballages, 0, '.', ' ') ?> cs</span>
            </div>
            <div class="total-line">
                <span>Total HT</span>
                <span><?= format_money_converted($totalHt) ?></span>
            </div>
            <div class="total-line">
                <span>TVA (<?= number_format($tvaTaux, 0, '.', ' ') ?>%)</span>
                <span><?= format_money_converted($totalTva) ?></span>
            </div>
            <div class="total-line total-grand">
                <span>TOTAL TTC</span>
                <span><?= format_money_converted($totalTtc) ?></span>
            </div>
        </div>

        <div class="footer">
            <div>Merci pour votre confiance !</div>
            <div>Vendeur: <?= htmlspecialchars(trim(($vente['created_by_prenom'] ?? '') . ' ' . ($vente['created_by_nom'] ?? ''))) ?></div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="no-print" style="margin-top: 12px;">
            <button onclick="window.print()" style="width: 100%; padding: 8px; background: #2563eb; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                IMPRIMER
            </button>
            <a href="<?= url('ventes/' . $vente['id']) ?>" style="display: block; width: 100%; padding: 8px; background: #e5e7eb; color: #374151; text-align: center; border-radius: 4px; text-decoration: none; margin-top: 4px;">
                RETOUR
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
