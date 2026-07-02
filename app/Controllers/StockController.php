<?php
/**
 * Contrôleur des stocks
 */

class StockController extends Controller
{
    private $stockModel;
    private $produitModel;
    private $emplacementModel;
    private $mouvementModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->stockModel = new Stock();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
        $this->mouvementModel = new MouvementStock();
    }
    private function isEmballageRoute(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        return strpos($path, '/emballages') !== false;
    }

    private function requireStockOrEmballagePermission(string $action = 'voir'): void
    {
        $permission = $this->isEmballageRoute() ? 'emballages.' . $action : 'stock.' . $action;
        $this->requirePermission($permission);
    }
    
    /**
     * Vue globale du stock
     */
    public function index()
    {
        $this->requireStockOrEmballagePermission('voir');
        
        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
            'statut' => $_GET['statut'] ?? null,
            'date_stock' => $_GET['date_stock'] ?? null
        ];

        // Exporter en Excel
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($filters);
            return;
        }

        if (isset($_GET['print']) && (string) $_GET['print'] === '1') {
            if (!empty($filters['date_stock'])) {
                $stocks = $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters);
            } else {
                $stocks = [];
                $p = 1;
                $last = 1;
                do {
                    $result = $this->stockModel->getAllPaginated($p, 500, $filters);
                    $stocks = array_merge($stocks, $result['data']);
                    $last = (int) $result['last_page'];
                    $p++;
                } while ($p <= $last);
            }

            $emplacements = $this->emplacementModel->getWithStock();
            if (!empty($filters['date_stock'])) {
                foreach ($emplacements as &$emplacement) {
                    $emplacement['total_caisses_pleine'] = 0;
                    foreach ($stocks as $stock) {
                        if ((int) $stock['emplacement_id'] === (int) $emplacement['id']) {
                            $emplacement['total_caisses_pleine'] += (float) $stock['caisses_pleine'];
                        }
                    }
                }
                unset($emplacement);
            }

            $this->view('stocks/print', [
                'stocks' => $stocks,
                'emplacements' => $emplacements,
                'filters' => $filters
            ]);
            return;
        }

        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 5;
        
        if (!empty($filters['date_stock'])) {
            $allStocks = $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters);
            $total = count($allStocks);
            $result = ['data' => array_slice($allStocks, ($page - 1) * $perPage, $perPage), 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))];
        } else {
            $result = $this->stockModel->getAllPaginated($page, $perPage, $filters);
        }
        $emplacements = $this->emplacementModel->getWithStock();
        if (!empty($filters['date_stock'])) {
            foreach ($emplacements as &$emplacement) {
                $emplacement['total_caisses_pleine'] = 0;
                foreach ($allStocks as $stock) if ((int) $stock['emplacement_id'] === (int) $emplacement['id']) $emplacement['total_caisses_pleine'] += (float) $stock['caisses_pleine'];
            }
            unset($emplacement);
        }
        $produits = $this->produitModel->getActive();
        
        $this->view('stocks/index', [
            'stocks' => $result['data'],
            'pagination' => [
                'current_page' => $page,
                'last_page' => $result['last_page'],
                'total' => $result['total'],
                'per_page' => $perPage
            ],
            'emplacements' => $emplacements,
            'produits' => $produits,
            'filters' => $filters
        ]);
    }

    /**
     * Inventaire initial du stock
     */
    public function inventaireInitial()
    {
        $this->requireStockOrEmballagePermission('gerer');

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $produits = $this->produitModel->getActive();
        $stocks = [];

        foreach ($this->stockModel->getByEmplacement($emplacementPrincipal['id']) as $stock) {
            $stocks[$stock['produit_id']] = $stock;
        }

        $this->view('stocks/inventaire_initial', [
            'produits' => $produits,
            'emplacement' => $emplacementPrincipal,
            'stocks' => $stocks,
            'emballageMode' => $this->isEmballageRoute()
        ]);
    }

    /**
     * Enregistrer l'inventaire initial
     */
    public function enregistrerInventaireInitial()
    {
        $data = $this->getJsonInput();
        $mode = ($data['mode'] ?? 'stock') === 'emballage' ? 'emballage' : 'stock';
        $this->requirePermission($mode === 'emballage' ? 'emballages.gerer' : 'stock.gerer');


        $errors = $this->validate($data, [
            'emplacement_id' => 'required|numeric',
            'lignes' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $emplacementId = (int) $data['emplacement_id'];
        $motifInventaire = trim((string) ($data['motif_ecart'] ?? $data['motif'] ?? ''));
        try {
            $this->db->beginTransaction();

            $totalProduits = 0;
            $totalEcarts = 0;
            foreach ($data['lignes'] as $ligne) {
                $produitId = (int) ($ligne['produit_id'] ?? 0);

                if ($produitId <= 0) {
                    continue;
                }

                $produit = $this->produitModel->find($produitId);
                if (!$produit) {
                    throw new Exception('Produit #' . $produitId . ' non trouve');
                }

                $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($btlParCaisse <= 0) {
                    $btlParCaisse = 24;
                }

                $stockExistant = $this->stockModel->getStock($produitId, $emplacementId);
                $ancienPleine = max(0, (int) round((float) ($stockExistant['caisses_pleine'] ?? 0)));
                $ancienVide = max(0, (int) round((float) ($stockExistant['caisses_vide'] ?? 0)));
                $caissesPleines = $mode === 'emballage' ? $ancienPleine : max(0, (int) ($ligne['caisses_pleine'] ?? 0));
                $caissesVides = $mode === 'emballage' ? max(0, (int) ($ligne['caisses_vide'] ?? 0)) : $ancienVide;

                if (!$stockExistant && $caissesPleines <= 0 && $caissesVides <= 0) {
                    continue;
                }

                $ecartPlein = $caissesPleines - $ancienPleine;
                $ecartVide = $caissesVides - $ancienVide;

                if (($ecartPlein !== 0 || $ecartVide !== 0) && $motifInventaire === '') {
                    throw new Exception('Le motif de l\'inventaire est obligatoire lorsqu\'une quantite change.');
                }

                if ($ecartPlein !== 0) {
                    $this->mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementId,
                        'type_mouvement' => 'inventaire',
                        'quantite' => $ecartPlein * $btlParCaisse,
                        'reference_type' => 'inventaire_initial',
                        'reference_id' => null,
                        'motif' => 'Inventaire initial plein: ' . $motifInventaire,
                        'created_by' => $_SESSION['user_id'] ?? null,
                    ]);
                    $totalEcarts++;
                }

                if ($ecartVide !== 0) {
                    $this->mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementId,
                        'type_mouvement' => 'inventaire',
                        'quantite' => $ecartVide * $btlParCaisse,
                        'reference_type' => 'inventaire_initial',
                        'reference_id' => null,
                        'motif' => 'Inventaire initial vide: ' . $motifInventaire,
                        'created_by' => $_SESSION['user_id'] ?? null,
                    ]);
                    $totalEcarts++;
                }

                $this->stockModel->setInitialStock($produitId, $emplacementId, [
                    'caisses_pleine' => $caissesPleines,
                    'caisses_vide' => $caissesVides
                ]);

                $totalProduits++;
            }

            $this->db->commit();

            return $this->success([
                'total_produits' => $totalProduits,
                'total_ecarts' => $totalEcarts
            ], 'Inventaire initial enregistre avec succes');
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($e->getMessage(), 400);
        }
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

    /**
     * Exporter les stocks en Excel 
     */
    private function exportExcel($filters)
    {
        $this->requireAuth();

        $data = [];
        if (!empty($filters['date_stock'])) {
            $data = $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters);
        } else {
            $page = 1;
            $last = 1;
            do {
                $result = $this->stockModel->getAllPaginated($page, 1000, $filters);
                $data = array_merge($data, $result['data']);
                $last = (int) $result['last_page'];
                $page++;
            } while ($page <= $last);
        }

        $pivot = $this->buildStockPivot($data);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Stocks');

        $headers = array_merge(['Produit', 'Entrepot'], $pivot['vehicles'], ['Emballages', 'Total']);
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($pivot['rows'] as $item) {
            $line = [$item['produit'], round((float) $item['entrepot'], 2)];
            foreach ($pivot['vehicles'] as $vehicle) {
                $line[] = round((float) ($item['vehicles'][$vehicle] ?? 0), 2);
            }
            $line[] = round((float) $item['emballages'], 2);
            $line[] = round((float) $item['total'], 2);
            $sheet->fromArray($line, null, 'A' . $row++);
        }

        $totalLine = [$pivot['totals']['produit'], round((float) $pivot['totals']['entrepot'], 2)];
        foreach ($pivot['vehicles'] as $vehicle) {
            $totalLine[] = round((float) ($pivot['totals']['vehicles'][$vehicle] ?? 0), 2);
        }
        $totalLine[] = round((float) $pivot['totals']['emballages'], 2);
        $totalLine[] = round((float) $pivot['totals']['total'], 2);
        $sheet->fromArray($totalLine, null, 'A' . $row);

        $this->styleHeaderRow($sheet, count($headers));
        $sheet->getStyle('A' . $row . ':' . $sheet->getHighestColumn() . $row)->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $this->sendXlsx($spreadsheet, 'stocks_' . date('Y-m-d_H-i') . '.xlsx');
    }

    /**
     * Inventaire complet
     */
    public function inventaire()
    {
        $this->requireStockOrEmballagePermission('voir');
        
        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
            'categorie' => $_GET['categorie'] ?? null,
            'date_stock' => $_GET['date_stock'] ?? null
        ];

        $printMode = isset($_GET['print']) && (string)$_GET['print'] === '1';
        
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 5;

        // Exporter en Excel
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportInventaireExcel($filters);
            return;
        }
        
        if (!empty($filters['date_stock'])) {
            $allInventaire = $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters);
            $total = count($allInventaire);
            $inventaire = $printMode ? $allInventaire : array_slice($allInventaire, ($page - 1) * $perPage, $perPage);
            if ($printMode) { $page = 1; $perPage = max(1, $total); }
        } elseif ($printMode) {
            $inventaire = [];
            $p = 1;
            $last = 1;
            $chunk = 500;
            do {
                $result = $this->stockModel->getInventairePaginated($p, $chunk, $filters);
                $inventaire = array_merge($inventaire, $result['data']);
                $last = (int)$result['last_page'];
                $p++;
            } while ($p <= $last);

            $total = count($inventaire);
            $page = 1;
            $perPage = max(1, $total);
        } else {
            $result = $this->stockModel->getInventairePaginated($page, $perPage, $filters);
            $inventaire = $result['data'];
            $total = $result['total'];
        }

        $produits = $this->produitModel->getActive();
        $emplacements = $this->emplacementModel->getWithStock();
        $categories = $this->produitModel->getCategories();
        
        // Totaux globaux (non paginés pour le résumé)
        if (!empty($filters['date_stock'])) {
            $totaux = ['pleines' => 0, 'vides' => 0, 'caisses_pleine' => 0, 'caisses_vide' => 0, 'valeur' => 0, 'nb_produits' => 0]; $ids = [];
            foreach ($allInventaire as $item) { $totaux['pleines'] += (int) $item['quantite_pleine']; $totaux['vides'] += (int) $item['quantite_vide']; $totaux['caisses_pleine'] += (float) $item['caisses_pleine']; $totaux['caisses_vide'] += (float) $item['caisses_vide']; $totaux['valeur'] += (float) $item['caisses_pleine'] * (float) ($item['prix_vente_caisses'] ?? 0); $ids[$item['produit_id']] = true; }
            $totaux['nb_produits'] = count($ids);
        } else { $totaux = $this->stockModel->getInventaireTotaux($filters); }
        
        $viewData = [
            'inventaire' => $inventaire,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'categories' => $categories,
            'filters' => $filters,
            'totaux' => $totaux,
            'print_mode' => $printMode,
            'stockPivot' => $printMode ? $this->buildStockPivot($inventaire) : null,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage
            ]
        ];

        if ($printMode) {
            $this->view('stocks/print-inventaire', $viewData);
            return;
        }

        $this->view('stocks/inventaire', $viewData);
    }


    private function buildStockPivot(array $data): array
    {
        $products = [];
        $vehicles = [];
        $productOrders = [];
        foreach ($this->produitModel->getActive() as $produit) {
            $productOrders[(int) $produit['id']] = [
                'position' => (int) ($produit['position_affichage'] ?? 999),
                'nom' => (string) ($produit['nom'] ?? ''),
            ];
        }

        foreach ($data as $item) {
            $productId = (int) ($item['produit_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'produit' => trim(($item['produit_nom'] ?? '') . (!empty($item['produit_code']) ? ' (' . $item['produit_code'] . ')' : '')),
                    'entrepot' => 0.0,
                    'vehicles' => [],
                    'emballages' => 0.0,
                    'total' => 0.0,
                    'position' => $productOrders[$productId]['position'] ?? 999,
                    'nom_tri' => $productOrders[$productId]['nom'] ?? (string) ($item['produit_nom'] ?? ''),
                ];
            }

            $full = round((float) ($item['caisses_pleine'] ?? 0), 2);
            $empty = round((float) ($item['caisses_vide'] ?? 0), 2);
            $type = strtolower((string) ($item['emplacement_type'] ?? ''));
            $vehicleLabel = trim((string) ($item['vehicule'] ?? ''));
            if ($vehicleLabel === '' && $type === 'mobile') {
                $vehicleLabel = trim((string) ($item['emplacement_nom'] ?? 'Vehicule'));
            }

            if ($type === 'mobile' || $vehicleLabel !== '') {
                $label = $vehicleLabel !== '' ? $vehicleLabel : 'Vehicule';
                $vehicles[$label] = true;
                $products[$productId]['vehicles'][$label] = ($products[$productId]['vehicles'][$label] ?? 0) + $full;
            } else {
                $products[$productId]['entrepot'] += $full;
            }
            $products[$productId]['emballages'] += $empty;
            $products[$productId]['total'] += $full;
        }

        $vehicleLabels = array_keys($vehicles);
        sort($vehicleLabels, SORT_NATURAL | SORT_FLAG_CASE);
        uasort($products, static function ($a, $b) {
            return [(int) ($a['position'] ?? 999), (string) ($a['nom_tri'] ?? $a['produit'] ?? '')]
                <=> [(int) ($b['position'] ?? 999), (string) ($b['nom_tri'] ?? $b['produit'] ?? '')];
        });

        $totals = ['produit' => 'TOTAL', 'entrepot' => 0.0, 'vehicles' => [], 'emballages' => 0.0, 'total' => 0.0];
        foreach ($products as $row) {
            $totals['entrepot'] += (float) $row['entrepot'];
            $totals['emballages'] += (float) $row['emballages'];
            $totals['total'] += (float) $row['total'];
            foreach ($vehicleLabels as $label) {
                $totals['vehicles'][$label] = ($totals['vehicles'][$label] ?? 0) + (float) ($row['vehicles'][$label] ?? 0);
            }
        }

        return ['vehicles' => $vehicleLabels, 'rows' => array_values($products), 'totals' => $totals];
    }

    /**
     * Exporter l'inventaire en Excel
     */
    private function exportInventaireExcel($filters)
    {
        $this->requireAuth();

        $data = [];
        if (!empty($filters['date_stock'])) {
            $data = $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters);
        } else {
            $page = 1;
            $last = 1;
            do {
                $result = $this->stockModel->getInventairePaginated($page, 1000, $filters);
                $data = array_merge($data, $result['data']);
                $last = (int) $result['last_page'];
                $page++;
            } while ($page <= $last);
        }

        $pivot = $this->buildStockPivot($data);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Inventaire stock');

        $headers = array_merge(['Produit', 'Entrepot'], $pivot['vehicles'], ['Emballages', 'Total']);
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($pivot['rows'] as $item) {
            $line = [$item['produit'], round((float) $item['entrepot'], 2)];
            foreach ($pivot['vehicles'] as $vehicle) {
                $line[] = round((float) ($item['vehicles'][$vehicle] ?? 0), 2);
            }
            $line[] = round((float) $item['emballages'], 2);
            $line[] = round((float) $item['total'], 2);
            $sheet->fromArray($line, null, 'A' . $row++);
        }

        $totalLine = [$pivot['totals']['produit'], round((float) $pivot['totals']['entrepot'], 2)];
        foreach ($pivot['vehicles'] as $vehicle) {
            $totalLine[] = round((float) ($pivot['totals']['vehicles'][$vehicle] ?? 0), 2);
        }
        $totalLine[] = round((float) $pivot['totals']['emballages'], 2);
        $totalLine[] = round((float) $pivot['totals']['total'], 2);
        $sheet->fromArray($totalLine, null, 'A' . $row);

        $this->styleHeaderRow($sheet, count($headers));
        $sheet->getStyle('A' . $row . ':' . $sheet->getHighestColumn() . $row)->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $this->sendXlsx($spreadsheet, 'inventaire_stock_' . date('Y-m-d_H-i') . '.xlsx');
    }


    /**
     * Stock par emplacement
     */
    public function byEmplacement($emplacementId)
    {
        $this->requireStockOrEmballagePermission('voir');
        
        $emplacement = $this->emplacementModel->find($emplacementId);
        
        if (!$emplacement) {
            return $this->error('Emplacement non trouvé', 404);
        }
        
        $stocks = $this->stockModel->getByEmplacement($emplacementId);
        
        if ($this->isAjax()) {
            return $this->success([
                'emplacement' => $emplacement,
                'stocks' => $stocks
            ]);
        }
        
        $this->view('stocks/emplacement', [
            'emplacement' => $emplacement,
            'stocks' => $stocks
        ]);
    }
    
    /**
     * Historique des mouvements
     */
    public function mouvements()
    {
        $this->requireStockOrEmballagePermission('voir');

        $printMode = isset($_GET['print']) && (string)$_GET['print'] === '1';

        $date = $_GET['date'] ?? null;
        $dateDebut = $_GET['date_debut'] ?? null;
        $dateFin = $_GET['date_fin'] ?? null;
        if (!empty($date)) {
            $dateDebut = $date;
            $dateFin = $date;
        }

        $type = $_GET['type'] ?? null;

        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
            'type_mouvement' => $type,
            'type' => $type,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'date' => $date
        ];
        
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 5;

        if ($printMode) {
            $mouvements = [];
            $p = 1;
            $last = 1;
            $chunk = 500;
            do {
                $result = $this->mouvementModel->getHistorique($filters, $p, $chunk);
                $mouvements = array_merge($mouvements, $result['data']);
                $last = (int) $result['last_page'];
                $p++;
            } while ($p <= $last);

            $total = count($mouvements);
            $page = 1;
            $perPage = max(1, $total);
        } else {
            $result = $this->mouvementModel->getHistorique($filters, $page, $perPage);
            $mouvements = $result['data'];
            $total = $result['total'];
        }
        
        // Exporter en Excel
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportMouvementsExcel($filters);
            return;
        }

        $produits = $this->produitModel->getActive();
        $emplacements = $this->emplacementModel->getWithStock();
        
        $pagination = [
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'total' => $total,
            'per_page' => $perPage
        ];
        
        $viewData = [
            'mouvements' => $mouvements,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'filters' => $filters,
            'pagination' => $pagination,
            'print_mode' => $printMode
        ];

        if ($printMode) {
            $this->view('stocks/print-mouvements', $viewData);
            return;
        }

        $this->view('stocks/mouvements', $viewData);
    }

    /**
     * Exporter les mouvements en Excel
     */
    private function exportMouvementsExcel($filters)
    {
        $this->requireAuth();

        // Récupération paginée (inchangée)
        $data = [];
        $page = 1;
        $last = 1;
        do {
            $result = $this->mouvementModel->getHistorique($filters, $page, 1000);
            $data   = array_merge($data, $result['data']);
            $last   = (int) $result['last_page'];
            $page++;
        } while ($page <= $last);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Mouvements stock');

        $headers = ['Date', 'Type', 'Produit', 'Emplacement', 'Quantité (cs)', 'Référence', 'Par'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($data as $m) {
            $produit     = new Produit();
            $p           = $produit->find($m['produit_id']);
            $btlParCaisse = $p['bouteilles_par_caisses'] ?: 24;
            $caisses = isset($m['quantite_caisses_reference']) && $m['quantite_caisses_reference'] !== null
                ? abs((float) $m['quantite_caisses_reference'])
                : abs($m['quantite'] / $btlParCaisse);

            $emplacement = $m['emplacement_source'] ?? ($m['emplacement_nom'] ?? '');
            if (($m['type_mouvement'] ?? '') === 'transfert' && !empty($m['emplacement_dest'])) {
                $emplacement .= ' -> ' . $m['emplacement_dest'];
            }

            $sheet->fromArray([
                date('d/m/Y H:i', strtotime($m['created_at'])),
                ucfirst($m['type_mouvement']),
                $m['produit_nom'],
                $emplacement,
                round($caisses, 2),
                $m['reference_id'] ? $m['reference_type'] . ' #' . $m['reference_id'] : '-',
                $m['user_nom'],
            ], null, 'A' . $row++);
        }

        $this->styleHeaderRow($sheet, count($headers));
        $this->sendXlsx($spreadsheet, 'mouvements_stock_' . date('Y-m-d_H-i') . '.xlsx');
    }

    /**
     * API pour le stock global
     */
    public function apiGlobal()
    {
        $this->requireAuth();
        
        $stock = $this->stockModel->getGlobalByProduct();
        
        return $this->success($stock);
    }
    
    /**
     * API pour le stock d'un emplacement
     */
    public function apiEmplacement($emplacementId)
    {
        $this->requireAuth();
        
        $stock = $this->stockModel->getByEmplacement($emplacementId);
        
        return $this->success($stock);
    }
    
    /**
     * Transfert de stock
     */
    public function transfert()
    {
        $data = $this->getJsonInput();
        $mode = ($data['mode'] ?? 'stock') === 'emballage' ? 'emballage' : 'stock';
        $this->requirePermission($mode === 'emballage' ? 'emballages.gerer' : 'stock.gerer');

        if (!empty($data['lignes']) && is_array($data['lignes'])) {
            $totalLignes = 0;
            try {
                $this->db->beginTransaction();

                if (($data['emplacement_source'] ?? null) === ($data['emplacement_dest'] ?? null)) {
                    throw new Exception('Les emplacements source et destination doivent etre differents');
                }

                foreach ($data['lignes'] as $ligne) {
                    $produitId = (int) ($ligne['produit_id'] ?? 0);
                    $quantite = (int) ($ligne['quantite'] ?? 0);
                    if ($produitId <= 0 || $quantite <= 0) {
                        continue;
                    }

                    $stockSource = $this->stockModel->getStock($produitId, $data['emplacement_source']);
                    if (!$stockSource || (int) ($stockSource['quantite_pleine'] ?? 0) < $quantite) {
                        $produit = $this->produitModel->find($produitId);
                        throw new Exception('Stock insuffisant pour ' . ($produit['nom'] ?? ('produit #' . $produitId)));
                    }

                    $this->stockModel->updateOrCreate($produitId, $data['emplacement_source'], ['quantite_pleine' => -$quantite]);
                    $this->stockModel->updateOrCreate($produitId, $data['emplacement_dest'], ['quantite_pleine' => $quantite]);

                    $this->mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $data['emplacement_source'],
                        'type_mouvement' => 'transfert',
                        'quantite' => -$quantite,
                        'reference_type' => 'transfert',
                        'reference_id' => $data['emplacement_dest'],
                        'motif' => $data['motif'] ?? 'Transfert multi-produits',
                        'created_by' => $_SESSION['user_id']
                    ]);
                    $totalLignes++;
                }

                if ($totalLignes <= 0) {
                    throw new Exception('Ajoutez au moins un produit a transferer');
                }

                $this->db->commit();
                return $this->success(['total_lignes' => $totalLignes], 'Transfert multi-produits effectue avec succes');
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return $this->error($e->getMessage(), 400);
            }
        }
        
        
        $errors = $this->validate($data, [
            'produit_id' => 'required|numeric',
            'emplacement_source' => 'required|numeric',
            'emplacement_dest' => 'required|numeric',
            'quantite' => 'required|numeric'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        if ($data['emplacement_source'] === $data['emplacement_dest']) {
            return $this->error('Les emplacements source et destination doivent être différents', 422);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Vérifier le stock source
            $stockSource = $this->stockModel->getStock($data['produit_id'], $data['emplacement_source']);
            
            if (!$stockSource || $stockSource['quantite_pleine'] < $data['quantite']) {
                throw new Exception('Stock insuffisant dans l\'emplacement source');
            }
            
            // Déduire de la source
            $this->stockModel->updateOrCreate(
                $data['produit_id'],
                $data['emplacement_source'],
                ['quantite_pleine' => -$data['quantite']]
            );
            
            // Ajouter à la destination
            $this->stockModel->updateOrCreate(
                $data['produit_id'],
                $data['emplacement_dest'],
                ['quantite_pleine' => $data['quantite']]
            );
            
            // Enregistrer les mouvements
            $this->mouvementModel->create([
                'produit_id' => $data['produit_id'],
                'emplacement_id' => $data['emplacement_source'],
                'type_mouvement' => 'transfert',
                'quantite' => -$data['quantite'],
                'quantite_avant' => $stockSource['quantite_pleine'],
                'quantite_apres' => $stockSource['quantite_pleine'] - $data['quantite'],
                'reference_type' => 'transfert',
                'reference_id' => $data['emplacement_dest'],
                'motif' => $data['motif'] ?? 'Transfert vers un autre emplacement',
                'created_by' => $_SESSION['user_id']
            ]);
            
            $this->db->commit();
            
            return $this->success(null, 'Transfert effectué avec succès');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($e->getMessage(), 400);
        }
    }
    
    /**
     * Ajustement de stock (inventaire)
     */
    public function ajustement()
    {
        $data = $this->getJsonInput();
        $mode = ($data['mode'] ?? 'stock') === 'emballage' ? 'emballage' : 'stock';
        $this->requirePermission($mode === 'emballage' ? 'emballages.gerer' : 'stock.gerer');
        
        
        $errors = $this->validate($data, [
            'produit_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'quantite_reelle' => 'required|numeric',
            'motif' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        try {
            $this->db->beginTransaction();
            
            $stock = $this->stockModel->getStock($data['produit_id'], $data['emplacement_id']);
            $quantiteTheorique = $stock ? $stock['quantite_pleine'] : 0;
            $ecart = $data['quantite_reelle'] - $quantiteTheorique;
            
            if ($ecart === 0) {
                $this->db->commit();
                return $this->success(null, 'Aucun écart détecté');
            }
            
            $produit = $this->produitModel->find($data['produit_id']);
            $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($btlParCaisse <= 0) {
                $btlParCaisse = 24;
            }
            $caissesReelles = (int) round($data['quantite_reelle'] / $btlParCaisse);

            // Mettre à jour le stock
            $this->stockModel->setInitialStock($data['produit_id'], $data['emplacement_id'], [
                'quantite_pleine' => (int) $data['quantite_reelle'],
                'quantite_vide' => (int) ($stock['quantite_vide'] ?? 0),
                'caisses_pleine' => $caissesReelles,
                'caisses_vide' => (int) ($stock['caisses_vide'] ?? 0)
            ]);
            
            // Enregistrer le mouvement
            $this->mouvementModel->create([
                'produit_id' => $data['produit_id'],
                'emplacement_id' => $data['emplacement_id'],
                'type_mouvement' => 'inventaire',
                'quantite' => $ecart,
                'quantite_avant' => $quantiteTheorique,
                'quantite_apres' => $data['quantite_reelle'],
                'reference_type' => 'inventaire',
                'reference_id' => null,
                'motif' => $data['motif'],
                'created_by' => $_SESSION['user_id']
            ]);
            
            $this->db->commit();
            
            return $this->success([
                'ecart' => $ecart,
                'quantite_avant' => $quantiteTheorique,
                'quantite_apres' => $data['quantite_reelle']
            ], 'Ajustement enregistré avec succès');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Page de correction des écarts système/physique.
     */
    public function correction()
    {
        $this->requirePermission('stock.gerer');

        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
        ];

        $ecarts = $this->stockModel->getEcarts($filters);

        $this->view('stocks/correction', [
            'ecarts' => $ecarts,
            'filters' => $filters,
            'produits' => $this->produitModel->getActive(),
            'emplacements' => $this->emplacementModel->getWithStock(),
        ]);
    }

    /**
     * API : valider une correction d'écart.
     */
    public function saveCorrection()
    {
        $data = $this->getJsonInput();
        $mode = ($data['mode'] ?? 'stock') === 'emballage' ? 'emballage' : 'stock';
        $this->requirePermission($mode === 'emballage' ? 'emballages.gerer' : 'stock.gerer');

        $errors = $this->validate($data, [
            'produit_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'motif' => 'required',
        ]);

        if (!empty($errors)) {
            return $this->error('Données invalides', 422, $errors);
        }

        $result = $this->stockModel->corrigerEcart(
            (int) $data['produit_id'],
            (int) $data['emplacement_id'],
            [
                'corriger_plein' => !empty($data['corriger_plein']),
                'corriger_vide' => !empty($data['corriger_vide']),
                'motif' => trim((string) ($data['motif'] ?? '')),
                'created_by' => $_SESSION['user_id'] ?? null,
            ]
        );

        if (!empty($result['success'])) {
            return $this->success($result, $result['message'] ?? 'Écart corrigé avec succès');
        }

        return $this->error($result['message'] ?? 'Correction impossible', 400);
    }

    /**
     * Historique des corrections d'écarts.
     */
    public function historiqueCorrections()
    {
        $this->requireStockOrEmballagePermission('voir');

        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
        ];

        $ajustements = $this->stockModel->getHistoriqueAjustements($filters);

        $this->view('stocks/historique-ajustements', [
            'ajustements' => $ajustements,
            'filters' => $filters,
            'produits' => $this->produitModel->getActive(),
            'emplacements' => $this->emplacementModel->getWithStock(),
        ]);
    }

}
