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
                    COALESCE(SUM(s.caisses_pleine), 0) as total_caisses_pleine,
                    COALESCE(SUM(s.caisses_vide), 0) as total_caisses_vide
             FROM {$this->table} e
             LEFT JOIN stocks s ON e.id = s.emplacement_id
             WHERE e.actif = 1
             GROUP BY e.id
             ORDER BY e.type, e.nom"
        );
    }
}
