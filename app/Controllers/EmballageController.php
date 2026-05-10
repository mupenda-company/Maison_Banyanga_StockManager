<?php

class EmballageController extends Controller
{
    private $clientModel;
    private $retourModel;
    private $detteModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->retourModel = new RetourEmballage();
        $this->detteModel = new DetteEmballage();
    }

    public function index()
    {
        $this->requireAuth();

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        $statsRetours = $this->retourModel->getStats($dateDebut, $dateFin, 5);
        $statsDettes = $this->detteModel->getStatsGlobales();
        $retoursRecents = $this->retourModel->getRecents(8);
        $dettesEnCours = $this->detteModel->getEnCours();
        $clientsEmballage = $this->getClientsEmballageDetails($dateDebut, $dateFin);

        $this->view('emballages/index', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'statsRetours' => $statsRetours,
            'statsDettes' => $statsDettes,
            'retoursRecents' => $retoursRecents,
            'dettesEnCours' => $dettesEnCours,
            'clientsEmballage' => $clientsEmballage,
        ]);
    }

    public function suivi()
    {
        $this->requireAuth();

        $dettesEnCours = $this->detteModel->getEnCours();
        $retoursRecents = $this->retourModel->getRecents(20);
        $statsRetours = $this->retourModel->getStats(date('Y-m-01'), date('Y-m-d'), 10);
        $statsDettes = $this->detteModel->getStatsGlobales();
        $clientsEmballage = $this->getClientsEmballageDetails(date('Y-m-01'), date('Y-m-d'));

        $this->view('emballages/suivi', [
            'dettesEnCours' => $dettesEnCours,
            'retoursRecents' => $retoursRecents,
            'statsRetours' => $statsRetours,
            'statsDettes' => $statsDettes,
            'clientsEmballage' => $clientsEmballage,
        ]);
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
                    'produit_nom' => $produit['produit_nom'],
                    'produit_code' => $produit['produit_code'],
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
