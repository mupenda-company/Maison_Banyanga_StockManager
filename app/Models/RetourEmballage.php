<?php
/**
 * Modèle RetourEmballage
 */

class RetourEmballage extends Model
{
    protected $table = 'retours_emballages';
    protected $fillable = ['client_id', 'produit_id', 'quantite', 'date_retour', 'emplacement_id', 'created_by'];

    /**
     * Enregistrer un retour et mettre à jour le stock vide
     */
    public function enregistrer($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Insérer le retour
            $id = $this->create($data);

            // 2. Mettre à jour le stock (quantite_vide)
            $stockModel = new Stock();
            $stockModel->updateOrCreate(
                $data['produit_id'],
                $data['emplacement_id'],
                ['quantite_vide' => $data['quantite']] // Additionne au stock vide existant
            );

            // 3. Enregistrer le mouvement de stock
            $mouvementModel = new MouvementStock();
            $mouvementModel->create([
                'produit_id' => $data['produit_id'],
                'emplacement_id' => $data['emplacement_id'],
                'type_mouvement' => 'entree',
                'quantite' => $data['quantite'],
                'reference_type' => 'retour_emballage',
                'reference_id' => $id,
                'motif' => 'Retour emballages client',
                'created_by' => $data['created_by']
            ]);

            $this->db->commit();
            return ['success' => true, 'id' => $id];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Récupérer les derniers retours avec détails
     */
    public function getRecents($limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT r.*, c.nom as client_nom, p.nom as produit_nom, e.nom as emplacement_nom
             FROM {$this->table} r
             JOIN clients c ON r.client_id = c.id
             JOIN produits p ON r.produit_id = p.id
             JOIN emplacements e ON r.emplacement_id = e.id
             ORDER BY r.date_retour DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
