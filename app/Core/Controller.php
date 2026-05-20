<?php
/**
 * Classe Controller de base
 */

abstract class Controller
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->refreshSessionPermissions();
    }
    
    /**
     * Rendre une vue
     */
    protected function view($view, $data = [])
    {
        extract($data);
        $viewFile = ROOT_PATH . '/app/Views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            throw new Exception("Vue non trouvée : {$view}");
        }
    }
    
    /**
     * Renvoyer une réponse JSON
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Renvoyer une réponse de succès JSON
     */
    protected function success($data = null, $message = 'Opération réussie')
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Renvoyer une réponse d'erreur JSON
     */
    protected function error($message = 'Une erreur est survenue', $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Vérifier si la requête est AJAX
     */
    protected function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Récupérer les données POST JSON
     */
    protected function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Vérifier l'authentification
     */
    protected function requireAuth()
    {
        if (!isset($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                $this->error('Non autorisé', 401);
                exit;
            }
            redirect('login');
            exit;
        }
    }
    
    /**
     * Vérifier le rôle
     */
    protected function requireRole($roles)
    {
        $this->requireAuth();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($_SESSION['user_role'], $roles)) {
            if ($this->isAjax()) {
                return $this->error('Accès refusé', 403);
            }
            redirect('unauthorized');
        }
    }
    
    /**
     * Vérifier une permission granulaire
     */
    protected function requirePermission($permissionCode)
    {
        $this->requireAuth();
        
        $permissions = $_SESSION['user_permissions'] ?? [];
        if (!in_array($permissionCode, $permissions)) {
            if ($this->isAjax()) {
                return $this->error('Accès refusé - permission requise : ' . $permissionCode, 403);
            }
            redirect('unauthorized');
        }
        return true;
    }
    
    /**
     * Vérifier si l'utilisateur a une permission (sans redirection)
     */
    protected function hasPermission($permissionCode)
    {
        if (!isset($_SESSION['user_id'])) return false;
        return in_array($permissionCode, $_SESSION['user_permissions'] ?? []);
    }

    /**
     * Rafraîchir les permissions de l'utilisateur courant en session
     */
    protected function refreshSessionPermissions()
    {
        if (!isset($_SESSION['user_id'])) return;
        $roleModel = new Role();
        $_SESSION['user_permissions'] = $roleModel->getUserPermissionCodes($_SESSION['user_id']);
    }
    
    /**
     * Valider les données
     */
    protected function validate($data, $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $rulesArray = explode('|', $rule);
            
            foreach ($rulesArray as $r) {
                $r = trim($r);
                
                // Règle required
                if ($r === 'required') {
                    if (!isset($data[$field]) || $data[$field] === '') {
                        $errors[$field][] = "Le champ {$field} est obligatoire.";
                    }
                }
                
                // Règle email
                if ($r === 'email') {
                    if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "Le champ {$field} doit être une adresse email valide.";
                    }
                }
                
                // Règle min
                if (strpos($r, 'min:') === 0) {
                    $min = (int) substr($r, 4);
                    if (isset($data[$field]) && strlen($data[$field]) < $min) {
                        $errors[$field][] = "Le champ {$field} doit contenir au moins {$min} caractères.";
                    }
                }
                
                // Règle max
                if (strpos($r, 'max:') === 0) {
                    $max = (int) substr($r, 4);
                    if (isset($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field][] = "Le champ {$field} ne peut pas dépasser {$max} caractères.";
                    }
                }
                
                // Règle numeric
                if ($r === 'numeric') {
                    if (isset($data[$field]) && !is_numeric($data[$field])) {
                        $errors[$field][] = "Le champ {$field} doit être un nombre.";
                    }
                }
                
                // Règle unique
                if (strpos($r, 'unique:') === 0) {
                    $params = explode(',', substr($r, 7));
                    $table = trim($params[0]);
                    $column = isset($params[1]) ? trim($params[1]) : $field;
                    $ignoreId = isset($params[2]) ? trim($params[2]) : null;
                    
                    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
                    $queryParams = ['value' => $data[$field] ?? ''];
                    
                    if ($ignoreId) {
                        $sql .= " AND id != :ignoreId";
                        $queryParams['ignoreId'] = $ignoreId;
                    }
                    
                    if ($this->db->fetchColumn($sql, $queryParams) > 0) {
                        $errors[$field][] = "Cette valeur est déjà utilisée.";
                    }
                }
            }
        }
        
        return $errors;
    }
}
