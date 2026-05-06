<?php
/**
 * Modèle Mission
 */

class Mission extends Model
{
    protected $table = 'missions';
<<<<<<< HEAD
    protected $fillable = ['numero_mission', 'vehicule_id', 'chauffeur_id', 'date_depart', 'date_retour', 'zone_id', 'notes', 'statut', 'montant_encaisse', 'caisses_vides_retournees', 'created_by'];
=======
    protected $fillable = ['numero_mission', 'vehicule_id', 'chauffeur_id', 'date_depart', 'date_retour', 'zone_id', 'notes', 'statut', 'montant_encaisse', 'created_by'];
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
    
    /**
     * Générer un numéro de mission unique
     */
    public function generateNumeroMission()
    {
        $prefix = 'MSN-' . date('Ymd');
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
<<<<<<< HEAD
                            SELECT SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)))
=======
                            SELECT SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
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
<<<<<<< HEAD
                            SELECT SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)))
=======
                            SELECT SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
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

            $mission['montant_attendu'] = (float) ($mission['ventes']['total'] ?? 0);
            $mission['caisses_vendues_total'] = (int) ($mission['ventes']['caisses_vendues'] ?? 0);
<<<<<<< HEAD
            $mission['caisses_vides_retournees'] = (int) ($mission['caisses_vides_retournees'] ?? 0);
            $mission['retours_vides_total'] = $mission['caisses_vides_retournees'];
