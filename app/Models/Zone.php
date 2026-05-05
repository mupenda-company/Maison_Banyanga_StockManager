<?php
/**
 * Modèle Zone
 */

class Zone extends Model
{
    protected $table = 'zones';
    protected $fillable = ['nom', 'description', 'actif'];
    
    /**
     * Récupérer les zones actives
     */
    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY nom"
        );
    }
    
    /**
     * Récupérer avec le nombre de clients
     */
    public function getWithClientsCount()
    {
        return $this->db->fetchAll(
            "SELECT z.*, COUNT(c.id) as nb_clients
             FROM {$this->table} z
             LEFT JOIN clients c ON z.id = c.zone_id AND c.actif = 1
             WHERE z.actif = 1
             GROUP BY z.id
             ORDER BY z.nom"
        );
    }
    
    /**
     * Récupérer avec le CA total
     */
    public function getWithCA($dateDebut = null, $dateFin = null)
    {
        $whereDate = "";
        $params = [];
        
        if ($dateDebut && $dateFin) {
            $whereDate = "AND v.date_vente BETWEEN :date_debut AND :date_fin";
            $params['date_debut'] = $dateDebut;
            $params['date_fin'] = $dateFin;
        }
        
        return $this->db->fetchAll(
            "SELECT z.*, 
                    COUNT(DISTINCT c.id) as nb_clients,
                    COALESCE(SUM(v.total_ttc), 0) as ca_total
             FROM {$this->table} z
             LEFT JOIN clients c ON z.id = c.zone_id AND c.actif = 1
             LEFT JOIN ventes v ON c.id = v.client_id AND v.statut = 'validee' {$whereDate}
             WHERE z.actif = 1
             GROUP BY z.id
             ORDER BY ca_total DESC",
            $params
        );
    }
    
    /**
     * Récupérer avec stats (clients et CA du mois)
     */
    public function getWithStats()
    {
        return $this->db->fetchAll(
            "SELECT z.*, 
                    COUNT(DISTINCT c.id) as nb_clients,
                    COALESCE(SUM(CASE WHEN MONTH(v.date_vente) = MONTH(CURRENT_DATE) 
                                  AND YEAR(v.date_vente) = YEAR(CURRENT_DATE) 
                                  THEN v.total_ttc ELSE 0 END), 0) as ca_mois
             FROM {$this->table} z
             LEFT JOIN clients c ON z.id = c.zone_id AND c.actif = 1
             LEFT JOIN ventes v ON c.id = v.client_id AND v.statut = 'validee'
             GROUP BY z.id
             ORDER BY z.nom"
        );
    }
}
