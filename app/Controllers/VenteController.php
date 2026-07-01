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
        $this->requirePermission('ventes.voir');
        
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
        $this->requirePermission('ventes.creer');
        
        $clients = $this->clientModel->getAllWithZone();
        $produits = $this->produitModel->getWithStock();
        $emplacements = $this->emplacementModel->getFixes();
        $tva = $this->parametreModel->get('taux_tva', 16);
        $autoriserInterchangeEmballages = $this->parametreModel->get('autoriser_interchange_emballages', '1') === '1';
        
        $this->view('ventes/create', [
            'clients' => $clients,
            'produits' => $produits,
            'emplacements' => $emplacements,
            'tva' => $tva,
            'numero_facture' => $this->venteModel->generateNumeroFacture(),
            'autoriser_interchange_emballages' => $autoriserInterchangeEmballages
        ]);
    }
    
    /**
     * Enregistrer une vente
     */
    public function store()
    {
        $this->requirePermission('ventes.creer');
        
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
        $autoriserInterchangeEmballages = $this->parametreModel->get('autoriser_interchange_emballages', '1') === '1';
        $emballagesRecus = $autoriserInterchangeEmballages ? $this->normaliserEmballagesRecus($data['emballages_recus'] ?? null) : [];
        $totalCaissesVendues = 0;
        
        foreach ($data['details'] as $index => $detail) {
            if (!is_array($detail)) {
                return $this->error('Chaque ligne de vente doit être un objet valide.', 422);
            }

            if (empty($emballagesRecus) && (!array_key_exists('caisses_vides_recues', $detail) || $detail['caisses_vides_recues'] === '' || $detail['caisses_vides_recues'] === null)) {
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

            if (array_key_exists('caisses_vides_recues', $detail) && $detail['caisses_vides_recues'] !== '' && $detail['caisses_vides_recues'] !== null && !is_numeric($detail['caisses_vides_recues'])) {
                return $this->error('Les emballages reçus doivent être un nombre valide pour la ligne ' . ($index + 1) . '.', 422);
            }

            $caissesVidesRecues = max(0, (int) ($detail['caisses_vides_recues'] ?? 0));

            if ($caissesVidesRecues > $quantiteCaisses) {
                return $this->error('Les emballages reçus ne peuvent pas dépasser le nombre de caisses vendues pour la ligne ' . ($index + 1) . '.', 422);
            }

            $prixUnitaire = $detail['prix_unitaire'] ?? $produit['prix_vente_unitaire'];
            $sousTotal = $quantiteCaisses * $prixUnitaire * ($produit['bouteilles_par_caisses'] ?? 24);
            $totalHt += $sousTotal;
            $totalCaissesVendues += $quantiteCaisses;
            
            $details[] = [
                'produit_id' => $detail['produit_id'],
                'quantite_caisses' => $quantiteCaisses,
                'caisses_vides_recues' => $caissesVidesRecues,
                'quantite' => $quantiteCaisses * (int) ($produit['bouteilles_par_caisses'] ?? 24),
                'prix_unitaire' => $prixUnitaire,
                'sous_total' => $sousTotal
            ];
        }

        if (empty($emballagesRecus)) {
            $emballagesRecus = $this->normaliserEmballagesRecus(array_map(function ($detail) {
                return [
                    'produit_id' => $detail['produit_id'],
                    'caisses_recues' => $detail['caisses_vides_recues'] ?? 0
                ];
            }, $details));
        }

        if (array_sum(array_column($emballagesRecus, 'caisses_recues')) > $totalCaissesVendues) {
            return $this->error('Le total des emballages recus ne peut pas depasser le total des caisses vendues.', 422);
        }
        
        $totalTva = $totalHt * ($tva / 100);
        $totalTtc = $totalHt + $totalTva;
        $billetage = $data['billetage'] ?? [];
        $billetageModel = new Billetage();
        $totalBilletage = is_array($billetage) ? $billetageModel->totalBase($billetage) : 0;
        if ($totalBilletage > 0 && abs($totalBilletage - $totalTtc) > 0.01) {
            return $this->error('Le billetage ne correspond pas au total TTC. Billetage: ' . format_money_dual($totalBilletage) . ', attendu: ' . format_money_dual($totalTtc), 422);
        }        
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
        
        $result = $this->venteModel->createWithDetails($venteData, $details, $emballagesRecus);
        
        if ($result['success']) {
            if ($totalBilletage > 0) {
                $billetageModel->saveForReference('vente', (int) $result['id'], $billetage, $_SESSION['user_id'] ?? null);
            }
            return $this->success(['id' => $result['id']], 'Vente enregistrée avec succès');
        }
        
        return $this->error($result['message'], 400);
    }

    public function edit($id)
    {
        $this->requirePermission('ventes.creer');

        $vente = $this->venteModel->getWithDetails($id);

        if (!$vente) {
            return $this->error('Vente non trouvée', 404);
        }

        if (($vente['statut'] ?? '') !== 'validee') {
            return $this->error('Seules les ventes validées peuvent être modifiées', 422);
        }

        // Par défaut, les ventes créées depuis le back-office utilisent les points fixes.
        // Pour une facture mobile, le point de vente doit rester le véhicule de la mission.
        $emplacements = $this->emplacementModel->getFixes();
        $origineVente = null;

        if (!empty($vente['mission_id'])) {
            $origineVente = $this->db->fetch(
                "SELECT m.id as mission_id, m.numero_mission,
                        v.id as vehicule_id, v.immatriculation as vehicule_immatriculation,
                        v.emplacement_id as vehicule_emplacement_id,
                        e.nom as vehicule_emplacement_nom, e.type as vehicule_emplacement_type
                 FROM missions m
                 LEFT JOIN vehicules v ON m.vehicule_id = v.id
                 LEFT JOIN emplacements e ON v.emplacement_id = e.id
                 WHERE m.id = :mission_id
                 LIMIT 1",
                ['mission_id' => (int) $vente['mission_id']]
            );

            $vehiculeEmplacementId = (int) ($origineVente['vehicule_emplacement_id'] ?? 0);
            if ($vehiculeEmplacementId > 0) {
                // On force l'emplacement affiché et envoyé à l'API vers le véhicule réel.
                $vente['emplacement_id'] = $vehiculeEmplacementId;
                $vente['emplacement_nom'] = $origineVente['vehicule_emplacement_nom'] ?? $vente['emplacement_nom'] ?? '';

                $dejaDansListe = false;
                foreach ($emplacements as $emp) {
                    if ((int) ($emp['id'] ?? 0) === $vehiculeEmplacementId) {
                        $dejaDansListe = true;
                        break;
                    }
                }

                // getFixes() ne retourne généralement que les entrepôts; on ajoute donc le véhicule d'origine.
                if (!$dejaDansListe) {
                    $libelleVehicule = trim(
                        'Véhicule ' . ($origineVente['vehicule_immatriculation'] ?? '') .
                        (!empty($origineVente['vehicule_emplacement_nom']) ? ' - ' . $origineVente['vehicule_emplacement_nom'] : '')
                    );

                    $emplacements[] = [
                        'id' => $vehiculeEmplacementId,
                        'nom' => $libelleVehicule ?: 'Véhicule de la mission',
                        'type' => $origineVente['vehicule_emplacement_type'] ?? 'vehicule'
                    ];
                }
            }
        }

        $stockEmplacementId = (int) ($vente['emplacement_id'] ?? 0);
        $produits = $stockEmplacementId > 0
            ? $this->getProduitsAvecStockEmplacement($stockEmplacementId)
            : $this->produitModel->getWithStock();

        $this->view('ventes/edit', [
            'vente' => $vente,
            'clients' => $this->clientModel->getAllWithZone(),
            'produits' => $produits,
            'emplacements' => $emplacements,
            'origine_vente' => $origineVente,
            'stock_emplacement_id' => $stockEmplacementId,
            'tva' => $this->parametreModel->get('taux_tva', 16),
            'autoriser_interchange_emballages' => $this->parametreModel->get('autoriser_interchange_emballages', '1') === '1'
        ]);
    }

    public function update($id)
    {
        $this->requirePermission('ventes.creer');

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'client_id' => 'required|numeric',
            'emplacement_id' => 'required|numeric',
            'details' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $ancienneVente = $this->venteModel->getWithDetails($id);
        if (!$ancienneVente) {
            return $this->error('Vente non trouvée', 404);
        }

        if (!empty($ancienneVente['mission_id'])) {
            $vehiculeEmplacementId = (int) $this->db->fetchColumn(
                "SELECT v.emplacement_id
                 FROM missions m
                 JOIN vehicules v ON m.vehicule_id = v.id
                 WHERE m.id = :mission_id
                 LIMIT 1",
                ['mission_id' => (int) $ancienneVente['mission_id']]
            );

            if ($vehiculeEmplacementId > 0) {
                // Sécurité serveur: une facture mobile reste toujours liée au stock du véhicule.
                $data['emplacement_id'] = $vehiculeEmplacementId;
            }
        }

        $tva = $this->parametreModel->get('taux_tva', 16);

        $totalHt = 0;
        $details = [];
        $totalCaissesVendues = 0;

        foreach ($data['details'] as $index => $detail) {
            $produit = $this->produitModel->find($detail['produit_id']);

            if (!$produit) {
                return $this->error('Produit introuvable à la ligne ' . ($index + 1), 422);
            }

            $bouteillesParCaisse = max(1, (int)($produit['bouteilles_par_caisses'] ?? 24));
            $quantiteCaisses = max(0, (int)($detail['quantite_caisses'] ?? 0));

            if ($quantiteCaisses <= 0) {
                return $this->error('La quantité doit être supérieure à 0 à la ligne ' . ($index + 1), 422);
            }

            $prixUnitaire = (float)($detail['prix_unitaire'] ?? $produit['prix_vente_unitaire']);
            $sousTotal = $quantiteCaisses * $prixUnitaire * $bouteillesParCaisse;

            $totalHt += $sousTotal;
            $totalCaissesVendues += $quantiteCaisses;

            $details[] = [
                'produit_id' => (int)$detail['produit_id'],
                'quantite_caisses' => $quantiteCaisses,
                'caisses_vides_recues' => max(0, (int)($detail['caisses_vides_recues'] ?? 0)),
                'quantite' => $quantiteCaisses * $bouteillesParCaisse,
                'prix_unitaire' => $prixUnitaire,
                'sous_total' => $sousTotal
            ];
        }

        $totalTva = $totalHt * ($tva / 100);
        $totalTtc = $totalHt + $totalTva;

        $venteData = [
            'client_id' => $data['client_id'],
            'emplacement_id' => $data['emplacement_id'],
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
            'notes' => $data['notes'] ?? '',
            'updated_by' => $_SESSION['user_id'] ?? null
        ];

        $result = $this->venteModel->updateWithDetails(
            $id,
            $venteData,
            $details,
            $data['emballages_recus'] ?? null
        );

        if ($result['success']) {
            return $this->success(['id' => $id], 'Vente modifiée avec succès');
        }

        return $this->error($result['message'], 400);
    }   


    /**
     * Récupérer les produits avec le stock d'un emplacement précis.
     * Important pour la modification d'une facture mobile: le stock affiché doit être celui du véhicule,
     * pas le stock global ou celui de l'entrepôt.
     */
    private function getProduitsAvecStockEmplacement($emplacementId)
    {
        $emplacementId = (int) $emplacementId;

        return $this->db->fetchAll(
            "SELECT p.*,
                    COALESCE(s.quantite_pleine, 0) as stock_plein,
                    COALESCE(s.quantite_vide, 0) as stock_vide,
                    COALESCE(s.caisses_pleine, 0) as caisses_pleine,
                    COALESCE(s.caisses_vide, 0) as caisses_vide,
                    COALESCE(s.quantite_pleine, 0) as quantite_pleine,
                    COALESCE(s.quantite_vide, 0) as quantite_vide
             FROM produits p
             LEFT JOIN stocks s ON s.produit_id = p.id AND s.emplacement_id = :emplacement_id
             ORDER BY p.nom ASC",
            ['emplacement_id' => $emplacementId]
        );
    }

    private function normaliserEmballagesRecus($emballagesRecus)
    {
        $result = [];

        if (!is_array($emballagesRecus)) {
            return $result;
        }

        foreach ($emballagesRecus as $ligne) {
            if (!is_array($ligne)) {
                continue;
            }

            $produitId = (int) ($ligne['produit_id'] ?? 0);
            $caisses = max(0, (int) ($ligne['caisses_recues'] ?? $ligne['caisses'] ?? 0));
            if ($produitId <= 0 || $caisses <= 0) {
                continue;
            }

            if (!isset($result[$produitId])) {
                $result[$produitId] = [
                    'produit_id' => $produitId,
                    'caisses_recues' => 0
                ];
            }

            $result[$produitId]['caisses_recues'] += $caisses;
        }

        return array_values($result);
    }
    
    /**
     * Afficher une vente
     */
    public function show($id)
    {
        $this->requirePermission('ventes.voir');
        
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
        $this->requirePermission('ventes.supprimer');
        
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
        $this->requirePermission('ventes.voir');
        
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
        $this->requirePermission('ventes.voir');
        
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
        $this->requirePermission('ventes.voir');
        
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
        $this->requirePermission('ventes.voir');
        
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
    
    private function styleHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $nbCols): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbCols);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
        ]);
        foreach (range(1, $nbCols) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    // Helper pour envoyer le fichier xlsx au navigateur
    private function sendXlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (headers_sent()) {
            throw new Exception('Impossible de generer le fichier Excel: des donnees ont deja ete envoyees au navigateur.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
    }

    /**
     * Exporter les ventes par véhicule en Excel
     */
    public function exportParVehicule()
    {
        $this->requirePermission('ventes.voir');

        $vehiculeId = $_GET['vehicule_id'] ?? null;
        $dateDebut  = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin    = $_GET['date_fin']   ?? date('Y-m-d');

        if (!$vehiculeId) return $this->error('Véhicule non spécifié', 400);

        $vehicule = $this->db->fetch("SELECT id, immatriculation FROM vehicules WHERE id = :id", ['id' => (int) $vehiculeId]);
        if (!$vehicule) return $this->error('Véhicule non trouvé', 404);

        $ventes = $this->db->fetchAll(
            "SELECT v.id, v.numero_facture, v.date_vente, v.total_ttc,
                    c.id as client_id, c.nom as client_nom, c.telephone as client_telephone,
                    z.nom as zone_nom, m.numero_mission
            FROM ventes v
            JOIN clients c ON v.client_id = c.id
            LEFT JOIN zones z ON c.zone_id = z.id
            LEFT JOIN missions m ON v.mission_id = m.id
            WHERE m.vehicule_id = :vehicule_id
            AND DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
            AND v.statut = 'validee'
            ORDER BY v.date_vente DESC",
            ['vehicule_id' => (int) $vehiculeId, 'date_debut' => $dateDebut, 'date_fin' => $dateFin]
        );

        foreach ($ventes as &$vente) {
            $vente['details'] = $this->db->fetchAll(
                "SELECT p.nom as produit_nom, vd.quantite_caisses, vd.caisses_vides_recues, vd.sous_total
                FROM vente_details vd JOIN produits p ON vd.produit_id = p.id
                WHERE vd.vente_id = :vente_id",
                ['vente_id' => $vente['id']]
            );
        }

        $clientsData = [];
        foreach ($ventes as $vente) {
            $cid = $vente['client_id'];
            if (!isset($clientsData[$cid])) {
                $clientsData[$cid] = [
                    'numero' => $cid, 'zone' => $vente['zone_nom'],
                    'telephone' => $vente['client_telephone'], 'nom' => $vente['client_nom'],
                    'produits' => [], 'total_caisses' => 0, 'chiffre_affaire' => 0,
                    'restourne' => 0, 'nombre_ventes' => 0,
                ];
            }
            $clientsData[$cid]['nombre_ventes']++;
            foreach ($vente['details'] as $detail) {
                $pn = $detail['produit_nom'];
                if (!isset($clientsData[$cid]['produits'][$pn])) {
                    $clientsData[$cid]['produits'][$pn] = ['total_caisses' => 0, 'nombre_ventes' => 0];
                }
                $clientsData[$cid]['produits'][$pn]['total_caisses'] += $detail['quantite_caisses'];
                $clientsData[$cid]['produits'][$pn]['nombre_ventes']++;
                $clientsData[$cid]['total_caisses']   += $detail['quantite_caisses'];
                $clientsData[$cid]['chiffre_affaire'] += $detail['sous_total'];
                $clientsData[$cid]['restourne']       += $detail['caisses_vides_recues'];
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Ventes véhicule');

        $headers = ['Numéro', 'Zone', 'Téléphone', 'Nom Client', 'Produit', 'Total Caisses', "Chiffre d'Affaire", 'Retourne', 'Nombre Ventes'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($clientsData as $client) {
            foreach ($client['produits'] as $produitNom => $produitData) {
                $sheet->fromArray([
                    $client['numero'],
                    $client['zone'],
                    $client['telephone'],
                    $client['nom'],
                    $produitNom,
                    (int) $produitData['total_caisses'],
                    (float) $client['chiffre_affaire'],
                    (int) $client['restourne'],
                    (int) $produitData['nombre_ventes'],
                ], null, 'A' . $row++);
            }
        }

        $this->styleHeaderRow($sheet, count($headers));
        $filename = 'ventes_vehicule_' . $vehicule['immatriculation'] . '_' . date('Y-m-d') . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }       
    /**
     * Exporter toutes les ventes en Excel avec produits en colonnes
     */
    public function exportAll()
    {
        $this->requirePermission('ventes.voir');
        
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        $clientId = $_GET['client_id'] ?? null;
        $emplacementId = $_GET['emplacement_id'] ?? null;
        
        $params = [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        
        $clientClause = '';
        if ($clientId) {
            $clientClause = ' AND v.client_id = :client_id ';
            $params['client_id'] = (int) $clientId;
        }
        
        $emplacementClause = '';
        if ($emplacementId) {
            $emplacementClause = ' AND v.emplacement_id = :emplacement_id ';
            $params['emplacement_id'] = (int) $emplacementId;
        }
        
        // Récupérer tous les produits
        $produits = $this->db->fetchAll("SELECT id, nom FROM produits ORDER BY nom");
        
        // Récupérer TOUS les clients
        $allClients = $this->db->fetchAll(
            "SELECT 
                c.id, 
                c.nom, 
                COALESCE(SUM(vd.quantite_caisses), 0) as total_caisses
            FROM clients c
            JOIN ventes v ON v.client_id = c.id
            JOIN vente_details vd ON vd.vente_id = v.id
            WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
            AND v.statut = 'validee'
            " . $clientClause . $emplacementClause . "
            GROUP BY c.id, c.nom
            HAVING total_caisses >= 1
            ORDER BY c.nom",
            $params
        );
        
        // Récupérer les ventes groupées par client avec détails par produit
        $salesByClient = [];
        $ventes = $this->db->fetchAll(
            "SELECT v.client_id, v.total_ttc
             FROM ventes v
             WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'" . $clientClause . $emplacementClause,
            $params
        );
        
        // Calculer les totaux par client
        foreach ($ventes as $vente) {
            $cid = $vente['client_id'];
            if (!isset($salesByClient[$cid])) {
                $salesByClient[$cid] = [
                    'total_ttc' => 0,
                    'ristourne' => 0,
                    'produits_qty' => []
                ];
            }
            $salesByClient[$cid]['total_ttc'] += $vente['total_ttc'];
        }
        
        // Calculer la ristourne pour chaque client ayant des ventes
        foreach ($salesByClient as $cid => &$data) {
            $client = $this->db->fetch(
                "SELECT taux_ristourne FROM clients WHERE id = :id",
                ['id' => $cid]
            );
            $taux = (float) ($client['taux_ristourne'] ?? 5);
            $data['ristourne'] = ($data['total_ttc'] * $taux) / 100;
        }
        
        // Récupérer les quantités par produit pour chaque client avec ventes
        $detailsAll = $this->db->fetchAll(
            "SELECT v.client_id, p.nom as produit_nom, 
                    SUM(vd.quantite_caisses) as total_caisses,
                    SUM(vd.caisses_vides_recues) as total_restourne
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'" . $clientClause . $emplacementClause . "
             GROUP BY v.client_id, p.nom",
            $params
        );
        
        foreach ($detailsAll as $detail) {
            $cid = $detail['client_id'];
            $salesByClient[$cid]['produits_qty'][$detail['produit_nom']] = $detail['total_caisses'];
            $salesByClient[$cid]['total_restourne'] = ($salesByClient[$cid]['total_restourne'] ?? 0) + $detail['total_restourne'];
        }
        

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventes');

        // En-têtes en gras
        $headers = ['Nom Client'];
        foreach ($produits as $produit) {
            $headers[] = $produit['nom'];
        }
        $headers[] = 'Nombre de caisses';
        $headers[] = 'Total (Chiffre d\'affaire)';
        $headers[] = 'Ristourne';

        $sheet->fromArray($headers, null, 'A1');

        // Style header : gras + fond gris
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9']
            ]
        ]);

        // Données
        $rowNum = 2;
        $totauxProduits = [];
        foreach ($produits as $produit) {
            $totauxProduits[$produit['nom']] = 0;
        }

        $totalGeneralCaisses = 0;
        $totalGeneralCA = 0;
        $totalGeneralRistourne = 0;
        foreach ($allClients as $client) {
            $cid = $client['id'];
            $clientData = $salesByClient[$cid] ?? null;
            if (!$clientData) {
                continue;
            }

            $row = [
                $client['nom']
            ];

            $totalCaisses = 0;
            foreach ($produits as $produit) {
                $qty = $clientData['produits_qty'][$produit['nom']] ?? 0;

                $row[] = (int)$qty;
                $totalCaisses += $qty;

                // Total par produit
                $totauxProduits[$produit['nom']] += $qty;
            }

            $row[] = (int) $totalCaisses;
            $row[] = (float) $clientData['total_ttc'];
            $row[] = (float) $clientData['ristourne'];

            $totalGeneralCaisses += $totalCaisses;
            $totalGeneralCA += $clientData['total_ttc'];
            $totalGeneralRistourne += $clientData['ristourne'];
            
            $sheet->fromArray($row, null, 'A' . $rowNum);
            $rowNum++;
        }
        $totalRow = [
            'TOTAL GENERAL'
        ];

        foreach ($produits as $produit) {
            $totalRow[] = (int)$totauxProduits[$produit['nom']];
        }

        $totalRow[] = (int)$totalGeneralCaisses;
        $totalRow[] = (float)$totalGeneralCA;
        $totalRow[] = (float)$totalGeneralRistourne;

        $sheet->fromArray($totalRow, null, 'A' . $rowNum);
        $sheet->getStyle(
            'A' . $rowNum . ':' . $lastCol . $rowNum
        )->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK
                ]
            ]
        ]);
        // Largeur automatique pour toutes les colonnes
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Telechargement
        $filename = 'ventes_' . $dateDebut . '_' . $dateFin . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }
}

