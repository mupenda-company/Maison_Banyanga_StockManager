<?php
/**
 * Modèle Client
 */

class Client extends Model
{
    protected $table = 'clients';
    protected $fillable = ['nom', 'telephone', 'numero_client', 'qr_token', 'adresse', 'zone_id', 'email', 'taux_ristourne', 'notes', 'actif'];

    private static bool $numeroClientColumnChecked = false;
    private static bool $numeroClientUniqueIndexChecked = false;
    private static bool $qrTokenColumnChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureNumeroClientColumn();
        $this->ensureNumeroClientUniqueIndex();
        $this->ensureQrTokens();
    }

    private function ensureNumeroClientColumn(): void
    {
        if (self::$numeroClientColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'clients'
               AND COLUMN_NAME = 'numero_client'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE clients ADD numero_client VARCHAR(50) NULL AFTER telephone");
        }

        self::$numeroClientColumnChecked = true;
    }

    private function ensureNumeroClientUniqueIndex(): void
    {
        if (self::$numeroClientUniqueIndexChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'clients'
               AND INDEX_NAME = 'uk_clients_numero_client'"
        );

        if (!$exists) {
            $hasDuplicates = (bool) $this->db->fetchColumn(
                "SELECT COUNT(*)
                 FROM (
                     SELECT numero_client
                     FROM clients
                     WHERE numero_client IS NOT NULL AND numero_client <> ''
                     GROUP BY numero_client
                     HAVING COUNT(*) > 1
                 ) duplicates"
            );

            if (!$hasDuplicates) {
                $this->db->query("ALTER TABLE clients ADD UNIQUE KEY uk_clients_numero_client (numero_client)");
            }
        }

        self::$numeroClientUniqueIndexChecked = true;
    }

    private function ensureQrTokens(): void
    {
        if (self::$qrTokenColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'qr_token'"
        );
        if (!$exists) {
            $this->db->query("ALTER TABLE clients ADD qr_token CHAR(32) NULL AFTER numero_client");
        }

        $clients = $this->db->fetchAll("SELECT id FROM clients WHERE qr_token IS NULL OR qr_token = ''");
        foreach ($clients as $client) {
            $this->db->query(
                "UPDATE clients SET qr_token = :token WHERE id = :id",
                ['token' => $this->generateUniqueQrToken(), 'id' => (int) $client['id']]
            );
        }

        $indexExists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND INDEX_NAME = 'uk_clients_qr_token'"
        );
        if (!$indexExists) {
            $this->db->query("ALTER TABLE clients ADD UNIQUE KEY uk_clients_qr_token (qr_token)");
        }

        self::$qrTokenColumnChecked = true;
    }

    private function generateUniqueQrToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while ($this->db->fetchColumn("SELECT COUNT(*) FROM clients WHERE qr_token = :token", ['token' => $token]));
        return $token;
    }

    public function getByQrToken(string $token)
    {
        return $this->db->fetch(
            "SELECT c.id, c.nom, c.telephone, c.numero_client, c.adresse, c.email,
                    c.zone_id, c.actif, z.nom AS zone_nom
             FROM clients c
             LEFT JOIN zones z ON z.id = c.zone_id
             WHERE c.qr_token = :token AND c.actif = 1
             LIMIT 1",
            ['token' => strtolower(trim($token))]
        );
    }

    public function regenerateQrToken(int $clientId): string
    {
        $token = $this->generateUniqueQrToken();
        $this->update($clientId, ['qr_token' => $token]);
        return $token;
    }

    public function qrPayload(array $client): string
    {
        return 'BRALIMA-CLIENT:' . strtolower((string) ($client['qr_token'] ?? ''));
    }
    
    /**
     * Récupérer avec la zone
     */
    public function getWithZone($id)
    {
        return $this->db->fetch(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE c.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Récupérer tous avec zone
     */
    public function getAllWithZone()
    {
        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE c.actif = 1
             ORDER BY c.nom"
        );
    }
    
    /**
     * Récupérer par zone
     */
    public function getByZone($zoneId)
    {
        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE c.zone_id = :zone_id AND c.actif = 1
             ORDER BY c.nom",
            ['zone_id' => $zoneId]
        );
    }

    /**
     * Rechercher des clients par terme et zone.
     */
    public function searchWithZone($term, $zoneId = null, $includeInactive = false)
    {
        $where = $includeInactive ? '1=1' : 'c.actif = 1';
        $params = [];

        if ($zoneId !== null && $zoneId !== '') {
            $where .= ' AND c.zone_id = :zone_id';
            $params['zone_id'] = $zoneId;
        }

        $term = trim((string) $term);
        if ($term !== '') {
            $where .= " AND (c.nom LIKE :term_nom OR c.telephone LIKE :term_telephone OR c.numero_client LIKE :term_numero_client OR c.email LIKE :term_email OR c.adresse LIKE :term_adresse OR z.nom LIKE :term_zone)";
            $likeTerm = '%' . $term . '%';
            $params['term_nom'] = $likeTerm;
            $params['term_telephone'] = $likeTerm;
            $params['term_numero_client'] = $likeTerm;
            $params['term_email'] = $likeTerm;
            $params['term_adresse'] = $likeTerm;
            $params['term_zone'] = $likeTerm;
        }

        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE {$where}
             ORDER BY c.nom",
            $params
        );
    }
    
    /**
     * Calculer le CA d'un client pour une période
     */
    public function getCAPeriod($clientId, $dateDebut, $dateFin)
    {
        return $this->db->fetchColumn(
            "SELECT COALESCE(SUM(v.total_ttc), 0)
             FROM ventes v
             WHERE v.client_id = :client_id
             AND v.date_vente BETWEEN :date_debut AND :date_fin
             AND v.statut = 'validee'",
            [
                'client_id' => $clientId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]
        );
    }

    /**
     * Récupérer les indicateurs client sur une période
     */
    public function getKpis($clientId, $dateDebut = null, $dateFin = null)
    {
        $client = $this->find($clientId);
        if (!$client) {
            return null;
        }

        $saleWhere = "v.client_id = :client_id AND v.statut = 'validee'";
        $saleParams = ['client_id' => $clientId];
        if (!empty($dateDebut)) {
            $saleWhere .= " AND v.date_vente >= :date_debut";
            $saleParams['date_debut'] = $dateDebut;
        }
        if (!empty($dateFin)) {
            $saleWhere .= " AND v.date_vente <= :date_fin";
            $saleParams['date_fin'] = str_contains($dateFin, ':') ? $dateFin : ($dateFin . ' 23:59:59');
        }

        $emballageStats = $this->getEmballageStats($clientId, $dateDebut, $dateFin);
        $caissesAchetees = (int) ($emballageStats['total_caisses_vendues'] ?? 0);
        $caissesRetournees = (int) (($emballageStats['total_caisses_vides_recues'] ?? 0) + ($emballageStats['total_caisses_retournees'] ?? 0));
        $detteEmballages = (int) ($emballageStats['total_dette'] ?? 0);

        $caTotal = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(v.total_ttc), 0)
             FROM ventes v
             WHERE {$saleWhere}",
            $saleParams
        );

        $tauxRistourne = (float) ($client['taux_ristourne'] ?? 5);
        $montantRistourne = ($caTotal * $tauxRistourne) / 100;

        return [
            'client' => $client,
            'caisses_achetees' => $caissesAchetees,
            'ca_total' => $caTotal,
            'caisses_retournees' => $caissesRetournees,
            'dette_emballages' => $detteEmballages,
            'taux_ristourne' => $tauxRistourne,
            'montant_ristourne' => $montantRistourne,
            'emballage_stats' => $emballageStats,
        ];
    }

    /**
     * Récupérer le détail des emballages d'un client par produit
     */
    public function getEmballageStats($clientId, $dateDebut = null, $dateFin = null)
    {
        $saleWhere = "v.client_id = :client_id AND v.statut = 'validee'";
        $saleParams = ['client_id' => $clientId];
        if (!empty($dateDebut)) {
            $saleWhere .= " AND v.date_vente >= :date_debut";
            $saleParams['date_debut'] = $dateDebut;
        }
        if (!empty($dateFin)) {
            $saleWhere .= " AND v.date_vente <= :date_fin";
            $saleParams['date_fin'] = str_contains($dateFin, ':') ? $dateFin : ($dateFin . ' 23:59:59');
        }

        $produits = $this->db->fetchAll(
            "SELECT vd.produit_id,
                    p.nom as produit_nom,
                    p.code as produit_code,
                    p.position_affichage,
                    COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24) as bouteilles_par_caisses,
                    COALESCE(SUM(COALESCE(vd.quantite_caisses, ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0))), 0) as caisses_vendues,
                    COALESCE(SUM(COALESCE(vd.caisses_vides_recues, 0)), 0) as caisses_vides_recues
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE {$saleWhere}
             GROUP BY vd.produit_id, p.nom, p.code, p.position_affichage, p.bouteilles_par_caisses
             ORDER BY p.position_affichage ASC, p.nom ASC",
            $saleParams
        );

        $returnWhere = "r.client_id = :client_id";
        $returnParams = ['client_id' => $clientId];
        if (!empty($dateDebut)) {
            $returnWhere .= " AND r.date_retour >= :ret_date_debut";
            $returnParams['ret_date_debut'] = $dateDebut;
        }
        if (!empty($dateFin)) {
            $returnWhere .= " AND r.date_retour <= :ret_date_fin";
            $returnParams['ret_date_fin'] = str_contains($dateFin, ':') ? $dateFin : ($dateFin . ' 23:59:59');
        }

        $retours = $this->db->fetchAll(
            "SELECT r.produit_id,
                    COALESCE(SUM(ROUND(r.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)), 0) as caisses_retournees
             FROM retours_emballages r
             JOIN produits p ON r.produit_id = p.id
             WHERE {$returnWhere}
             GROUP BY r.produit_id",
            $returnParams
        );

        $retoursByProduit = [];
        foreach ($retours as $retour) {
            $retoursByProduit[(int) $retour['produit_id']] = (int) ($retour['caisses_retournees'] ?? 0);
        }

        $totalCaissesVendues = 0;
        $totalCaissesVidesRecues = 0;
        $totalCaissesRetournees = 0;
        $totalDette = 0;
        $resultats = [];

        foreach ($produits as $produit) {
            $produitId = (int) $produit['produit_id'];
            $caissesVendues = (int) ($produit['caisses_vendues'] ?? 0);
            $caissesVidesRecues = (int) ($produit['caisses_vides_recues'] ?? 0);
            $caissesRetournees = (int) ($retoursByProduit[$produitId] ?? 0);
            $dette = max(0, $caissesVendues - $caissesVidesRecues - $caissesRetournees);

            $totalCaissesVendues += $caissesVendues;
            $totalCaissesVidesRecues += $caissesVidesRecues;
            $totalCaissesRetournees += $caissesRetournees;
            $totalDette += $dette;

            $resultats[] = [
                'produit_id' => $produitId,
                'produit_nom' => $produit['produit_nom'],
                'produit_code' => $produit['produit_code'],
                'bouteilles_par_caisses' => (int) $produit['bouteilles_par_caisses'],
                'caisses_vendues' => $caissesVendues,
                'caisses_vides_recues' => $caissesVidesRecues,
                'caisses_retournees' => $caissesRetournees,
                'dette_caisses' => $dette,
            ];
        }

        return [
            'produits' => $resultats,
            'total_caisses_vendues' => $totalCaissesVendues,
            'total_caisses_vides_recues' => $totalCaissesVidesRecues,
            'total_caisses_retournees' => $totalCaissesRetournees,
            'total_dette' => $totalDette,
        ];
    }
    
    /**
     * Récupérer le CA de tous les clients
     */
    public function getAllWithCA($dateDebut = null, $dateFin = null)
    {
        $whereDate = "";
        $params = [];
        
        if ($dateDebut && $dateFin) {
            $whereDate = "AND v.date_vente BETWEEN :date_debut AND :date_fin";
            $params['date_debut'] = $dateDebut;
            $params['date_fin'] = $dateFin;
        }
        
        return $this->db->fetchAll(
            "SELECT c.*, z.nom as zone_nom,
                    COALESCE(SUM(v.total_ttc), 0) as ca_total,
                    COUNT(v.id) as nb_ventes
             FROM {$this->table} c
             LEFT JOIN zones z ON c.zone_id = z.id
             LEFT JOIN ventes v ON c.id = v.client_id AND v.statut = 'validee' {$whereDate}
             WHERE c.actif = 1
             GROUP BY c.id
             ORDER BY ca_total DESC",
            $params
        );
    }
    
    /**
     * Récupérer les achats d'un client
     */
    public function getAchats($id, $limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT v.*, 
                    SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)) as total_caisses,
                    SUM(vd.quantite) as total_bouteilles,
                    GROUP_CONCAT(p.nom SEPARATOR ', ') as produits
             FROM ventes v
             JOIN vente_details vd ON v.id = vd.vente_id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.client_id = :id AND v.statut = 'validee'
             GROUP BY v.id
             ORDER BY v.date_vente DESC
             LIMIT :limit",
            ['id' => $id, 'limit' => $limit]
        );
    }
    
    /**
     * Récupérer les clients d'une zone avec stats
     */
    public function getByZoneWithStats($zoneId)
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    COALESCE(SUM(CASE WHEN MONTH(v.date_vente) = MONTH(CURRENT_DATE) 
                                  AND YEAR(v.date_vente) = YEAR(CURRENT_DATE) 
                                  THEN v.total_ttc ELSE 0 END), 0) as ca_mois
             FROM {$this->table} c
             LEFT JOIN ventes v ON c.id = v.client_id AND v.statut = 'validee'
             WHERE c.zone_id = :zone_id AND c.actif = 1
             GROUP BY c.id
             ORDER BY c.nom",
            ['zone_id' => $zoneId]
        );
    }
    
    /**
     * Récupérer les dettes d'emballages d'un client
     */
    public function getDettesEmballages($clientId)
    {
        $stats = $this->getEmballageStats($clientId);

        return ['total' => (int) ($stats['total_dette'] ?? 0)];
    }
}
