<?php
/**
 * Contrôleur des missions
 */

class MissionController extends Controller
{
    private $missionModel;
    private $vehiculeModel;
    private $produitModel;
    private $zoneModel;
    private $emplacementModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->missionModel = new Mission();
        $this->vehiculeModel = new Vehicule();
        $this->produitModel = new Produit();
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
            'vehicule_id' => $_GET['vehicule_id'] ?? null
        ];
        
        $page = (int) ($_GET['page'] ?? 1);
        
        $missions = $this->db->fetchAll(
            "SELECT m.*, v.immatriculation, u.nom as agent_nom, u.prenom as agent_prenom, z.nom as zone_nom,
                    COALESCE((SELECT SUM(COALESCE(mc.caisses_deja_dans_vehicule, 0) + COALESCE(mc.quantite_caisses, FLOOR(mc.quantite_chargee / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24))))
                              FROM mission_chargements mc
                              JOIN produits p ON mc.produit_id = p.id
                              WHERE mc.mission_id = m.id), 0) as total_caisses,
                    COALESCE((SELECT COUNT(DISTINCT v2.client_id)
                              FROM ventes v2
                              WHERE v2.mission_id = m.id AND v2.statut = 'validee'), 0) as nb_clients
             FROM missions m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN users u ON v.agent_responsable_id = u.id
             LEFT JOIN zones z ON m.zone_id = z.id
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
     * Terminer une mission
     */
    public function terminer($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_MAGASINIER]);
        
        $data = $this->getJsonInput();
        $emplacementPrincipal = $this->emplacementModel->getPrincipal();
        $mission = $this->missionModel->getWithDetails($id);

        if (empty($data) || (!isset($data['retours']) && !isset($data['vides_retournes']) && !isset($data['montant_encaisse']))) {
            return $this->error('Veuillez enregistrer les retours (pleins/vides) et le montant encaissé avant de clôturer la mission', 422);
        }

        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }
        
        // Préparer les retours de produits pleins (invendus)
        $retours = [];
        if (isset($data['retours'])) {
            foreach ($data['retours'] as $produitId => $quantite) {
                $retours[$produitId] = (int) $quantite;
            }
        }

        $retoursPleinsAttendues = 0;
        if (!empty($mission['chargements']) && is_array($mission['chargements'])) {
            foreach ($mission['chargements'] as $chargement) {
                $totalReel = (int) ($chargement['caisses_total'] ?? (($chargement['caisses_deja_dans_vehicule'] ?? 0) + ($chargement['quantite_caisses'] ?? 0)));
                $vendues = (int) ($chargement['caisses_vendues'] ?? floor(((int) ($chargement['quantite_vendue'] ?? 0)) / max((int) ($chargement['bouteilles_par_caisses'] ?? 24), 1)));
                $retoursPleinsAttendues += max($totalReel - $vendues, 0);
            }
        }

        $retoursPleinsRetournes = 0;
        foreach ($retours as $quantite) {
            $retoursPleinsRetournes += max((int) $quantite, 0);
        }
        
        // Préparer les retours de caisses vides
        $vides_retournes = [];
        if (isset($data['vides_retournes'])) {
            foreach ($data['vides_retournes'] as $produitId => $nbCaisses) {
                $vides_retournes[$produitId] = (int) $nbCaisses;
            }
        }

        $montant_attendu = (float) ($mission['montant_attendu'] ?? 0);
        $montant_encaisse = isset($data['montant_encaisse']) ? floatval($data['montant_encaisse']) : 0;
        if ($montant_encaisse <= 0 && $montant_attendu > 0) {
            $montant_encaisse = $montant_attendu;
        }

        $caissesVidesAttendues = (int) ($mission['caisses_vides_attendues'] ?? 0);
        $caissesVidesRetournees = 0;
        foreach ($vides_retournes as $nbCaisses) {
            $caissesVidesRetournees += (int) $nbCaisses;
        }

        $montantEcart = round($montant_encaisse - $montant_attendu, 2);
        $retoursPleinsEcart = $retoursPleinsAttendues - $retoursPleinsRetournes;
        $caissesEcart = $caissesVidesAttendues - $caissesVidesRetournees;
        $hasDiscrepancy = abs($montantEcart) > 0.01 || $retoursPleinsEcart !== 0 || $caissesEcart !== 0;
        $justificationCloture = trim((string) ($data['justification_cloture'] ?? ''));

        if ($hasDiscrepancy && $justificationCloture === '') {
            return $this->error(
                'Une justification est obligatoire lorsqu’il y a un écart entre le montant attendu et le montant encaissé, les retours pleins attendus et retournés, ou les caisses vides attendues et retournées.',
                422,
                [
                    'montant_ecart' => $montantEcart,
                    'retours_pleins_ecart' => $retoursPleinsEcart,
                    'caisses_vides_ecart' => $caissesEcart,
                ]
            );
        }
        
        $result = $this->missionModel->terminer(
            $id, 
            $retours, 
            $vides_retournes, 
            $montant_encaisse, 
            $emplacementPrincipal['id'],
            $justificationCloture
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
