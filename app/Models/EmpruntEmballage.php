<?php

class EmpruntEmballage extends Model
{
    protected $table = 'emprunts_emballages';
    protected $fillable = [
        'direction', 'type_stock', 'source_type', 'client_id', 'source_nom', 'source_contact', 'produit_id',
        'quantite_empruntee', 'quantite_utilisee', 'quantite_retournee',
        'emplacement_id', 'date_emprunt', 'statut', 'notes', 'created_by'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureDirectionColumn();
        $this->ensureTypeStockColumn();
    }

    private function ensureDirectionColumn(): void
    {
        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emprunts_emballages' AND COLUMN_NAME = 'direction'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE emprunts_emballages ADD direction ENUM('recu','donne') NOT NULL DEFAULT 'recu' AFTER id");
        }
    }

    private function ensureTypeStockColumn(): void
    {
        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emprunts_emballages' AND COLUMN_NAME = 'type_stock'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE emprunts_emballages ADD type_stock ENUM('vide','plein') NOT NULL DEFAULT 'vide' AFTER direction");
        }
    }
    private function normaliserGamme($nom)
    {
        $nom = strtoupper(trim((string) $nom));
        $nom = preg_replace('/\b\d+\s*CL\b/', '', $nom);
        $nom = preg_replace('/\b(BL|B|CAN|PET)\b/', '', $nom);
        $nom = preg_replace('/\s+/', ' ', trim($nom));
        return $nom;
    }

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

        if (!empty($filters['direction'])) {
            $where .= ' AND e.direction = :direction';
            $params['direction'] = $filters['direction'];
        }

        if (!empty($filters['type_stock'])) {
            $where .= ' AND e.type_stock = :type_stock';
            $params['type_stock'] = $filters['type_stock'];
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

            $data['direction'] = in_array(($data['direction'] ?? 'recu'), ['recu', 'donne'], true) ? $data['direction'] : 'recu';
            $data['type_stock'] = in_array(($data['type_stock'] ?? 'vide'), ['vide', 'plein'], true) ? $data['type_stock'] : 'vide';
            $data['quantite_empruntee'] = (int) $data['quantite_empruntee'];
            $data['quantite_utilisee'] = 0;
            $data['quantite_retournee'] = 0;
            $data['statut'] = 'en_cours';

            $id = $this->create($data);

            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();
            $quantiteBouteilles = $data['quantite_empruntee'] * $btlParCaisse;

            $isPlein = $data['type_stock'] === 'plein';
            $quantiteKey = $isPlein ? 'quantite_pleine' : 'quantite_vide';
            $caissesKey = $isPlein ? 'caisses_pleine' : 'caisses_vide';
            $labelStock = $isPlein ? 'produits pleins' : 'emballages vides';

            if ($data['direction'] === 'donne') {
                $stock = $stockModel->getStock($data['produit_id'], $data['emplacement_id']);
                if (!$stock || (int) ($stock[$caissesKey] ?? 0) < $data['quantite_empruntee']) {
                    throw new Exception('Stock ' . $labelStock . ' insuffisant : disponible ' . (int) ($stock[$caissesKey] ?? 0) . ' cs, demandé ' . $data['quantite_empruntee'] . ' cs');
                }

                $stockModel->updateOrCreate($data['produit_id'], $data['emplacement_id'], [
                    $quantiteKey => -$quantiteBouteilles,
                    $caissesKey => -$data['quantite_empruntee']
                ]);

                $mouvementModel->create([
                    'produit_id' => $data['produit_id'],
                    'emplacement_id' => $data['emplacement_id'],
                    'type_mouvement' => 'sortie',
                    'quantite' => -$quantiteBouteilles,
                    'reference_type' => 'emprunt_emballage',
                    'reference_id' => $id,
                    'motif' => ucfirst($labelStock) . ' prêtés',
                    'created_by' => $data['created_by']
                ]);
            } else {
                $stockModel->updateOrCreate($data['produit_id'], $data['emplacement_id'], [
                    $quantiteKey => $quantiteBouteilles,
                    $caissesKey => $data['quantite_empruntee']
                ]);

                $mouvementModel->create([
                    'produit_id' => $data['produit_id'],
                    'emplacement_id' => $data['emplacement_id'],
                    'type_mouvement' => 'entree',
                    'quantite' => $quantiteBouteilles,
                    'reference_type' => 'emprunt_emballage',
                    'reference_id' => $id,
                    'motif' => 'Emprunt ' . $labelStock,
                    'created_by' => $data['created_by']
                ]);
            }

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

        $produitVendu = (new Produit())->find($produitId);
        $gammeVendue = $this->normaliserGamme($produitVendu['nom'] ?? '');

        $emprunts = $this->db->fetchAll(
            "SELECT e.id, e.produit_id, e.quantite_empruntee, e.quantite_utilisee, e.quantite_retournee,
                    p.nom as produit_nom
             FROM {$this->table}
             e JOIN produits p ON e.produit_id = p.id
             WHERE e.source_type = 'client'
               AND e.client_id = :client_id
               AND e.statut = 'en_cours'
               AND e.direction = 'recu'
               AND e.type_stock = 'vide'
             ORDER BY CASE WHEN e.produit_id = :produit_id THEN 0 ELSE 1 END, e.date_emprunt ASC, e.id ASC",
            ['client_id' => $clientId, 'produit_id' => $produitId]
        );

        foreach ($emprunts as $emprunt) {
            if ((int) $emprunt['produit_id'] !== (int) $produitId && $this->normaliserGamme($emprunt['produit_nom'] ?? '') !== $gammeVendue) {
                continue;
            }

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

            $stockModel = new Stock();
            $direction = $emprunt['direction'] ?? 'recu';
            $isPlein = ($emprunt['type_stock'] ?? 'vide') === 'plein';
            $quantiteKey = $isPlein ? 'quantite_pleine' : 'quantite_vide';
            $caissesKey = $isPlein ? 'caisses_pleine' : 'caisses_vide';
            $labelStock = $isPlein ? 'produits pleins' : 'emballages vides';

            if ($direction === 'donne') {
                $stockModel->updateOrCreate($emprunt['produit_id'], $emplacementId, [
                    $quantiteKey => $quantiteCaisses * $btlParCaisse,
                    $caissesKey => $quantiteCaisses
                ]);
            } else {
                $stock = $stockModel->getStock($emprunt['produit_id'], $emplacementId);
                if (!$stock || (int) ($stock[$caissesKey] ?? 0) < $quantiteCaisses) {
                    throw new Exception('Stock ' . $labelStock . ' insuffisant pour rembourser cet emprunt');
                }

                $stockModel->updateOrCreate($emprunt['produit_id'], $emplacementId, [
                    $quantiteKey => -($quantiteCaisses * $btlParCaisse),
                    $caissesKey => -$quantiteCaisses
                ]);
            }

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
                'quantite' => ($direction === 'donne' ? 1 : -1) * ($quantiteCaisses * $btlParCaisse),
                'reference_type' => 'emprunt_emballage_rembourse',
                'reference_id' => $id,
                'motif' => ($direction === 'donne' ? 'Retour ' : 'Remboursement ') . $labelStock,
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
