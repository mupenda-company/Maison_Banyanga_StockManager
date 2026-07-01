<?php

class EmpruntEmballageController extends Controller
{
    private $empruntModel;
    private $clientModel;
    private $produitModel;
    private $emplacementModel;

    public function __construct()
    {
        parent::__construct();
        $this->empruntModel = new EmpruntEmballage();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
    }

    public function index()
    {
        $this->requirePermission('emballages.voir');

        $filters = [
            'statut' => $_GET['statut'] ?? null,
            'source_type' => $_GET['source_type'] ?? null,
            'direction' => $_GET['direction'] ?? null,
            'type_stock' => $_GET['type_stock'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null,
        ];

        $this->view('emballages/emprunts', [
            'emprunts' => $this->empruntModel->getAllWithDetails($filters),
            'clients' => $this->clientModel->all('nom'),
            'produits' => $this->produitModel->getActive(),
            'emplacements' => $this->emplacementModel->getFixes(),
            'filters' => $filters
        ]);
    }

    public function store()
    {
        $this->requirePermission('emballages.gerer');
        $data = $this->getJsonInput();

        $lignes = $data['lignes'] ?? null;
        if (is_array($lignes)) {
            $operationRef = 'EMP-' . date('YmdHis') . '-' . random_int(100, 999);
            $createdIds = [];

            foreach ($lignes as $ligne) {
                if (empty($ligne['produit_id']) || (int) ($ligne['quantite_empruntee'] ?? 0) <= 0) {
                    continue;
                }

                $payload = array_merge($data, [
                    'operation_ref' => $operationRef,
                    'produit_id' => (int) $ligne['produit_id'],
                    'quantite_empruntee' => (int) $ligne['quantite_empruntee'],
                ]);
                unset($payload['lignes']);

                $result = $this->empruntModel->createWithStock($payload + ['created_by' => $_SESSION['user_id']]);
                if (empty($result['success'])) {
                    return $this->error($result['message'] ?? 'Operation impossible', 400);
                }
                $createdIds[] = (int) $result['id'];
            }

            if (empty($createdIds)) {
                return $this->error('Ajoutez au moins un produit avec une quantite', 422);
            }

            return $this->success(['ids' => $createdIds, 'operation_ref' => $operationRef], 'Operation multi-produits enregistree avec succes');
        }

        $errors = $this->validate($data, [
            'source_type' => 'required',
            'produit_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'quantite_empruntee' => 'required|numeric',
            'date_emprunt' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $direction = $data['direction'] ?? 'recu';
        if (!in_array($direction, ['recu', 'donne'], true)) {
            return $this->error('Type d\'emprunt invalide', 422);
        }

        $typeStock = $data['type_stock'] ?? 'vide';
        if (!in_array($typeStock, ['vide', 'plein'], true)) {
            return $this->error('Type de stock invalide', 422);
        }

        if (!in_array($data['source_type'], ['client', 'externe'], true)) {
            return $this->error('Type de source invalide', 422);
        }

        if ($data['source_type'] === 'client' && empty($data['client_id'])) {
            return $this->error('Selectionnez le client preteur', 422);
        }

        if ($data['source_type'] === 'externe' && empty($data['source_nom'])) {
            return $this->error('Indiquez le nom de la personne externe', 422);
        }

        $result = $this->empruntModel->createWithStock([
            'direction' => $direction,
            'type_stock' => $typeStock,
            'source_type' => $data['source_type'],
            'client_id' => $data['source_type'] === 'client' ? $data['client_id'] : null,
            'source_nom' => $data['source_type'] === 'externe' ? trim($data['source_nom']) : null,
            'source_contact' => trim($data['source_contact'] ?? ''),
            'produit_id' => $data['produit_id'],
            'quantite_empruntee' => $data['quantite_empruntee'],
            'emplacement_id' => $data['emplacement_id'],
            'date_emprunt' => $data['date_emprunt'],
            'notes' => trim($data['notes'] ?? ''),
            'created_by' => $_SESSION['user_id']
        ]);

        if ($result['success']) {
            return $this->success(['id' => $result['id']], $direction === 'donne' ? 'Pret enregistre avec succes' : 'Emprunt enregistre avec succes');
        }

        return $this->error($result['message'], 400);
    }

    public function show($id)
    {
        $this->requirePermission('emballages.voir');

        $emprunt = $this->empruntModel->find($id);
        if (!$emprunt) {
            return $this->error('Operation non trouvee', 404);
        }

        $operationRef = $emprunt['operation_ref'] ?: ('EMP-' . $emprunt['id']);
        $rows = $this->db->fetchAll(
            "SELECT e.*, c.nom as client_nom, p.nom as produit_nom, p.code as produit_code,
                    emp.nom as emplacement_nom,
                    (e.quantite_empruntee - e.quantite_utilisee - e.quantite_retournee) as reste_caisses
             FROM emprunts_emballages e
             LEFT JOIN clients c ON e.client_id = c.id
             JOIN produits p ON p.id = e.produit_id
             JOIN emplacements emp ON emp.id = e.emplacement_id
             WHERE COALESCE(e.operation_ref, CONCAT('EMP-', e.id)) = :ref
             ORDER BY p.nom",
            ['ref' => $operationRef]
        );

        return $this->success(['operation_ref' => $operationRef, 'lignes' => $rows]);
    }

    public function update($id)
    {
        $this->requirePermission('emballages.gerer');
        $data = $this->getJsonInput();
        $emprunt = $this->empruntModel->find($id);
        if (!$emprunt) {
            return $this->error('Operation non trouvee', 404);
        }
        if ((int) ($emprunt['quantite_utilisee'] ?? 0) > 0 || (int) ($emprunt['quantite_retournee'] ?? 0) > 0) {
            return $this->error('Cette operation a deja ete utilisee ou remboursee. Modification bloquee.', 422);
        }

        $updateData = array_intersect_key($data, array_flip(['date_emprunt', 'notes', 'source_contact']));
        $quantite = isset($data['quantite_empruntee']) ? max(1, (int) $data['quantite_empruntee']) : (int) $emprunt['quantite_empruntee'];
        $diff = $quantite - (int) $emprunt['quantite_empruntee'];

        try {
            $this->db->beginTransaction();

            if ($diff !== 0) {
                $produit = $this->produitModel->find($emprunt['produit_id']);
                $btl = max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24));
                $isPlein = ($emprunt['type_stock'] ?? 'vide') === 'plein';
                $quantiteKey = $isPlein ? 'quantite_pleine' : 'quantite_vide';
                $caissesKey = $isPlein ? 'caisses_pleine' : 'caisses_vide';
                $factor = ($emprunt['direction'] ?? 'recu') === 'donne' ? -1 : 1;
                (new Stock())->updateOrCreate($emprunt['produit_id'], $emprunt['emplacement_id'], [
                    $quantiteKey => $factor * $diff * $btl,
                    $caissesKey => $factor * $diff,
                ]);
                $updateData['quantite_empruntee'] = $quantite;
            }

            if (!empty($updateData)) {
                $this->empruntModel->update($id, $updateData);
            }

            $this->db->commit();
            return $this->success(null, 'Operation modifiee avec succes');
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->error($e->getMessage(), 400);
        }
    }

    public function delete($id)
    {
        $this->requirePermission('emballages.gerer');
        $emprunt = $this->empruntModel->find($id);
        if (!$emprunt) {
            return $this->error('Operation non trouvee', 404);
        }
        if ((int) ($emprunt['quantite_utilisee'] ?? 0) > 0 || (int) ($emprunt['quantite_retournee'] ?? 0) > 0) {
            return $this->error('Cette operation a deja ete utilisee ou remboursee. Suppression bloquee.', 422);
        }

        try {
            $this->db->beginTransaction();
            $produit = $this->produitModel->find($emprunt['produit_id']);
            $btl = max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24));
            $isPlein = ($emprunt['type_stock'] ?? 'vide') === 'plein';
            $quantiteKey = $isPlein ? 'quantite_pleine' : 'quantite_vide';
            $caissesKey = $isPlein ? 'caisses_pleine' : 'caisses_vide';
            $factor = ($emprunt['direction'] ?? 'recu') === 'donne' ? 1 : -1;
            (new Stock())->updateOrCreate($emprunt['produit_id'], $emprunt['emplacement_id'], [
                $quantiteKey => $factor * (int) $emprunt['quantite_empruntee'] * $btl,
                $caissesKey => $factor * (int) $emprunt['quantite_empruntee'],
            ]);
            $this->empruntModel->delete($id);
            $this->db->commit();
            return $this->success(null, 'Operation supprimee avec succes');
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->error($e->getMessage(), 400);
        }
    }

    public function rembourser($id)
    {
        $this->requirePermission('emballages.gerer');
        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'quantite_caisses' => 'required|numeric',
            'emplacement_id' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $result = $this->empruntModel->rembourser(
            $id,
            $data['quantite_caisses'],
            $data['emplacement_id'],
            $_SESSION['user_id']
        );

        if ($result['success']) {
            return $this->success(['solde' => $result['solde']], $result['solde'] ? 'Emprunt rembourse et solde' : 'Remboursement enregistre');
        }

        return $this->error($result['message'], 400);
    }
}
