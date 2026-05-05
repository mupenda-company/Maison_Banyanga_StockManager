<?php
/**
 * Modèle User
 */

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'telephone', 'password', 'nom', 'prenom', 'role', 'actif', 'derniere_connexion'];
    
    /**
     * Authentifier un utilisateur
     */
    public function authenticate($username, $password)
    {
        $user = $this->findBy('username', $username);
        
        if (!$user || !$user['actif']) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            // Mettre à jour la date de dernière connexion
            $this->update($user['id'], ['derniere_connexion' => date('Y-m-d H:i:s')]);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Créer un utilisateur avec mot de passe hashé
     */
    public function createUser($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->create($data);
    }
    
    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword($id, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $this->update($id, ['password' => $hashedPassword]);
    }
    
    /**
     * Récupérer les utilisateurs par rôle
     */
    public function getByRole($role)
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE role = :role ORDER BY nom, prenom",
            ['role' => $role]
        );
    }
    
    /**
     * Récupérer tous les utilisateurs actifs
     */
    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY nom, prenom"
        );
    }
    
    /**
     * Vérifier si un username existe
     */
    public function usernameExists($username, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];
        
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        return $this->db->fetchColumn($sql, $params) > 0;
    }
    
    /**
     * Vérifier si un numéro de téléphone existe
     */
    public function telephoneExists($telephone, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE telephone = :telephone";
        $params = ['telephone' => $telephone];
        
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        return $this->db->fetchColumn($sql, $params) > 0;
    }
}
