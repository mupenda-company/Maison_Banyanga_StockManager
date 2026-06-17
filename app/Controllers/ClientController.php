<?php
/**
 * Contrôleur des clients
 */

class ClientController extends Controller
{
    private $clientModel;
    private $zoneModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->zoneModel = new Zone();
    }

    /**
     * Liste des clients
     */
    public function index()
    {
        $this->requirePermission('clients.voir');
        
        $search = trim((string) ($_GET['q'] ?? ''));
        $zoneId = $_GET['zone_id'] ?? null;

        if ($search !== '' || !empty($zoneId)) {
            $clients = $this->clientModel->searchWithZone($search, $zoneId);
        } else {
            $clients = $this->clientModel->getAllWithZone();
        }
        $zones = $this->zoneModel->all();
        
        $this->view('clients/index', [
            'clients' => $clients,
            'zones' => $zones,
            'search' => $search,
            'selectedZoneId' => $zoneId
        ]);
    }

    public function apiList()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        $isMobile = strpos($uri, '/api/mobile/') !== false;
        if (!$isMobile) {
            $this->requireAuth();
        }

        $actifs = ($_GET['actifs'] ?? 'true');
        $zoneId = $_GET['zone_id'] ?? null;
        $search = trim((string) ($_GET['q'] ?? ''));
        $includeInactive = $actifs === 'false' || $actifs === '0';

        if ($search !== '') {
            $clients = $this->clientModel->searchWithZone($search, $zoneId, $includeInactive);
        } elseif (!empty($zoneId)) {
            $clients = $includeInactive
                ? $this->clientModel->searchWithZone('', $zoneId, true)
                : $this->clientModel->getByZone($zoneId);
        } elseif ($includeInactive) {
            $clients = $this->db->fetchAll(
                "SELECT c.*, z.nom as zone_nom
                 FROM clients c
                 LEFT JOIN zones z ON c.zone_id = z.id
                 ORDER BY c.nom"
            );
        } else {
            $clients = $this->clientModel->getAllWithZone();
        }

        return $this->success($clients);
    }

    /**
     * Enregistrer ou mettre à jour un client
     */
    public function store()
    {
        $this->requirePermission('clients.creer');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'nom' => 'required',
            'zone_id' => 'required|numeric'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $numeroClient = trim((string) ($data['numero_client'] ?? ''));
        if ($numeroClient !== '') {
            $existingClientId = !empty($data['id']) ? (int) $data['id'] : 0;
            $duplicateSql = "SELECT COUNT(*)
                             FROM clients
                             WHERE numero_client = :numero_client";
            $duplicateParams = ['numero_client' => $numeroClient];
            if ($existingClientId > 0) {
                $duplicateSql .= " AND id <> :client_id";
                $duplicateParams['client_id'] = $existingClientId;
            }

            $duplicateCount = (int) $this->db->fetchColumn($duplicateSql, $duplicateParams);

            if ($duplicateCount > 0) {
                return $this->error('Ce numéro client existe déjà. Merci d’en choisir un autre.', 422);
            }
        }
        
        if (!empty($data['id'])) {
            $this->clientModel->update($data['id'], [
                'nom' => $data['nom'],
                'telephone' => $data['telephone'] ?? null,
                'numero_client' => $numeroClient !== '' ? $numeroClient : null,
                'adresse' => $data['adresse'] ?? null,
                'zone_id' => $data['zone_id'],
                'taux_ristourne' => isset($data['taux_ristourne']) && $data['taux_ristourne'] !== ''
                    ? (float) $data['taux_ristourne']
                    : 5
            ]);
            return $this->success(null, 'Client mis à jour avec succès');
        } else {
            $this->clientModel->create([
                'nom' => $data['nom'],
                'telephone' => $data['telephone'] ?? null,
                'numero_client' => $numeroClient !== '' ? $numeroClient : null,
                'adresse' => $data['adresse'] ?? null,
                'zone_id' => $data['zone_id'],
                'taux_ristourne' => isset($data['taux_ristourne']) && $data['taux_ristourne'] !== ''
                    ? (float) $data['taux_ristourne']
                    : 5
            ]);
            return $this->success(null, 'Client créé avec succès');
        }
    }

    /**
     * Voir les détails d'un client
     */
    public function show($id)
    {
        $this->requirePermission('clients.voir');

        $dateDebut = $_GET['date_debut'] ?? null;
        $dateFin = $_GET['date_fin'] ?? null;
        
        $client = $this->clientModel->find($id);
        if (!$client) {
            return $this->error('Client non trouvé', 404);
        }
        
        // Récupérer la zone
        $client['zone_nom'] = $this->zoneModel->find($client['zone_id'])['nom'] ?? 'N/A';
        
        // Récupérer l'historique des ventes (achats pour le client)
        $venteModel = new Vente();
        $ventesData = $venteModel->getAllWithClient(1, 100, [
            'client_id' => $id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);

        $kpis = $this->clientModel->getKpis($id, $dateDebut, $dateFin);

        $detteEmballages = (int) ($this->clientModel->getDettesEmballages($id)['total'] ?? 0);

        // Récupérer la dernière ristourne active (en attente)
        $ristourneModel = new Ristourne();
        $ristournes = $ristourneModel->getAllWithDetails(['client_id' => $id]);
        $ristourneActive = null;
        foreach ($ristournes as $r) {
            if ($r['statut'] === 'calculee') {
                // Adapter le format pour la vue qui attend 'montant_accumule'
                $ristourneActive = $r;
                $ristourneActive['montant_accumule'] = $r['montant_ristourne'] ?? 0;
                break;
            }
        }
        
        // Récupérer les dettes d'emballages
        $dettes = $this->db->fetch(
            "SELECT SUM(quantite_dette_caisses) as total 
             FROM dettes_emballages 
             WHERE produit_id IN (SELECT id FROM produits) AND statut = 'en_cours'",
            []
        );
        
        // Note: La table dettes_emballages ne semble pas avoir de client_id direct 
        // selon le schéma, on va mettre 0 par défaut pour éviter le crash
        if (!$dettes) $dettes = ['total' => 0];
        
        $this->view('clients/show', [
            'client' => $client,
            'achats' => $ventesData['data'] ?? [],
            'kpis' => $kpis,
            'emballage_stats' => $kpis['emballage_stats'] ?? [],
            'filters' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ],
            'ristourne' => $ristourneActive,
            'dettes' => $dettes,
            'dette_emballages' => $detteEmballages
        ]);
    }

    /**
     * Supprimer un client
     */
    public function delete($id)
    {
        $this->requirePermission('clients.supprimer');
        
        // Bloquer uniquement si le client possède au moins une vente encore validée.
        // Les factures annulées ne doivent pas empêcher la suppression, car elles n'ont plus d'impact commercial.
        $hasVentesValidees = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM ventes WHERE client_id = :id AND statut = 'validee'",
            ['id' => $id]
        );
        
        if ($hasVentesValidees > 0) {
            return $this->error("Impossible de supprimer ce client car il possède encore des ventes validées.");
        }

        // S'il n'a que des ventes annulées, on le considère comme un client sans activité valide.
        // On désactive le client pour éviter les erreurs de clé étrangère avec les anciennes factures annulées.
        $this->clientModel->update($id, ['actif' => 0]);
        return $this->success(null, "Client supprimé avec succès.");
    }
}
