<?php
/**
 * Modèle Depense
 */

class Depense extends Model
{
    protected $table = 'depenses';
    protected $fillable = ['categorie', 'description', 'montant', 'date_depense', 'created_by'];

    /**
     * Récupérer avec le nom de l'utilisateur
     */
    public function getWithUser($id)
    {
        return $this->db->fetch(
            "SELECT d.*, u.nom as created_by_nom, u.prenom as created_by_prenom
             FROM {$this->table} d
             LEFT JOIN users u ON d.created_by = u.id
             WHERE d.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupérer toutes les dépenses avec filtres
     */
    public function getAllWithFilters($filters = [])
    {
        $where = "1=1";
        $params = [];

        if (!empty($filters['categorie'])) {
            $where .= " AND d.categorie = :categorie";
            $params['categorie'] = $filters['categorie'];
        }

        if (!empty($filters['date_debut'])) {
            $where .= " AND d.date_depense >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where .= " AND d.date_depense <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }

        return $this->db->fetchAll(
            "SELECT d.*, u.nom as created_by_nom, u.prenom as created_by_prenom
             FROM {$this->table} d
             LEFT JOIN users u ON d.created_by = u.id
             WHERE {$where}
             ORDER BY d.date_depense DESC, d.id DESC",
            $params
        );
    }

    /**
     * Statistiques des dépenses
     */
    public function getStats($dateDebut, $dateFin)
    {
        return $this->db->fetch(
            "SELECT COUNT(*) as nb_depenses,
                    COALESCE(SUM(montant), 0) as total_depenses
             FROM {$this->table}
             WHERE date_depense BETWEEN :date_debut AND :date_fin",
            ['date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );
    }

    /**
     * Dépenses par catégorie
     */
    public function getByCategorie($dateDebut = null, $dateFin = null)
    {
        $where = "1=1";
        $params = [];

        if ($dateDebut && $dateFin) {
            $where .= " AND date_depense BETWEEN :date_debut AND :date_fin";
            $params['date_debut'] = $dateDebut;
            $params['date_fin'] = $dateFin;
        }

        return $this->db->fetchAll(
            "SELECT categorie, COUNT(*) as nb, SUM(montant) as total
             FROM {$this->table}
             WHERE {$where}
             GROUP BY categorie
             ORDER BY total DESC",
            $params
        );
    }
}
