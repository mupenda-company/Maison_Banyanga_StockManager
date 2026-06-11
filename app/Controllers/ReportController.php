<?php
/**
 * Controleur des Rapports et Statistiques
 */

class ReportController extends Controller
{
    private $venteModel;
    private $produitModel;
    private $clientModel;
    private $retourModel;
    private $detteModel;

    public function __construct()
    {
        parent::__construct();
        $this->venteModel = new Vente();
        $this->produitModel = new Produit();
        $this->clientModel = new Client();
        $this->retourModel = new RetourEmballage();
        $this->detteModel = new DetteEmballage();
    }

    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        $statsVentes = $this->venteModel->getStatsGlobales($dateDebut, $dateFin);
        $topProduits = $this->produitModel->getTopVentes($dateDebut, $dateFin, 5);
        $ventesParZone = $this->venteModel->getVentesParZone($dateDebut, $dateFin);
        $statsEmballages = $this->retourModel->getStats($dateDebut, $dateFin, 5);
        $statsDettes = $this->detteModel->getStatsGlobales();

        $this->view('reports/index', [
            'statsVentes' => $statsVentes,
            'topProduits' => $topProduits,
            'ventesParZone' => $ventesParZone,
            'statsEmballages' => $statsEmballages,
            'statsDettes' => $statsDettes,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }

    public function ventesParAgent()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $this->view('reports/ventes_par_agent', $this->getVentesParAgentReportData());
    }
    private function styleHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $nbCols): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbCols);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
        ]);
        foreach (range(1, $nbCols) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    // Helper pour envoyer le fichier xlsx au navigateur
    private function sendXlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportVentesParAgent()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $data = $this->getVentesParAgentReportData();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Ventes par agent');

        // Résumé
        $sheet->fromArray(['Rapport ventes par agent'], null, 'A1');
        $sheet->fromArray(['Periode', $data['dateDebut'] . ' au ' . $data['dateFin']], null, 'A2');
        $sheet->fromArray(['Total ventes',    $data['totalVentes']], null, 'A3');
        $sheet->fromArray(['Agents concernes',$data['nbAgents']],    null, 'A4');
        $sheet->fromArray(['Total caisses',   (float) $data['totalCaisses']], null, 'A5');
        $sheet->fromArray(['CA total',        (float) $data['totalCa']],     null, 'A6');

        // En-têtes
        $headers = ['Agent', 'Role', 'Date', 'Facture', 'Client', 'Emplacement', 'Caisses', 'Total TTC'];
        $sheet->fromArray($headers, null, 'A8');
        $this->styleHeaderRow($sheet, count($headers));  // style ligne 1, on va re-styler ligne 8

        // Re-style header sur ligne 8 (styleHeaderRow cible ligne 1 — on duplique ici)
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A8:' . $lastCol . '8')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);

        $row = 9;
        foreach ($data['ventesParAgent'] as $agent) {
            foreach ($agent['ventes'] as $vente) {
                $sheet->fromArray([
                    $agent['agent_nom'],
                    $agent['agent_role'],
                    !empty($vente['date_vente']) ? date('d/m/Y H:i', strtotime($vente['date_vente'])) : '',
                    $vente['numero_facture'] ?? '',
                    $vente['client_nom'] ?? 'N/A',
                    $vente['emplacement_nom'] ?? 'N/A',
                    (float)($vente['total_caisses'] ?? 0),
                    (float)($vente['total_ttc'] ?? 0),
                ], null, 'A' . $row++);
            }

            // Ligne sous-total en gras
            $sheet->fromArray([
                'Sous-total ' . $agent['agent_nom'],
                '', '', '', '', '',
                (float)($agent['total_caisses'] ?? 0),
                (float)($agent['total_ca'] ?? 0),
            ], null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
            $row += 2; // +1 ligne vide
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'ventes_par_agent_' . $data['dateDebut'] . '_' . $data['dateFin'] . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }



    private function getVentesParAgentReportData()
    {
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-d');
        $dateFin = $_GET['date_fin'] ?? $dateDebut;

        $ventes = $this->venteModel->getVentesParAgent($dateDebut, $dateFin);

        $ventesParAgent = [];
        $totalCa = 0;
        $totalVentes = 0;
        $totalCaisses = 0;

        foreach ($ventes as $vente) {
            $agentId = $vente['created_by'] ?? 0;
            $agentNom = trim(($vente['agent_prenom'] ?? '') . ' ' . ($vente['agent_nom'] ?? ''));
            if ($agentNom === '') {
                $agentNom = 'Systeme';
            }

            if (!isset($ventesParAgent[$agentId])) {
                $ventesParAgent[$agentId] = [
                    'agent_id' => $agentId,
                    'agent_nom' => $agentNom,
                    'agent_role' => $vente['agent_role'] ?? '',
                    'ventes' => [],
                    'total_ca' => 0,
                    'total_caisses' => 0,
                ];
            }

            $ventesParAgent[$agentId]['ventes'][] = $vente;
            $ventesParAgent[$agentId]['total_ca'] += (float) ($vente['total_ttc'] ?? 0);
            $ventesParAgent[$agentId]['total_caisses'] += (float) ($vente['total_caisses'] ?? 0);
            $totalCa += (float) ($vente['total_ttc'] ?? 0);
            $totalCaisses += (float) ($vente['total_caisses'] ?? 0);
            $totalVentes++;
        }

        $ventesParAgent = array_values($ventesParAgent);

        return [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'ventesParAgent' => $ventesParAgent,
            'totalCa' => $totalCa,
            'totalVentes' => $totalVentes,
            'totalCaisses' => $totalCaisses,
            'nbAgents' => count($ventesParAgent),
        ];
    }
}
