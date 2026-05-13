<?php
/**
 * Contrôleur des missions
 */

class MissionController extends Controller
{
    private $missionModel;
    private $vehiculeModel;
    private $produitModel;
    private $clientModel;
    private $ristourneModel;
    private $zoneModel;
    private $emplacementModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->missionModel = new Mission();
        $this->vehiculeModel = new Vehicule();
        $this->produitModel = new Produit();
        $this->clientModel = new Client();
        $this->ristourneModel = new Ristourne();
        $this->zoneModel = new Zone();
        $this->emplacementModel = new Emplacement();
    }
    
    /**
     * Liste des missions
     */
    public function index()
    {
        $this->requireAuth();
        
        $filters = [
            'statut' => $_GET['statut'] ?? null,
            'vehicule_id' => $_GET['vehicule_id'] ?? null,
            'type_mission' => $_GET['type_mission'] ?? null
        ];

        $typeMissionFilter = in_array($filters['type_mission'], ['vente', 'ristourne'], true) ? $filters['type_mission'] : null;
        $typeMissionSql = $typeMissionFilter ? " AND COALESCE(m.type_mission, 'vente') = '" . $typeMissionFilter . "'" : '';
        
        $page = (int) ($_GET['page'] ?? 1);
        
        $missions = $this->db->fetchAll(
            "SELECT m.*, v.immatriculation, u.nom as agent_nom, u.prenom as agent_prenom, z.nom as zone_nom, c.nom as client_nom,
                    CASE
                        WHEN COALESCE(m.type_mission, 'vente') = 'ristourne' THEN COALESCE(m.montant_ristourne_initial, 0)
                        ELSE COALESCE((SELECT SUM(COALESCE(mc.quantite_caisses, 0))
                                      FROM mission_chargements mc
                                      WHERE mc.mission_id = m.id), 0)
                    END as total_caisses,
                    COALESCE((SELECT COUNT(DISTINCT v2.client_id)
                              FROM ventes v2
                              WHERE v2.mission_id = m.id AND v2.statut = 'validee' AND COALESCE(m.type_mission, 'vente') = 'vente'), 0) as nb_clients,
                    COALESCE(m.montant_ristourne_initial, 0) as montant_ristourne_initial,
                    COALESCE(m.montant_livre, 0) as montant_livre,
                    COALESCE(m.montant_restant_admin, 0) as montant_restant_admin
             FROM missions m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN zones z ON m.zone_id = z.id
             LEFT JOIN clients c ON m.client_id = c.id
             WHERE 1=1{$typeMissionSql}
             ORDER BY m.date_depart DESC
             LIMIT 20 OFFSET " . (($page - 1) * 20)
        );
        
        $vehicules = $this->vehiculeModel->getWithAgent();
        
        $this->view('missions/index', [
            'missions' => $missions,
            'vehicules' => $vehicules,
            'filters' => $filters
        ]);
    }

    /**
     * Formulaire de création d'une mission de ristourne
     */
    public function createRestourne()
    {
        $this->requireRole([ROLE_ADMIN]);

        $vehicules = $this->vehiculeModel->getDisponibles();
        $produits = $this->produitModel->getWithStock();
        $zones = $this->zoneModel->getActive();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $clients = $this->clientModel->getAllWithZone();
        $ristournes = $this->db->fetchAll(
            "SELECT r.*, c.nom as client_nom, c.numero_client
             FROM ristournes r
             JOIN clients c ON r.client_id = c.id
             WHERE r.statut = 'calculee'
             ORDER BY r.id DESC"
        );

        $this->view('missions/ristourne-create', [
            'vehicules' => $vehicules,
            'produits' => $produits,
            'zones' => $zones,
            'clients' => $clients,
            'ristournes' => $ristournes,
            'emplacementPrincipal' => $emplacementPrincipal,
            'numero_mission' => $this->missionModel->generateNumeroMission('RST')
        ]);
    }
    
    /**
     * Missions en cours
     */
    public function enCours()
    {
        $this->requireAuth();
        
        $missions = $this->missionModel->getEnCours();
        
        if ($this->isAjax()) {
            return $this->success($missions);
        }
        
        $this->view('missions/en-cours', [
            'missions' => $missions
        ]);
    }
    
    /**
     * Formulaire de création
     */
    public function create()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $vehicules = $this->vehiculeModel->getDisponibles();
        $produits = $this->produitModel->getWithStock();
        $zones = $this->zoneModel->getActive();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        
        $this->view('missions/create', [
            'vehicules' => $vehicules,
            'produits' => $produits,
            'zones' => $zones,
            'emplacementPrincipal' => $emplacementPrincipal,
            'numero_mission' => $this->missionModel->generateNumeroMission()
        ]);
    }
    
    /**
     * Enregistrer une mission
     */
    public function store()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'date_depart' => 'required',
            'chargements' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $vehicule = $this->vehiculeModel->getWithStock((int) ($data['vehicule_id'] ?? 0));
        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }

        $stockVehiculeProduits = [];
        foreach (($vehicule['stock'] ?? []) as $stock) {
            $stockVehiculeProduits[(int) ($stock['produit_id'] ?? 0)] = true;
        }

        $chargements = [];
        foreach ($data['chargements'] as $chargement) {
            $produitId = (int) ($chargement['produit_id'] ?? 0);
            $quantiteCaisses = (int) ($chargement['quantite_caisses'] ?? 0);
            $quantiteBouteilles = (int) ($chargement['quantite'] ?? 0);

            if ($produitId <= 0) {
                continue;
            }

            if ($quantiteCaisses <= 0 && $quantiteBouteilles <= 0 && empty($stockVehiculeProduits[$produitId])) {
                continue;
            }

            if (!isset($chargements[$produitId])) {
                $chargements[$produitId] = [
                    'produit_id' => $produitId,
                    'quantite_caisses' => 0,
                    'quantite_chargee' => 0
                ];
            }

            $chargements[$produitId]['quantite_caisses'] += $quantiteCaisses;
            $chargements[$produitId]['quantite_chargee'] += $quantiteBouteilles;
        }

        $chargements = array_values($chargements);

        if (empty($chargements)) {
            return $this->error('Ajoutez au moins un produit présent dans le véhicule ou une quantité à charger avant de lancer la mission.', 422);
        }

        $capaciteVehicule = (int) ($vehicule['capacite'] ?? 0);
        if ($capaciteVehicule > 0) {
            $totalMissionCaisses = 0;
            foreach ($chargements as $chargement) {
                $totalMissionCaisses += max(0, (int) ($chargement['quantite_caisses'] ?? 0));
            }

            if ($totalMissionCaisses > $capaciteVehicule) {
                return $this->error(
                    'La mission dépasse la capacité du véhicule. Capacité: ' . $capaciteVehicule . ' caisses, stock final demandé: ' . $totalMissionCaisses . ' caisses.',
                    422,
                    [
                        'capacite_vehicule' => $capaciteVehicule,
                        'stock_final_demande' => $totalMissionCaisses
                    ]
                );
            }
        }
        
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        
        $missionData = [
            'numero_mission' => $this->missionModel->generateNumeroMission(),
            'vehicule_id' => $data['vehicule_id'],
            'chauffeur_id' => $data['chauffeur_id'] ?? null,
            'date_depart' => $data['date_depart'],
            'zone_id' => $data['zone_id'] ?? null,
            'notes' => $data['notes'] ?? '',
            'statut' => 'en_cours',
            'created_by' => $_SESSION['user_id']
        ];
        
        $result = $this->missionModel->createWithChargement(
            $missionData,
            $chargements,
            $emplacementPrincipal['id']
        );
        
        if ($result['success']) {
            return $this->success(['id' => $result['id']], 'Mission créée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }

    /**
     * Enregistrer une mission de ristourne
     */
    public function storeRestourne()
    {
        $this->requireRole([ROLE_ADMIN]);

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'date_depart' => 'required',
            'client_id' => 'required|numeric',
            'ristourne_id' => 'required|numeric',
            'produit_id' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $ristourne = $this->db->fetch(
            "SELECT r.*, c.nom as client_nom, c.zone_id
             FROM ristournes r
             JOIN clients c ON r.client_id = c.id
             WHERE r.id = :id AND r.client_id = :client_id",
            [
                'id' => (int) $data['ristourne_id'],
                'client_id' => (int) $data['client_id']
            ]
        );

        if (!$ristourne) {
            return $this->error('Ristourne introuvable pour ce client', 404);
        }

        $vehicule = $this->vehiculeModel->getWithStock((int) ($data['vehicule_id'] ?? 0));
        if (!$vehicule) {
            return $this->error('Véhicule non trouvé', 404);
        }

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();

        $missionData = [
            'numero_mission' => $this->missionModel->generateNumeroMission('RST'),
            'vehicule_id' => $data['vehicule_id'],
            'chauffeur_id' => $data['chauffeur_id'] ?? null,
            'client_id' => $data['client_id'],
            'ristourne_id' => $data['ristourne_id'],
            'date_depart' => $data['date_depart'],
            'zone_id' => $data['zone_id'] ?? ($ristourne['zone_id'] ?? null),
            'notes' => $data['notes'] ?? '',
            'statut' => 'en_cours',
            'montant_ristourne_initial' => (float) ($data['montant_ristourne_initial'] ?? $ristourne['montant_ristourne'] ?? 0),
            'created_by' => $_SESSION['user_id']
        ];

        $result = $this->missionModel->createWithRestourne(
            $missionData,
            [
                'produit_id' => (int) $data['produit_id']
            ],
            $emplacementPrincipal['id']
        );

        if ($result['success']) {
            return $this->success([
                'id' => $result['id'],
                'caisses_livrees' => $result['caisses_livrees'] ?? 0,
                'montant_livre' => $result['montant_livre'] ?? 0,
                'montant_restant_admin' => $result['montant_restant_admin'] ?? 0
            ], 'Mission de ristourne créée avec succès');
        }

        return $this->error($result['message'], 400);
    }
    
    /**
     * Afficher une mission
     */
    public function show($id)
    {
        $this->requireAuth();
        
        $mission = $this->missionModel->getWithDetails($id);
        
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }
        
        if ($this->isAjax()) {
            return $this->success($mission);
        }
        
        $this->view('missions/show', [
            'mission' => $mission
        ]);
    }

    /**
     * Formulaire de modification d'une mission
     */
    public function edit($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);

        $mission = $this->missionModel->getWithDetails($id);
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        if (($mission['statut'] ?? '') !== 'en_cours') {
            return $this->error('Seules les missions en cours peuvent être modifiées', 422);
        }

        $vehiculesDisponibles = $this->vehiculeModel->getDisponibles();
        $vehiculeCourant = $this->vehiculeModel->getWithStock((int) ($mission['vehicule_id'] ?? 0));
        if ($vehiculeCourant) {
            $vehiculeCourant['stock_plein'] = 0;
            $vehiculeCourant['stock_vide'] = 0;
            $vehiculeCourant['stock_caisses_pleine'] = 0;
            $vehiculeCourant['stock_caisses_vide'] = 0;
            foreach (($vehiculeCourant['stock'] ?? []) as $stock) {
                $vehiculeCourant['stock_plein'] += (int) round((float) ($stock['quantite_pleine'] ?? 0));
                $vehiculeCourant['stock_vide'] += (int) round((float) ($stock['quantite_vide'] ?? 0));
                $vehiculeCourant['stock_caisses_pleine'] += (int) round((float) ($stock['caisses_pleine'] ?? 0));
                $vehiculeCourant['stock_caisses_vide'] += (int) round((float) ($stock['caisses_vide'] ?? 0));
            }

            $vehiculesParId = [];
            foreach ($vehiculesDisponibles as $vehicule) {
                $vehiculesParId[(int) ($vehicule['id'] ?? 0)] = $vehicule;
            }
            $vehiculesParId[(int) $vehiculeCourant['id']] = $vehiculeCourant;
            $vehiculesDisponibles = array_values($vehiculesParId);

            usort($vehiculesDisponibles, static function ($a, $b) {
                return strcmp((string) ($a['immatriculation'] ?? ''), (string) ($b['immatriculation'] ?? ''));
            });
        }

        $this->view('missions/edit', [
            'mission' => $mission,
            'vehicules' => $vehiculesDisponibles,
            'produits' => $this->produitModel->getWithStock(),
            'zones' => $this->zoneModel->getActive(),
            'emplacementPrincipal' => $this->emplacementModel->getPrincipal(),
        ]);
    }

    /**
     * Mettre à jour une mission
     */
    public function update($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);

        $mission = $this->missionModel->getWithDetails($id);
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        $data = $this->getJsonInput();
        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'date_depart' => 'required',
            'chargements' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $chargements = [];
        if (isset($data['chargements']) && is_array($data['chargements'])) {
            foreach ($data['chargements'] as $chargement) {
                $produitId = (int) ($chargement['produit_id'] ?? 0);
                if ($produitId <= 0) {
                    continue;
                }

                $chargements[] = [
                    'produit_id' => $produitId,
                    'quantite_caisses' => (int) ($chargement['quantite_caisses'] ?? 0),
                    'quantite' => (int) ($chargement['quantite'] ?? 0),
                    'stock_depart_caisses' => (int) ($chargement['stock_depart_caisses'] ?? 0),
                    'stock_depart_bouteilles' => (int) ($chargement['stock_depart_bouteilles'] ?? 0),
                ];
            }
        }

        if (empty($chargements)) {
            return $this->error('Ajoutez au moins un produit présent dans le véhicule ou une quantité à charger avant d’enregistrer la modification.', 422);
        }

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $result = $this->missionModel->updateWithChargement(
            $id,
            $data,
            $chargements,
            $emplacementPrincipal['id']
        );

        if ($result['success']) {
            return $this->success(['id' => $id], 'Mission modifiée avec succès');
        }

        return $this->error($result['message'], 400);
    }
    
    /**
     * Terminer une mission
     */
    public function terminer($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);

        $data = $this->getJsonInput();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $mission = $this->missionModel->getWithDetails($id);

        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        $retours = [];
        if (isset($data['retours']) && is_array($data['retours'])) {
            foreach ($data['retours'] as $produitId => $quantite) {
                $retours[$produitId] = (int) $quantite;
            }
        }

        $vides_retournes = [];
        if (isset($data['vides_retournes']) && is_array($data['vides_retournes'])) {
            foreach ($data['vides_retournes'] as $produitId => $nbCaisses) {
                $vides_retournes[$produitId] = (int) $nbCaisses;
            }
        }

        $montant_attendu = (float) ($mission['montant_attendu'] ?? 0);
        $montant_encaisse = isset($data['montant_encaisse']) ? (float) $data['montant_encaisse'] : 0;
        if ($montant_encaisse <= 0 && $montant_attendu > 0) {
            $montant_encaisse = $montant_attendu;
        }

        $result = $this->missionModel->terminer(
            $id,
            $retours,
            $vides_retournes,
            $montant_encaisse,
            $emplacementPrincipal['id'],
            $data['justification_cloture'] ?? null
        );

        if ($result['success']) {
            return $this->success(null, 'Mission terminée et stock mis à jour avec succès');
        }

        return $this->error($result['message'], 400);
    }
    
    /**
     * Imprimer le bon de sortie
     */
    public function print($id)
    {
        $this->requireAuth();
        
        $mission = $this->missionModel->getWithDetails($id);
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        $params = (new Parametre())->getPersonnalisation();
        
        $this->view('missions/bon-sortie', [
            'mission' => $mission,
            'params' => $params
        ]);
    }

    /**
     * Imprimer la facture de fin de mission
     */
    public function facture($id)
    {
        $this->requireAuth();

        $mission = $this->missionModel->getWithDetails($id);
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        $params = (new Parametre())->getPersonnalisation();

        $this->view('missions/facture', [
            'mission' => $mission,
            'params' => $params
        ]);
    }
}
