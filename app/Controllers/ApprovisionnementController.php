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
            $this->exportExcel($filters);
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
