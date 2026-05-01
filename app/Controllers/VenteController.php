<?php
/**
 * Contrôleur des ventes
 */

class VenteController extends Controller
{
    private $venteModel;
    private $clientModel;
    private $produitModel;
    private $emplacementModel;
    private $parametreModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->venteModel = new Vente();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
        $this->parametreModel = new Parametre();
    }
    
    /**
     * Liste des ventes
     */
    public function index()
    {
        $this->requireAuth();
        
        $filters = [
            'client_id' => $_GET['client_id'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null
        ];
        
        $page = (int) ($_GET['page'] ?? 1);
        $ventes = $this->venteModel->getAllWithClient($page, 20, $filters);
        
        $clients = $this->clientModel->getAllWithZone();
        $emplacements = $this->emplacementModel->all('type, nom');
        
        $this->view('ventes/index', [
            'ventes' => $ventes,
            'clients' => $clients,
            'emplacements' => $emplacements,
            'filters' => $filters
        ]);
    }
    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requireAuth();
        
        $clients = $this->clientModel->getAllWithZone();
        $produits = $this->produitModel->getWithStock();
        $emplacements = $this->emplacementModel->getFixes();
        $tva = $this->parametreModel->get('taux_tva', 16);
        
        $this->view('ventes/create', [
            'clients' => $clients,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'tva' => $tva,
            'numero_facture' => $this->venteModel->generateNumeroFacture()
        ]);
    }
    
    /**
     * Enregistrer une vente
     */
    public function store()
    {
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'client_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'details' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $tva = $this->parametreModel->get('taux_tva', 16);
        
        $totalHt = 0;
        $details = [];
        
        foreach ($data['details'] as $detail) {
            $produit = $this->produitModel->find($detail['produit_id']);
            $prixUnitaire = $detail['prix_unitaire'] ?? $produit['prix_vente_unitaire'];
            $sousTotal = $detail['quantite'] * $prixUnitaire;
            $totalHt += $sousTotal;
            
            $details[] = [
                'produit_id' => $detail['produit_id'],
                'quantite' => $detail['quantite'],
                'prix_unitaire' => $prixUnitaire,
                'sous_total' => $sousTotal
            ];
        }
        
        $totalTva = $totalHt * ($tva / 100);
        $totalTtc = $totalHt + $totalTva;
        
        $venteData = [
            'numero_facture' => $this->venteModel->generateNumeroFacture(),
            'client_id' => $data['client_id'],
            'date_vente' => date('Y-m-d H:i:s'),
            'emplacement_id' => $data['emplacement_id'],
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
            'statut' => 'validee',
            'notes' => $data['notes'] ?? '',
            'created_by' => $_SESSION['user_id']
        ];
        
        $result = $this->venteModel->createWithDetails($venteData, $details);
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Vente enregistrée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Afficher une vente
     */
    public function show($id)
    {
        $this->requireAuth();
        
        $vente = $this->venteModel->getWithDetails($id);
        
        if (!$vente) {
            return $this->error('Vente non trouvée', 404);
        }
        
        $params = $this->parametreModel->getPersonnalisation();
        
        if ($this->isAjax()) {
            return $this->success([
                'vente' => $vente,
                'params' => $params
            ]);
        }
        
        $this->view('ventes/show', [
            'vente' => $vente,
            'params' => $params
        ]);
    }
    
    /**
     * Annuler une vente
     */
    public function annuler($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $result = $this->venteModel->annuler($id);
        
        if ($result['success']) {
            return $this->success(null, 'Vente annulée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Imprimer la facture
     */
    public function print($id)
    {
        $this->requireAuth();
        
        $vente = $this->venteModel->getWithDetails($id);
        
        if (!$vente) {
            return $this->error('Vente non trouvée', 404);
        }
        
        $params = $this->parametreModel->getPersonnalisation();

        $totalCaissesClient = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(vd.quantite / p.bouteilles_par_caisses), 0)
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.client_id = :client_id AND v.statut = 'validee'",
            ['client_id' => $vente['client_id']]
        );

        $mois = (int) date('m', strtotime($vente['date_vente']));
        $annee = (int) date('Y', strtotime($vente['date_vente']));
        $ristourneInfo = (new Ristourne())->calculerRistourne($vente['client_id'], $mois, $annee);
        
        $this->view('ventes/facture', [
            'vente' => $vente,
            'params' => $params,
            'totalCaissesClient' => $totalCaissesClient,
            'ristourneInfo' => $ristourneInfo
        ]);
    }
    
    /**
     * Statistiques de ventes
     */
    public function stats()
    {
        $this->requireAuth();
        
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        
        $stats = $this->venteModel->getStats($dateDebut . ' 00:00:00', $dateFin . ' 23:59:59');
        $ventesParProduit = $this->venteModel->getVentesParProduit($dateDebut, $dateFin);
        
        $this->view('ventes/stats', [
            'stats' => $stats,
            'ventesParProduit' => $ventesParProduit,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }
}
