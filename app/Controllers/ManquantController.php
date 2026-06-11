<?php

class ManquantController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Manquant();
    }

    public function index()
    {
        $this->requirePermission('pertes.voir');
        $filters = [
            'agent_id' => $_GET['agent_id'] ?? null,
            'produit_id' => $_GET['produit_id'] ?? null,
            'statut' => $_GET['statut'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? date('Y-m-01'),
            'date_fin' => $_GET['date_fin'] ?? date('Y-m-d'),
        ];

        $rows = $this->model->getWithDetails($filters);
        if (isset($_GET['export'])) {
            return $this->exportRows($rows);
        }

        if (isset($_GET['print'])) {
            $this->view('manquants/print', [
                'manquants' => $rows,
                'resume' => $this->model->getSummaryByAgent($filters),
                'filters' => $filters
            ]);
            return;
        }

        $this->view('manquants/index', [
            'manquants' => $rows,
            'resume' => $this->model->getSummaryByAgent($filters),
            'agents' => (new User())->getActive(),
            'produits' => (new Produit())->getActive(),
            'filters' => $filters,
            'print_mode' => isset($_GET['print']),
        ]);
    }

    public function create()
    {
        $this->requirePermission('pertes.creer');
        $this->view('manquants/create', [
            'agents' => (new User())->getActive(),
            'produits' => (new Produit())->getActive()
        ]);
    }

    public function store()
    {
        $this->requirePermission('pertes.creer');
        $data = $this->getJsonInput();
        $errors = $this->validate($data, [
            'agent_id' => 'required|numeric',
            'quantite_caisses' => 'required|numeric',
            'date_manquant' => 'required'
        ]);
        if ($errors) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $data['produit_id'] = !empty($data['produit_id']) ? (int) $data['produit_id'] : null;
        $data['montant'] = max(0, (float) ($data['montant'] ?? 0));
        $data['montant_paye'] = max(0, (float) ($data['montant_paye'] ?? 0));
        $data['statut'] = $data['montant_paye'] >= $data['montant'] && $data['montant'] > 0
            ? 'paye'
            : ($data['montant_paye'] > 0 ? 'partiel' : 'ouvert');
        $data['created_by'] = $_SESSION['user_id'];

        $id = $this->model->create($data);
        return $this->success(['id' => $id], 'Manquant enregistré avec succès');
    }

    public function payer($id)
    {
        $this->requirePermission('pertes.creer');
        $data = $this->getJsonInput();
        $result = $this->model->enregistrerPaiement(
            (int) $id,
            $data['montant'] ?? 0,
            $data['date_paiement'] ?? date('Y-m-d'),
            trim($data['note'] ?? ''),
            $_SESSION['user_id'] ?? null
        );

        if ($result['success']) {
            return $this->success($result, 'Paiement enregistré');
        }
        return $this->error($result['message'], 400);
    }

    public function delete($id)
    {
        $this->requirePermission('pertes.creer');
        $this->model->delete($id);
        return $this->success(null, 'Manquant supprimé');
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
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function export()
    {
        $_GET['export'] = 1;
        $this->index();
    }

    private function exportRows($rows)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Manquants');

        $headers = ['Date', 'Agent', 'Produit', 'Caisses', 'Montant', 'Payé', 'Reste', 'Statut', 'Motif'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['date_manquant'],
                $r['agent_nom'],
                $r['produit_nom'] ?: '-',
                (float) $r['quantite_caisses'],
                (float) $r['montant'],
                (float) $r['montant_paye'],
                (float) $r['reste_montant'],
                $r['statut'],
                $r['motif'],
            ], null, 'A' . $row++);
        }

        $this->styleHeaderRow($sheet, count($headers));
        $this->sendXlsx($spreadsheet, 'manquants_agents_' . date('Y-m-d_H-i') . '.xlsx');
    }

}
