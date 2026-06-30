<?php
/**
 * Contrôleur des pertes
 */

class PerteController extends Controller
{
    private $perteModel;
    private $produitModel;
    private $emplacementModel;
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->perteModel = new Perte();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
        $this->userModel = new User();
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
            'agent_id' => $_GET['agent_id'] ?? null,
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
        $agents = $this->userModel->getActive();
        
        // Stats du mois
        $stats = $this->perteModel->getStats(date('Y-m-01'), date('Y-m-d'));
        $pertesParAgent = $this->perteModel->getByAgent($filters['date_debut'] ?: date('Y-m-01'), $filters['date_fin'] ?: date('Y-m-d'));

        if ($printMode) {
            $this->view('pertes/print', [
                'pertes' => $pertes,
                'filters' => $filters,
                'stats' => $stats,
                'pertesParAgent' => $pertesParAgent
            ]);
            return;
        }
        
        $this->view('pertes/index', [
            'pertes' => $pertes,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'agents' => $agents,
            'filters' => $filters,
            'stats' => $stats,
            'pertesParAgent' => $pertesParAgent,
            'print_mode' => $printMode
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

    private function exportExcel($pertes)
    {
        $this->requireAuth();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Pertes');

        $headers = ['Date', 'Produit', 'Code', 'Type stock', 'Categorie', 'Quantite', 'Valeur', 'Agent', 'Emplacement', 'Motif'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($pertes as $perte) {
            $caisses      = (float)($perte['quantite'] ?? 0);
            $btlParCaisse = (int)($perte['bouteilles_par_caisses'] ?? 24);
            $totalBtl     = round($caisses * $btlParCaisse);
            $cs           = intdiv($totalBtl, $btlParCaisse);
            $btlReste     = $totalBtl % $btlParCaisse;

            if ($cs > 0 && $btlReste > 0) {
                $qte = $cs . ' cs + ' . $btlReste . ' btl';
            } elseif ($cs > 0) {
                $qte = $cs . ' cs';
            } else {
                $qte = $totalBtl . ' btl';
            }

            $sheet->fromArray([
                !empty($perte['date_perte']) ? date('d/m/Y', strtotime($perte['date_perte'])) : '',
                $perte['produit_nom'] ?? '',
                $perte['produit_code'] ?? '',
                $perte['type_stock'] ?? '',
                $perte['type_perte'] ?? '',
                $qte,
                (float)($perte['valeur_perte'] ?? 0),
                trim(($perte['agent_prenom'] ?? '') . ' ' . ($perte['agent_nom'] ?? '')),
                $perte['emplacement_nom'] ?? '',
                $perte['motif'] ?? '',
            ], null, 'A' . $row++);
        }

        $this->styleHeaderRow($sheet, count($headers));
        $this->sendXlsx($spreadsheet, 'pertes_' . date('Y-m-d_H-i') . '.xlsx');
    }

    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requirePermission('pertes.creer');
        
        $produits = $this->produitModel->getWithStock();
        $emplacements = $this->emplacementModel->all('type, nom');
        $agents = $this->userModel->getActive();
        
        $this->view('pertes/create', [
            'produits' => $produits,
            'emplacements' => $emplacements,
            'agents' => $agents
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
            'agent_id' => $data['agent_id'] ?? null,
            'created_by' => $_SESSION['user_id']
        ]);
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Perte enregistrée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    

    /**
     * Formulaire de modification
     */
    public function edit($id)
    {
        $this->requirePermission('pertes.creer');

        $perte = $this->perteModel->getWithDetails((int) $id);
        if (!$perte) {
            return $this->error('Perte introuvable', 404);
        }

        $this->view('pertes/edit', [
            'perte' => $perte,
            'produits' => $this->produitModel->getWithStock(),
            'emplacements' => $this->emplacementModel->all('type, nom'),
            'agents' => $this->userModel->getActive()
        ]);
    }

    /**
     * Mettre à jour une perte et synchroniser le stock
     */
    public function update($id)
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

        $result = $this->perteModel->updateWithStockUpdate((int) $id, [
            'produit_id' => (int) $data['produit_id'],
            'emplacement_id' => (int) $data['emplacement_id'],
            'quantite' => (float) $data['quantite'],
            'unite_perte' => $data['unite_perte'] ?? 'caisse',
            'type_perte' => $data['type_perte'],
            'type_stock' => $data['type_stock'] ?? 'plein',
            'motif' => $data['motif'] ?? '',
            'date_perte' => $data['date_perte'],
            'valeur_perte' => $data['valeur_perte'] ?? 0,
            'agent_id' => !empty($data['agent_id']) ? (int) $data['agent_id'] : null,
            'updated_by' => $_SESSION['user_id'] ?? null
        ]);

        if ($result['success']) {
            return $this->success(['id' => (int) $id], 'Perte modifiée avec succès');
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
