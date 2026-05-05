<?php
/**
 * Modèle Stock
 */

class Stock extends Model
{
    protected $table = 'stocks';
    protected $fillable = ['produit_id', 'emplacement_id', 'quantite_pleine', 'quantite_vide', 'caisses_pleine', 'caisses_vide'];
    
    /**
     * Récupérer le stock d'un produit dans un emplacement
     */
    public function getStock($produitId, $emplacementId)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
            ['produit_id' => $produitId, 'emplacement_id' => $emplacementId]
        );
    }
    
    /**
     * Alias pour getStock (utilisé par MouvementStock)
     */
    public function getByProduitAndEmplacement($produitId, $emplacementId)
    {
        return $this->getStock($produitId, $emplacementId);
    }
    
    /**
     * Récupérer tous les stocks avec pagination et filtres
     */
    public function getAllPaginated($page = 1, $perPage = 20, $filters = [])
    {
        $where = "p.actif = 1 AND e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)";
        $params = [];
        
        if (!empty($filters['produit_id'])) {
            $where .= " AND s.produit_id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND s.emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }

        if (!empty($filters['statut'])) {
            if ($filters['statut'] === 'critique') {
                $where .= " AND s.quantite_pleine <= p.seuil_alerte";
            } elseif ($filters['statut'] === 'ok') {
                $where .= " AND s.quantite_pleine > p.seuil_alerte";
            }
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} s 
                     JOIN produits p ON s.produit_id = p.id 
                     JOIN emplacements e ON s.emplacement_id = e.id 
                     LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                     WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT s.*, p.nom as produit_nom, p.code as produit_code, p.seuil_alerte,
                       e.nom as emplacement_nom, e.type as emplacement_type,
                       v.immatriculation as vehicule_immatriculation
                FROM {$this->table} s
                JOIN produits p ON s.produit_id = p.id
                JOIN emplacements e ON s.emplacement_id = e.id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                WHERE {$where}
                ORDER BY e.type, e.nom, p.nom
                LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }
    
    /**
     * Récupérer le stock global par produit
     */
    public function getGlobalByProduct()
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.code, p.nom, p.seuil_alerte,
                    COALESCE(SUM(s.quantite_pleine), 0) as total_plein,
                    COALESCE(SUM(s.quantite_vide), 0) as total_vide,
                    CAST(COALESCE(SUM(s.caisses_pleine), 0) AS SIGNED) as total_caisses_pleine,
                    CAST(COALESCE(SUM(s.caisses_vide), 0) AS SIGNED) as total_caisses_vide
             FROM produits p
             LEFT JOIN {$this->table} s ON p.id = s.produit_id
             LEFT JOIN emplacements e ON s.emplacement_id = e.id
             WHERE p.actif = 1
             GROUP BY p.id
             ORDER BY p.nom"
        );
    }
    
    /**
     * Récupérer le stock par emplacement
     */
    public function getByEmplacement($emplacementId)
    {
        return $this->db->fetchAll(
            "SELECT s.*, p.nom as produit_nom, p.code as produit_code, p.seuil_alerte
             FROM {$this->table} s
             JOIN produits p ON s.produit_id = p.id
             WHERE s.emplacement_id = :emplacement_id AND p.actif = 1
             ORDER BY p.nom",
            ['emplacement_id' => $emplacementId]
        );
    }
    
    /**
     * Mettre à jour ou créer un stock
     */
    public function updateOrCreate($produitId, $emplacementId, $data)
    {
        $existing = $this->getStock($produitId, $emplacementId);
        $produit = (new Produit())->find($produitId);
        $btlParCaisse = $produit['bouteilles_par_caisses'] ?: 24;
        
        // Si on donne seulement les caisses, on calcule les bouteilles
        if (isset($data['caisses_pleine']) && !isset($data['quantite_pleine'])) {
            $data['quantite_pleine'] = $data['caisses_pleine'] * $btlParCaisse;
        }
        // Si on donne seulement les bouteilles, on calcule les caisses
        elseif (isset($data['quantite_pleine']) && !isset($data['caisses_pleine'])) {
            $data['caisses_pleine'] = intval($data['quantite_pleine'] / $btlParCaisse);
        }

        if ($existing) {
            $this->db->query(
                "UPDATE {$this->table} SET 
                    quantite_pleine = quantite_pleine + :quantite_pleine,
                    quantite_vide = quantite_vide + :quantite_vide,
                    caisses_pleine = caisses_pleine + :caisses_pleine,
                    caisses_vide = caisses_vide + :caisses_vide,
                    updated_at = NOW()
                 WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
                [
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'quantite_pleine' => $data['quantite_pleine'] ?? 0,
                    'quantite_vide' => $data['quantite_vide'] ?? 0,
                    'caisses_pleine' => $data['caisses_pleine'] ?? 0,
                    'caisses_vide' => $data['caisses_vide'] ?? 0
                ]
            );
            return $existing['id'];
        } else {
            return $this->create([
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'quantite_pleine' => $data['quantite_pleine'] ?? 0,
                'quantite_vide' => $data['quantite_vide'] ?? 0,
                'caisses_pleine' => $data['caisses_pleine'] ?? 0,
                'caisses_vide' => $data['caisses_vide'] ?? 0
            ]);
        }
    }
    
    /**
     * Déduire du stock vide
     */
    public function deduireVide($produitId, $emplacementId, $quantiteCaisses)
    {
        $existing = $this->getStock($produitId, $emplacementId);
        
        if (!$existing) {
            return ['success' => false, 'message' => 'Stock non trouvé'];
        }
        
        if ($existing['caisses_vide'] < $quantiteCaisses) {
            return [
                'success' => false, 
                'message' => 'Stock de caisses vides insuffisant',
                'disponible' => $existing['caisses_vide'],
                'demande' => $quantiteCaisses
            ];
        }
        
        $this->db->query(
            "UPDATE {$this->table} SET 
                caisses_vide = caisses_vide - :quantite,
                updated_at = NOW()
             WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
            [
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'quantite' => $quantiteCaisses
            ]
        );
        
        return ['success' => true, 'message' => 'Stock déduit avec succès'];
    }
    
    /**
     * Obtenir l'inventaire complet paginé avec filtres
     */
    public function getInventairePaginated($page = 1, $perPage = 5, $filters = [])
    {
        $where = "p.actif = 1 AND e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)";
        $params = [];
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND e.id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }
        
        if (!empty($filters['categorie'])) {
            $where .= " AND p.categorie = :categorie";
            $params['categorie'] = $filters['categorie'];
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM produits p 
                     JOIN emplacements e ON e.actif = 1
                     LEFT JOIN {$this->table} s ON p.id = s.produit_id AND e.id = s.emplacement_id 
                     LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                     WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT 
                    p.id as produit_id, p.code as produit_code, p.nom as produit_nom, 
                    p.prix_vente_caisses, p.prix_vente_unitaire, p.bouteilles_par_caisses, 
                    p.categorie, p.seuil_alerte,
                    e.id as emplacement_id, e.nom as emplacement_nom, e.type as emplacement_type,
                    v.immatriculation as vehicule,
                    u.nom as agent_nom, u.prenom as agent_prenom,
                    COALESCE(s.quantite_pleine, 0) as quantite_pleine,
                    COALESCE(s.quantite_vide, 0) as quantite_vide,
                    COALESCE(s.caisses_pleine, 0) as caisses_pleine,
                    COALESCE(s.caisses_vide, 0) as caisses_vide
                FROM produits p
                JOIN emplacements e ON e.actif = 1
                LEFT JOIN {$this->table} s ON p.id = s.produit_id AND e.id = s.emplacement_id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                LEFT JOIN users u ON v.agent_responsable_id = u.id
                WHERE {$where}
                ORDER BY p.nom, e.type, e.nom
                LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Calculer les totaux de l'inventaire avec filtres
     */
    public function getInventaireTotaux($filters = [])
    {
        $where = "p.actif = 1 AND e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)";
        $params = [];
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND e.id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }
        
        if (!empty($filters['categorie'])) {
            $where .= " AND p.categorie = :categorie";
            $params['categorie'] = $filters['categorie'];
        }
        
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as nb_produits,
                    COALESCE(SUM(s.caisses_pleine), 0) as total_caisses_pleine,
                    COALESCE(SUM(s.caisses_vide), 0) as total_caisses_vide,
                    COALESCE(SUM(s.quantite_pleine), 0) as total_btl_pleine,
                    COALESCE(SUM(s.quantite_vide), 0) as total_btl_vide,
                    SUM(COALESCE(s.caisses_pleine, 0) * (COALESCE(p.prix_vente_caisses, 0))) as valeur_stock
                FROM produits p
                JOIN emplacements e ON e.actif = 1
                LEFT JOIN {$this->table} s ON p.id = s.produit_id AND e.id = s.emplacement_id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                WHERE {$where}";
        
        $row = $this->db->fetch($sql, $params);
        
        return [
            'pleines' => (int)$row['total_btl_pleine'],
            'vides' => (int)$row['total_btl_vide'],
            'caisses_pleine' => (int) round($row['total_caisses_pleine']),
            'caisses_vide' => (int) round($row['total_caisses_vide']),
            'valeur' => (float)$row['valeur_stock'],
            'nb_produits' => (int)$row['nb_produits']
        ];
    }
}
