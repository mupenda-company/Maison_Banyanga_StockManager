<?php

class EmpruntEmballageController extends Controller
{
    private $empruntModel;
    private $clientModel;
    private $produitModel;
    private $emplacementModel;

    public function __construct()
    {
        parent::__construct();
        $this->empruntModel = new EmpruntEmballage();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
    }

    public function index()
    {
        $this->requirePermission('emballages.voir');

        $filters = [
            'statut' => $_GET['statut'] ?? null,
            'source_type' => $_GET['source_type'] ?? null,
        ];

        $this->view('emballages/emprunts', [
            'emprunts' => $this->empruntModel->getAllWithDetails($filters),
            'clients' => $this->clientModel->all('nom'),
            'produits' => $this->produitModel->getActive(),
            'emplacements' => $this->emplacementModel->getFixes(),
            'filters' => $filters
        ]);
    }

    public function store()
    {
        $this->requirePermission('emballages.gerer');
        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'source_type' => 'required',
            'produit_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'quantite_empruntee' => 'required|numeric',
            'date_emprunt' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        if (!in_array($data['source_type'], ['client', 'externe'], true)) {
            return $this->error('Type de source invalide', 422);
        }

        if ($data['source_type'] === 'client' && empty($data['client_id'])) {
            return $this->error('Selectionnez le client preteur', 422);
        }

        if ($data['source_type'] === 'externe' && empty($data['source_nom'])) {
            return $this->error('Indiquez le nom de la personne externe', 422);
        }

        $result = $this->empruntModel->createWithStock([
            'source_type' => $data['source_type'],
            'client_id' => $data['source_type'] === 'client' ? $data['client_id'] : null,
            'source_nom' => $data['source_type'] === 'externe' ? trim($data['source_nom']) : null,
            'source_contact' => trim($data['source_contact'] ?? ''),
            'produit_id' => $data['produit_id'],
            'quantite_empruntee' => $data['quantite_empruntee'],
            'emplacement_id' => $data['emplacement_id'],
            'date_emprunt' => $data['date_emprunt'],
            'notes' => trim($data['notes'] ?? ''),
            'created_by' => $_SESSION['user_id']
        ]);

        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Emprunt enregistre avec succes');
        }

        return $this->error($result['message'], 400);
    }

    public function rembourser($id)
    {
        $this->requirePermission('emballages.gerer');
        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'quantite_caisses' => 'required|numeric',
            'emplacement_id' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $result = $this->empruntModel->rembourser(
            $id,
            $data['quantite_caisses'],
            $data['emplacement_id'],
            $_SESSION['user_id']
        );

        if ($result['success']) {
            return $this->success(['solde' => $result['solde']], $result['solde'] ? 'Emprunt rembourse et solde' : 'Remboursement enregistre');
        }

        return $this->error($result['message'], 400);
    }
}
