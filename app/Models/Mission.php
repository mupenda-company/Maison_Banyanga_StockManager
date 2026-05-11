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
        'montant_encaisse', 'montant_ristourne_initial', 'montant_livre', 'montant_restant_admin',
        'caisses_vides_retournees', 'created_by'
    ];

    private static bool $justificationColumnChecked = false;
    private static bool $chargementDepartColumnChecked = false;
    private static bool $missionTypeColumnChecked = false;
    private static bool $restourneColumnsChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureJustificationClosureColumn();
        $this->ensureChargementDepartColumn();
        $this->ensureMissionTypeColumn();
        $this->ensureRestourneColumns();
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
            'montant_restant_admin' => "ALTER TABLE missions ADD montant_restant_admin DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_livre",
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
                            SELECT SUM(v.total_ttc)
                            FROM ventes v
                            WHERE v.mission_id = ? AND v.statut = 'validee'
                        ), 0) as total",
                [$id, $id, $id]
            ) ?: ['quantite_bouteilles' => 0, 'caisses_vendues' => 0, 'total' => 0];

            $mission['ventes_par_produit'] = $this->db->fetchAll(
                "SELECT vd.produit_id,
                        p.nom as produit_nom,
                        p.code as produit_code,
                        p.bouteilles_par_caisses,
                        COALESCE(SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))), 0) as caisses_vendues,
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

            $mission['montant_attendu'] = (float) ($mission['ventes']['total'] ?? 0);
            $mission['caisses_vendues_total'] = (int) ($mission['ventes']['caisses_vendues'] ?? 0);
            $mission['caisses_vides_retournees'] = (int) ($mission['caisses_vides_retournees'] ?? 0);
            $mission['retours_vides_total'] = $mission['caisses_vides_retournees'];
            $mission['caisses_vides_attendues'] = $mission['caisses_vendues_total'];
            $mission['caisses_vides_ecart'] = $mission['caisses_vides_attendues'] - $mission['caisses_vides_retournees'];
            $mission['montant_ecart'] = round((float) ($mission['montant_encaisse'] ?? 0) - $mission['montant_attendu'], 2);
            $mission['justification_cloture'] = trim((string) ($mission['justification_cloture'] ?? ''));
            
            // Calculer le total du chargement
            $total = 0;
            $totalCaisses = 0;
            foreach ($mission['chargements'] as &$item) {
                $btlParCaisse = (int) ($item['bouteilles_par_caisses'] ?? 24);
                if ($btlParCaisse <= 0) {
                    $btlParCaisse = 24;
                }

                $prixCaisse = $item['prix_vente_caisses'] ?: ($item['prix_vente_unitaire'] * $item['bouteilles_par_caisses']);
                $stockDepartCaisses = (int) ($item['caisses_deja_dans_vehicule'] ?? 0);
                $item['quantite_caisses'] = (int) ($item['quantite_caisses'] ?? intdiv((int) $item['quantite_chargee'], $btlParCaisse));
                $item['caisses_vendues'] = (int) intdiv((int) ($item['quantite_vendue'] ?? 0), $btlParCaisse);
                $item['caisses_total'] = $stockDepartCaisses + $item['quantite_caisses'];
                $item['stock_depart_bouteilles'] = $stockDepartCaisses * $btlParCaisse;
                $item['stock_total_bouteilles'] = $item['stock_depart_bouteilles'] + (int) ($item['quantite_chargee'] ?? 0);
                $item['montant_vendu'] = $item['caisses_vendues'] * $prixCaisse;
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
            $mouvementModel = new MouvementStock();
            
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

                $quantiteCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
                if ($quantiteCaisses <= 0) {
                    $quantiteCaisses = (int) floor(((int) ($chargement['quantite_chargee'] ?? 0)) / $bouteillesParCaisse);
                }

                $quantiteBouteilles = $quantiteCaisses * $bouteillesParCaisse;
                $chargement['quantite_caisses'] = $quantiteCaisses;
                $chargement['quantite_chargee'] = $quantiteBouteilles;
                $chargement['caisses_deja_dans_vehicule'] = $caissesDejaDansVehicule;
                $chargement['prix_caisse'] = (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse));
                $chargement['mission_id'] = $missionId;
                $this->db->insert('mission_chargements', $chargement);
                
                // Transférer du stock principal vers le véhicule
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => -$quantiteCaisses
                    ]
                );
                
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => $quantiteBouteilles,
                        'caisses_pleine' => $quantiteCaisses
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
            
            $this->db->commit();
            return ['success' => true, 'id' => $missionId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Créer une mission de ristourne
     */
    public function createWithRestourne($data, $chargement, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            $ristourne = $this->db->fetch(
                "SELECT r.*, c.nom as client_nom, c.zone_id
                 FROM ristournes r
                 JOIN clients c ON r.client_id = c.id
                 WHERE r.id = :id",
                ['id' => (int) ($data['ristourne_id'] ?? 0)]
            );

            if (!$ristourne) {
                throw new Exception('Ristourne non trouvée');
            }

            $produit = (new Produit())->find((int) ($chargement['produit_id'] ?? 0));
            if (!$produit) {
                throw new Exception('Produit non trouvé');
            }

            $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($bouteillesParCaisse <= 0) {
                $bouteillesParCaisse = 24;
            }

            $prixCaisse = (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse));
            if ($prixCaisse <= 0) {
                throw new Exception('Le produit sélectionné doit avoir un prix caisse valide');
            }

            $montantInitial = (float) ($data['montant_ristourne_initial'] ?? $ristourne['montant_ristourne'] ?? 0);
            if ($montantInitial <= 0) {
                throw new Exception('Le montant de ristourne doit être supérieur à zéro');
            }

            $caissesLivrees = (int) floor($montantInitial / $prixCaisse);
            if ($caissesLivrees <= 0) {
                throw new Exception('Le montant de ristourne est insuffisant pour livrer ce produit');
            }

            $montantLivre = round($caissesLivrees * $prixCaisse, 2);
            $montantRestantAdmin = round(max($montantInitial - $montantLivre, 0), 2);
            $quantiteBouteilles = $caissesLivrees * $bouteillesParCaisse;

            $vehicule = (new Vehicule())->find((int) ($data['vehicule_id'] ?? 0));
            if (!$vehicule) {
                throw new Exception('Véhicule non trouvé');
            }

            $emplacementVehicule = (int) ($vehicule['emplacement_id'] ?? 0);
            if ($emplacementVehicule <= 0) {
                throw new Exception('Emplacement véhicule introuvable');
            }

            $stockModel = new Stock();
            $stockPrincipal = $stockModel->getStock((int) $produit['id'], (int) $emplacementPrincipalId);
            $caissesDisponibles = (int) ($stockPrincipal['caisses_pleine'] ?? floor(((int) ($stockPrincipal['quantite_pleine'] ?? 0)) / $bouteillesParCaisse));
            if ($caissesDisponibles < $caissesLivrees) {
                throw new Exception('Stock principal insuffisant pour créer cette mission de ristourne');
            }

            $missionId = $this->create([
                'numero_mission' => $data['numero_mission'],
                'type_mission' => 'ristourne',
                'vehicule_id' => $data['vehicule_id'],
                'chauffeur_id' => $data['chauffeur_id'] ?? null,
                'client_id' => (int) $ristourne['client_id'],
                'ristourne_id' => (int) $ristourne['id'],
                'date_depart' => $data['date_depart'],
                'date_retour' => null,
                'zone_id' => $data['zone_id'] ?? ($ristourne['zone_id'] ?? null),
                'notes' => $data['notes'] ?? '',
                'justification_cloture' => null,
                'statut' => 'en_cours',
                'montant_encaisse' => 0,
                'montant_ristourne_initial' => $montantInitial,
                'montant_livre' => $montantLivre,
                'montant_restant_admin' => $montantRestantAdmin,
                'caisses_vides_retournees' => 0,
                'created_by' => $data['created_by']
            ]);

            $this->db->insert('mission_chargements', [
                'mission_id' => $missionId,
                'produit_id' => (int) $produit['id'],
                'quantite_caisses' => $caissesLivrees,
                'caisses_deja_dans_vehicule' => 0,
                'quantite_chargee' => $quantiteBouteilles,
                'quantite_retournee' => 0,
                'quantite_vendue' => 0,
                'prix_caisse' => $prixCaisse
            ]);

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();

            $stockModel->updateOrCreate(
                (int) $produit['id'],
                $emplacementPrincipalId,
                [
                    'quantite_pleine' => -$quantiteBouteilles,
                    'caisses_pleine' => -$caissesLivrees
                ]
            );

            $stockModel->updateOrCreate(
                (int) $produit['id'],
                $emplacementVehicule,
                [
                    'quantite_pleine' => $quantiteBouteilles,
                    'caisses_pleine' => $caissesLivrees
                ]
            );

            $mouvementModel->create([
                'produit_id' => (int) $produit['id'],
                'emplacement_id' => $emplacementPrincipalId,
                'type_mouvement' => 'transfert',
                'quantite' => -$quantiteBouteilles,
                'quantite_avant' => 0,
                'quantite_apres' => 0,
                'reference_type' => 'mission',
                'reference_id' => $missionId,
                'motif' => 'Mission de ristourne ' . $data['numero_mission'],
                'created_by' => $data['created_by']
            ]);

            $this->db->commit();
            return ['success' => true, 'id' => $missionId, 'caisses_livrees' => $caissesLivrees, 'montant_livre' => $montantLivre, 'montant_restant_admin' => $montantRestantAdmin];
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
                $updateData = [
                    'statut' => 'terminee',
                    'date_retour' => date('Y-m-d H:i:s'),
                    'notes' => ($mission['notes'] ?? '') . "\nMission de ristourne terminée.",
                ];

                if (!empty($mission['montant_ristourne_initial']) && empty($mission['montant_livre'])) {
                    $updateData['montant_livre'] = $mission['montant_ristourne_initial'];
                    $updateData['montant_restant_admin'] = 0;
                }

                $this->update($id, $updateData);
                $this->db->commit();
                return ['success' => true];
            }
            
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            
            // 1. Gérer les INVENDUS (Produits pleins conservés dans le véhicule pour transfert manuel)
            foreach ($invendus as $produitId => $quantiteInvendue) {
                // Mettre à jour le chargement avec ce qui revient
                $this->db->query(
                    "UPDATE mission_chargements SET quantite_retournee = ? 
                     WHERE mission_id = ? AND produit_id = ?",
                    [$quantiteInvendue, $id, $produitId]
                );
            }

            // 2. Gérer les VIDES retournés
            $totalVidesRetournes = 0;
            $emplacementVehicule = (int) ($mission['vehicule']['emplacement_id'] ?? 0);
            foreach ($vides_retournes as $produitId => $nbCaissesVides) {
                if ($nbCaissesVides > 0) {
                    $totalVidesRetournes += (int) $nbCaissesVides;
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
            
            // 3. Clôturer la mission
            $updateData = [
                'statut' => 'terminee',
                'date_retour' => date('Y-m-d H:i:s'),
                'notes' => ($mission['notes'] ?? '') . "\nMission terminée. Montant encaissé: " . $montant_encaisse
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
}
