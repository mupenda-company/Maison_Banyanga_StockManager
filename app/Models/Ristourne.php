<?php
/**
 * Modèle Ristourne
 */

class Ristourne extends Model
{
    protected $table = 'ristournes';
    protected $fillable = [
        'client_id', 'periode_debut', 'periode_fin', 
        'ca_total', 'palier_id', 'taux_applique', 
        'montant_ristourne', 'statut', 'date_paiement', 'notes'
    ];

    /**
     * Récupérer les paliers de ristourne
     */
    public function getPaliers()
    {
        return $this->db->fetchAll("SELECT * FROM paliers_ristourne WHERE actif = 1 ORDER BY ca_min ASC");
    }

    /**
     * Calculer la ristourne pour un client sur un mois donné
     */
    public function calculerRistourne($clientId, $mois, $annee)
    {
        // 1. Calculer le total de caisses livrées (ventes validées)
        $sql = "SELECT SUM(vd.quantite / p.bouteilles_par_caisses) as total_caisses,
                       SUM(vd.sous_total) as total_ca
                FROM vente_details vd
                JOIN ventes v ON vd.vente_id = v.id
                JOIN produits p ON vd.produit_id = p.id
                WHERE v.client_id = :client_id 
                AND MONTH(v.date_vente) = :mois 
                AND YEAR(v.date_vente) = :annee
                AND v.statut = 'validee'";
        
        $result = $this->db->fetch($sql, [
            'client_id' => $clientId,
            'mois' => $mois,
            'annee' => $annee
        ]);

        $totalCaisses = (float)($result['total_caisses'] ?? 0);
        $totalCA = (float)($result['total_ca'] ?? 0);
        
        if ($totalCA <= 0) return null;

        // 2. Trouver le palier correspondant (basé sur le CA total)
        $palierSql = "SELECT * 
                      FROM paliers_ristourne 
                      WHERE actif = 1 
                      AND :ca >= ca_min 
                      AND (:ca2 < ca_max OR ca_max IS NULL)
                      ORDER BY ca_min DESC LIMIT 1";
        
        $palier = $this->db->fetch($palierSql, [
            'ca' => $totalCA,
            'ca2' => $totalCA
        ]);

        if (!$palier) return null;

        $tauxRistourne = (float)$palier['taux_ristourne'];
        $montantRistourne = ($totalCA * $tauxRistourne) / 100;

        return [
            'client_id' => $clientId,
            'periode_debut' => "$annee-$mois-01",
            'periode_fin' => date('Y-m-t', strtotime("$annee-$mois-01")),
            'total_caisses' => $totalCaisses,
            'ca_total' => $totalCA,
            'palier_id' => $palier['id'],
            'taux_applique' => $tauxRistourne,
            'montant_ristourne' => $montantRistourne
        ];
    }

    /**
     * Récupérer l'historique des ristournes avec détails clients
     */
    public function getAllWithDetails($filters = [])
    {
        $where = "1=1";
        $params = [];

        if (!empty($filters['client_id'])) {
            $where .= " AND r.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        return $this->db->fetchAll(
            "SELECT r.*, c.nom as client_nom
             FROM {$this->table} r
             JOIN clients c ON r.client_id = c.id
             WHERE {$where}
             ORDER BY r.id DESC",
            $params
        );
    }

    /**
     * Marquer comme payée
     */
    public function marquerPayee($id)
    {
        return $this->update($id, [
            'statut' => 'payee',
            'date_paiement' => date('Y-m-d H:i:s')
        ]);
    }
}
