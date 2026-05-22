<?php
/**
 * Contrôleur des produits
 */

class ProduitController extends Controller
{
    private $produitModel;
    private $stockModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->produitModel = new Produit();
        $this->stockModel = new Stock();
    }
    
    /**
     * Liste des produits
     */
    public function index()
    {
        $this->requirePermission('produits.voir');
        $produits = $this->produitModel->getWithStock();
        $categories = $this->produitModel->getCategories();
        
        $this->view('produits/index', [
            'produits' => $produits,
            'categories' => $categories
        ]);
    }
    
    /**
     * API liste des produits
     */
    public function apiList()
    {
        $this->requireAuth();
        
        $actifsOnly = isset($_GET['actifs']) && $_GET['actifs'] === 'true';
        $withStock = isset($_GET['with_stock']) && $_GET['with_stock'] === 'true';
        
        if ($withStock) {
            $produits = $this->produitModel->getWithStock();
        } else {
            $produits = $actifsOnly ? $this->produitModel->getActive() : $this->produitModel->all();
        }
        
        return $this->success($produits);
    }
    
    /**
     * Générer un code produit automatique
     */
    public function nextCode()
    {
        $this->requireAuth();
        
        // Récupérer le dernier code produit
        $lastCode = $this->db->fetchColumn(
            "SELECT code FROM produits WHERE code LIKE 'PRD-%' ORDER BY id DESC LIMIT 1"
        );
        
        if ($lastCode) {
            // Extraire le numéro et incrémenter
            $numero = (int) substr($lastCode, 4) + 1;
            $newCode = 'PRD-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
        } else {
            $newCode = 'PRD-0001';
        }
        
        return $this->success(['code' => $newCode]);
    }
    
    /**
     * Afficher un produit
     */
    public function show($id)
    {
        $this->requirePermission('produits.voir');
        
        $produit = $this->produitModel->find($id);
        
        if (!$produit) {
            return $this->error('Produit non trouvé', 404);
        }
        
        // Récupérer le stock par emplacement
        $stocks = $this->db->fetchAll(
            "SELECT s.*, e.nom as emplacement_nom, e.type as emplacement_type
             FROM stocks s
             JOIN emplacements e ON s.emplacement_id = e.id
             WHERE s.produit_id = :produit_id AND e.actif = 1
             ORDER BY e.type, e.nom",
            ['produit_id' => $id]
        );
        
        if ($this->isAjax()) {
            return $this->success([
                'produit' => $produit,
                'stocks' => $stocks
            ]);
        }
        
        $this->view('produits/show', [
            'produit' => $produit,
            'stocks' => $stocks
        ]);
    }
    
    /**
     * Créer un produit
     */
    public function store()
    {
        $this->requirePermission('produits.creer');
        
        $data = $this->getJsonInput();
        
        // Générer le code automatiquement s'il n'est pas fourni
        if (empty($data['code'])) {
            $data['code'] = $this->generateProductCode();
        }
        
        $errors = $this->validate($data, [
            'code' => 'required|unique:produits,code',
            'nom' => 'required',
            'prix_achat_unitaire' => 'required|numeric',
            'prix_achat_deposer' => 'numeric',
            'prix_achat_enlever' => 'numeric',
            'prix_vente_unitaire' => 'required|numeric',
            'bouteilles_par_caisses' => 'required|numeric',
            'seuil_alerte' => 'numeric'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $id = $this->produitModel->create([
            'code' => $data['code'],
            'nom' => $data['nom'],
            'description' => $data['description'] ?? '',
            'categorie' => $data['categorie'] ?? null,
            'unite_base' => $data['unite_base'] ?? 'bouteille',
            'bouteilles_par_caisses' => $data['bouteilles_par_caisses'],
            'prix_achat_unitaire' => $data['prix_achat_enlever'] ?? $data['prix_achat_unitaire'],
            'prix_achat_deposer' => $data['prix_achat_deposer'] ?? 0,
            'prix_achat_enlever' => $data['prix_achat_enlever'] ?? 0,
            'prix_vente_unitaire' => $data['prix_vente_unitaire'],
            'prix_vente_caisses' => $data['prix_vente_caisses'] ?? 0,
            'seuil_alerte' => $data['seuil_alerte'] ?? DEFAULT_ALERT_THRESHOLD,
            'actif' => 1
        ]);
        
        return $this->success(['id' => $id, 'code' => $data['code']], 'Produit créé avec succès');
    }
    
    /**
     * Générer un code produit unique
     */
    private function generateProductCode()
    {
        $lastCode = $this->db->fetchColumn(
            "SELECT code FROM produits WHERE code LIKE 'PRD-%' ORDER BY id DESC LIMIT 1"
        );
        
        if ($lastCode) {
            $numero = (int) substr($lastCode, 4) + 1;
            return 'PRD-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
        }
        
        return 'PRD-0001';
    }
    
    /**
     * Mettre à jour un produit
     */
    public function update($id)
    {
        $this->requirePermission('produits.creer');
        
        $produit = $this->produitModel->find($id);
        
        if (!$produit) {
            return $this->error('Produit non trouvé', 404);
        }
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'nom' => 'required',
            'prix_achat_unitaire' => 'numeric',
            'prix_achat_deposer' => 'numeric',
            'prix_achat_enlever' => 'numeric',
            'prix_vente_unitaire' => 'numeric'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        // Vérifier le code unique si modifié
        if (isset($data['code']) && $data['code'] !== $produit['code']) {
            if ($this->produitModel->codeExists($data['code'], $id)) {
                return $this->error('Ce code existe déjà', 422);
            }
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'code', 'nom', 'description', 'categorie', 'unite_base',
            'bouteilles_par_caisses', 'prix_achat_deposer', 'prix_achat_enlever', 'prix_vente_unitaire',
            'prix_vente_caisses', 'seuil_alerte'
        ]));
        
        if (isset($data['prix_achat_enlever'])) {
            $updateData['prix_achat_unitaire'] = $data['prix_achat_enlever'];
        }
        
        $this->produitModel->update($id, $updateData);
        
        return $this->success(null, 'Produit mis à jour avec succès');
    }
    
    /**
     * Supprimer un produit définitivement
     */
    public function delete($id)
    {
        $this->requirePermission('produits.supprimer');
        
        $produit = $this->produitModel->find($id);
        
        if (!$produit) {
            return $this->error('Produit non trouvé', 404);
        }
        
        // Sécurité : Vérifier s'il y a du stock ou des mouvements
        $hasStock = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM stocks WHERE produit_id = :id AND (quantite_pleine > 0 OR quantite_vide > 0)",
            ['id' => $id]
        );
        
        if ($hasStock > 0) {
            return $this->error('Impossible de supprimer ce produit car il reste du stock. Videz le stock d\'abord.', 400);
        }

        // Vérifier s'il y a des ventes ou approvisionnements liés
        $hasLinks = $this->db->fetchColumn(
            "SELECT (SELECT COUNT(*) FROM vente_details WHERE produit_id = :id) + 
                    (SELECT COUNT(*) FROM approvisionnement_details WHERE produit_id = :id_appro)",
            [
                'id' => $id,
                'id_appro' => $id,
            ]
        );

        if ($hasLinks > 0) {
            // Si le produit a un historique, on le désactive seulement pour ne pas casser la base
            $this->produitModel->update($id, ['actif' => 0]);
            return $this->success(null, 'Produit désactivé (conservé en historique car il possède des factures liées)');
        }
        
        // Suppression réelle si aucune liaison
        $this->db->query("DELETE FROM produits WHERE id = :id", ['id' => $id]);
        
        return $this->success(null, 'Produit supprimé définitivement');
    }
}
