<?php
/**
 * Contrôleur du tableau de bord
 */

class DashboardController extends Controller
{
    private $produitModel;
    private $stockModel;
    private $venteModel;
    private $alerteModel;
    private $missionModel;
    private $perteModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->produitModel = new Produit();
        $this->stockModel = new Stock();
        $this->venteModel = new Vente();
        $this->alerteModel = new Alerte();
        $this->missionModel = new Mission();
        $this->perteModel = new Perte();
    }
    
    /**
     * Afficher le tableau de bord
     */
    public function index()
    {
        $this->requireAuth();
        
        // Vérifier et générer les alertes
        $this->alerteModel->checkStockAlerts();
        
        // Statistiques du jour
        $today = date('Y-m-d');
        $statsToday = $this->venteModel->getStats($today . ' 00:00:00', $today . ' 23:59:59');
        
        // Statistiques du mois
        $firstDayMonth = date('Y-m-01');
        $statsMonth = $this->venteModel->getStats($firstDayMonth . ' 00:00:00', date('Y-m-d H:i:s'));
        
        // Produits en alerte
        $produitsAlerte = array_slice($this->produitModel->getAlertProducts(), 0, 5);
        
        // Alertes non lues
        $alertes = $this->alerteModel->getNonLues(5);
        $nbAlertes = $this->alerteModel->countNonLues();
        
        // Missions en cours
        $missionsEnCours = array_slice($this->missionModel->getEnCours(), 0, 5);
        
        // Derniers mouvements de stock
        $mouvementModel = new MouvementStock();
        $derniersMouvements = $mouvementModel->getDerniers(5);
        
        // Ventes par produit (top 5 du mois)
        $ventesParProduit = $this->venteModel->getVentesParProduit($firstDayMonth, date('Y-m-d'));
        
        // Pertes du mois
        $pertesStats = $this->perteModel->getStats($firstDayMonth, date('Y-m-d'));
        
        $this->view('dashboard/index', [
            'statsToday' => $statsToday,
            'statsMonth' => $statsMonth,
            'produitsAlerte' => $produitsAlerte,
            'alertes' => $alertes,
            'nbAlertes' => $nbAlertes,
            'missionsEnCours' => $missionsEnCours,
            'derniersMouvements' => $derniersMouvements,
            'ventesParProduit' => $ventesParProduit,
            'pertesStats' => $pertesStats
        ]);
    }
    
    /**
     * API pour les données du dashboard
     */
    public function apiStats()
    {
        $this->requireAuth();
        
        $period = $_GET['period'] ?? 'today';
        
        switch ($period) {
            case 'today':
                $debut = date('Y-m-d') . ' 00:00:00';
                $fin = date('Y-m-d') . ' 23:59:59';
                break;
            case 'week':
                $debut = date('Y-m-d', strtotime('monday this week')) . ' 00:00:00';
                $fin = date('Y-m-d H:i:s');
                break;
            case 'month':
                $debut = date('Y-m-01') . ' 00:00:00';
                $fin = date('Y-m-d H:i:s');
                break;
            case 'year':
                $debut = date('Y-01-01') . ' 00:00:00';
                $fin = date('Y-m-d H:i:s');
                break;
            default:
                $debut = date('Y-m-d') . ' 00:00:00';
                $fin = date('Y-m-d') . ' 23:59:59';
        }
        
        $stats = $this->venteModel->getStats($debut, $fin);
        $ventesParProduit = $this->venteModel->getVentesParProduit($debut, $fin);
        
        return $this->success([
            'stats' => $stats,
            'ventesParProduit' => $ventesParProduit
        ]);
    }
    
    /**
     * API pour les alertes
     */
    public function apiAlertes()
    {
        $this->requireAuth();
        
        // On ne récupère que les alertes non lues ET non résolues
        $alertes = $this->alerteModel->getNonLues(20);
        $nbAlertes = $this->alerteModel->countNonLues();
        
        return $this->success([
            'alertes' => $alertes,
            'count' => $nbAlertes
        ]);
    }
    
    /**
     * Marquer les alertes comme lues
     */
    public function markAlertsRead()
    {
        $this->requireAuth();
        
        $this->alerteModel->marquerToutesLues();
        
        return $this->success(null, 'Alertes marquées comme lues');
    }
}
