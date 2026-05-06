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
     * Statistiques des retours d'emballages sur une période
     */
    public function getStats($dateDebut, $dateFin, $limit = 5)
    {
        $dateFin = str_contains($dateFin, ':') ? $dateFin : ($dateFin . ' 23:59:59');

        $resume = $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_retours,
                COUNT(DISTINCT r.client_id) as nb_clients,
                COUNT(DISTINCT r.produit_id) as nb_produits,
                COALESCE(SUM(r.quantite), 0) as total_bouteilles,
                COALESCE(SUM(ROUND(r.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0) as total_caisses
             FROM {$this->table} r
             JOIN produits p ON r.produit_id = p.id
             WHERE r.date_retour BETWEEN :date_debut AND :date_fin",
            [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ]
        ) ?: [
            'nb_retours' => 0,
            'nb_clients' => 0,
            'nb_produits' => 0,
            'total_bouteilles' => 0,
            'total_caisses' => 0,
        ];

        $topProduits = $this->db->fetchAll(
            "SELECT p.id, p.nom, p.code, p.bouteilles_par_caisses,
                    COUNT(r.id) as nb_retours,
                    COALESCE(SUM(r.quantite), 0) as total_bouteilles,
                    COALESCE(SUM(ROUND(r.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0) as total_caisses
             FROM {$this->table} r
             JOIN produits p ON r.produit_id = p.id
             WHERE r.date_retour BETWEEN :date_debut AND :date_fin
             GROUP BY p.id
             ORDER BY total_caisses DESC, total_bouteilles DESC
             LIMIT :limit",
            [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'limit' => (int) $limit,
            ]
        );

        return [
            'resume' => $resume,
            'top_produits' => $topProduits,
        ];
    }

    /**
     * Récupérer les derniers retours avec détails
     */
    public function getRecents($limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT r.*, c.nom as client_nom, p.nom as produit_nom, p.bouteilles_par_caisses, e.nom as emplacement_nom
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
