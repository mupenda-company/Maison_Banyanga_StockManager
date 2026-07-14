<?php
/**
 * Modèle Stock
 */

class Stock extends Model
{
    protected $table = 'stocks';
    protected $fillable = ['produit_id', 'emplacement_id', 'quantite_pleine', 'quantite_vide', 'caisses_pleine', 'caisses_vide', 'quantite_pleine_physique', 'quantite_vide_physique', 'caisses_pleine_physique', 'caisses_vide_physique', 'last_physical_count_at', 'last_physical_mission_id'];

    private function refreshStockAlerts()
    {
        (new Alerte())->checkStockAlerts();
    }

    public function __construct()
    {
        parent::__construct();
        $this->ensurePhysicalStockColumns();
        $this->ensureAjustementsStockTable();
    }

    private function ensurePhysicalStockColumns(): void
    {
        $columns = [
            'quantite_pleine_physique' => "ALTER TABLE stocks ADD quantite_pleine_physique INT NULL AFTER quantite_vide",
            'quantite_vide_physique' => "ALTER TABLE stocks ADD quantite_vide_physique INT NULL AFTER quantite_pleine_physique",
            'caisses_pleine_physique' => "ALTER TABLE stocks ADD caisses_pleine_physique INT NULL AFTER caisses_vide",
            'caisses_vide_physique' => "ALTER TABLE stocks ADD caisses_vide_physique INT NULL AFTER caisses_pleine_physique",
            'last_physical_count_at' => "ALTER TABLE stocks ADD last_physical_count_at DATETIME NULL AFTER caisses_vide_physique",
            'last_physical_mission_id' => "ALTER TABLE stocks ADD last_physical_mission_id INT UNSIGNED NULL AFTER last_physical_count_at",
        ];

        foreach ($columns as $column => $sql) {
            $exists = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'stocks'
                   AND COLUMN_NAME = :column",
                ['column' => $column]
            );

            if (!$exists) {
                $this->db->query($sql);
            }
        }
    }


    private function ensureAjustementsStockTable(): void
    {
        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ajustements_stock'"
        );

        if (!$exists) {
            $this->db->query(
                "CREATE TABLE ajustements_stock (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    produit_id INT UNSIGNED NOT NULL,
                    emplacement_id INT UNSIGNED NOT NULL,
                    type_ajustement VARCHAR(30) NOT NULL DEFAULT 'inventaire',
                    ancien_systeme_plein DECIMAL(12,2) NOT NULL DEFAULT 0,
                    physique_plein DECIMAL(12,2) NOT NULL DEFAULT 0,
                    nouveau_systeme_plein DECIMAL(12,2) NOT NULL DEFAULT 0,
                    ecart_plein DECIMAL(12,2) NOT NULL DEFAULT 0,
                    ancien_systeme_vide DECIMAL(12,2) NOT NULL DEFAULT 0,
                    physique_vide DECIMAL(12,2) NOT NULL DEFAULT 0,
                    nouveau_systeme_vide DECIMAL(12,2) NOT NULL DEFAULT 0,
                    ecart_vide DECIMAL(12,2) NOT NULL DEFAULT 0,
                    motif TEXT NULL,
                    created_by INT UNSIGNED NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ajustements_stock_produit (produit_id),
                    INDEX idx_ajustements_stock_emplacement (emplacement_id),
                    INDEX idx_ajustements_stock_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }
    }

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
     * Reconstituer le stock a la fin d'une date depuis l'etat actuel.
     */
    public function getHistoricalInventory($date, $filters = [])
    {
        $where = "p.actif = 1 AND e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)";
        $params = ['date_fin_plein' => $date . ' 23:59:59', 'date_fin_vide' => $date . ' 23:59:59'];
        foreach (['produit_id' => 'p.id', 'emplacement_id' => 'e.id', 'categorie' => 'p.categorie'] as $key => $column) {
            if (!empty($filters[$key])) {
                $where .= " AND {$column} = :{$key}";
                $params[$key] = $filters[$key];
            }
        }
        $isVide = "(LOWER(COALESCE(m.motif, '')) LIKE '%vide%' OR m.reference_type = 'retour_emballage')";
        $sql = "SELECT p.id AS produit_id, p.code AS produit_code, p.nom AS produit_nom,
                       p.position_affichage,
                       p.prix_vente_caisses, p.prix_vente_unitaire, p.bouteilles_par_caisses, p.categorie, p.seuil_alerte,
                       e.id AS emplacement_id, e.nom AS emplacement_nom, e.type AS emplacement_type,
                       v.id AS vehicule_id, v.immatriculation AS vehicule, v.immatriculation AS vehicule_immatriculation,
                       u.nom AS agent_nom, u.prenom AS agent_prenom,
                       GREATEST(0, COALESCE(s.quantite_pleine, 0) - COALESCE(SUM(CASE WHEN m.created_at > :date_fin_plein AND NOT {$isVide} THEN m.quantite ELSE 0 END), 0)) AS quantite_pleine,
                       GREATEST(0, COALESCE(s.quantite_vide, 0) - COALESCE(SUM(CASE WHEN m.created_at > :date_fin_vide AND {$isVide} THEN m.quantite ELSE 0 END), 0)) AS quantite_vide
                FROM produits p JOIN emplacements e ON e.actif = 1
                LEFT JOIN stocks s ON s.produit_id = p.id AND s.emplacement_id = e.id
                LEFT JOIN mouvements_stock m ON m.produit_id = p.id AND m.emplacement_id = e.id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                LEFT JOIN users u ON v.agent_responsable_id = u.id
                WHERE {$where}
                GROUP BY p.id, e.id, s.quantite_pleine, s.quantite_vide, v.id, u.id
                ORDER BY e.type, e.nom, p.position_affichage ASC, p.nom ASC";
        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $btl = max(1, (int) ($row['bouteilles_par_caisses'] ?? 24));
            $row['caisses_pleine'] = (float) $row['quantite_pleine'] / $btl;
            $row['caisses_vide'] = (float) $row['quantite_vide'] / $btl;
        }
        unset($row);
        if (!empty($filters['statut'])) {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                $critique = (float) $row['caisses_pleine'] <= (float) ($row['seuil_alerte'] ?? 0);
                return $filters['statut'] === 'critique' ? $critique : !$critique;
            }));
        }
        return $rows;
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
                $where .= " AND s.caisses_pleine <= p.seuil_alerte";
            } elseif ($filters['statut'] === 'ok') {
                $where .= " AND s.caisses_pleine > p.seuil_alerte";
            }
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} s 
                     JOIN produits p ON s.produit_id = p.id 
                     JOIN emplacements e ON s.emplacement_id = e.id 
                     LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                     WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT s.*, p.nom as produit_nom, p.code as produit_code, p.position_affichage, p.seuil_alerte,
                       e.nom as emplacement_nom, e.type as emplacement_type,
                       v.id as vehicule_id,
                       v.immatriculation as vehicule_immatriculation,
                       COALESCE(s.quantite_pleine_physique, s.quantite_pleine, 0) as quantite_pleine_physique_calc,
                       COALESCE(s.quantite_vide_physique, s.quantite_vide, 0) as quantite_vide_physique_calc,
                       COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) as caisses_pleine_physique_calc,
                       COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) as caisses_vide_physique_calc,
                       (COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) - COALESCE(s.caisses_pleine, 0)) as ecart_caisses_pleine,
                       (COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) - COALESCE(s.caisses_vide, 0)) as ecart_caisses_vide
                FROM {$this->table} s
                JOIN produits p ON s.produit_id = p.id
                JOIN emplacements e ON s.emplacement_id = e.id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                WHERE {$where}
                ORDER BY e.type, e.nom, p.position_affichage ASC, p.nom ASC
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
             ORDER BY p.position_affichage ASC, p.nom ASC"
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
             ORDER BY p.position_affichage ASC, p.nom ASC",
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

        $delta = [
            'quantite_pleine' => (int) ($data['quantite_pleine'] ?? 0),
            'quantite_vide' => (int) ($data['quantite_vide'] ?? 0),
            'caisses_pleine' => (int) ($data['caisses_pleine'] ?? 0),
            'caisses_vide' => (int) ($data['caisses_vide'] ?? 0),
        ];

        $actuel = [
            'quantite_pleine' => (int) ($existing['quantite_pleine'] ?? 0),
            'quantite_vide' => (int) ($existing['quantite_vide'] ?? 0),
            'caisses_pleine' => (int) ($existing['caisses_pleine'] ?? 0),
            'caisses_vide' => (int) ($existing['caisses_vide'] ?? 0),
        ];

        foreach ($delta as $champ => $variation) {
            if ($actuel[$champ] + $variation < 0) {
                $libelle = str_replace('_', ' ', $champ);
                throw new Exception('Stock insuffisant pour ' . $libelle . ' : disponible ' . $actuel[$champ] . ', demande ' . abs($variation));
            }
        }

        if ($existing) {
            $this->db->query(
                "UPDATE {$this->table} SET 
                    quantite_pleine = quantite_pleine + :quantite_pleine,
                    quantite_vide = quantite_vide + :quantite_vide,
                    caisses_pleine = caisses_pleine + :caisses_pleine,
                    caisses_vide = caisses_vide + :caisses_vide,
                    quantite_pleine_physique = CASE WHEN quantite_pleine_physique IS NULL THEN NULL ELSE quantite_pleine_physique + :quantite_pleine_physique_delta END,
                    quantite_vide_physique = CASE WHEN quantite_vide_physique IS NULL THEN NULL ELSE quantite_vide_physique + :quantite_vide_physique_delta END,
                    caisses_pleine_physique = CASE WHEN caisses_pleine_physique IS NULL THEN NULL ELSE caisses_pleine_physique + :caisses_pleine_physique_delta END,
                    caisses_vide_physique = CASE WHEN caisses_vide_physique IS NULL THEN NULL ELSE caisses_vide_physique + :caisses_vide_physique_delta END,
                    updated_at = NOW()
                 WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
                [
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'quantite_pleine' => $delta['quantite_pleine'],
                    'quantite_vide' => $delta['quantite_vide'],
                    'caisses_pleine' => $delta['caisses_pleine'],
                    'caisses_vide' => $delta['caisses_vide'],
                    'quantite_pleine_physique_delta' => $delta['quantite_pleine'],
                    'quantite_vide_physique_delta' => $delta['quantite_vide'],
                    'caisses_pleine_physique_delta' => $delta['caisses_pleine'],
                    'caisses_vide_physique_delta' => $delta['caisses_vide']
                ]
            );
            $this->refreshStockAlerts();
            return $existing['id'];
        } else {
            $id = $this->create([
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'quantite_pleine' => $delta['quantite_pleine'],
                'quantite_vide' => $delta['quantite_vide'],
                'caisses_pleine' => $delta['caisses_pleine'],
                'caisses_vide' => $delta['caisses_vide'],
                'quantite_pleine_physique' => $delta['quantite_pleine'],
                'quantite_vide_physique' => $delta['quantite_vide'],
                'caisses_pleine_physique' => $delta['caisses_pleine'],
                'caisses_vide_physique' => $delta['caisses_vide']
            ]);
            $this->refreshStockAlerts();
            return $id;
        }
    }
    
    /**
     * Définir un stock initial absolu
     */
    public function setInitialStock($produitId, $emplacementId, $data)
    {
        $produit = (new Produit())->find($produitId);
        $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
        if ($btlParCaisse <= 0) {
            $btlParCaisse = 24;
        }

        $caissesPleines = (int) ($data['caisses_pleine'] ?? 0);
        $caissesVides = (int) ($data['caisses_vide'] ?? 0);
        $quantitePleine = isset($data['quantite_pleine'])
            ? (int) $data['quantite_pleine']
            : ($caissesPleines * $btlParCaisse);
        $quantiteVide = isset($data['quantite_vide'])
            ? (int) $data['quantite_vide']
            : ($caissesVides * $btlParCaisse);

        $existing = $this->getStock($produitId, $emplacementId);
        if ($existing) {
            $this->db->query(
                "UPDATE {$this->table} SET
                    quantite_pleine = :quantite_pleine,
                    quantite_vide = :quantite_vide,
                    caisses_pleine = :caisses_pleine,
                    caisses_vide = :caisses_vide,
                    quantite_pleine_physique = :quantite_pleine_physique,
                    quantite_vide_physique = :quantite_vide_physique,
                    caisses_pleine_physique = :caisses_pleine_physique,
                    caisses_vide_physique = :caisses_vide_physique,
                    last_physical_count_at = NOW(),
                    updated_at = NOW()
                 WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
                [
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'quantite_pleine' => $quantitePleine,
                    'quantite_vide' => $quantiteVide,
                    'caisses_pleine' => $caissesPleines,
                    'caisses_vide' => $caissesVides,
                    'quantite_pleine_physique' => $quantitePleine,
                    'quantite_vide_physique' => $quantiteVide,
                    'caisses_pleine_physique' => $caissesPleines,
                    'caisses_vide_physique' => $caissesVides
                ]
            );

            $this->refreshStockAlerts();
            return $existing['id'];
        }

        $id = $this->create([
            'produit_id' => $produitId,
            'emplacement_id' => $emplacementId,
            'quantite_pleine' => $quantitePleine,
            'quantite_vide' => $quantiteVide,
            'caisses_pleine' => $caissesPleines,
            'caisses_vide' => $caissesVides,
            'quantite_pleine_physique' => $quantitePleine,
            'quantite_vide_physique' => $quantiteVide,
            'caisses_pleine_physique' => $caissesPleines,
            'caisses_vide_physique' => $caissesVides,
            'last_physical_count_at' => date('Y-m-d H:i:s')
        ]);
        $this->refreshStockAlerts();
        return $id;
    }
    
    /**
     * Définir le stock physique constaté sans changer le stock système.
     * Utilisé surtout à la clôture de mission pour garder une double lecture :
     * - caisses_pleine / caisses_vide = stock système selon les ventes saisies ;
     * - *_physique = stock réellement compté sur terrain.
     */
    public function setPhysicalStock($produitId, $emplacementId, $data, $missionId = null)
    {
        $produit = (new Produit())->find($produitId);
        $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
        if ($btlParCaisse <= 0) {
            $btlParCaisse = 24;
        }

        $caissesPleines = max(0, (int) ($data['caisses_pleine'] ?? 0));
        $caissesVides = max(0, (int) ($data['caisses_vide'] ?? 0));
        $quantitePleine = isset($data['quantite_pleine'])
            ? max(0, (int) $data['quantite_pleine'])
            : ($caissesPleines * $btlParCaisse);
        $quantiteVide = isset($data['quantite_vide'])
            ? max(0, (int) $data['quantite_vide'])
            : ($caissesVides * $btlParCaisse);

        $existing = $this->getStock($produitId, $emplacementId);
        if (!$existing) {
            $this->create([
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'quantite_pleine' => 0,
                'quantite_vide' => 0,
                'caisses_pleine' => 0,
                'caisses_vide' => 0,
            ]);
        }

        $this->db->query(
            "UPDATE {$this->table} SET
                quantite_pleine_physique = :quantite_pleine_physique,
                quantite_vide_physique = :quantite_vide_physique,
                caisses_pleine_physique = :caisses_pleine_physique,
                caisses_vide_physique = :caisses_vide_physique,
                last_physical_count_at = NOW(),
                last_physical_mission_id = :mission_id,
                updated_at = NOW()
             WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
            [
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'quantite_pleine_physique' => $quantitePleine,
                'quantite_vide_physique' => $quantiteVide,
                'caisses_pleine_physique' => $caissesPleines,
                'caisses_vide_physique' => $caissesVides,
                'mission_id' => $missionId,
            ]
        );

        return true;
    }

    /**
     * Déduire du stock vide
     */
    public function deduireVide($produitId, $emplacementId, $quantiteCaisses)
    {
        $existing = $this->getStock($produitId, $emplacementId);
        $produit = (new Produit())->find($produitId);
        $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
        if ($btlParCaisse <= 0) {
            $btlParCaisse = 24;
        }
        $quantiteBouteilles = (int) $quantiteCaisses * $btlParCaisse;
        
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
                quantite_vide = GREATEST(0, quantite_vide - :quantite_bouteilles_stock),
                caisses_vide = caisses_vide - :quantite_caisses_stock,
                quantite_vide_physique = CASE WHEN quantite_vide_physique IS NULL THEN NULL ELSE GREATEST(0, quantite_vide_physique - :quantite_bouteilles_physique) END,
                caisses_vide_physique = CASE WHEN caisses_vide_physique IS NULL THEN NULL ELSE caisses_vide_physique - :quantite_caisses_physique END,
                updated_at = NOW()
             WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
            [
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'quantite_caisses_stock' => $quantiteCaisses,
                'quantite_bouteilles_stock' => $quantiteBouteilles,
                'quantite_caisses_physique' => $quantiteCaisses,
                'quantite_bouteilles_physique' => $quantiteBouteilles
            ]
        );
        
        $this->refreshStockAlerts();

        return ['success' => true, 'message' => 'Stock déduit avec succès'];
    }
    
    /**
     * Obtenir l'inventaire complet paginé avec filtres
     */
    public function getInventairePaginated($page = 1, $perPage = 5, $filters = [])
    {
        $where = "p.actif = 1 AND e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)";
        $params = [];
        
        if (!empty($filters['produit_id'])) {
            $where .= " AND p.id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }

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
                    p.id as produit_id, p.code as produit_code, p.nom as produit_nom, p.position_affichage,
                    p.prix_vente_caisses, p.prix_vente_unitaire, p.bouteilles_par_caisses, 
                    p.categorie, p.seuil_alerte,
                    e.id as emplacement_id, e.nom as emplacement_nom, e.type as emplacement_type,
                    v.id as vehicule_id,
                    v.immatriculation as vehicule,
                    u.nom as agent_nom, u.prenom as agent_prenom,
                    COALESCE(s.quantite_pleine, 0) as quantite_pleine,
                    COALESCE(s.quantite_vide, 0) as quantite_vide,
                    COALESCE(s.caisses_pleine, 0) as caisses_pleine,
                    COALESCE(s.caisses_vide, 0) as caisses_vide,
                    COALESCE(s.quantite_pleine_physique, s.quantite_pleine, 0) as quantite_pleine_physique_calc,
                    COALESCE(s.quantite_vide_physique, s.quantite_vide, 0) as quantite_vide_physique_calc,
                    COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) as caisses_pleine_physique_calc,
                    COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) as caisses_vide_physique_calc,
                    (COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) - COALESCE(s.caisses_pleine, 0)) as ecart_caisses_pleine,
                    (COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) - COALESCE(s.caisses_vide, 0)) as ecart_caisses_vide
                FROM produits p
                JOIN emplacements e ON e.actif = 1
                LEFT JOIN {$this->table} s ON p.id = s.produit_id AND e.id = s.emplacement_id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                LEFT JOIN users u ON v.agent_responsable_id = u.id
                WHERE {$where}
                ORDER BY p.position_affichage ASC, p.nom ASC, e.type, e.nom
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
        
        if (!empty($filters['produit_id'])) {
            $where .= " AND p.id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }
        
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
                    COALESCE(SUM(COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0)), 0) as total_caisses_pleine_physique,
                    COALESCE(SUM(COALESCE(s.caisses_vide_physique, s.caisses_vide, 0)), 0) as total_caisses_vide_physique,
                    COALESCE(SUM(COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) - COALESCE(s.caisses_pleine, 0)), 0) as total_ecart_caisses_pleine,
                    COALESCE(SUM(COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) - COALESCE(s.caisses_vide, 0)), 0) as total_ecart_caisses_vide,
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
            'caisses_pleine_physique' => (int) round($row['total_caisses_pleine_physique'] ?? $row['total_caisses_pleine']),
            'caisses_vide_physique' => (int) round($row['total_caisses_vide_physique'] ?? $row['total_caisses_vide']),
            'ecart_caisses_pleine' => (int) round($row['total_ecart_caisses_pleine'] ?? 0),
            'ecart_caisses_vide' => (int) round($row['total_ecart_caisses_vide'] ?? 0),
            'valeur' => (float)$row['valeur_stock'],
            'nb_produits' => (int)$row['nb_produits']
        ];
    }

    /**
     * Récupérer uniquement les lignes qui ont un écart système/physique.
     */
    public function getEcarts($filters = [])
    {
        $where = "p.actif = 1 AND e.actif = 1 AND (e.type != 'mobile' OR v.emplacement_id IS NOT NULL)";
        $params = [];

        if (!empty($filters['produit_id'])) {
            $where .= " AND p.id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }

        if (!empty($filters['emplacement_id'])) {
            $where .= " AND e.id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }

        $sql = "SELECT s.*, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses,
                       e.nom as emplacement_nom, e.type as emplacement_type,
                       v.id as vehicule_id, v.immatriculation as vehicule_immatriculation,
                       COALESCE(s.caisses_pleine, 0) as caisses_pleine_systeme,
                       COALESCE(s.caisses_vide, 0) as caisses_vide_systeme,
                       COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) as caisses_pleine_physique_calc,
                       COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) as caisses_vide_physique_calc,
                       (COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) - COALESCE(s.caisses_pleine, 0)) as ecart_caisses_pleine,
                       (COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) - COALESCE(s.caisses_vide, 0)) as ecart_caisses_vide
                FROM {$this->table} s
                JOIN produits p ON p.id = s.produit_id
                JOIN emplacements e ON e.id = s.emplacement_id
                LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
                WHERE {$where}
                  AND (
                    COALESCE(s.caisses_pleine_physique, s.caisses_pleine, 0) <> COALESCE(s.caisses_pleine, 0)
                    OR COALESCE(s.caisses_vide_physique, s.caisses_vide, 0) <> COALESCE(s.caisses_vide, 0)
                  )
                ORDER BY e.type, e.nom, p.position_affichage ASC, p.nom ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Corriger l'écart en alignant le stock système sur le stock physique.
     * Règle métier : les emballages/vides ne se corrigent jamais dans un véhicule.
     */
    public function corrigerEcart($produitId, $emplacementId, $options = [])
    {
        $corrigerPlein = !empty($options['corriger_plein']);
        $corrigerVide = !empty($options['corriger_vide']);
        $motif = trim((string) ($options['motif'] ?? ''));
        $createdBy = $options['created_by'] ?? ($_SESSION['user_id'] ?? null);

        if (!$corrigerPlein && !$corrigerVide) {
            return ['success' => false, 'message' => 'Sélectionnez au moins un type d’écart à corriger.'];
        }

        if ($motif === '') {
            return ['success' => false, 'message' => 'Le motif est obligatoire pour corriger un écart.'];
        }

        try {
            $this->db->beginTransaction();

            $stock = $this->db->fetch(
                "SELECT s.*, p.bouteilles_par_caisses, p.nom as produit_nom, e.type as emplacement_type, e.nom as emplacement_nom
                 FROM {$this->table} s
                 JOIN produits p ON p.id = s.produit_id
                 JOIN emplacements e ON e.id = s.emplacement_id
                 WHERE s.produit_id = :produit_id AND s.emplacement_id = :emplacement_id
                 FOR UPDATE",
                ['produit_id' => $produitId, 'emplacement_id' => $emplacementId]
            );

            if (!$stock) {
                throw new Exception('Stock introuvable pour ce produit et cet emplacement.');
            }

            $isMobile = ($stock['emplacement_type'] ?? '') === 'mobile';
            if ($isMobile && $corrigerVide) {
                throw new Exception('Les écarts d’emballages/vides doivent être corrigés uniquement dans l’entrepôt, pas dans le véhicule.');
            }

            $btlParCaisse = (int) ($stock['bouteilles_par_caisses'] ?? 24);
            if ($btlParCaisse <= 0) {
                $btlParCaisse = 24;
            }

            $ancienPlein = (float) ($stock['caisses_pleine'] ?? 0);
            $physiquePlein = (float) ($stock['caisses_pleine_physique'] ?? $stock['caisses_pleine'] ?? 0);
            $ecartPlein = $physiquePlein - $ancienPlein;

            $ancienVide = (float) ($stock['caisses_vide'] ?? 0);
            $physiqueVide = (float) ($stock['caisses_vide_physique'] ?? $stock['caisses_vide'] ?? 0);
            $ecartVide = $physiqueVide - $ancienVide;

            $nouveauPlein = $ancienPlein;
            $nouveauVide = $ancienVide;
            $mouvements = [];

            if ($corrigerPlein && abs($ecartPlein) > 0.0001) {
                $nouveauPlein = $physiquePlein;
                $mouvements[] = [
                    'type' => 'plein',
                    'ecart' => $ecartPlein,
                    'quantite' => $ecartPlein * $btlParCaisse,
                    'motif' => 'Correction écart inventaire plein: ' . ($ecartPlein > 0 ? '+' : '') . $ecartPlein . ' cs - ' . $motif,
                ];
            }

            if ($corrigerVide && abs($ecartVide) > 0.0001) {
                $nouveauVide = $physiqueVide;
                $mouvements[] = [
                    'type' => 'vide',
                    'ecart' => $ecartVide,
                    'quantite' => $ecartVide * $btlParCaisse,
                    'motif' => 'Correction écart inventaire vides: ' . ($ecartVide > 0 ? '+' : '') . $ecartVide . ' cs - ' . $motif,
                ];
            }

            if (empty($mouvements)) {
                $this->db->commit();
                return ['success' => true, 'message' => 'Aucun écart à corriger.'];
            }

            $this->db->insert('ajustements_stock', [
                'produit_id' => $produitId,
                'emplacement_id' => $emplacementId,
                'type_ajustement' => 'inventaire',
                'ancien_systeme_plein' => $ancienPlein,
                'physique_plein' => $physiquePlein,
                'nouveau_systeme_plein' => $nouveauPlein,
                'ecart_plein' => $corrigerPlein ? $ecartPlein : 0,
                'ancien_systeme_vide' => $ancienVide,
                'physique_vide' => $physiqueVide,
                'nouveau_systeme_vide' => $nouveauVide,
                'ecart_vide' => $corrigerVide ? $ecartVide : 0,
                'motif' => $motif,
                'created_by' => $createdBy,
            ]);

            $this->db->query(
                "UPDATE {$this->table} SET
                    caisses_pleine = :caisses_pleine,
                    quantite_pleine = :quantite_pleine,
                    caisses_vide = :caisses_vide,
                    quantite_vide = :quantite_vide,
                    caisses_pleine_physique = :caisses_pleine_physique,
                    quantite_pleine_physique = :quantite_pleine_physique,
                    caisses_vide_physique = :caisses_vide_physique,
                    quantite_vide_physique = :quantite_vide_physique,
                    updated_at = NOW()
                 WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id",
                [
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'caisses_pleine' => $nouveauPlein,
                    'quantite_pleine' => (int) round($nouveauPlein * $btlParCaisse),
                    'caisses_vide' => $nouveauVide,
                    'quantite_vide' => (int) round($nouveauVide * $btlParCaisse),
                    'caisses_pleine_physique' => $nouveauPlein,
                    'quantite_pleine_physique' => (int) round($nouveauPlein * $btlParCaisse),
                    'caisses_vide_physique' => $nouveauVide,
                    'quantite_vide_physique' => (int) round($nouveauVide * $btlParCaisse),
                ]
            );

            $mouvementModel = new MouvementStock();
            foreach ($mouvements as $mvt) {
                $mouvementModel->create([
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'type_mouvement' => 'inventaire',
                    'quantite' => $mvt['quantite'],
                    'reference_type' => 'ajustement_stock',
                    'reference_id' => 0,
                    'motif' => $mvt['motif'],
                    'created_by' => $createdBy,
                ]);
            }

            $this->refreshStockAlerts();
            $this->db->commit();

            return ['success' => true, 'message' => 'Écart corrigé et stock aligné avec le comptage physique.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getHistoriqueAjustements($filters = [])
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['produit_id'])) {
            $where .= ' AND a.produit_id = :produit_id';
            $params['produit_id'] = $filters['produit_id'];
        }
        if (!empty($filters['emplacement_id'])) {
            $where .= ' AND a.emplacement_id = :emplacement_id';
            $params['emplacement_id'] = $filters['emplacement_id'];
        }

        return $this->db->fetchAll(
            "SELECT a.*, p.nom as produit_nom, p.code as produit_code, e.nom as emplacement_nom, e.type as emplacement_type,
                    CONCAT(u.prenom, ' ', u.nom) as user_nom
             FROM ajustements_stock a
             JOIN produits p ON p.id = a.produit_id
             JOIN emplacements e ON e.id = a.emplacement_id
             LEFT JOIN users u ON u.id = a.created_by
             WHERE {$where}
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT 300",
            $params
        );
    }

}
