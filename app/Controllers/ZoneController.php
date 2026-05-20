<?php
/**
 * Contrôleur des zones
 */

class ZoneController extends Controller
{
    private $zoneModel;
    private $clientModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->zoneModel = new Zone();
        $this->clientModel = new Client();
    }
    
    /**
     * Liste des zones
     */
    public function index()
    {
        $this->requirePermission('clients.view');
        
        $zones = $this->zoneModel->getWithStats();
        
        $this->view('zones/index', [
            'zones' => $zones
        ]);
    }
    
    /**
     * Afficher une zone
     */
    public function show($id)
    {
        $this->requirePermission('clients.view');
        
        $zone = $this->zoneModel->find($id);
        
        if (!$zone) {
            return $this->error('Zone non trouvée', 404);
        }
        
        // Clients de la zone
        $clients = $this->clientModel->getByZoneWithStats($id);
        
        // Dernières ventes de la zone
        $ventes = $this->db->fetchAll(
            "SELECT v.*, c.nom as client_nom
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             WHERE c.zone_id = :zone_id
             ORDER BY v.date_vente DESC
             LIMIT 10",
            ['zone_id' => $id]
        );
        
        // Stats du mois
        $stats = $this->db->fetch(
            "SELECT COUNT(DISTINCT v.id) as nb_ventes,
                    COALESCE(SUM(v.total_ttc), 0) as ca_mois,
                    COALESCE(SUM(vd.quantite), 0) as total_bouteilles
             FROM ventes v
             JOIN vente_details vd ON v.id = vd.vente_id
             JOIN clients c ON v.client_id = c.id
             WHERE c.zone_id = :zone_id
             AND MONTH(v.date_vente) = MONTH(CURRENT_DATE)
             AND YEAR(v.date_vente) = YEAR(CURRENT_DATE)",
            ['zone_id' => $id]
        );
        
        // Top clients du mois
        $topClients = $this->db->fetchAll(
            "SELECT c.nom, SUM(v.total_ttc) as ca
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             WHERE c.zone_id = :zone_id
             AND MONTH(v.date_vente) = MONTH(CURRENT_DATE)
             AND YEAR(v.date_vente) = YEAR(CURRENT_DATE)
             GROUP BY c.id
             ORDER BY ca DESC
             LIMIT 5",
            ['zone_id' => $id]
        );
        
        if ($this->isAjax()) {
            return $this->success([
                'zone' => $zone,
                'clients' => $clients,
                'ventes' => $ventes,
                'stats' => $stats,
                'topClients' => $topClients
            ]);
        }
        
        $this->view('zones/show', [
            'zone' => $zone,
            'clients' => $clients,
            'ventes' => $ventes,
            'stats' => $stats ?: ['ca_mois' => 0, 'nb_ventes' => 0, 'total_bouteilles' => 0],
            'topClients' => $topClients
        ]);
    }
    
    /**
     * Créer une zone
     */
    public function store()
    {
        $this->requirePermission('clients.create');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'nom' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $id = $this->zoneModel->create([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'actif' => 1
        ]);
        
        return $this->success(['id' => $id], 'Zone créée avec succès');
    }
    
    /**
     * Mettre à jour une zone
     */
    public function update($id)
    {
        $this->requirePermission('clients.create');
        
        $zone = $this->zoneModel->find($id);
        
        if (!$zone) {
            return $this->error('Zone non trouvée', 404);
        }
        
        $data = $this->getJsonInput();
        
        $updateData = array_intersect_key($data, array_flip(['nom', 'description']));
        
        $this->zoneModel->update($id, $updateData);
        
        return $this->success(null, 'Zone mise à jour avec succès');
    }
    
    /**
     * Supprimer une zone (désactiver)
     */
    public function delete($id)
    {
        $this->requirePermission('clients.delete');
        
        $zone = $this->zoneModel->find($id);
        
        if (!$zone) {
            return $this->error('Zone non trouvée', 404);
        }
        
        // Vérifier s'il y a des clients dans cette zone
        $nbClients = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM clients WHERE zone_id = :id",
            ['id' => $id]
        );
        
        if ($nbClients > 0) {
            return $this->error('Impossible de désactiver cette zone car elle contient des clients', 400);
        }
        
        $this->zoneModel->update($id, ['actif' => 0]);
        
        return $this->success(null, 'Zone désactivée avec succès');
    }
    
    /**
     * API liste des zones actives
     */
    public function apiList()
    {
        $this->requireAuth();
        
        $zones = $this->zoneModel->getActive();
        
        return $this->success($zones);
    }
}
