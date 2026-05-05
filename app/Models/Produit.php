<?php
/**
 * Modèle Produit
 */

class Produit extends Model
{
    protected $table = 'produits';
    protected $fillable = [
        'code', 'nom', 'description', 'categorie', 'unite_base',
        'bouteilles_par_caisses', 'prix_achat_unitaire', 'prix_vente_unitaire',
        'prix_vente_caisses', 'seuil_alerte', 'actif'
    ];
    
    /**
     * Récupérer tous les produits actifs
     */
    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY nom"
        );
    }
    
    /**
     * Récupérer les produits avec leur stock global
     */
    public function getWithStock()
    {
        return $this->db->fetchAll(
            "SELECT p.*, 
                    COALESCE(SUM(s.quantite_pleine), 0) as stock_plein,
                    COALESCE(SUM(s.quantite_vide), 0) as stock_vide,
                    COALESCE(SUM(s.caisses_pleine), 0) as stock_caisses_pleine,
                    COALESCE(SUM(s.caisses_vide), 0) as stock_caisses_vide
             FROM {$this->table} p
             LEFT JOIN stocks s ON p.id = s.produit_id
             LEFT JOIN emplacements e ON s.emplacement_id = e.id
             WHERE p.actif = 1
             GROUP BY p.id
             ORDER BY p.nom"
        );
    }
    
    /**
     * Récupérer les produits en alerte (stock sous le seuil)
     */
    public function getAlertProducts()
    {
        return $this->db->fetchAll(
            "SELECT p.*, 
                    COALESCE(SUM(s.quantite_pleine), 0) as stock_plein,
                    p.seuil_alerte
             FROM {$this->table} p
             LEFT JOIN stocks s ON p.id = s.produit_id
             WHERE p.actif = 1
             GROUP BY p.id
             HAVING stock_plein < p.seuil_alerte
             ORDER BY stock_plein ASC"
        );
    }
    
    /**
     * Récupérer par catégorie
     */
    public function getByCategory($categorie)
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE categorie = :categorie AND actif = 1 ORDER BY nom",
            ['categorie' => $categorie]
        );
    }
    
    /**
     * Récupérer toutes les catégories
     */
    public function getCategories()
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT categorie FROM {$this->table} WHERE categorie IS NOT NULL ORDER BY categorie"
        );
    }
    
    /**
     * Vérifier si un code existe
     */
    public function codeExists($code, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE code = :code";
        $params = ['code' => $code];
        
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        return $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Top ventes produits
     */
    public function getTopVentes($dateDebut, $dateFin, $limit = 5)
    {
        return $this->db->fetchAll(
            "SELECT p.nom, p.code, SUM(vd.quantite) as total_bouteilles, SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)) as total_caisses, SUM(vd.total_ligne) as total_ca
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.statut = 'validee' AND v.date_vente BETWEEN :d1 AND :d2
             GROUP BY p.id
             ORDER BY total_ca DESC
             LIMIT :limit",
            ['d1' => $dateDebut, 'd2' => $dateFin, 'limit' => (int)$limit]
        );
    }
}
