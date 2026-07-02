<?php
/**
 * Modele ObjectifProduit
 */

class ObjectifProduit extends Model
{
    protected $table = 'objectifs_produits';
    protected $fillable = ['produit_id', 'annee', 'mois', 'type_objectif', 'objectif_caisses', 'created_by'];

    private function normalizeType($type)
    {
        return $type === 'approvisionnement' ? 'approvisionnement' : 'vente';
    }

    /**
     * Enregistrer ou mettre a jour l'objectif d'un produit pour un mois donne.
     */
    public function saveMonthlyObjective($produitId, $annee, $mois, $objectifCaisses, $createdBy = null, $type = 'vente')
    {
        $objectifCaisses = max(0, (int) $objectifCaisses);
        $annee = (int) $annee;
        $mois = (int) $mois;
        $type = $this->normalizeType($type);

        $existingId = $this->db->fetchColumn(
            "SELECT id
             FROM {$this->table}
             WHERE produit_id = :produit_id
               AND annee = :annee
               AND mois = :mois
               AND type_objectif = :type_objectif",
            [
                'produit_id' => $produitId,
                'annee' => $annee,
                'mois' => $mois,
                'type_objectif' => $type,
            ]
        );

        $data = [
            'produit_id' => (int) $produitId,
            'annee' => $annee,
            'mois' => $mois,
            'type_objectif' => $type,
            'objectif_caisses' => $objectifCaisses,
            'created_by' => $createdBy,
        ];

        if ($existingId) {
            return $this->update($existingId, $data);
        }

        return $this->create($data);
    }

    /**
     * Recuperer les objectifs d'un mois avec les ventes ou approvisionnements realises.
     */
    public function getMonthlyOverview($annee, $mois, $type = 'vente')
    {
        $annee = (int) $annee;
        $mois = (int) $mois;
        $type = $this->normalizeType($type);

        if ($type === 'approvisionnement') {
            $realiseSelect = 'COALESCE(vp.approvisionnement_caisses, 0) as realise_caisses';
            $realiseAlias = 'approvisionnement_caisses';
            $realiseJoin = "LEFT JOIN (
                SELECT ad.produit_id,
                       SUM(COALESCE(ad.quantite_caisses, 0)) as approvisionnement_caisses
                FROM approvisionnement_details ad
                JOIN approvisionnements a ON ad.approvisionnement_id = a.id
                WHERE a.statut = 'valide'
                  AND YEAR(a.date_approvisionnement) = :realise_annee
                  AND MONTH(a.date_approvisionnement) = :realise_mois
                GROUP BY ad.produit_id
             ) vp ON vp.produit_id = p.id";
        } else {
            $realiseSelect = 'COALESCE(vp.ventes_caisses, 0) as realise_caisses';
            $realiseAlias = 'ventes_caisses';
            $realiseJoin = "LEFT JOIN (
                SELECT vd.produit_id,
                       SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p2.bouteilles_par_caisses, 0), 24), 0))) as ventes_caisses
                FROM vente_details vd
                JOIN ventes v ON vd.vente_id = v.id
                JOIN produits p2 ON vd.produit_id = p2.id
                WHERE v.statut = 'validee'
                  AND YEAR(v.date_vente) = :realise_annee
                  AND MONTH(v.date_vente) = :realise_mois
                GROUP BY vd.produit_id
             ) vp ON vp.produit_id = p.id";
        }

        $rows = $this->db->fetchAll(
            "SELECT p.id as produit_id, p.code, p.nom,
                    COALESCE(o.objectif_caisses, 0) as objectif_caisses,
                    {$realiseSelect},
                    CASE
                        WHEN COALESCE(o.objectif_caisses, 0) - COALESCE(vp.{$realiseAlias}, 0) > 0
                        THEN COALESCE(o.objectif_caisses, 0) - COALESCE(vp.{$realiseAlias}, 0)
                        ELSE 0
                    END as reste_caisses
             FROM produits p
             LEFT JOIN objectifs_produits o
                ON o.produit_id = p.id
               AND o.annee = :objectif_annee
               AND o.mois = :objectif_mois
               AND o.type_objectif = :type_objectif
             {$realiseJoin}
             WHERE p.actif = 1
             ORDER BY p.position_affichage ASC, p.nom ASC",
            [
                'objectif_annee' => $annee,
                'objectif_mois' => $mois,
                'type_objectif' => $type,
                'realise_annee' => $annee,
                'realise_mois' => $mois,
            ]
        );

        $summary = [
            'objectif_total' => 0,
            'realise_total' => 0,
            'vendu_total' => 0,
            'approvisionnement_total' => 0,
            'reste_total' => 0,
            'progression' => 0,
            'nb_produits' => count($rows),
        ];

        foreach ($rows as &$row) {
            $row['type_objectif'] = $type;
            $row['realise_caisses'] = (int) ($row['realise_caisses'] ?? 0);
            $row['ventes_caisses'] = $type === 'vente' ? $row['realise_caisses'] : 0;
            $row['approvisionnement_caisses'] = $type === 'approvisionnement' ? $row['realise_caisses'] : 0;

            $summary['objectif_total'] += (int) ($row['objectif_caisses'] ?? 0);
            $summary['realise_total'] += $row['realise_caisses'];
            $summary['reste_total'] += (int) ($row['reste_caisses'] ?? 0);
        }
        unset($row);

        if ($type === 'vente') {
            $summary['vendu_total'] = $summary['realise_total'];
        } else {
            $summary['approvisionnement_total'] = $summary['realise_total'];
        }

        if ($summary['objectif_total'] > 0) {
            $summary['progression'] = min(100, round(($summary['realise_total'] / $summary['objectif_total']) * 100, 1));
        }

        return [
            'rows' => $rows,
            'summary' => $summary,
            'annee' => $annee,
            'mois' => $mois,
            'type' => $type,
        ];
    }
}
