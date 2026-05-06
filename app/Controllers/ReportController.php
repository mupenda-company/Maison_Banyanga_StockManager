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
}
