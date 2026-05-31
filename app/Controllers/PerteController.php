<?php
/**
 * Contrôleur des pertes
 */

class PerteController extends Controller
{
    private $perteModel;
    private $produitModel;
    private $emplacementModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->perteModel = new Perte();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
    }
    
    /**
     * Liste des pertes
     */
    public function index()
    {
        $this->requirePermission('pertes.voir');
        
        $filters = [
            'produit_id' => $_GET['produit_id'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null,
            'type_perte' => $_GET['type'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null
        ];
        
        $pertes = $this->perteModel->getAllWithDetails($filters);
        $printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($pertes);
            return;
        }

        $produits = $this->produitModel->getActive();
        $emplacements = $this->emplacementModel->all('type, nom');
        
        // Stats du mois
        $stats = $this->perteModel->getStats(date('Y-m-01'), date('Y-m-d'));
        
        $this->view('pertes/index', [
            'pertes' => $pertes,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'filters' => $filters,
            'stats' => $stats,
            'print_mode' => $printMode
        ]);
    }

    private function exportExcel($pertes)
    {
        $this->requireAuth();

        $filename = 'pertes_' . date('Y-m-d_H-i') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Date', 'Produit', 'Code', 'Type stock', 'Categorie', 'Quantite', 'Valeur', 'Emplacement', 'Motif']);

        foreach ($pertes as $perte) {
            $caisses = (float)($perte['quantite'] ?? 0);
            $btlParCaisse = (int)($perte['bouteilles_par_caisses'] ?? 24);
            $totalBouteilles = round($caisses * $btlParCaisse);
            $caissesPleines = intdiv($totalBouteilles, $btlParCaisse);
            $bouteillesReste = $totalBouteilles % $btlParCaisse;

            if ($caissesPleines > 0 && $bouteillesReste > 0) {
                $quantiteExport = $caissesPleines . ' cs + ' . $bouteillesReste . ' btl';
            } elseif ($caissesPleines > 0) {
                $quantiteExport = $caissesPleines . ' cs';
            } else {
                $quantiteExport = $totalBouteilles . ' btl';
            }

            fputcsv($output, [
                !empty($perte['date_perte']) ? date('d/m/Y', strtotime($perte['date_perte'])) : '',
                $perte['produit_nom'] ?? '',
                $perte['produit_code'] ?? '',
                $perte['type_stock'] ?? '',
                $perte['type_perte'] ?? '',
                $quantiteExport,
                number_format((float) ($perte['valeur_perte'] ?? 0), 2, '.', ''),
                $perte['emplacement_nom'] ?? '',
                $perte['motif'] ?? ''
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
        $this->requirePermission('pertes.creer');
        
        $produits = $this->produitModel->getWithStock();
        $emplacements = $this->emplacementModel->all('type, nom');
        
        $this->view('pertes/create', [
            'produits' => $produits,
            'emplacements' => $emplacements
        ]);
    }
    
    /**
     * Enregistrer une perte
     */
    public function store()
    {
        $this->requirePermission('pertes.creer');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'produit_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'quantite' => 'required|numeric',
            'type_perte' => 'required',
            'date_perte' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        // Le calcul de la valeur et la conversion sont gérés dans le modèle createWithStockUpdate
        $result = $this->perteModel->createWithStockUpdate([
            'produit_id' => $data['produit_id'],
            'emplacement_id' => $data['emplacement_id'],
            'quantite' => $data['quantite'],
            'unite_perte' => $data['unite_perte'] ?? 'caisse',
            'type_perte' => $data['type_perte'],
            'type_stock' => $data['type_stock'] ?? 'plein',
            'motif' => $data['motif'] ?? '',
            'date_perte' => $data['date_perte'],
            'valeur_perte' => $data['valeur_perte'] ?? 0,
            'created_by' => $_SESSION['user_id']
        ]);
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Perte enregistrée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Supprimer une perte (API)
     */
    public function delete($id)
    {
        $this->requirePermission('pertes.voir');
        
        $result = $this->perteModel->supprimer($id);
        
        if ($result['success']) {
            return $this->success(null, 'Perte supprimée et stock restauré');
        }
        
        return $this->error($result['message']);
    }
    
    /**
     * Statistiques des pertes
     */
    public function stats()
    {
        $this->requirePermission('pertes.voir');
        
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        
        $stats = $this->perteModel->getStats($dateDebut, $dateFin);
        $byType = $this->perteModel->getByType($dateDebut, $dateFin);
        
        $this->view('pertes/stats', [
            'stats' => $stats,
            'byType' => $byType,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }
}
