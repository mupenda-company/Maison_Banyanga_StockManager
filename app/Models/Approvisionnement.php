<?php
/**
 * Modèle Approvisionnement
 */

class Approvisionnement extends Model
{
    protected $table = 'approvisionnements';
    protected $fillable = ['numero_bon', 'date_approvisionnement', 'fournisseur', 'notes', 'total_ht', 'statut', 'created_by'];
    
    /**
     * Générer un numéro de bon unique
     */
    public function generateNumeroBon()
    {
        $prefix = 'APR-' . date('Ymd');
        $last = $this->db->fetchColumn(
            "SELECT MAX(numero_bon) FROM {$this->table} WHERE numero_bon LIKE :prefix",
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
        $approvisionnement = $this->find($id);
        
        if ($approvisionnement) {
            $approvisionnement['details'] = $this->db->fetchAll(
                "SELECT ad.*, p.nom as produit_nom, p.code as produit_code,
                        p.bouteilles_par_caisses, p.caisses_par_palette,
                        p.prix_achat_deposer, p.prix_achat_enlever,
                        p.prix_vente_unitaire, p.prix_vente_caisses
                 FROM approvisionnement_details ad
                 JOIN produits p ON ad.produit_id = p.id
                 WHERE ad.approvisionnement_id = :id
                 ORDER BY p.position_affichage ASC, p.nom ASC",
                ['id' => $id]
            );
        }
        
        return $approvisionnement;
    }
    
    /**
     * Récupérer tous avec pagination
     */
    public function getAllPaginated($page = 1, $perPage = 5, $filters = [])
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['date_debut'])) {
            $where .= " AND date_approvisionnement >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where .= " AND date_approvisionnement <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        
        if (!empty($filters['statut'])) {
            $where .= " AND statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        
        $offset = ($page - 1) * $perPage;
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE {$where}", $params);

        $data = $this->db->fetchAll(
            "SELECT a.*,
                    COALESCE(SUM(ad.quantite_caisses), 0) as total_quantite_caisses
             FROM {$this->table} a
             LEFT JOIN approvisionnement_details ad ON ad.approvisionnement_id = a.id
             WHERE {$where}
             GROUP BY a.id
             ORDER BY a.date_approvisionnement DESC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }
    
    /**
     * Créer un approvisionnement avec détails
     */
    public function createWithDetails($data, $details, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();
            
            // Créer l'approvisionnement
            $approvisionnementId = $this->create($data);
            
            // Créer les détails et mettre à jour le stock
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();

            $besoinsEmballages = [];
            foreach ($details as $detail) {
                $produitId = (int) ($detail['produit_id'] ?? 0);
                $besoinsEmballages[$produitId] = ($besoinsEmballages[$produitId] ?? 0) + (int) ($detail['quantite_caisses'] ?? 0);
            }

            foreach ($besoinsEmballages as $produitId => $caissesNecessaires) {
                if ($caissesNecessaires <= 0) {
                    continue;
                }

                $stockVide = $stockModel->getStock($produitId, $emplacementPrincipalId);
                $disponible = (int) ($stockVide['caisses_vide'] ?? 0);
                if ($disponible < $caissesNecessaires) {
                    $produit = (new Produit())->find($produitId);
                    $nomProduit = $produit['nom'] ?? ('Produit #' . $produitId);
                    throw new Exception('Emballages insuffisants pour ' . $nomProduit . ' : disponible ' . $disponible . ' cs, demandé ' . $caissesNecessaires . ' cs. Enregistrez un emprunt d\'emballages avant de valider cet approvisionnement.');
                }
            }
            
            foreach ($details as $detail) {
                $detail['approvisionnement_id'] = $approvisionnementId;
                $this->db->insert('approvisionnement_details', $detail);
                
                // Mettre à jour le stock (entrée de pleins)
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => $detail['quantite_bouteilles'],
                        'caisses_pleine' => $detail['quantite_caisses']
                    ]
                );
                
                // Déduire les caisses vides
                $produit = (new Produit())->find($detail['produit_id']);
                $caissesNecessaires = $detail['quantite_caisses'];
                
                $resultDeduction = $stockModel->deduireVide(
                    $detail['produit_id'],
                    $emplacementPrincipalId,
                    $caissesNecessaires
                );
                
                if (!$resultDeduction['success']) {
                    $disponible = (int) ($resultDeduction['disponible'] ?? 0);
                    throw new Exception('Emballages insuffisants pour ' . ($produit['nom'] ?? 'Produit') . ' : disponible ' . $disponible . ' cs, demande ' . $caissesNecessaires . ' cs. Enregistrez d\'abord un emprunt d\'emballages avant de valider cet achat.');
                }
                
                // Enregistrer le mouvement
                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'entree',
                    'quantite' => $detail['quantite_bouteilles'],
                    'quantite_avant' => 0, // Sera calculé
                    'quantite_apres' => 0, // Sera calculé
                    'reference_type' => 'approvisionnement',
                    'reference_id' => $approvisionnementId,
                    'motif' => 'Approvisionnement du ' . $data['date_approvisionnement'],
                    'created_by' => $data['created_by']
                ]);
            }

            // DÉCLENCHER LA RÉSOLUTION DES ALERTES IMMÉDIATEMENT
            (new Alerte())->checkStockAlerts();
            
            $this->db->commit();
            return ['success' => true, 'id' => $approvisionnementId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Valider un approvisionnement
     */
    public function valider($id)
    {
        return $this->update($id, ['statut' => 'valide']);
    }

    public function updateWithDetails($id, $data, $details, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            $ancienAppro = $this->getWithDetails($id);

            if (!$ancienAppro) {
                throw new Exception('Approvisionnement non trouvé');
            }

            if (($ancienAppro['statut'] ?? '') !== 'valide') {
                throw new Exception('Seuls les approvisionnements validés peuvent être modifiés');
            }

            $stockModel = new Stock();

            $ancien = [];
            foreach ($ancienAppro['details'] as $d) {
                $ancien[(int)$d['produit_id']] = [
                    'caisses' => (int)$d['quantite_caisses'],
                    'bouteilles' => (int)$d['quantite_bouteilles']
                ];
            }

            $nouveau = [];
            foreach ($details as $d) {
                $nouveau[(int)$d['produit_id']] = [
                    'caisses' => (int)$d['quantite_caisses'],
                    'bouteilles' => (int)$d['quantite_bouteilles']
                ];
            }

            $produitsIds = array_unique(array_merge(array_keys($ancien), array_keys($nouveau)));

            foreach ($produitsIds as $produitId) {
                $ancienneCaisses = $ancien[$produitId]['caisses'] ?? 0;
                $nouvelleCaisses = $nouveau[$produitId]['caisses'] ?? 0;

                $ancienneBouteilles = $ancien[$produitId]['bouteilles'] ?? 0;
                $nouvelleBouteilles = $nouveau[$produitId]['bouteilles'] ?? 0;

                $diffCaisses = $nouvelleCaisses - $ancienneCaisses;
                $diffBouteilles = $nouvelleBouteilles - $ancienneBouteilles;

                if ($diffCaisses != 0 || $diffBouteilles != 0) {
                    $stockModel->updateOrCreate(
                        $produitId,
                        $emplacementPrincipalId,
                        [
                            'quantite_pleine' => $diffBouteilles,
                            'caisses_pleine' => $diffCaisses
                        ]
                    );
                }
            }

            $this->update($id, [
                'date_approvisionnement' => $data['date_approvisionnement'],
                'fournisseur' => $data['fournisseur'] ?? 'Bralima',
                'notes' => $data['notes'] ?? '',
                'total_ht' => $data['total_ht']
            ]);

            $this->db->query(
                "DELETE FROM approvisionnement_details WHERE approvisionnement_id = :id",
                ['id' => $id]
            );

            foreach ($details as $detail) {
                $detail['approvisionnement_id'] = $id;
                $this->db->insert('approvisionnement_details', $detail);
            }

            (new Alerte())->checkStockAlerts();

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    /**
     * Annuler un approvisionnement
     */
    public function annuler($id, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();
            
            $approvisionnement = $this->getWithDetails($id);
            
            if (!$approvisionnement) {
                throw new Exception('Approvisionnement non trouvé');
            }
            
            // Annuler l'approvisionnement
            $this->update($id, ['statut' => 'annule']);
            
            // Annuler les dettes associées
            $this->db->update('dettes_emballages', ['statut' => 'solde'], 'approvisionnement_id = :id', ['id' => $id]);
            
            // Reverser le stock
            $stockModel = new Stock();
            foreach ($approvisionnement['details'] as $detail) {
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$detail['quantite_bouteilles'],
                        'caisses_pleine' => -$detail['quantite_caisses']
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
}
