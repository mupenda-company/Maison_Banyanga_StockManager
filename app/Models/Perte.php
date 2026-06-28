<?php
/**
 * Modèle Perte
 */

class Perte extends Model
{
    protected $table = 'pertes';
    protected $fillable = ['produit_id', 'emplacement_id', 'quantite', 'type_perte', 'motif', 'date_perte', 'valeur_perte', 'agent_id', 'created_by', 'type_stock', 'manquant_id'];

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

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'pertes'
               AND COLUMN_NAME = 'manquant_id'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE pertes ADD manquant_id INT UNSIGNED NULL AFTER agent_id");
        }

        self::$columnsChecked = true;
    }
    
    /**
     * Récupérer avec les détails
     */
    public function getWithDetails($id)
    {
        return $this->db->fetch(
            "SELECT p.*, pr.nom as produit_nom, pr.code as produit_code, pr.bouteilles_par_caisses,
                    e.nom as emplacement_nom, e.type as emplacement_type,
                    u.nom as created_by_nom, u.prenom as created_by_prenom,
                    a.nom as agent_nom, a.prenom as agent_prenom,
                    m.statut as manquant_statut,
                    GREATEST(COALESCE(m.montant, 0) - COALESCE(m.montant_paye, 0), 0) AS manquant_reste_montant,
                    GREATEST(COALESCE(m.quantite_caisses, 0) - COALESCE(m.quantite_caisses_reglee, 0), 0) AS manquant_reste_caisses,
                    GREATEST(COALESCE(m.quantite_emballages, 0) - COALESCE(m.quantite_emballages_reglee, 0), 0) AS manquant_reste_emballages
             FROM {$this->table} p
             JOIN produits pr ON p.produit_id = pr.id
             JOIN emplacements e ON p.emplacement_id = e.id
             LEFT JOIN users u ON p.created_by = u.id
             LEFT JOIN users a ON p.agent_id = a.id
             LEFT JOIN manquants_agents m ON m.id = p.manquant_id
             WHERE p.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Récupérer toutes les pertes avec détails
     */
    public function getAllWithDetails($filters = [])
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['produit_id'])) {
            $where .= " AND p.produit_id = :produit_id";
            $params['produit_id'] = $filters['produit_id'];
        }
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND p.emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }

        if (!empty($filters['agent_id'])) {
            $where .= " AND p.agent_id = :agent_id";
            $params['agent_id'] = $filters['agent_id'];
        }
        
        if (!empty($filters['type_perte'])) {
            $where .= " AND p.type_perte = :type_perte";
            $params['type_perte'] = $filters['type_perte'];
        }
        
        if (!empty($filters['date_debut'])) {
            $where .= " AND p.date_perte >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where .= " AND p.date_perte <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        
        return $this->db->fetchAll(
            "SELECT p.*, pr.nom as produit_nom, pr.code as produit_code, pr.bouteilles_par_caisses,
                    e.nom as emplacement_nom, e.type as emplacement_type,
                    a.nom as agent_nom, a.prenom as agent_prenom,
                    m.statut as manquant_statut,
                    GREATEST(COALESCE(m.montant, 0) - COALESCE(m.montant_paye, 0), 0) AS manquant_reste_montant,
                    GREATEST(COALESCE(m.quantite_caisses, 0) - COALESCE(m.quantite_caisses_reglee, 0), 0) AS manquant_reste_caisses,
                    GREATEST(COALESCE(m.quantite_emballages, 0) - COALESCE(m.quantite_emballages_reglee, 0), 0) AS manquant_reste_emballages
             FROM {$this->table} p
             JOIN produits pr ON p.produit_id = pr.id
             JOIN emplacements e ON p.emplacement_id = e.id
             LEFT JOIN users a ON p.agent_id = a.id
             LEFT JOIN manquants_agents m ON m.id = p.manquant_id
             WHERE {$where}
             ORDER BY p.date_perte DESC",
            $params
        );
    }
    
    /**
     * Enregistrer une perte et mettre à jour le stock
     */
    public function createWithStockUpdate($data)
    {
        try {
            $this->db->beginTransaction();
            
            // Récupérer les détails du produit
            $produitModel = new Produit();
            $produit = $produitModel->find($data['produit_id']);
            $btlParCaisse = (int)($produit['bouteilles_par_caisses'] ?? 24);

            // Créer la perte
            $typeStock = $data['type_stock'] ?? 'plein';
            $unitePerte = $data['unite_perte'] ?? 'caisse';
            $quantiteSaisie = (float) $data['quantite'];
            $nbBouteilles = $unitePerte === 'bouteille' ? $quantiteSaisie : ($quantiteSaisie * $btlParCaisse);
            $nbCaisses = $nbBouteilles / max(1, $btlParCaisse);

            if (abs($nbCaisses - round($nbCaisses)) < 0.0001) {
                $nbCaisses = (float) round($nbCaisses);
            }

            $data['quantite'] = $nbCaisses;
            $perteId = $this->create($data);
            
            // Déduire du stock
            $stockModel = new Stock();
            $typeStock = $data['type_stock'] ?? 'plein';
            
            // La donnée reçue dans $data['quantite'] est maintenant le nombre de CAISSES
            $nbCaisses = (float)$data['quantite'];
            $nbBouteilles = $nbCaisses * $btlParCaisse;

            $updateData = [];
            if ($typeStock === 'vide') {
                $updateData = [
                    'quantite_vide' => -$nbBouteilles,
                    'caisses_vide' => -$nbCaisses
                ];
            } else {
                $updateData = [
                    'quantite_pleine' => -$nbBouteilles,
                    'caisses_pleine' => -$nbCaisses
                ];
            }

            $stockModel->updateOrCreate(
                $data['produit_id'],
                $data['emplacement_id'],
                $updateData
            );
            
            // Enregistrer le mouvement (on garde les bouteilles pour l'historique précis)
            $mouvementModel = new MouvementStock();
            $mouvementModel->create([
                'produit_id' => $data['produit_id'],
                'emplacement_id' => $data['emplacement_id'],
                'type_mouvement' => 'sortie',
                'quantite' => -$nbBouteilles,
                'reference_type' => 'perte',
                'reference_id' => $perteId,
                'motif' => 'Perte (' . $typeStock . ') - ' . $data['type_perte'],
                'created_by' => $data['created_by']
            ]);

            $manquantId = $this->syncManquantForPerte($perteId, array_merge($data, [
                'quantite' => $nbCaisses,
                'produit_nom' => $produit['nom'] ?? '',
                'produit_code' => $produit['code'] ?? '',
            ]));
            if ($manquantId) {
                $this->update($perteId, ['manquant_id' => $manquantId]);
            }
            
            $this->db->commit();
            return ['success' => true, 'id' => $perteId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiques des pertes (calcul précis en caisses)
     */
    public function getStats($dateDebut, $dateFin)
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_pertes,
                SUM(p.quantite) as total_caisses,
                SUM(p.valeur_perte) as total_valeur
             FROM {$this->table} p
             WHERE p.date_perte BETWEEN :date_debut AND :date_fin",
            ['date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );
    }
    
    /**
     * Pertes par type
     */
    public function getByType($dateDebut = null, $dateFin = null)
    {
        $where = "1=1";
        $params = [];
        
        if ($dateDebut && $dateFin) {
            $where .= " AND date_perte BETWEEN :date_debut AND :date_fin";
            $params['date_debut'] = $dateDebut;
            $params['date_fin'] = $dateFin;
        }
        
        return $this->db->fetchAll(
            "SELECT type_perte, COUNT(*) as nb, SUM(quantite) as quantite, SUM(valeur_perte) as valeur
             FROM {$this->table}
             WHERE {$where}
             GROUP BY type_perte
             ORDER BY valeur DESC",
            $params
        );
    }

    public function getByAgent($dateDebut = null, $dateFin = null)
    {
        $where = "1=1";
        $params = [];

        if ($dateDebut && $dateFin) {
            $where .= " AND p.date_perte BETWEEN :date_debut AND :date_fin";
            $params['date_debut'] = $dateDebut;
            $params['date_fin'] = $dateFin;
        }

        return $this->db->fetchAll(
            "SELECT p.agent_id,
                    COALESCE(CONCAT(u.prenom, ' ', u.nom), 'Non assigne') as agent_nom_complet,
                    COUNT(*) as nb,
                    COALESCE(SUM(p.quantite), 0) as total_caisses,
                    COALESCE(SUM(p.valeur_perte), 0) as total_valeur
             FROM {$this->table} p
             LEFT JOIN users u ON p.agent_id = u.id
             WHERE {$where}
             GROUP BY p.agent_id, u.prenom, u.nom
             ORDER BY total_valeur DESC",
            $params
        );
    }


    /**
     * Modifier une perte en restaurant l'ancien impact stock puis en appliquant le nouveau.
     */
    public function updateWithStockUpdate($id, $data)
    {
        try {
            $this->db->beginTransaction();

            $anciennePerte = $this->getWithDetails((int) $id);
            if (!$anciennePerte) {
                throw new Exception('Perte non trouvée');
            }

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();

            // 1) Restaurer l'ancien stock touché par la perte initiale
            $ancienBtlParCaisse = (int) ($anciennePerte['bouteilles_par_caisses'] ?? 24);
            if ($ancienBtlParCaisse <= 0) {
                $ancienBtlParCaisse = 24;
            }
            $ancienneQuantiteCaisses = (float) ($anciennePerte['quantite'] ?? 0);
            $ancienneQuantiteBouteilles = $ancienneQuantiteCaisses * $ancienBtlParCaisse;
            $ancienTypeStock = $anciennePerte['type_stock'] ?? 'plein';

            $stockModel->updateOrCreate(
                (int) $anciennePerte['produit_id'],
                (int) $anciennePerte['emplacement_id'],
                $ancienTypeStock === 'vide'
                    ? [
                        'quantite_vide' => $ancienneQuantiteBouteilles,
                        'caisses_vide' => $ancienneQuantiteCaisses,
                    ]
                    : [
                        'quantite_pleine' => $ancienneQuantiteBouteilles,
                        'caisses_pleine' => $ancienneQuantiteCaisses,
                    ]
            );

            $mouvementModel->create([
                'produit_id' => (int) $anciennePerte['produit_id'],
                'emplacement_id' => (int) $anciennePerte['emplacement_id'],
                'type_mouvement' => 'entree',
                'quantite' => $ancienneQuantiteBouteilles,
                'reference_type' => 'perte_modification',
                'reference_id' => (int) $id,
                'motif' => 'Restauration ancien impact perte ID: ' . (int) $id,
                'created_by' => $data['updated_by'] ?? ($_SESSION['user_id'] ?? null)
            ]);

            // 2) Recalculer la nouvelle quantité selon l'unité saisie
            $produit = (new Produit())->find((int) $data['produit_id']);
            if (!$produit) {
                throw new Exception('Produit introuvable');
            }

            $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($btlParCaisse <= 0) {
                $btlParCaisse = 24;
            }

            $unitePerte = $data['unite_perte'] ?? 'caisse';
            $quantiteSaisie = max(0, (float) ($data['quantite'] ?? 0));
            $nouvelleQuantiteBouteilles = $unitePerte === 'bouteille'
                ? $quantiteSaisie
                : ($quantiteSaisie * $btlParCaisse);
            $nouvelleQuantiteCaisses = $nouvelleQuantiteBouteilles / max(1, $btlParCaisse);

            if (abs($nouvelleQuantiteCaisses - round($nouvelleQuantiteCaisses)) < 0.0001) {
                $nouvelleQuantiteCaisses = (float) round($nouvelleQuantiteCaisses);
            }

            if ($nouvelleQuantiteCaisses <= 0) {
                throw new Exception('La quantité de perte doit être supérieure à 0');
            }

            $nouveauTypeStock = $data['type_stock'] ?? 'plein';

            // 3) Déduire le nouveau stock
            $stockModel->updateOrCreate(
                (int) $data['produit_id'],
                (int) $data['emplacement_id'],
                $nouveauTypeStock === 'vide'
                    ? [
                        'quantite_vide' => -$nouvelleQuantiteBouteilles,
                        'caisses_vide' => -$nouvelleQuantiteCaisses,
                    ]
                    : [
                        'quantite_pleine' => -$nouvelleQuantiteBouteilles,
                        'caisses_pleine' => -$nouvelleQuantiteCaisses,
                    ]
            );

            $mouvementModel->create([
                'produit_id' => (int) $data['produit_id'],
                'emplacement_id' => (int) $data['emplacement_id'],
                'type_mouvement' => 'sortie',
                'quantite' => -$nouvelleQuantiteBouteilles,
                'reference_type' => 'perte',
                'reference_id' => (int) $id,
                'motif' => 'Modification perte (' . $nouveauTypeStock . ') - ' . ($data['type_perte'] ?? ''),
                'created_by' => $data['updated_by'] ?? ($_SESSION['user_id'] ?? null)
            ]);

            // 4) Mettre à jour l'enregistrement de la perte
            $this->update((int) $id, [
                'produit_id' => (int) $data['produit_id'],
                'emplacement_id' => (int) $data['emplacement_id'],
                'quantite' => $nouvelleQuantiteCaisses,
                'type_perte' => $data['type_perte'],
                'type_stock' => $nouveauTypeStock,
                'motif' => $data['motif'] ?? '',
                'date_perte' => $data['date_perte'],
                'valeur_perte' => max(0, (float) ($data['valeur_perte'] ?? 0)),
                'agent_id' => !empty($data['agent_id']) ? (int) $data['agent_id'] : null,
            ]);

            $manquantId = $this->syncManquantForPerte((int) $id, [
                'manquant_id' => $anciennePerte['manquant_id'] ?? null,
                'produit_id' => (int) $data['produit_id'],
                'produit_nom' => $produit['nom'] ?? '',
                'produit_code' => $produit['code'] ?? '',
                'quantite' => $nouvelleQuantiteCaisses,
                'type_perte' => $data['type_perte'],
                'type_stock' => $nouveauTypeStock,
                'motif' => $data['motif'] ?? '',
                'date_perte' => $data['date_perte'],
                'valeur_perte' => max(0, (float) ($data['valeur_perte'] ?? 0)),
                'agent_id' => !empty($data['agent_id']) ? (int) $data['agent_id'] : null,
                'created_by' => $data['updated_by'] ?? ($_SESSION['user_id'] ?? null),
            ]);
            $this->update((int) $id, ['manquant_id' => $manquantId]);

            $this->db->commit();
            return ['success' => true, 'id' => (int) $id];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Supprimer une perte et restaurer le stock
     */
    public function supprimer($id)
    {
        try {
            $this->db->beginTransaction();
            $perte = $this->getWithDetails($id);
            if (!$perte) throw new Exception("Perte non trouvée");

            // 1. Restaurer le stock
            $stockModel = new Stock();
            $btlParCaisse = (int)($perte['bouteilles_par_caisses'] ?? 24);
            $nbCaisses = (float)$perte['quantite'];
            $nbBouteilles = $nbCaisses * $btlParCaisse;

            $updateData = ($perte['type_stock'] === 'vide') 
                ? [
                    'quantite_vide' => $nbBouteilles,
                    'caisses_vide' => $nbCaisses
                  ] 
                : [
                    'quantite_pleine' => $nbBouteilles,
                    'caisses_pleine' => $nbCaisses
                  ];
            
            $stockModel->updateOrCreate($perte['produit_id'], $perte['emplacement_id'], $updateData);

            // 2. Créer un mouvement d'annulation
            $mouvementModel = new MouvementStock();
            $mouvementModel->create([
                'produit_id' => $perte['produit_id'],
                'emplacement_id' => $perte['emplacement_id'],
                'type_mouvement' => 'entree',
                'quantite' => $nbBouteilles,
                'reference_type' => 'perte_annulee',
                'reference_id' => $id,
                'motif' => 'Annulation Perte ID: ' . $id,
                'created_by' => $_SESSION['user_id']
            ]);

            // 3. Supprimer l'enregistrement
            $this->deleteLinkedManquantIfOpen($perte);
            $this->delete($id);

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function syncManquantForPerte(int $perteId, array $data): ?int
    {
        $agentId = !empty($data['agent_id']) ? (int) $data['agent_id'] : 0;
        $manquantId = !empty($data['manquant_id']) ? (int) $data['manquant_id'] : 0;

        if ($agentId <= 0) {
            if ($manquantId > 0) {
                $this->deleteLinkedManquantIfOpen(['manquant_id' => $manquantId]);
            }
            return null;
        }

        $quantite = max(0, (float) ($data['quantite'] ?? 0));
        $typeStock = $data['type_stock'] ?? 'plein';
        $montant = round(max(0, (float) ($data['valeur_perte'] ?? 0)), 2);
        $motif = trim((string) ($data['motif'] ?? ''));
        $produitLabel = trim(($data['produit_nom'] ?? '') . (!empty($data['produit_code']) ? ' (' . $data['produit_code'] . ')' : ''));

        $payload = [
            'agent_id' => $agentId,
            'mission_id' => null,
            'type_manquant' => 'perte',
            'produit_id' => !empty($data['produit_id']) ? (int) $data['produit_id'] : null,
            'quantite_caisses' => $typeStock === 'vide' ? 0 : $quantite,
            'quantite_emballages' => $typeStock === 'vide' ? $quantite : 0,
            'montant' => $montant,
            'date_manquant' => $data['date_perte'] ?? date('Y-m-d'),
            'motif' => 'Perte #' . $perteId . ($produitLabel !== '' ? ' - ' . $produitLabel : '') . ($motif !== '' ? ' : ' . $motif : ''),
            'notes_reglement' => 'Manquant genere automatiquement depuis la perte #' . $perteId . '. A regler dans le module manquants.',
            'created_by' => $data['created_by'] ?? ($_SESSION['user_id'] ?? null),
        ];

        if ($manquantId > 0 && $this->db->fetchColumn("SELECT COUNT(*) FROM manquants_agents WHERE id = :id", ['id' => $manquantId])) {
            $manquant = $this->db->fetch("SELECT * FROM manquants_agents WHERE id = :id LIMIT 1", ['id' => $manquantId]);
            $payload['quantite_caisses_reglee'] = min((float) ($manquant['quantite_caisses_reglee'] ?? 0), (float) $payload['quantite_caisses']);
            $payload['quantite_emballages_reglee'] = min((float) ($manquant['quantite_emballages_reglee'] ?? 0), (float) $payload['quantite_emballages']);
            $payload['montant_paye'] = min((float) ($manquant['montant_paye'] ?? 0), $montant);
            $payload['statut'] = $this->determineManquantStatus($payload);
            $payload['date_reglement'] = $payload['statut'] === 'paye' ? ($manquant['date_reglement'] ?? date('Y-m-d')) : null;
            $this->db->update('manquants_agents', $payload, 'id = :id', ['id' => $manquantId]);
            return $manquantId;
        }

        $payload['quantite_caisses_reglee'] = 0;
        $payload['quantite_emballages_reglee'] = 0;
        $payload['montant_paye'] = 0;
        $payload['statut'] = $this->determineManquantStatus($payload);

        return (int) $this->db->insert('manquants_agents', $payload);
    }

    private function determineManquantStatus(array $data): string
    {
        $resteMontant = max(0, (float) ($data['montant'] ?? 0) - (float) ($data['montant_paye'] ?? 0));
        $resteCaisses = max(0, (float) ($data['quantite_caisses'] ?? 0) - (float) ($data['quantite_caisses_reglee'] ?? 0));
        $resteEmballages = max(0, (float) ($data['quantite_emballages'] ?? 0) - (float) ($data['quantite_emballages_reglee'] ?? 0));

        if ($resteMontant <= 0.01 && $resteCaisses <= 0.0001 && $resteEmballages <= 0.0001) {
            return 'paye';
        }

        if ((float) ($data['montant_paye'] ?? 0) > 0 || (float) ($data['quantite_caisses_reglee'] ?? 0) > 0 || (float) ($data['quantite_emballages_reglee'] ?? 0) > 0) {
            return 'partiel';
        }

        return 'ouvert';
    }

    private function deleteLinkedManquantIfOpen(array $perte): void
    {
        $manquantId = (int) ($perte['manquant_id'] ?? 0);
        if ($manquantId <= 0) {
            return;
        }

        $manquant = $this->db->fetch("SELECT * FROM manquants_agents WHERE id = :id LIMIT 1", ['id' => $manquantId]);
        if (!$manquant) {
            return;
        }

        $hasSettlement = (float) ($manquant['montant_paye'] ?? 0) > 0
            || (float) ($manquant['quantite_caisses_reglee'] ?? 0) > 0
            || (float) ($manquant['quantite_emballages_reglee'] ?? 0) > 0
            || (int) $this->db->fetchColumn("SELECT COUNT(*) FROM manquant_paiements WHERE manquant_id = :id", ['id' => $manquantId]) > 0;

        if ($hasSettlement) {
            throw new Exception('Cette perte est deja liee a un manquant partiellement ou totalement regle. Annulez d abord le reglement du manquant avant de supprimer la perte.');
        }

        $this->db->delete('manquants_agents', 'id = :id', ['id' => $manquantId]);
    }
}
