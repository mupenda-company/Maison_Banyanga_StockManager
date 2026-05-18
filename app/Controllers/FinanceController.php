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
        $this->requireRole([ROLE_ADMIN]);

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
                    COALESCE(SUM(CASE WHEN statut = 'en_attente' THEN montant_ristourne ELSE 0 END), 0) as ristournes_en_attente
             FROM ristournes
             WHERE periode_debut >= :date_debut AND periode_fin <= :date_fin",
            ['date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );
        $ristourneStats = $ristournes[0] ?? ['total_ristournes' => 0, 'nb_ristournes' => 0, 'ristournes_payees' => 0, 'ristournes_en_attente' => 0];

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

        // Calcul bénéfice = CA - pertes - ristournes payées - dépenses
        $caTotal = (float) ($statsVentes['total_ttc'] ?? 0);
        $pertesValeur = (float) ($statsPertes['total_valeur'] ?? 0);
        $ristournesPayees = (float) ($ristourneStats['ristournes_payees'] ?? 0);
        $benefice = $caTotal - $pertesValeur - $ristournesPayees - $totalDepenses;

        // TVA collectée
        $tvaCollectee = (float) ($statsVentes['total_tva'] ?? 0);

        $this->view('finance/index', [
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
            'ristournesPayees' => $ristournesPayees,
            'benefice' => $benefice,
            'tvaCollectee' => $tvaCollectee,
        ]);
    }

    /**
     * API données financières (pour les graphiques)
     */
    public function apiStats()
    {
        $this->requireAuth();
        $this->requireRole([ROLE_ADMIN]);

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
