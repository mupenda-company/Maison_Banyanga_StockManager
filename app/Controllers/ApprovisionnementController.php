<?php
/**
 * Contrôleur des approvisionnements
 */

class ApprovisionnementController extends Controller
{
    private $approvisionnementModel;
    private $produitModel;
    private $emplacementModel;
    private $detteModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->approvisionnementModel = new Approvisionnement();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
        $this->detteModel = new DetteEmballage();
    }
    
    /**
     * Liste des approvisionnements
     */
    public function index()
    {
        $this->requirePermission('approvisionnements.voir');
        
        $filters = [
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null,
            'statut' => $_GET['statut'] ?? null
        ];
        
        // Exporter en Excel
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportProduitsExcel($filters);
            return;
        }

        if (isset($_GET['print']) && $_GET['print'] === '1') {
            $this->printProduits($filters);
            return;
        }

        $page = (int) ($_GET['page'] ?? 1);
        $approvisionnements = $this->approvisionnementModel->getAllPaginated($page, 5, $filters);
        
        $this->view('approvisionnements/index', [
            'approvisionnements' => $approvisionnements,
            'filters' => $filters
        ]);
    }

    private function styleHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $nbCols): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbCols);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
        ]);
        foreach (range(1, $nbCols) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    // Helper pour envoyer le fichier xlsx au navigateur
    private function sendXlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (headers_sent()) {
            throw new Exception('Impossible de generer le fichier Excel: des donnees ont deja ete envoyees au navigateur.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
    }
    
   
    private function exportProduitsExcel($filters)
    {
        $this->requireAuth();

        $report = $this->buildProduitsApprovisionnementReport($filters);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Appro par produit');

        // Bloc info en haut
        $sheet->fromArray(['Etat des approvisionnements par produit'], null, 'A1');
        $sheet->fromArray(['Du', $filters['date_debut'] ?: 'Debut'], null, 'A2');
        $sheet->fromArray(['Au', $filters['date_fin'] ?: 'Fin'], null, 'A3');
        $sheet->fromArray(['Statut', $filters['statut'] ?: 'Tous'], null, 'A4');
        $sheet->fromArray(['Taux systeme', '1 USD = ' . number_format(get_taux_change(), 2, '.', '') . ' CDF'], null, 'A5');
        $sheet->fromArray([
            'Total prix achat',
            (float) $report['totals']['pt'],
            'CDF',
            (float) convert_money($report['totals']['pt'], get_base_devise(), 'USD'),
            'USD',
        ], null, 'A6');

        // En-têtes données
        $headers = ['PRODUITS', 'N P', 'ACHAT', 'PLT', 'P.A.AD', 'P.A.A.E', 'P.T', 'P.V.U', 'P.V.T', 'ECART', 'TOTAL EC', 'ECART A EN', 'TOTAL A ENL'];
        $sheet->fromArray($headers, null, 'A8');

        $row = 9;
        foreach ($report['items'] as $r) {
            $sheet->fromArray([
                $r['produit'],
                $r['np'],
                (float) $r['achat'],
                (float) $r['plt'],
                (float) $r['paad'],
                (float) $r['paae'],
                (float) $r['pt'],
                (float) $r['pvu'],
                (float) $r['pvt'],
                (float) $r['ecart'],
                (float) $r['total_ec'],
                (float) $r['ecart_a_en'],
                (float) $r['total_a_enl'],
            ], null, 'A' . $row++);
        }

        // Ligne totaux CDF
        $sheet->fromArray([
            'TOTAUX CDF', '',
            (float) $report['totals']['achat'],
            (float) $report['totals']['plt'],
            '', '',
            (float) convert_money($report['totals']['pt'],  get_base_devise(), 'CDF'),
            '',
            (float) convert_money($report['totals']['pvt'], get_base_devise(), 'CDF'),
            '',
            (float) convert_money($report['totals']['total_ec'],    get_base_devise(), 'CDF'),
            '',
            (float) convert_money($report['totals']['total_a_enl'], get_base_devise(), 'CDF'),
        ], null, 'A' . $row++);

        // Ligne totaux USD
        $sheet->fromArray([
            'TOTAUX USD', '', '', '', '', '',
            (float) convert_money($report['totals']['pt'],  get_base_devise(), 'USD'),
            '',
            (float) convert_money($report['totals']['pvt'], get_base_devise(), 'USD'),
            '',
            (float) convert_money($report['totals']['total_ec'],    get_base_devise(), 'USD'),
            '',
            (float) convert_money($report['totals']['total_a_enl'], get_base_devise(), 'USD'),
        ], null, 'A' . $row);

        // Style header ligne 8
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A8:' . $lastCol . '8')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'approvisionnements_produits_' . date('Y-m-d_H-i') . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }


    private function printProduits($filters)
    {
        $this->requireAuth();

        $this->view('approvisionnements/print-produits', [
            'filters' => $filters,
            'report' => $this->buildProduitsApprovisionnementReport($filters)
        ]);
    }

    private function buildProduitsApprovisionnementReport($filters)
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['date_debut'])) {
            $where[] = 'a.date_approvisionnement >= :date_debut';
            $params['date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where[] = 'a.date_approvisionnement <= :date_fin';
            $params['date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['statut'])) {
            $where[] = 'a.statut = :statut';
            $params['statut'] = $filters['statut'];
        }

        $whereSql = implode(' AND ', $where);
        $rows = $this->db->fetchAll(
            "SELECT p.id, p.nom, p.code, p.caisses_par_palette, p.bouteilles_par_caisses,
                    p.prix_achat_deposer, p.prix_achat_enlever, p.prix_vente_unitaire, p.prix_vente_caisses,
                    COALESCE(ap.achat, 0) as achat,
                    COALESCE(ap.pt, 0) as pt
             FROM produits p
             LEFT JOIN (
                SELECT ad.produit_id,
                       SUM(ad.quantite_caisses) as achat,
                       SUM(ad.sous_total) as pt
                FROM approvisionnement_details ad
                JOIN approvisionnements a ON a.id = ad.approvisionnement_id
                WHERE {$whereSql}
                GROUP BY ad.produit_id
             ) ap ON ap.produit_id = p.id
             WHERE p.actif = 1
             ORDER BY p.position_affichage ASC, p.nom ASC",
            $params
        );

        $items = [];
        $totals = [
            'achat' => 0,
            'plt' => 0,
            'pt' => 0,
            'pvt' => 0,
            'total_ec' => 0,
            'total_a_enl' => 0,
        ];

        foreach ($rows as $row) {
            $achat = (float) ($row['achat'] ?? 0);
            $np = (int) ($row['caisses_par_palette'] ?? 0);
            $plt = $np > 0 ? ($achat / $np) : 0;
            $btl = max(1, (int) ($row['bouteilles_par_caisses'] ?? 1));
            $paad = (float) ($row['prix_achat_deposer'] ?? 0);
            $paae = (float) ($row['prix_achat_enlever'] ?? 0);
            $pt = (float) ($row['pt'] ?? 0);
            $pvu = (float) ($row['prix_vente_caisses'] ?? 0);
            if ($pvu <= 0) {
                $pvu = (float) ($row['prix_vente_unitaire'] ?? 0) * $btl;
            }
            $pvt = $achat * $pvu;
            $ecart = $pvu - $paad;
            $totalEc = $achat * $ecart;
            $ecartAEn = $paad - $paae;
            $totalAEnl = $achat * $ecartAEn;

            $items[] = [
                'produit' => $row['nom'],
                'np' => $np,
                'achat' => $achat,
                'plt' => $plt,
                'paad' => $paad,
                'paae' => $paae,
                'pt' => $pt,
                'pvu' => $pvu,
                'pvt' => $pvt,
                'ecart' => $ecart,
                'total_ec' => $totalEc,
                'ecart_a_en' => $ecartAEn,
                'total_a_enl' => $totalAEnl,
            ];

            $totals['achat'] += $achat;
            $totals['plt'] += $plt;
            $totals['pt'] += $pt;
            $totals['pvt'] += $pvt;
            $totals['total_ec'] += $totalEc;
            $totals['total_a_enl'] += $totalAEnl;
        }

        return ['items' => $items, 'totals' => $totals];
    }

    public function create()
    {
        $this->requirePermission('approvisionnements.voir');
        
        $produits = $this->produitModel->getActive();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        
        $this->view('approvisionnements/create', [
            'produits' => $produits,
            'emplacementPrincipal' => $emplacementPrincipal,
            'numero_bon' => $this->approvisionnementModel->generateNumeroBon()
        ]);
    }
    
    /**
     * Enregistrer un approvisionnement
     */
    public function store()
    {
        $this->requirePermission('approvisionnements.voir');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'date_approvisionnement' => 'required',
            'details' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        
        $approvisionnementData = [
            'numero_bon' => $this->approvisionnementModel->generateNumeroBon(),
            'date_approvisionnement' => $data['date_approvisionnement'],
            'fournisseur' => $data['fournisseur'] ?? 'Bralima',
            'notes' => $data['notes'] ?? '',
            'total_ht' => 0,
            'statut' => 'valide',
            'created_by' => $_SESSION['user_id']
        ];
        
        $details = [];
        $totalHt = 0;
        
        foreach ($data['details'] as $detail) {
            $produit = $this->produitModel->find($detail['produit_id']);
            $typeAchat = $detail['type_achat'] ?? 'deposer';
            
            // Determine case price (prix_caisse) based on product fields. These are stored as price per case.
            if ($typeAchat === 'enlever' && $produit['prix_achat_enlever'] > 0) {
                $prixCaisse = $produit['prix_achat_enlever'];
            } elseif ($typeAchat === 'deposer' && $produit['prix_achat_deposer'] > 0) {
                $prixCaisse = $produit['prix_achat_deposer'];
            } else {
                // Fallback: compute case price from unit price
                $prixCaisse = $produit['prix_achat_unitaire'] * $produit['bouteilles_par_caisses'];
            }

            $sousTotal = $detail['quantite_caisses'] * $prixCaisse;
            $totalHt += $sousTotal;

            $details[] = [
                'produit_id' => $detail['produit_id'],
                'quantite_caisses' => $detail['quantite_caisses'],
                'quantite_bouteilles' => $detail['quantite_caisses'] * $produit['bouteilles_par_caisses'],
                'prix_unitaire' => $prixCaisse / max(1, $produit['bouteilles_par_caisses']),
                'prix_caisse' => $prixCaisse,
                'type_achat' => $typeAchat,
                'sous_total' => $sousTotal
            ];
        }
        
        $approvisionnementData['total_ht'] = $totalHt;
        
        $result = $this->approvisionnementModel->createWithDetails(
            $approvisionnementData,
            $details,
            $emplacementPrincipal['id']
        );
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Approvisionnement enregistré avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Afficher un approvisionnement
     */
    public function show($id)
    {
        $this->requirePermission('approvisionnements.voir');
        
        $approvisionnement = $this->approvisionnementModel->getWithDetails($id);
        
        if (!$approvisionnement) {
            return $this->error('Approvisionnement non trouvé', 404);
        }
        
        // Récupérer les dettes associées
        $dettes = $this->db->fetchAll(
            "SELECT d.*, p.nom as produit_nom
             FROM dettes_emballages d
             JOIN produits p ON d.produit_id = p.id
             WHERE d.approvisionnement_id = :id",
            ['id' => $id]
        );
        
        if ($this->isAjax()) {
            return $this->success([
                'approvisionnement' => $approvisionnement,
                'dettes' => $dettes
            ]);
        }
        
        $this->view('approvisionnements/show', [
            'approvisionnement' => $approvisionnement,
            'dettes' => $dettes
        ]);
    }
    public function edit($id)
    {
        $this->requirePermission('approvisionnements.voir');

        $approvisionnement = $this->approvisionnementModel->getWithDetails($id);

        if (!$approvisionnement) {
            return $this->error('Approvisionnement non trouvé', 404);
        }

        if (($approvisionnement['statut'] ?? '') !== 'valide') {
            return $this->error('Seuls les approvisionnements validés peuvent être modifiés', 422);
        }

        $this->view('approvisionnements/edit', [
            'approvisionnement' => $approvisionnement,
            'produits' => $this->produitModel->getActive(),
            'emplacementPrincipal' => $this->emplacementModel->getPrincipal(),
            'numero_bon' => $approvisionnement['numero_bon']
        ]);
    }

    public function update($id)
    {
        $this->requirePermission('approvisionnements.voir');

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'date_approvisionnement' => 'required',
            'details' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $details = [];
        $totalHt = 0;

        foreach ($data['details'] as $detail) {
            $produit = $this->produitModel->find($detail['produit_id']);
            if (!$produit) {
                return $this->error('Produit introuvable', 422);
            }

            $typeAchat = $detail['type_achat'] ?? 'deposer';

            if ($typeAchat === 'enlever' && $produit['prix_achat_enlever'] > 0) {
                $prixCaisse = $produit['prix_achat_enlever'];
            } elseif ($typeAchat === 'deposer' && $produit['prix_achat_deposer'] > 0) {
                $prixCaisse = $produit['prix_achat_deposer'];
            } else {
                $prixCaisse = $produit['prix_achat_unitaire'] * $produit['bouteilles_par_caisses'];
            }

            $quantiteCaisses = max(0, (int)($detail['quantite_caisses'] ?? 0));

            if ($quantiteCaisses <= 0) {
                continue;
            }

            $sousTotal = $quantiteCaisses * $prixCaisse;
            $totalHt += $sousTotal;

            $details[] = [
                'produit_id' => (int)$detail['produit_id'],
                'quantite_caisses' => $quantiteCaisses,
                'quantite_bouteilles' => $quantiteCaisses * (int)$produit['bouteilles_par_caisses'],
                'prix_unitaire' => $prixCaisse / max(1, (int)$produit['bouteilles_par_caisses']),
                'prix_caisse' => $prixCaisse,
                'type_achat' => $typeAchat,
                'sous_total' => $sousTotal
            ];
        }

        if (empty($details)) {
            return $this->error('Ajoutez au moins un produit', 422);
        }

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();

        $result = $this->approvisionnementModel->updateWithDetails(
            $id,
            [
                'date_approvisionnement' => $data['date_approvisionnement'],
                'fournisseur' => $data['fournisseur'] ?? 'Bralima',
                'notes' => $data['notes'] ?? '',
                'total_ht' => $totalHt
            ],
            $details,
            $emplacementPrincipal['id']
        );

        if ($result['success']) {
            return $this->success(['id' => $id], 'Approvisionnement modifié avec succès');
        }

        return $this->error($result['message'], 400);
    }
    public function print($id)
    {
        $this->requirePermission('approvisionnements.voir');

        $approvisionnement = $this->approvisionnementModel->getWithDetails($id);
        if (!$approvisionnement) {
            return $this->error('Approvisionnement non trouve', 404);
        }

        $this->view('approvisionnements/print', [
            'approvisionnement' => $approvisionnement,
            'rows' => $this->buildDetailRows($approvisionnement)
        ]);
    }

    public function exportDetail($id)
    {
        $this->requirePermission('approvisionnements.voir');

        $approvisionnement = $this->approvisionnementModel->getWithDetails($id);
        if (!$approvisionnement) {
            return $this->error('Approvisionnement non trouve', 404);
        }

        $rows = $this->buildDetailRows($approvisionnement);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Detail appro');

        $sheet->fromArray(['Approvisionnement', $approvisionnement['numero_bon'] ?? $id], null, 'A1');
        $sheet->fromArray(['Date', $approvisionnement['date_approvisionnement'] ?? ''], null, 'A2');
        $sheet->fromArray(['Fournisseur', $approvisionnement['fournisseur'] ?? 'Bralima'], null, 'A3');
        $sheet->fromArray(['Taux systeme', '1 USD = ' . number_format(get_taux_change(), 2, '.', '') . ' CDF'], null, 'A4');
        $sheet->fromArray([
            'Total prix achat',
            (float) $rows['totals']['pt'],
            'CDF',
            (float) convert_money($rows['totals']['pt'], get_base_devise(), 'USD'),
            'USD',
        ], null, 'A5');

        $headers = ['PRODUITS', 'N P', 'ACHAT', 'PLT', 'P.A.AD', 'P.A.A.E', 'P.T', 'P.V.U', 'P.V.T', 'ECART', 'TOTAL EC', 'ECART A EN', 'TOTAL A ENL'];
        $sheet->fromArray($headers, null, 'A7');

        $rowIndex = 8;
        foreach ($rows['items'] as $row) {
            $sheet->fromArray([
                $row['produit'],
                (int) $row['np'],
                (float) $row['achat'],
                (float) $row['plt'],
                (float) $row['paad'],
                (float) $row['paae'],
                (float) $row['pt'],
                (float) $row['pvu'],
                (float) $row['pvt'],
                (float) $row['ecart'],
                (float) $row['total_ec'],
                (float) $row['ecart_a_en'],
                (float) $row['total_a_enl'],
            ], null, 'A' . $rowIndex++);
        }

        $rowIndex++;
        $sheet->fromArray(['TOTAUX CDF', '', (float) $rows['totals']['achat'], (float) $rows['totals']['plt'], '', '', (float) convert_money($rows['totals']['pt'], get_base_devise(), 'CDF'), '', (float) convert_money($rows['totals']['pvt'], get_base_devise(), 'CDF'), '', (float) convert_money($rows['totals']['total_ec'], get_base_devise(), 'CDF'), '', (float) convert_money($rows['totals']['total_a_enl'], get_base_devise(), 'CDF')], null, 'A' . $rowIndex++);
        $sheet->fromArray(['TOTAUX USD', '', '', '', '', '', (float) convert_money($rows['totals']['pt'], get_base_devise(), 'USD'), '', (float) convert_money($rows['totals']['pvt'], get_base_devise(), 'USD'), '', (float) convert_money($rows['totals']['total_ec'], get_base_devise(), 'USD'), '', (float) convert_money($rows['totals']['total_a_enl'], get_base_devise(), 'USD')], null, 'A' . $rowIndex);

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A7:' . $lastCol . '7')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'approvisionnement_' . ($approvisionnement['numero_bon'] ?? $id) . '_' . date('Y-m-d_H-i') . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }

    private function buildDetailRows(array $approvisionnement)
    {
        $items = [];
        $totals = [
            'achat' => 0,
            'plt' => 0,
            'pt' => 0,
            'pvt' => 0,
            'total_ec' => 0,
            'total_a_enl' => 0,
        ];

        foreach ($approvisionnement['details'] ?? [] as $detail) {
            $achat = (float) ($detail['quantite_caisses'] ?? 0);
            $np = (int) ($detail['caisses_par_palette'] ?? 0);
            $plt = $np > 0 ? ($achat / $np) : 0;
            $btl = max(1, (int) ($detail['bouteilles_par_caisses'] ?? 1));
            $paad = (float) ($detail['prix_achat_deposer'] ?? 0);
            $paae = (float) ($detail['prix_achat_enlever'] ?? 0);
            $pvu = (float) ($detail['prix_vente_caisses'] ?? 0);
            if ($pvu <= 0) {
                $pvu = (float) ($detail['prix_vente_unitaire'] ?? 0) * $btl;
            }

            $prixAchatChoisi = (($detail['type_achat'] ?? 'deposer') === 'enlever') ? $paae : $paad;
            if ($prixAchatChoisi <= 0) {
                $prixAchatChoisi = (float)($detail['prix_caisse'] ?? (($detail['prix_unitaire'] ?? 0) * $btl));
            }

            $pt = $achat * $prixAchatChoisi;
            $pvt = $achat * $pvu;
            $ecart = $pvu - $paad;
            $totalEc = $ecart * $achat;
            $ecartAEn = $paad - $paae;
            $totalAEnl = $ecartAEn * $achat;

            $items[] = [
                'produit' => $detail['produit_nom'] ?? '',
                'np' => $np,
                'achat' => $achat,
                'plt' => $plt,
                'paad' => $paad,
                'paae' => $paae,
                'pt' => $pt,
                'pvu' => $pvu,
                'pvt' => $pvt,
                'ecart' => $ecart,
                'total_ec' => $totalEc,
                'ecart_a_en' => $ecartAEn,
                'total_a_enl' => $totalAEnl,
            ];

            $totals['achat'] += $achat;
            $totals['plt'] += $plt;
            $totals['pt'] += $pt;
            $totals['pvt'] += $pvt;
            $totals['total_ec'] += $totalEc;
            $totals['total_a_enl'] += $totalAEnl;
        }

        return ['items' => $items, 'totals' => $totals];
    }
    
    /**
     * Annuler un approvisionnement
     */
    public function annuler($id)
    {
        $this->requirePermission('approvisionnements.voir');
        
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $result = $this->approvisionnementModel->annuler($id, $emplacementPrincipal['id']);
        
        if ($result['success']) {
            return $this->success(null, 'Approvisionnement annulé avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Dettes d'emballages
     */
    public function dettes()
    {
        $this->requirePermission('approvisionnements.voir');
        
        $dettes = $this->detteModel->getWithDetails(['statut' => 'en_cours']);
        $total = $this->detteModel->getTotalEnCours();
        
        $this->view('approvisionnements/dettes', [
            'dettes' => $dettes,
            'total' => $total
        ]);
    }
    
    /**
     * Rembourser une dette
     */
    public function rembourserDette($id)
    {
        $this->requirePermission('approvisionnements.voir');
        
        $data = $this->getJsonInput();
        
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $result = $this->detteModel->rembourser($id, $data['quantite'], $emplacementPrincipal['id']);
        
        if ($result['success']) {
            return $this->success([
                'solde' => $result['solde'] ?? false
            ], 'Remboursement enregistré avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
}
