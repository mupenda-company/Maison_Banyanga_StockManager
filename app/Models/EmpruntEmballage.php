<?php

class EmpruntEmballage extends Model
{
    protected $table = 'emprunts_emballages';
    protected $fillable = [
        'source_type', 'client_id', 'source_nom', 'source_contact', 'produit_id',
        'quantite_empruntee', 'quantite_utilisee', 'quantite_retournee',
        'emplacement_id', 'date_emprunt', 'statut', 'notes', 'created_by'
    ];

    public function getAllWithDetails($filters = [])
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['statut'])) {
            $where .= ' AND e.statut = :statut';
            $params['statut'] = $filters['statut'];
        }

        if (!empty($filters['source_type'])) {
            $where .= ' AND e.source_type = :source_type';
            $params['source_type'] = $filters['source_type'];
        }

        return $this->db->fetchAll(
            "SELECT e.*, c.nom as client_nom, p.nom as produit_nom, p.code as produit_code,
                    p.bouteilles_par_caisses, emp.nom as emplacement_nom,
                    (e.quantite_empruntee - e.quantite_utilisee - e.quantite_retournee) as reste_caisses
             FROM {$this->table} e
             LEFT JOIN clients c ON e.client_id = c.id
             JOIN produits p ON e.produit_id = p.id
             JOIN emplacements emp ON e.emplacement_id = emp.id
             WHERE {$where}
             ORDER BY e.date_emprunt DESC, e.id DESC",
            $params
        );
    }

    public function createWithStock($data)
    {
        try {
            $this->db->beginTransaction();

            $produit = (new Produit())->find($data['produit_id']);
            $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($btlParCaisse <= 0) {
                $btlParCaisse = 24;
            }

            $data['quantite_empruntee'] = (int) $data['quantite_empruntee'];
            $data['quantite_utilisee'] = 0;
            $data['quantite_retournee'] = 0;
            $data['statut'] = 'en_cours';

            $id = $this->create($data);

            $stockModel = new Stock();
            $stockModel->updateOrCreate($data['produit_id'], $data['emplacement_id'], [
                'quantite_vide' => $data['quantite_empruntee'] * $btlParCaisse,
                'caisses_vide' => $data['quantite_empruntee']
            ]);

            (new MouvementStock())->create([
                'produit_id' => $data['produit_id'],
                'emplacement_id' => $data['emplacement_id'],
                'type_mouvement' => 'entree',
                'quantite' => $data['quantite_empruntee'] * $btlParCaisse,
                'reference_type' => 'emprunt_emballage',
                'reference_id' => $id,
                'motif' => 'Emprunt emballages vides',
                'created_by' => $data['created_by']
            ]);

            $this->db->commit();
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function utiliserCreditClient($clientId, $produitId, $quantiteCaisses)
    {
        $resteAUtiliser = (int) $quantiteCaisses;
        $totalUtilise = 0;

        if ($resteAUtiliser <= 0) {
            return 0;
        }

        $emprunts = $this->db->fetchAll(
            "SELECT id, quantite_empruntee, quantite_utilisee, quantite_retournee
             FROM {$this->table}
             WHERE source_type = 'client'
               AND client_id = :client_id
               AND produit_id = :produit_id
               AND statut = 'en_cours'
             ORDER BY date_emprunt ASC, id ASC",
            ['client_id' => $clientId, 'produit_id' => $produitId]
        );

        foreach ($emprunts as $emprunt) {
            $disponible = (int) $emprunt['quantite_empruntee'] - (int) $emprunt['quantite_utilisee'] - (int) $emprunt['quantite_retournee'];
            if ($disponible <= 0) {
                continue;
            }

            $utilise = min($disponible, $resteAUtiliser);
            $nouveauUtilise = (int) $emprunt['quantite_utilisee'] + $utilise;
            $nouveauReste = (int) $emprunt['quantite_empruntee'] - $nouveauUtilise - (int) $emprunt['quantite_retournee'];

            $this->update($emprunt['id'], [
                'quantite_utilisee' => $nouveauUtilise,
                'statut' => $nouveauReste <= 0 ? 'solde' : 'en_cours'
            ]);

            $totalUtilise += $utilise;
            $resteAUtiliser -= $utilise;

            if ($resteAUtiliser <= 0) {
                break;
            }
        }

        return $totalUtilise;
    }

    public function rembourser($id, $quantiteCaisses, $emplacementId, $userId)
    {
        try {
            $this->db->beginTransaction();

            $emprunt = $this->find($id);
            if (!$emprunt || $emprunt['statut'] !== 'en_cours') {
                throw new Exception('Emprunt non trouve ou deja solde');
            }

            $reste = (int) $emprunt['quantite_empruntee'] - (int) $emprunt['quantite_utilisee'] - (int) $emprunt['quantite_retournee'];
            $quantiteCaisses = (int) $quantiteCaisses;
            if ($quantiteCaisses <= 0) {
                throw new Exception('La quantite doit etre superieure a zero');
            }
            if ($quantiteCaisses > $reste) {
                throw new Exception('La quantite remboursee depasse le reste a remettre');
            }

            $produit = (new Produit())->find($emprunt['produit_id']);
            $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($btlParCaisse <= 0) {
                $btlParCaisse = 24;
            }

            $stock = (new Stock())->getStock($emprunt['produit_id'], $emplacementId);
            if (!$stock || (int) $stock['caisses_vide'] < $quantiteCaisses) {
                throw new Exception('Stock de caisses vides insuffisant pour rembourser cet emprunt');
            }

            (new Stock())->updateOrCreate($emprunt['produit_id'], $emplacementId, [
                'quantite_vide' => -($quantiteCaisses * $btlParCaisse),
                'caisses_vide' => -$quantiteCaisses
            ]);

            $nouveauRetourne = (int) $emprunt['quantite_retournee'] + $quantiteCaisses;
            $nouveauReste = (int) $emprunt['quantite_empruntee'] - (int) $emprunt['quantite_utilisee'] - $nouveauRetourne;
            $this->update($id, [
                'quantite_retournee' => $nouveauRetourne,
                'statut' => $nouveauReste <= 0 ? 'solde' : 'en_cours'
            ]);

            (new MouvementStock())->create([
                'produit_id' => $emprunt['produit_id'],
                'emplacement_id' => $emplacementId,
                'type_mouvement' => 'sortie',
                'quantite' => -($quantiteCaisses * $btlParCaisse),
                'reference_type' => 'emprunt_emballage_rembourse',
                'reference_id' => $id,
                'motif' => 'Remboursement emprunt emballages',
                'created_by' => $userId
            ]);

            $this->db->commit();
            return ['success' => true, 'solde' => $nouveauReste <= 0];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
