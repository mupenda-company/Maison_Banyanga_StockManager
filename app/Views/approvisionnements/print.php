<?php
$numeroBon = $approvisionnement['numero_bon'] ?? $approvisionnement['id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvisionnement <?= htmlspecialchars($numeroBon) ?></title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 10px; }
        .no-print { margin-bottom: 10px; text-align: center; }
        h1 { margin: 0; font-size: 18px; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #111; padding: 5px 4px; vertical-align: middle; }
        th { font-size: 11px; text-align: left; font-weight: 800; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: 800; background: #f3f4f6; }
        button, a { display: inline-block; padding: 7px 12px; border-radius: 4px; border: 1px solid #ccc; background: #f3f4f6; color: #111; text-decoration: none; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Imprimer</button>
        <a href="<?= htmlspecialchars(url('approvisionnements/' . $approvisionnement['id']), ENT_QUOTES, 'UTF-8') ?>">Retour</a>
    </div>

    <header>
        <h1>Approvisionnement <?= htmlspecialchars($numeroBon) ?></h1>
        <p>Date: <?= !empty($approvisionnement['date_approvisionnement']) ? date('d/m/Y', strtotime($approvisionnement['date_approvisionnement'])) : '' ?></p>
        <p>Fournisseur: <?= htmlspecialchars($approvisionnement['fournisseur'] ?? 'Bralima') ?></p>
        <p>Taux systeme: 1 USD = <?= number_format(get_taux_change(), 2, ',', ' ') ?> CDF</p>
        <p><strong>Total prix achat: <?= format_money_dual($rows['totals']['pt']) ?></strong></p>
    </header>

    <table>
        <thead>
            <tr>
                <th>PRODUITS</th>
                <th class="num">N P</th>
                <th class="num">ACHAT</th>
                <th class="num">PLT</th>
                <th class="num">P.A.AD</th>
                <th class="num">P.A.A.E</th>
                <th class="num">P.T</th>
                <th class="num">P.V.U</th>
                <th class="num">P.V.T</th>
                <th class="num">ECART</th>
                <th class="num">TOTAL EC</th>
                <th class="num">ECART A EN</th>
                <th class="num">TOTAL A ENL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows['items'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['produit']) ?></td>
                <td class="num"><?= number_format($row['np'], 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($row['achat'], 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($row['plt'], 2, ',', ' ') ?></td>
                <td class="num"><?= format_money_converted($row['paad']) ?></td>
                <td class="num"><?= format_money_converted($row['paae']) ?></td>
                <td class="num"><?= format_money_converted($row['pt']) ?></td>
                <td class="num"><?= format_money_converted($row['pvu']) ?></td>
                <td class="num"><?= format_money_converted($row['pvt']) ?></td>
                <td class="num"><?= format_money_converted($row['ecart']) ?></td>
                <td class="num"><?= format_money_converted($row['total_ec']) ?></td>
                <td class="num"><?= format_money_converted($row['ecart_a_en']) ?></td>
                <td class="num"><?= format_money_converted($row['total_a_enl']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAUX CDF</td>
                <td></td>
                <td class="num"><?= number_format($rows['totals']['achat'], 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($rows['totals']['plt'], 2, ',', ' ') ?></td>
                <td></td>
                <td></td>
                <td class="num"><?= format_money_converted($rows['totals']['pt']) ?></td>
                <td></td>
                <td class="num"><?= format_money_converted($rows['totals']['pvt']) ?></td>
                <td></td>
                <td class="num"><?= format_money_converted($rows['totals']['total_ec']) ?></td>
                <td></td>
                <td class="num"><?= format_money_converted($rows['totals']['total_a_enl']) ?></td>
            </tr>
            <tr>
                <td>TOTAUX USD</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="num"><?= format_money(convert_money($rows['totals']['pt'], get_base_devise(), 'USD'), 'USD') ?></td>
                <td></td>
                <td class="num"><?= format_money(convert_money($rows['totals']['pvt'], get_base_devise(), 'USD'), 'USD') ?></td>
                <td></td>
                <td class="num"><?= format_money(convert_money($rows['totals']['total_ec'], get_base_devise(), 'USD'), 'USD') ?></td>
                <td></td>
                <td class="num"><?= format_money(convert_money($rows['totals']['total_a_enl'], get_base_devise(), 'USD'), 'USD') ?></td>
            </tr>
        </tfoot>
    </table>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
