<?php

class MobileController extends Controller {
    
    /**
     * Authentification du vendeur
     */
    public function login() {
        $data = $this->getJsonInput();
        $auth = new Auth();
        $user = $auth->attempt($data['email'], $data['password']);
        
        if ($user) {
            // Récupérer la mission en cours pour ce vendeur
            $missionModel = new Mission();
            $mission = (new Mission())->db->fetch(
                "SELECT * FROM missions WHERE chauffeur_id = ? AND statut = 'en_cours' LIMIT 1",
                [$user['id']]
            );
            
            return $this->success([
                'user' => $user,
                'mission' => $mission
            ]);
        }
        return $this->error('Identifiants invalides', 401);
    }

    /**
     * Voir le stock disponible dans le véhicule de la mission
     */
    public function getStock($missionId) {
        $sql = "SELECT p.id, p.nom, p.code, mc.quantite_chargee, 
                (mc.quantite_chargee - IFNULL(mc.quantite_vendue, 0)) as stock_actuel
                FROM mission_chargements mc
                JOIN produits p ON mc.produit_id = p.id
                WHERE mc.mission_id = ?";
        $stock = (new Mission())->db->fetchAll($sql, [$missionId]);
        return $this->success($stock);
    }

    /**
     * Enregistrer une vente depuis le mobile
     */
    public function storeVente() {
        $data = $this->getJsonInput(); // client_id, mission_id, produits[], total
        
        try {
            $this->db->beginTransaction();

            $venteModel = new Vente();
            $venteId = $venteModel->create([
                'numero_facture' => 'MOB-' . date('YmdHis'),
                'client_id' => $data['client_id'],
                'mission_id' => $data['mission_id'],
                'total_ttc' => $data['total'],
                'statut' => 'validee',
                'created_by' => $data['user_id']
            ]);

            foreach ($data['produits'] as $item) {
                // 1. Ajouter l'item de vente
                $this->db->insert('vente_items', [
                    'vente_id' => $venteId,
                    'produit_id' => $item['produit_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix']
                ]);

                // 2. Déduire du stock de la mission (mission_chargements)
                $this->db->query(
                    "UPDATE mission_chargements 
                     SET quantite_vendue = IFNULL(quantite_vendue, 0) + ? 
                     WHERE mission_id = ? AND produit_id = ?",
                    [$item['quantite'], $data['mission_id'], $item['produit_id']]
                );
            }

            $this->db->commit();
            return $this->success(['vente_id' => $venteId], 'Vente enregistrée');

        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
