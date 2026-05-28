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

    public function exportVentesParAgent()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $data = $this->getVentesParAgentReportData();
        $filename = 'ventes_par_agent_' . $data['dateDebut'] . '_' . $data['dateFin'] . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Rapport ventes par agent']);
        fputcsv($output, ['Periode', $data['dateDebut'] . ' au ' . $data['dateFin']]);
        fputcsv($output, ['Total ventes', $data['totalVentes']]);
        fputcsv($output, ['Agents concernes', $data['nbAgents']]);
        fputcsv($output, ['Total caisses', number_format((float)$data['totalCaisses'], 0, '.', '')]);
        fputcsv($output, ['CA total', number_format((float)$data['totalCa'], 2, '.', '')]);
        fputcsv($output, []);

        fputcsv($output, ['Agent', 'Role', 'Date', 'Facture', 'Client', 'Emplacement', 'Caisses', 'Total TTC']);
        foreach ($data['ventesParAgent'] as $agent) {
            foreach ($agent['ventes'] as $vente) {
                fputcsv($output, [
                    $agent['agent_nom'],
                    $agent['agent_role'],
                    !empty($vente['date_vente']) ? date('d/m/Y H:i', strtotime($vente['date_vente'])) : '',
                    $vente['numero_facture'] ?? '',
                    $vente['client_nom'] ?? 'N/A',
                    $vente['emplacement_nom'] ?? 'N/A',
                    number_format((float)($vente['total_caisses'] ?? 0), 0, '.', ''),
                    number_format((float)($vente['total_ttc'] ?? 0), 2, '.', ''),
                ]);
            }

            fputcsv($output, [
                'Sous-total ' . $agent['agent_nom'],
                '',
                '',
                '',
                '',
                '',
                number_format((float)($agent['total_caisses'] ?? 0), 0, '.', ''),
                number_format((float)($agent['total_ca'] ?? 0), 2, '.', ''),
            ]);
            fputcsv($output, []);
        }

        fclose($output);
        exit;
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
