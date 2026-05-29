<?php
/**
 * Modele Alerte
 */

class Alerte extends Model
{
    protected $table = 'alertes';
    protected $fillable = ['type', 'titre', 'message', 'produit_id', 'emplacement_id', 'niveau', 'lu'];

    /**
     * Creer une alerte de stock bas.
     */
    public function createStockAlert($produitId, $emplacementId, $quantite, $seuil)
    {
        $produit = (new Produit())->find($produitId);
        $emplacement = (new Emplacement())->find($emplacementId);

        return $this->create([
            'type' => 'stock_bas',
            'titre' => 'Stock bas - ' . $produit['nom'],
            'message' => "Le stock de {$produit['nom']} a {$emplacement['nom']} est de {$quantite} caisse(s) (seuil: {$seuil}).",
            'produit_id' => $produitId,
            'emplacement_id' => $emplacementId,
            'niveau' => $quantite <= 0 ? 'danger' : 'warning'
        ]);
    }

    /**
     * Recuperer les alertes non lues.
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
     * Compter les alertes non lues.
     */
    public function countNonLues()
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE lu = 0 AND resolved_at IS NULL"
        );
    }

    public function marquerLue($id)
    {
        return $this->update($id, ['lu' => 1]);
    }

    public function marquerToutesLues()
    {
        return $this->db->query(
            "UPDATE {$this->table} SET lu = 1 WHERE lu = 0"
        )->rowCount();
    }

    public function resoudre($id)
    {
        return $this->update($id, ['resolved_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Verifier et generer les alertes uniquement pour l'entrepot.
     */
    public function checkStockAlerts()
    {
        $stocks = $this->db->fetchAll(
            "SELECT s.produit_id, s.emplacement_id, s.caisses_pleine, p.seuil_alerte
             FROM stocks s
             JOIN produits p ON p.id = s.produit_id
             JOIN emplacements e ON e.id = s.emplacement_id
             LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
             WHERE p.actif = 1
               AND e.actif = 1
               AND e.type != 'mobile'
               AND v.id IS NULL"
        );

        $alertesGenerees = 0;

        foreach ($stocks as $stock) {
            $quantiteCaisses = (float) ($stock['caisses_pleine'] ?? 0);
            $isLow = $quantiteCaisses <= (float) $stock['seuil_alerte'];

            if ($isLow) {
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
                        $quantiteCaisses,
                        $stock['seuil_alerte']
                    );
                    $alertesGenerees++;
                }
            } else {
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

        $this->db->query(
            "UPDATE {$this->table} a
             JOIN emplacements e ON e.id = a.emplacement_id
             LEFT JOIN vehicules v ON v.emplacement_id = e.id AND v.actif = 1
             SET a.resolved_at = NOW(), a.lu = 1
             WHERE a.type = 'stock_bas'
               AND a.resolved_at IS NULL
               AND (e.type = 'mobile' OR v.id IS NOT NULL)"
        );

        return $alertesGenerees;
    }
}
