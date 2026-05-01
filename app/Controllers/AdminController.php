<?php
/**
 * Contrôleur d'administration
 */

class AdminController extends Controller
{
    private $userModel;
    private $parametreModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->parametreModel = new Parametre();
    }
    
    /**
     * Tableau de bord admin
     */
    public function index()
    {
        $this->requireRole([ROLE_ADMIN]);
        
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
        $this->requireRole([ROLE_ADMIN]);
        
        $users = $this->userModel->all('nom, prenom');
        
        $this->view('admin/users', [
            'users' => $users
        ]);
    }
    
    /**
     * Créer un utilisateur
     */
    public function storeUser()
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nom' => 'required',
            'prenom' => 'required',
            'role' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $userData = [
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'password' => $data['password'],
            'nom' => trim($data['nom']),
            'prenom' => trim($data['prenom']),
            'role' => $data['role'],
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
        $this->requireRole([ROLE_ADMIN]);
        
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
        
        // Vérifier email unique
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if ($this->userModel->emailExists($data['email'], $id)) {
                return $this->error('Cet email existe déjà', 422);
            }
        }
        
        $updateData = array_intersect_key($data, array_flip([
            'username', 'email', 'nom', 'prenom', 'role', 'actif'
        ]));
        
        // Mettre à jour le mot de passe si fourni
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $this->userModel->update($id, $updateData);
        
        $user = $this->userModel->find($id);
        return $this->success($user, 'Utilisateur mis à jour avec succès');
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        
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
        $this->requireRole([ROLE_ADMIN]);
        
        $newPassword = substr(md5(uniqid()), 0, 8);
        $this->userModel->updatePassword($id, $newPassword);
        
        return $this->success(['password' => $newPassword], 'Mot de passe réinitialisé');
    }
    
    /**
     * Paramètres de l'application
     */
    public function settings()
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $params = $this->parametreModel->getPersonnalisation();
        
        $this->view('admin/settings', [
            'params' => $params
        ]);
    }
    
    /**
     * Mettre à jour les paramètres
     */
    public function updateSettings()
    {
        $this->requireRole([ROLE_ADMIN]);
        
        $data = $this->getJsonInput();
        
        $allowedParams = [
            'nom_entreprise', 'logo', 'couleur_primaire',
            'adresse', 'telephone', 'email_contact',
            'devise', 'taux_change', 'taux_tva'
        ];
        
        foreach ($allowedParams as $param) {
            if (isset($data[$param])) {
                $this->parametreModel->set($param, $data[$param]);
            }
        }
        
        return $this->success(null, 'Paramètres mis à jour avec succès');
    }
    
    /**
     * Upload du logo
     */
    public function uploadLogo()
    {
        $this->requireRole([ROLE_ADMIN]);
        
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
