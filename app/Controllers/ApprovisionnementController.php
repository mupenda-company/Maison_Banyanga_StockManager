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

    /**
     * Exporter les approvisionnements en Excel (CSV)
     */
    private function exportExcel($filters)
    {
        $this->requireAuth();
        
        $approvisionnements = $this->approvisionnementModel->getAllPaginated(1, 1000, $filters);
        $data = $approvisionnements['data'];
        
        $filename = "approvisionnements_" . date('Y-m-d_H-i') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Entête UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes des colonnes
        fputcsv($output, ['N° Bon', 'Date', 'Fournisseur', 'Total HT', 'Statut', 'Date Création']);
        
        foreach ($data as $appro) {
            fputcsv($output, [
                $appro['numero_bon'],
                date('d/m/Y', strtotime($appro['date_approvisionnement'])),
                $appro['fournisseur'] ?? 'Bralima',
                $appro['total_ht'],
                ucfirst($appro['statut']),
                date('d/m/Y H:i', strtotime($appro['created_at']))
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Formulaire de création
     */
    private function exportProduitsExcel($filters)
    {
        $this->requireAuth();

        $report = $this->buildProduitsApprovisionnementReport($filters);
        $filename = "approvisionnements_produits_" . date('Y-m-d_H-i') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Etat des approvisionnements par produit']);
        fputcsv($output, ['Du', $filters['date_debut'] ?: 'Debut']);
        fputcsv($output, ['Au', $filters['date_fin'] ?: 'Fin']);
        fputcsv($output, ['Statut', $filters['statut'] ?: 'Tous']);
        fputcsv($output, ['Taux systeme', '1 USD = ' . number_format(get_taux_change(), 2, '.', '') . ' CDF']);
        fputcsv($output, ['Total prix achat', number_format($report['totals']['pt'], 2, '.', ''), 'CDF', number_format(convert_money($report['totals']['pt'], get_base_devise(), 'USD'), 2, '.', ''), 'USD']);
        fputcsv($output, []);
        fputcsv($output, ['PRODUITS', 'N P', 'ACHAT', 'PLT', 'P.A.AD', 'P.A.A.E', 'P.T', 'P.V.U', 'P.V.T', 'ECART', 'TOTAL EC', 'ECART A EN', 'TOTAL A ENL']);

        foreach ($report['items'] as $row) {
            fputcsv($output, [
                $row['produit'],
                $row['np'],
                number_format($row['achat'], 0, '.', ''),
                number_format($row['plt'], 2, '.', ''),
                number_format($row['paad'], 2, '.', ''),
                number_format($row['paae'], 2, '.', ''),
                number_format($row['pt'], 2, '.', ''),
                number_format($row['pvu'], 2, '.', ''),
                number_format($row['pvt'], 2, '.', ''),
                number_format($row['ecart'], 2, '.', ''),
                number_format($row['total_ec'], 2, '.', ''),
                number_format($row['ecart_a_en'], 2, '.', ''),
                number_format($row['total_a_enl'], 2, '.', ''),
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['TOTAUX CDF', '', number_format($report['totals']['achat'], 0, '.', ''), number_format($report['totals']['plt'], 2, '.', ''), '', '', number_format($report['totals']['pt'], 2, '.', ''), '', number_format($report['totals']['pvt'], 2, '.', ''), '', number_format($report['totals']['total_ec'], 2, '.', ''), '', number_format($report['totals']['total_a_enl'], 2, '.', '')]);
        fputcsv($output, ['TOTAUX USD', '', '', '', '', '', number_format(convert_money($report['totals']['pt'], get_base_devise(), 'USD'), 2, '.', ''), '', number_format(convert_money($report['totals']['pvt'], get_base_devise(), 'USD'), 2, '.', ''), '', number_format(convert_money($report['totals']['total_ec'], get_base_devise(), 'USD'), 2, '.', ''), '', number_format(convert_money($report['totals']['total_a_enl'], get_base_devise(), 'USD'), 2, '.', '')]);

        fclose($output);
        exit;
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
             ORDER BY p.nom",
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
        $filename = 'approvisionnement_' . ($approvisionnement['numero_bon'] ?? $id) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Approvisionnement', $approvisionnement['numero_bon'] ?? $id]);
        fputcsv($output, ['Date', $approvisionnement['date_approvisionnement'] ?? '']);
        fputcsv($output, ['Fournisseur', $approvisionnement['fournisseur'] ?? 'Bralima']);
        fputcsv($output, ['Taux systeme', '1 USD = ' . number_format(get_taux_change(), 2, '.', '') . ' CDF']);
        fputcsv($output, ['Total prix achat', number_format($rows['totals']['pt'], 2, '.', ''), 'CDF', number_format(convert_money($rows['totals']['pt'], get_base_devise(), 'USD'), 2, '.', ''), 'USD']);
        fputcsv($output, []);
        fputcsv($output, ['PRODUITS', 'N P', 'ACHAT', 'PLT', 'P.A.AD', 'P.A.A.E', 'P.T', 'P.V.U', 'P.V.T', 'ECART', 'TOTAL EC', 'ECART A EN', 'TOTAL A ENL']);

        foreach ($rows['items'] as $row) {
            fputcsv($output, [
                $row['produit'],
                $row['np'],
                $row['achat'],
                number_format($row['plt'], 2, '.', ''),
                number_format($row['paad'], 2, '.', ''),
                number_format($row['paae'], 2, '.', ''),
                number_format($row['pt'], 2, '.', ''),
                number_format($row['pvu'], 2, '.', ''),
                number_format($row['pvt'], 2, '.', ''),
                number_format($row['ecart'], 2, '.', ''),
                number_format($row['total_ec'], 2, '.', ''),
                number_format($row['ecart_a_en'], 2, '.', ''),
                number_format($row['total_a_enl'], 2, '.', ''),
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['TOTAUX CDF', '', $rows['totals']['achat'], number_format($rows['totals']['plt'], 2, '.', ''), '', '', number_format($rows['totals']['pt'], 2, '.', ''), '', number_format($rows['totals']['pvt'], 2, '.', ''), '', number_format($rows['totals']['total_ec'], 2, '.', ''), '', number_format($rows['totals']['total_a_enl'], 2, '.', '')]);
        fputcsv($output, ['TOTAUX USD', '', '', '', '', '', number_format(convert_money($rows['totals']['pt'], get_base_devise(), 'USD'), 2, '.', ''), '', number_format(convert_money($rows['totals']['pvt'], get_base_devise(), 'USD'), 2, '.', ''), '', number_format(convert_money($rows['totals']['total_ec'], get_base_devise(), 'USD'), 2, '.', ''), '', number_format(convert_money($rows['totals']['total_a_enl'], get_base_devise(), 'USD'), 2, '.', '')]);

        fclose($output);
        exit;
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
