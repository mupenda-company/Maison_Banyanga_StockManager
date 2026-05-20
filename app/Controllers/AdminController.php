<?php
/**
 * Contrôleur d'administration
 */

class AdminController extends Controller
{
    private $userModel;
    private $parametreModel;
    private $produitModel;
    private $objectifProduitModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->parametreModel = new Parametre();
        $this->produitModel = new Produit();
        $this->objectifProduitModel = new ObjectifProduit();
    }
    
    /**
     * Tableau de bord admin
     */
    public function index()
    {
        $this->requirePermission('admin.view');
        
        $stats = [
            'users' => $this->userModel->count(),
            'produits' => (new Produit())->count(),
            'clients' => (new Client())->count(),
            'vehicules' => (new Vehicule())->count()
        ];
        
        $this->view('admin/index', [
            'stats' => $stats
        ]);
    }
    
    /**
     * Gestion des utilisateurs
     */
    public function users()
    {
        $this->requirePermission('admin.view');
        
        $users = $this->userModel->all('nom, prenom');
        $roleModel = new Role();
        
        // Ajouter les role_ids et role_names à chaque utilisateur
        foreach ($users as &$user) {
            $userRoles = $roleModel->getUserRoles($user['id']);
            $user['role_ids'] = array_column($userRoles, 'id');
            $user['role_names'] = array_column($userRoles, 'nom');
        }
        
        $this->view('admin/users', [
            'users' => $users
        ]);
    }
    
    /**
     * Créer un utilisateur
     */
    public function storeUser()
    {
        $this->requirePermission('admin.view');
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'username' => 'required|unique:users,username',
            'telephone' => 'required|unique:users,telephone',
            'password' => 'required|min:6',
            'nom' => 'required',
            'prenom' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        // Déduire le rôle legacy depuis les role_ids
        $roleModel = new Role();
        $legacyRole = 'vendeur';
        if (!empty($data['role_ids'])) {
            $assignedRoles = $roleModel->getUserRoles(0); // dummy
            $roleNames = [];
            foreach ($data['role_ids'] as $rid) {
                $r = $roleModel->find((int)$rid);
                if ($r) $roleNames[] = $r['nom'];
            }
            if (in_array('admin', $roleNames)) $legacyRole = 'admin';
            elseif (in_array('magasinier', $roleNames)) $legacyRole = 'magasinier';
            elseif (!empty($roleNames)) $legacyRole = $roleNames[0];
        }
        
        $userData = [
            'username' => trim($data['username']),
            'telephone' => trim($data['telephone']),
            'password' => $data['password'],
            'nom' => trim($data['nom']),
            'prenom' => trim($data['prenom']),
            'role' => $legacyRole,
            'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
        ];
        
        $id = $this->userModel->createUser($userData);
        
        if ($id) {
            $user = $this->userModel->find($id);
            return $this->success($user, 'Utilisateur créé avec succès');
        }
        
        return $this->error('Erreur lors de la création de l\'utilisateur');
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($id)
    {
        $this->requirePermission('admin.view');
        
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->error('Utilisateur non trouvé', 404);
        }
        
        $data = $this->getJsonInput();
        
        // Vérifier username unique
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            if ($this->userModel->usernameExists($data['username'], $id)) {
                return $this->error('Ce nom d\'utilisateur existe déjà', 422);
            }
        }
        
        // Vérifier téléphone unique
        if (isset($data['telephone']) && $data['telephone'] !== $user['telephone']) {
            if ($this->userModel->telephoneExists($data['telephone'], $id)) {
                return $this->error('Ce numéro de téléphone existe déjà', 422);
            }
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'username', 'telephone', 'nom', 'prenom', 'actif'
        ]));
        
        // Sync legacy role field from role_ids
        if (!empty($data['role_ids'])) {
            $roleModel = new Role();
            $roleNames = [];
            foreach ($data['role_ids'] as $rid) {
                $r = $roleModel->find((int)$rid);
                if ($r) $roleNames[] = $r['nom'];
            }
            if (in_array('admin', $roleNames)) $updateData['role'] = 'admin';
            elseif (in_array('magasinier', $roleNames)) $updateData['role'] = 'magasinier';
            elseif (!empty($roleNames)) $updateData['role'] = $roleNames[0];
        }
        
        // Mettre à jour le mot de passe si fourni
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $this->userModel->update($id, $updateData);
        
        $user = $this->userModel->find($id);
        
        // Ajouter role_ids et role_names à la réponse
        $roleModel = new Role();
        $userRoles = $roleModel->getUserRoles($id);
        $user['role_ids'] = array_column($userRoles, 'id');
        $user['role_names'] = array_column($userRoles, 'nom');
        
        return $this->success($user, 'Utilisateur mis à jour avec succès');
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        $this->requirePermission('admin.view');
        
        if ($id == $_SESSION['user_id']) {
            return $this->error('Vous ne pouvez pas supprimer votre propre compte', 400);
        }
        
        $this->userModel->update($id, ['actif' => 0]);
        
        return $this->success(null, 'Utilisateur désactivé avec succès');
    }
    
    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword($id)
    {
        $this->requirePermission('admin.view');
        
        $newPassword = substr(md5(uniqid()), 0, 8);
        $this->userModel->updatePassword($id, $newPassword);
        
        return $this->success(['password' => $newPassword], 'Mot de passe réinitialisé');
    }
    
    /**
     * Paramètres de l'application
     */
    public function settings()
    {
        $this->requirePermission('admin.view');
        
        $params = $this->parametreModel->getPersonnalisation();
        
        $this->view('admin/settings', [
            'params' => $params
        ]);
    }

    /**
     * Objectifs mensuels par produit
     */
    public function objectifs()
    {
        $this->requirePermission('admin.view');

        $periode = $_GET['periode'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $periode)) {
            $periode = date('Y-m');
        }

        [$annee, $mois] = array_map('intval', explode('-', $periode));
        $overview = $this->objectifProduitModel->getMonthlyOverview($annee, $mois);
        $produits = $this->produitModel->getActive();

        $objectifsParProduit = [];
        foreach ($overview['rows'] as $row) {
            $objectifsParProduit[(int) $row['produit_id']] = $row;
        }

        $this->view('admin/objectifs', [
            'periode' => $periode,
            'annee' => $annee,
            'mois' => $mois,
            'produits' => $produits,
            'objectifsParProduit' => $objectifsParProduit,
            'summary' => $overview['summary'],
        ]);
    }

    /**
     * Enregistrer les objectifs mensuels
     */
    public function storeObjectifs()
    {
        $this->requirePermission('admin.view');

        $data = $this->getJsonInput();
        if (empty($data)) {
            $data = $_POST;
        }

        $periode = $data['periode'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $periode)) {
            return $this->error('La période doit être au format AAAA-MM.', 422);
        }

        [$annee, $mois] = array_map('intval', explode('-', $periode));
        $objectifs = $data['objectifs'] ?? [];

        if (!is_array($objectifs)) {
            return $this->error('Les objectifs doivent être envoyés sous forme de liste.', 422);
        }

        try {
            $this->db->beginTransaction();

            foreach ($objectifs as $objectif) {
                if (!is_array($objectif) || !isset($objectif['produit_id'])) {
                    continue;
                }

                $this->objectifProduitModel->saveMonthlyObjective(
                    $objectif['produit_id'],
                    $annee,
                    $mois,
                    $objectif['objectif_caisses'] ?? 0,
                    $_SESSION['user_id'] ?? null
                );
            }

            $this->db->commit();

            return $this->success(null, 'Objectifs enregistrés avec succès');
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return $this->error('Impossible d’enregistrer les objectifs : ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Mettre à jour les paramètres
     */
    public function updateSettings()
    {
        $this->requirePermission('admin.view');
        
        $data = $this->getJsonInput();
        
        $allowedParams = [
            'nom_entreprise', 'logo', 'couleur_primaire',
            'adresse', 'telephone', 'email_contact',
            'contact', 'rccm', 'id_nat', 'nif', 'numero_compte',
            'devise', 'taux_change', 'taux_tva'
        ];
        
        foreach ($allowedParams as $param) {
            if (isset($data[$param])) {
                $this->parametreModel->set($param, $data[$param]);
            }
        }
        $this->parametreModel->set('devise_base', 'CDF');
        
        return $this->success(null, 'Paramètres mis à jour avec succès');
    }
    
    /**
     * Upload du logo
     */
    public function uploadLogo()
    {
        $this->requirePermission('admin.view');
        
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            return $this->error('Erreur lors de l\'upload', 400);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            return $this->error('Type de fichier non autorisé', 400);
        }
        
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $destination = UPLOADS_PATH . '/' . $filename;
        
        if (!is_dir(UPLOADS_PATH)) {
            mkdir(UPLOADS_PATH, 0755, true);
        }
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
            $this->parametreModel->set('logo', $filename);
            
            return $this->success([
                'filename' => $filename,
                'url' => asset('uploads/' . $filename)
            ], 'Logo uploadé avec succès');
        }
        
        return $this->error('Erreur lors de l\'enregistrement du fichier', 400);
    }
    
    /**
     * Récupérer le taux de change du jour
     */
    public function getTauxChange()
    {
        $this->requireAuth();
        
        // Taux de change actuel stocké en base
        $tauxActuel = floatval($this->parametreModel->get('taux_change', '2800'));
        
        // Pour un système réel, on pourrait appeler une API externe
        // Exemple avec une API de change (à configurer selon vos besoins)
        try {
            // Ici on peut intégrer une vraie API de taux de change
            // Pour l'instant, on retourne le taux stocké avec une légère variation simulée
            $taux = $tauxActuel;
            
            // Optionnel: Simuler une variation réaliste (±1%)
            // $variation = rand(-10, 10);
            // $taux = $tauxActuel + $variation;
            
            return $this->success([
                'taux' => $taux,
                'date' => date('Y-m-d H:i:s'),
                'source' => 'local'
            ], 'Taux de change récupéré');
        } catch (Exception $e) {
            return $this->success([
                'taux' => $tauxActuel,
                'date' => date('Y-m-d H:i:s'),
                'source' => 'fallback'
            ], 'Taux de change par défaut');
        }
    }
}
