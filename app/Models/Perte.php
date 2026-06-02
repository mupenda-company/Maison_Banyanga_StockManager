<?php
/**
 * Modèle Perte
 */

class Perte extends Model
{
    protected $table = 'pertes';
    protected $fillable = ['produit_id', 'emplacement_id', 'quantite', 'type_perte', 'motif', 'date_perte', 'valeur_perte', 'agent_id', 'created_by', 'type_stock'];
    
    /**
     * Récupérer avec les détails
     */
    public function getWithDetails($id)
    {
        return $this->db->fetch(
            "SELECT p.*, pr.nom as produit_nom, pr.code as produit_code, pr.bouteilles_par_caisses,
                    e.nom as emplacement_nom, e.type as emplacement_type,
                    u.nom as created_by_nom, u.prenom as created_by_prenom,
                    a.nom as agent_nom, a.prenom as agent_prenom
             FROM {$this->table} p
             JOIN produits pr ON p.produit_id = pr.id
             JOIN emplacements e ON p.emplacement_id = e.id
             LEFT JOIN users u ON p.created_by = u.id
             LEFT JOIN users a ON p.agent_id = a.id
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
                    a.nom as agent_nom, a.prenom as agent_prenom
             FROM {$this->table} p
             JOIN produits pr ON p.produit_id = pr.id
             JOIN emplacements e ON p.emplacement_id = e.id
             LEFT JOIN users a ON p.agent_id = a.id
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
            $this->delete($id);

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
