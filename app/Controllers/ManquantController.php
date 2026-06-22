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
            'produits' => (new Produit())->getActive(),
            'manquant' => null,
            'editMode' => false
        ]);
    }

    public function edit($id)
    {
        $this->requirePermission('pertes.creer');

        $manquant = $this->model->find((int) $id);
        if (!$manquant) {
            return $this->error('Manquant introuvable', 404);
        }

        $this->view('manquants/create', [
            'agents' => (new User())->getActive(),
            'produits' => (new Produit())->getActive(),
            'manquant' => $manquant,
            'editMode' => true
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

        $montants = $this->montantsDepuisDeuxDevises($data);

        $payload = [
            'agent_id' => (int) $data['agent_id'],
            'mission_id' => !empty($data['mission_id']) ? (int) $data['mission_id'] : null,
            'type_manquant' => $data['type_manquant'] ?? 'manuel',
            'produit_id' => !empty($data['produit_id']) ? (int) $data['produit_id'] : null,
            'quantite_caisses' => max(0, (float) ($data['quantite_caisses'] ?? 0)),
            'quantite_caisses_reglee' => max(0, (float) ($data['quantite_caisses_reglee'] ?? 0)),
            'quantite_emballages' => max(0, (float) ($data['quantite_emballages'] ?? 0)),
            'quantite_emballages_reglee' => max(0, (float) ($data['quantite_emballages_reglee'] ?? 0)),
            'montant' => $montants['montant_base'],
            'montant_cdf' => $montants['montant_cdf'],
            'montant_usd' => $montants['montant_usd'],
            'montant_paye' => $montants['montant_paye_base'],
            'montant_paye_cdf' => $montants['montant_paye_cdf'],
            'montant_paye_usd' => $montants['montant_paye_usd'],
            'date_manquant' => $data['date_manquant'],
            'motif' => trim((string) ($data['motif'] ?? '')),
            'notes_reglement' => trim((string) ($data['notes_reglement'] ?? '')),
            'created_by' => $_SESSION['user_id'] ?? null,
        ];

        $payload['statut'] = $this->determinerStatut(
            $payload['montant'],
            $payload['montant_paye'],
            $payload['quantite_caisses'],
            $payload['quantite_caisses_reglee'],
            $payload['quantite_emballages'],
            $payload['quantite_emballages_reglee']
        );

        $id = $this->model->create($payload);
        return $this->success(['id' => $id], 'Manquant enregistré avec succès');
    }

    public function update($id)
    {
        $this->requirePermission('pertes.creer');

        $manquant = $this->model->find((int) $id);
        if (!$manquant) {
            return $this->error('Manquant introuvable', 404);
        }

        $data = $this->getJsonInput();
        $errors = $this->validate($data, [
            'agent_id' => 'required|numeric',
            'quantite_caisses' => 'required|numeric',
            'date_manquant' => 'required'
        ]);

        if ($errors) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $montants = $this->montantsDepuisDeuxDevises($data);

        $payload = [
            'agent_id' => (int) $data['agent_id'],
            'produit_id' => !empty($data['produit_id']) ? (int) $data['produit_id'] : null,
            'quantite_caisses' => max(0, (float) ($data['quantite_caisses'] ?? 0)),
            'quantite_caisses_reglee' => max(0, (float) ($data['quantite_caisses_reglee'] ?? ($manquant['quantite_caisses_reglee'] ?? 0))),
            'quantite_emballages' => max(0, (float) ($data['quantite_emballages'] ?? 0)),
            'quantite_emballages_reglee' => max(0, (float) ($data['quantite_emballages_reglee'] ?? ($manquant['quantite_emballages_reglee'] ?? 0))),
            'montant' => $montants['montant_base'],
            'montant_cdf' => $montants['montant_cdf'],
            'montant_usd' => $montants['montant_usd'],
            'montant_paye' => $montants['montant_paye_base'],
            'montant_paye_cdf' => $montants['montant_paye_cdf'],
            'montant_paye_usd' => $montants['montant_paye_usd'],
            'date_manquant' => $data['date_manquant'],
            'motif' => trim((string) ($data['motif'] ?? '')),
            'notes_reglement' => trim((string) ($data['notes_reglement'] ?? ($manquant['notes_reglement'] ?? ''))),
        ];

        if (array_key_exists('mission_id', $data)) {
            $payload['mission_id'] = !empty($data['mission_id']) ? (int) $data['mission_id'] : null;
        }

        if (array_key_exists('type_manquant', $data)) {
            $payload['type_manquant'] = $data['type_manquant'] ?: ($manquant['type_manquant'] ?? 'manuel');
        }

        $payload['statut'] = $this->determinerStatut(
            $payload['montant'],
            $payload['montant_paye'],
            $payload['quantite_caisses'],
            $payload['quantite_caisses_reglee'],
            $payload['quantite_emballages'],
            $payload['quantite_emballages_reglee']
        );

        $this->model->update((int) $id, $payload);
        return $this->success(['id' => (int) $id], 'Manquant modifié avec succès');
    }

    public function payer($id)
    {
        $this->requirePermission('pertes.creer');

        $data = $this->getJsonInput();
        $montants = $this->montantsDepuisDeuxDevises($data, true);

        $result = $this->model->enregistrerPaiement(
            (int) $id,
            $montants['montant_paye_base'],
            $data['date_paiement'] ?? date('Y-m-d'),
            trim($data['note'] ?? ''),
            $_SESSION['user_id'] ?? null,
            $montants['montant_paye_cdf'],
            $montants['montant_paye_usd'],
            max(0, (float) ($data['quantite_caisses_reglee'] ?? $data['caisses_reglees'] ?? 0)),
            max(0, (float) ($data['quantite_emballages_reglee'] ?? $data['emballages_regles'] ?? 0))
        );

        if ($result['success']) {
            return $this->success($result, 'Paiement enregistré');
        }

        return $this->error($result['message'], 400);
    }

    public function delete($id)
    {
        $this->requirePermission('pertes.creer');

        $this->model->delete((int) $id);
        return $this->success(null, 'Manquant supprimé');
    }

    private function montantsDepuisDeuxDevises(array $data, bool $paiementSeulement = false): array
    {
        $parametre = new Parametre();
        $baseDevise = strtoupper((string) $parametre->get('devise_base', $parametre->get('devise', 'CDF')));
        $tauxChange = (float) $parametre->get('taux_change', '2800');
        if ($tauxChange <= 0) {
            $tauxChange = 2800;
        }

        $montantCdf = max(0, (float) ($data['montant_cdf'] ?? 0));
        $montantUsd = max(0, (float) ($data['montant_usd'] ?? 0));
        $montantPayeCdf = max(0, (float) ($data['montant_paye_cdf'] ?? ($data['paiement_cdf'] ?? 0)));
        $montantPayeUsd = max(0, (float) ($data['montant_paye_usd'] ?? ($data['paiement_usd'] ?? 0)));

        // Compatibilité avec les anciens champs.
        if (!$paiementSeulement && $montantCdf <= 0 && $montantUsd <= 0 && isset($data['montant'])) {
            if ($baseDevise === 'USD') {
                $montantUsd = max(0, (float) $data['montant']);
            } else {
                $montantCdf = max(0, (float) $data['montant']);
            }
        }

        if ($paiementSeulement && $montantPayeCdf <= 0 && $montantPayeUsd <= 0 && isset($data['montant'])) {
            if ($baseDevise === 'USD') {
                $montantPayeUsd = max(0, (float) $data['montant']);
            } else {
                $montantPayeCdf = max(0, (float) $data['montant']);
            }
        }

        if (!$paiementSeulement && $montantPayeCdf <= 0 && $montantPayeUsd <= 0 && isset($data['montant_paye'])) {
            if ($baseDevise === 'USD') {
                $montantPayeUsd = max(0, (float) $data['montant_paye']);
            } else {
                $montantPayeCdf = max(0, (float) $data['montant_paye']);
            }
        }

        $toBase = static function (float $cdf, float $usd) use ($baseDevise, $tauxChange): float {
            if ($baseDevise === 'USD') {
                return round($usd + ($cdf / $tauxChange), 2);
            }
            return round($cdf + ($usd * $tauxChange), 2);
        };

        return [
            'montant_cdf' => $montantCdf,
            'montant_usd' => $montantUsd,
            'montant_base' => $toBase($montantCdf, $montantUsd),
            'montant_paye_cdf' => $montantPayeCdf,
            'montant_paye_usd' => $montantPayeUsd,
            'montant_paye_base' => $toBase($montantPayeCdf, $montantPayeUsd),
        ];
    }

    private function determinerStatut(
        float $montant,
        float $montantPaye,
        float $quantiteCaisses,
        float $quantiteCaissesReglee,
        float $quantiteEmballages,
        float $quantiteEmballagesReglee
    ): string {
        $resteMontant = max(0, $montant - $montantPaye);
        $resteCaisses = max(0, $quantiteCaisses - $quantiteCaissesReglee);
        $resteEmballages = max(0, $quantiteEmballages - $quantiteEmballagesReglee);

        if ($resteMontant <= 0.01 && $resteCaisses <= 0.0001 && $resteEmballages <= 0.0001) {
            return 'paye';
        }

        if ($montantPaye > 0 || $quantiteCaissesReglee > 0 || $quantiteEmballagesReglee > 0) {
            return 'partiel';
        }

        return 'ouvert';
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

        $headers = ['Date', 'Agent', 'Produit', 'Caisses dues', 'Caisses réglées', 'Reste caisses', 'Emballages dus', 'Emballages réglés', 'Reste emballages', 'Montant', 'Payé', 'Reste montant', 'Statut', 'Motif'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['date_manquant'],
                $r['agent_nom'],
                $r['produit_nom'] ?: '-',
                (float) $r['quantite_caisses'],
                (float) ($r['quantite_caisses_reglee'] ?? 0),
                (float) ($r['reste_caisses'] ?? 0),
                (float) ($r['quantite_emballages'] ?? 0),
                (float) ($r['quantite_emballages_reglee'] ?? 0),
                (float) ($r['reste_emballages'] ?? 0),
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
