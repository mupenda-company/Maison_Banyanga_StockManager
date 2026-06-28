<?php
/**
 * Modèle Vente
 */

class Vente extends Model
{
    protected $table = 'ventes';
    protected $fillable = ['numero_facture', 'client_id', 'date_vente', 'mission_id', 'emplacement_id', 'total_ht', 'total_tva', 'total_ttc', 'statut', 'notes', 'created_by'];
    
    /**
     * Générer un numéro de facture unique
     */
    public function generateNumeroFacture($prefix = 'FAC-')
    {
        $prefix = rtrim((string) $prefix);
        if ($prefix === '') {
            $prefix = 'FAC-';
        }

        if (substr($prefix, -1) !== '-') {
            $prefix .= '-';
        }

        $row = $this->db->fetch(
            "SELECT MAX(CAST(SUBSTRING(numero_facture, :offset) AS UNSIGNED)) as max_num
             FROM {$this->table} 
             WHERE numero_facture LIKE :prefix",
            ['prefix' => $prefix . '%', 'offset' => strlen($prefix) + 1]
        );

        $num = ((int) ($row['max_num'] ?? 0)) + 1;

        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Récupérer avec les détails
     */
    public function getWithDetails($id)
    {
        $vente = $this->db->fetch(
            "SELECT v.*, u.nom as created_by_nom, u.prenom as created_by_prenom, 
                    e.nom as emplacement_nom, c.nom as client_nom, c.telephone as client_telephone,
                    c.numero_client as client_numero, c.adresse as client_adresse, z.nom as zone_nom
             FROM {$this->table} v
             LEFT JOIN users u ON v.created_by = u.id
             LEFT JOIN emplacements e ON v.emplacement_id = e.id
             LEFT JOIN clients c ON v.client_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE v.id = :id",
            ['id' => $id]
        );
        
        if ($vente) {
            $vente['client'] = $this->db->fetch(
                "SELECT c.*, z.nom as zone_nom
                 FROM clients c
                 LEFT JOIN zones z ON c.zone_id = z.id
                 WHERE c.id = :id",
                ['id' => $vente['client_id']]
            );
            
            $vente['details'] = $this->db->fetchAll(
                "SELECT vd.*, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses
                 FROM vente_details vd
                 JOIN produits p ON vd.produit_id = p.id
                 WHERE vd.vente_id = :id",
                ['id' => $id]
            );

            $vente['emballages_recus'] = $this->db->fetchAll(
                "SELECT ver.*, p.nom as produit_nom, p.code as produit_code, p.bouteilles_par_caisses
                 FROM vente_emballages_recus ver
                 JOIN produits p ON ver.produit_id = p.id
                 WHERE ver.vente_id = :id",
                ['id' => $id]
            );
        }
        
        return $vente;
    }
    
    /**
     * Récupérer les ventes avec client
     */
    public function getAllWithClient($page = 1, $perPage = 20, $filters = [])
    {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['client_id'])) {
            $where .= " AND v.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }
        
        if (!empty($filters['date_debut'])) {
            $where .= " AND v.date_vente >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where .= " AND v.date_vente <= :date_fin";
            $params['date_fin'] = str_contains((string) $filters['date_fin'], ':')
                ? $filters['date_fin']
                : ($filters['date_fin'] . ' 23:59:59');
        }
        
        if (!empty($filters['emplacement_id'])) {
            $where .= " AND v.emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $filters['emplacement_id'];
        }
        
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} v WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT v.*, c.nom as client_nom, c.telephone as client_telephone,
                       e.nom as emplacement_nom, e.type as emplacement_type
                FROM {$this->table} v
                LEFT JOIN clients c ON v.client_id = c.id
                LEFT JOIN emplacements e ON v.emplacement_id = e.id
                WHERE {$where}
                ORDER BY v.date_vente DESC
                LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }
    
    /**
     * Créer une vente avec détails
     */
    public function createWithDetails($data, $details, $emballagesRecus = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Créer la vente
            $venteId = $this->create($data);
            
            // Créer les détails et mettre à jour le stock
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $produitModel = new Produit();
            $empruntModel = new EmpruntEmballage();
            $emballagesRecus = $this->normaliserEmballagesRecus($emballagesRecus, $details);
            $totalVidesRecusPhysiques = array_sum($emballagesRecus);
            $totalCaissesVendues = 0;
            foreach ($details as $detailForTotal) {
                $produitForTotal = $produitModel->find($detailForTotal['produit_id']);
                $btlForTotal = max(1, (int) ($produitForTotal['bouteilles_par_caisses'] ?? 24));
                $totalCaissesVendues += (int) ($detailForTotal['quantite_caisses'] ?? intdiv((int) ($detailForTotal['quantite'] ?? 0), $btlForTotal));
            }

            if ($totalVidesRecusPhysiques > $totalCaissesVendues) {
                throw new Exception('Le total des emballages recus ne peut pas depasser le total des caisses vendues.');
            }

            $videsRestantsAAffecter = $totalVidesRecusPhysiques;
            
            foreach ($details as $detail) {
                $detail['vente_id'] = $venteId;

                if (!array_key_exists('caisses_vides_recues', $detail) || $detail['caisses_vides_recues'] === '' || $detail['caisses_vides_recues'] === null) {
                    throw new Exception('Les emballages reçus doivent être renseignés pour chaque ligne de vente.');
                }

                if (!is_numeric($detail['caisses_vides_recues'])) {
                    throw new Exception('Les emballages reçus doivent être un nombre valide.');
                }
                
                $produit = $produitModel->find($detail['produit_id']);
                $btlParCaisse = (int)($produit['bouteilles_par_caisses'] ?? 24);
                $quantiteCaisses = (int) ($detail['quantite_caisses'] ?? intdiv((int) $detail['quantite'], $btlParCaisse));
                if ($quantiteCaisses <= 0) {
                    $quantiteCaisses = 1;
                }
                $caissesVidesAffectees = max(0, min($quantiteCaisses, $videsRestantsAAffecter));
                $videsRestantsAAffecter -= $caissesVidesAffectees;
                $creditEmballagesUtilise = 0;
                if (!empty($data['client_id'])) {
                    $creditEmballagesUtilise = $empruntModel->utiliserCreditClient(
                        $data['client_id'],
                        $detail['produit_id'],
                        max(0, $quantiteCaisses - $caissesVidesAffectees)
                    );
                }
                $caissesVidesRecues = min($quantiteCaisses, $caissesVidesAffectees + $creditEmballagesUtilise);
                $quantiteBouteilles = $quantiteCaisses * $btlParCaisse;
                $prixCaisse = (float) ($detail['prix_caisse'] ?? ($produit['prix_vente_caisses'] ?? ($produit['prix_vente_unitaire'] * $btlParCaisse)));
                if ($prixCaisse <= 0) {
                    $prixCaisse = (float) (($produit['prix_vente_unitaire'] ?? 0) * $btlParCaisse);
                }
                
                // VÉRIFICATION DU STOCK DISPONIBLE
                $currentStock = $this->db->fetch(
                    "SELECT quantite_pleine FROM stocks WHERE produit_id = :p AND emplacement_id = :e",
                    ['p' => $detail['produit_id'], 'e' => $data['emplacement_id']]
                );

                if (!$currentStock || $currentStock['quantite_pleine'] < $quantiteBouteilles) {
                    $dispo = $currentStock ? ($currentStock['quantite_pleine'] / $btlParCaisse) : 0;
                    throw new Exception("Stock insuffisant pour {$produit['nom']}. Disponible: " . number_format($dispo, 1) . " cs");
                }

                $this->db->insert('vente_details', [
                    'vente_id' => $venteId,
                    'produit_id' => $detail['produit_id'],
                    'quantite_caisses' => $quantiteCaisses,
                    'caisses_vides_recues' => $caissesVidesRecues,
                    'quantite' => $quantiteBouteilles,
                    'prix_unitaire' => $prixCaisse / $btlParCaisse,
                    'prix_caisse' => $prixCaisse,
                    'sous_total' => $quantiteCaisses * $prixCaisse
                ]);
                
                // Déduire du stock (PLEIN)
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $data['emplacement_id'],
                    [
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => -$quantiteCaisses
                    ]
                );
                
                // Enregistrer le mouvement PLEIN (Sortie)
                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => $data['emplacement_id'],
                    'type_mouvement' => 'sortie',
                    'quantite' => -$quantiteBouteilles,
                    'reference_type' => 'vente',
                    'reference_id' => $venteId,
                    'motif' => 'Vente N° ' . $data['numero_facture'] . ' - Sortie des caisses pleines',
                    'created_by' => $data['created_by']
                ]);

            }


            foreach ($emballagesRecus as $produitId => $caissesRecues) {
                $caissesRecues = (int) $caissesRecues;
                if ($caissesRecues <= 0) {
                    continue;
                }

                $produit = $produitModel->find($produitId);
                if (!$produit) {
                    throw new Exception('Produit emballage introuvable.');
                }

                $btlParCaisse = max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24));
                $this->db->insert('vente_emballages_recus', [
                    'vente_id' => $venteId,
                    'produit_id' => $produitId,
                    'caisses_recues' => $caissesRecues
                ]);

                $stockModel->updateOrCreate(
                    $produitId,
                    $data['emplacement_id'],
                    [
                        'quantite_vide' => $caissesRecues * $btlParCaisse,
                        'caisses_vide' => $caissesRecues
                    ]
                );

                $mouvementModel->create([
                    'produit_id' => $produitId,
                    'emplacement_id' => $data['emplacement_id'],
                    'type_mouvement' => 'entree',
                    'quantite' => $caissesRecues * $btlParCaisse,
                    'reference_type' => 'vente',
                    'reference_id' => $venteId,
                    'motif' => 'Vente N° ' . $data['numero_facture'] . ' - Entree des emballages vides',
                    'created_by' => $data['created_by']
                ]);
            }
            // DÉCLENCHER LES ALERTES IMMÉDIATEMENT
            (new Alerte())->checkStockAlerts();
            
            $this->db->commit();
            return ['success' => true, 'id' => $venteId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function normaliserEmballagesRecus($emballagesRecus, array $details)
    {
        $result = [];

        if (is_array($emballagesRecus) && !empty($emballagesRecus)) {
            foreach ($emballagesRecus as $ligne) {
                if (!is_array($ligne)) {
                    continue;
                }

                $produitId = (int) ($ligne['produit_id'] ?? 0);
                $caisses = (int) ($ligne['caisses_recues'] ?? $ligne['caisses'] ?? 0);
                if ($produitId > 0 && $caisses > 0) {
                    $result[$produitId] = ($result[$produitId] ?? 0) + $caisses;
                }
            }

            return $result;
        }

        foreach ($details as $detail) {
            $produitId = (int) ($detail['produit_id'] ?? 0);
            $caisses = (int) ($detail['caisses_vides_recues'] ?? 0);
            if ($produitId > 0 && $caisses > 0) {
                $result[$produitId] = ($result[$produitId] ?? 0) + $caisses;
            }
        }

        return $result;
    }
    
    /**
     * Statistiques globales de ventes
     */
    public function getStatsGlobales($dateDebut, $dateFin)
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_ventes,
                COALESCE(SUM(total_ttc), 0) as ca_total,
                COALESCE(SUM(total_tva), 0) as tva_total,
                (SELECT COUNT(*) FROM ventes WHERE statut = 'annulee' AND date_vente BETWEEN :d1 AND :d2) as nb_annulees
             FROM {$this->table} 
             WHERE statut = 'validee' AND date_vente BETWEEN :d3 AND :d4",
            ['d1' => $dateDebut, 'd2' => $dateFin, 'd3' => $dateDebut, 'd4' => $dateFin]
        );
    }

    /**
     * Ventes groupées par zone
     */
    public function getVentesParZone($dateDebut, $dateFin)
    {
        return $this->db->fetchAll(
            "SELECT z.nom as zone_nom, COUNT(v.id) as nb_ventes, SUM(v.total_ttc) as total_ca
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             JOIN zones z ON c.zone_id = z.id
             WHERE v.statut = 'validee' AND v.date_vente BETWEEN :d1 AND :d2
             GROUP BY z.id
             ORDER BY total_ca DESC",
            ['d1' => $dateDebut, 'd2' => $dateFin]
        );
    }

    /**
     * Ventes validées par agent sur une période donnée
     */
    public function getVentesParAgent($dateDebut, $dateFin = null)
    {
        if ($dateFin === null) {
            $dateFin = $dateDebut;
        }

        $dateDebut = str_contains((string) $dateDebut, ':')
            ? (string) $dateDebut
            : ((string) $dateDebut . ' 00:00:00');

        $dateFin = str_contains((string) $dateFin, ':')
            ? (string) $dateFin
            : ((string) $dateFin . ' 23:59:59');

        return $this->db->fetchAll(
            "SELECT v.id, v.numero_facture, v.date_vente, v.total_ht, v.total_tva, v.total_ttc,
                    v.statut, v.notes,
                    v.created_by,
                    u.nom as agent_nom, u.prenom as agent_prenom, u.role as agent_role,
                    c.nom as client_nom, e.nom as emplacement_nom,
                    COALESCE(vd_totaux.total_caisses, 0) as total_caisses
             FROM {$this->table} v
             LEFT JOIN users u ON v.created_by = u.id
             LEFT JOIN clients c ON v.client_id = c.id
             LEFT JOIN emplacements e ON v.emplacement_id = e.id
             LEFT JOIN (
                SELECT vd.vente_id,
                       SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))) as total_caisses
                FROM vente_details vd
                JOIN produits p ON vd.produit_id = p.id
                GROUP BY vd.vente_id
             ) vd_totaux ON vd_totaux.vente_id = v.id
             WHERE v.statut = 'validee'
               AND v.date_vente BETWEEN :date_debut AND :date_fin
             ORDER BY COALESCE(u.prenom, ''), COALESCE(u.nom, ''), v.date_vente ASC",
            [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ]
        );
    }
    public function annuler($id)
    {
        try {
            $this->db->beginTransaction();
            
            $vente = $this->getWithDetails($id);
            if (!$vente) {
                throw new Exception('Vente introuvable.');
            }

            if (($vente['statut'] ?? '') === 'annulee') {
                throw new Exception('Cette vente est déjà annulée.');
            }

            $emplacementRetourId = (int) ($vente['emplacement_id'] ?? 0);
            $missionId = (int) ($vente['mission_id'] ?? 0);

            /**
             * Une vente faite depuis le téléphone est liée à une mission.
             * Dans ce cas, l'emplacement de retour doit toujours être celui du véhicule,
             * même si l'emplacement enregistré sur la facture a été modifié ou mal renseigné.
             */
            if ($missionId > 0) {
                $missionVehicule = $this->db->fetch(
                    "SELECT v.emplacement_id AS vehicule_emplacement_id
                     FROM missions m
                     JOIN vehicules v ON m.vehicule_id = v.id
                     WHERE m.id = :mission_id
                     LIMIT 1",
                    ['mission_id' => $missionId]
                );

                if (!empty($missionVehicule['vehicule_emplacement_id'])) {
                    $emplacementRetourId = (int) $missionVehicule['vehicule_emplacement_id'];
                }
            }

            if ($emplacementRetourId <= 0) {
                throw new Exception('Emplacement de retour introuvable pour cette vente.');
            }
            
            // Marquer comme annulée
            $this->update($id, ['statut' => 'annulee']);
            
            // Reverser le stock au bon emplacement: véhicule si mission mobile, sinon entrepôt/emplacement de la facture.
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $emballagesRecus = $vente['emballages_recus'] ?? [];

            foreach ($vente['details'] as $detail) {
                $btlParCaisse = (int) ($detail['bouteilles_par_caisses'] ?? 24);
                if ($btlParCaisse <= 0) {
                    $btlParCaisse = 24;
                }

                $quantiteBouteilles = (int) ($detail['quantite'] ?? 0);
                $quantiteCaisses = (int) ($detail['quantite_caisses'] ?? intdiv($quantiteBouteilles, $btlParCaisse));

                if ($quantiteBouteilles <= 0 || $quantiteCaisses <= 0) {
                    continue;
                }

                // Remettre les caisses pleines dans le véhicule ou l'entrepôt concerné.
                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    $emplacementRetourId,
                    [
                        'quantite_pleine' => $quantiteBouteilles,
                        'caisses_pleine' => $quantiteCaisses
                    ]
                );

                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => $emplacementRetourId,
                    'type_mouvement' => 'entree',
                    'quantite' => $quantiteBouteilles,
                    'reference_type' => 'annulation_vente',
                    'reference_id' => $id,
                    'motif' => 'Annulation vente N° ' . ($vente['numero_facture'] ?? '') . ' - Retour des caisses pleines',
                    'created_by' => $_SESSION['user_id'] ?? ($vente['created_by'] ?? null)
                ]);

                // Si la vente vient du mobile, corriger aussi le suivi du chargement de la mission.
                if ($missionId > 0) {
                    $this->db->query(
                        "UPDATE mission_chargements
                         SET quantite_vendue = GREATEST(0, IFNULL(quantite_vendue, 0) - :quantite)
                         WHERE mission_id = :mission_id AND produit_id = :produit_id",
                        [
                            'quantite' => $quantiteBouteilles,
                            'mission_id' => $missionId,
                            'produit_id' => $detail['produit_id']
                        ]
                    );
                }
            }

            if (empty($emballagesRecus)) {
                $emballagesRecus = array_map(function ($detail) {
                    return [
                        'produit_id' => $detail['produit_id'],
                        'caisses_recues' => $detail['caisses_vides_recues'] ?? 0,
                        'bouteilles_par_caisses' => $detail['bouteilles_par_caisses'] ?? 24
                    ];
                }, $vente['details']);
            }

            foreach ($emballagesRecus as $emballage) {
                $caissesVidesRecues = (int) ($emballage['caisses_recues'] ?? 0);
                if ($caissesVidesRecues <= 0) {
                    continue;
                }

                $btlParCaisse = (int) ($emballage['bouteilles_par_caisses'] ?? 24);
                if ($btlParCaisse <= 0) {
                    $btlParCaisse = 24;
                }

                $quantiteVides = $caissesVidesRecues * $btlParCaisse;

                // L'annulation retire les emballages vides qui étaient entrés lors de la vente.
                $stockModel->updateOrCreate(
                    $emballage['produit_id'],
                    $emplacementRetourId,
                    [
                        'quantite_vide' => -$quantiteVides,
                        'caisses_vide' => -$caissesVidesRecues
                    ]
                );

                $mouvementModel->create([
                    'produit_id' => $emballage['produit_id'],
                    'emplacement_id' => $emplacementRetourId,
                    'type_mouvement' => 'sortie',
                    'quantite' => -$quantiteVides,
                    'reference_type' => 'annulation_vente',
                    'reference_id' => $id,
                    'motif' => 'Annulation vente N° ' . ($vente['numero_facture'] ?? '') . ' - Retrait des emballages vides',
                    'created_by' => $_SESSION['user_id'] ?? ($vente['created_by'] ?? null)
                ]);
            }
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiques de ventes
     */
    public function getStats($dateDebut, $dateFin, $emplacementId = null)
    {
        $where = "date_vente BETWEEN :date_debut AND :date_fin AND statut = 'validee'";
        $params = [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        
        if ($emplacementId) {
            $where .= " AND emplacement_id = :emplacement_id";
            $params['emplacement_id'] = $emplacementId;
        }

        $caissesWhere = "v2.date_vente BETWEEN :caisses_date_debut AND :caisses_date_fin AND v2.statut = 'validee'";
        if ($emplacementId) {
            $caissesWhere .= " AND v2.emplacement_id = :caisses_emplacement_id";
        }

        $params['caisses_date_debut'] = $dateDebut;
        $params['caisses_date_fin'] = $dateFin;
        if ($emplacementId) {
            $params['caisses_emplacement_id'] = $emplacementId;
        }

        return $this->db->fetch(
            "SELECT 
                COUNT(*) as nb_ventes,
                SUM(total_ht) as total_ht,
                SUM(total_tva) as total_tva,
                SUM(total_ttc) as total_ttc,
                AVG(total_ttc) as moyenne_vente,
                (
                    SELECT COALESCE(SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))), 0)
                    FROM vente_details vd
                    JOIN ventes v2 ON vd.vente_id = v2.id
                    JOIN produits p ON vd.produit_id = p.id
                    WHERE {$caissesWhere}
                ) as caisses_vendues
             FROM {$this->table}
             WHERE {$where}",
            $params
        );
    }
    
    /**
     * Ventes par produit
     */
    public function getVentesParProduit($dateDebut, $dateFin)
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.code, p.nom,
                    SUM(vd.quantite) as quantite_vendue,
                    SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))) as total_caisses,
                    SUM(vd.sous_total) as total_vente
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.date_vente BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'
             GROUP BY p.id
             ORDER BY total_caisses DESC",
            ['date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );
    }
    public function updateWithDetails($id, $data, $details, $emballagesRecus = null)
    {
        try {
            $this->db->beginTransaction();

            $ancienneVente = $this->getWithDetails($id);

            if (!$ancienneVente) {
                throw new Exception('Vente non trouvée');
            }

            if (($ancienneVente['statut'] ?? '') !== 'validee') {
                throw new Exception('Seules les ventes validées peuvent être modifiées');
            }

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $produitModel = new Produit();

            $missionId = (int)($ancienneVente['mission_id'] ?? 0);
            $emplacementId = (int)($data['emplacement_id'] ?? $ancienneVente['emplacement_id'] ?? 0);

            // Sécurité : une facture mobile doit toujours impacter l'emplacement du véhicule de la mission.
            if ($missionId > 0) {
                $vehiculeEmplacementId = (int)$this->db->fetchColumn(
                    "SELECT v.emplacement_id
                     FROM missions m
                     JOIN vehicules v ON m.vehicule_id = v.id
                     WHERE m.id = :mission_id
                     LIMIT 1",
                    ['mission_id' => $missionId]
                );

                if ($vehiculeEmplacementId <= 0) {
                    throw new Exception('Emplacement du véhicule introuvable pour cette mission.');
                }

                $emplacementId = $vehiculeEmplacementId;
                $data['emplacement_id'] = $vehiculeEmplacementId;
            }

            if ($emplacementId <= 0) {
                throw new Exception('Emplacement de stock introuvable.');
            }

            // Anciennes quantités vendues par produit.
            $ancien = [];
            foreach ($ancienneVente['details'] as $d) {
                $produitId = (int)$d['produit_id'];
                if ($produitId <= 0) continue;

                if (!isset($ancien[$produitId])) {
                    $ancien[$produitId] = ['caisses' => 0, 'quantite' => 0];
                }

                $btl = max(1, (int)($d['bouteilles_par_caisses'] ?? 24));
                $caisses = (int)($d['quantite_caisses'] ?? intdiv((int)($d['quantite'] ?? 0), $btl));
                $quantite = (int)($d['quantite'] ?? ($caisses * $btl));

                $ancien[$produitId]['caisses'] += $caisses;
                $ancien[$produitId]['quantite'] += $quantite;
            }

            // Nouvelles quantités vendues par produit.
            $nouveau = [];
            foreach ($details as $d) {
                $produitId = (int)($d['produit_id'] ?? 0);
                if ($produitId <= 0) continue;

                $produit = $produitModel->find($produitId);
                if (!$produit) {
                    throw new Exception('Produit introuvable.');
                }

                $btl = max(1, (int)($produit['bouteilles_par_caisses'] ?? 24));
                $caisses = max(0, (int)($d['quantite_caisses'] ?? intdiv((int)($d['quantite'] ?? 0), $btl)));
                $quantite = $caisses * $btl;

                if (!isset($nouveau[$produitId])) {
                    $nouveau[$produitId] = ['caisses' => 0, 'quantite' => 0];
                }

                $nouveau[$produitId]['caisses'] += $caisses;
                $nouveau[$produitId]['quantite'] += $quantite;
            }

            // 1) Synchroniser les caisses pleines : stock véhicule/entrepôt + stock mission si mobile.
            $produitsIds = array_unique(array_merge(array_keys($ancien), array_keys($nouveau)));

            foreach ($produitsIds as $produitId) {
                $ancienneQte = (int)($ancien[$produitId]['quantite'] ?? 0);
                $nouvelleQte = (int)($nouveau[$produitId]['quantite'] ?? 0);
                $ancienneCaisses = (int)($ancien[$produitId]['caisses'] ?? 0);
                $nouvelleCaisses = (int)($nouveau[$produitId]['caisses'] ?? 0);

                $diffQte = $nouvelleQte - $ancienneQte;
                $diffCaisses = $nouvelleCaisses - $ancienneCaisses;

                if ($diffQte === 0 && $diffCaisses === 0) {
                    continue;
                }

                if ($diffQte > 0) {
                    $stock = $this->db->fetch(
                        "SELECT quantite_pleine, caisses_pleine
                         FROM stocks
                         WHERE produit_id = :p AND emplacement_id = :e
                         LIMIT 1",
                        ['p' => $produitId, 'e' => $emplacementId]
                    );

                    if (!$stock || (int)$stock['quantite_pleine'] < $diffQte) {
                        $produit = $produitModel->find($produitId);
                        $dispo = $stock ? (int)$stock['caisses_pleine'] : 0;
                        throw new Exception('Stock insuffisant pour ' . ($produit['nom'] ?? 'Produit') . '. Disponible: ' . $dispo . ' cs');
                    }
                }

                // Si on augmente la facture: on sort le supplément. Si on diminue: on remet la différence.
                $stockModel->updateOrCreate($produitId, $emplacementId, [
                    'quantite_pleine' => -$diffQte,
                    'caisses_pleine' => -$diffCaisses,
                ]);

                $mouvementModel->create([
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'type_mouvement' => $diffQte > 0 ? 'sortie' : 'entree',
                    'quantite' => -$diffQte,
                    'reference_type' => 'modification_vente',
                    'reference_id' => $id,
                    'motif' => 'Modification vente N° ' . ($ancienneVente['numero_facture'] ?? '') . ' - Ajustement caisses pleines',
                    'created_by' => $data['updated_by'] ?? $_SESSION['user_id'] ?? ($ancienneVente['created_by'] ?? null),
                ]);

                // Pour une vente mobile, l'API mission calcule le stock avec mission_chargements.quantite_vendue.
                if ($missionId > 0) {
                    $chargementExiste = (int)$this->db->fetchColumn(
                        "SELECT COUNT(*)
                         FROM mission_chargements
                         WHERE mission_id = :mission_id AND produit_id = :produit_id",
                        ['mission_id' => $missionId, 'produit_id' => $produitId]
                    );

                    if ($chargementExiste <= 0 && $diffQte > 0) {
                        $produit = $produitModel->find($produitId);
                        throw new Exception('Impossible d’ajouter ' . ($produit['nom'] ?? 'ce produit') . ' : ce produit n’est pas dans le chargement de la mission.');
                    }

                    if ($chargementExiste > 0) {
                        $this->db->query(
                            "UPDATE mission_chargements
                             SET quantite_vendue = GREATEST(0, IFNULL(quantite_vendue, 0) + :diff)
                             WHERE mission_id = :mission_id AND produit_id = :produit_id",
                            [
                                'diff' => $diffQte,
                                'mission_id' => $missionId,
                                'produit_id' => $produitId,
                            ]
                        );
                    }
                }
            }

            // 2) Synchroniser les emballages vides reçus : ce qui a changé doit changer aussi dans le stock.
            $anciensEmballages = $this->normaliserEmballagesRecus($ancienneVente['emballages_recus'] ?? null, $ancienneVente['details'] ?? []);
            $nouveauxEmballages = $this->normaliserEmballagesRecus($emballagesRecus, $details);
            $emballageProduitIds = array_unique(array_merge(array_keys($anciensEmballages), array_keys($nouveauxEmballages)));

            foreach ($emballageProduitIds as $produitId) {
                $anciennesCaissesVides = (int)($anciensEmballages[$produitId] ?? 0);
                $nouvellesCaissesVides = (int)($nouveauxEmballages[$produitId] ?? 0);
                $diffCaissesVides = $nouvellesCaissesVides - $anciennesCaissesVides;

                if ($diffCaissesVides === 0) {
                    continue;
                }

                $produit = $produitModel->find($produitId);
                if (!$produit) {
                    throw new Exception('Produit emballage introuvable.');
                }

                $btl = max(1, (int)($produit['bouteilles_par_caisses'] ?? 24));
                $diffQteVides = $diffCaissesVides * $btl;

                // Si on diminue les emballages reçus, on doit retirer du véhicule/entrepôt ce qui avait été ajouté avant.
                if ($diffCaissesVides < 0) {
                    $stockVide = $this->db->fetch(
                        "SELECT quantite_vide, caisses_vide
                         FROM stocks
                         WHERE produit_id = :p AND emplacement_id = :e
                         LIMIT 1",
                        ['p' => $produitId, 'e' => $emplacementId]
                    );

                    if (!$stockVide || (int)$stockVide['caisses_vide'] < abs($diffCaissesVides)) {
                        throw new Exception('Stock insuffisant en emballages vides pour ' . ($produit['nom'] ?? 'Produit') . '.');
                    }
                }

                $stockModel->updateOrCreate($produitId, $emplacementId, [
                    'quantite_vide' => $diffQteVides,
                    'caisses_vide' => $diffCaissesVides,
                ]);

                $mouvementModel->create([
                    'produit_id' => $produitId,
                    'emplacement_id' => $emplacementId,
                    'type_mouvement' => $diffCaissesVides > 0 ? 'entree' : 'sortie',
                    'quantite' => $diffQteVides,
                    'reference_type' => 'modification_vente',
                    'reference_id' => $id,
                    'motif' => 'Modification vente N° ' . ($ancienneVente['numero_facture'] ?? '') . ' - Ajustement emballages vides',
                    'created_by' => $data['updated_by'] ?? $_SESSION['user_id'] ?? ($ancienneVente['created_by'] ?? null),
                ]);
            }

            // 3) Remplacer les anciennes lignes par les nouvelles lignes de facture.
            $this->db->query("DELETE FROM vente_details WHERE vente_id = :id", ['id' => $id]);
            $this->db->query("DELETE FROM vente_emballages_recus WHERE vente_id = :id", ['id' => $id]);

            $this->update($id, [
                'client_id' => $data['client_id'],
                'emplacement_id' => $emplacementId,
                'total_ht' => $data['total_ht'],
                'total_tva' => $data['total_tva'],
                'total_ttc' => $data['total_ttc'],
                'notes' => $data['notes'] ?? '',
            ]);

            foreach ($details as $detail) {
                $produit = $produitModel->find($detail['produit_id']);
                if (!$produit) {
                    throw new Exception('Produit introuvable.');
                }

                $btl = max(1, (int)($produit['bouteilles_par_caisses'] ?? 24));
                $quantiteCaisses = (int)($detail['quantite_caisses'] ?? 0);
                $prixUnitaire = (float)($detail['prix_unitaire'] ?? 0);

                $this->db->insert('vente_details', [
                    'vente_id' => $id,
                    'produit_id' => (int)$detail['produit_id'],
                    'quantite_caisses' => $quantiteCaisses,
                    'caisses_vides_recues' => (int)($detail['caisses_vides_recues'] ?? 0),
                    'quantite' => $quantiteCaisses * $btl,
                    'prix_unitaire' => $prixUnitaire,
                    'prix_caisse' => $prixUnitaire * $btl,
                    'sous_total' => (float)($detail['sous_total'] ?? ($quantiteCaisses * $prixUnitaire * $btl)),
                ]);
            }

            foreach ($nouveauxEmballages as $produitId => $caissesRecues) {
                if ((int)$caissesRecues <= 0) continue;

                $this->db->insert('vente_emballages_recus', [
                    'vente_id' => $id,
                    'produit_id' => (int)$produitId,
                    'caisses_recues' => (int)$caissesRecues,
                ]);
            }

            (new Alerte())->checkStockAlerts();

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

}

