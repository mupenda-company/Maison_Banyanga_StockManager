<?php
$clients = $clients ?? [];
$params = new Parametre();
$companyName = $params->get('nom_entreprise', APP_NAME);
$logo = $params->get('logo');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR codes clients</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 20px; color: #111827; background: #f3f4f6; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 12px; margin-bottom: 20px; }
        .toolbar button { border: 0; border-radius: 8px; padding: 10px 18px; color: #fff; background: #2563eb; font-weight: 700; cursor: pointer; }
        .cards { display: block; max-width: 190mm; margin: 0 auto; }
        .qr-card { position: relative; width: 100%; min-height: 277mm; margin-bottom: 20px; padding: 10mm; border: 2px solid #111827; border-radius: 12px; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        .logo { width: 58px; height: 58px; object-fit: contain; margin-bottom: 5px; }
        .company { margin: 0; font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .instruction { margin: 4px 0 10px; color: #4b5563; font-size: 12px; }
        .qr { width: 165mm; height: 165mm; aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .qr img, .qr canvas { display: block; width: 165mm !important; height: 165mm !important; max-width: 100%; aspect-ratio: 1 / 1; object-fit: contain; image-rendering: pixelated; }
        .client-name { margin: 10px 0 2px; font-size: 26px; font-weight: 800; }
        .client-meta { margin: 2px 0; font-size: 13px; color: #374151; }
        .notice { position: absolute; right: 10mm; bottom: 8mm; left: 10mm; margin: 0; font-size: 11px; color: #6b7280; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { padding: 0; background: #fff; }
            .toolbar { display: none !important; }
            .cards { width: 190mm; max-width: 190mm; margin: 0; }
            .qr-card { width: 190mm; height: 277mm; min-height: 277mm; margin: 0; border-radius: 0; break-after: page; page-break-after: always; }
            .qr-card:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimer les QR codes</button>
        <button type="button" onclick="window.close()" style="background:#4b5563">Fermer</button>
    </div>
    <main class="cards">
        <?php foreach ($clients as $index => $client): ?>
        <article class="qr-card">
            <?php if ($logo): ?><img class="logo" src="<?= asset('uploads/' . $logo) ?>" alt="Logo"><?php endif; ?>
            <h1 class="company"><?= htmlspecialchars($companyName) ?></h1>
            <p class="instruction">Scannez ce QR code avec l'application de l'agent pour identifier le client.</p>
            <div class="qr" id="client-qr-<?= (int) $index ?>" data-payload="<?= htmlspecialchars($client['qr_payload'], ENT_QUOTES, 'UTF-8') ?>"></div>
            <h2 class="client-name"><?= htmlspecialchars($client['nom']) ?></h2>
            <?php if (!empty($client['numero_client'])): ?><p class="client-meta">N° client : <?= htmlspecialchars($client['numero_client']) ?></p><?php endif; ?>
            <?php if (!empty($client['zone_nom'])): ?><p class="client-meta">Zone : <?= htmlspecialchars($client['zone_nom']) ?></p><?php endif; ?>
            <p class="notice">QR personnel du client — ne pas remplacer par celui d'un autre établissement.</p>
        </article>
        <?php endforeach; ?>
    </main>
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script>
        document.querySelectorAll('.qr[data-payload]').forEach(function (element) {
            new QRCode(element, {
                text: element.dataset.payload,
                width: 900,
                height: 900,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        });
    </script>
</body>
</html>
