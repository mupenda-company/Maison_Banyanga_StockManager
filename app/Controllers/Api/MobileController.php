<?php

class MobileController extends Controller {
    
    /**
     * Authentification du vendeur
     */
    public function login() {
        $data = $this->getJsonInput();
        $userModel = new User();
        $user = false;

        $password = $data['password'] ?? '';

        if (!empty($data['username'])) {
            $user = $userModel->authenticate($data['username'], $password);
        } elseif (!empty($data['email'])) {
            $candidate = $userModel->findBy('email', $data['email']);
            if ($candidate && ($candidate['actif'] ?? 0) && password_verify($password, $candidate['password'] ?? '')) {
                $userModel->update($candidate['id'], ['derniere_connexion' => date('Y-m-d H:i:s')]);
                $user = $candidate;
            }
        }
        
        if ($user) {
            if (isset($user['password'])) {
                unset($user['password']);
            }

            // Récupérer la mission en cours pour ce vendeur
            $mission = $this->db->fetch(
                "SELECT m.*, 
                        v.immatriculation as vehicule_immatriculation,
                        v.emplacement_id as vehicule_emplacement_id,
                        z.nom as zone_nom
                 FROM missions m
                 JOIN vehicules v ON m.vehicule_id = v.id
                 LEFT JOIN zones z ON m.zone_id = z.id
                 WHERE m.statut = 'en_cours'
                 AND (m.chauffeur_id = :chauffeur_id OR v.agent_responsable_id = :agent_id)
                 ORDER BY m.date_depart DESC
                 LIMIT 1",
                [
                    'chauffeur_id' => (int) $user['id'],
                    'agent_id' => (int) $user['id']
                ]
            );

            $parametreModel = new Parametre();
            $personnalisation = $parametreModel->getPersonnalisation();

            $settings = array_merge(
                $personnalisation,
                [
                    'devise' => $parametreModel->get('devise', 'CDF'),
                    'devise_base' => $parametreModel->get('devise_base', 'CDF'),
                    'taux_change' => (float) $parametreModel->get('taux_change', '2800'),
                    'taux_tva' => (float) $parametreModel->get('taux_tva', 16)
                ]
            );

            return $this->success([
                'user' => $user,
                'mission' => $mission,
                'settings' => $settings
            ]);
        }
        return $this->error('Identifiants invalides', 401);
    }

    /**
     * Branding / personnalisation (public) pour le mobile
     */
    public function branding()
    {
        $parametreModel = new Parametre();
        $params = $parametreModel->getPersonnalisation();
        $params['logo_url'] = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';

        return $this->success($params);
    }

    /**
     * Voir le stock disponible dans le véhicule de la mission
     */
    public function getStock($missionId) {
        new Mission();

        $mission = $this->db->fetch(
            "SELECT id, statut
             FROM missions
             WHERE id = :id
             LIMIT 1",
            ['id' => (int) $missionId]
        );

        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        if (($mission['statut'] ?? null) !== 'en_cours') {
            return $this->error('Mission clôturée', 409);
        }

        $hasCaissesDejaColumn = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'mission_chargements'
               AND COLUMN_NAME = 'caisses_deja_dans_vehicule'"
        );

        $stockInitialCaissesExpr = $hasCaissesDejaColumn
            ? 'COALESCE(mc.caisses_deja_dans_vehicule, 0)'
            : '0';
        $stockInitialBouteillesExpr = $hasCaissesDejaColumn
            ? '(COALESCE(mc.caisses_deja_dans_vehicule, 0) * COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24))'
            : '0';

