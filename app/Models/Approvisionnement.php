<?php
/**
 * Modèle Approvisionnement
 */

class Approvisionnement extends Model
{
    protected $table = 'approvisionnements';
    protected $fillable = [
        'numero_bon', 'date_approvisionnement', 'fournisseur', 'notes', 'total_ht',
        'solde_fournisseur_avant', 'montant_depose_fournisseur',
        'montant_utilise_fournisseur', 'solde_fournisseur_apres',
        'statut', 'created_by'
    ];

    private static bool $detailMoneyColumnsChecked = false;
    private static bool $supplierBalanceChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureDetailMoneyColumns();
        $this->ensureSupplierBalanceStorage();
    }

    private function ensureSupplierBalanceStorage(): void
    {
        if (self::$supplierBalanceChecked) {
            return;
        }

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS soldes_fournisseurs (
                fournisseur VARCHAR(150) NOT NULL,
                solde DECIMAL(15,2) NOT NULL DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (fournisseur)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = [
            'solde_fournisseur_avant' => "DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_ht",
            'montant_depose_fournisseur' => "DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER solde_fournisseur_avant",
            'montant_utilise_fournisseur' => "DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_depose_fournisseur",
            'solde_fournisseur_apres' => "DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_utilise_fournisseur",
        ];
        foreach ($columns as $column => $definition) {
            $exists = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'approvisionnements'
                   AND COLUMN_NAME = :column_name",
                ['column_name' => $column]
            );
            if (!$exists) {
                $this->db->query("ALTER TABLE approvisionnements ADD {$column} {$definition}");
            }
        }

        self::$supplierBalanceChecked = true;
    }

    public function getSupplierBalance(string $fournisseur): float
    {
        $fournisseur = trim($fournisseur);
        if ($fournisseur === '') {
            return 0;
        }

        return (float) ($this->db->fetchColumn(
            "SELECT solde FROM soldes_fournisseurs WHERE fournisseur = :fournisseur",
            ['fournisseur' => $fournisseur]
        ) ?: 0);
    }

    private function ensureDetailMoneyColumns(): void
    {
        if (self::$detailMoneyColumnsChecked) {
            return;
        }

        $columns = [
            'prix_produit' => "DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER prix_unitaire",
            'prix_emballage' => "DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER prix_produit",
            'prix_original' => "DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER prix_unitaire",
            'devise_prix' => "VARCHAR(3) NOT NULL DEFAULT 'CDF' AFTER prix_original",
            'taux_change' => "DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER devise_prix",
            'emballage_source_produit_id' => "INT UNSIGNED NULL AFTER produit_id",
        ];
        foreach ($columns as $column => $definition) {
            $exists = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'approvisionnement_details'
                   AND COLUMN_NAME = :column_name",
                ['column_name' => $column]
            );
            if (!$exists) {
                $this->db->query("ALTER TABLE approvisionnement_details ADD {$column} {$definition}");
            }
        }

        self::$detailMoneyColumnsChecked = true;
    }

    private function normalizeDetailType($type): string
    {
        if ($type === 'emballage') {
            return 'emballage';
        }
        if ($type === 'injection') {
            return 'injection';
        }
        return 'produit';
    }
    
    /**
     * Générer un numéro de bon unique
     */
    public function generateNumeroBon()
    {
        $prefix = 'APR-' . date('Ymd');
        $last = $this->db->fetchColumn(
            "SELECT MAX(numero_bon) FROM {$this->table} WHERE numero_bon LIKE :prefix",
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
        $approvisionnement = $this->find($id);
        
        if ($approvisionnement) {
            $approvisionnement['details'] = $this->db->fetchAll(
                "SELECT ad.*, p.nom as produit_nom, p.code as produit_code,
                        pe.nom as emballage_source_nom, pe.code as emballage_source_code,
                        p.bouteilles_par_caisses, p.caisses_par_palette,
                        p.prix_achat_deposer, p.prix_achat_enlever,
                        p.prix_vente_unitaire, p.prix_vente_caisses
                 FROM approvisionnement_details ad
                 JOIN produits p ON ad.produit_id = p.id
                 LEFT JOIN produits pe ON ad.emballage_source_produit_id = pe.id
                 WHERE ad.approvisionnement_id = :id
                 ORDER BY p.position_affichage ASC, p.nom ASC",
                ['id' => $id]
            );
        }
        
        return $approvisionnement;
    }
    
    /**
     * Récupérer tous avec pagination
     */
    public function getAllPaginated($page = 1, $perPage = 5, $filters = [])
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['date_debut'])) {
            $where .= " AND date_approvisionnement >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where .= " AND date_approvisionnement <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        
        if (!empty($filters['statut'])) {
            $where .= " AND statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        
        $offset = ($page - 1) * $perPage;
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE {$where}", $params);

        $data = $this->db->fetchAll(
            "SELECT a.*,
                    COALESCE(SUM(ad.quantite_caisses), 0) as total_quantite_caisses
             FROM {$this->table} a
             LEFT JOIN approvisionnement_details ad ON ad.approvisionnement_id = a.id
             WHERE {$where}
             GROUP BY a.id
             ORDER BY a.date_approvisionnement DESC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }
    
    /**
     * Créer un approvisionnement avec détails
     */
    public function createWithDetails($data, $details, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            $fournisseur = trim((string) ($data['fournisseur'] ?? 'Bralima'));
            if ($fournisseur === '') {
                $fournisseur = 'Bralima';
            }
            $montantDepose = max(0, (float) ($data['montant_depose_fournisseur'] ?? 0));
            $totalApprovisionnement = max(0, (float) ($data['total_ht'] ?? 0));
            $this->db->query(
                "INSERT IGNORE INTO soldes_fournisseurs (fournisseur, solde)
                 VALUES (:fournisseur, 0)",
                ['fournisseur' => $fournisseur]
            );
            $soldeAvant = (float) $this->db->fetchColumn(
                "SELECT solde FROM soldes_fournisseurs
                 WHERE fournisseur = :fournisseur FOR UPDATE",
                ['fournisseur' => $fournisseur]
            );
            $soldeDisponible = $soldeAvant + $montantDepose;
            if ($soldeDisponible + 0.001 < $totalApprovisionnement) {
                throw new Exception(
                    'Montant fournisseur insuffisant : disponible '
                    . number_format($soldeDisponible, 2, ',', ' ')
                    . ', approvisionnement '
                    . number_format($totalApprovisionnement, 2, ',', ' ')
                );
            }
            $soldeApres = round($soldeDisponible - $totalApprovisionnement, 2);
            $this->db->query(
                "UPDATE soldes_fournisseurs SET solde = :solde
                 WHERE fournisseur = :fournisseur",
                ['solde' => $soldeApres, 'fournisseur' => $fournisseur]
            );
            $data['fournisseur'] = $fournisseur;
            $data['solde_fournisseur_avant'] = $soldeAvant;
            $data['montant_depose_fournisseur'] = $montantDepose;
            $data['montant_utilise_fournisseur'] = $totalApprovisionnement;
            $data['solde_fournisseur_apres'] = $soldeApres;

            // Créer l'approvisionnement
            $approvisionnementId = $this->create($data);
            
            // Créer les détails et mettre à jour le stock
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();

            $besoinsEmballages = [];
            $apportsEmballages = [];
            foreach ($details as $detail) {
                $detailType = $this->normalizeDetailType($detail['type_chargement'] ?? 'produit');
                if ($detailType === 'emballage') {
                    $produitId = (int) ($detail['produit_id'] ?? 0);
                    $apportsEmballages[$produitId] = ($apportsEmballages[$produitId] ?? 0) + (int) ($detail['quantite_caisses'] ?? 0);
                    continue;
                }
                if ($detailType === 'injection') {
                    continue;
                }
                $produitId = (int) ($detail['produit_id'] ?? 0);
                $sourceId = (int) ($detail['emballage_source_produit_id'] ?? $produitId);
                $besoinsEmballages[$sourceId] = ($besoinsEmballages[$sourceId] ?? 0) + (int) ($detail['quantite_caisses'] ?? 0);
            }

            foreach ($besoinsEmballages as $produitId => $caissesNecessaires) {
                if ($caissesNecessaires <= 0) {
                    continue;
                }

                $stockVide = $stockModel->getStock($produitId, $emplacementPrincipalId);
                $disponible = (int) ($stockVide['caisses_vide'] ?? 0);
                $disponibleAvecAchat = $disponible + (int) ($apportsEmballages[$produitId] ?? 0);
                if ($disponibleAvecAchat < $caissesNecessaires) {
                    $produit = (new Produit())->find($produitId);
                    $nomProduit = $produit['nom'] ?? ('Produit #' . $produitId);
                    throw new Exception('Emballages insuffisants pour ' . $nomProduit . ' : disponible avec cet achat ' . $disponibleAvecAchat . ' cs, demandé ' . $caissesNecessaires . ' cs.');
                }
            }

            // Les emballages achetés sont enregistrés avant les produits pleins
            // afin de pouvoir être utilisés dans la même opération.
            usort($details, function ($a, $b) {
                return ($this->normalizeDetailType($a['type_chargement'] ?? 'produit') === 'emballage' ? 0 : 1)
                    <=> ($this->normalizeDetailType($b['type_chargement'] ?? 'produit') === 'emballage' ? 0 : 1);
            });

            foreach ($details as $detail) {
                $detail['approvisionnement_id'] = $approvisionnementId;
                $this->db->insert('approvisionnement_details', $detail);

                $produit = (new Produit())->find($detail['produit_id']);
                $detailType = $this->normalizeDetailType($detail['type_chargement'] ?? 'produit');
                $isEmballage = $detailType === 'emballage';
                $isInjection = $detailType === 'injection';
                $stockAvant = $stockModel->getStock($detail['produit_id'], $emplacementPrincipalId) ?: [];
                $quantiteKey = $isEmballage ? 'quantite_vide' : 'quantite_pleine';
                $quantiteAvant = (int) ($stockAvant[$quantiteKey] ?? 0);

                if ($isEmballage) {
                    $stockModel->updateOrCreate(
                        $detail['produit_id'],
                        $emplacementPrincipalId,
                        [
                            'quantite_vide' => $detail['quantite_bouteilles'],
                            'caisses_vide' => $detail['quantite_caisses'],
                        ]
                    );

                    $nouveauPrix = (float) ($detail['prix_emballage'] ?? $detail['prix_caisse'] ?? 0);
                    if (abs($nouveauPrix - (float) ($produit['prix_emballage'] ?? 0)) >= 0.01) {
                        (new Produit())->update($detail['produit_id'], ['prix_emballage' => $nouveauPrix]);
                    }
                } else {
                    // Entrée des produits pleins.
                    $stockModel->updateOrCreate(
                        $detail['produit_id'],
                        $emplacementPrincipalId,
                        [
                            'quantite_pleine' => $detail['quantite_bouteilles'],
                            'caisses_pleine' => $detail['quantite_caisses'],
                        ]
                    );

                    if (!$isInjection) {
                        $sourceEmballageId = (int) ($detail['emballage_source_produit_id'] ?? $detail['produit_id']);
                        $resultDeduction = $stockModel->deduireVide(
                            $sourceEmballageId,
                            $emplacementPrincipalId,
                            $detail['quantite_caisses']
                        );
                        if (!$resultDeduction['success']) {
                            $disponible = (int) ($resultDeduction['disponible'] ?? 0);
                            throw new Exception('Emballages insuffisants pour ' . ($produit['nom'] ?? 'Produit') . ' : disponible ' . $disponible . ' cs, demande ' . $detail['quantite_caisses'] . ' cs.');
                        }
                    } elseif ((float) ($detail['prix_emballage'] ?? 0) > 0
                        && abs((float) $detail['prix_emballage'] - (float) ($produit['prix_emballage'] ?? 0)) >= 0.01) {
                        (new Produit())->update($detail['produit_id'], ['prix_emballage' => (float) $detail['prix_emballage']]);
                    }
                }

                // Enregistrer le mouvement
                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'entree',
                    'quantite' => $detail['quantite_bouteilles'],
                    'quantite_avant' => $quantiteAvant,
                    'quantite_apres' => $quantiteAvant + $detail['quantite_bouteilles'],
                    'reference_type' => 'approvisionnement',
                    'reference_id' => $approvisionnementId,
                    'motif' => ($isEmballage
                        ? 'Approvisionnement emballages vides du '
                        : ($isInjection ? 'Approvisionnement par injection du ' : 'Approvisionnement produits seulement du ')) . $data['date_approvisionnement'],
                    'created_by' => $data['created_by']
                ]);
            }

            // DÉCLENCHER LA RÉSOLUTION DES ALERTES IMMÉDIATEMENT
            (new Alerte())->checkStockAlerts();
            
            $this->db->commit();
            return ['success' => true, 'id' => $approvisionnementId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Valider un approvisionnement
     */
    public function valider($id)
    {
        return $this->update($id, ['statut' => 'valide']);
    }

    public function updateWithDetails($id, $data, $details, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();

            $ancienAppro = $this->getWithDetails($id);

            if (!$ancienAppro) {
                throw new Exception('Approvisionnement non trouvé');
            }

            if (($ancienAppro['statut'] ?? '') !== 'valide') {
                throw new Exception('Seuls les approvisionnements validés peuvent être modifiés');
            }

            $ancienFournisseur = trim((string) ($ancienAppro['fournisseur'] ?? 'Bralima'));
            $nouveauFournisseur = trim((string) ($data['fournisseur'] ?? $ancienFournisseur));
            if (strcasecmp($ancienFournisseur, $nouveauFournisseur) !== 0) {
                throw new Exception('Le fournisseur ne peut pas être changé après validation.');
            }

            $stockModel = new Stock();

            $ancien = [];
            foreach ($ancienAppro['details'] as $d) {
                $detailType = $this->normalizeDetailType($d['type_chargement'] ?? 'produit');
                $sourceId = (int) ($d['emballage_source_produit_id'] ?? $d['produit_id']);
                $key = $detailType . ':' . (int) $d['produit_id'] . ':' . $sourceId;
                $ancien[$key] = $ancien[$key] ?? [
                    'produit_id' => (int) $d['produit_id'],
                    'emballage_source_produit_id' => $sourceId,
                    'type_chargement' => $detailType,
                    'caisses' => 0,
                    'bouteilles' => 0,
                ];
                $ancien[$key]['caisses'] += (int) $d['quantite_caisses'];
                $ancien[$key]['bouteilles'] += (int) $d['quantite_bouteilles'];
            }

            $nouveau = [];
            foreach ($details as $d) {
                $detailType = $this->normalizeDetailType($d['type_chargement'] ?? 'produit');
                $sourceId = (int) ($d['emballage_source_produit_id'] ?? $d['produit_id']);
                $key = $detailType . ':' . (int) $d['produit_id'] . ':' . $sourceId;
                $nouveau[$key] = $nouveau[$key] ?? [
                    'produit_id' => (int) $d['produit_id'],
                    'emballage_source_produit_id' => $sourceId,
                    'type_chargement' => $detailType,
                    'caisses' => 0,
                    'bouteilles' => 0,
                ];
                $nouveau[$key]['caisses'] += (int) $d['quantite_caisses'];
                $nouveau[$key]['bouteilles'] += (int) $d['quantite_bouteilles'];
            }

            $detailKeys = array_unique(array_merge(array_keys($ancien), array_keys($nouveau)));
            $stockDeltas = [];
            foreach ($detailKeys as $key) {
                $ligneReference = $nouveau[$key] ?? $ancien[$key];
                $produitId = (int) $ligneReference['produit_id'];
                $sourceId = (int) ($ligneReference['emballage_source_produit_id'] ?? $produitId);
                $detailType = $this->normalizeDetailType($ligneReference['type_chargement'] ?? 'produit');
                $isEmballage = $detailType === 'emballage';
                $isInjection = $detailType === 'injection';
                $ancienneCaisses = $ancien[$key]['caisses'] ?? 0;
                $nouvelleCaisses = $nouveau[$key]['caisses'] ?? 0;

                $ancienneBouteilles = $ancien[$key]['bouteilles'] ?? 0;
                $nouvelleBouteilles = $nouveau[$key]['bouteilles'] ?? 0;

                $diffCaisses = $nouvelleCaisses - $ancienneCaisses;
                $diffBouteilles = $nouvelleBouteilles - $ancienneBouteilles;

                $stockDeltas[$produitId] = $stockDeltas[$produitId] ?? [
                    'quantite_pleine' => 0,
                    'caisses_pleine' => 0,
                    'quantite_vide' => 0,
                    'caisses_vide' => 0,
                ];
                if ($isEmballage) {
                    $stockDeltas[$produitId]['quantite_vide'] += $diffBouteilles;
                    $stockDeltas[$produitId]['caisses_vide'] += $diffCaisses;
                } else {
                    $stockDeltas[$produitId]['quantite_pleine'] += $diffBouteilles;
                    $stockDeltas[$produitId]['caisses_pleine'] += $diffCaisses;
                    if (!$isInjection) {
                        $stockDeltas[$sourceId] = $stockDeltas[$sourceId] ?? [
                            'quantite_pleine' => 0,
                            'caisses_pleine' => 0,
                            'quantite_vide' => 0,
                            'caisses_vide' => 0,
                        ];
                        $stockDeltas[$sourceId]['quantite_vide'] -= $diffBouteilles;
                        $stockDeltas[$sourceId]['caisses_vide'] -= $diffCaisses;
                    }
                }
            }

            foreach ($stockDeltas as $produitId => $delta) {
                $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, $delta);
            }

            $this->db->query(
                "INSERT IGNORE INTO soldes_fournisseurs (fournisseur, solde)
                 VALUES (:fournisseur, 0)",
                ['fournisseur' => $ancienFournisseur]
            );
            $soldeCourant = (float) $this->db->fetchColumn(
                "SELECT solde FROM soldes_fournisseurs
                 WHERE fournisseur = :fournisseur FOR UPDATE",
                ['fournisseur' => $ancienFournisseur]
            );
            $nouveauSolde = round(
                $soldeCourant + (float) ($ancienAppro['total_ht'] ?? 0) - (float) $data['total_ht'],
                2
            );
            if ($nouveauSolde < -0.001) {
                throw new Exception('Le solde fournisseur est insuffisant pour augmenter cet approvisionnement.');
            }
            $nouveauSolde = max(0, $nouveauSolde);
            $this->db->query(
                "UPDATE soldes_fournisseurs SET solde = :solde
                 WHERE fournisseur = :fournisseur",
                ['solde' => $nouveauSolde, 'fournisseur' => $ancienFournisseur]
            );

            $this->update($id, [
                'date_approvisionnement' => $data['date_approvisionnement'],
                'fournisseur' => $data['fournisseur'] ?? 'Bralima',
                'notes' => $data['notes'] ?? '',
                'total_ht' => $data['total_ht'],
                'montant_utilise_fournisseur' => $data['total_ht'],
                'solde_fournisseur_apres' => $nouveauSolde
            ]);

            $this->db->query(
                "DELETE FROM approvisionnement_details WHERE approvisionnement_id = :id",
                ['id' => $id]
            );

            foreach ($details as $detail) {
                $detail['approvisionnement_id'] = $id;
                $this->db->insert('approvisionnement_details', $detail);
                $detailType = $this->normalizeDetailType($detail['type_chargement'] ?? 'produit');
                if (in_array($detailType, ['emballage', 'injection'], true)) {
                    $produit = (new Produit())->find($detail['produit_id']);
                    $nouveauPrix = (float) ($detail['prix_emballage'] ?? 0);
                    if (abs($nouveauPrix - (float) ($produit['prix_emballage'] ?? 0)) >= 0.01) {
                        (new Produit())->update($detail['produit_id'], ['prix_emballage' => $nouveauPrix]);
                    }
                }
            }

            (new Alerte())->checkStockAlerts();

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    /**
     * Annuler un approvisionnement
     */
    public function annuler($id, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();
            
            $approvisionnement = $this->getWithDetails($id);
            
            if (!$approvisionnement) {
                throw new Exception('Approvisionnement non trouvé');
            }

            if (($approvisionnement['statut'] ?? '') === 'annule') {
                throw new Exception('Cet approvisionnement est déjà annulé');
            }
            
            // Annuler l'approvisionnement
            $this->update($id, ['statut' => 'annule']);
            
            // Annuler les dettes associées
            $this->db->update('dettes_emballages', ['statut' => 'solde'], 'approvisionnement_id = :id', ['id' => $id]);

            $fournisseur = trim((string) ($approvisionnement['fournisseur'] ?? 'Bralima'));
            $this->db->query(
                "INSERT IGNORE INTO soldes_fournisseurs (fournisseur, solde)
                 VALUES (:fournisseur, 0)",
                ['fournisseur' => $fournisseur]
            );
            $this->db->query(
                "UPDATE soldes_fournisseurs
                 SET solde = solde + :montant
                 WHERE fournisseur = :fournisseur",
                [
                    'montant' => (float) ($approvisionnement['montant_utilise_fournisseur'] ?? $approvisionnement['total_ht'] ?? 0),
                    'fournisseur' => $fournisseur,
                ]
            );
            
            // Reverser le stock
            $stockModel = new Stock();
            $stockDeltas = [];
            foreach ($approvisionnement['details'] as $detail) {
                $detailType = $this->normalizeDetailType($detail['type_chargement'] ?? 'produit');
                $isEmballage = $detailType === 'emballage';
                $isInjection = $detailType === 'injection';
                $produitId = (int) $detail['produit_id'];
                $stockDeltas[$produitId] = $stockDeltas[$produitId] ?? [
                    'quantite_pleine' => 0,
                    'caisses_pleine' => 0,
                    'quantite_vide' => 0,
                    'caisses_vide' => 0,
                ];
                if ($isEmballage) {
                    $stockDeltas[$produitId]['quantite_vide'] -= (int) $detail['quantite_bouteilles'];
                    $stockDeltas[$produitId]['caisses_vide'] -= (int) $detail['quantite_caisses'];
                } else {
                    $stockDeltas[$produitId]['quantite_pleine'] -= (int) $detail['quantite_bouteilles'];
                    $stockDeltas[$produitId]['caisses_pleine'] -= (int) $detail['quantite_caisses'];
                    if (!$isInjection) {
                        $sourceId = (int) ($detail['emballage_source_produit_id'] ?? $produitId);
                        $stockDeltas[$sourceId] = $stockDeltas[$sourceId] ?? [
                            'quantite_pleine' => 0,
                            'caisses_pleine' => 0,
                            'quantite_vide' => 0,
                            'caisses_vide' => 0,
                        ];
                        $stockDeltas[$sourceId]['quantite_vide'] += (int) $detail['quantite_bouteilles'];
                        $stockDeltas[$sourceId]['caisses_vide'] += (int) $detail['quantite_caisses'];
                    }
                }
            }

            foreach ($stockDeltas as $produitId => $delta) {
                $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, $delta);
            }
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
