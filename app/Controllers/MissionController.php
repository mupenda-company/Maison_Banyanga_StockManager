<?php
/**
 * Contrôleur des missions
 */

class MissionController extends Controller
{
    private $missionModel;
    private $vehiculeModel;
    private $produitModel;
    private $zoneModel;
    private $emplacementModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->missionModel = new Mission();
        $this->vehiculeModel = new Vehicule();
        $this->produitModel = new Produit();
        $this->zoneModel = new Zone();
        $this->emplacementModel = new Emplacement();
    }
    
    /**
     * Liste des missions
     */
    public function index()
    {
        $this->requireAuth();
        
        $filters = [
            'statut' => $_GET['statut'] ?? null,
            'vehicule_id' => $_GET['vehicule_id'] ?? null
        ];
        
        $page = (int) ($_GET['page'] ?? 1);
        
        $missions = $this->db->fetchAll(
            "SELECT m.*, v.immatriculation, u.nom as agent_nom, u.prenom as agent_prenom, z.nom as zone_nom,
<<<<<<< HEAD
                    COALESCE((SELECT SUM(COALESCE(mc.quantite_caisses, FLOOR(mc.quantite_chargee / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24))))
=======
                    COALESCE((SELECT SUM(ROUND(mc.quantite_chargee / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                              FROM mission_chargements mc
                              JOIN produits p ON mc.produit_id = p.id
                              WHERE mc.mission_id = m.id), 0) as total_caisses,
                    COALESCE((SELECT COUNT(DISTINCT v2.client_id)
                              FROM ventes v2
                              WHERE v2.mission_id = m.id AND v2.statut = 'validee'), 0) as nb_clients
             FROM missions m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN zones z ON m.zone_id = z.id
             ORDER BY m.date_depart DESC
             LIMIT 20 OFFSET " . (($page - 1) * 20)
        );
        
        $vehicules = $this->vehiculeModel->getWithAgent();
        
        $this->view('missions/index', [
            'missions' => $missions,
            'vehicules' => $vehicules,
            'filters' => $filters
        ]);
    }
    
    /**
     * Missions en cours
     */
    public function enCours()
    {
        $this->requireAuth();
        
        $missions = $this->missionModel->getEnCours();
        
        if ($this->isAjax()) {
            return $this->success($missions);
        }
        
        $this->view('missions/en-cours', [
            'missions' => $missions
        ]);
    }
    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $vehicules = $this->vehiculeModel->getDisponibles();
        $produits = $this->produitModel->getWithStock();
        $zones = $this->zoneModel->getActive();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        
        $this->view('missions/create', [
            'vehicules' => $vehicules,
            'produits' => $produits,
            'zones' => $zones,
            'emplacementPrincipal' => $emplacementPrincipal,
            'numero_mission' => $this->missionModel->generateNumeroMission()
        ]);
    }
    
    /**
     * Enregistrer une mission
     */
    public function store()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'date_depart' => 'required',
            'chargements' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        
        $missionData = [
            'numero_mission' => $this->missionModel->generateNumeroMission(),
            'vehicule_id' => $data['vehicule_id'],
            'chauffeur_id' => $data['chauffeur_id'] ?? null,
            'date_depart' => $data['date_depart'],
            'zone_id' => $data['zone_id'] ?? null,
            'notes' => $data['notes'] ?? '',
            'statut' => 'en_cours',
            'created_by' => $_SESSION['user_id']
        ];
        
        $chargements = [];
        foreach ($data['chargements'] as $chargement) {
<<<<<<< HEAD
            $quantiteCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
            $quantiteBouteilles = (int) ($chargement['quantite'] ?? 0);
            $chargements[] = [
                'produit_id' => $chargement['produit_id'],
                'quantite_caisses' => $quantiteCaisses,
                'quantite_chargee' => $quantiteBouteilles
=======
            $chargements[] = [
                'produit_id' => $chargement['produit_id'],
                'quantite_chargee' => $chargement['quantite']
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
            ];
        }
        
        $result = $this->missionModel->createWithChargement(
            $missionData,
            $chargements,
            $emplacementPrincipal['id']
        );
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Mission créée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Afficher une mission
     */
    public function show($id)
    {
        $this->requireAuth();
        
        $mission = $this->missionModel->getWithDetails($id);
        
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }
        
        if ($this->isAjax()) {
            return $this->success($mission);
        }
        
        $this->view('missions/show', [
            'mission' => $mission
        ]);
    }
    
    /**
     * Terminer une mission
     */
    public function terminer($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $data = $this->getJsonInput();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $mission = $this->missionModel->getWithDetails($id);

        if (empty($data) || (!isset($data['retours']) && !isset($data['vides_retournes']) && !isset($data['montant_encaisse']))) {
            return $this->error('Veuillez enregistrer les retours (pleins/vides) et le montant encaissé avant de clôturer la mission', 422);
        }

        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }
        
        // Préparer les retours de produits pleins (invendus)
        $retours = [];
        if (isset($data['retours'])) {
            foreach ($data['retours'] as $produitId => $quantite) {
                $retours[$produitId] = (int) $quantite;
            }
        }
        
        // Préparer les retours de caisses vides
        $vides_retournes = [];
        if (isset($data['vides_retournes'])) {
            foreach ($data['vides_retournes'] as $produitId => $nbCaisses) {
                $vides_retournes[$produitId] = (int) $nbCaisses;
            }
        }

        $montant_attendu = (float) ($mission['montant_attendu'] ?? 0);
        $montant_encaisse = isset($data['montant_encaisse']) ? floatval($data['montant_encaisse']) : 0;
        if ($montant_encaisse <= 0 && $montant_attendu > 0) {
            $montant_encaisse = $montant_attendu;
        }
        
        $result = $this->missionModel->terminer(
            $id, 
            $retours, 
            $vides_retournes, 
            $montant_encaisse, 
            $emplacementPrincipal['id']
        );
        
        if ($result['success']) {
            return $this->success(null, 'Mission terminée et stock mis à jour avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Imprimer le bon de sortie
     */
    public function print($id)
    {
        $this->requireAuth();
        
        $mission = $this->missionModel->getWithDetails($id);
        $params = (new Parametre())->getPersonnalisation();
        
        $this->view('missions/bon-sortie', [
            'mission' => $mission,
            'params' => $params
        ]);
    }
}
