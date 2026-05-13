<?php
/**
 * Modèle ObjectifProduit
 */

class ObjectifProduit extends Model
{
    protected $table = 'objectifs_produits';
    protected $fillable = ['produit_id', 'annee', 'mois', 'objectif_caisses', 'created_by'];

    /**
     * Enregistrer ou mettre à jour l'objectif d'un produit pour un mois donné
     */
    public function saveMonthlyObjective($produitId, $annee, $mois, $objectifCaisses, $createdBy = null)
    {
        $objectifCaisses = max(0, (int) $objectifCaisses);
        $annee = (int) $annee;
        $mois = (int) $mois;

        $existingId = $this->db->fetchColumn(
            "SELECT id
             FROM {$this->table}
             WHERE produit_id = :produit_id AND annee = :annee AND mois = :mois",
            [
                'produit_id' => $produitId,
                'annee' => $annee,
                'mois' => $mois,
            ]
        );

        $data = [
            'produit_id' => (int) $produitId,
            'annee' => $annee,
            'mois' => $mois,
            'objectif_caisses' => $objectifCaisses,
            'created_by' => $createdBy,
        ];

        if ($existingId) {
            return $this->update($existingId, $data);
        }

        return $this->create($data);
    }

    /**
     * Récupérer les objectifs d'un mois avec les ventes réalisées
     */
    public function getMonthlyOverview($annee, $mois)
    {
        $annee = (int) $annee;
        $mois = (int) $mois;

        $rows = $this->db->fetchAll(
            "SELECT p.id as produit_id, p.code, p.nom,
                    COALESCE(o.objectif_caisses, 0) as objectif_caisses,
                    COALESCE(vp.ventes_caisses, 0) as ventes_caisses,
                    CASE
                        WHEN COALESCE(o.objectif_caisses, 0) - COALESCE(vp.ventes_caisses, 0) > 0
                        THEN COALESCE(o.objectif_caisses, 0) - COALESCE(vp.ventes_caisses, 0)
                        ELSE 0
                    END as reste_caisses
             FROM produits p
             LEFT JOIN objectifs_produits o
                ON o.produit_id = p.id
               AND o.annee = :objectif_annee
               AND o.mois = :objectif_mois
             LEFT JOIN (
                SELECT vd.produit_id,
                       SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p2.bouteilles_par_caisses, 0), 24), 0))) as ventes_caisses
                FROM vente_details vd
                JOIN ventes v ON vd.vente_id = v.id
                JOIN produits p2 ON vd.produit_id = p2.id
                WHERE v.statut = 'validee'
                  AND YEAR(v.date_vente) = :vente_annee
                  AND MONTH(v.date_vente) = :vente_mois
                GROUP BY vd.produit_id
             ) vp ON vp.produit_id = p.id
             WHERE p.actif = 1
             ORDER BY p.nom",
            [
                'objectif_annee' => $annee,
                'objectif_mois' => $mois,
                'vente_annee' => $annee,
                'vente_mois' => $mois,
            ]
        );

        $summary = [
            'objectif_total' => 0,
            'vendu_total' => 0,
            'reste_total' => 0,
            'progression' => 0,
            'nb_produits' => count($rows),
        ];

        foreach ($rows as $row) {
            $summary['objectif_total'] += (int) ($row['objectif_caisses'] ?? 0);
            $summary['vendu_total'] += (int) ($row['ventes_caisses'] ?? 0);
            $summary['reste_total'] += (int) ($row['reste_caisses'] ?? 0);
        }

        if ($summary['objectif_total'] > 0) {
            $summary['progression'] = min(100, round(($summary['vendu_total'] / $summary['objectif_total']) * 100, 1));
        }

        return [
            'rows' => $rows,
            'summary' => $summary,
            'annee' => $annee,
            'mois' => $mois,
        ];
    }
}
