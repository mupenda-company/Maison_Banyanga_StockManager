<?php
/**
 * Modèle MouvementStock
 */

class MouvementStock extends Model
{
    protected $table = 'mouvements_stock';
    protected $fillable = ['produit_id', 'emplacement_id', 'type_mouvement', 'quantite', 'quantite_avant', 'quantite_apres', 'reference_type', 'reference_id', 'motif', 'created_by'];
    
    /**
     * Surcharger create pour gérer automatiquement les quantités avant/après
     */
    public function create($data)
    {
        if (!array_key_exists('quantite_avant', $data) || !array_key_exists('quantite_apres', $data)) {
            // Récupérer le stock actuel (Plein ou Vide selon le motif/type)
            $stockModel = new Stock();
            $stock = $stockModel->getByProduitAndEmplacement($data['produit_id'], $data['emplacement_id']);

            // Déterminer quelle quantité suivre (Pleine par défaut, sauf si spécifié "vide" dans le motif)
            $isVide = (isset($data['motif']) && stripos($data['motif'], 'vide') !== false) ||
                      (isset($data['reference_type']) && $data['reference_type'] === 'retour_emballage');

            $quantiteAvant = $isVide ? ($stock['quantite_vide'] ?? 0) : ($stock['quantite_pleine'] ?? 0);

            $data['quantite_avant'] = $quantiteAvant;
            $data['quantite_apres'] = $quantiteAvant + $data['quantite'];
        }

        return parent::create($data);
    }

    /**
     * Récupérer l'historique avec détails
     */
    public function getHistorique($filters = [], $page = 1, $perPage = 50)
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['produit_id'])) {
            $where .= " AND m.produit_id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND m.emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }
        
        if (!empty($filters['type_mouvement'])) {
            $where .= " AND m.type_mouvement = :type_mouvement";
            $params['type_mouvement'] = $filters['type_mouvement'];
        }
        
        if (!empty($filters['date_debut'])) {
            $where .= " AND m.created_at >= :date_debut";
            $params['date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_fin'])) {
            $where .= " AND m.created_at <= :date_fin";
            $params['date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} m WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT m.*, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses,
                       e.nom as emplacement_source, e.type as emplacement_type,
                       ed.nom as emplacement_dest,
                       u.nom as user_nom, u.prenom as user_prenom,
                       CASE 
                           WHEN m.reference_type = 'approvisionnement' THEN (SELECT numero_bon FROM approvisionnements WHERE id = m.reference_id)
                           WHEN m.reference_type = 'mission' THEN (SELECT numero_mission FROM missions WHERE id = m.reference_id)
                           ELSE NULL
                       END as reference_numero,
                       CASE
                           WHEN m.reference_type = 'vente' AND m.type_mouvement = 'sortie' THEN (
                               SELECT SUM(vd.quantite_caisses)
                               FROM vente_details vd
                               WHERE vd.vente_id = m.reference_id AND vd.produit_id = m.produit_id
                           )
                           WHEN m.reference_type = 'vente' AND m.type_mouvement = 'entree' THEN (
                               SELECT SUM(vd.caisses_vides_recues)
                               FROM vente_details vd
                               WHERE vd.vente_id = m.reference_id AND vd.produit_id = m.produit_id
                           )
                           ELSE NULL
                       END as quantite_caisses_reference
                FROM {$this->table} m
                JOIN produits p ON m.produit_id = p.id
                JOIN emplacements e ON m.emplacement_id = e.id
                LEFT JOIN emplacements ed ON (m.reference_type = 'transfert' AND m.reference_id = ed.id)
                LEFT JOIN users u ON m.created_by = u.id
                WHERE {$where}
                ORDER BY m.created_at DESC
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
     * Derniers mouvements
     */
    public function getDerniers($limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT m.*, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses,
                    e.nom as emplacement_nom,
                    CASE
                        WHEN m.reference_type = 'vente' AND m.type_mouvement = 'sortie' THEN (
                            SELECT SUM(vd.quantite_caisses)
                            FROM vente_details vd
                            WHERE vd.vente_id = m.reference_id AND vd.produit_id = m.produit_id
                        )
                        WHEN m.reference_type = 'vente' AND m.type_mouvement = 'entree' THEN (
                            SELECT SUM(vd.caisses_vides_recues)
                            FROM vente_details vd
                            WHERE vd.vente_id = m.reference_id AND vd.produit_id = m.produit_id
                        )
                        ELSE NULL
                    END as quantite_caisses_reference
             FROM {$this->table} m
             JOIN produits p ON m.produit_id = p.id
             JOIN emplacements e ON m.emplacement_id = e.id
             ORDER BY m.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
