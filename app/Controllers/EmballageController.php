<?php

class EmballageController extends Controller
{
    private $retourModel;
    private $detteModel;

    public function __construct()
    {
        parent::__construct();
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

        $this->view('emballages/index', [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'statsRetours' => $statsRetours,
            'statsDettes' => $statsDettes,
            'retoursRecents' => $retoursRecents,
            'dettesEnCours' => $dettesEnCours,
        ]);
    }

    public function suivi()
    {
        $this->requireAuth();

        $dettesEnCours = $this->detteModel->getEnCours();
        $retoursRecents = $this->retourModel->getRecents(20);
        $statsRetours = $this->retourModel->getStats(date('Y-m-01'), date('Y-m-d'), 10);
        $statsDettes = $this->detteModel->getStatsGlobales();

        $this->view('emballages/suivi', [
            'dettesEnCours' => $dettesEnCours,
            'retoursRecents' => $retoursRecents,
            'statsRetours' => $statsRetours,
            'statsDettes' => $statsDettes,
        ]);
    }
}
