<?php
/**
 * Contrôleur Finance - Tableau de bord financier
 */

class FinanceController extends Controller
{
    private $venteModel;
    private $perteModel;
    private $approvisionnementModel;
    private $depenseModel;

    public function __construct()
    {
        parent::__construct();
        $this->venteModel = new Vente();
        $this->perteModel = new Perte();
        $this->approvisionnementModel = new Approvisionnement();
        $this->depenseModel = new Depense();
    }

    /**
     * Tableau de bord financier
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('finance.voir');

        $data = $this->getFinanceData();
        $this->view('finance/index', $data);
    }

    public function print()
    {
        $this->requireAuth();
        $this->requirePermission('finance.voir');

        $data = $this->getFinanceData();
        $this->view('finance/print', $data);
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
        $this->requireAuth();
        $this->requirePermission('finance.voir');

        $data = $this->getFinanceData();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Finance');

        $sheet->fromArray(['Detail financier'], null, 'A1');
        $sheet->fromArray(['Periode', $data['dateDebut'] . ' au ' . $data['dateFin']], null, 'A2');

        // Résumé
        $sheet->fromArray(['Resume', 'Valeur'], null, 'A4');
        $resume = [
            ["Chiffre d'affaires HT",  (float)($data['statsVentes']['total_ht'] ?? 0)],
            ['TVA collectee',           (float) $data['tvaCollectee']],
            ["Chiffre d'affaires TTC",  (float) $data['caTotal']],
            ['Valeur des pertes',       (float) $data['pertesValeur']],
            ['Depenses',                (float) $data['totalDepenses']],
            ['Solde net',               (float) $data['benefice']],
            ['Dettes emballages (caisses)', (int)($data['dettesAppro']['total_dettes'] ?? 0)],
            ['Panier moyen',            (float)($data['statsVentes']['moyenne_vente'] ?? 0)],
        ];
        $row = 5;
        foreach ($resume as $line) {
            $sheet->fromArray($line, null, 'A' . $row++);
        }

        // Style header résumé
        $sheet->getStyle('A4:B4')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);

        // Sections détaillées — helper local
        $addSection = function (string $titre, array $headers, array $rows, callable $mapper) use ($sheet, &$row) {
            $row++; // ligne vide
            $sheet->setCellValue('A' . $row++, $titre);
            $sheet->fromArray($headers, null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            ]);
            $row++;
            foreach ($rows as $r) {
                $sheet->fromArray($mapper($r), null, 'A' . $row++);
            }
        };

        $addSection('Evolution du CA par jour',
            ['Jour', 'Nombre de ventes', 'CA', 'TVA'],
            $data['ventesParJour'],
            fn($r) => [$r['jour'] ?? '', $r['nb_ventes'] ?? 0, (float)($r['ca_jour'] ?? 0), (float)($r['tva_jour'] ?? 0)]
        );
        $addSection('Ventes par zone',
            ['Zone', 'Ventes', 'CA'],
            $data['ventesParZone'],
            fn($r) => [$r['zone_nom'] ?? '', $r['nb_ventes'] ?? 0, (float)($r['total_ca'] ?? 0)]
        );
        $addSection('Ventes par produit',
            ['Produit', 'Caisses', 'CA'],
            $data['ventesParProduit'],
            fn($r) => [$r['nom'] ?? '', (float)($r['total_caisses'] ?? 0), (float)($r['total_vente'] ?? 0)]
        );
        $addSection('Top clients',
            ['Client', 'Numero client', 'Ventes', 'CA'],
            $data['topClients'],
            fn($r) => [$r['nom'] ?? '', $r['numero_client'] ?? '', $r['nb_ventes'] ?? 0, (float)($r['total_ca'] ?? 0)]
        );
        $addSection('Pertes par type',
            ['Type', 'Nombre', 'Valeur', 'Caisses'],
            $data['pertesParType'],
            fn($r) => [$r['type_perte'] ?? 'Autre', $r['nb'] ?? 0, (float)($r['valeur'] ?? 0), (float)($r['quantite'] ?? 0)]
        );
        $addSection('Depenses par categorie',
            ['Categorie', 'Nombre', 'Total'],
            $data['depensesParCategorie'],
            fn($r) => [$r['categorie'] ?? '', $r['nb'] ?? 0, (float)($r['total'] ?? 0)]
        );

        foreach (range(1, 8) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'detail_finance_' . $data['dateDebut'] . '_' . $data['dateFin'] . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }

    private function getFinanceData()
    {
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        $debut = $dateDebut . ' 00:00:00';
        $fin = $dateFin . ' 23:59:59';

        // Stats ventes
        $statsVentes = $this->venteModel->getStats($debut, $fin);

        // Stats pertes
        $statsPertes = $this->perteModel->getStats($dateDebut, $dateFin);

        // Ventes par produit
        $ventesParProduit = $this->venteModel->getVentesParProduit($dateDebut, $dateFin);

        // Ventes par zone
        $ventesParZone = $this->venteModel->getVentesParZone($debut, $fin);

        // Pertes par type
        $pertesParType = $this->perteModel->getByType($dateDebut, $dateFin);

        // Ristournes de la période
        $ristournes = $this->db->fetchAll(
            "SELECT COALESCE(SUM(montant_ristourne), 0) as total_ristournes,
                    COUNT(*) as nb_ristournes,
                    COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_ristourne ELSE 0 END), 0) as ristournes_payees,
                    COALESCE(SUM(CASE WHEN statut IN ('en_attente', 'calculee', 'en_livraison') THEN montant_ristourne ELSE 0 END), 0) as ristournes_en_attente
             FROM ristournes
             WHERE periode_debut >= :date_debut AND periode_fin <= :date_fin",
            ['date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );
        $ristourneStats = $ristournes[0] ?? ['total_ristournes' => 0, 'nb_ristournes' => 0, 'ristournes_payees' => 0, 'ristournes_en_attente' => 0];

        // Récolte locale (deduction locale sur ristournes de la période)
        $ristourneModel = new Ristourne();
        $clientsAvecRistourne = $this->db->fetchAll(
            "SELECT v.client_id,
                    COALESCE(SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0) as total_caisses
             FROM ventes v
             JOIN vente_details vd ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.statut = 'validee'
             AND v.date_vente BETWEEN :debut AND :fin
             GROUP BY v.client_id",
            ['debut' => $debut, 'fin' => $fin]
        );
        $totalRecolteLocale = 0;
        foreach ($clientsAvecRistourne as $cr) {
            $deduction = $ristourneModel->calculerDeductionLocale((int) $cr['total_caisses']);
            $totalRecolteLocale += $deduction['deduction_locale'];
        }

        // Dettes emballages (caisses vides)
        $dettesAppro = $this->db->fetch(
            "SELECT COALESCE(SUM(quantite_dette_caisses - quantite_remboursee), 0) as total_dettes,
                    COUNT(*) as nb_dettes
             FROM dettes_emballages
             WHERE statut = 'en_cours'"
        );

        // Ventes par jour (graphique)
        $ventesParJour = $this->db->fetchAll(
            "SELECT DATE(date_vente) as jour,
                    COUNT(*) as nb_ventes,
                    SUM(total_ttc) as ca_jour,
                    SUM(total_tva) as tva_jour
             FROM ventes
             WHERE statut = 'validee'
             AND date_vente BETWEEN :debut AND :fin
             GROUP BY DATE(date_vente)
             ORDER BY jour ASC",
            ['debut' => $debut, 'fin' => $fin]
        );

        // Top clients
        $topClients = $this->db->fetchAll(
            "SELECT c.nom, c.numero_client,
                    COUNT(v.id) as nb_ventes,
                    SUM(v.total_ttc) as total_ca
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             WHERE v.statut = 'validee'
             AND v.date_vente BETWEEN :debut AND :fin
             GROUP BY c.id
             ORDER BY total_ca DESC
             LIMIT 10",
            ['debut' => $debut, 'fin' => $fin]
        );

        // Dépenses
        $statsDepenses = $this->depenseModel->getStats($dateDebut, $dateFin);
        $depensesParCategorie = $this->depenseModel->getByCategorie($dateDebut, $dateFin);
        $totalDepenses = (float) ($statsDepenses['total_depenses'] ?? 0);

        // Calcul bénéfice = CA - pertes - dépenses
        $caTotal = (float) ($statsVentes['total_ttc'] ?? 0);
        $pertesValeur = (float) ($statsPertes['total_valeur'] ?? 0);
        $benefice = $caTotal - $pertesValeur - $totalDepenses;

        // TVA collectée
        $tvaCollectee = (float) ($statsVentes['total_tva'] ?? 0);

        return [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'statsVentes' => $statsVentes,
            'statsPertes' => $statsPertes,
            'ventesParProduit' => $ventesParProduit,
            'ventesParZone' => $ventesParZone,
            'pertesParType' => $pertesParType,
            'ristourneStats' => $ristourneStats,
            'dettesAppro' => $dettesAppro,
            'ventesParJour' => $ventesParJour,
            'topClients' => $topClients,
            'statsDepenses' => $statsDepenses,
            'depensesParCategorie' => $depensesParCategorie,
            'totalDepenses' => $totalDepenses,
            'caTotal' => $caTotal,
            'pertesValeur' => $pertesValeur,
            'benefice' => $benefice,
            'tvaCollectee' => $tvaCollectee,
            'totalRecolteLocale' => $totalRecolteLocale,
        ];
    }


    /**
     * API données financières (pour les graphiques)
     */
    public function apiStats()
    {
        $this->requireAuth();
        $this->requirePermission('finance.voir');

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        $debut = $dateDebut . ' 00:00:00';
        $fin = $dateFin . ' 23:59:59';

        $statsVentes = $this->venteModel->getStats($debut, $fin);
        $statsPertes = $this->perteModel->getStats($dateDebut, $dateFin);
        $ventesParJour = $this->db->fetchAll(
            "SELECT DATE(date_vente) as jour,
                    COUNT(*) as nb_ventes,
                    SUM(total_ttc) as ca_jour
             FROM ventes
             WHERE statut = 'validee'
             AND date_vente BETWEEN :debut AND :fin
             GROUP BY DATE(date_vente)
             ORDER BY jour ASC",
            ['debut' => $debut, 'fin' => $fin]
        );

        return $this->success([
            'statsVentes' => $statsVentes,
            'statsPertes' => $statsPertes,
            'ventesParJour' => $ventesParJour,
        ]);
    }
}
