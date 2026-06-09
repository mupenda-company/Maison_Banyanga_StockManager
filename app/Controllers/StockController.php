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
    
    /**
     * Vue globale du stock
     */
    public function index()
    {
        $this->requirePermission('stock.voir');
        
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
        $this->requirePermission('stock.gerer');

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $produits = $this->produitModel->getActive();
        $stocks = [];

        foreach ($this->stockModel->getByEmplacement($emplacementPrincipal['id']) as $stock) {
            $stocks[$stock['produit_id']] = $stock;
        }

        $this->view('stocks/inventaire_initial', [
            'produits' => $produits,
            'emplacement' => $emplacementPrincipal,
            'stocks' => $stocks
        ]);
    }

    /**
     * Enregistrer l'inventaire initial
     */
    public function enregistrerInventaireInitial()
    {
        $this->requirePermission('stock.gerer');

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'emplacement_id' => 'required|numeric',
            'lignes' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $emplacementId = (int) $data['emplacement_id'];
        $motifEcart = trim($data['motif_ecart'] ?? '');
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $hasEcart = false;

        // Vérifier s'il y a des écarts et si le motif est requis
        foreach ($data['lignes'] as $ligne) {
            $produitId = (int) ($ligne['produit_id'] ?? 0);
            if ($produitId <= 0) continue;
            $ancienPleine = (int) ($ligne['ancien_caisses_pleine'] ?? 0);
            $ancienVide = (int) ($ligne['ancien_caisses_vide'] ?? 0);
            $nouveauPleine = (int) ($ligne['caisses_pleine'] ?? 0);
            $nouveauVide = (int) ($ligne['caisses_vide'] ?? 0);
            if ($nouveauPleine !== $ancienPleine || $nouveauVide !== $ancienVide) {
                $hasEcart = true;
                break;
            }
        }

        if ($hasEcart && empty($motifEcart)) {
            return $this->error('Le motif de l\'écart est requis lorsque des différences sont constatées', 422);
        }

        try {
            $this->db->beginTransaction();

            $totalProduits = 0;
            $totalEcarts = 0;
            foreach ($data['lignes'] as $ligne) {
                $produitId = (int) ($ligne['produit_id'] ?? 0);
                $caissesPleines = (int) ($ligne['caisses_pleine'] ?? 0);
                $caissesVides = (int) ($ligne['caisses_vide'] ?? 0);
                $ancienPleine = (int) ($ligne['ancien_caisses_pleine'] ?? 0);
                $ancienVide = (int) ($ligne['ancien_caisses_vide'] ?? 0);
                $stockExistant = $this->stockModel->getStock($produitId, $emplacementId);

                if ($produitId <= 0) {
                    continue;
                }

                if (!$stockExistant && $caissesPleines <= 0 && $caissesVides <= 0) {
                    continue;
                }

                $this->stockModel->setInitialStock($produitId, $emplacementId, [
                    'caisses_pleine' => $caissesPleines,
                    'caisses_vide' => $caissesVides
                ]);

                // Enregistrer les mouvements d'inventaire pour les écarts
                $ecartPleine = $caissesPleines - $ancienPleine;
                $ecartVide = $caissesVides - $ancienVide;
                $produit = (new Produit())->find($produitId);
                $btlParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24) ?: 24;

                if ($ecartPleine !== 0) {
                    $this->mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementId,
                        'type_mouvement' => 'inventaire',
                        'quantite' => $ecartPleine * $btlParCaisse,
                        'reference_type' => 'inventaire',
                        'reference_id' => 0,
                        'motif' => 'Ecart inventaire pleines: ' . ($ecartPleine > 0 ? '+' : '') . $ecartPleine . ' cs' . ($motifEcart ? ' - ' . $motifEcart : ''),
                        'created_by' => $userId,
                    ]);
                    $totalEcarts++;
                }

                if ($ecartVide !== 0) {
                    $this->mouvementModel->create([
                        'produit_id' => $produitId,
                        'emplacement_id' => $emplacementId,
                        'type_mouvement' => 'inventaire',
                        'quantite' => $ecartVide * $btlParCaisse,
                        'reference_type' => 'inventaire',
                        'reference_id' => 0,
                        'motif' => 'Ecart inventaire vides: ' . ($ecartVide > 0 ? '+' : '') . $ecartVide . ' cs' . ($motifEcart ? ' - ' . $motifEcart : ''),
                        'created_by' => $userId,
                    ]);
                    $totalEcarts++;
                }

                $totalProduits++;
            }

            $this->db->commit();

            return $this->success([
                'total_produits' => $totalProduits,
                'total_ecarts' => $totalEcarts
            ], 'Inventaire enregistré avec succès' . ($totalEcarts > 0 ? " ({$totalEcarts} écart(s) enregistré(s))" : ''));
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Exporter les stocks en Excel (CSV)
     */
    private function exportExcel($filters)
    {
        $this->requireAuth();
        
        if (!empty($filters['date_stock'])) {
            $data = $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters);
        } else {
            $result = $this->stockModel->getAllPaginated(1, 1000, $filters);
            $data = $result['data'];
        }
        
        $filename = "stocks_" . date('Y-m-d_H-i') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        
        fputcsv($output, ['Produit', 'Code', 'Emplacement', 'Type', 'Stock Plein (cs)', 'Stock Plein (btl)', 'Stock Vide (cs)', 'Statut']);
        
        foreach ($data as $s) {
            $statut = ($s['caisses_pleine'] <= ($s['seuil_alerte'] ?? 0)) ? 'CRITIQUE' : 'OK';
            fputcsv($output, [
                $s['produit_nom'],
                $s['produit_code'],
                $s['emplacement_nom'],
                ucfirst($s['emplacement_type']),
                (int) round($s['caisses_pleine']),
                $s['quantite_pleine'],
                (int) round($s['caisses_vide']),
                $statut
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Inventaire complet
     */
    public function inventaire()
    {
        $this->requirePermission('stock.voir');
        
        $filters = [
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
        
        $this->view('stocks/inventaire', [
            'inventaire' => $inventaire,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'categories' => $categories,
            'filters' => $filters,
            'totaux' => $totaux,
            'print_mode' => $printMode,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage
            ]
        ]);
    }

    /**
     * Exporter l'inventaire en Excel
     */
    private function exportInventaireExcel($filters)
    {
        $this->requireAuth();
        $data = !empty($filters['date_stock']) ? $this->stockModel->getHistoricalInventory($filters['date_stock'], $filters) : [];
        if (empty($filters['date_stock'])) {
        $page = 1;
        $last = 1;
        $perPage = 1000;
        do {
            $result = $this->stockModel->getInventairePaginated($page, $perPage, $filters);
            $data = array_merge($data, $result['data']);
            $last = (int)$result['last_page'];
            $page++;
        } while ($page <= $last);
        }
        
        $filename = "inventaire_stock_" . date('Y-m-d_H-i') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Produit', 'Code', 'Catégorie', 'Emplacement', 'Type', 'Véhicule', 'Agent', 'Stock Plein (cs)', 'Stock Vide (cs)']);
        
        foreach ($data as $item) {
            fputcsv($output, [
                $item['produit_nom'],
                $item['produit_code'],
                $item['categorie'],
                $item['emplacement_nom'],
                ucfirst($item['emplacement_type']),
                $item['vehicule'] ?: '-',
                $item['agent_nom'] ? $item['agent_prenom'] . ' ' . $item['agent_nom'] : '-',
                number_format($item['caisses_pleine'], 2, '.', ''),
                number_format($item['caisses_vide'], 2, '.', '')
            ]);
        }
        fclose($output);
        exit;
    }
    
    /**
     * Stock par emplacement
     */
    public function byEmplacement($emplacementId)
    {
        $this->requirePermission('stock.voir');
        
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
        $this->requirePermission('stock.voir');

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
        
        $this->view('stocks/mouvements', [
            'mouvements' => $mouvements,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'filters' => $filters,
            'pagination' => $pagination,
            'print_mode' => $printMode
        ]);
    }

    /**
     * Exporter les mouvements en Excel
     */
    private function exportMouvementsExcel($filters)
    {
        $this->requireAuth();
        $data = [];
        $page = 1;
        $last = 1;
        $perPage = 1000;
        do {
            $result = $this->mouvementModel->getHistorique($filters, $page, $perPage);
            $data = array_merge($data, $result['data']);
            $last = (int) $result['last_page'];
            $page++;
        } while ($page <= $last);
        
        $filename = "mouvements_stock_" . date('Y-m-d_H-i') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Date', 'Type', 'Produit', 'Emplacement', 'Quantité (cs)', 'Référence', 'Par']);
        
        foreach ($data as $m) {
            $produit = new Produit();
            $p = $produit->find($m['produit_id']);
            $btlParCaisse = $p['bouteilles_par_caisses'] ?: 24;
            $caisses = isset($m['quantite_caisses_reference']) && $m['quantite_caisses_reference'] !== null
                ? abs((float) $m['quantite_caisses_reference'])
                : abs($m['quantite'] / $btlParCaisse);

            $emplacement = $m['emplacement_source'] ?? ($m['emplacement_nom'] ?? '');
            if (($m['type_mouvement'] ?? '') === 'transfert' && !empty($m['emplacement_dest'])) {
                $emplacement .= ' -> ' . $m['emplacement_dest'];
            }

            fputcsv($output, [
                date('d/m/Y H:i', strtotime($m['created_at'])),
                ucfirst($m['type_mouvement']),
                $m['produit_nom'],
                $emplacement,
                number_format($caisses, 2, '.', ''),
                $m['reference_id'] ? $m['reference_type'] . ' #' . $m['reference_id'] : '-',
                $m['user_nom']
            ]);
        }
        fclose($output);
        exit;
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
        $this->requirePermission('stock.gerer');
        
        $data = $this->getJsonInput();
        
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
        $this->requirePermission('stock.gerer');
        
        $data = $this->getJsonInput();
        
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
}
