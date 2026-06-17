<?php
/**
 * Modèle Mission
 */

class Mission extends Model
{
    protected $table = 'missions';
    protected $fillable = [
        'numero_mission', 'type_mission', 'vehicule_id', 'chauffeur_id', 'client_id', 'ristourne_id',
        'date_depart', 'date_retour', 'zone_id', 'notes', 'justification_cloture', 'statut',
        'montant_encaisse', 'montant_ristourne_initial', 'montant_livre',
        'caisses_vides_retournees', 'manquant_id', 'montant_retour_physique',
        'ecart_montant_systeme', 'caisses_retournees_physiques',
        'caisses_vides_retournees_physiques', 'ecart_caisses_pleines',
        'ecart_caisses_vides', 'created_by'
    ];

    private static bool $justificationColumnChecked = false;
    private static bool $chargementDepartColumnChecked = false;
    private static bool $missionTypeColumnChecked = false;
    private static bool $restourneColumnsChecked = false;
    private static bool $missionReturnColumnsChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureJustificationClosureColumn();
        $this->ensureChargementDepartColumn();
        $this->ensureMissionTypeColumn();
        $this->ensureRestourneColumns();
        $this->ensureMissionReturnColumns();
    }

    private function ensureMissionReturnColumns(): void
    {
        if (self::$missionReturnColumnsChecked) {
            return;
        }

        $columns = [
            'missions' => [
                'manquant_id' => "ALTER TABLE missions ADD manquant_id INT UNSIGNED NULL AFTER caisses_vides_retournees",
                'montant_retour_physique' => "ALTER TABLE missions ADD montant_retour_physique DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_encaisse",
                'ecart_montant_systeme' => "ALTER TABLE missions ADD ecart_montant_systeme DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_retour_physique",
                'caisses_retournees_physiques' => "ALTER TABLE missions ADD caisses_retournees_physiques INT NOT NULL DEFAULT 0 AFTER caisses_vides_retournees",
                'caisses_vides_retournees_physiques' => "ALTER TABLE missions ADD caisses_vides_retournees_physiques INT NOT NULL DEFAULT 0 AFTER caisses_retournees_physiques",
                'ecart_caisses_pleines' => "ALTER TABLE missions ADD ecart_caisses_pleines INT NOT NULL DEFAULT 0 AFTER caisses_vides_retournees_physiques",
                'ecart_caisses_vides' => "ALTER TABLE missions ADD ecart_caisses_vides INT NOT NULL DEFAULT 0 AFTER ecart_caisses_pleines",
            ],
            'mission_chargements' => [
                'caisses_retournees_physiques' => "ALTER TABLE mission_chargements ADD caisses_retournees_physiques INT NOT NULL DEFAULT 0 AFTER quantite_retournee",
                'caisses_vides_retournees_physiques' => "ALTER TABLE mission_chargements ADD caisses_vides_retournees_physiques INT NOT NULL DEFAULT 0 AFTER caisses_retournees_physiques",
            ],
            'manquants_agents' => [
                'mission_id' => "ALTER TABLE manquants_agents ADD mission_id INT UNSIGNED NULL AFTER agent_id",
                'type_manquant' => "ALTER TABLE manquants_agents ADD type_manquant VARCHAR(30) NOT NULL DEFAULT 'manuel' AFTER mission_id",
                'quantite_emballages' => "ALTER TABLE manquants_agents ADD quantite_emballages DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantite_caisses",
            ],
        ];

        foreach ($columns as $table => $tableColumns) {
            foreach ($tableColumns as $column => $sql) {
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

        self::$missionReturnColumnsChecked = true;
    }

    private function ensureJustificationClosureColumn(): void
    {
        if (self::$justificationColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'missions'
               AND COLUMN_NAME = 'justification_cloture'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE missions ADD justification_cloture TEXT NULL AFTER notes");
        }

        self::$justificationColumnChecked = true;
    }

    private function ensureChargementDepartColumn(): void
    {
        if (self::$chargementDepartColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'mission_chargements'
               AND COLUMN_NAME = 'caisses_deja_dans_vehicule'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE mission_chargements ADD caisses_deja_dans_vehicule INT NOT NULL DEFAULT 0 AFTER quantite_caisses");
        }

        self::$chargementDepartColumnChecked = true;
    }

    private function ensureMissionTypeColumn(): void
    {
        if (self::$missionTypeColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'missions'
               AND COLUMN_NAME = 'type_mission'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE missions ADD type_mission VARCHAR(20) NOT NULL DEFAULT 'vente' AFTER numero_mission");
        }

        self::$missionTypeColumnChecked = true;
    }

    private function ensureRestourneColumns(): void
    {
        if (self::$restourneColumnsChecked) {
            return;
        }

        $columns = [
            'client_id' => "ALTER TABLE missions ADD client_id INT UNSIGNED NULL AFTER chauffeur_id",
            'ristourne_id' => "ALTER TABLE missions ADD ristourne_id INT UNSIGNED NULL AFTER client_id",
            'montant_ristourne_initial' => "ALTER TABLE missions ADD montant_ristourne_initial DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER notes",
            'montant_livre' => "ALTER TABLE missions ADD montant_livre DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_ristourne_initial",
        ];

        foreach ($columns as $column => $sql) {
            $exists = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'missions'
                   AND COLUMN_NAME = :column",
                ['column' => $column]
            );

            if (!$exists) {
                $this->db->query($sql);
            }
        }

        self::$restourneColumnsChecked = true;

        // Créer la table de liaison mission_ristournes si elle n'existe pas
        $tableExists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'mission_ristournes'"
        );
        if (!$tableExists) {
            $this->db->query(
                "CREATE TABLE `mission_ristournes` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `mission_id` INT UNSIGNED NOT NULL,
                    `ristourne_id` INT UNSIGNED NOT NULL,
                    `produit_id` INT UNSIGNED NOT NULL,
                    `montant_ristourne` DECIMAL(15,2) NOT NULL DEFAULT 0,
                    `caisses_prevues` INT NOT NULL DEFAULT 0,
                    `bouteilles_prevues` INT NOT NULL DEFAULT 0,
                    `caisses_livrees` INT NOT NULL DEFAULT 0,
                    `bouteilles_livrees` INT NOT NULL DEFAULT 0,
                    `montant_livre` DECIMAL(15,2) NOT NULL DEFAULT 0,
                    `proposition_montant` DECIMAL(15,2) NOT NULL DEFAULT 0,
                    `client_id` INT UNSIGNED NOT NULL,
                    `statut` ENUM('en_attente','livree','non_livree') NOT NULL DEFAULT 'en_attente',
                    FOREIGN KEY (`mission_id`) REFERENCES `missions`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`ristourne_id`) REFERENCES `ristournes`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }
            else {
                // If table exists, ensure it has the proposition_montant column
                $colExists = (bool) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mission_ristournes' AND COLUMN_NAME = 'proposition_montant'"
                );
                if (!$colExists) {
                    $this->db->query("ALTER TABLE mission_ristournes ADD COLUMN proposition_montant DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_livre");
                }
                // Ensure bouteilles_livrees column exists for older installations
                $colBtlExists = (bool) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mission_ristournes' AND COLUMN_NAME = 'bouteilles_livrees'"
                );
                if (!$colBtlExists) {
                    $this->db->query("ALTER TABLE mission_ristournes ADD COLUMN bouteilles_livrees INT NOT NULL DEFAULT 0 AFTER caisses_livrees");
                }
                // Add caisses_prevues column for prevues/livrees separation
                $colPrevuesExists = (bool) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mission_ristournes' AND COLUMN_NAME = 'caisses_prevues'"
                );
                if (!$colPrevuesExists) {
                    $this->db->query("ALTER TABLE mission_ristournes ADD COLUMN caisses_prevues INT NOT NULL DEFAULT 0 AFTER montant_ristourne");
                    // Migrate existing caisses_livrees to caisses_prevues for existing rows
                    $this->db->query("UPDATE mission_ristournes SET caisses_prevues = caisses_livrees WHERE caisses_prevues = 0");
                }
                // Add bouteilles_prevues column
                $colBtlPrevuesExists = (bool) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mission_ristournes' AND COLUMN_NAME = 'bouteilles_prevues'"
                );
                if (!$colBtlPrevuesExists) {
                    $this->db->query("ALTER TABLE mission_ristournes ADD COLUMN bouteilles_prevues INT NOT NULL DEFAULT 0 AFTER caisses_prevues");
                    $this->db->query("UPDATE mission_ristournes SET bouteilles_prevues = bouteilles_livrees WHERE bouteilles_prevues = 0");
                }
                // Add statut column for per-ristourne tracking
                $colStatutExists = (bool) $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mission_ristournes' AND COLUMN_NAME = 'statut'"
                );
                if (!$colStatutExists) {
                    $this->db->query("ALTER TABLE mission_ristournes ADD COLUMN statut ENUM('en_attente','livree','non_livree') NOT NULL DEFAULT 'en_attente' AFTER client_id");
                }
            }
    }
    
    /**
     * Générer un numéro de mission unique
     */
    public function generateNumeroMission($prefix = 'MSN')
    {
        $prefix = rtrim($prefix, '-') . '-' . date('Ymd');
        $last = $this->db->fetchColumn(
            "SELECT MAX(numero_mission) FROM {$this->table} WHERE numero_mission LIKE :prefix",
            ['prefix' => $prefix . '%']
        );
        
        if ($last) {
            $num = (int) substr($last, -4) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Récupérer avec les détails
     */
    public function getWithDetails($id)
    {
        $mission = $this->find($id);
        
        if ($mission) {
            $mission['vehicule'] = $this->db->fetch(
                "SELECT v.*, u.nom as agent_nom, u.prenom as agent_prenom
                 FROM vehicules v
                 LEFT JOIN users u ON v.agent_responsable_id = u.id
                 WHERE v.id = :id",
                ['id' => $mission['vehicule_id']]
            );

            $mission['created_by_user'] = $this->db->fetch(
                "SELECT u.nom, u.prenom
                 FROM users u
                 WHERE u.id = :id",
                ['id' => $mission['created_by']]
            );

            $createdByNom = trim((($mission['created_by_user']['prenom'] ?? '') . ' ' . ($mission['created_by_user']['nom'] ?? '')));
            $mission['created_by_nom'] = $createdByNom !== '' ? $createdByNom : 'Système';
            
            // Garantir que l'immatriculation est disponible au niveau racine pour la vue
            $mission['immatriculation'] = $mission['vehicule']['immatriculation'] ?? 'N/A';
            $mission['agent_nom'] = ($mission['vehicule']['agent_prenom'] ?? '') . ' ' . ($mission['vehicule']['agent_nom'] ?? '');
            if (trim($mission['agent_nom']) === '') $mission['agent_nom'] = 'N/A';
            
            $mission['zone'] = $this->db->fetch(
                "SELECT * FROM zones WHERE id = :id",
                ['id' => $mission['zone_id']]
            );
            $mission['zone_nom'] = $mission['zone']['nom'] ?? 'N/A';

            $mission['client'] = !empty($mission['client_id']) ? $this->db->fetch(
                "SELECT c.*, z.nom as zone_nom
                 FROM clients c
                 LEFT JOIN zones z ON c.zone_id = z.id
                 WHERE c.id = :id",
                ['id' => $mission['client_id']]
            ) : null;

            $mission['ristourne'] = !empty($mission['ristourne_id']) ? $this->db->fetch(
                "SELECT r.*, c.nom as client_nom
                 FROM ristournes r
                 JOIN clients c ON r.client_id = c.id
                 WHERE r.id = :id",
                ['id' => $mission['ristourne_id']]
            ) : null;

            // Charger les ristournes multiples depuis la table de liaison
            $mission['ristournes'] = [];
            if (($mission['type_mission'] ?? 'vente') === 'ristourne') {
                $mission['ristournes'] = $this->db->fetchAll(
                    "SELECT mr.*, c.nom as client_nom, c.numero_client, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses, p.prix_vente_caisses, p.prix_vente_unitaire
                     FROM mission_ristournes mr
                     JOIN clients c ON mr.client_id = c.id
                     JOIN produits p ON mr.produit_id = p.id
                     WHERE mr.mission_id = :mission_id
                     ORDER BY c.nom ASC",
                    ['mission_id' => $id]
                );
            }
            
            $mission['chargements'] = $this->db->fetchAll(
                "SELECT mc.*, p.nom as produit_nom, p.code as produit_code, p.prix_vente_unitaire, p.prix_vente_caisses, p.bouteilles_par_caisses
                 FROM mission_chargements mc
                 JOIN produits p ON mc.produit_id = p.id
                 WHERE mc.mission_id = :id",
                ['id' => $id]
            );

            $mission['clients'] = $this->db->fetchAll(
                "SELECT c.id, c.nom, c.telephone, c.adresse,
                        COALESCE((
                            SELECT SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)))
                            FROM ventes v2
                            LEFT JOIN vente_details vd ON vd.vente_id = v2.id
                            LEFT JOIN produits p ON vd.produit_id = p.id
                            WHERE v2.mission_id = ?
                              AND v2.statut = 'validee'
                              AND v2.client_id = c.id
                        ), 0) as quantite_caisses,
                        COALESCE((
                            SELECT SUM(v2.total_ttc)
                            FROM ventes v2
                            WHERE v2.mission_id = ?
                              AND v2.statut = 'validee'
                              AND v2.client_id = c.id
                        ), 0) as montant
                 FROM clients c
                 WHERE EXISTS (
                    SELECT 1
                    FROM ventes v3
                    WHERE v3.mission_id = ?
                      AND v3.statut = 'validee'
                      AND v3.client_id = c.id
                 )
                 ORDER BY (
                    SELECT MAX(v4.date_vente)
                    FROM ventes v4
                    WHERE v4.mission_id = ?
                      AND v4.statut = 'validee'
                      AND v4.client_id = c.id
                 ) DESC",
                [$id, $id, $id, $id]
            );

            $mission['ventes'] = $this->db->fetch(
                "SELECT 
                        COALESCE((
                            SELECT SUM(vd.quantite)
                            FROM ventes v
                            JOIN vente_details vd ON vd.vente_id = v.id
                            WHERE v.mission_id = ? AND v.statut = 'validee'
                        ), 0) as quantite_bouteilles,
                        COALESCE((
                            SELECT SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)))
                            FROM ventes v
                            JOIN vente_details vd ON vd.vente_id = v.id
                            LEFT JOIN produits p ON vd.produit_id = p.id
                            WHERE v.mission_id = ? AND v.statut = 'validee'
                        ), 0) as caisses_vendues,
                        COALESCE((
                            SELECT SUM(COALESCE(vd.caisses_vides_recues, 0))
                            FROM ventes v
                            JOIN vente_details vd ON vd.vente_id = v.id
                            WHERE v.mission_id = ? AND v.statut = 'validee'
                        ), 0) as caisses_vides_recues,
                        COALESCE((
                            SELECT SUM(v.total_ttc)
                            FROM ventes v
                            WHERE v.mission_id = ? AND v.statut = 'validee'
                        ), 0) as total",
                [$id, $id, $id, $id]
            ) ?: ['quantite_bouteilles' => 0, 'caisses_vendues' => 0, 'total' => 0];

            $mission['ventes_par_produit'] = $this->db->fetchAll(
                "SELECT vd.produit_id,
                        p.nom as produit_nom,
                        p.code as produit_code,
                        p.bouteilles_par_caisses,
                        COALESCE(SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))), 0) as caisses_vendues,
                        COALESCE(SUM(COALESCE(vd.caisses_vides_recues, 0)), 0) as caisses_vides_recues,
                        COALESCE(SUM(vd.quantite), 0) as bouteilles_vendues,
                        COALESCE(SUM(vd.sous_total), 0) as montant
                 FROM ventes v
                 JOIN vente_details vd ON vd.vente_id = v.id
                 LEFT JOIN produits p ON vd.produit_id = p.id
                 WHERE v.mission_id = ?
                   AND v.statut = 'validee'
                 GROUP BY vd.produit_id, p.nom, p.code, p.bouteilles_par_caisses
                 ORDER BY p.nom ASC",
                [$id]
            );

            $ventesParProduitIndex = [];
            foreach ($mission['ventes_par_produit'] as $venteProduit) {
                $ventesParProduitIndex[(int) ($venteProduit['produit_id'] ?? 0)] = $venteProduit;
            }

            $videsRecuesParProduit = [];
            foreach ($ventesParProduitIndex as $produitId => $venteProduit) {
                $videsRecuesParProduit[$produitId] = (int) ($venteProduit['caisses_vides_recues'] ?? 0);
            }

            $mission['montant_attendu'] = (float) ($mission['ventes']['total'] ?? 0);
            $mission['caisses_vendues_total'] = (int) ($mission['ventes']['caisses_vendues'] ?? 0);
            $mission['caisses_vides_recues_total'] = (int) ($mission['ventes']['caisses_vides_recues'] ?? 0);
            $mission['caisses_vides_retournees'] = (int) ($mission['caisses_vides_retournees'] ?? 0);
            $mission['retours_vides_total'] = $mission['caisses_vides_retournees'];
            $mission['caisses_vides_attendues'] = $mission['caisses_vendues_total'];
            $mission['caisses_vides_ecart'] = $mission['caisses_vides_attendues'] - $mission['caisses_vides_retournees'];
            $mission['montant_retour_physique'] = (float) ($mission['montant_retour_physique'] ?? 0);
            $mission['ecart_montant_systeme'] = (float) ($mission['ecart_montant_systeme'] ?? 0);
            $mission['caisses_retournees_physiques'] = (int) ($mission['caisses_retournees_physiques'] ?? 0);
            $mission['caisses_vides_retournees_physiques'] = (int) ($mission['caisses_vides_retournees_physiques'] ?? $mission['caisses_vides_retournees']);
            $mission['ecart_caisses_pleines'] = (int) ($mission['ecart_caisses_pleines'] ?? 0);
            $mission['ecart_caisses_vides'] = (int) ($mission['ecart_caisses_vides'] ?? 0);
            $mission['montant_ecart'] = round((float) ($mission['montant_encaisse'] ?? 0) - $mission['montant_attendu'], 2);
            $mission['justification_cloture'] = trim((string) ($mission['justification_cloture'] ?? ''));
            
            // Calculer le total du chargement
            $total = 0;
            $totalCaisses = 0;
            foreach ($mission['chargements'] as &$item) {
                $produitId = (int) ($item['produit_id'] ?? 0);
                $venteProduit = $ventesParProduitIndex[$produitId] ?? [];
                $btlParCaisse = (int) ($item['bouteilles_par_caisses'] ?? 24);
                if ($btlParCaisse <= 0) {
                    $btlParCaisse = 24;
                }

                $prixCaisse = $item['prix_vente_caisses'] ?: ($item['prix_vente_unitaire'] * $item['bouteilles_par_caisses']);
                $stockDepartCaisses = (int) ($item['caisses_deja_dans_vehicule'] ?? 0);
                $item['quantite_caisses'] = max(0, (int) ($item['quantite_caisses'] ?? intdiv((int) $item['quantite_chargee'], $btlParCaisse)));
                $item['delta_caisses'] = $item['quantite_caisses'] - $stockDepartCaisses;
                $item['caisses_vendues'] = (int) intdiv((int) ($item['quantite_vendue'] ?? 0), $btlParCaisse);
                $item['caisses_vendues_auto'] = (int) ($venteProduit['caisses_vendues'] ?? $item['caisses_vendues']);
                $item['caisses_vides_recues'] = (int) ($videsRecuesParProduit[$produitId] ?? 0);
                $item['caisses_vides_recues_auto'] = (int) ($venteProduit['caisses_vides_recues'] ?? $item['caisses_vides_recues']);
                $item['caisses_a_remettre_pleines'] = max(0, $item['quantite_caisses'] - $item['caisses_vendues_auto']);
                $item['caisses_a_remettre_vides'] = max(0, $item['caisses_vendues_auto']);
                $item['caisses_retournees_physiques'] = ($mission['statut'] === 'terminee')
                    ? (int) ($item['caisses_retournees_physiques'] ?? 0)
                    : $item['caisses_a_remettre_pleines'];
                $item['caisses_vides_retournees_physiques'] = ($mission['statut'] === 'terminee')
                    ? (int) ($item['caisses_vides_retournees_physiques'] ?? 0)
                    : $item['caisses_a_remettre_vides'];
                $item['ecart_retour_pleines'] = $item['caisses_retournees_physiques'] - $item['caisses_a_remettre_pleines'];
                $item['ecart_retour_vides'] = $item['caisses_vides_retournees_physiques'] - $item['caisses_a_remettre_vides'];
                $item['caisses_total'] = $item['quantite_caisses'];
                $item['stock_depart_bouteilles'] = $stockDepartCaisses * $btlParCaisse;
                $item['stock_total_bouteilles'] = $item['caisses_total'] * $btlParCaisse;
                $item['montant_vendu'] = $item['caisses_vendues_auto'] * $prixCaisse;
                $item['montant_retour_physique'] = max(0, $item['caisses_total'] - $item['caisses_retournees_physiques']) * $prixCaisse;
                $item['sous_total'] = $item['caisses_total'] * $prixCaisse;
                $total += $item['sous_total'];
                $totalCaisses += $item['caisses_total'];
            }
            $mission['total_chargement'] = $total;
            $mission['total_caisses'] = $totalCaisses;
            $mission['total_bouteilles'] = array_sum(array_map(static function ($item) {
                return (int) ($item['stock_total_bouteilles'] ?? 0);
            }, $mission['chargements']));
        }
        
        return $mission;
    }
    
    /**
     * Récupérer les missions en cours
     */
    public function getEnCours()
    {
        return $this->db->fetchAll(
            "SELECT m.*, v.immatriculation, u.nom as agent_nom, u.prenom as agent_prenom, z.nom as zone_nom
             FROM {$this->table} m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN zones z ON m.zone_id = z.id
             WHERE m.statut = 'en_cours' AND COALESCE(m.type_mission, 'vente') = 'vente'
             ORDER BY m.date_depart DESC"
        );
    }
    
    /**
     * Créer une mission avec chargement
     */
    public function createWithChargement($data, $chargements, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();
            
            // Récupérer l'emplacement du véhicule
            $vehicule = (new Vehicule())->find($data['vehicule_id']);
            $emplacementVehicule = $vehicule['emplacement_id'];
            
            // Créer la mission
            $missionId = $this->create($data);
            
            // Charger le véhicule et déduire de l'entrepôt
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $totalManquantCaisses = 0;
            $totalManquantMontant = 0.0;
            
            foreach ($chargements as $chargement) {
                $produit = (new Produit())->find($chargement['produit_id']);
                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                $stockVehicule = $this->db->fetch(
                    "SELECT quantite_pleine, caisses_pleine
                     FROM stocks
                     WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id
                     LIMIT 1",
                    [
                        'produit_id' => $chargement['produit_id'],
                        'emplacement_id' => $emplacementVehicule
                    ]
                );

                $caissesDejaDansVehicule = 0;
                if ($stockVehicule) {
                    $caissesDejaDansVehicule = (int) ($stockVehicule['caisses_pleine'] ?? 0);
                    if ($caissesDejaDansVehicule <= 0) {
                        $caissesDejaDansVehicule = (int) floor(((int) ($stockVehicule['quantite_pleine'] ?? 0)) / $bouteillesParCaisse);
                    }
                }

                $stockDepartCaisses = $caissesDejaDansVehicule;
                if ($stockDepartCaisses <= 0) {
                    $stockDepartCaisses = max(0, (int) ($chargement['stock_depart_caisses'] ?? 0));
                }

                $quantiteCaissesFinale = array_key_exists('quantite_caisses', $chargement)
                    ? max(0, (int) $chargement['quantite_caisses'])
                    : $stockDepartCaisses;

                $quantiteChargee = (int) ($chargement['quantite_chargee'] ?? 0);
                $deltaCaisses = $quantiteCaissesFinale - $stockDepartCaisses;
                $quantiteBouteilles = $deltaCaisses * $bouteillesParCaisse;
                $stockVideVehicule = $this->db->fetch(
                    "SELECT quantite_vide, caisses_vide
                     FROM stocks
                     WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id
                     LIMIT 1",
                    [
                        'produit_id' => $chargement['produit_id'],
                        'emplacement_id' => $emplacementVehicule
                    ]
                );
                $chargementInsert = [
                    'mission_id' => $missionId,
                    'produit_id' => (int) $chargement['produit_id'],
                    'quantite_caisses' => $quantiteCaissesFinale,
                    'caisses_deja_dans_vehicule' => $caissesDejaDansVehicule,
                    'quantite_chargee' => $quantiteBouteilles,
                    'quantite_retournee' => 0,
                    'quantite_vendue' => 0,
                    'prix_caisse' => (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse)),
                ];
                $this->db->insert('mission_chargements', $chargementInsert);
                $totalManquantCaisses += $quantiteCaissesFinale;
                $totalManquantMontant += $quantiteCaissesFinale * (float) $chargementInsert['prix_caisse'];
                
                // Transférer du stock principal vers le véhicule
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => -$deltaCaisses
                    ]
                );
                
                $stockModel->setInitialStock(
                    $chargement['produit_id'],
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => $quantiteCaissesFinale * $bouteillesParCaisse,
                        'caisses_pleine' => $quantiteCaissesFinale,
                        'quantite_vide' => (int) ($stockVideVehicule['quantite_vide'] ?? 0),
                        'caisses_vide' => (int) ($stockVideVehicule['caisses_vide'] ?? 0),
                    ]
                );
                
                // Enregistrer le mouvement de transfert
                $mouvementModel->create([
                    'produit_id' => $chargement['produit_id'],
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
                    'quantite' => -$quantiteBouteilles,
                    'quantite_avant' => 0,
                    'quantite_apres' => 0,
                    'reference_type' => 'mission',
                    'reference_id' => $missionId,
                    'motif' => 'Chargement véhicule pour mission ' . $data['numero_mission'],
                    'created_by' => $data['created_by']
                ]);
            }
            $agentId = (int) ($vehicule['agent_responsable_id'] ?? 0);
            if (($data['type_mission'] ?? 'vente') === 'vente' && $agentId > 0 && $totalManquantCaisses > 0) {
                $manquantId = $this->db->insert('manquants_agents', [
                    'agent_id' => $agentId,
                    'mission_id' => $missionId,
                    'type_manquant' => 'mission',
                    'produit_id' => null,
                    'quantite_caisses' => $totalManquantCaisses,
                    'quantite_emballages' => $totalManquantCaisses,
                    'montant' => round($totalManquantMontant, 2),
                    'montant_paye' => 0,
                    'date_manquant' => date('Y-m-d', strtotime($data['date_depart'] ?? 'now')),
                    'motif' => 'Mission ' . ($data['numero_mission'] ?? $missionId) . ' - chargement initial',
                    'notes_reglement' => 'Manquant ouvert automatiquement au lancement de la mission.',
                    'statut' => 'ouvert',
                    'created_by' => $data['created_by'] ?? ($_SESSION['user_id'] ?? null),
                ]);

                $this->update($missionId, ['manquant_id' => $manquantId]);
            }

            $this->db->commit();
            return ['success' => true, 'id' => $missionId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mettre à jour une mission avec remplacement complet des chargements
     */
    public function updateWithChargement($id, $data, $chargements, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            $mission = $this->getWithDetails($id);
            if (!$mission) {
                throw new Exception('Mission non trouvée');
            }

            if (($mission['statut'] ?? '') !== 'en_cours') {
                throw new Exception('Seules les missions en cours peuvent être modifiées');
            }

            $vehicule = (new Vehicule())->getWithStock((int) ($data['vehicule_id'] ?? 0));
            if (!$vehicule) {
                throw new Exception('Véhicule non trouvé');
            }

            $emplacementVehicule = (int) ($vehicule['emplacement_id'] ?? 0);
            if ($emplacementVehicule <= 0) {
                throw new Exception('Emplacement véhicule introuvable');
            }

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();

            $missionData = [
                'vehicule_id' => (int) ($data['vehicule_id'] ?? $mission['vehicule_id']),
                'chauffeur_id' => array_key_exists('chauffeur_id', $data) ? $data['chauffeur_id'] : ($mission['chauffeur_id'] ?? null),
                'date_depart' => $data['date_depart'] ?? ($mission['date_depart'] ?? null),
                'zone_id' => array_key_exists('zone_id', $data) ? $data['zone_id'] : ($mission['zone_id'] ?? null),
                'notes' => $data['notes'] ?? ($mission['notes'] ?? ''),
            ];

            if (array_key_exists('client_id', $data)) {
                $missionData['client_id'] = $data['client_id'];
            }

            if (array_key_exists('ristourne_id', $data)) {
                $missionData['ristourne_id'] = $data['ristourne_id'];
            }

            if (array_key_exists('montant_ristourne_initial', $data)) {
                $missionData['montant_ristourne_initial'] = $data['montant_ristourne_initial'];
            }

            $this->update($id, $missionData);
            $this->db->query("DELETE FROM mission_chargements WHERE mission_id = :mission_id", ['mission_id' => $id]);

            $vehiculeMisAJour = (new Vehicule())->getWithStock((int) $missionData['vehicule_id']);
            if (!$vehiculeMisAJour) {
                throw new Exception('Véhicule de mise à jour introuvable');
            }

            $stockVehicule = [];
            foreach (($vehiculeMisAJour['stock'] ?? []) as $stock) {
                $stockVehicule[(int) ($stock['produit_id'] ?? 0)] = $stock;
            }

            $chargementsValides = [];
            foreach ($chargements as $chargement) {
                $produitId = (int) ($chargement['produit_id'] ?? 0);
                if ($produitId <= 0) {
                    continue;
                }

                $stockCourantVehicule = $stockVehicule[$produitId] ?? null;
                $bouteillesParCaisse = (int) (($stockCourantVehicule['bouteilles_par_caisses'] ?? 0) ?: 0);
                if ($bouteillesParCaisse <= 0) {
                    $produitTmp = (new Produit())->find($produitId);
                    $bouteillesParCaisse = (int) ($produitTmp['bouteilles_par_caisses'] ?? 24);
                }
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                $stockDepartCaisses = (int) ($stockCourantVehicule['caisses_pleine'] ?? 0);
                if ($stockDepartCaisses <= 0 && $stockCourantVehicule) {
                    $stockDepartCaisses = (int) floor(((int) ($stockCourantVehicule['quantite_pleine'] ?? 0)) / $bouteillesParCaisse);
                }
                if ($stockDepartCaisses <= 0) {
                    $stockDepartCaisses = max(0, (int) ($chargement['stock_depart_caisses'] ?? 0));
                }

                $quantiteCaissesFinale = array_key_exists('quantite_caisses', $chargement)
                    ? max(0, (int) $chargement['quantite_caisses'])
                    : $stockDepartCaisses;

                if ($quantiteCaissesFinale <= 0 && $stockDepartCaisses <= 0) {
                    continue;
                }

                if (!isset($chargementsValides[$produitId])) {
                    $chargementsValides[$produitId] = [
                        'produit_id' => $produitId,
                        'quantite_caisses_finale' => 0,
                        'stock_depart_caisses' => 0,
                        'bouteilles_par_caisses' => $bouteillesParCaisse,
                    ];
                }

                $chargementsValides[$produitId]['quantite_caisses_finale'] += $quantiteCaissesFinale;
                $chargementsValides[$produitId]['stock_depart_caisses'] = max(0, $stockDepartCaisses);
            }

            if (empty($chargementsValides)) {
                throw new Exception('Ajoutez au moins un produit présent dans le véhicule ou une quantité à charger avant d’enregistrer la modification.');
            }

            $chargementsParProduit = $chargementsValides;
            $chargementsValides = array_values($chargementsValides);

            $capaciteVehicule = (int) ($vehiculeMisAJour['capacite'] ?? 0);
            if ($capaciteVehicule > 0) {
                $stockVehiculeActuel = 0;
                foreach (($vehiculeMisAJour['stock'] ?? []) as $stock) {
                    $stockVehiculeActuel += (int) round((float) ($stock['caisses_pleine'] ?? 0));
                    $stockVehiculeActuel += (int) round((float) ($stock['caisses_vide'] ?? 0));
                }

                $totalMissionCaisses = 0;
                foreach ($chargementsValides as $chargement) {
                    $totalMissionCaisses += max(0, (int) ($chargement['quantite_caisses_finale'] ?? 0));
                }

                if ($totalMissionCaisses > $capaciteVehicule) {
                    throw new Exception(
                        'La mission dépasse la capacité du véhicule. Capacité: ' . $capaciteVehicule . ' caisses, stock final demandé: ' . $totalMissionCaisses . ' caisses.'
                    );
                }
            }

            foreach ($stockVehicule as $produitId => $stockCourant) {
                if (isset($chargementsParProduit[(int) $produitId])) {
                    continue;
                }

                $caissesAVider = (int) round((float) ($stockCourant['caisses_pleine'] ?? 0));
                $produit = (new Produit())->find((int) $produitId);
                if (!$produit) {
                    continue;
                }

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                if ($caissesAVider <= 0) {
                    $caissesAVider = (int) floor(((int) ($stockCourant['quantite_pleine'] ?? 0)) / $bouteillesParCaisse);
                }

                if ($caissesAVider <= 0) {
                    continue;
                }

                $quantiteBouteilles = -($caissesAVider * $bouteillesParCaisse);

                $stockModel->updateOrCreate(
                    (int) $produitId,
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => $caissesAVider
                    ]
                );

                $stockModel->updateOrCreate(
                    (int) $produitId,
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => $quantiteBouteilles,
                        'caisses_pleine' => -$caissesAVider
                    ]
                );

                $mouvementModel->create([
                    'produit_id' => (int) $produitId,
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
                    'quantite' => -$quantiteBouteilles,
                    'reference_type' => 'mission',
                    'reference_id' => $id,
                    'motif' => 'Modification mission ' . $mission['numero_mission'],
                    'created_by' => $_SESSION['user_id'] ?? ($mission['created_by'] ?? null)
                ]);
            }

            foreach ($chargementsValides as $chargement) {
                $produit = (new Produit())->find((int) $chargement['produit_id']);
                if (!$produit) {
                    throw new Exception('Produit non trouvé pour la mission');
                }

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                $stockDepartCaisses = max(0, (int) ($chargement['stock_depart_caisses'] ?? 0));
                $quantiteCaissesFinale = max(0, (int) ($chargement['quantite_caisses_finale'] ?? 0));
                $deltaCaisses = $quantiteCaissesFinale - $stockDepartCaisses;
                $quantiteBouteilles = $deltaCaisses * $bouteillesParCaisse;
                $prixCaisse = (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse));

                $this->db->insert('mission_chargements', [
                    'mission_id' => $id,
                    'produit_id' => (int) $chargement['produit_id'],
                    'quantite_caisses' => $quantiteCaissesFinale,
                    'caisses_deja_dans_vehicule' => $stockDepartCaisses,
                    'quantite_chargee' => $quantiteBouteilles,
                    'quantite_retournee' => 0,
                    'quantite_vendue' => 0,
                    'prix_caisse' => $prixCaisse
                ]);

                if ($deltaCaisses === 0) {
                    continue;
                }

                $stockModel->updateOrCreate(
                    (int) $chargement['produit_id'],
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => -$deltaCaisses
                    ]
                );

                $stockModel->updateOrCreate(
                    (int) $chargement['produit_id'],
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => $quantiteBouteilles,
                        'caisses_pleine' => $deltaCaisses
                    ]
                );

                $mouvementModel->create([
                    'produit_id' => (int) $chargement['produit_id'],
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
                    'quantite' => -$quantiteBouteilles,
                    'reference_type' => 'mission',
                    'reference_id' => $id,
                    'motif' => 'Modification mission ' . $mission['numero_mission'],
                    'created_by' => $_SESSION['user_id'] ?? ($mission['created_by'] ?? null)
                ]);
            }

            $this->db->commit();
            return ['success' => true, 'id' => $id];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Créer une mission de ristourne avec plusieurs ristournes
     */
    public function createWithMultipleRestournes($data, $ristournesData, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            if (empty($ristournesData)) {
                throw new Exception('Aucune ristourne sélectionnée');
            }

            $vehicule = (new Vehicule())->find((int) ($data['vehicule_id'] ?? 0));
            if (!$vehicule) {
                throw new Exception('Véhicule non trouvé');
            }

            $emplacementVehicule = (int) ($vehicule['emplacement_id'] ?? 0);
            if ($emplacementVehicule <= 0) {
                throw new Exception('Emplacement véhicule introuvable');
            }

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $totalMontantRistourne = 0;
            $totalMontantLivre = 0;
            $chargementsParProduit = [];
            $missionRistournes = [];
            $zoneIdDemandee = (int) ($data['zone_id'] ?? 0);
            if ($zoneIdDemandee <= 0) {
                throw new Exception('Selectionnez une zone pour la mission de ristourne');
            }

            foreach ($ristournesData as $ristourneItem) {
                $ristourneId = (int) ($ristourneItem['ristourne_id'] ?? 0);
                $produitId = (int) ($ristourneItem['produit_id'] ?? 0);

                if ($ristourneId <= 0 || $produitId <= 0) continue;

                $ristourne = $this->db->fetch(
                    "SELECT r.*, c.nom as client_nom, c.zone_id
                     FROM ristournes r
                     JOIN clients c ON r.client_id = c.id
                     WHERE r.id = :id",
                    ['id' => $ristourneId]
                );

                if (!$ristourne) {
                    throw new Exception('Ristourne #' . $ristourneId . ' non trouvée');
                }
                if ((int) ($ristourne['zone_id'] ?? 0) !== $zoneIdDemandee) {
                    throw new Exception('La ristourne du client ' . ($ristourne['client_nom'] ?? '#' . $ristourneId) . ' ne correspond pas a la zone selectionnee');
                }

                $produit = (new Produit())->find($produitId);
                if (!$produit) {
                    throw new Exception('Produit #' . $produitId . ' non trouvé');
                }

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) $bouteillesParCaisse = 24;

                $prixCaisse = (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse));
                if ($prixCaisse <= 0) {
                    throw new Exception('Le produit ' . ($produit['nom'] ?? '#' . $produitId) . ' doit avoir un prix caisse valide');
                }

                $montantRistourne = (float) ($ristourne['montant_ristourne'] ?? 0);
                $propositionMontant = (float) ($ristourneItem['proposition_montant'] ?? 0);
                $montantEffectif = $montantRistourne + $propositionMontant;
                if ($montantRistourne <= 0 && $propositionMontant <= 0) continue;

                $prixUnitaire = $prixCaisse / $bouteillesParCaisse;

                $caissesLivrees = (int) ceil($montantEffectif / $prixCaisse);
                $resteApresCaisses = $montantEffectif - ($caissesLivrees * $prixCaisse);

                // Permettre la livraison en bouteilles pour la partie restante
                $bouteillesSupplementaires = (int) floor($resteApresCaisses / $prixUnitaire);

                $quantiteBouteilles = $caissesLivrees * $bouteillesParCaisse + $bouteillesSupplementaires;

                $montantLivre = round(($caissesLivrees * $prixCaisse) + ($bouteillesSupplementaires * $prixUnitaire), 2);
                // Do not allocate any remainder to administration; deliver as much as possible
                $montantRestantAdmin = 0.00;

                $totalMontantRistourne += $montantRistourne;
                // montant_livre starts at 0 - will be updated by agent on the field
                

                // Agréger les caisses par produit
                if (!isset($chargementsParProduit[$produitId])) {
                    $chargementsParProduit[$produitId] = [
                        'produit_id' => $produitId,
                        'caisses' => 0,
                        'bouteilles' => 0,
                        'prix_caisse' => $prixCaisse,
                        'bouteilles_par_caisse' => $bouteillesParCaisse
                    ];
                }
                $chargementsParProduit[$produitId]['caisses'] += $caissesLivrees;
                $chargementsParProduit[$produitId]['bouteilles'] += $quantiteBouteilles;

                $missionRistournes[] = [
                    'ristourne_id' => $ristourneId,
                    'produit_id' => $produitId,
                    'client_id' => (int) $ristourne['client_id'],
                    'montant_ristourne' => $montantRistourne,
                    'caisses_prevues' => $caissesLivrees,
                    'bouteilles_prevues' => $quantiteBouteilles,
                    'caisses_livrees' => 0,
                    'bouteilles_livrees' => 0,
                    'montant_livre' => 0,
                    'proposition_montant' => $propositionMontant,
                    'statut' => 'en_attente'
                ];
            }

            if (empty($chargementsParProduit)) {
                throw new Exception('Aucune ristourne valide à livrer');
            }

            // Déterminer la zone (celle de la première ristourne si pas fournie)
            $zoneId = $data['zone_id'] ?? null;
            if (!$zoneId && !empty($missionRistournes)) {
                $firstRistourne = $this->db->fetch(
                    "SELECT c.zone_id FROM ristournes r JOIN clients c ON r.client_id = c.id WHERE r.id = :id",
                    ['id' => $missionRistournes[0]['ristourne_id']]
                );
                $zoneId = $firstRistourne['zone_id'] ?? null;
            }

            // Créer la mission
            $missionId = $this->create([
                'numero_mission' => $data['numero_mission'],
                'type_mission' => 'ristourne',
                'vehicule_id' => $data['vehicule_id'],
                'chauffeur_id' => $data['chauffeur_id'] ?? null,
                'client_id' => null,
                'ristourne_id' => null,
                'date_depart' => $data['date_depart'],
                'date_retour' => null,
                'zone_id' => $zoneId,
                'notes' => $data['notes'] ?? '',
                'justification_cloture' => null,
                'statut' => 'en_cours',
                'montant_encaisse' => 0,
                'montant_ristourne_initial' => $totalMontantRistourne,
                'montant_livre' => 0,
                'caisses_vides_retournees' => 0,
                'created_by' => $data['created_by']
            ]);

            // Insérer les chargements et mouvements de stock par produit
            foreach ($chargementsParProduit as $produitId => $chargement) {
                // Vérifier le stock disponible
                $stockPrincipal = $stockModel->getStock($produitId, (int) $emplacementPrincipalId);
                $caissesDisponibles = (int) ($stockPrincipal['caisses_pleine'] ?? floor(((int) ($stockPrincipal['quantite_pleine'] ?? 0)) / $chargement['bouteilles_par_caisse']));
                if ($caissesDisponibles < $chargement['caisses']) {
                    throw new Exception('Stock principal insuffisant pour le produit #' . $produitId . ' (disponible: ' . $caissesDisponibles . ' cs, demandé: ' . $chargement['caisses'] . ' cs)');
                }
                // Vérifier aussi les bouteilles totales disponibles
                $bouteillesDisponibles = (int) ($stockPrincipal['quantite_pleine'] ?? 0);
                if ($bouteillesDisponibles < $chargement['bouteilles']) {
                    throw new Exception('Stock principal insuffisant pour le produit #' . $produitId . ' (bouteilles disponible: ' . $bouteillesDisponibles . ', demandé: ' . $chargement['bouteilles'] . ')');
                }

                $this->db->insert('mission_chargements', [
                    'mission_id' => $missionId,
                    'produit_id' => $produitId,
                    'quantite_caisses' => $chargement['caisses'],
                    'caisses_deja_dans_vehicule' => 0,
                    'quantite_chargee' => $chargement['bouteilles'],
                    'quantite_retournee' => 0,
                    'quantite_vendue' => 0,
                    'prix_caisse' => $chargement['prix_caisse']
                ]);

                // Déduire du stock principal (delta)
                $stockModel->updateOrCreate(
                    $produitId,
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$chargement['bouteilles'],
                        'caisses_pleine' => -$chargement['caisses']
                    ]
                );

                // Fixer le stock du véhicule à la valeur absolue (comme createWithChargement)
                // pour éviter l'accumulation avec le stock restant d'une mission précédente
                $stockVideVehicule = $this->db->fetch(
                    "SELECT quantite_vide, caisses_vide FROM stocks WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id LIMIT 1",
                    ['produit_id' => $produitId, 'emplacement_id' => $emplacementVehicule]
                );
                $stockModel->setInitialStock(
                    $produitId,
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => $chargement['bouteilles'],
                        'caisses_pleine' => $chargement['caisses'],
                        'quantite_vide' => (int) ($stockVideVehicule['quantite_vide'] ?? 0),
                        'caisses_vide' => (int) ($stockVideVehicule['caisses_vide'] ?? 0),
                    ]
                );

                $mouvementModel->create([
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
                    'quantite' => -$chargement['bouteilles'],
                    'quantite_avant' => 0,
                    'quantite_apres' => 0,
                    'reference_type' => 'mission',
                    'reference_id' => $missionId,
                    'motif' => 'Mission de ristourne ' . $data['numero_mission'],
                    'created_by' => $data['created_by']
                ]);
            }

            // Insérer les liaisons mission_ristournes
            foreach ($missionRistournes as $mr) {
                $mr['mission_id'] = $missionId;
                $this->db->insert('mission_ristournes', $mr);
            }

            // Marquer les ristournes comme en livraison (seront marquées payees à la clôture de la mission)
            foreach ($missionRistournes as $mr) {
                $this->db->query(
                    "UPDATE ristournes SET statut = 'en_livraison' WHERE id = :id",
                    ['id' => $mr['ristourne_id']]
                );
            }

            $this->db->commit();
            return [
                'success' => true,
                'id' => $missionId,
                'nb_ristournes' => count($missionRistournes),
                'montant_livre' => $totalMontantLivre
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Terminer une mission avec retour de vides et réintégration des invendus
     */
    public function terminer($id, $invendus, $vides_retournes, $montant_encaisse, $emplacementPrincipalId, $justificationCloture = null)
    {
        try {
            $this->db->beginTransaction();
            
            $mission = $this->getWithDetails($id);
            if (!$mission) throw new Exception("Mission non trouvée");

            if (($mission['type_mission'] ?? 'vente') === 'ristourne') {
                $stockModel = new Stock();
                $mouvementModel = new MouvementStock();

                // Récupérer les ristournes de la mission
                $missionRistournes = $this->db->fetchAll(
                    "SELECT mr.*, p.bouteilles_par_caisses, p.prix_vente_caisses, p.prix_vente_unitaire
                     FROM mission_ristournes mr
                     JOIN produits p ON mr.produit_id = p.id
                     WHERE mr.mission_id = :mission_id",
                    ['mission_id' => $id]
                );

                // Marquer les ristournes non livrées comme non_livree
                foreach ($missionRistournes as $mr) {
                    if (($mr['statut'] ?? '') === 'en_attente') {
                        $this->db->query(
                            "UPDATE mission_ristournes SET statut = 'non_livree' WHERE id = :id",
                            ['id' => (int) $mr['id']]
                        );
                    }
                }

                // Calculer les retours par produit (caisses prévues - caisses livrées)
                // et les caisses vides reçues des clients
                $retoursParProduit = [];
                $videsParProduit = [];
                foreach ($missionRistournes as $mr) {
                    $produitId = (int) ($mr['produit_id'] ?? 0);
                    $caissesPrevues = (int) ($mr['caisses_prevues'] ?? 0);
                    $caissesLivrees = (int) ($mr['caisses_livrees'] ?? 0);
                    $bouteillesPrevues = (int) ($mr['bouteilles_prevues'] ?? 0);
                    $bouteillesLivrees = (int) ($mr['bouteilles_livrees'] ?? 0);
                    $caissesVidesRecues = (int) ($mr['caisses_vides_recues'] ?? 0);

                    $caissesRetour = max($caissesPrevues - $caissesLivrees, 0);
                    $bouteillesRetour = max($bouteillesPrevues - $bouteillesLivrees, 0);

                    if ($caissesRetour > 0 || $bouteillesRetour > 0) {
                        if (!isset($retoursParProduit[$produitId])) {
                            $retoursParProduit[$produitId] = [
                                'caisses' => 0,
                                'bouteilles' => 0,
                                'bouteilles_par_caisse' => (int) ($mr['bouteilles_par_caisses'] ?? 24)
                            ];
                        }
                        $retoursParProduit[$produitId]['caisses'] += $caissesRetour;
                        $retoursParProduit[$produitId]['bouteilles'] += $bouteillesRetour;
                    }

                    // Caisses vides reçues du client
                    if ($caissesVidesRecues > 0) {
                        if (!isset($videsParProduit[$produitId])) {
                            $videsParProduit[$produitId] = 0;
                        }
                        $videsParProduit[$produitId] += $caissesVidesRecues;
                    }
                }

                // Retourner le stock invendu du véhicule au dépôt principal
                $vehicule = $mission['vehicule'] ?? null;
                $emplacementVehicule = $vehicule ? (int) ($vehicule['emplacement_id'] ?? 0) : 0;
                $emplacementPrincipalId = (int) $emplacementPrincipalId;
                if ($emplacementPrincipalId <= 0) {
                    $principal = $this->db->fetch("SELECT id FROM emplacements WHERE type = 'principal' LIMIT 1");
                    $emplacementPrincipalId = (int) ($principal['id'] ?? 0);
                }

                foreach ($retoursParProduit as $produitId => $retour) {
                    if ($retour['bouteilles'] > 0 && $emplacementVehicule > 0 && $emplacementPrincipalId > 0) {
                        // Retirer du véhicule
                        $stockModel->updateOrCreate($produitId, $emplacementVehicule, [
                            'quantite_pleine' => -$retour['bouteilles'],
                            'caisses_pleine' => -$retour['caisses']
                        ]);
                        // Remettre au dépôt
                        $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, [
                            'quantite_pleine' => $retour['bouteilles'],
                            'caisses_pleine' => $retour['caisses']
                        ]);
                        // Mouvement de stock
                        $mouvementModel->create([
                            'produit_id' => $produitId,
                            'emplacement_id' => $emplacementVehicule,
                            'type_mouvement' => 'transfert',
                            'quantite' => $retour['bouteilles'],
                            'quantite_avant' => 0,
                            'quantite_apres' => 0,
                            'reference_type' => 'mission',
                            'reference_id' => $id,
                            'motif' => 'Retour ristournes non livrées mission ' . ($mission['numero_mission'] ?? $id),
                            'created_by' => $_SESSION['user_id'] ?? null
                        ]);
                    }
                }

                // Les caisses vides reçues sont déjà ajoutées au stock véhicule lors de chaque encaisserMissionRistourne

                // Mettre à jour les chargements avec les quantités vendues (livrées)
                $chargements = $this->db->fetchAll(
                    "SELECT * FROM mission_chargements WHERE mission_id = :mission_id",
                    ['mission_id' => $id]
                );
                // Agréger les livrées par produit
                $livreesParProduit = [];
                foreach ($missionRistournes as $mr) {
                    $produitId = (int) ($mr['produit_id'] ?? 0);
                    $caissesLivrees = (int) ($mr['caisses_livrees'] ?? 0);
                    $bouteillesLivrees = (int) ($mr['bouteilles_livrees'] ?? 0);
                    if (!isset($livreesParProduit[$produitId])) {
                        $livreesParProduit[$produitId] = ['caisses' => 0, 'bouteilles' => 0];
                    }
                    $livreesParProduit[$produitId]['caisses'] += $caissesLivrees;
                    $livreesParProduit[$produitId]['bouteilles'] += $bouteillesLivrees;
                }
                foreach ($chargements as $chargement) {
                    $produitId = (int) ($chargement['produit_id'] ?? 0);
                    $livrees = $livreesParProduit[$produitId] ?? ['caisses' => 0, 'bouteilles' => 0];
                    $this->db->query(
                        "UPDATE mission_chargements SET quantite_vendue = :vendue, quantite_retournee = :retournee WHERE id = :id",
                        [
                            'vendue' => $livrees['bouteilles'],
                            'retournee' => ($retoursParProduit[$produitId]['bouteilles'] ?? 0),
                            'id' => (int) $chargement['id']
                        ]
                    );
                }

                // Calculer le montant_livre total des ristournes livrées
                $totalMontantLivre = (float) $this->db->fetchColumn(
                    "SELECT COALESCE(SUM(montant_livre), 0) FROM mission_ristournes WHERE mission_id = :mid AND statut = 'livree'",
                    ['mid' => $id]
                );

                // Total caisses vides reçues des clients
                $totalVidesRecues = array_sum($videsParProduit);

                $updateData = [
                    'statut' => 'terminee',
                    'date_retour' => date('Y-m-d H:i:s'),
                    'montant_livre' => $totalMontantLivre,
                    'caisses_vides_retournees' => $totalVidesRecues,
                    'notes' => ($mission['notes'] ?? '') . "\nMission de ristourne terminée.",
                ];

                $this->update($id, $updateData);

                // Marquer les ristournes livrées comme payees
                $ristournesIds = $this->db->fetchAll(
                    "SELECT ristourne_id FROM mission_ristournes WHERE mission_id = :mission_id AND statut = 'livree'",
                    ['mission_id' => $id]
                );
                foreach ($ristournesIds as $row) {
                    $this->db->query(
                        "UPDATE ristournes SET statut = 'payee', date_paiement = CURDATE() WHERE id = :id",
                        ['id' => (int) $row['ristourne_id']]
                    );
                }
                // Remettre les ristournes non livrées en statut calculee
                $nonLivreesIds = $this->db->fetchAll(
                    "SELECT ristourne_id FROM mission_ristournes WHERE mission_id = :mission_id AND statut = 'non_livree'",
                    ['mission_id' => $id]
                );
                foreach ($nonLivreesIds as $row) {
                    $this->db->query(
                        "UPDATE ristournes SET statut = 'calculee', date_paiement = NULL WHERE id = :id",
                        ['id' => (int) $row['ristourne_id']]
                    );
                }

                $this->db->commit();
                return ['success' => true];
            }
            
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $chargements = $mission['chargements'] ?? [];
            $ventesParProduit = [];
            foreach (($mission['ventes_par_produit'] ?? []) as $venteProduit) {
                $produitId = (int) ($venteProduit['produit_id'] ?? 0);
                if ($produitId <= 0) {
                    continue;
                }

                $ventesParProduit[$produitId] = $venteProduit;
            }

            $totalVidesRetournes = 0;
            $totalCaissesVendues = 0;
            $totalCaissesRetournees = 0;
            $totalCaissesRetourAttendues = 0;
            $totalVidesAttendues = 0;
            $totalMontantPhysique = 0.0;
            $totalValeurRetoursPleins = 0.0;
            $totalValeurChargement = 0.0;
            $emplacementVehicule = (int) ($mission['vehicule']['emplacement_id'] ?? 0);

            foreach ($chargements as $chargement) {
                $produitId = (int) ($chargement['produit_id'] ?? 0);
                if ($produitId <= 0) {
                    continue;
                }

                $produit = (new Produit())->find($produitId);
                if (!$produit) {
                    continue;
                }

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                $caissesChargees = max(0, (int) ($chargement['caisses_total'] ?? $chargement['quantite_caisses'] ?? 0));
                $venteProduit = $ventesParProduit[$produitId] ?? [];
                $caissesVendues = max(0, (int) ($venteProduit['caisses_vendues'] ?? 0));
                $bouteillesVendues = max(0, (int) ($venteProduit['bouteilles_vendues'] ?? 0));
                if ($bouteillesVendues <= 0 && $caissesVendues > 0) {
                    $bouteillesVendues = $caissesVendues * $bouteillesParCaisse;
                }

                $prixCaisse = (float) ($chargement['prix_caisse'] ?? 0);
                if ($prixCaisse <= 0) {
                    $prixCaisse = (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse));
                }

                $caissesRetourAttendues = max(0, $caissesChargees - $caissesVendues);
                $caissesVidesAttendues = $caissesVendues;
                $caissesRetournees = array_key_exists($produitId, $invendus)
                    ? max(0, (int) $invendus[$produitId])
                    : $caissesRetourAttendues;
                $caissesVidesRetournees = array_key_exists($produitId, $vides_retournes)
                    ? max(0, (int) $vides_retournes[$produitId])
                    : $caissesVidesAttendues;

                if ($caissesRetournees > $caissesChargees) {
                    throw new Exception('Retour plein incoherent pour ' . ($produit['nom'] ?? 'produit #' . $produitId) . ' : retour superieur au chargement.');
                }

                $quantiteRetournee = $caissesRetournees * $bouteillesParCaisse;
                $caissesVenduesPhysiques = max(0, $caissesChargees - $caissesRetournees);
                $montantPhysiqueProduit = $caissesVenduesPhysiques * $prixCaisse;

                $totalCaissesVendues += $caissesVendues;
                $totalCaissesRetournees += $caissesRetournees;
                $totalVidesRetournes += $caissesVidesRetournees;
                $totalCaissesRetourAttendues += $caissesRetourAttendues;
                $totalVidesAttendues += $caissesVidesAttendues;
                $totalMontantPhysique += $montantPhysiqueProduit;
                $totalValeurRetoursPleins += $caissesRetournees * $prixCaisse;
                $totalValeurChargement += $caissesChargees * $prixCaisse;

                $this->db->query(
                    "UPDATE mission_chargements
                     SET quantite_vendue = ?, quantite_retournee = ?, caisses_retournees_physiques = ?, caisses_vides_retournees_physiques = ?
                     WHERE mission_id = ? AND produit_id = ?",
                    [$bouteillesVendues, $quantiteRetournee, $caissesRetournees, $caissesVidesRetournees, $id, $produitId]
                );

                // Enregistrer le stock physique constaté sur le véhicule à la clôture.
                // Cela ne remplace pas le stock système : cela sert à comparer système vs physique.
                if ($emplacementVehicule > 0) {
                    $stockModel->setPhysicalStock($produitId, $emplacementVehicule, [
                        'caisses_pleine' => $caissesRetournees,
                        'caisses_vide' => $caissesVidesRetournees,
                        'quantite_pleine' => $quantiteRetournee,
                        'quantite_vide' => $caissesVidesRetournees * $bouteillesParCaisse,
                    ], $id);
                }

                // Les caisses pleines retournées restent dans le véhicule.

                if ($caissesVidesRetournees > 0 && $emplacementPrincipalId > 0) {
                    $quantiteBouteillesVides = $caissesVidesRetournees * $bouteillesParCaisse;
                    if ($emplacementVehicule > 0) {
                        $stockVideVehicule = $this->db->fetch(
                            "SELECT COALESCE(caisses_vide, 0) as caisses_vide
                             FROM stocks
                             WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id
                             LIMIT 1",
                            ['produit_id' => $produitId, 'emplacement_id' => $emplacementVehicule]
                        );
                        $caissesVidesVehicule = max(0, (int) ($stockVideVehicule['caisses_vide'] ?? 0));
                        $caissesVidesASortirVehicule = min($caissesVidesRetournees, $caissesVidesVehicule);
                        if ($caissesVidesASortirVehicule > 0) {
                            $stockModel->updateOrCreate($produitId, $emplacementVehicule, [
                                'quantite_vide' => -($caissesVidesASortirVehicule * $bouteillesParCaisse),
                                'caisses_vide' => -$caissesVidesASortirVehicule
                            ]);
                            $mouvementModel->create([
                                'produit_id' => $produitId,
                                'emplacement_id' => $emplacementVehicule,
                                'type_mouvement' => 'sortie',
                                'quantite' => -($caissesVidesASortirVehicule * $bouteillesParCaisse),
                                'reference_type' => 'mission',
                                'reference_id' => $id,
                                'motif' => 'Sortie emballages vides mission ' . $mission['numero_mission'],
                                'created_by' => $_SESSION['user_id'] ?? ($mission['created_by'] ?? null)
                            ]);
                        }
                    }

                    $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, [
                        'quantite_vide' => $quantiteBouteillesVides,
                        'caisses_vide' => $caissesVidesRetournees
                    ]);
                    $mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementPrincipalId,
                        'type_mouvement' => 'entree',
                        'quantite' => $quantiteBouteillesVides,
                        'reference_type' => 'mission',
                        'reference_id' => $id,
                        'motif' => 'Retour emballages vides mission ' . $mission['numero_mission'],
                        'created_by' => $_SESSION['user_id'] ?? ($mission['created_by'] ?? null)
                    ]);
                }
            }

            $montantSysteme = (float) ($mission['montant_attendu'] ?? 0);
            $ecartMontantSysteme = round($totalMontantPhysique - $montantSysteme, 2);
            $ecartCaissesPleines = $totalCaissesRetournees - $totalCaissesRetourAttendues;
            $ecartCaissesVides = $totalVidesRetournes - $totalVidesAttendues;
            $couvertureManquant = min($totalValeurChargement, max(0, (float) $montant_encaisse) + $totalValeurRetoursPleins);

            $updateData = [
                'statut' => 'terminee',
                'date_retour' => date('Y-m-d H:i:s'),
                'montant_retour_physique' => round($totalMontantPhysique, 2),
                'ecart_montant_systeme' => $ecartMontantSysteme,
                'caisses_retournees_physiques' => $totalCaissesRetournees,
                'caisses_vides_retournees_physiques' => $totalVidesRetournes,
                'ecart_caisses_pleines' => $ecartCaissesPleines,
                'ecart_caisses_vides' => $ecartCaissesVides,
                'caisses_vides_retournees' => $totalVidesRetournes,
                'montant_encaisse' => $montant_encaisse,
                'notes' => ($mission['notes'] ?? '') . "\nMission terminee avec retour physique. Vendu systeme: " . $totalCaissesVendues . " cs, retour plein: " . $totalCaissesRetournees . " cs, retour vide: " . $totalVidesRetournes . " cs. Montant physique: " . round($totalMontantPhysique, 2) . ", montant systeme: " . round($montantSysteme, 2) . ", encaisse: " . $montant_encaisse
            ];

            $justificationCloture = trim((string) $justificationCloture);
            if ($justificationCloture !== '') {
                $updateData['justification_cloture'] = $justificationCloture;
            }

            $manquantId = (int) ($mission['manquant_id'] ?? 0);
            if ($manquantId <= 0) {
                $manquantId = (int) $this->db->fetchColumn(
                    "SELECT id FROM manquants_agents WHERE mission_id = :mission_id AND type_manquant = 'mission' ORDER BY id DESC LIMIT 1",
                    ['mission_id' => $id]
                );
            }

            if ($manquantId > 0) {
                $statutManquant = ($couvertureManquant + 0.01 >= $totalValeurChargement && $ecartCaissesVides >= 0) ? 'paye' : ($couvertureManquant > 0 ? 'partiel' : 'ouvert');
                $this->db->query(
                    "UPDATE manquants_agents
                     SET montant_paye = :montant_paye,
                         date_reglement = :date_reglement,
                         notes_reglement = :notes_reglement,
                         statut = :statut
                     WHERE id = :id",
                    [
                        'montant_paye' => round($couvertureManquant, 2),
                        'date_reglement' => $statutManquant === 'paye' ? date('Y-m-d') : null,
                        'notes_reglement' => 'Cloture mission ' . ($mission['numero_mission'] ?? $id) . ' - retour plein: ' . $totalCaissesRetournees . ' cs, retour vide: ' . $totalVidesRetournes . ' cs, montant encaisse: ' . round((float) $montant_encaisse, 2) . ', montant du physique: ' . round($totalMontantPhysique, 2) . '.',
                        'statut' => $statutManquant,
                        'id' => $manquantId,
                    ]
                );
            }

            $this->update($id, $updateData);

            $this->db->commit();
            return ['success' => true];

            $invendusAuto = [];
            $videsRetournesAuto = [];
            $totalVidesRetournes = 0;
            $totalCaissesVendues = 0;
            $totalCaissesRetournees = 0;

            foreach ($chargements as $chargement) {
                $produitId = (int) ($chargement['produit_id'] ?? 0);
                if ($produitId <= 0) {
                    continue;
                }

                $produit = (new Produit())->find($produitId);
                if (!$produit) {
                    continue;
                }

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                $caissesChargees = max(0, (int) ($chargement['caisses_total'] ?? $chargement['quantite_caisses'] ?? 0));
                $venteProduit = $ventesParProduit[$produitId] ?? [];
                $caissesVendues = max(0, (int) ($venteProduit['caisses_vendues'] ?? 0));
                $bouteillesVendues = max(0, (int) ($venteProduit['bouteilles_vendues'] ?? 0));
                if ($bouteillesVendues <= 0 && $caissesVendues > 0) {
                    $bouteillesVendues = $caissesVendues * $bouteillesParCaisse;
                }

                $caissesRestantes = max(0, $caissesChargees - $caissesVendues);
                $quantiteRetournee = $caissesRestantes * $bouteillesParCaisse;
                $caissesVidesRetournees = max(0, (int) ($venteProduit['caisses_vides_recues'] ?? 0));

                $invendusAuto[$produitId] = $quantiteRetournee;
                $videsRetournesAuto[$produitId] = $caissesVidesRetournees;
                $totalCaissesVendues += $caissesVendues;
                $totalCaissesRetournees += $caissesRestantes;
                $totalVidesRetournes += $caissesVidesRetournees;

                $this->db->query(
                    "UPDATE mission_chargements
                     SET quantite_vendue = ?, quantite_retournee = ?
                     WHERE mission_id = ? AND produit_id = ?",
                    [$bouteillesVendues, $quantiteRetournee, $id, $produitId]
                );
            }

            if (empty($invendusAuto)) {
                $invendusAuto = $invendus;
            }

            if (empty($videsRetournesAuto)) {
                $videsRetournesAuto = $vides_retournes;
            }

            // 1. Gérer les INVENDUS automatiquement à partir des ventes validées
            foreach ($invendusAuto as $produitId => $quantiteInvendue) {
                $this->db->query(
                    "UPDATE mission_chargements SET quantite_retournee = ? 
                     WHERE mission_id = ? AND produit_id = ?",
                    [$quantiteInvendue, $id, $produitId]
                );
            }

            // 2. Gérer les VIDES retournés automatiquement à partir des ventes validées
            $emplacementVehicule = (int) ($mission['vehicule']['emplacement_id'] ?? 0);
            foreach ($videsRetournesAuto as $produitId => $nbCaissesVides) {
                if ($nbCaissesVides > 0) {
                    $produit = (new Produit())->find($produitId);
                    $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                    if ($bouteillesParCaisse <= 0) {
                        $bouteillesParCaisse = 24;
                    }

                    $quantiteBouteillesVides = $nbCaissesVides * $bouteillesParCaisse;

                    if ($emplacementVehicule > 0) {
                        $mouvementModel->create([
                            'produit_id' => $produitId,
                            'emplacement_id' => $emplacementVehicule,
                            'type_mouvement' => 'sortie',
                            'quantite' => -$quantiteBouteillesVides,
                            'reference_type' => 'mission',
                            'reference_id' => $id,
                            'motif' => 'Retour emballages vides mission ' . $mission['numero_mission'],
                            'created_by' => $_SESSION['user_id']
                        ]);

                        $stockModel->updateOrCreate($produitId, $emplacementVehicule, [
                            'quantite_vide' => -$quantiteBouteillesVides,
                            'caisses_vide' => -$nbCaissesVides
                        ]);
                    }

                    // Les vides retournés vont à l'entrepôt principal
                    $mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementPrincipalId,
                        'type_mouvement' => 'entree',
                        'quantite' => $quantiteBouteillesVides,
                        'reference_type' => 'mission',
                        'reference_id' => $id,
                        'motif' => 'Retour emballages vides mission ' . $mission['numero_mission'],
                        'created_by' => $_SESSION['user_id']
                    ]);

                    $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, [
                        'quantite_vide' => $quantiteBouteillesVides,
                        'caisses_vide' => $nbCaissesVides
                    ]);
                }
            }
            
            // 3. Clôturer la mission sans logique d'écart manuelle
            $updateData = [
                'statut' => 'terminee',
                'date_retour' => date('Y-m-d H:i:s'),
                'notes' => ($mission['notes'] ?? '') . "\nMission terminée automatiquement. Vendu: " . $totalCaissesVendues . " cs, retourné: " . $totalCaissesRetournees . " cs, vides retournés: " . $totalVidesRetournes . " cs. Montant encaissé: " . $montant_encaisse
            ];

            $justificationCloture = trim((string) $justificationCloture);
            if ($justificationCloture !== '') {
                $updateData['justification_cloture'] = $justificationCloture;
            }

            $hasMontantEncaisseColumn = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'missions'
                   AND COLUMN_NAME = 'montant_encaisse'"
            );

            if ($hasMontantEncaisseColumn) {
                $updateData['montant_encaisse'] = $montant_encaisse;
            }

            $hasVidesRetournesColumn = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'missions'
                   AND COLUMN_NAME = 'caisses_vides_retournees'"
            );

            if ($hasVidesRetournesColumn) {
                $updateData['caisses_vides_retournees'] = $totalVidesRetournes;
            }

            $this->update($id, $updateData);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Annuler une mission en cours (reverse stock, supprime chargements)
     */
    public function annuler($id, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            $mission = $this->getWithDetails($id);
            if (!$mission) throw new Exception("Mission non trouvée");

            if (($mission['statut'] ?? '') !== 'en_cours') {
                throw new Exception('Seules les missions en cours peuvent être annulées');
            }

            // Vérifier qu'aucune vente validée n'est liée
            $nbVentes = (int) $this->db->fetch(
                "SELECT COUNT(*) as nb FROM ventes WHERE mission_id = :id AND statut = 'validee'",
                ['id' => $id]
            )['nb'] ?? 0;

            if ($nbVentes > 0) {
                throw new Exception('Impossible d\'annuler : cette mission a des ventes validées (' . $nbVentes . '). Terminez-la normalement.');
            }

            $vehicule = (new Vehicule())->find($mission['vehicule_id']);
            if (!$vehicule) throw new Exception('Véhicule introuvable');

            $emplacementVehicule = (int) ($vehicule['emplacement_id'] ?? 0);
            if ($emplacementVehicule <= 0) throw new Exception('Emplacement véhicule introuvable');

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $chargements = $mission['chargements'] ?? [];

            foreach ($chargements as $chargement) {
                $produitId = (int) ($chargement['produit_id'] ?? 0);
                if ($produitId <= 0) continue;

                $produit = (new Produit())->find($produitId);
                if (!$produit) continue;

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) $bouteillesParCaisse = 24;

                $caissesDansVehicule = max(0, (int) ($chargement['caisses_total'] ?? $chargement['quantite_caisses'] ?? 0));
                $quantiteBouteilles = $caissesDansVehicule * $bouteillesParCaisse;

                if ($caissesDansVehicule <= 0) continue;

                // Retirer du véhicule
                $stockModel->updateOrCreate(
                    $produitId,
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => -$caissesDansVehicule
                    ]
                );

                // Réintégrer dans l'entrepôt principal
                $stockModel->updateOrCreate(
                    $produitId,
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => $quantiteBouteilles,
                        'caisses_pleine' => $caissesDansVehicule
                    ]
                );

                // Mouvement de retour
                $mouvementModel->create([
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
                    'quantite' => $quantiteBouteilles,
                    'quantite_avant' => 0,
                    'quantite_apres' => 0,
                    'reference_type' => 'mission',
                    'reference_id' => $id,
                    'motif' => 'Annulation mission ' . $mission['numero_mission'],
                    'created_by' => $_SESSION['user_id'] ?? ($mission['created_by'] ?? null)
                ]);
            }

            // Supprimer les chargements
            $this->db->query("DELETE FROM mission_chargements WHERE mission_id = :mission_id", ['mission_id' => $id]);

            // Rétablir les ristournes liées en statut calculee
            $ristournesIds = $this->db->fetchAll(
                "SELECT ristourne_id FROM mission_ristournes WHERE mission_id = :mission_id",
                ['mission_id' => $id]
            );
            foreach ($ristournesIds as $row) {
                $this->db->query(
                    "UPDATE ristournes SET statut = 'calculee', date_paiement = NULL WHERE id = :id",
                    ['id' => (int) $row['ristourne_id']]
                );
            }

            // Supprimer les liaisons mission_ristournes
            $this->db->query("DELETE FROM mission_ristournes WHERE mission_id = :mission_id", ['mission_id' => $id]);

            // Marquer la mission comme annulée
            $this->update($id, [
                'statut' => 'annulee',
                'date_retour' => date('Y-m-d H:i:s'),
                'notes' => ($mission['notes'] ?? '') . "\nMission annulée le " . date('d/m/Y H:i')
            ]);

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
