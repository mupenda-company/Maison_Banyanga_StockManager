<?php
/**
 * Modèle Client
 */

class Client extends Model
{
    protected $table = 'clients';
    protected $fillable = ['nom', 'telephone', 'adresse', 'zone_id', 'email', 'taux_ristourne', 'notes', 'actif'];
    
    /**
     * Récupérer avec la zone
     */
    public function getWithZone($id)
    {
        return $this->db->fetch(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE c.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Récupérer tous avec zone
     */
    public function getAllWithZone()
    {
        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE c.actif = 1
             ORDER BY c.nom"
        );
    }
    
    /**
     * Récupérer par zone
     */
    public function getByZone($zoneId)
    {
        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE c.zone_id = :zone_id AND c.actif = 1
             ORDER BY c.nom",
            ['zone_id' => $zoneId]
        );
    }

    /**
     * Rechercher des clients par terme et zone.
     */
    public function searchWithZone($term, $zoneId = null, $includeInactive = false)
    {
        $where = $includeInactive ? '1=1' : 'c.actif = 1';
        $params = [];

        if ($zoneId !== null && $zoneId !== '') {
            $where .= ' AND c.zone_id = :zone_id';
            $params['zone_id'] = $zoneId;
        }

        $term = trim((string) $term);
        if ($term !== '') {
            $where .= " AND (c.nom LIKE :term_nom OR c.telephone LIKE :term_telephone OR c.email LIKE :term_email OR c.adresse LIKE :term_adresse OR z.nom LIKE :term_zone)";
            $likeTerm = '%' . $term . '%';
            $params['term_nom'] = $likeTerm;
            $params['term_telephone'] = $likeTerm;
            $params['term_email'] = $likeTerm;
            $params['term_adresse'] = $likeTerm;
            $params['term_zone'] = $likeTerm;
        }

        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE {$where}
             ORDER BY c.nom",
            $params
        );
    }
    
    /**
     * Calculer le CA d'un client pour une période
     */
    public function getCAPeriod($clientId, $dateDebut, $dateFin)
    {
        return $this->db->fetchColumn(
            "SELECT COALESCE(SUM(v.total_ttc), 0)
             FROM ventes v
             WHERE v.client_id = :client_id
             AND v.date_vente BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'",
            [
                'client_id' => $clientId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]
        );
    }

    /**
     * Récupérer les indicateurs client sur une période
     */
    public function getKpis($clientId, $dateDebut = null, $dateFin = null)
    {
        $client = $this->find($clientId);
        if (!$client) {
            return null;
        }

        $saleWhere = "v.client_id = :client_id AND v.statut = 'validee'";
        $saleParams = ['client_id' => $clientId];
        if (!empty($dateDebut)) {
            $saleWhere .= " AND v.date_vente >= :date_debut";
            $saleParams['date_debut'] = $dateDebut;
        }
        if (!empty($dateFin)) {
            $saleWhere .= " AND v.date_vente <= :date_fin";
            $saleParams['date_fin'] = str_contains($dateFin, ':') ? $dateFin : ($dateFin . ' 23:59:59');
        }

        $caseSql = "SELECT COALESCE(SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0)
                    FROM vente_details vd
                    JOIN ventes v ON vd.vente_id = v.id
                    JOIN produits p ON vd.produit_id = p.id
                    WHERE {$saleWhere}";
        $caissesAchetees = (int) $this->db->fetchColumn($caseSql, $saleParams);

        $caTotal = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(v.total_ttc), 0)
             FROM ventes v
             WHERE {$saleWhere}",
            $saleParams
        );

        $returnWhere = "r.client_id = :client_id";
        $returnParams = ['client_id' => $clientId];
        if (!empty($dateDebut)) {
            $returnWhere .= " AND r.date_retour >= :ret_date_debut";
            $returnParams['ret_date_debut'] = $dateDebut;
        }
        if (!empty($dateFin)) {
            $returnWhere .= " AND r.date_retour <= :ret_date_fin";
            $returnParams['ret_date_fin'] = str_contains($dateFin, ':') ? $dateFin : ($dateFin . ' 23:59:59');
        }

        $returnsSql = "SELECT COALESCE(SUM(ROUND(r.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0)
                       FROM retours_emballages r
                       JOIN produits p ON r.produit_id = p.id
                       WHERE {$returnWhere}";
        $caissesRetournees = (int) $this->db->fetchColumn($returnsSql, $returnParams);

        $tauxRistourne = (float) ($client['taux_ristourne'] ?? 5);
        $montantRistourne = ($caTotal * $tauxRistourne) / 100;

        return [
            'client' => $client,
            'caisses_achetees' => $caissesAchetees,
            'ca_total' => $caTotal,
            'caisses_retournees' => $caissesRetournees,
            'taux_ristourne' => $tauxRistourne,
            'montant_ristourne' => $montantRistourne,
        ];
    }
    
    /**
     * Récupérer le CA de tous les clients
     */
    public function getAllWithCA($dateDebut = null, $dateFin = null)
    {
        $whereDate = "";
        $params = [];
        
        if ($dateDebut && $dateFin) {
            $whereDate = "AND v.date_vente BETWEEN :date_debut AND :date_fin";
            $params['date_debut'] = $dateDebut;
            $params['date_fin'] = $dateFin;
        }
        
        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom,
                    COALESCE(SUM(v.total_ttc), 0) as ca_total,
                    COUNT(v.id) as nb_ventes
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             LEFT JOIN ventes v ON c.id = v.client_id AND v.statut = 'validee' {$whereDate}
             WHERE c.actif = 1
             GROUP BY c.id
             ORDER BY ca_total DESC",
            $params
        );
    }
    
    /**
     * Récupérer les achats d'un client
     */
    public function getAchats($id, $limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT v.*, 
                    SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)) as total_caisses,
                    SUM(vd.quantite) as total_bouteilles,
                    GROUP_CONCAT(p.nom SEPARATOR ', ') as produits
             FROM ventes v
             JOIN vente_details vd ON v.id = vd.vente_id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.client_id = :id AND v.statut = 'validee'
             GROUP BY v.id
             ORDER BY v.date_vente DESC
             LIMIT :limit",
            ['id' => $id, 'limit' => $limit]
        );
    }
    
    /**
     * Récupérer les clients d'une zone avec stats
     */
    public function getByZoneWithStats($zoneId)
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    COALESCE(SUM(CASE WHEN MONTH(v.date_vente) = MONTH(CURRENT_DATE) 
                                  AND YEAR(v.date_vente) = YEAR(CURRENT_DATE) 
                                  THEN v.total_ttc ELSE 0 END), 0) as ca_mois
             FROM {$this->table} c
             LEFT JOIN ventes v ON c.id = v.client_id AND v.statut = 'validee'
             WHERE c.zone_id = :zone_id AND c.actif = 1
             GROUP BY c.id
             ORDER BY c.nom",
            ['zone_id' => $zoneId]
        );
    }
    
    /**
     * Récupérer les dettes d'emballages d'un client
     */
    public function getDettesEmballages($clientId)
    {
        $result = $this->db->fetch(
            "SELECT 
                (SELECT COALESCE(SUM(vd.quantite), 0) 
                 FROM ventes v 
                 JOIN vente_details vd ON v.id = vd.vente_id 
                 WHERE v.client_id = :client_id AND v.statut = 'validee') 
                - 
                (SELECT COALESCE(SUM(r.quantite), 0) 
                 FROM retours_emballages r 
                 WHERE r.client_id = :client_id2) as total",
            ['client_id' => $clientId, 'client_id2' => $clientId]
        );
        
        return ['total' => $result['total'] ?? 0];
    }
}
