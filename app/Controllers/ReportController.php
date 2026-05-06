<?php
/**
 * Contrôleur des Rapports et Statistiques
 */

class ReportController extends Controller
{
    private $venteModel;
    private $produitModel;
    private $clientModel;
<<<<<<< HEAD
    private $retourModel;
    private $detteModel;
=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13

    public function __construct()
    {
        parent::__construct();
        $this->venteModel = new Vente();
        $this->produitModel = new Produit();
        $this->clientModel = new Client();
<<<<<<< HEAD
        $this->retourModel = new RetourEmballage();
        $this->detteModel = new DetteEmballage();
=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
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
<<<<<<< HEAD
        $statsEmballages = $this->retourModel->getStats($dateDebut, $dateFin, 5);
        $statsDettes = $this->detteModel->getStatsGlobales();
=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13

        $this->view('reports/index', [
            'statsVentes' => $statsVentes,
            'topProduits' => $topProduits,
            'ventesParZone' => $ventesParZone,
<<<<<<< HEAD
            'statsEmballages' => $statsEmballages,
            'statsDettes' => $statsDettes,
=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }
}
