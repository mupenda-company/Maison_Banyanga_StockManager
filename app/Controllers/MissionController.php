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
        $this->requirePermission('missions.voir');
        
        $filters = [
            'statut' => in_array($_GET['statut'] ?? '', ['en_cours', 'terminee', 'annulee'], true) ? $_GET['statut'] : null,
            'vehicule_id' => (int) ($_GET['vehicule_id'] ?? 0),
            'type_mission' => in_array($_GET['type_mission'] ?? '', ['vente', 'ristourne'], true) ? $_GET['type_mission'] : null,
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : null,
        ];

        $conditions = ["1=1"];
        $params = [];

        if ($filters['type_mission']) {
            $conditions[] = "COALESCE(m.type_mission, 'vente') = :type_mission";
            $params['type_mission'] = $filters['type_mission'];
        }

        if ($filters['statut']) {
            $conditions[] = "m.statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        if ($filters['vehicule_id'] > 0) {
            $conditions[] = "m.vehicule_id = :vehicule_id";
            $params['vehicule_id'] = $filters['vehicule_id'];
        }

        if ($filters['date']) {
            $conditions[] = "DATE(m.date_depart) = :date_depart";
            $params['date_depart'] = $filters['date'];
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        
        $missions = $this->db->fetchAll(
            "SELECT m.*, v.immatriculation, u.nom as agent_nom, u.prenom as agent_prenom, z.nom as zone_nom, c.nom as client_nom,
                    -- total_caisses should represent actual delivered/loading caisses
                    COALESCE((SELECT SUM(COALESCE(mc.quantite_caisses, 0)) FROM mission_chargements mc WHERE mc.mission_id = m.id), 0)
                    + COALESCE((SELECT SUM(COALESCE(mr.caisses_livrees, 0)) FROM mission_ristournes mr WHERE mr.mission_id = m.id), 0)
                    as total_caisses,
                    COALESCE((SELECT COUNT(DISTINCT v2.client_id)
                              FROM ventes v2
                              WHERE v2.mission_id = m.id AND v2.statut = 'validee' AND COALESCE(m.type_mission, 'vente') = 'vente'), 0) as nb_clients,
                    COALESCE(m.montant_ristourne_initial, 0) as montant_ristourne_initial,
                    COALESCE(m.montant_livre, 0) as montant_livre
             FROM missions m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN zones z ON m.zone_id = z.id
             LEFT JOIN clients c ON m.client_id = c.id
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY m.date_depart DESC
             LIMIT 20 OFFSET " . (($page - 1) * 20)
            , $params
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
        $this->requirePermission('admin.voir');

        $vehicules = $this->vehiculeModel->getDisponibles();
        $produits = $this->produitModel->getWithStock();
        $zones = $this->zoneModel->getActive();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $ristournes = $this->db->fetchAll(
            "SELECT r.*, c.nom as client_nom, c.numero_client
             FROM ristournes r
             JOIN clients c ON r.client_id = c.id
             WHERE r.statut = 'calculee'
             ORDER BY c.nom ASC, r.id DESC"
        );

        $this->view('missions/ristourne-create', [
            'vehicules' => $vehicules,
            'produits' => $produits,
            'zones' => $zones,
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
        $this->requirePermission('missions.voir');
        
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
        $this->requirePermission('missions.gerer');
        
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
        $this->requirePermission('missions.gerer');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'zone_id' => 'required|numeric',
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
            $stockDepartCaisses = (int) ($chargement['stock_depart_caisses'] ?? 0);

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
                    'quantite_chargee' => 0,
                    'stock_depart_caisses' => 0
                ];
            }

            $chargements[$produitId]['quantite_caisses'] += $quantiteCaisses;
            $chargements[$produitId]['quantite_chargee'] += $quantiteBouteilles;
            $chargements[$produitId]['stock_depart_caisses'] = max(
                $chargements[$produitId]['stock_depart_caisses'],
                $stockDepartCaisses
            );
        }

        $chargements = array_values($chargements);

        if (empty($chargements)) {
            return $this->error('Ajoutez au moins un produit présent dans le véhicule ou une quantité à charger avant de lancer la mission.', 422);
        }

        // Vérifier le stock disponible dans l'entrepôt principal
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $stockInsuffisants = [];
        foreach ($chargements as $chargement) {
            $produitId = (int) ($chargement['produit_id'] ?? 0);
            if ($produitId <= 0) continue;

            $produit = $this->produitModel->find($produitId);
            if (!$produit) continue;

            $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($bouteillesParCaisse <= 0) $bouteillesParCaisse = 24;

            // Caisses demandées pour la mission (quantite_caisses = stock final dans le véhicule)
            $caissesDemandees = max(0, (int) ($chargement['quantite_caisses'] ?? 0));

            // Stock déjà dans le véhicule
            $caissesDejaDansVehicule = 0;
            foreach (($vehicule['stock'] ?? []) as $vs) {
                if ((int) ($vs['produit_id'] ?? 0) === $produitId) {
                    $caissesDejaDansVehicule = max(0, (int) ($vs['caisses_pleine'] ?? 0));
                    if ($caissesDejaDansVehicule <= 0) {
                        $caissesDejaDansVehicule = (int) floor(((int) ($vs['quantite_pleine'] ?? 0)) / $bouteillesParCaisse);
                    }
                    break;
                }
            }

            // Ce qu'on doit réellement sortir de l'entrepôt
            $caissesASortir = max(0, $caissesDemandees - $caissesDejaDansVehicule);

            if ($caissesASortir <= 0) continue;

            // Stock disponible dans l'entrepôt principal
            $stockPrincipal = $this->db->fetch(
                "SELECT COALESCE(caisses_pleine, 0) as caisses_disponibles
                 FROM stocks
                 WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id
                 LIMIT 1",
                [
                    'produit_id' => $produitId,
                    'emplacement_id' => (int) ($emplacementPrincipal['id'] ?? 0)
                ]
            );
            $caissesDisponibles = max(0, (int) ($stockPrincipal['caisses_disponibles'] ?? 0));

            if ($caissesASortir > $caissesDisponibles) {
                $stockInsuffisants[] = sprintf(
                    '%s : demandé %d cs à sortir de l\'entrepôt, disponible %d cs',
                    $produit['nom'] ?? 'Produit #' . $produitId,
                    $caissesASortir,
                    $caissesDisponibles
                );
            }
        }

        if (!empty($stockInsuffisants)) {
            return $this->error(
                'Stock insuffisant dans l\'entrepôt pour créer la mission : ' . implode(' ; ', $stockInsuffisants),
                422
            );
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
        
        $missionData = [
            'numero_mission' => $this->missionModel->generateNumeroMission(),
            'vehicule_id' => $data['vehicule_id'],
            'chauffeur_id' => $data['chauffeur_id'] ?? null,
            'date_depart' => $data['date_depart'],
            'zone_id' => $data['zone_id'] ?? null,
            'notes' => $data['notes'] ?? '',
            'statut' => 'en_cours',
            'type_mission' => 'vente',
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
     * Enregistrer une mission de ristourne (plusieurs ristournes)
     */
    public function storeRestourne()
    {
        $this->requirePermission('admin.voir');

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'date_depart' => 'required',
            'ristournes' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $ristournesData = $data['ristournes'] ?? [];
        if (empty($ristournesData) || !is_array($ristournesData)) {
            return $this->error('Sélectionnez au moins une ristourne à livrer', 422);
        }

        // Valider chaque ristourne
        foreach ($ristournesData as $i => $ristourneItem) {
            if (empty($ristourneItem['ristourne_id']) || empty($ristourneItem['produit_id'])) {
                return $this->error('Chaque ristourne doit avoir un produit sélectionné', 422);
            }
        }

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();

        $missionData = [
            'numero_mission' => $this->missionModel->generateNumeroMission('RST'),
            'vehicule_id' => $data['vehicule_id'],
            'chauffeur_id' => $data['chauffeur_id'] ?? null,
            'date_depart' => $data['date_depart'],
            'zone_id' => $data['zone_id'] ?? null,
            'notes' => $data['notes'] ?? '',
            'created_by' => $_SESSION['user_id']
        ];

        $result = $this->missionModel->createWithMultipleRestournes(
            $missionData,
            $ristournesData,
            $emplacementPrincipal['id']
        );

        if ($result['success']) {
            return $this->success([
                'id' => $result['id'],
                'nb_ristournes' => $result['nb_ristournes'] ?? 0,
                'montant_livre' => $result['montant_livre'] ?? 0
            ], 'Mission de ristourne créée avec succès (' . ($result['nb_ristournes'] ?? 0) . ' ristournes)');
        }

        return $this->error($result['message'], 400);
    }
    
    /**
     * Afficher une mission
     */
    public function show($id)
    {
        $this->requirePermission('missions.voir');
        
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
        $this->requirePermission('missions.gerer');

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
        $this->requirePermission('missions.gerer');

        $mission = $this->missionModel->getWithDetails($id);
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        $data = $this->getJsonInput();
        $errors = $this->validate($data, [
            'vehicule_id' => 'required|numeric',
            'zone_id' => 'required|numeric',
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
            return $this->error('Ajoutez au moins un produit présent dans le véhicule ou une quantité à charger avant d\'enregistrer la modification.', 422);
        }

        // Vérifier le stock disponible dans l'entrepôt principal pour les ajouts
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $vehicule = $this->vehiculeModel->getWithStock((int) ($data['vehicule_id'] ?? 0));
        $stockInsuffisants = [];
        foreach ($chargements as $chargement) {
            $produitId = (int) ($chargement['produit_id'] ?? 0);
            if ($produitId <= 0) continue;

            $produit = $this->produitModel->find($produitId);
            if (!$produit) continue;

            $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
            if ($bouteillesParCaisse <= 0) $bouteillesParCaisse = 24;

            $caissesDemandees = max(0, (int) ($chargement['quantite_caisses'] ?? 0));

            // Stock déjà dans le véhicule
            $caissesDejaDansVehicule = 0;
            if ($vehicule) {
                foreach (($vehicule['stock'] ?? []) as $vs) {
                    if ((int) ($vs['produit_id'] ?? 0) === $produitId) {
                        $caissesDejaDansVehicule = max(0, (int) ($vs['caisses_pleine'] ?? 0));
                        if ($caissesDejaDansVehicule <= 0) {
                            $caissesDejaDansVehicule = (int) floor(((int) ($vs['quantite_pleine'] ?? 0)) / $bouteillesParCaisse);
                        }
                        break;
                    }
                }
            }

            $caissesASortir = max(0, $caissesDemandees - $caissesDejaDansVehicule);
            if ($caissesASortir <= 0) continue;

            $stockPrincipal = $this->db->fetch(
                "SELECT COALESCE(caisses_pleine, 0) as caisses_disponibles
                 FROM stocks
                 WHERE produit_id = :produit_id AND emplacement_id = :emplacement_id
                 LIMIT 1",
                [
                    'produit_id' => $produitId,
                    'emplacement_id' => (int) ($emplacementPrincipal['id'] ?? 0)
                ]
            );
            $caissesDisponibles = max(0, (int) ($stockPrincipal['caisses_disponibles'] ?? 0));

            if ($caissesASortir > $caissesDisponibles) {
                $stockInsuffisants[] = sprintf(
                    '%s : demandé %d cs à sortir de l\'entrepôt, disponible %d cs',
                    $produit['nom'] ?? 'Produit #' . $produitId,
                    $caissesASortir,
                    $caissesDisponibles
                );
            }
        }

        if (!empty($stockInsuffisants)) {
            return $this->error(
                'Stock insuffisant dans l\'entrepôt pour modifier la mission : ' . implode(' ; ', $stockInsuffisants),
                422
            );
        }

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
        $this->requirePermission('missions.gerer');

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
     * Annuler une mission en cours
     */
    public function annuler($id)
    {
        $this->requirePermission('missions.gerer');

        $mission = $this->missionModel->getWithDetails($id);
        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        if (($mission['statut'] ?? '') !== 'en_cours') {
            return $this->error('Seules les missions en cours peuvent être annulées', 422);
        }

        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $result = $this->missionModel->annuler($id, $emplacementPrincipal['id']);

        if ($result['success']) {
            return $this->success(null, 'Mission annulée avec succès');
        }

        return $this->error($result['message'], 400);
    }
    
    /**
     * Imprimer le bon de sortie
     */
    public function print($id)
    {
        $this->requirePermission('missions.voir');
        
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
        $this->requirePermission('missions.voir');

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

    /**
     * Synthèse des missions par agent (imprimable)
     */
    public function synthese()
    {
        $this->requirePermission('missions.voir');

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        $statut = in_array($_GET['statut'] ?? '', ['en_cours', 'terminee'], true) ? $_GET['statut'] : null;
        $printMode = isset($_GET['print']) && (string)$_GET['print'] === '1';

        $conditions = ["m.date_depart BETWEEN :date_debut AND :date_fin"];
        $params = [
            'date_debut' => $dateDebut . ' 00:00:00',
            'date_fin' => $dateFin . ' 23:59:59'
        ];

        if ($statut) {
            $conditions[] = "m.statut = :statut";
            $params['statut'] = $statut;
        }

        // Récupérer les IDs des missions de la période
        $missionRows = $this->db->fetchAll(
            "SELECT m.id, CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) as agent_nom
             FROM missions m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY u.nom, u.prenom, m.date_depart DESC",
            $params
        );

        // Charger les détails complets de chaque mission
        $missionsByAgent = [];
        $totauxGeneraux = [
            'nb_missions' => 0,
            'total_caisses' => 0,
            'total_clients' => 0,
            'total_attendu' => 0,
            'total_encaisse' => 0
        ];

        foreach ($missionRows as $row) {
            $mission = $this->missionModel->getWithDetails($row['id']);
            if (!$mission) continue;

            $agent = trim($row['agent_nom']) ?: 'Non assigné';
            if (!isset($missionsByAgent[$agent])) {
                $missionsByAgent[$agent] = [
                    'agent' => $agent,
                    'missions' => [],
                    'nb_missions' => 0,
                    'total_caisses' => 0,
                    'total_clients' => 0,
                    'total_attendu' => 0,
                    'total_encaisse' => 0
                ];
            }

            $montantAttendu = (float)($mission['montant_attendu'] ?? 0);
            $montantEncaisse = (float)($mission['montant_encaisse'] ?? 0);
            $nbClients = count($mission['clients'] ?? []);
            $totalCaisses = (int)($mission['total_caisses'] ?? 0);

            $mission['montant_attendu'] = $montantAttendu;
            $mission['nb_clients'] = $nbClients;

            $missionsByAgent[$agent]['missions'][] = $mission;
            $missionsByAgent[$agent]['nb_missions']++;
            $missionsByAgent[$agent]['total_caisses'] += $totalCaisses;
            $missionsByAgent[$agent]['total_clients'] += $nbClients;
            $missionsByAgent[$agent]['total_attendu'] += $montantAttendu;
            $missionsByAgent[$agent]['total_encaisse'] += $montantEncaisse;

            $totauxGeneraux['nb_missions']++;
            $totauxGeneraux['total_caisses'] += $totalCaisses;
            $totauxGeneraux['total_clients'] += $nbClients;
            $totauxGeneraux['total_attendu'] += $montantAttendu;
            $totauxGeneraux['total_encaisse'] += $montantEncaisse;
        }

        $synthese = array_values($missionsByAgent);

        $params = (new Parametre())->getPersonnalisation();

        $this->view('missions/synthese', [
            'synthese' => $synthese,
            'totauxGeneraux' => $totauxGeneraux,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'statut' => $statut,
            'params' => $params,
            'printMode' => $printMode
        ]);
    }
}
