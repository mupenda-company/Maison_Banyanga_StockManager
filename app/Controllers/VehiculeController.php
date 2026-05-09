<?php
/**
 * Contrôleur des véhicules
 */

class VehiculeController extends Controller
{
    private $vehiculeModel;
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->vehiculeModel = new Vehicule();
        $this->userModel = new User();
    }
    
    /**
     * Liste des véhicules
     */
    public function index()
    {
        $this->requireAuth();
        
        $vehicules = $this->vehiculeModel->getWithAgent();
        $agents = $this->userModel->getByRole(ROLE_VENDEUR);
        
        $this->view('vehicules/index', [
            'vehicules' => $vehicules,
            'agents' => $agents
        ]);
    }
    
    /**
     * API liste des véhicules
     */
    public function apiList()
    {
        $this->requireAuth();
        
        $disponibles = isset($_GET['disponibles']) && $_GET['disponibles'] === 'true';
        
        if ($disponibles) {
            $vehicules = $this->vehiculeModel->getDisponibles();
        } else {
            $vehicules = $this->vehiculeModel->getWithAgent();
        }
        
        return $this->success($vehicules);
    }

    /**
     * API détail d'un véhicule avec stock
     */
    public function apiShow($id)
    {
        $this->requireAuth();

        $vehicule = $this->vehiculeModel->getWithStock($id);

        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }

        return $this->success($vehicule);
    }
    
    /**
     * Afficher un véhicule
     */
    public function show($id)
    {
        $this->requireAuth();
        
        $vehicule = $this->vehiculeModel->getWithStock($id);
        
        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }
        
        // Missions du véhicule
        $missions = $this->db->fetchAll(
            "SELECT m.*, z.nom as zone_nom
             FROM missions m
             LEFT JOIN zones z ON m.zone_id = z.id
             WHERE m.vehicule_id = :vehicule_id
             ORDER BY m.date_depart DESC
             LIMIT 10",
            ['vehicule_id' => $id]
        );
        
        // Stats du mois
        $stats = $this->db->fetch(
            "SELECT COUNT(DISTINCT m.id) as nb_missions, 
                    COALESCE(SUM(COALESCE(vd.quantite_caisses, CASE WHEN p.bouteilles_par_caisses > 0 THEN FLOOR(vd.quantite / p.bouteilles_par_caisses) ELSE vd.quantite END)), 0) as total_livre,
                    COALESCE(SUM(vd.quantite * vd.prix_unitaire), 0) as total_ca
             FROM missions m
             LEFT JOIN ventes v ON m.id = v.mission_id
             LEFT JOIN vente_details vd ON v.id = vd.vente_id
             LEFT JOIN produits p ON vd.produit_id = p.id
             WHERE m.vehicule_id = :vehicule_id 
             AND m.statut = 'terminee'
             AND v.statut = 'validee'
             AND MONTH(m.date_depart) = MONTH(CURRENT_DATE)
             AND YEAR(m.date_depart) = YEAR(CURRENT_DATE)",
            ['vehicule_id' => $id]
        );
        
        if ($this->isAjax()) {
            return $this->success([
                'vehicule' => $vehicule,
                'missions' => $missions,
                'stats' => $stats
            ]);
        }
        
        $this->view('vehicules/show', [
            'vehicule' => $vehicule,
            'missions' => $missions,
            'stats' => $stats ?: ['nb_missions' => 0, 'total_livre' => 0, 'total_ca' => 0]
        ]);
    }
    
    /**
     * Créer un véhicule
     */
    public function store()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'immatriculation' => 'required',
            'agent_responsable_id' => 'required|numeric'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        if ($this->vehiculeModel->immatriculationExists($data['immatriculation'])) {
            return $this->error('Cette immatriculation existe déjà', 422);
        }

        if ($this->vehiculeModel->agentHasVehicule($data['agent_responsable_id'])) {
            return $this->error('Cet agent est déjà responsable d\'un autre véhicule', 422);
        }
        
        $result = $this->vehiculeModel->createWithEmplacement([
            'immatriculation' => $data['immatriculation'],
            'marque' => $data['marque'] ?? null,
            'modele' => $data['modele'] ?? null,
            'agent_responsable_id' => $data['agent_responsable_id'],
            'capacite' => $data['capacite'] ?? 0,
            'actif' => 1
        ]);
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Véhicule créé avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Mettre à jour un véhicule
     */
    public function update($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $vehicule = $this->vehiculeModel->find($id);
        
        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }
        
        $data = $this->getJsonInput();
        
        // Vérifier l'immatriculation unique si modifiée
        if (isset($data['immatriculation']) && $data['immatriculation'] !== $vehicule['immatriculation']) {
            if ($this->vehiculeModel->immatriculationExists($data['immatriculation'], $id)) {
                return $this->error('Cette immatriculation existe déjà', 422);
            }
        }

        // Vérifier si le nouvel agent est déjà pris
        if (isset($data['agent_responsable_id']) && $data['agent_responsable_id'] != $vehicule['agent_responsable_id']) {
            if ($this->vehiculeModel->agentHasVehicule($data['agent_responsable_id'], $id)) {
                return $this->error('Cet agent est déjà responsable d\'un autre véhicule', 422);
            }
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'immatriculation', 'marque', 'modele', 'agent_responsable_id', 'capacite'
        ]));
        
        $this->vehiculeModel->update($id, $updateData);
        
        return $this->success(null, 'Véhicule mis à jour avec succès');
    }
    
    /**
     * Supprimer un véhicule (désactiver)
     */
    public function delete($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $vehicule = $this->vehiculeModel->find($id);
        
        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }
        
        // Vérifier s'il y a une mission en cours
        $missionEnCours = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM missions WHERE vehicule_id = :id AND statut = 'en_cours'",
            ['id' => $id]
        );
        
        if ($missionEnCours > 0) {
            return $this->error('Impossible de désactiver ce véhicule car il est en mission', 400);
        }
        
        $this->vehiculeModel->update($id, ['actif' => 0]);
        
        return $this->success(null, 'Véhicule désactivé avec succès');
    }
}
