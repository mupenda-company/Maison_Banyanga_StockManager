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

        $emprunts = $this->empruntModel->getAllWithDetails($filters);

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($emprunts);
            return;
        }

        if (isset($_GET['print']) && (string) $_GET['print'] === '1') {
            foreach ($emprunts as &$emprunt) {
                $emprunt['lignes'] = $this->empruntModel->getOperationDetails(
                    $emprunt['operation_ref_label'] ?? ('EMP-' . $emprunt['id'])
                );
            }
            unset($emprunt);

            $this->view('emballages/print-emprunts', [
                'emprunts' => $emprunts,
                'filters' => $filters,
            ]);
            return;
        }

        $this->view('emballages/emprunts', [
            'emprunts' => $emprunts,
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

        $sourceType = $data['source_type'] ?? '';
        $data['client_id'] = $sourceType === 'client' && !empty($data['client_id'])
            ? (int) $data['client_id']
            : null;
        $data['source_nom'] = $sourceType === 'externe'
            ? trim((string) ($data['source_nom'] ?? ''))
            : null;

        $lignes = $data['lignes'] ?? null;
        if (is_array($lignes)) {
            if (!in_array($sourceType, ['client', 'externe'], true)) {
                return $this->error('Type de source invalide', 422);
            }
            if ($sourceType === 'client' && $data['client_id'] === null) {
                return $this->error('Selectionnez le client partenaire', 422);
            }
            if ($sourceType === 'externe' && $data['source_nom'] === '') {
                return $this->error('Indiquez le nom de la personne externe', 422);
            }
            if (empty($data['emplacement_id']) || empty($data['date_emprunt'])) {
                return $this->error('Selectionnez l\'emplacement et la date', 422);
            }

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
            return $this->error('Selectionnez le client partenaire', 422);
        }

        if ($data['source_type'] === 'externe' && empty($data['source_nom'])) {
            return $this->error('Indiquez le nom de la personne externe', 422);
        }

        $result = $this->empruntModel->createWithStock([
            'direction' => $direction,
            'type_stock' => $typeStock,
            'source_type' => $data['source_type'],
            'client_id' => $data['client_id'],
            'source_nom' => $data['source_nom'],
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
                    p.bouteilles_par_caisses,
                    emp.nom as emplacement_nom,
                    (e.quantite_empruntee - e.quantite_utilisee - e.quantite_retournee) as reste_caisses
             FROM emprunts_emballages e
             LEFT JOIN clients c ON e.client_id = c.id
             JOIN produits p ON p.id = e.produit_id
             JOIN emplacements emp ON emp.id = e.emplacement_id
             WHERE COALESCE(e.operation_ref, CONCAT('EMP-', e.id)) = :ref
             ORDER BY p.position_affichage ASC, p.nom ASC",
            ['ref' => $operationRef]
        );

        foreach ($rows as &$row) {
            $row['remboursements'] = $this->db->fetchAll(
                "SELECT id, created_at, ABS(quantite) / :btl_par_caisse AS quantite_caisses
                 FROM mouvements_stock
                 WHERE reference_type = 'emprunt_emballage_rembourse'
                   AND reference_id = :emprunt_id
                 ORDER BY created_at DESC, id DESC",
                [
                    'btl_par_caisse' => max(1, (int) ($row['bouteilles_par_caisses'] ?? 24)),
                    'emprunt_id' => (int) $row['id'],
                ]
            );
        }
        unset($row);

        return $this->success(['operation_ref' => $operationRef, 'lignes' => $rows]);
    }

    public function printBon($id)
    {
        $this->requirePermission('emballages.voir');
        $emprunt = $this->empruntModel->find($id);
        if (!$emprunt) {
            http_response_code(404);
            exit('Operation non trouvee');
        }

        $operationRef = $emprunt['operation_ref'] ?: ('EMP-' . $emprunt['id']);
        $lignes = $this->empruntModel->getOperationDetails($operationRef);
        $operation = $lignes[0] ?? $emprunt;
        $operation['operation_ref_label'] = $operationRef;
        $auteur = !empty($operation['created_by'])
            ? $this->db->fetch("SELECT nom, prenom FROM users WHERE id = :id", ['id' => $operation['created_by']])
            : null;

        $this->view('emballages/bon-emprunt', [
            'mode' => 'operation',
            'operation' => $operation,
            'lignes' => $lignes,
            'auteur' => $auteur,
        ]);
    }

    public function printRemboursement($mouvementId)
    {
        $this->requirePermission('emballages.voir');
        $mouvement = $this->db->fetch(
            "SELECT m.*, e.operation_ref, e.direction, e.type_stock, e.source_type,
                    e.source_nom, e.source_contact, e.client_id, e.date_emprunt,
                    c.nom AS client_nom, p.nom AS produit_nom, p.code AS produit_code,
                    p.bouteilles_par_caisses, emp.nom AS emplacement_nom,
                    u.nom AS user_nom, u.prenom AS user_prenom
             FROM mouvements_stock m
             JOIN emprunts_emballages e ON e.id = m.reference_id
             LEFT JOIN clients c ON c.id = e.client_id
             JOIN produits p ON p.id = m.produit_id
             JOIN emplacements emp ON emp.id = m.emplacement_id
             LEFT JOIN users u ON u.id = m.created_by
             WHERE m.id = :id AND m.reference_type = 'emprunt_emballage_rembourse'",
            ['id' => $mouvementId]
        );
        if (!$mouvement) {
            http_response_code(404);
            exit('Remboursement non trouve');
        }

        $mouvement['operation_ref_label'] = $mouvement['operation_ref'] ?: ('EMP-' . $mouvement['reference_id']);
        $mouvement['quantite_caisses'] = abs((float) $mouvement['quantite'])
            / max(1, (int) ($mouvement['bouteilles_par_caisses'] ?? 24));

        $this->view('emballages/bon-emprunt', [
            'mode' => 'remboursement',
            'operation' => $mouvement,
            'lignes' => [$mouvement],
            'auteur' => [
                'nom' => $mouvement['user_nom'] ?? '',
                'prenom' => $mouvement['user_prenom'] ?? '',
            ],
        ]);
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
        $operationRef = $emprunt['operation_ref'] ?: ('EMP-' . $emprunt['id']);
        $operationRows = $this->getOperationRows($operationRef);
        foreach ($operationRows as $row) {
            if ((int) ($row['quantite_utilisee'] ?? 0) > 0 || (int) ($row['quantite_retournee'] ?? 0) > 0) {
                return $this->error('Cette operation a deja ete utilisee ou remboursee. Modification bloquee.', 422);
            }
        }

        $direction = $data['direction'] ?? ($emprunt['direction'] ?? 'recu');
        $typeStock = $data['type_stock'] ?? ($emprunt['type_stock'] ?? 'vide');
        $sourceType = $data['source_type'] ?? ($emprunt['source_type'] ?? 'client');
        if (!in_array($direction, ['recu', 'donne'], true)) {
            return $this->error('Type d\'emprunt invalide', 422);
        }
        if (!in_array($typeStock, ['vide', 'plein'], true)) {
            return $this->error('Type de stock invalide', 422);
        }
        if (!in_array($sourceType, ['client', 'externe'], true)) {
            return $this->error('Type de source invalide', 422);
        }
        $clientId = !empty($data['client_id'])
            ? (int) $data['client_id']
            : (!empty($emprunt['client_id']) ? (int) $emprunt['client_id'] : null);
        if ($sourceType === 'client' && $clientId === null) {
            return $this->error('Selectionnez le client partenaire', 422);
        }
        if ($sourceType === 'externe' && empty($data['source_nom']) && empty($emprunt['source_nom'])) {
            return $this->error('Indiquez le nom de la personne externe', 422);
        }
        $lignes = is_array($data['lignes'] ?? null) ? $data['lignes'] : null;
        if ($lignes === null && empty($data['produit_id']) && empty($emprunt['produit_id'])) {
            return $this->error('Selectionnez le produit', 422);
        }
        if (empty($data['emplacement_id']) && empty($emprunt['emplacement_id'])) {
            return $this->error('Selectionnez l\'emplacement', 422);
        }

        $updateData = [
            'direction' => $direction,
            'type_stock' => $typeStock,
            'source_type' => $sourceType,
            'client_id' => $sourceType === 'client' ? $clientId : null,
            'source_nom' => $sourceType === 'externe' ? trim($data['source_nom'] ?? ($emprunt['source_nom'] ?? '')) : null,
            'source_contact' => trim($data['source_contact'] ?? ($emprunt['source_contact'] ?? '')),
            'produit_id' => isset($data['produit_id']) ? (int) $data['produit_id'] : (int) $emprunt['produit_id'],
            'emplacement_id' => isset($data['emplacement_id']) ? (int) $data['emplacement_id'] : (int) $emprunt['emplacement_id'],
            'date_emprunt' => $data['date_emprunt'] ?? $emprunt['date_emprunt'],
            'notes' => trim($data['notes'] ?? ($emprunt['notes'] ?? '')),
        ];
        $quantite = isset($data['quantite_empruntee']) ? max(1, (int) $data['quantite_empruntee']) : (int) $emprunt['quantite_empruntee'];
        $updateData['quantite_empruntee'] = $quantite;

        try {
            $this->db->beginTransaction();

            if ($lignes !== null) {
                $validLignes = array_values(array_filter($lignes, function ($ligne) {
                    return !empty($ligne['produit_id']) && (int) ($ligne['quantite_empruntee'] ?? 0) > 0;
                }));

                if (empty($validLignes)) {
                    throw new Exception('Ajoutez au moins un produit avec une quantite');
                }

                foreach ($operationRows as $row) {
                    $oldFactor = ($row['direction'] ?? 'recu') === 'donne' ? -1 : 1;
                    $this->adjustStockForEmprunt($row, -1 * $oldFactor * (int) $row['quantite_empruntee']);
                    $this->empruntModel->delete($row['id']);
                }

                foreach ($validLignes as $ligne) {
                    $newData = $updateData;
                    $newData['operation_ref'] = $operationRef;
                    $newData['produit_id'] = (int) $ligne['produit_id'];
                    $newData['quantite_empruntee'] = max(1, (int) $ligne['quantite_empruntee']);
                    $newData['quantite_utilisee'] = 0;
                    $newData['quantite_retournee'] = 0;
                    $newData['statut'] = 'en_cours';
                    $newData['created_by'] = $_SESSION['user_id'] ?? ($emprunt['created_by'] ?? null);

                    $newId = $this->empruntModel->create($newData);
                    $newData['id'] = $newId;
                    $newFactor = ($newData['direction'] ?? 'recu') === 'donne' ? -1 : 1;
                    $this->adjustStockForEmprunt($newData, $newFactor * (int) $newData['quantite_empruntee']);
                }
            } else {
                $oldFactor = ($emprunt['direction'] ?? 'recu') === 'donne' ? -1 : 1;
                $this->adjustStockForEmprunt($emprunt, -1 * $oldFactor * (int) $emprunt['quantite_empruntee']);

                $newEmprunt = array_merge($emprunt, $updateData);
                $newFactor = ($newEmprunt['direction'] ?? 'recu') === 'donne' ? -1 : 1;
                $this->adjustStockForEmprunt($newEmprunt, $newFactor * $quantite);

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
        $operationRef = $emprunt['operation_ref'] ?: ('EMP-' . $emprunt['id']);
        $operationRows = $this->getOperationRows($operationRef);
        foreach ($operationRows as $row) {
            if ((int) ($row['quantite_utilisee'] ?? 0) > 0 || (int) ($row['quantite_retournee'] ?? 0) > 0) {
                return $this->error('Cette operation a deja ete utilisee ou remboursee. Suppression bloquee.', 422);
            }
        }

        try {
            $this->db->beginTransaction();
            foreach ($operationRows as $row) {
                $factor = ($row['direction'] ?? 'recu') === 'donne' ? 1 : -1;
                $this->adjustStockForEmprunt($row, $factor * (int) $row['quantite_empruntee']);
                $this->empruntModel->delete($row['id']);
            }
            $this->db->commit();
            return $this->success(null, 'Operation supprimee avec succes');
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->error($e->getMessage(), 400);
        }
    }

    private function getOperationRows(string $operationRef): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM emprunts_emballages
             WHERE COALESCE(operation_ref, CONCAT('EMP-', id)) = :ref
             ORDER BY id ASC",
            ['ref' => $operationRef]
        );
    }

    private function adjustStockForEmprunt(array $emprunt, int $deltaCaisses): void
    {
        if ($deltaCaisses === 0) {
            return;
        }

        $stockModel = new Stock();
        $stock = $stockModel->getStock($emprunt['produit_id'], $emprunt['emplacement_id']) ?: [];
        $isPlein = ($emprunt['type_stock'] ?? 'vide') === 'plein';
        $caissesKey = $isPlein ? 'caisses_pleine' : 'caisses_vide';
        $currentCaisses = (int) ($stock[$caissesKey] ?? 0);
        $newCaisses = $currentCaisses + $deltaCaisses;

        if ($newCaisses < 0) {
            $emplacement = $this->emplacementModel->find($emprunt['emplacement_id']);
            throw new Exception(
                'Stock insuffisant dans ' . ($emplacement['nom'] ?? 'cet emplacement')
                . ' : disponible ' . $currentCaisses . ' cs, demande ' . abs($deltaCaisses) . ' cs.'
            );
        }

        $stockModel->setInitialStock($emprunt['produit_id'], $emprunt['emplacement_id'], [
            'caisses_pleine' => $isPlein ? $newCaisses : (int) ($stock['caisses_pleine'] ?? 0),
            'caisses_vide' => $isPlein ? (int) ($stock['caisses_vide'] ?? 0) : $newCaisses,
        ]);

        $produit = $this->produitModel->find($emprunt['produit_id']);
        $btlParCaisse = max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24));
        $deltaBouteilles = $deltaCaisses * $btlParCaisse;
        $quantiteKey = $isPlein ? 'quantite_pleine' : 'quantite_vide';
        $quantiteAvant = (int) ($stock[$quantiteKey] ?? 0);
        (new MouvementStock())->create([
            'produit_id' => $emprunt['produit_id'],
            'emplacement_id' => $emprunt['emplacement_id'],
            'type_mouvement' => $deltaBouteilles > 0 ? 'entree' : 'sortie',
            'quantite' => $deltaBouteilles,
            'quantite_avant' => $quantiteAvant,
            'quantite_apres' => $quantiteAvant + $deltaBouteilles,
            'reference_type' => 'emprunt_emballage_ajustement',
            'reference_id' => (int) ($emprunt['id'] ?? 0),
            'motif' => 'Ajustement opération emprunt/prêt - ' . ($isPlein ? 'produits pleins' : 'emballages vides'),
            'created_by' => $_SESSION['user_id'] ?? null,
        ]);
    }

    private function exportExcel(array $emprunts): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Emprunts et prets');
        $sheet->fromArray([
            'Date', 'Reference', 'Sens', 'Type', 'Partenaire', 'Produit', 'Code produit',
            'Quantite empruntee / pretee (cs)', 'Utilise (cs)', 'Retourne (cs)',
            'Reste (cs)', 'Emplacement', 'Statut'
        ], null, 'A1');

        $rowNumber = 2;
        $totalRows = [];
        foreach ($emprunts as $emprunt) {
            $partenaire = ($emprunt['source_type'] ?? 'client') === 'client'
                ? ($emprunt['client_nom'] ?? 'Client')
                : ($emprunt['source_nom'] ?? 'Externe');
            $sens = ($emprunt['direction'] ?? 'recu') === 'donne' ? 'Prêter' : 'Emprunter';
            $type = ($emprunt['type_stock'] ?? 'vide') === 'plein' ? 'Produits pleins' : 'Emballages vides';
            $reference = $emprunt['operation_ref_label'] ?? ('EMP-' . ($emprunt['id'] ?? ''));
            $lignes = $this->empruntModel->getOperationDetails($reference);

            foreach ($lignes as $ligne) {
                $sheet->fromArray([
                    $ligne['date_emprunt'] ?? ($emprunt['date_emprunt'] ?? ''),
                    $reference,
                    $sens,
                    $type,
                    $partenaire,
                    $ligne['produit_nom'] ?? '',
                    $ligne['produit_code'] ?? '',
                    (int) ($ligne['quantite_empruntee'] ?? 0),
                    (int) ($ligne['quantite_utilisee'] ?? 0),
                    (int) ($ligne['quantite_retournee'] ?? 0),
                    (int) ($ligne['reste_caisses'] ?? 0),
                    $ligne['emplacement_nom'] ?? ($emprunt['emplacement_nom'] ?? ''),
                    ($ligne['statut'] ?? 'en_cours') === 'solde' ? 'Soldé' : 'En cours',
                ], null, 'A' . $rowNumber++);
            }

            if (count($lignes) > 1) {
                $sheet->fromArray([
                    $emprunt['date_emprunt'] ?? '',
                    $reference,
                    $sens,
                    $type,
                    $partenaire,
                    'TOTAL DE L\'OPERATION',
                    '',
                    (int) ($emprunt['quantite_empruntee'] ?? 0),
                    (int) ($emprunt['quantite_utilisee'] ?? 0),
                    (int) ($emprunt['quantite_retournee'] ?? 0),
                    (int) ($emprunt['reste_caisses'] ?? 0),
                    $emprunt['emplacement_nom'] ?? '',
                    ($emprunt['statut'] ?? 'en_cours') === 'solde' ? 'Soldé' : 'En cours',
                ], null, 'A' . $rowNumber);
                $totalRows[] = $rowNumber++;
            }
        }

        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        foreach ($totalRows as $totalRow) {
            $sheet->getStyle('A' . $totalRow . ':M' . $totalRow)->getFont()->setBold(true);
        }
        $sheet->setAutoFilter('A1:M' . max(1, $rowNumber - 1));
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="emprunts_prets_' . date('Y-m-d_H-i') . '.xlsx"');
        header('Cache-Control: max-age=0, must-revalidate');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
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
            return $this->success([
                'solde' => $result['solde'],
                'mouvement_id' => $result['mouvement_id'] ?? null,
            ], $result['solde'] ? 'Emprunt rembourse et solde' : 'Remboursement enregistre');
        }

        return $this->error($result['message'], 400);
    }
}
