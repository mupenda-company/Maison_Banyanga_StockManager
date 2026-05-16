<?php
/**
 * Contrôleur des ventes
 */

class VenteController extends Controller
{
    private $venteModel;
    private $clientModel;
    private $produitModel;
    private $emplacementModel;
    private $parametreModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->venteModel = new Vente();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
        $this->emplacementModel = new Emplacement();
        $this->parametreModel = new Parametre();
    }
    
    /**
     * Liste des ventes
     */
    public function index()
    {
        $this->requireAuth();
        
        $filters = [
            'client_id' => $_GET['client_id'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null,
            'emplacement_id' => $_GET['emplacement_id'] ?? null
        ];
        
        $page = (int) ($_GET['page'] ?? 1);
        $ventes = $this->venteModel->getAllWithClient($page, 20, $filters);
        
        $clients = $this->clientModel->getAllWithZone();
        $emplacements = $this->emplacementModel->all('type, nom');
        
        $this->view('ventes/index', [
            'ventes' => $ventes,
            'clients' => $clients,
            'emplacements' => $emplacements,
            'filters' => $filters
        ]);
    }
    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requireAuth();
        
        $clients = $this->clientModel->getAllWithZone();
        $produits = $this->produitModel->getWithStock();
        $emplacements = $this->emplacementModel->getFixes();
        $tva = $this->parametreModel->get('taux_tva', 16);
        
        $this->view('ventes/create', [
            'clients' => $clients,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'tva' => $tva,
            'numero_facture' => $this->venteModel->generateNumeroFacture()
        ]);
    }
    
    /**
     * Enregistrer une vente
     */
    public function store()
    {
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'client_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'details' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $tva = $this->parametreModel->get('taux_tva', 16);
        
        $totalHt = 0;
        $details = [];
        
        foreach ($data['details'] as $index => $detail) {
            if (!is_array($detail)) {
                return $this->error('Chaque ligne de vente doit être un objet valide.', 422);
            }

            if (!array_key_exists('caisses_vides_recues', $detail) || $detail['caisses_vides_recues'] === '' || $detail['caisses_vides_recues'] === null) {
                return $this->error('Veuillez renseigner les emballages reçus pour la ligne ' . ($index + 1) . '. Indiquez 0 si aucun emballage vide n’a été récupéré.', 422);
            }

            $produit = $this->produitModel->find($detail['produit_id']);
            if (!$produit) {
                return $this->error('Produit introuvable pour la ligne ' . ($index + 1) . '.', 422);
            }

            $quantiteCaisses = max(0, (int) ($detail['quantite_caisses'] ?? round(((int) ($detail['quantite'] ?? 0)) / max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24)))));
            if ($quantiteCaisses <= 0) {
                return $this->error('La quantité de caisses doit être supérieure à 0 pour la ligne ' . ($index + 1) . '.', 422);
            }

            if (!is_numeric($detail['caisses_vides_recues'])) {
                return $this->error('Les emballages reçus doivent être un nombre valide pour la ligne ' . ($index + 1) . '.', 422);
            }

            $caissesVidesRecues = max(0, (int) ($detail['caisses_vides_recues'] ?? 0));

            if ($caissesVidesRecues > $quantiteCaisses) {
                return $this->error('Les emballages reçus ne peuvent pas dépasser le nombre de caisses vendues pour la ligne ' . ($index + 1) . '.', 422);
            }

            $prixUnitaire = $detail['prix_unitaire'] ?? $produit['prix_vente_unitaire'];
            $sousTotal = $quantiteCaisses * $prixUnitaire * ($produit['bouteilles_par_caisses'] ?? 24);
            $totalHt += $sousTotal;
            
            $details[] = [
                'produit_id' => $detail['produit_id'],
                'quantite_caisses' => $quantiteCaisses,
                'caisses_vides_recues' => $caissesVidesRecues,
                'quantite' => $quantiteCaisses * (int) ($produit['bouteilles_par_caisses'] ?? 24),
                'prix_unitaire' => $prixUnitaire,
                'sous_total' => $sousTotal
            ];
        }
        
        $totalTva = $totalHt * ($tva / 100);
        $totalTtc = $totalHt + $totalTva;
        
        $venteData = [
            'numero_facture' => $this->venteModel->generateNumeroFacture(),
            'client_id' => $data['client_id'],
            'date_vente' => date('Y-m-d H:i:s'),
            'emplacement_id' => $data['emplacement_id'],
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
            'statut' => 'validee',
            'notes' => $data['notes'] ?? '',
            'created_by' => $_SESSION['user_id']
        ];
        
        $result = $this->venteModel->createWithDetails($venteData, $details);
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Vente enregistrée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Afficher une vente
     */
    public function show($id)
    {
        $this->requireAuth();
        
        $vente = $this->venteModel->getWithDetails($id);
        
        if (!$vente) {
            return $this->error('Vente non trouvée', 404);
        }
        
        $params = $this->parametreModel->getPersonnalisation();
        
        if ($this->isAjax()) {
            return $this->success([
                'vente' => $vente,
                'params' => $params
            ]);
        }
        
        $this->view('ventes/show', [
            'vente' => $vente,
            'params' => $params
        ]);
    }
    
    /**
     * Annuler une vente
     */
    public function annuler($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $result = $this->venteModel->annuler($id);
        
        if ($result['success']) {
            return $this->success(null, 'Vente annulée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }
    
    /**
     * Imprimer la facture
     */
    public function print($id)
    {
        $this->requireAuth();
        
        $vente = $this->venteModel->getWithDetails($id);
        
        if (!$vente) {
            return $this->error('Vente non trouvée', 404);
        }
        
        $params = $this->parametreModel->getPersonnalisation();

        $totalCaissesClient = (int) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0)
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.client_id = :client_id AND v.statut = 'validee'",
            ['client_id' => $vente['client_id']]
        );

        $mois = (int) date('m', strtotime($vente['date_vente']));
        $annee = (int) date('Y', strtotime($vente['date_vente']));
        $ristourneInfo = (new Ristourne())->calculerRistourne($vente['client_id'], $mois, $annee);
        
        $this->view('ventes/facture', [
            'vente' => $vente,
            'params' => $params,
            'totalCaissesClient' => $totalCaissesClient,
            'ristourneInfo' => $ristourneInfo
        ]);
    }
    
    /**
     * Statistiques de ventes
     */
    public function stats()
    {
        $this->requireAuth();
        
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        
        $stats = $this->venteModel->getStats($dateDebut . ' 00:00:00', $dateFin . ' 23:59:59');
        $ventesParProduit = $this->venteModel->getVentesParProduit($dateDebut, $dateFin);
        
        $this->view('ventes/stats', [
            'stats' => $stats,
            'ventesParProduit' => $ventesParProduit,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }
    
    /**
     * Historique des ventes par véhicule
     */
    public function parVehicule()
    {
        $this->requireAuth();
        
        $vehiculeId = $_GET['vehicule_id'] ?? null;
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        
        $vehicules = $this->db->fetchAll("SELECT id, immatriculation FROM vehicules ORDER BY immatriculation");
        
        $ventes = [];
        $clients = [];
        $produits = [];
        
        if ($vehiculeId) {
            // Récupérer les ventes pour ce véhicule via les missions
            $ventes = $this->db->fetchAll(
                "SELECT v.id, v.numero_facture, v.date_vente, v.total_ttc, v.total_ht, v.total_tva,
                        c.nom as client_nom, c.telephone as client_telephone, c.adresse as client_adresse,
                        z.nom as zone_nom,
                        m.numero_mission
                 FROM ventes v
                 JOIN clients c ON v.client_id = c.id
                 LEFT JOIN zones z ON c.zone_id = z.id
                 LEFT JOIN missions m ON v.mission_id = m.id
                 WHERE m.vehicule_id = :vehicule_id
                 AND DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
                 AND v.statut = 'validee'
                 ORDER BY v.date_vente DESC",
                [
                    'vehicule_id' => (int) $vehiculeId,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin
                ]
            );
            
            // Récupérer les clients uniques avec leurs dettes d'emballage
            $clients = $this->db->fetchAll(
                "SELECT DISTINCT c.id, c.nom, c.telephone, c.adresse, z.nom as zone_nom,
                        COALESCE(SUM(vd.quantite_caisses - vd.caisses_vides_recues), 0) as dette_caisses
                 FROM ventes v
                 JOIN clients c ON v.client_id = c.id
                 LEFT JOIN zones z ON c.zone_id = z.id
                 LEFT JOIN missions m ON v.mission_id = m.id
                 LEFT JOIN vente_details vd ON v.id = vd.vente_id
                 WHERE m.vehicule_id = :vehicule_id
                 AND DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
                 AND v.statut = 'validee'
                 GROUP BY c.id, c.nom, c.telephone, c.adresse, z.nom
                 ORDER BY c.nom",
                [
                    'vehicule_id' => (int) $vehiculeId,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin
                ]
            );
            
            // Récupérer les produits vendus avec quantités
            $produits = $this->db->fetchAll(
                "SELECT p.id, p.nom, p.code,
                        SUM(vd.quantite_caisses) as total_caisses,
                        SUM(vd.quantite) as total_bouteilles,
                        SUM(vd.sous_total) as total_montant
                 FROM vente_details vd
                 JOIN ventes v ON vd.vente_id = v.id
                 JOIN produits p ON vd.produit_id = p.id
                 LEFT JOIN missions m ON v.mission_id = m.id
                 WHERE m.vehicule_id = :vehicule_id
                 AND DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
                 AND v.statut = 'validee'
                 GROUP BY p.id, p.nom, p.code
                 ORDER BY total_caisses DESC",
                [
                    'vehicule_id' => (int) $vehiculeId,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin
                ]
            );
        }
        
        $this->view('ventes/par_vehicule', [
            'vehicules' => $vehicules,
            'vehiculeId' => $vehiculeId,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'ventes' => $ventes,
            'clients' => $clients,
            'produits' => $produits
        ]);
    }
    
    /**
     * Imprimer l'historique des ventes par véhicule
     */
    public function printParVehicule()
    {
        $this->requireAuth();
        
        $vehiculeId = $_GET['vehicule_id'] ?? null;
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        
        if (!$vehiculeId) {
            return $this->error('Véhicule non spécifié', 400);
        }
        
        // Récupérer les informations du véhicule
        $vehicule = $this->db->fetch(
            "SELECT id, immatriculation FROM vehicules WHERE id = :id",
            ['id' => (int) $vehiculeId]
        );
        
        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }
        
        // Récupérer les ventes avec détails
        $ventes = $this->db->fetchAll(
            "SELECT v.id, v.numero_facture, v.date_vente, v.total_ttc,
                    c.id as client_id, c.nom as client_nom, c.telephone as client_telephone, c.adresse as client_adresse,
                    z.nom as zone_nom,
                    m.numero_mission
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             LEFT JOIN missions m ON v.mission_id = m.id
             WHERE m.vehicule_id = :vehicule_id
             AND DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'
             ORDER BY v.date_vente DESC",
            [
                'vehicule_id' => (int) $vehiculeId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]
        );
        
        // Récupérer les détails de chaque vente
        foreach ($ventes as &$vente) {
            $vente['details'] = $this->db->fetchAll(
                "SELECT p.nom as produit_nom, p.code as produit_code,
                        vd.quantite_caisses, vd.caisses_vides_recues,
                        (vd.quantite_caisses - vd.caisses_vides_recues) as dette_caisses,
                        vd.quantite as bouteilles,
                        vd.sous_total
                 FROM vente_details vd
                 JOIN produits p ON vd.produit_id = p.id
                 WHERE vd.vente_id = :vente_id",
                ['vente_id' => $vente['id']]
            );
        }
        
        // Récupérer les paramètres de personnalisation
        $params = $this->parametreModel->getPersonnalisation();
        
        $this->view('ventes/print_par_vehicule', [
            'vehicule' => $vehicule,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'ventes' => $ventes,
            'params' => $params
        ]);
    }
}
