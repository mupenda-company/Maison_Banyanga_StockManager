<?php
/**
 * Modèle Mission
 */

class Mission extends Model
{
    protected $table = 'missions';
    protected $fillable = ['numero_mission', 'vehicule_id', 'chauffeur_id', 'date_depart', 'date_retour', 'zone_id', 'notes', 'statut', 'montant_encaisse', 'created_by'];
    
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
            
            // Calculer le total du chargement
            $total = 0;
            foreach ($mission['chargements'] as &$item) {
                $prixCaisse = $item['prix_vente_caisses'] ?: ($item['prix_vente_unitaire'] * $item['bouteilles_par_caisses']);
                $item['sous_total'] = ($item['quantite_chargee'] / $item['bouteilles_par_caisses']) * $prixCaisse;
                $total += $item['sous_total'];
            }
            $mission['total_chargement'] = $total;
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
                $chargement['mission_id'] = $missionId;
                $this->db->insert('mission_chargements', $chargement);
                
                // Transférer du stock principal vers le véhicule
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementPrincipalId,
                    [
                        'quantite_pleine' => -$chargement['quantite_chargee'],
                        'caisses_pleine' => -intval($chargement['quantite_chargee'] / 24) // Approximation
                    ]
                );
                
                $stockModel->updateOrCreate(
                    $chargement['produit_id'],
                    $emplacementVehicule,
                    [
                        'quantite_pleine' => $chargement['quantite_chargee'],
                        'caisses_pleine' => intval($chargement['quantite_chargee'] / 24)
                    ]
                );
                
                // Enregistrer le mouvement de transfert
                $mouvementModel->create([
                    'produit_id' => $chargement['produit_id'],
                    'emplacement_id' => $emplacementPrincipalId,
                    'type_mouvement' => 'transfert',
                    'quantite' => -$chargement['quantite_chargee'],
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
            foreach ($vides_retournes as $produitId => $nbCaissesVides) {
                if ($nbCaissesVides > 0) {
                    // Les vides retournés vont à l'entrepôt principal
                    $stockModel->updateOrCreate($produitId, $emplacementPrincipalId, [
                        'caisses_vide' => $nbCaissesVides
                    ]);

                    // Mouvement pour les vides
                    $mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementPrincipalId,
                        'type_mouvement' => 'entree_vide',
                        'quantite' => 0,
                        'caisses_vide' => $nbCaissesVides,
                        'reference_type' => 'mission',
                        'reference_id' => $id,
                        'motif' => 'Retour emballages vides mission ' . $mission['numero_mission'],
                        'created_by' => $_SESSION['user_id']
                    ]);
                }
            }
            
            // 3. Clôturer la mission
            $this->update($id, [
                'statut' => 'terminee',
                'date_retour' => date('Y-m-d H:i:s'),
                'montant_encaisse' => $montant_encaisse,
                'notes' => ($mission['notes'] ?? '') . "\nMission terminée. Montant encaissé: " . $montant_encaisse
            ]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
