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
        $client = $this->db->fetch(
            "SELECT taux_ristourne, nom, prenom
             FROM clients
             WHERE id = :client_id",
            ['client_id' => $clientId]
        );

        // 1. Calculer le total de caisses livrées (ventes validées)
        $sql = "SELECT COALESCE(SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0) as total_caisses,
                       COALESCE(SUM(vd.sous_total), 0) as total_ca
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

        $totalCaisses = (int)($result['total_caisses'] ?? 0);
        $totalCA = (float)($result['total_ca'] ?? 0);
        
        if ($totalCA <= 0) return null;

        $tauxRistourne = (float) ($client['taux_ristourne'] ?? 5);
        $montantRistourne = ($totalCA * $tauxRistourne) / 100;

        $palierNom = abs($tauxRistourne - 5.0) < 0.0001 ? 'Standard' : 'Spécial';

        return [
            'client_id' => $clientId,
            'periode_debut' => "$annee-$mois-01",
            'periode_fin' => date('Y-m-t', strtotime("$annee-$mois-01")),
            'total_caisses' => $totalCaisses,
            'ca_total' => $totalCA,
            'palier_id' => null,
            'palier_nom' => $palierNom,
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
