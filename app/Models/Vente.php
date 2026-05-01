<?php
/**
 * Modèle Vente
 */

class Vente extends Model
{
    protected $table = 'ventes';
    protected $fillable = ['numero_facture', 'client_id', 'date_vente', 'mission_id', 'emplacement_id', 'total_ht', 'total_tva', 'total_ttc', 'statut', 'notes', 'created_by'];
    
    /**
     * Générer un numéro de facture unique
     */
    public function generateNumeroFacture()
    {
        $prefix = 'FAC-' . date('Ymd');
        $last = $this->db->fetchColumn(
            "SELECT MAX(numero_facture) FROM {$this->table} WHERE numero_facture LIKE :prefix",
            ['prefix' => $prefix . '%']
        );
        
        if ($last) {
            $num = (int) substr($last, -4) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Récupérer avec les détails
     */
    public function getWithDetails($id)
    {
        $vente = $this->db->fetch(
            "SELECT v.*, u.nom as created_by_nom, u.prenom as created_by_prenom, 
                    e.nom as emplacement_nom, c.nom as client_nom, c.telephone as client_telephone,
                    c.adresse as client_adresse, z.nom as zone_nom
             FROM {$this->table} v
             LEFT JOIN users u ON v.created_by = u.id
             LEFT JOIN emplacements e ON v.emplacement_id = e.id
             LEFT JOIN clients c ON v.client_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE v.id = :id",
            ['id' => $id]
        );
        
        if ($vente) {
            $vente['client'] = $this->db->fetch(
                "SELECT c.*, z.nom as zone_nom
                 FROM clients c
                 LEFT JOIN zones z ON c.zone_id = z.id
                 WHERE c.id = :id",
                ['id' => $vente['client_id']]
            );
            
            $vente['details'] = $this->db->fetchAll(
                "SELECT vd.*, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses
                 FROM vente_details vd
                 JOIN produits p ON vd.produit_id = p.id
                 WHERE vd.vente_id = :id",
                ['id' => $id]
            );
        }
        
        return $vente;
    }
    
    /**
     * Récupérer les ventes avec client
     */
    public function getAllWithClient($page = 1, $perPage = 20, $filters = [])
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['client_id'])) {
            $where .= " AND v.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        
        if (!empty($filters['date_debut'])) {
            $where .= " AND v.date_vente >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where .= " AND v.date_vente <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND v.emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} v WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT v.*, c.nom as client_nom, c.telephone as client_telephone,
                       e.nom as emplacement_nom, e.type as emplacement_type
                FROM {$this->table} v
                LEFT JOIN clients c ON v.client_id = c.id
                LEFT JOIN emplacements e ON v.emplacement_id = e.id
                WHERE {$where}
                ORDER BY v.date_vente DESC
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
     * Créer une vente avec détails
     */
    public function createWithDetails($data, $details)
    {
        try {
            $this->db->beginTransaction();
            
            // Créer la vente
            $venteId = $this->create($data);
            
            // Créer les détails et mettre à jour le stock
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $produitModel = new Produit();
            
            foreach ($details as $detail) {
                $detail['vente_id'] = $venteId;
                
                $produit = $produitModel->find($detail['produit_id']);
                $btlParCaisse = (int)($produit['bouteilles_par_caisses'] ?? 24);
                
                // VÉRIFICATION DU STOCK DISPONIBLE
                $currentStock = $this->db->fetch(
                    "SELECT quantite_pleine FROM stocks WHERE produit_id = :p AND emplacement_id = :e",
                    ['p' => $detail['produit_id'], 'e' => $data['emplacement_id']]
                );

                if (!$currentStock || $currentStock['quantite_pleine'] < $detail['quantite']) {
                    $dispo = $currentStock ? ($currentStock['quantite_pleine'] / $btlParCaisse) : 0;
                    throw new Exception("Stock insuffisant pour {$produit['nom']}. Disponible: " . number_format($dispo, 1) . " cs");
                }

                $this->db->insert('vente_details', $detail);
                
                // Déduire du stock (PLEIN)
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $data['emplacement_id'],
                    [
                        'quantite_pleine' => -$detail['quantite'],
                        'caisses_pleine' => -($detail['quantite'] / $btlParCaisse)
                    ]
                );
                
                // Ajouter au stock (VIDE) - Car le client rend les caisses vides
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $data['emplacement_id'],
                    [
                        'quantite_vide' => $detail['quantite'],
                        'caisses_vide' => ($detail['quantite'] / $btlParCaisse)
                    ]
                );
                
                // Enregistrer le mouvement PLEIN (Sortie)
                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => $data['emplacement_id'],
                    'type_mouvement' => 'sortie',
                    'quantite' => -$detail['quantite'],
                    'reference_type' => 'vente',
                    'reference_id' => $venteId,
                    'motif' => 'Vente N° ' . $data['numero_facture'] . ' (Plein)',
                    'created_by' => $data['created_by']
                ]);

