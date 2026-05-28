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

    public function export()
    {
        $this->requireAuth();
        $this->requirePermission('finance.voir');

        $data = $this->getFinanceData();
        $filename = 'detail_finance_' . $data['dateDebut'] . '_' . $data['dateFin'] . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Detail financier']);
        fputcsv($output, ['Periode', $data['dateDebut'] . ' au ' . $data['dateFin']]);
        fputcsv($output, []);

        fputcsv($output, ['Resume', 'Valeur']);
        fputcsv($output, ["Chiffre d'affaires HT", number_format((float)($data['statsVentes']['total_ht'] ?? 0), 2, '.', '')]);
        fputcsv($output, ['TVA collectee', number_format((float)$data['tvaCollectee'], 2, '.', '')]);
        fputcsv($output, ["Chiffre d'affaires TTC", number_format((float)$data['caTotal'], 2, '.', '')]);
        fputcsv($output, ['Valeur des pertes', number_format((float)$data['pertesValeur'], 2, '.', '')]);
        fputcsv($output, ['Depenses', number_format((float)$data['totalDepenses'], 2, '.', '')]);
        fputcsv($output, ['Solde net', number_format((float)$data['benefice'], 2, '.', '')]);
        fputcsv($output, ['Recolte locale', number_format((float)$data['totalRecolteLocale'], 2, '.', '')]);
        fputcsv($output, ['Dettes emballages (caisses)', (int)($data['dettesAppro']['total_dettes'] ?? 0)]);
        fputcsv($output, ['Panier moyen', number_format((float)($data['statsVentes']['moyenne_vente'] ?? 0), 2, '.', '')]);
        fputcsv($output, []);

        $this->writeCsvSection($output, 'Evolution du CA par jour', ['Jour', 'Nombre de ventes', 'CA', 'TVA'], $data['ventesParJour'], function ($row) {
            return [$row['jour'] ?? '', $row['nb_ventes'] ?? 0, number_format((float)($row['ca_jour'] ?? 0), 2, '.', ''), number_format((float)($row['tva_jour'] ?? 0), 2, '.', '')];
        });
        $this->writeCsvSection($output, 'Ventes par zone', ['Zone', 'Ventes', 'CA'], $data['ventesParZone'], function ($row) {
            return [$row['zone_nom'] ?? '', $row['nb_ventes'] ?? 0, number_format((float)($row['total_ca'] ?? 0), 2, '.', '')];
        });
        $this->writeCsvSection($output, 'Ventes par produit', ['Produit', 'Caisses', 'CA'], $data['ventesParProduit'], function ($row) {
            return [$row['nom'] ?? '', number_format((float)($row['total_caisses'] ?? 0), 2, '.', ''), number_format((float)($row['total_vente'] ?? 0), 2, '.', '')];
        });
        $this->writeCsvSection($output, 'Top clients', ['Client', 'Numero client', 'Ventes', 'CA'], $data['topClients'], function ($row) {
            return [$row['nom'] ?? '', $row['numero_client'] ?? '', $row['nb_ventes'] ?? 0, number_format((float)($row['total_ca'] ?? 0), 2, '.', '')];
        });
        $this->writeCsvSection($output, 'Pertes par type', ['Type', 'Nombre', 'Valeur', 'Caisses'], $data['pertesParType'], function ($row) {
            return [$row['type_perte'] ?? 'Autre', $row['nb'] ?? 0, number_format((float)($row['valeur'] ?? 0), 2, '.', ''), number_format((float)($row['quantite'] ?? 0), 2, '.', '')];
        });
        $this->writeCsvSection($output, 'Depenses par categorie', ['Categorie', 'Nombre', 'Total'], $data['depensesParCategorie'], function ($row) {
            return [$row['categorie'] ?? '', $row['nb'] ?? 0, number_format((float)($row['total'] ?? 0), 2, '.', '')];
        });

        fclose($output);
        exit;
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

    private function writeCsvSection($output, $title, array $headers, array $rows, callable $mapper)
    {
        fputcsv($output, [$title]);
        fputcsv($output, $headers);

        if (empty($rows)) {
            fputcsv($output, ['Aucune donnee']);
            fputcsv($output, []);
            return;
        }

        foreach ($rows as $row) {
            fputcsv($output, $mapper($row));
        }

        fputcsv($output, []);
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