        $sql = "SELECT p.id, p.nom, p.code, p.bouteilles_par_caisses,
                       p.prix_vente_unitaire, p.prix_vente_caisses,
                       {$stockInitialCaissesExpr} as caisses_deja_dans_vehicule,
                       mc.quantite_caisses,
                       mc.quantite_chargee,
                       IFNULL(mc.quantite_vendue, 0) as quantite_vendue,
                       (IFNULL(mc.quantite_caisses, 0) -
                        FLOOR(IFNULL(mc.quantite_vendue, 0) / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24))) as stock_actuel_caisses,
                       (IFNULL(mc.quantite_caisses, 0) * COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24) - IFNULL(mc.quantite_vendue, 0)) as stock_actuel_bouteilles,
                       (IFNULL(mc.quantite_caisses, 0) -
                        FLOOR(IFNULL(mc.quantite_vendue, 0) / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24))) as stock_actuel
                FROM mission_chargements mc
                JOIN produits p ON mc.produit_id = p.id
                WHERE mc.mission_id = ?";
        $stock = $this->db->fetchAll($sql, [$missionId]);
        return $this->success($stock);
    }

    /**
     * Résumé (dashboard) de mission: caisses restantes + vides dans le véhicule
     */
    public function getMissionStats($missionId)
    {
        $missionId = (int) $missionId;
        new Mission();

        $mission = $this->db->fetch(
            "SELECT m.*, 
                    v.immatriculation as vehicule_immatriculation,
                    v.emplacement_id as vehicule_emplacement_id,
                    z.nom as zone_nom
             FROM missions m
             JOIN vehicules v ON m.vehicule_id = v.id
             LEFT JOIN zones z ON m.zone_id = z.id
             WHERE m.id = :id
             LIMIT 1",
            ['id' => $missionId]
        );

        if (!$mission) {
            return $this->error('Mission non trouvée', 404);
        }

        if (($mission['statut'] ?? null) !== 'en_cours') {
            return $this->error('Mission clôturée', 409);
        }

        $emplacementId = (int) ($mission['vehicule_emplacement_id'] ?? 0);
        if ($emplacementId <= 0) {
            return $this->error('Emplacement véhicule introuvable', 422);
        }

        $stockTotals = $this->db->fetch(
            "SELECT 
                    COALESCE(SUM(caisses_pleine), 0) as caisses_pleine,
                    COALESCE(SUM(caisses_vide), 0) as caisses_vide,
                    COALESCE(SUM(quantite_pleine), 0) as bouteilles_pleine,
                    COALESCE(SUM(quantite_vide), 0) as bouteilles_vide
             FROM stocks
             WHERE emplacement_id = :emplacement_id",
            ['emplacement_id' => $emplacementId]
        );

        $chargementTotals = $this->db->fetch(
            "SELECT
                    COALESCE(SUM(COALESCE(mc.quantite_caisses, 0)), 0) as caisses_chargees,
                    COALESCE(SUM(COALESCE(mc.quantite_caisses, 0) - FLOOR(IFNULL(mc.quantite_vendue, 0) / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24))), 0) as caisses_restantes
             FROM mission_chargements mc
             JOIN produits p ON mc.produit_id = p.id
             WHERE mc.mission_id = :mission_id",
            ['mission_id' => $missionId]
        );

        $clientsCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT client_id)
             FROM ventes
             WHERE mission_id = :mission_id AND statut = 'validee'",
            ['mission_id' => $missionId]
        );

        $caissesChargees = round((float) ($chargementTotals['caisses_chargees'] ?? 0), 0);
        $caissesRestantes = round((float) ($chargementTotals['caisses_restantes'] ?? 0), 0);
        $caissesPleines = round((float) ($stockTotals['caisses_pleine'] ?? 0), 0);
        $caissesVides = round((float) ($stockTotals['caisses_vide'] ?? 0), 0);

        $stockCoherent = abs($caissesRestantes - $caissesPleines) < 0.0001;

        $dernierClient = $this->db->fetch(
            "SELECT c.nom, c.telephone, c.adresse
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             WHERE v.mission_id = :mission_id AND v.statut = 'validee'
             ORDER BY v.date_vente DESC, v.id DESC
             LIMIT 1",
            ['mission_id' => $missionId]
        );

        return $this->success([
            'mission' => [
                'id' => (int) ($mission['id'] ?? 0),
                'numero_mission' => $mission['numero_mission'] ?? null,
                'statut' => $mission['statut'] ?? null,
                'date_depart' => $mission['date_depart'] ?? null,
                'vehicule_immatriculation' => $mission['vehicule_immatriculation'] ?? null,
                'zone_nom' => $mission['zone_nom'] ?? null,
            ],
            'chargement' => [
                'caisses_chargees' => $caissesChargees,
                'caisses_restantes' => $caissesRestantes,
                'stock_coherent' => $stockCoherent,
            ],
            'clients' => [
                'clients_count' => $clientsCount,
                'dernier_client_nom' => $dernierClient['nom'] ?? null,
                'dernier_client_telephone' => $dernierClient['telephone'] ?? null,
                'dernier_client_adresse' => $dernierClient['adresse'] ?? null,
            ],
            'stock' => [
                'caisses_pleine' => $caissesPleines,
                'caisses_vide' => $caissesVides,
                'bouteilles_pleine' => (float) ($stockTotals['bouteilles_pleine'] ?? 0),
                'bouteilles_vide' => (float) ($stockTotals['bouteilles_vide'] ?? 0),
                'stock_actuel_bouteilles' => array_sum(array_map(static function ($row) {
                    return (int) ($row['stock_actuel_bouteilles'] ?? 0);
                }, $this->db->fetchAll("SELECT (COALESCE(mc.quantite_caisses, 0) * COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24)) - IFNULL(mc.quantite_vendue, 0) as stock_actuel_bouteilles FROM mission_chargements mc JOIN produits p ON mc.produit_id = p.id WHERE mc.mission_id = :mission_id", ['mission_id' => $missionId]))),
            ]
        ]);
    }

    public function listVentes()
    {
        $missionId = $_GET['mission_id'] ?? null;
        if (empty($missionId)) {
            return $this->success([]);
        }

        $date = trim((string) ($_GET['date'] ?? ''));
        $params = ['mission_id' => (int) $missionId];
        $dateClause = '';

        if ($date !== '') {
            $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
            if ($parsedDate instanceof DateTime) {
                $dateClause = ' AND DATE(v.date_vente) = :date_vente_date ';
                $params['date_vente_date'] = $parsedDate->format('Y-m-d');
            }
        }

        $rows = $this->db->fetchAll(
            "SELECT v.id, v.numero_facture, v.date_vente, v.total_ttc,
                    c.nom as client_nom,
                    c.telephone as client_telephone,
                    COALESCE(SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))), 0) as caisses_vendues
             FROM ventes v
             JOIN clients c ON v.client_id = c.id
             LEFT JOIN vente_details vd ON vd.vente_id = v.id
             LEFT JOIN produits p ON vd.produit_id = p.id
             WHERE v.mission_id = :mission_id AND v.statut = 'validee'" . $dateClause . "
             GROUP BY v.id, v.numero_facture, v.date_vente, v.total_ttc, c.nom, c.telephone
             ORDER BY v.date_vente DESC
             LIMIT 100",
            $params
        );

        return $this->success($rows);
    }

    /**
     * Données facture (ticket) d'une vente pour le mobile
     */
    public function getVenteFacture($id)
    {
        $venteId = (int) $id;
        if ($venteId <= 0) {
            return $this->error('Vente invalide', 422);
        }

        $vente = (new Vente())->getWithDetails($venteId);
        if (!$vente) {
            return $this->error('Vente non trouvée', 404);
        }

        $parametreModel = new Parametre();
        $params = $parametreModel->getPersonnalisation();
        $params['logo_url'] = !empty($params['logo']) ? asset('uploads/' . $params['logo']) : '';

        $totalCaissesClient = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(vd.quantite / p.bouteilles_par_caisses), 0)
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.client_id = :client_id AND v.statut = 'validee'",
            ['client_id' => (int) ($vente['client_id'] ?? 0)]
        );

        $mois = (int) date('m', strtotime($vente['date_vente'] ?? date('Y-m-d')));
        $annee = (int) date('Y', strtotime($vente['date_vente'] ?? date('Y-m-d')));
        $ristourneInfo = (new Ristourne())->calculerRistourne((int) $vente['client_id'], $mois, $annee);

        return $this->success([
            'vente' => $vente,
            'params' => $params,
            'totalCaissesClient' => $totalCaissesClient,
            'ristourneInfo' => $ristourneInfo
        ]);
    }

    /**
     * Enregistrer une vente depuis le mobile
     */
    public function storeVente() {
        $data = $this->getJsonInput(); // client_id, mission_id, produits[], total, devise

        if (empty($data['produits']) && !empty($data['details']) && is_array($data['details'])) {
            $data['produits'] = $data['details'];
        }

        if (empty($data['produits']) && !empty($data['items']) && is_array($data['items'])) {
            $data['produits'] = $data['items'];
        }
        
        try {
            $this->db->beginTransaction();

            $errors = $this->validate($data, [
                'client_id' => 'required|numeric',
                'mission_id' => 'required|numeric',
                'user_id' => 'required|numeric',
                'produits' => 'required'
            ]);

            if (!empty($errors)) {
                $this->db->rollBack();
                return $this->error('Erreurs de validation', 422, $errors);
            }

            if (!is_array($data['produits'] ?? null) || empty($data['produits'])) {
                $this->db->rollBack();
                return $this->error('Liste des produits invalide', 422);
            }

            $parametreModel = new Parametre();
            $tva = (float) $parametreModel->get('taux_tva', 16);

            $fromDevise = $data['devise'] ?? get_devise();
            $toDevise = get_base_devise();

            $mission = $this->db->fetch(
                "SELECT * FROM missions WHERE id = :id LIMIT 1",
                ['id' => (int) $data['mission_id']]
            );

            if (!$mission) {
                $this->db->rollBack();
                return $this->error('Mission non trouvée', 404);
            }

            if (($mission['statut'] ?? null) !== 'en_cours') {
                $this->db->rollBack();
                return $this->error('Mission non en cours', 422);
            }

            $vehicule = $this->db->fetch(
                "SELECT * FROM vehicules WHERE id = :id LIMIT 1",
                ['id' => (int) $mission['vehicule_id']]
            );

            $emplacementVehiculeId = $vehicule['emplacement_id'] ?? null;

            if (empty($emplacementVehiculeId)) {
                $this->db->rollBack();
                return $this->error('Emplacement véhicule introuvable', 422);
            }

            $venteModel = new Vente();

            $produitModel = new Produit();
            $stockModel = new Stock();
            $mouvementModel = new MouvementStock();

            $totalHt = 0;
            $details = [];

            foreach ($data['produits'] as $item) {
                $produitId = (int) ($item['produit_id'] ?? 0);
                $quantite = (float) ($item['quantite'] ?? 0);
                $quantiteCaisses = (int) ($item['quantite_caisses'] ?? 0);
                $caissesVidesRecues = max(0, (int) ($item['caisses_vides_recues'] ?? 0));

                if ($produitId <= 0 || $quantite <= 0) {
                    throw new Exception('Produit ou quantité invalide');
                }

                $produit = $produitModel->find($produitId);
                if (!$produit) {
                    throw new Exception('Produit non trouvé');
                }

                $bouteillesParCaisse = (int) ($produit['bouteilles_par_caisses'] ?? 24);
                if ($bouteillesParCaisse <= 0) {
                    $bouteillesParCaisse = 24;
                }

                // Verrouiller la ligne pour éviter les ventes concurrentes sur le même chargement
                $chargement = $this->db->fetch(
                    "SELECT quantite_chargee, IFNULL(quantite_vendue, 0) as quantite_vendue
                     FROM mission_chargements
                     WHERE mission_id = :m AND produit_id = :p
                     FOR UPDATE",
                    ['m' => (int) $data['mission_id'], 'p' => $produitId]
                );

                if (!$chargement) {
                    throw new Exception('Chargement mission introuvable pour ce produit');
                }

                $stockVehicule = $this->db->fetch(
                    "SELECT quantite_pleine, caisses_pleine
                     FROM stocks
                     WHERE produit_id = :p AND emplacement_id = :e
                     LIMIT 1
                     FOR UPDATE",
                    [
                        'p' => $produitId,
                        'e' => (int) $emplacementVehiculeId
                    ]
                );

                if ($stockVehicule) {
                    $stockActuelMission = (float) ($stockVehicule['quantite_pleine'] ?? 0);
                } else {
                    $stockActuelMission = ((float) ($chargement['quantite_caisses'] ?? 0) * $bouteillesParCaisse)
                        - (float) ($chargement['quantite_vendue'] ?? 0);
                }

                if ($quantite > $stockActuelMission) {
                    $disponibleCs = max($stockActuelMission / $bouteillesParCaisse, 0);
                    throw new Exception('Stock mission insuffisant. Disponible: ' . number_format($disponibleCs, 1) . ' cs');
                }

                if ($quantiteCaisses <= 0) {
                    $quantiteCaisses = (int) floor($quantite / $bouteillesParCaisse);
                }
                if ($quantiteCaisses <= 0) {
                    throw new Exception('Quantité de caisses invalide');
                }

                if ($caissesVidesRecues > $quantiteCaisses) {
                    throw new Exception('Les emballages reçus ne peuvent pas dépasser les caisses vendues');
                }

                $prixCaisseInput = $item['prix_caisse'] ?? $item['prix'] ?? null;
                if ($prixCaisseInput === null || $prixCaisseInput === '') {
                    $prixCaisseBase = (float) ($produit['prix_vente_caisses'] ?? 0);
                    if ($prixCaisseBase <= 0) {
                        $prixCaisseBase = (float) (($produit['prix_vente_unitaire'] ?? 0) * $bouteillesParCaisse);
                    }
                } else {
                    $prixCaisseBase = convert_money((float) $prixCaisseInput, $fromDevise, $toDevise);
                }

                $prixUnitaireBase = $prixCaisseBase / $bouteillesParCaisse;
                $sousTotal = $quantiteCaisses * $prixCaisseBase;
                $totalHt += $sousTotal;

                $details[] = [
                    'produit_id' => $produitId,
                    'quantite_caisses' => $quantiteCaisses,
                    'quantite' => $quantiteCaisses * $bouteillesParCaisse,
                    'caisses_vides_recues' => $caissesVidesRecues,
                    'prix_unitaire' => $prixUnitaireBase,
                    'prix_caisse' => $prixCaisseBase,
                    'sous_total' => $sousTotal,
                    'bouteilles_par_caisses' => (float) $bouteillesParCaisse
                ];
            }

            $totalTva = $totalHt * ($tva / 100);
            $totalTtc = $totalHt + $totalTva;

            $numeroFacture = $venteModel->generateNumeroFacture('MOB-');

            $dateVente = date('Y-m-d H:i:s');
            $dateVenteInput = trim((string) ($data['date_vente'] ?? ''));
            if ($dateVenteInput !== '') {
                $parsedDateVente = DateTime::createFromFormat('Y-m-d H:i:s', $dateVenteInput)
                    ?: DateTime::createFromFormat(DateTime::ATOM, $dateVenteInput)
                    ?: DateTime::createFromFormat('Y-m-d\TH:i:sP', $dateVenteInput);

                if ($parsedDateVente instanceof DateTime) {
                    $dateVente = $parsedDateVente->format('Y-m-d H:i:s');
                }
            }

            $venteId = $venteModel->create([
                'numero_facture' => $numeroFacture,
                'client_id' => (int) $data['client_id'],
                'date_vente' => $dateVente,
                'mission_id' => (int) $data['mission_id'],
                'emplacement_id' => (int) $emplacementVehiculeId,
                'total_ht' => $totalHt,
                'total_tva' => $totalTva,
                'total_ttc' => $totalTtc,
                'statut' => 'validee',
                'notes' => $data['notes'] ?? '',
                'created_by' => (int) $data['user_id']
            ]);

            foreach ($details as $detail) {
                // 1. Ajouter le détail de vente (stocké en devise_base)
                $this->db->insert('vente_details', [
                    'vente_id' => $venteId,
                    'produit_id' => $detail['produit_id'],
                    'quantite_caisses' => $detail['quantite_caisses'],
                    'caisses_vides_recues' => $detail['caisses_vides_recues'],
                    'quantite' => $detail['quantite'],
                    'prix_unitaire' => $detail['prix_unitaire'],
                    'prix_caisse' => $detail['prix_unitaire'] * (int) $detail['bouteilles_par_caisses'],
                    'sous_total' => $detail['sous_total']
                ]);

                // 2. Déduire du stock de la mission
                $this->db->query(
                    "UPDATE mission_chargements
                     SET quantite_vendue = IFNULL(quantite_vendue, 0) + :q
                     WHERE mission_id = :m AND produit_id = :p",
                    [
                        'q' => $detail['quantite'],
                        'm' => (int) $data['mission_id'],
                        'p' => $detail['produit_id']
                    ]
                );

                $this->db->query(
                    "UPDATE mission_chargements
                     SET quantite_vendue = IFNULL(quantite_vendue, 0) + 0
                     WHERE mission_id = :m AND produit_id = :p",
                    [
                        'm' => (int) $data['mission_id'],
                        'p' => $detail['produit_id']
                    ]
                );

                // 3. Déduire du stock du véhicule (pleins) pour que l'inventaire reste cohérent
                $mouvementModel->create([
                    'produit_id' => $detail['produit_id'],
                    'emplacement_id' => (int) $emplacementVehiculeId,
                    'type_mouvement' => 'sortie',
                    'quantite' => -$detail['quantite'],
                    'reference_type' => 'vente',
                    'reference_id' => $venteId,
                    'motif' => 'Vente mobile N° ' . $numeroFacture . ' (Plein)',
                    'created_by' => (int) $data['user_id']
                ]);

                $stockModel->updateOrCreate(
                    $detail['produit_id'],
                    (int) $emplacementVehiculeId,
                    [
                        'quantite_pleine' => -$detail['quantite'],
                        'caisses_pleine' => -$detail['quantite_caisses']
                    ]
                );

                // 4. Ajouter les vides uniquement si réellement reçus
                if ($detail['caisses_vides_recues'] > 0) {
                    $quantiteVides = $detail['caisses_vides_recues'] * $bouteillesParCaisse;

                    $stockModel->updateOrCreate(
                        $detail['produit_id'],
                        (int) $emplacementVehiculeId,
                        [
                            'quantite_vide' => $quantiteVides,
                            'caisses_vide' => $detail['caisses_vides_recues']
                        ]
                    );

                    $mouvementModel->create([
                        'produit_id' => $detail['produit_id'],
                        'emplacement_id' => (int) $emplacementVehiculeId,
                        'type_mouvement' => 'entree',
                        'quantite' => $quantiteVides,
                        'reference_type' => 'vente',
                        'reference_id' => $venteId,
                        'motif' => 'Retour vide Vente N° ' . $numeroFacture,
                        'created_by' => (int) $data['user_id']
                    ]);
                }
            }

            $this->db->commit();
            return $this->success([
                'vente_id' => $venteId,
                'numero_facture' => $numeroFacture,
                'total_ht' => $totalHt,
                'total_tva' => $totalTva,
                'total_ttc' => $totalTtc,
                'devise_base' => $toDevise
            ], 'Vente enregistrée');

        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
