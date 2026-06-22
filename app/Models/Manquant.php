<?php

class Manquant extends Model
{
    protected $table = 'manquants_agents';
    protected $fillable = [
        'agent_id', 'mission_id', 'type_manquant', 'produit_id',
        'quantite_caisses', 'quantite_caisses_reglee', 'quantite_emballages', 'quantite_emballages_reglee',
        'montant', 'montant_cdf', 'montant_usd',
        'montant_paye', 'montant_paye_cdf', 'montant_paye_usd',
        'date_manquant', 'date_reglement', 'motif', 'notes_reglement',
        'statut', 'created_by'
    ];

    private static bool $columnsChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureColumns();
    }

    private function ensureColumns(): void
    {
        if (self::$columnsChecked) {
            return;
        }

        $columns = [
            'manquants_agents' => [
                'mission_id' => "ALTER TABLE manquants_agents ADD mission_id INT UNSIGNED NULL AFTER agent_id",
                'type_manquant' => "ALTER TABLE manquants_agents ADD type_manquant VARCHAR(30) NOT NULL DEFAULT 'manuel' AFTER mission_id",
                'quantite_caisses_reglee' => "ALTER TABLE manquants_agents ADD quantite_caisses_reglee DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantite_caisses",
                'quantite_emballages' => "ALTER TABLE manquants_agents ADD quantite_emballages DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantite_caisses_reglee",
                'quantite_emballages_reglee' => "ALTER TABLE manquants_agents ADD quantite_emballages_reglee DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantite_emballages",
                'montant_cdf' => "ALTER TABLE manquants_agents ADD montant_cdf DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant",
                'montant_usd' => "ALTER TABLE manquants_agents ADD montant_usd DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_cdf",
                'montant_paye_cdf' => "ALTER TABLE manquants_agents ADD montant_paye_cdf DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_paye",
                'montant_paye_usd' => "ALTER TABLE manquants_agents ADD montant_paye_usd DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_paye_cdf",
            ],
            'manquant_paiements' => [
                'montant_cdf' => "ALTER TABLE manquant_paiements ADD montant_cdf DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant",
                'montant_usd' => "ALTER TABLE manquant_paiements ADD montant_usd DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_cdf",
                'quantite_caisses_reglee' => "ALTER TABLE manquant_paiements ADD quantite_caisses_reglee DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER montant_usd",
                'quantite_emballages_reglee' => "ALTER TABLE manquant_paiements ADD quantite_emballages_reglee DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantite_caisses_reglee",
            ],
        ];

        foreach ($columns as $table => $defs) {
            $tableExists = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name",
                ['table_name' => $table]
            );

            if (!$tableExists && $table === 'manquant_paiements') {
                $this->db->query(
                    "CREATE TABLE manquant_paiements (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        manquant_id INT UNSIGNED NOT NULL,
                        montant DECIMAL(15,2) NOT NULL DEFAULT 0,
                        montant_cdf DECIMAL(15,2) NOT NULL DEFAULT 0,
                        montant_usd DECIMAL(15,2) NOT NULL DEFAULT 0,
                        quantite_caisses_reglee DECIMAL(12,2) NOT NULL DEFAULT 0,
                        quantite_emballages_reglee DECIMAL(12,2) NOT NULL DEFAULT 0,
                        date_paiement DATE NOT NULL,
                        note TEXT NULL,
                        created_by INT UNSIGNED NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                );
                continue;
            }

            if (!$tableExists) {
                continue;
            }

            foreach ($defs as $column => $sql) {
                $exists = (bool) $this->db->fetchColumn(
                    "SELECT COUNT(*)
                     FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = :table_name
                       AND COLUMN_NAME = :column_name",
                    ['table_name' => $table, 'column_name' => $column]
                );

                if (!$exists) {
                    $this->db->query($sql);
                }
            }
        }

        self::$columnsChecked = true;
    }

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
                    GREATEST(COALESCE(m.montant, 0) - COALESCE(m.montant_paye, 0), 0) AS reste_montant,
                    GREATEST(COALESCE(m.quantite_caisses, 0) - COALESCE(m.quantite_caisses_reglee, 0), 0) AS reste_caisses,
                    GREATEST(COALESCE(m.quantite_emballages, 0) - COALESCE(m.quantite_emballages_reglee, 0), 0) AS reste_emballages
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

        if (!empty($filters['agent_id'])) {
            $where .= ' AND m.agent_id = :agent_id';
            $params['agent_id'] = $filters['agent_id'];
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
            "SELECT m.agent_id, CONCAT(a.prenom, ' ', a.nom) AS agent_nom, COUNT(*) AS nombre,
                    COALESCE(SUM(m.quantite_caisses), 0) AS total_caisses,
                    COALESCE(SUM(m.quantite_caisses_reglee), 0) AS total_caisses_reglees,
                    COALESCE(SUM(GREATEST(m.quantite_caisses - m.quantite_caisses_reglee, 0)), 0) AS total_reste_caisses,
                    COALESCE(SUM(m.quantite_emballages), 0) AS total_emballages,
                    COALESCE(SUM(m.quantite_emballages_reglee), 0) AS total_emballages_reglees,
                    COALESCE(SUM(GREATEST(m.quantite_emballages - m.quantite_emballages_reglee, 0)), 0) AS total_reste_emballages,
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

    public function enregistrerPaiement($id, $montantBase, $datePaiement, $note, $createdBy, $montantCdf = 0, $montantUsd = 0, $caissesReglees = 0, $emballagesRegles = 0)
    {
        $montantBase = round(max(0, (float) $montantBase), 2);
        $montantCdf = round(max(0, (float) $montantCdf), 2);
        $montantUsd = round(max(0, (float) $montantUsd), 2);
        $caissesReglees = round(max(0, (float) $caissesReglees), 2);
        $emballagesRegles = round(max(0, (float) $emballagesRegles), 2);

        if ($montantBase <= 0 && $caissesReglees <= 0 && $emballagesRegles <= 0) {
            return ['success' => false, 'message' => 'Veuillez renseigner au moins un règlement : argent, caisses ou emballages.'];
        }

        try {
            $this->db->beginTransaction();

            $manquant = $this->find($id);
            if (!$manquant) {
                throw new Exception('Manquant introuvable.');
            }

            $total = (float) ($manquant['montant'] ?? 0);
            $dejaPaye = (float) ($manquant['montant_paye'] ?? 0);
            $nouveauPaye = $total > 0 ? min($total, $dejaPaye + $montantBase) : ($dejaPaye + $montantBase);
            $resteMontant = max(0, $total - $nouveauPaye);

            $totalCaisses = (float) ($manquant['quantite_caisses'] ?? 0);
            $caissesDejaReglees = (float) ($manquant['quantite_caisses_reglee'] ?? 0);
            $nouvelleCaissesReglees = $totalCaisses > 0 ? min($totalCaisses, $caissesDejaReglees + $caissesReglees) : ($caissesDejaReglees + $caissesReglees);
            $resteCaisses = max(0, $totalCaisses - $nouvelleCaissesReglees);

            $totalEmballages = (float) ($manquant['quantite_emballages'] ?? 0);
            $emballagesDejaRegles = (float) ($manquant['quantite_emballages_reglee'] ?? 0);
            $nouvelleEmballagesRegles = $totalEmballages > 0 ? min($totalEmballages, $emballagesDejaRegles + $emballagesRegles) : ($emballagesDejaRegles + $emballagesRegles);
            $resteEmballages = max(0, $totalEmballages - $nouvelleEmballagesRegles);

            $statut = ($resteMontant <= 0.01 && $resteCaisses <= 0.0001 && $resteEmballages <= 0.0001)
                ? 'paye'
                : (($nouveauPaye > 0 || $nouvelleCaissesReglees > 0 || $nouvelleEmballagesRegles > 0) ? 'partiel' : 'ouvert');

            $this->db->insert('manquant_paiements', [
                'manquant_id' => $id,
                'montant' => $montantBase,
                'montant_cdf' => $montantCdf,
                'montant_usd' => $montantUsd,
                'quantite_caisses_reglee' => $caissesReglees,
                'quantite_emballages_reglee' => $emballagesRegles,
                'date_paiement' => $datePaiement,
                'note' => $note,
                'created_by' => $createdBy
            ]);

            $this->update($id, [
                'montant_paye' => $nouveauPaye,
                'montant_paye_cdf' => (float) ($manquant['montant_paye_cdf'] ?? 0) + $montantCdf,
                'montant_paye_usd' => (float) ($manquant['montant_paye_usd'] ?? 0) + $montantUsd,
                'quantite_caisses_reglee' => $nouvelleCaissesReglees,
                'quantite_emballages_reglee' => $nouvelleEmballagesRegles,
                'date_reglement' => $statut === 'paye' ? $datePaiement : null,
                'notes_reglement' => $note,
                'statut' => $statut
            ]);

            $this->db->commit();
            return [
                'success' => true,
                'reste' => $resteMontant,
                'reste_caisses' => $resteCaisses,
                'reste_emballages' => $resteEmballages,
                'statut' => $statut
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
