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
                 WHERE ad.approvisionnement_id = :id",
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
        
        return $this->paginate($page, $perPage, $where, $params, "date_approvisionnement DESC");
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
            $detteModel = new DetteEmballage();
            
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
                
                // Si stock vide insuffisant, créer une dette
                if (!$resultDeduction['success']) {
                    $detteCaisses = $caissesNecessaires - ($resultDeduction['disponible'] ?? 0);
                    
                    // Déduire ce qui est disponible
                    if (($resultDeduction['disponible'] ?? 0) > 0) {
                        $stockModel->deduireVide(
                            $detail['produit_id'],
                            $emplacementPrincipalId,
                            $resultDeduction['disponible']
                        );
                    }
                    
                    // Créer la dette
                    $detteModel->create([
                        'approvisionnement_id' => $approvisionnementId,
                        'produit_id' => $detail['produit_id'],
                        'quantite_dette_caisses' => $detteCaisses,
                        'statut' => 'en_cours',
                        'notes' => 'Dette générée automatiquement - stock vide insuffisant'
                    ]);
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
