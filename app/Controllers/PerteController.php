<?php
/**
 * Contrôleur des pertes
 */

class PerteController extends Controller
{
    private $perteModel;
    private $produitModel;
    private $emplacementModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->perteModel = new Perte();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
    }
    
    /**
     * Liste des pertes
     */
    public function index()
    {
        $this->requirePermission('pertes.view');
        
        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
            'type_perte' => $_GET['type'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null
        ];
        
        $pertes = $this->perteModel->getAllWithDetails($filters);
        $produits = $this->produitModel->getActive();
        $emplacements = $this->emplacementModel->all('type, nom');
        
        // Stats du mois
        $stats = $this->perteModel->getStats(date('Y-m-01'), date('Y-m-d'));
        
        $this->view('pertes/index', [
            'pertes' => $pertes,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'filters' => $filters,
            'stats' => $stats
        ]);
    }
    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requirePermission('pertes.create');
        
        $produits = $this->produitModel->getWithStock();
        $emplacements = $this->emplacementModel->all('type, nom');
        
        $this->view('pertes/create', [
            'produits' => $produits,
            'emplacements' => $emplacements
        ]);
    }
    
    /**
     * Enregistrer une perte
     */
    public function store()
    {
        $this->requirePermission('pertes.create');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'produit_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'quantite' => 'required|numeric',
            'type_perte' => 'required',
            'date_perte' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        // Le calcul de la valeur et la conversion sont gérés dans le modèle createWithStockUpdate
        $result = $this->perteModel->createWithStockUpdate([
            'produit_id' => $data['produit_id'],
            'emplacement_id' => $data['emplacement_id'],
            'quantite' => $data['quantite'],
            'type_perte' => $data['type_perte'],
            'type_stock' => $data['type_stock'] ?? 'plein',
            'motif' => $data['motif'] ?? '',
            'date_perte' => $data['date_perte'],
            'valeur_perte' => $data['valeur_perte'] ?? 0,
            'created_by' => $_SESSION['user_id']
        ]);
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Perte enregistrée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Supprimer une perte (API)
     */
    public function delete($id)
    {
        $this->requirePermission('pertes.view');
        
        $result = $this->perteModel->supprimer($id);
        
        if ($result['success']) {
            return $this->success(null, 'Perte supprimée et stock restauré');
        }
        
        return $this->error($result['message']);
    }
    
    /**
     * Statistiques des pertes
     */
    public function stats()
    {
        $this->requirePermission('pertes.view');
        
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        
        $stats = $this->perteModel->getStats($dateDebut, $dateFin);
        $byType = $this->perteModel->getByType($dateDebut, $dateFin);
        
        $this->view('pertes/stats', [
            'stats' => $stats,
            'byType' => $byType,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }
}
