<?php

class EmballageController extends Controller
{
    private $clientModel;
    private $retourModel;
    private $detteModel;
    private $emplacementModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->retourModel = new RetourEmballage();
        $this->detteModel = new DetteEmballage();
        $this->emplacementModel = new Emplacement();
    }

    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('emballages.voir');

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        $statsRetours = $this->retourModel->getStats($dateDebut, $dateFin, 5);
        $statsDettes = $this->detteModel->getStatsGlobales();
        $retoursRecents = $this->retourModel->getRecents(8);
        $dettesEnCours = $this->detteModel->getEnCours();
        $clientsEmballage = $this->getClientsEmballageDetails($dateDebut, $dateFin);
        $emplacements = $this->emplacementModel->getFixes();
        $stockEmballages = $this->getStockEmballages();
        $resumeEmprunts = $this->getResumeEmprunts();

        $this->view('emballages/index', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'statsRetours' => $statsRetours,
            'statsDettes' => $statsDettes,
            'retoursRecents' => $retoursRecents,
            'dettesEnCours' => $dettesEnCours,
            'clientsEmballage' => $clientsEmballage,
            'emplacements' => $emplacements,
            'stockEmballages' => $stockEmballages,
            'resumeEmprunts' => $resumeEmprunts,
        ]);
    }

    public function suivi()
    {
        $this->requireAuth();
        $this->requirePermission('emballages.voir');

        $dettesEnCours = $this->detteModel->getEnCours();
        $retoursRecents = $this->retourModel->getRecents(20);
        $statsRetours = $this->retourModel->getStats(date('Y-m-01'), date('Y-m-d'), 10);
        $statsDettes = $this->detteModel->getStatsGlobales();
        $clientsEmballage = $this->getClientsEmballageDetails(date('Y-m-01'), date('Y-m-d'));
        $emplacements = $this->emplacementModel->getFixes();

        $this->view('emballages/suivi', [
            'dettesEnCours' => $dettesEnCours,
            'retoursRecents' => $retoursRecents,
            'statsRetours' => $statsRetours,
            'statsDettes' => $statsDettes,
            'clientsEmballage' => $clientsEmballage,
            'emplacements' => $emplacements,
        ]);
    }

    private function getStockEmballages(): array
    {
        $parProduit = $this->db->fetchAll(
            "SELECT p.id AS produit_id, p.nom AS produit_nom, p.code AS produit_code,
                    COALESCE(SUM(s.caisses_vide), 0) AS total_caisses,
                    COALESCE(SUM(CASE WHEN emp.type = 'fixe' THEN s.caisses_vide ELSE 0 END), 0) AS fixe_caisses,
                    COALESCE(SUM(CASE WHEN emp.type = 'mobile' THEN s.caisses_vide ELSE 0 END), 0) AS mobile_caisses
             FROM produits p
             LEFT JOIN stocks s ON s.produit_id = p.id
             LEFT JOIN emplacements emp ON emp.id = s.emplacement_id
             WHERE p.actif = 1
             GROUP BY p.id, p.nom, p.code
             ORDER BY p.nom"
        );

        $parEmplacement = $this->db->fetchAll(
            "SELECT emp.id AS emplacement_id, emp.nom AS emplacement_nom, emp.type AS emplacement_type,
                    COALESCE(SUM(s.caisses_vide), 0) AS total_caisses
             FROM emplacements emp
             LEFT JOIN stocks s ON s.emplacement_id = emp.id
             WHERE emp.actif = 1
             GROUP BY emp.id, emp.nom, emp.type
             ORDER BY emp.type, emp.nom"
        );

        $total = 0;
        $fixe = 0;
        $mobile = 0;
        foreach ($parProduit as $ligne) {
            $total += (int) $ligne['total_caisses'];
            $fixe += (int) $ligne['fixe_caisses'];
            $mobile += (int) $ligne['mobile_caisses'];
        }

        return [
            'total_caisses' => $total,
            'fixe_caisses' => $fixe,
            'mobile_caisses' => $mobile,
            'par_produit' => $parProduit,
            'par_emplacement' => $parEmplacement,
        ];
    }

    private function getResumeEmprunts(): array
    {
        new EmpruntEmballage();
        $rows = $this->db->fetchAll(
            "SELECT direction, type_stock, COUNT(*) AS nb_operations,
                    COALESCE(SUM(GREATEST(quantite_empruntee - quantite_utilisee - quantite_retournee, 0)), 0) AS reste_caisses
             FROM emprunts_emballages
             WHERE statut = 'en_cours'
             GROUP BY direction, type_stock"
        );

        $resume = [
            'nb_en_cours' => 0,
            'recu_vide' => 0,
            'donne_vide' => 0,
            'recu_plein' => 0,
            'donne_plein' => 0,
        ];

        foreach ($rows as $row) {
            $key = ($row['direction'] ?? 'recu') . '_' . ($row['type_stock'] ?? 'vide');
            if (array_key_exists($key, $resume)) {
                $resume[$key] = (int) $row['reste_caisses'];
            }
            $resume['nb_en_cours'] += (int) $row['nb_operations'];
        }

        return $resume;
    }

    private function getClientsEmballageDetails($dateDebut = null, $dateFin = null)
    {
        $clients = $this->clientModel->getAllWithZone();
        $lignes = [];
        $totalCaissesVendues = 0;
        $totalCaissesVidesRecues = 0;
        $totalCaissesRetournees = 0;
        $totalDette = 0;

        foreach ($clients as $client) {
            $stats = $this->clientModel->getEmballageStats($client['id'], $dateDebut, $dateFin);

            foreach (($stats['produits'] ?? []) as $produit) {
                $dette = (int) ($produit['dette_caisses'] ?? 0);
                if ($dette <= 0) {
                    continue;
                }

                $lignes[] = [
                    'client_id' => (int) $client['id'],
                    'client_nom' => $client['nom'],
                    'zone_nom' => $client['zone_nom'] ?? 'N/A',
                    'produit_id' => (int) $produit['produit_id'],
                    'produit_nom' => $produit['produit_nom'],
                    'produit_code' => $produit['produit_code'],
                    'bouteilles_par_caisses' => (int) ($produit['bouteilles_par_caisses'] ?? 24),
                    'caisses_vendues' => (int) ($produit['caisses_vendues'] ?? 0),
                    'caisses_vides_recues' => (int) ($produit['caisses_vides_recues'] ?? 0),
                    'caisses_retournees' => (int) ($produit['caisses_retournees'] ?? 0),
                    'dette_caisses' => $dette,
                ];

                $totalCaissesVendues += (int) ($produit['caisses_vendues'] ?? 0);
                $totalCaissesVidesRecues += (int) ($produit['caisses_vides_recues'] ?? 0);
                $totalCaissesRetournees += (int) ($produit['caisses_retournees'] ?? 0);
                $totalDette += $dette;
            }
        }

        usort($lignes, function ($a, $b) {
            return $b['dette_caisses'] <=> $a['dette_caisses'];
        });

        return [
            'lignes' => $lignes,
            'total_caisses_vendues' => $totalCaissesVendues,
            'total_caisses_vides_recues' => $totalCaissesVidesRecues,
            'total_caisses_retournees' => $totalCaissesRetournees,
            'total_dette' => $totalDette,
        ];
    }
}