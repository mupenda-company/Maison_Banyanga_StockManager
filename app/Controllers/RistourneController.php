<?php
/**
 * Contrôleur des Ristournes
 */

class RistourneController extends Controller
{
    private $ristourneModel;
    private $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->ristourneModel = new Ristourne();
        $this->clientModel = new Client();
    }

    /**
     * Liste des ristournes calculées
     */
    public function index()
    {
        $this->requirePermission('admin.voir');
        
        $filters = [
            'mois' => $_GET['mois'] ?? date('n'),
            'annee' => $_GET['annee'] ?? date('Y'),
            'client_id' => $_GET['client_id'] ?? null
        ];

        $ristournes = $this->ristourneModel->getAllWithDetails($filters);
        $clients = $this->clientModel->all();
        $printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($ristournes, $filters);
            return;
        }

        if ($printMode) {
            $this->view('ristournes/print', [
                'ristournes' => $ristournes,
                'filters' => $filters
            ]);
            return;
        }
        
        $this->view('ristournes/index', [
            'ristournes' => $ristournes,
            'clients' => $clients,
            'filters' => $filters,
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
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportExcel($ristournes, $filters)
    {
        $this->requireAuth();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Ristournes');

        $headers = ['Client', 'Periode', 'Chiffre affaires', 'Taux (%)', 'Montant ristourne', 'Statut', 'Date paiement'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($ristournes as $r) {
            $sheet->fromArray([
                $r['client_nom'] ?? '',
                !empty($r['periode_debut']) ? date('m/Y', strtotime($r['periode_debut'])) : '',
                (float)($r['ca_total'] ?? 0),
                (float)($r['taux_applique'] ?? 0),
                (float)($r['montant_ristourne'] ?? 0),
                $r['statut'] ?? '',
                !empty($r['date_paiement']) ? date('d/m/Y H:i', strtotime($r['date_paiement'])) : '',
            ], null, 'A' . $row++);
        }

        $this->styleHeaderRow($sheet, count($headers));

        $mois   = $filters['mois']  ?? date('n');
        $annee  = $filters['annee'] ?? date('Y');
        $this->sendXlsx($spreadsheet, 'ristournes_' . $mois . '_' . $annee . '_' . date('Y-m-d_H-i') . '.xlsx');
    }

    /**
     * Lancer le calcul des ristournes pour un mois donné
     */
    public function calculer()
    {
        $this->requirePermission('admin.voir');
        
        $mois = $_GET['mois'] ?? date('n');
        $annee = $_GET['annee'] ?? date('Y');

        $clients = $this->clientModel->all();
        $nbCalcules = 0;

        foreach ($clients as $client) {
            $calcul = $this->ristourneModel->calculerRistourne($client['id'], $mois, $annee);
            
            if ($calcul) {
                // Vérifier si déjà calculé pour éviter les doublons
                $existe = $this->db->fetch(
                    "SELECT id FROM ristournes WHERE client_id = :cid AND periode_debut = :debut AND statut != 'annulee'",
                    ['cid' => $client['id'], 'debut' => $calcul['periode_debut']]
                );

                if (!$existe) {
                    $this->ristourneModel->create([
                        'client_id' => $calcul['client_id'],
                        'periode_debut' => $calcul['periode_debut'],
                        'periode_fin' => $calcul['periode_fin'],
                        'ca_total' => $calcul['ca_total'],
                        'palier_id' => $calcul['palier_id'],
                        'taux_applique' => $calcul['taux_applique'],
                        'montant_ristourne' => $calcul['montant_ristourne'],
                        'statut' => 'calculee'
                    ]);
                    $nbCalcules++;
                }
            }
        }

        return $this->success(null, "$nbCalcules ristournes ont été générées pour la période.");
    }

    /**
     * Marquer une ristourne comme payée
     */
    public function payer($id)
    {
        $this->requirePermission('admin.voir');
        
        $result = $this->ristourneModel->marquerPayee($id);
        
        if ($result) {
            return $this->success(null, "Ristourne marquée comme payée.");
        }
        
        return $this->error("Erreur lors de la mise à jour.");
    }

    /**
     * Gestion des paliers
     */
    public function paliers()
    {
        $this->requirePermission('admin.voir');
        $paliers = $this->ristourneModel->getPaliers();
        $this->view('ristournes/paliers', ['paliers' => $paliers]);
    }

    /**
     * Créer/Mettre à jour un palier
     */
    public function storePalier()
    {
        $this->requirePermission('admin.voir');
        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'nom' => 'required',
            'ca_min' => 'required|numeric',
            'taux_ristourne' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $fromDevise = get_devise();
        $toDevise = get_base_devise();
        $caMinBase = convert_money((float)($data['ca_min'] ?? 0), $fromDevise, $toDevise);
        $caMaxBase = !empty($data['ca_max']) ? convert_money((float)$data['ca_max'], $fromDevise, $toDevise) : null;

        $params = [
            'nom' => $data['nom'],
            'ca_min' => $caMinBase,
            'ca_max' => $caMaxBase,
            'taux_ristourne' => $data['taux_ristourne'],
            'actif' => 1
        ];

        if (!empty($data['id'])) {
            $this->db->update('paliers_ristourne', $params, 'id = :id', ['id' => $data['id']]);
        } else {
            $this->db->insert('paliers_ristourne', $params);
        }

        return $this->success(null, 'Palier enregistré avec succès.');
    }

    /**
     * Supprimer un palier
     */
    public function deletePalier($id)
    {
        $this->requirePermission('admin.voir');
        $this->db->delete('paliers_ristourne', 'id = :id', ['id' => $id]);
        return $this->success(null, 'Palier supprimé.');
    }
}
