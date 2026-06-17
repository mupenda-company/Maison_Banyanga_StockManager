<?php
/**
 * Modèle Emplacement
 */

class Emplacement extends Model
{
    protected $table = 'emplacements';
    protected $fillable = ['code', 'nom', 'type', 'capacite', 'actif'];
    
    /**
     * Récupérer les emplacements fixes
     */
    public function getFixes()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE type = 'fixe' AND actif = 1 ORDER BY nom"
        );
    }
    
    /**
     * Récupérer les emplacements mobiles (véhicules)
     */
    public function getMobiles()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE type = 'mobile' AND actif = 1 ORDER BY nom"
        );
    }
    
    /**
     * Récupérer l'emplacement principal (Entrepôt)
     */
    public function getPrincipal()
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE type = 'fixe' ORDER BY id ASC LIMIT 1"
        );
    }
    
    /**
     * Récupérer avec le stock total
     */
    public function getWithStock()
    {
        return $this->db->fetchAll(
            "SELECT e.*, 
                    COALESCE(SUM(s.quantite_pleine), 0) as total_stock_plein,
                    COALESCE(SUM(s.quantite_vide), 0) as total_stock_vide,
                    CAST(COALESCE(SUM(s.caisses_pleine), 0) AS SIGNED) as total_caisses_pleine,
                    CAST(COALESCE(SUM(s.caisses_vide), 0) AS SIGNED) as total_caisses_vide,
                    CAST(COALESCE(SUM(COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0)), 0) AS SIGNED) as total_caisses_pleine_physique,
                    CAST(COALESCE(SUM(COALESCE(s.caisses_vide_physique, s.caisses_vide, 0)), 0) AS SIGNED) as total_caisses_vide_physique,
                    CAST(COALESCE(SUM(COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) - COALESCE(s.caisses_pleine, 0)), 0) AS SIGNED) as ecart_caisses_pleine,
                    CAST(COALESCE(SUM(COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) - COALESCE(s.caisses_vide, 0)), 0) AS SIGNED) as ecart_caisses_vide,
                    MAX(v.id) as vehicule_id,
                    MAX(v.immatriculation) as vehicule_immatriculation
             FROM {$this->table} e
             LEFT JOIN stocks s ON e.id = s.emplacement_id
             LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
             WHERE e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)
             GROUP BY e.id, e.code, e.nom, e.type, e.capacite, e.actif, e.created_at, e.updated_at
             ORDER BY e.type, e.nom"
        );
    }
}
