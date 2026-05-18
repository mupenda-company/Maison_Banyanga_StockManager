<?php
/**
 * Contrôleur des dépenses
 */

class DepenseController extends Controller
{
    private $depenseModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->depenseModel = new Depense();
    }
    
    /**
     * Liste des dépenses
     */
    public function index()
    {
        $this->requireAuth();
        $this->requireRole([ROLE_ADMIN]);
        
        $filters = [
            'categorie' => $_GET['categorie'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? date('Y-m-01'),
            'date_fin' => $_GET['date_fin'] ?? date('Y-m-d')
        ];
        
        $depenses = $this->depenseModel->getAllWithFilters($filters);
        $stats = $this->depenseModel->getStats($filters['date_debut'], $filters['date_fin']);
        $parCategorie = $this->depenseModel->getByCategorie($filters['date_debut'], $filters['date_fin']);
        
        $this->view('depenses/index', [
            'depenses' => $depenses,
            'filters' => $filters,
            'stats' => $stats,
            'parCategorie' => $parCategorie
        ]);
    }
    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $this->view('depenses/create');
    }
    
    /**
     * Enregistrer une dépense
     */
    public function store()
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'categorie' => 'required',
            'description' => 'required',
            'montant' => 'required|numeric',
            'date_depense' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Données invalides', 422, $errors);
        }
        
        $data['created_by'] = $_SESSION['user_id'] ?? null;
        
        $id = $this->depenseModel->create($data);
        
        return $this->success(['id' => $id], 'Dépense enregistrée avec succès');
    }
    
    /**
     * Supprimer une dépense
     */
    public function delete($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $depense = $this->depenseModel->find($id);
        if (!$depense) {
            return $this->error('Dépense non trouvée', 404);
        }
        
        $this->depenseModel->delete($id);
        
        return $this->success(null, 'Dépense supprimée');
    }
}