                // Enregistrer le mouvement VIDE (Entrée)
                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => $data['emplacement_id'],
                    'type_mouvement' => 'entree',
                    'quantite' => $detail['quantite'],
                    'reference_type' => 'vente',
                    'reference_id' => $venteId,
                    'motif' => 'Retour vide automatique Vente N° ' . $data['numero_facture'],
                    'created_by' => $data['created_by']
                ]);
            }

            // DÉCLENCHER LES ALERTES IMMÉDIATEMENT
            (new Alerte())->checkStockAlerts();
            
            $this->db->commit();
            return ['success' => true, 'id' => $venteId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiques globales de ventes
     */
    public function getStatsGlobales($dateDebut, $dateFin)
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_ventes,
                COALESCE(SUM(total_ttc), 0) as ca_total,
                COALESCE(SUM(total_tva), 0) as tva_total,
                (SELECT COUNT(*) FROM ventes WHERE statut = 'annulee' AND date_vente BETWEEN :d1 AND :d2) as nb_annulees
             FROM {$this->table} 
             WHERE statut = 'validee' AND date_vente BETWEEN :d3 AND :d4",
            ['d1' => $dateDebut, 'd2' => $dateFin, 'd3' => $dateDebut, 'd4' => $dateFin]
        );
    }

    /**
     * Ventes groupées par zone
     */
    public function getVentesParZone($dateDebut, $dateFin)
    {
        return $this->db->fetchAll(
            "SELECT z.nom as zone_nom, COUNT(v.id) as nb_ventes, SUM(v.total_ttc) as total_ca
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             JOIN zones z ON c.zone_id = z.id
             WHERE v.statut = 'validee' AND v.date_vente BETWEEN :d1 AND :d2
             GROUP BY z.id
             ORDER BY total_ca DESC",
            ['d1' => $dateDebut, 'd2' => $dateFin]
        );
    }
    public function annuler($id)
    {
        try {
            $this->db->beginTransaction();
            
            $vente = $this->getWithDetails($id);
            
            // Marquer comme annulée
            $this->update($id, ['statut' => 'annulee']);
            
            // Reverser le stock
            $stockModel = new Stock();
            foreach ($vente['details'] as $detail) {
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $vente['emplacement_id'],
                    [
                        'quantite_pleine' => $detail['quantite']
                    ]
                );
            }
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiques de ventes
     */
    public function getStats($dateDebut, $dateFin, $emplacementId = null)
    {
        $where = "date_vente BETWEEN :date_debut AND :date_fin AND statut = 'validee'";
        $params = [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        
        if ($emplacementId) {
            $where .= " AND emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $emplacementId;
        }
        
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_ventes,
                SUM(total_ht) as total_ht,
                SUM(total_tva) as total_tva,
                SUM(total_ttc) as total_ttc,
                AVG(total_ttc) as moyenne_vente
             FROM {$this->table}
             WHERE {$where}",
            $params
        );
    }
    
    /**
     * Ventes par produit
     */
    public function getVentesParProduit($dateDebut, $dateFin)
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.code, p.nom,
                    SUM(vd.quantite) as quantite_vendue,
                    SUM(vd.sous_total) as total_vente
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.date_vente BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'
             GROUP BY p.id
             ORDER BY quantite_vendue DESC",
            ['date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );
    }
}
