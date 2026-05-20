<?php
/**
 * Contrôleur des Rapports et Statistiques
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
        $this->requirePermission('rapports.view');
        
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        // Statistiques globales
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
        $this->requirePermission('rapports.view');

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-d');
        $dateFin = $_GET['date_fin'] ?? $dateDebut;

        $ventes = $this->venteModel->getVentesParAgent($dateDebut, $dateFin);

        $ventesParAgent = [];
        $totalCa = 0;
        $totalVentes = 0;

        foreach ($ventes as $vente) {
            $agentId = $vente['created_by'] ?? 0;
            $agentNom = trim(($vente['agent_prenom'] ?? '') . ' ' . ($vente['agent_nom'] ?? ''));
            if ($agentNom === '') {
                $agentNom = 'Système';
            }

            if (!isset($ventesParAgent[$agentId])) {
                $ventesParAgent[$agentId] = [
                    'agent_id' => $agentId,
                    'agent_nom' => $agentNom,
                    'agent_role' => $vente['agent_role'] ?? '',
                    'ventes' => [],
                    'total_ca' => 0,
                ];
            }

            $ventesParAgent[$agentId]['ventes'][] = $vente;
            $ventesParAgent[$agentId]['total_ca'] += (float) ($vente['total_ttc'] ?? 0);
            $totalCa += (float) ($vente['total_ttc'] ?? 0);
            $totalVentes++;
        }

        $ventesParAgent = array_values($ventesParAgent);

        $this->view('reports/ventes_par_agent', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'ventesParAgent' => $ventesParAgent,
            'totalCa' => $totalCa,
            'totalVentes' => $totalVentes,
            'nbAgents' => count($ventesParAgent),
        ]);
    }
}
