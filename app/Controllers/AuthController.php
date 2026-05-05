<?php
/**
 * Contrôleur d'authentification
 */

class AuthController extends Controller
{
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Afficher le formulaire de connexion
     */
    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        
        $this->view('auth/login');
    }
    
    /**
     * Traiter la connexion
     */
    public function authenticate()
    {
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'username' => 'required',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        $user = $this->userModel->authenticate($data['username'], $data['password']);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_telephone'] = $user['telephone'];
            
            return $this->success([
                'redirect' => url('dashboard')
            ], 'Connexion réussie');
        }
        
        return $this->error('Identifiants invalides', 401);
    }
    
    /**
     * Déconnexion
     */
    public function logout()
    {
        session_destroy();
        redirect('login');
    }
    
    /**
     * Afficher le formulaire de changement de mot de passe
     */
    public function changePassword()
    {
        $this->requireAuth();
        $this->view('auth/change-password');
    }
    
    /**
     * Traiter le changement de mot de passe
     */
    public function updatePassword()
    {
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'current_password' => 'required',
            'new_password' => 'required|min:6'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        // La validation du mot de passe de confirmation est faite côté client
        
        $user = $this->userModel->find($_SESSION['user_id']);
        
        if (!password_verify($data['current_password'], $user['password'])) {
            return $this->error('Mot de passe actuel incorrect', 422);
        }
        
        $this->userModel->updatePassword($_SESSION['user_id'], $data['new_password']);
        
        return $this->success(null, 'Mot de passe modifié avec succès');
    }
    
    /**
     * Profil utilisateur
     */
    public function profile()
    {
        $this->requireAuth();
        $user = $this->userModel->find($_SESSION['user_id']);
        $this->view('auth/profile', ['user' => $user]);
    }
    
    /**
     * Mettre à jour le profil
     */
    public function updateProfile()
    {
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        $errors = $this->validate($data, [
            'nom' => 'required',
            'prenom' => 'required',
            'telephone' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }
        
        // Vérifier si le téléphone existe déjà
        if ($this->userModel->telephoneExists($data['telephone'], $_SESSION['user_id'])) {
            return $this->error('Ce numéro de téléphone est déjà utilisé', 422);
        }

        $this->userModel->update($_SESSION['user_id'], [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone']
        ]);

        $_SESSION['user_nom'] = $data['nom'];
        $_SESSION['user_prenom'] = $data['prenom'];
        $_SESSION['user_telephone'] = $data['telephone'];
        
        return $this->success(null, 'Profil mis à jour avec succès');
    }
}
