<?php
/**
 * Classe de connexion à la base de données via PDO
 */

class Database
{
    private static $instance = null;
    private $pdo;
    
    private function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Erreur de connexion : " . $e->getMessage());
            }
            die("Erreur de connexion à la base de données.");
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->pdo;
    }
    
    /**
     * Exécuter une requête préparée
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        if (!empty($params) && strpos($sql, '?') === false) {
            preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sql, $matches);
            if (!empty($matches[1])) {
                $allowed = array_flip(array_unique($matches[1]));
                $params = array_intersect_key($params, $allowed);
            }
        }
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Récupérer tous les résultats
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Récupérer un seul résultat
     */
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Récupérer une seule colonne
     */
    public function fetchColumn($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }
    
    /**
     * Insérer des données
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Mettre à jour des données
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        if (empty($data)) {
            return 0; // Rien à mettre à jour
        }
        
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClauses);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Supprimer des données
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Commencer une transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    /**
     * Annuler une transaction
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Vérifier si une transaction est active
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }
}
