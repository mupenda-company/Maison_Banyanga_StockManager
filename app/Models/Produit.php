<?php
/**
 * Modèle Produit
 */

class Produit extends Model
{
    protected $table = 'produits';
    protected $fillable = [
        'code', 'nom', 'description', 'categorie', 'unite_base',
        'bouteilles_par_caisses', 'caisses_par_palette', 'famille_emballage',
        'prix_achat_unitaire', 'prix_achat_deposer', 'prix_achat_enlever',
        'prix_vente_unitaire', 'prix_vente_caisses', 'prix_emballage',
        'seuil_alerte', 'position_affichage', 'actif'
    ];

    private static bool $positionColumnChecked = false;
    private static bool $prixEmballageColumnChecked = false;
    private static bool $familleEmballageColumnChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensurePositionAffichageColumn();
        $this->ensurePrixEmballageColumn();
        $this->ensureFamilleEmballageColumn();
    }

    private function ensurePositionAffichageColumn(): void
    {
        if (self::$positionColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'produits'
               AND COLUMN_NAME = 'position_affichage'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE produits ADD position_affichage INT NOT NULL DEFAULT 999 AFTER seuil_alerte");
        }

        self::$positionColumnChecked = true;
    }

    private function ensurePrixEmballageColumn(): void
    {
        if (self::$prixEmballageColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'produits'
               AND COLUMN_NAME = 'prix_emballage'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE produits ADD prix_emballage DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER prix_vente_caisses");
        }

        self::$prixEmballageColumnChecked = true;
    }

    private function ensureFamilleEmballageColumn(): void
    {
        if (self::$familleEmballageColumnChecked) {
            return;
        }

        $exists = (bool) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'produits'
               AND COLUMN_NAME = 'famille_emballage'"
        );

        if (!$exists) {
            $this->db->query("ALTER TABLE produits ADD famille_emballage VARCHAR(80) NULL AFTER caisses_par_palette");
        }

        // Compatibilité initiale demandée. Les autres produits restent isolés
        // jusqu'à ce qu'une famille leur soit explicitement attribuée.
        $this->db->query(
            "UPDATE produits SET famille_emballage = 'STANDARD_72CL_12'
             WHERE (famille_emballage IS NULL OR famille_emballage = '')
               AND bouteilles_par_caisses = 12
               AND (UPPER(nom) LIKE '%PRIMUS%72%' OR UPPER(nom) LIKE '%TURBO%72%')"
        );
        $this->db->query(
            "UPDATE produits SET famille_emballage = 'STANDARD_50CL_20'
             WHERE (famille_emballage IS NULL OR famille_emballage = '')
               AND bouteilles_par_caisses = 20
               AND (UPPER(nom) LIKE '%PRIMUS%50%' OR UPPER(nom) LIKE '%TURBO%50%')"
        );
        $this->db->query(
            "UPDATE produits SET famille_emballage = 'CLASS_50CL_20'
             WHERE (famille_emballage IS NULL OR famille_emballage = '')
               AND bouteilles_par_caisses = 20
               AND UPPER(nom) LIKE '%CLASS%50%'"
        );
        $this->db->query(
            "UPDATE produits
             SET famille_emballage = CONCAT('PRODUIT_', id)
             WHERE famille_emballage IS NULL OR famille_emballage = ''"
        );

        self::$familleEmballageColumnChecked = true;
    }

    public function emballagesCompatibles(array $produit, array $source): bool
    {
        $familleProduit = trim((string) ($produit['famille_emballage'] ?? ''));
        $familleSource = trim((string) ($source['famille_emballage'] ?? ''));

        return $familleProduit !== ''
            && $familleProduit === $familleSource
            && (int) ($produit['bouteilles_par_caisses'] ?? 0) === (int) ($source['bouteilles_par_caisses'] ?? 0);
    }
    
    /**
     * Récupérer tous les produits actifs
     */
    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY position_affichage ASC, nom ASC"
        );
    }
    
    /**
     * Récupérer les produits avec leur stock global
     */
    public function getWithStock()
    {
        $emplacementModel = new Emplacement();
        $emplacementPrincipal = $emplacementModel->getPrincipal();
        $emplacementPrincipalId = (int) ($emplacementPrincipal['id'] ?? 0);

        return $this->db->fetchAll(
            "SELECT p.*, 
                    COALESCE(s.quantite_pleine, 0) as stock_plein,
                    COALESCE(s.quantite_vide, 0) as stock_vide,
                    COALESCE(s.caisses_pleine, 0) as stock_caisses_pleine,
                    COALESCE(s.caisses_vide, 0) as stock_caisses_vide,

                    COALESCE(s.quantite_pleine, 0) as stock_entrepot_plein,
                    COALESCE(s.quantite_vide, 0) as stock_entrepot_vide,
                    COALESCE(s.caisses_pleine, 0) as stock_entrepot_caisses,
                    COALESCE(s.caisses_vide, 0) as stock_entrepot_caisses_vide

            FROM {$this->table} p
            LEFT JOIN stocks s 
                ON p.id = s.produit_id 
            AND s.emplacement_id = :emplacement_id
            WHERE p.actif = 1
            ORDER BY p.position_affichage ASC, p.nom ASC",
            [
                'emplacement_id' => $emplacementPrincipalId
            ]
        );
    }
    
    /**
     * Récupérer les produits en alerte (stock sous le seuil)
     */
    public function getAlertProducts()
    {
        return $this->db->fetchAll(
            "SELECT p.*, 
                    COALESCE(SUM(s.quantite_pleine), 0) as stock_plein,
                    p.seuil_alerte
             FROM {$this->table} p
             LEFT JOIN stocks s ON p.id = s.produit_id
             WHERE p.actif = 1
             GROUP BY p.id
             HAVING stock_plein < p.seuil_alerte
             ORDER BY stock_plein ASC"
        );
    }
    
    /**
     * Récupérer par catégorie
     */
    public function getByCategory($categorie)
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE categorie = :categorie AND actif = 1 ORDER BY position_affichage ASC, nom ASC",
            ['categorie' => $categorie]
        );
    }
    
    /**
     * Récupérer toutes les catégories
     */
    public function getCategories()
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT categorie FROM {$this->table} WHERE categorie IS NOT NULL ORDER BY categorie"
        );
    }
    
    /**
     * Vérifier si un code existe
     */
    public function codeExists($code, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE code = :code";
        $params = ['code' => $code];
        
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        return $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Top ventes produits
     */
    public function getTopVentes($dateDebut, $dateFin, $limit = 5)
    {
        return $this->db->fetchAll(
            "SELECT p.nom, p.code, SUM(vd.quantite) as total_bouteilles, SUM(ROUND(vd.quantite / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24), 0)) as total_caisses, SUM(vd.sous_total) as total_ca
             FROM vente_details vd
             JOIN ventes v ON vd.vente_id = v.id
             JOIN produits p ON vd.produit_id = p.id
             WHERE v.statut = 'validee' AND v.date_vente BETWEEN :d1 AND :d2
             GROUP BY p.id
             ORDER BY total_ca DESC
             LIMIT :limit",
            ['d1' => $dateDebut, 'd2' => $dateFin, 'limit' => (int)$limit]
        );
    }
}
