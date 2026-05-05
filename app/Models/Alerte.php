<?php
/**
 * Modèle Alerte
 */

class Alerte extends Model
{
    protected $table = 'alertes';
    protected $fillable = ['type', 'titre', 'message', 'produit_id', 'emplacement_id', 'niveau', 'lu'];
    
    /**
     * Créer une alerte de stock bas
     */
    public function createStockAlert($produitId, $emplacementId, $quantite, $seuil)
    {
        $produit = (new Produit())->find($produitId);
        $emplacement = (new Emplacement())->find($emplacementId);
        
        return $this->create([
            'type' => 'stock_bas',
            'titre' => 'Stock bas - ' . $produit['nom'],
            'message' => "Le stock de {$produit['nom']} à {$emplacement['nom']} est de {$quantite} unités (seuil: {$seuil}).",
            'produit_id' => $produitId,
            'emplacement_id' => $emplacementId,
            'niveau' => $quantite <= 0 ? 'danger' : 'warning'
        ]);
    }
    
    /**
     * Récupérer les alertes non lues
     */
    public function getNonLues($limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT a.*, p.nom as produit_nom, e.nom as emplacement_nom
             FROM {$this->table} a
             LEFT JOIN produits p ON a.produit_id = p.id
             LEFT JOIN emplacements e ON a.emplacement_id = e.id
             WHERE a.lu = 0 AND a.resolved_at IS NULL
             ORDER BY a.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Compter les alertes non lues
     */
    public function countNonLues()
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE lu = 0 AND resolved_at IS NULL"
        );
    }
    
    /**
     * Marquer comme lue
     */
    public function marquerLue($id)
    {
        return $this->update($id, ['lu' => 1]);
    }
    
    /**
     * Marquer toutes comme lues
     */
    public function marquerToutesLues()
    {
        return $this->db->query(
            "UPDATE {$this->table} SET lu = 1 WHERE lu = 0"
        )->rowCount();
    }
    
    /**
     * Résoudre une alerte
     */
    public function resoudre($id)
    {
        return $this->update($id, ['resolved_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Vérifier et générer les alertes de stock
     */
    public function checkStockAlerts()
    {
        $stockModel = new Stock();
        // On récupère TOUS les stocks (sans pagination pour le check)
        $result = $stockModel->getAllPaginated(1, 1000);
        $stocks = $result['data'];
        
        $alertesGenerees = 0;
        
        foreach ($stocks as $stock) {
            $isLow = (float)$stock['quantite_pleine'] <= (float)$stock['seuil_alerte'];
            
            if ($isLow) {
                // Vérifier si une alerte active existe déjà
                $exists = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM {$this->table} 
                     WHERE type = 'stock_bas' 
                     AND produit_id = :produit_id 
                     AND emplacement_id = :emplacement_id 
                     AND resolved_at IS NULL",
                    ['produit_id' => $stock['produit_id'], 'emplacement_id' => $stock['emplacement_id']]
                );
                
                if (!$exists) {
                    $this->createStockAlert(
                        $stock['produit_id'],
                        $stock['emplacement_id'],
                        $stock['quantite_pleine'],
                        $stock['seuil_alerte']
                    );
                    $alertesGenerees++;
                }
            } else {
                // IMPORTANT : Si le stock est OK, on résout TOUTES les alertes en cours pour ce produit
                $this->db->query(
                    "UPDATE {$this->table} 
                     SET resolved_at = NOW(), lu = 1 
                     WHERE type = 'stock_bas' 
                     AND produit_id = :produit_id 
                     AND emplacement_id = :emplacement_id 
                     AND resolved_at IS NULL",
                    ['produit_id' => $stock['produit_id'], 'emplacement_id' => $stock['emplacement_id']]
                );
            }
        }
        
        return $alertesGenerees;
    }
}
