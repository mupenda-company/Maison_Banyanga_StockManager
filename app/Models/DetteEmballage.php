<?php
/**
 * Modèle DetteEmballage
 */

class DetteEmballage extends Model
{
    protected $table = 'dettes_emballages';
    protected $fillable = ['approvisionnement_id', 'produit_id', 'quantite_dette_caisses', 'quantite_remboursee', 'statut', 'notes'];
    
    /**
     * Récupérer les dettes avec détails
     */
    public function getWithDetails($filters = [])
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['statut'])) {
            $where .= " AND d.statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        
        if (!empty($filters['produit_id'])) {
            $where .= " AND d.produit_id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }
        
        return $this->db->fetchAll(
            "SELECT d.*, p.nom as produit_nom, p.code as produit_code,
                    a.numero_bon, a.date_approvisionnement, a.fournisseur
             FROM {$this->table} d
             JOIN produits p ON d.produit_id = p.id
             JOIN approvisionnements a ON d.approvisionnement_id = a.id
             WHERE {$where}
             ORDER BY d.created_at DESC",
            $params
        );
    }
    
    /**
     * Récupérer les dettes en cours
     */
    public function getEnCours()
    {
        return $this->getWithDetails(['statut' => 'en_cours']);
    }
    
    /**
     * Rembourser une dette
     */
    public function rembourser($id, $quantite, $emplacementId)
    {
        try {
            $this->db->beginTransaction();
            
            $dette = $this->find($id);
            
            if (!$dette || $dette['statut'] === 'solde') {
                throw new Exception('Dette non trouvée ou déjà soldée');
            }
            
            $nouvelleQuantiteRemboursee = $dette['quantite_remboursee'] + $quantite;
            $reste = $dette['quantite_dette_caisses'] - $nouvelleQuantiteRemboursee;
            
            // Mettre à jour la dette
            $statut = $reste <= 0 ? 'solde' : 'en_cours';
            $this->update($id, [
                'quantite_remboursee' => $nouvelleQuantiteRemboursee,
                'statut' => $statut
            ]);
            
            // Ajouter au stock de caisses vides
            $stockModel = new Stock();
            $stockModel->updateOrCreate(
                $dette['produit_id'],
                $emplacementId,
                ['caisses_vide' => $quantite]
            );
            
            $this->db->commit();
            return ['success' => true, 'solde' => $reste <= 0];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Total des dettes en cours
     */
    public function getTotalEnCours()
    {
        return $this->db->fetch(
            "SELECT COUNT(*) as nb_dettes, SUM(quantite_dette_caisses - quantite_remboursee) as total_caisses
             FROM {$this->table}
             WHERE statut = 'en_cours'"
        );
    }
<<<<<<< HEAD

    /**
     * Statistiques globales des dettes d'emballages
     */
    public function getStatsGlobales()
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_dettes,
                COALESCE(SUM(quantite_dette_caisses), 0) as caisses_dettes,
                COALESCE(SUM(quantite_remboursee), 0) as caisses_remboursees,
                COALESCE(SUM(quantite_dette_caisses - quantite_remboursee), 0) as caisses_restantes,
                SUM(CASE WHEN statut = 'solde' THEN 1 ELSE 0 END) as nb_soldees
             FROM {$this->table}"
        );
    }
=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
}