=======
            $mission['retours_vides_total'] = 0;
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
            
            // Calculer le total du chargement
            $total = 0;
            $totalCaisses = 0;
            foreach ($mission['chargements'] as &$item) {
                $btlParCaisse = (int) ($item['bouteilles_par_caisses'] ?? 24);
                if ($btlParCaisse <= 0) {
                    $btlParCaisse = 24;
                }

                $prixCaisse = $item['prix_vente_caisses'] ?: ($item['prix_vente_unitaire'] * $item['bouteilles_par_caisses']);
<<<<<<< HEAD
                $item['quantite_caisses'] = (int) ($item['quantite_caisses'] ?? intdiv((int) $item['quantite_chargee'], $btlParCaisse));
                $item['caisses_vendues'] = (int) intdiv((int) ($item['quantite_vendue'] ?? 0), $btlParCaisse);
                $item['montant_vendu'] = $item['caisses_vendues'] * $prixCaisse;
                $item['sous_total'] = $item['quantite_caisses'] * $prixCaisse;
=======
                $item['quantite_caisses'] = intdiv((int) $item['quantite_chargee'], $btlParCaisse);
                $item['caisses_vendues'] = (int) round(((int) ($item['quantite_vendue'] ?? 0)) / $btlParCaisse, 0);
                $item['montant_vendu'] = $item['caisses_vendues'] * $prixCaisse;
                $item['sous_total'] = ($item['quantite_chargee'] / $btlParCaisse) * $prixCaisse;
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                $total += $item['sous_total'];
                $totalCaisses += $item['quantite_caisses'];
            }
            $mission['total_chargement'] = $total;
            $mission['total_caisses'] = $totalCaisses;
            $mission['total_bouteilles'] = array_sum(array_map(static function ($item) {
                return (int) ($item['quantite_chargee'] ?? 0);
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
             WHERE m.statut = 'en_cours'
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
            
            foreach ($chargements as $chargement) {
<<<<<<< HEAD
=======
                $chargement['mission_id'] = $missionId;
                $this->db->insert('mission_chargements', $chargement);

>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                $produit = (new Produit())->find($chargement['produit_id']);
                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }
<<<<<<< HEAD

                $quantiteCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
                if ($quantiteCaisses <= 0) {
                    $quantiteCaisses = (int) floor(((int) ($chargement['quantite_chargee'] ?? 0)) / $bouteillesParCaisse);
                }

                $quantiteBouteilles = $quantiteCaisses * $bouteillesParCaisse;
                $chargement['quantite_caisses'] = $quantiteCaisses;
                $chargement['quantite_chargee'] = $quantiteBouteilles;
                $chargement['prix_caisse'] = (float) ($produit['prix_vente_caisses'] ?: (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse));
                $chargement['mission_id'] = $missionId;
                $this->db->insert('mission_chargements', $chargement);
=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                
                // Transférer du stock principal vers le véhicule
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementPrincipalId,
                    [
<<<<<<< HEAD
                        'quantite_pleine' => -$quantiteBouteilles,
                        'caisses_pleine' => -$quantiteCaisses
=======
                        'quantite_pleine' => -$chargement['quantite_chargee'],
                        'caisses_pleine' => -intval($chargement['quantite_chargee'] / $bouteillesParCaisse)
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                    ]
                );
                
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementVehicule,
                    [
<<<<<<< HEAD
                        'quantite_pleine' => $quantiteBouteilles,
                        'caisses_pleine' => $quantiteCaisses
=======
                        'quantite_pleine' => $chargement['quantite_chargee'],
                        'caisses_pleine' => intval($chargement['quantite_chargee'] / $bouteillesParCaisse)
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                    ]
                );
                
                // Enregistrer le mouvement de transfert
                $mouvementModel->create([
                    'produit_id' => $chargement['produit_id'],
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
<<<<<<< HEAD
                    'quantite' => -$quantiteBouteilles,
=======
                    'quantite' => -$chargement['quantite_chargee'],
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
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
     * Terminer une mission avec retour de vides et réintégration des invendus
     */
    public function terminer($id, $invendus, $vides_retournes, $montant_encaisse, $emplacementPrincipalId)
    {
        try {
            $this->db->beginTransaction();
            
            $mission = $this->getWithDetails($id);
            if (!$mission) throw new Exception("Mission non trouvée");

            $vehicule = (new Vehicule())->find($mission['vehicule_id']);
            $emplacementVehicule = $vehicule['emplacement_id'];
            
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            
            // 1. Gérer les INVENDUS (Produits pleins qui reviennent à l'entrepôt)
            foreach ($invendus as $produitId => $quantiteInvendue) {
                // Mettre à jour le chargement avec ce qui revient
                $this->db->query(
                    "UPDATE mission_chargements SET quantite_retournee = ? 
                     WHERE mission_id = ? AND produit_id = ?",
                    [$quantiteInvendue, $id, $produitId]
                );
                
                if ($quantiteInvendue > 0) {
                    // Transférer du véhicule vers l'entrepôt principal
                    $stockModel->updateOrCreate($produitId, $emplacementVehicule, [
                        'quantite_pleine' => -$quantiteInvendue
                    ]);
                    
                    $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, [
                        'quantite_pleine' => $quantiteInvendue
                    ]);

                    // Mouvement de stock pour la réintégration
                    $mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementPrincipalId,
                        'type_mouvement' => 'entree',
                        'quantite' => $quantiteInvendue,
                        'reference_type' => 'mission',
                        'reference_id' => $id,
                        'motif' => 'Réintégration invendus mission ' . $mission['numero_mission'],
                        'created_by' => $_SESSION['user_id']
                    ]);
                }
            }

            // 2. Gérer les VIDES retournés
<<<<<<< HEAD
            $totalVidesRetournes = 0;
            foreach ($vides_retournes as $produitId => $nbCaissesVides) {
                if ($nbCaissesVides > 0) {
                    $totalVidesRetournes += (int) $nbCaissesVides;
=======
            foreach ($vides_retournes as $produitId => $nbCaissesVides) {
                if ($nbCaissesVides > 0) {
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
                    // Les vides retournés vont à l'entrepôt principal
                    $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, [
                        'caisses_vide' => $nbCaissesVides
                    ]);

                    // Mouvement pour les vides
                    $mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementPrincipalId,
                        'type_mouvement' => 'entree',
                        'quantite' => 0,
                        'reference_type' => 'mission',
                        'reference_id' => $id,
                        'motif' => 'Retour emballages vides mission ' . $mission['numero_mission'],
                        'created_by' => $_SESSION['user_id']
                    ]);
                }
            }
            
            // 3. Clôturer la mission
            $updateData = [
                'statut' => 'terminee',
                'date_retour' => date('Y-m-d H:i:s'),
                'notes' => ($mission['notes'] ?? '') . "\nMission terminée. Montant encaissé: " . $montant_encaisse
            ];

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

<<<<<<< HEAD
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

=======
>>>>>>> 4dfb7cff4d92b9d22e94a6ec77f9e0d319c68f13
            $this->update($id, $updateData);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
