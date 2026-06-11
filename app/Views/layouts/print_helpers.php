<?php
if (!function_exists('print_report_company')) {
    function print_report_company()
    {
        $parametre = new Parametre();
        return [
            'name' => $parametre->get('nom_entreprise', APP_NAME),
            'address' => $parametre->get('adresse', ''),
            'phone' => $parametre->get('telephone', ''),
        ];
    }
}

if (!function_exists('print_report_css')) {
    function print_report_css($landscape = true)
    {
        $size = $landscape ? 'A4 landscape' : 'A4 portrait';
        return <<<CSS
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; color: #222; font-size: 10px; line-height: 1.35; background: #fff; }
.page { padding: 12px; }
.page-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; }
.page-header h1 { font-size: 16px; text-transform: uppercase; margin-bottom: 3px; }
.page-header p { font-size: 10px; color: #555; }
.info-bar { display: flex; justify-content: space-between; gap: 8px; padding: 7px 10px; border: 1px solid #bbb; background: #f6f6f6; margin-bottom: 10px; }
.info-bar div { font-size: 10px; }
.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 10px; }
.summary-item { border: 1px solid #bbb; background: #fafafa; padding: 7px; }
.summary-label { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 3px; }
.summary-value { font-size: 12px; font-weight: bold; }
.section-title { font-size: 12px; font-weight: bold; padding: 5px 7px; background: #e8e8e8; margin: 10px 0 6px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 1px solid #999; padding: 4px 5px; vertical-align: top; overflow-wrap: anywhere; word-break: normal; }
th { background: #e8e8e8; text-align: center; font-weight: bold; font-size: 9px; text-transform: uppercase; }
td { font-size: 9px; }
.num { text-align: right; white-space: nowrap; }
.center { text-align: center; }
.muted { color: #666; font-size: 8px; }
.total-row td { background: #eee; font-weight: bold; }
.ok { color: #166534; font-weight: bold; }
.warn { color: #b91c1c; font-weight: bold; }
.no-print { margin: 12px 0; text-align: center; }
.no-print button { padding: 7px 14px; cursor: pointer; }
thead { display: table-header-group; }
tfoot { display: table-footer-group; }
tr, .summary-item { break-inside: avoid; page-break-inside: avoid; }
@media print {
    @page { size: {$size}; margin: 10mm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .page { padding: 0; }
    .no-print { display: none !important; }
}
</style>
CSS;
    }
}

if (!function_exists('print_report_header')) {
    function print_report_header($title, $subtitle = '')
    {
        $company = print_report_company();
        $meta = trim(($company['address'] ? $company['address'] . ' | ' : '') . ($company['phone'] ?? ''));
        ?>
        <div class="page-header">
            <h1><?= htmlspecialchars($company['name']) ?></h1>
            <?php if ($meta): ?><p><?= htmlspecialchars($meta) ?></p><?php endif; ?>
            <p><?= htmlspecialchars($title) ?><?= $subtitle ? ' - ' . htmlspecialchars($subtitle) : '' ?></p>
        </div>
        <?php
    }
}

if (!function_exists('print_report_scripts')) {
    function print_report_scripts()
    {
        ?>
        <div class="no-print">
            <button onclick="window.print()">Imprimer</button>
            <button onclick="window.close()">Fermer</button>
        </div>
        <script>
            window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });
            window.addEventListener('afterprint', function () { if (window.opener) window.close(); });
        </script>
        <?php
    }
}
