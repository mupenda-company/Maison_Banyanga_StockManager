<?php
/**
 * Contrôleur des retours d'emballages
 */

class RetourController extends Controller
{
    private $retourModel;
    private $clientModel;
    private $produitModel;
    private $emplacementModel;

    public function __construct()
    {
        parent::__construct();
        $this->retourModel = new RetourEmballage();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
    }

    /**
     * Liste des retours
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('emballages.manage');
        
        $retours = $this->retourModel->getRecents();
        $clients = $this->clientModel->all('nom');
        $produits = $this->produitModel->getActive();
        $emplacements = $this->emplacementModel->getFixes();

        $this->view('retours/index', [
            'retours' => $retours,
            'clients' => $clients,
            'produits' => $produits,
            'emplacements' => $emplacements
        ]);
    }

    /**
     * API : Enregistrer un retour
     */
    public function store()
    {
        $this->requireAuth();
        $this->requirePermission('emballages.manage');
        $data = $this->getJsonInput();

        if (empty($data['client_id']) || empty($data['produit_id']) || empty($data['quantite'])) {
            return $this->error('Données incomplètes');
        }

        $data['created_by'] = $_SESSION['user_id'];
        $data['emplacement_id'] = $data['emplacement_id'] ?? 1; // Par défaut Entrepôt Principal

        $result = $this->retourModel->enregistrer($data);

        if ($result['success']) {
            return $this->success(null, 'Retour enregistré avec succès');
        }

        return $this->error($result['message']);
    }
}
