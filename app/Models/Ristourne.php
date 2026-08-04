<?php
/**
 * Modèle Ristourne
 */

class Ristourne extends Model
{
    protected $table = 'ristournes';
    protected $fillable = [
        'client_id', 'periode_debut', 'periode_fin', 'total_caisses',
        'ca_total', 'palier_id', 'taux_applique', 
        'montant_ristourne', 'produits_ristourne', 'produit_complement_id',
        'statut', 'date_paiement', 'notes'
    ];

    private static bool $columnsChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureRistourneColumns();
    }

    private function ensureRistourneColumns(): void
    {
        if (self::$columnsChecked) {
            return;
        }

        $columns = [
            'total_caisses' => "ALTER TABLE ristournes ADD total_caisses INT NOT NULL DEFAULT 0 AFTER periode_fin",
            'produits_ristourne' => "ALTER TABLE ristournes ADD produits_ristourne TEXT NULL AFTER montant_ristourne",
            'produit_complement_id' => "ALTER TABLE ristournes ADD produit_complement_id INT UNSIGNED NULL AFTER produits_ristourne",
        ];

        foreach ($columns as $column => $sql) {
            $exists = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ristournes'
                   AND COLUMN_NAME = :column",
                ['column' => $column]
            );

            if (!$exists) {
                $this->db->query($sql);
            }
        }

        self::$columnsChecked = true;
    }

    /**
     * Récupérer les paliers de ristourne
     */
    public function getPaliers()
    {
        return $this->db->fetchAll("SELECT * FROM paliers_ristourne WHERE actif = 1 ORDER BY ca_min ASC");
    }

    /**
     * Calculer la deduction locale par caisse selon le palier
     * - < 5 caisses: 0 CDF/caisse
     * - 5-200 caisses: 100 CDF/caisse
     * - 201-500 caisses: 200 CDF/caisse
     * - 501+ caisses: 250 CDF/caisse
     */
    public function calculerDeductionLocale($totalCaisses)
    {
        $totalCaisses = (int) $totalCaisses;

        // La recolte locale est volontairement desactivee lorsque la constante
        // n'existe pas. Il suffit donc de commenter sa ligne dans config.php.
        if (!defined('APPLIQUER_RECOLTE_LOCALE') || APPLIQUER_RECOLTE_LOCALE !== true) {
            return [
                'active' => false,
                'taux_local' => 0,
                'deduction_locale' => 0,
                'palier_local' => 'Desactivee'
            ];
        }

        if ($totalCaisses < 5) {
            return ['active' => true, 'taux_local' => 0, 'deduction_locale' => 0, 'palier_local' => 'Aucun'];
        } elseif ($totalCaisses <= 200) {
            $taux = 100;
            $palier = '5-200 cs';
        } elseif ($totalCaisses <= 500) {
            $taux = 200;
            $palier = '201-500 cs';
        } else {
            $taux = 250;
            $palier = '501+ cs';
        }
        return [
            'active' => true,
            'taux_local' => $taux,
            'deduction_locale' => $taux * $totalCaisses,
            'palier_local' => $palier
        ];
    }

    /**
     * Calculer la ristourne pour un client sur un mois donné
     */
    public function calculerRistourne($clientId, $mois, $annee)
    {
        $client = $this->db->fetch(
            "SELECT taux_ristourne, nom
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

        // Deduction locale
        $deductionLocale = $this->calculerDeductionLocale($totalCaisses);
        $caTotalApresRecolte = max(0, $totalCA - $deductionLocale['deduction_locale']);
        $montantRistourneNet = ($caTotalApresRecolte * $tauxRistourne) / 100;

        return [
            'client_id' => $clientId,
            'periode_debut' => "$annee-$mois-01",
            'periode_fin' => date('Y-m-t', strtotime("$annee-$mois-01")),
            'total_caisses' => $totalCaisses,
            'ca_total' => $totalCA,
            'palier_id' => null,
            'palier_nom' => $palierNom,
            'taux_applique' => $tauxRistourne,
            'montant_ristourne' => $montantRistourne,
            'taux_local' => $deductionLocale['taux_local'],
            'deduction_locale' => $deductionLocale['deduction_locale'],
            'palier_local' => $deductionLocale['palier_local'],
            'recolte_locale_active' => $deductionLocale['active'],
            'ca_total_apres_recolte' => $caTotalApresRecolte,
            'montant_ristourne_net' => $montantRistourneNet
        ];
    }

    /**
     * Récupérer l'historique des ristournes avec détails clients
     */
    public function getAllWithDetails($filters = [])
    {
        $where = "COALESCE(r.statut, '') != 'annulee'";
        $params = [];

        if (!empty($filters['client_id'])) {
            $where .= " AND r.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['mois']) && !empty($filters['annee'])) {
            $where .= " AND MONTH(r.periode_debut) = :mois AND YEAR(r.periode_debut) = :annee";
            $params['mois'] = (int) $filters['mois'];
            $params['annee'] = (int) $filters['annee'];
        }

        return $this->db->fetchAll(
            "SELECT r.*, c.nom as client_nom, c.numero_client, z.nom as zone_nom,
                    pc.nom as produit_complement_nom
             FROM {$this->table} r
             JOIN clients c ON r.client_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             LEFT JOIN produits pc ON pc.id = r.produit_complement_id
             WHERE {$where}
             ORDER BY z.nom ASC, c.nom ASC, r.id DESC",
            $params
        );
    }

    /**
     * Corriger les ristournes restees "en_livraison" alors qu'aucune mission
     * de ristourne en cours ne les porte encore.
     */
    public function synchroniserStatutsLivraison()
    {
        $bloquees = $this->db->fetchAll(
            "SELECT r.id,
                    COALESCE(SUM(CASE WHEN m.statut = 'en_cours' THEN 1 ELSE 0 END), 0) AS missions_en_cours,
                    COALESCE(SUM(CASE WHEN m.statut = 'terminee' AND mr.statut = 'livree' THEN 1 ELSE 0 END), 0) AS lignes_livrees_terminees,
                    MAX(CASE WHEN m.statut = 'terminee' AND mr.statut = 'livree' THEN COALESCE(m.date_retour, m.updated_at, m.created_at) ELSE NULL END) AS date_livraison
             FROM {$this->table} r
             LEFT JOIN mission_ristournes mr ON mr.ristourne_id = r.id
             LEFT JOIN missions m ON m.id = mr.mission_id
             WHERE r.statut = 'en_livraison'
             GROUP BY r.id"
        );

        $corrigees = 0;
        foreach ($bloquees as $row) {
            if ((int) ($row['missions_en_cours'] ?? 0) > 0) {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if ((int) ($row['lignes_livrees_terminees'] ?? 0) > 0) {
                $corrigees += (int) $this->db->update($this->table, [
                    'statut' => 'payee',
                    'date_paiement' => $row['date_livraison'] ?: date('Y-m-d H:i:s')
                ], 'id = :where_id', ['where_id' => $id]);
            } else {
                $corrigees += (int) $this->db->update($this->table, [
                    'statut' => 'calculee',
                    'date_paiement' => null
                ], 'id = :where_id', ['where_id' => $id]);
            }
        }

        return $corrigees;
    }

    /**
     * Marquer comme payÃ©e
     */
    public function marquerPayee($id)
    {
        return $this->update($id, [
            'statut' => 'payee',
            'date_paiement' => date('Y-m-d H:i:s')
        ]);
    }
}
