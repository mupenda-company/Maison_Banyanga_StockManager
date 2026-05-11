<?php
/**
 * Modèle Vehicule
 */

class Vehicule extends Model
{
    protected $table = 'vehicules';
    protected $fillable = ['immatriculation', 'marque', 'modele', 'agent_responsable_id', 'emplacement_id', 'capacite', 'actif'];
    
    /**
     * Récupérer les véhicules avec agent responsable
     */
    public function getWithAgent()
    {
        return $this->db->fetchAll(
            "SELECT v.*, 
                    u.nom as agent_nom, u.prenom as agent_prenom,
                    e.nom as emplacement_nom,
                    (SELECT COUNT(*) FROM missions m WHERE m.vehicule_id = v.id AND m.statut = 'en_cours' AND COALESCE(m.type_mission, 'vente') = 'vente') as en_mission,
                    (SELECT COALESCE(SUM(s.caisses_pleine), 0) FROM stocks s WHERE s.emplacement_id = v.emplacement_id) as stock_actuel
             FROM {$this->table} v
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN emplacements e ON v.emplacement_id = e.id
             WHERE v.actif = 1
             ORDER BY v.immatriculation"
        );
    }
    
    /**
     * Récupérer les véhicules disponibles (pas en mission)
     */
    public function getDisponibles()
    {
        return $this->db->fetchAll(
            "SELECT v.*, u.nom as agent_nom, u.prenom as agent_prenom,
                    COALESCE((SELECT SUM(s.quantite_pleine) FROM stocks s WHERE s.emplacement_id = v.emplacement_id), 0) as stock_plein,
                    COALESCE((SELECT SUM(s.quantite_vide) FROM stocks s WHERE s.emplacement_id = v.emplacement_id), 0) as stock_vide,
                    COALESCE((SELECT SUM(s.caisses_pleine) FROM stocks s WHERE s.emplacement_id = v.emplacement_id), 0) as stock_caisses_pleine,
                    COALESCE((SELECT SUM(s.caisses_vide) FROM stocks s WHERE s.emplacement_id = v.emplacement_id), 0) as stock_caisses_vide
             FROM {$this->table} v
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN emplacements e ON v.emplacement_id = e.id
             WHERE v.actif = 1 
             AND NOT EXISTS (
                 SELECT 1 FROM missions m 
                 WHERE m.vehicule_id = v.id AND m.statut = 'en_cours' AND COALESCE(m.type_mission, 'vente') = 'vente'
             )
             ORDER BY v.immatriculation"
        );
    }
    
    /**
     * Récupérer avec le stock actuel
     */
    public function getWithStock($id)
    {
        $vehicule = $this->db->fetch(
            "SELECT v.*, 
                    u.nom as agent_nom, u.prenom as agent_prenom,
                    e.nom as emplacement_nom,
                    (SELECT COUNT(*) FROM missions m WHERE m.vehicule_id = v.id AND m.statut = 'en_cours' AND COALESCE(m.type_mission, 'vente') = 'vente') as en_mission
             FROM {$this->table} v
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN emplacements e ON v.emplacement_id = e.id
             WHERE v.id = :id AND v.actif = 1",
            ['id' => $id]
        );
        
        if ($vehicule && $vehicule['emplacement_id']) {
            $stockModel = new Stock();
            $vehicule['stock'] = $stockModel->getByEmplacement($vehicule['emplacement_id']);
        }
        
        return $vehicule;
    }
    
    /**
     * Créer un véhicule avec son emplacement mobile
     */
    public function createWithEmplacement($data)
    {
        try {
            $this->db->beginTransaction();
            
            // Créer l'emplacement mobile
            $emplacementModel = new Emplacement();
            $emplacementId = $emplacementModel->create([
                'code' => 'VEH-' . str_replace(' ', '', $data['immatriculation']),
                'nom' => 'Véhicule ' . $data['immatriculation'],
                'type' => 'mobile',
                'capacite' => $data['capacite'] ?? 0
            ]);
            
            // Créer le véhicule
            $data['emplacement_id'] = $emplacementId;
            $vehiculeId = $this->create($data);
            
            $this->db->commit();
            return ['success' => true, 'id' => $vehiculeId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Vérifier si une immatriculation existe
     */
    public function immatriculationExists($immatriculation, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE immatriculation = :immatriculation";
        $params = ['immatriculation' => $immatriculation];
        
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        return $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Vérifier si un agent est déjà responsable d'un autre véhicule actif
     */
    public function agentHasVehicule($agentId, $excludeVehiculeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE agent_responsable_id = :agent_id AND actif = 1";
        $params = ['agent_id' => $agentId];

        if ($excludeVehiculeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeVehiculeId;
        }

        return $this->db->fetchColumn($sql, $params) > 0;
    }
}
