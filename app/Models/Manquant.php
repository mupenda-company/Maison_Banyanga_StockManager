<?php

class Manquant extends Model
{
    protected $table = 'manquants_agents';
    protected $fillable = [
        'agent_id', 'produit_id', 'quantite_caisses', 'montant', 'montant_paye',
        'date_manquant', 'date_reglement', 'motif', 'notes_reglement', 'statut', 'created_by'
    ];

    public function getWithDetails($filters = [])
    {
        $where = '1=1';
        $params = [];
        foreach (['agent_id', 'produit_id'] as $field) {
            if (!empty($filters[$field])) {
                $where .= " AND m.{$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }
        if (!empty($filters['statut'])) {
            if ($filters['statut'] === 'paye') {
                $where .= " AND m.statut IN ('paye', 'regle')";
            } else {
                $where .= " AND m.statut = :statut";
                $params['statut'] = $filters['statut'];
            }
        }
        if (!empty($filters['date_debut'])) {
            $where .= ' AND m.date_manquant >= :date_debut';
            $params['date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $where .= ' AND m.date_manquant <= :date_fin';
            $params['date_fin'] = $filters['date_fin'];
        }

        return $this->db->fetchAll(
            "SELECT m.*, CONCAT(a.prenom, ' ', a.nom) AS agent_nom, p.nom AS produit_nom,
                    p.code AS produit_code, CONCAT(u.prenom, ' ', u.nom) AS createur_nom,
                    GREATEST(COALESCE(m.montant, 0) - COALESCE(m.montant_paye, 0), 0) AS reste_montant
             FROM manquants_agents m
             JOIN users a ON a.id = m.agent_id
             LEFT JOIN produits p ON p.id = m.produit_id
             LEFT JOIN users u ON u.id = m.created_by
             WHERE {$where}
             ORDER BY m.date_manquant DESC, a.nom, a.prenom",
            $params
        );
    }

    public function getSummaryByAgent($filters = [])
    {
        $where = '1=1';
        $params = [];
        if (!empty($filters['agent_id'])) { $where .= ' AND m.agent_id = :agent_id'; $params['agent_id'] = $filters['agent_id']; }
        if (!empty($filters['date_debut'])) { $where .= ' AND m.date_manquant >= :date_debut'; $params['date_debut'] = $filters['date_debut']; }
        if (!empty($filters['date_fin'])) { $where .= ' AND m.date_manquant <= :date_fin'; $params['date_fin'] = $filters['date_fin']; }

        return $this->db->fetchAll(
            "SELECT m.agent_id, CONCAT(a.prenom, ' ', a.nom) AS agent_nom, COUNT(*) AS nombre,
                    COALESCE(SUM(m.quantite_caisses), 0) AS total_caisses,
                    COALESCE(SUM(m.montant), 0) AS total_montant,
                    COALESCE(SUM(m.montant_paye), 0) AS total_paye,
                    COALESCE(SUM(GREATEST(m.montant - m.montant_paye, 0)), 0) AS total_reste
             FROM manquants_agents m
             JOIN users a ON a.id = m.agent_id
             WHERE {$where}
             GROUP BY m.agent_id, a.prenom, a.nom
             ORDER BY total_reste DESC, total_montant DESC",
            $params
        );
    }

    public function enregistrerPaiement($id, $montant, $datePaiement, $note, $createdBy)
    {
        $montant = round(max(0, (float) $montant), 2);
        if ($montant <= 0) {
            return ['success' => false, 'message' => 'Le montant payé doit être supérieur à 0.'];
        }

        try {
            $this->db->beginTransaction();
            $manquant = $this->find($id);
            if (!$manquant) {
                throw new Exception('Manquant introuvable.');
            }

            $total = (float) ($manquant['montant'] ?? 0);
            $dejaPaye = (float) ($manquant['montant_paye'] ?? 0);
            $nouveauPaye = $total > 0 ? min($total, $dejaPaye + $montant) : ($dejaPaye + $montant);
            $reste = max(0, $total - $nouveauPaye);
            $statut = $reste <= 0.01 ? 'paye' : ($nouveauPaye > 0 ? 'partiel' : 'ouvert');

            $this->db->insert('manquant_paiements', [
                'manquant_id' => $id,
                'montant' => $montant,
                'date_paiement' => $datePaiement,
                'note' => $note,
                'created_by' => $createdBy
            ]);

            $this->update($id, [
                'montant_paye' => $nouveauPaye,
                'date_reglement' => $statut === 'paye' ? $datePaiement : null,
                'notes_reglement' => $note,
                'statut' => $statut
            ]);

            $this->db->commit();
            return ['success' => true, 'reste' => $reste, 'statut' => $statut];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}