<?php
/**
 * Classe Model de base
 */

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Récupérer tous les enregistrements
     */
    public function all($orderBy = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Trouver par ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * Trouver par un champ
     */
    public function findBy($field, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        return $this->db->fetch($sql, ['value' => $value]);
    }
    
    /**
     * Trouver tous par un champ
     */
    public function findAllBy($field, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        return $this->db->fetchAll($sql, ['value' => $value]);
    }
    
    /**
     * Créer un nouvel enregistrement
     */
    public function create($data)
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Mettre à jour un enregistrement
     */
    public function update($id, $data)
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        return $this->db->update($this->table, $data, "{$this->primaryKey} = :id", ['id' => $id]);
    }
    
    /**
     * Supprimer un enregistrement
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", ['id' => $id]);
    }
    
    /**
     * Compter les enregistrements
     */
    public function count($where = '1=1', $params = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";
        return (int) $this->db->fetchColumn($sql, $params);
    }
    
    /**
     * Requête personnalisée
     */
    public function query($sql, $params = [])
    {
        return $this->db->query($sql, $params);
    }
    
    /**
     * Récupérer avec pagination
     */
    public function paginate($page = 1, $perPage = 20, $where = '1=1', $params = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";
        $total = (int) $this->db->fetchColumn($countSql, $params);
        
        $sql = "SELECT * FROM {$this->table} WHERE {$where}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }
}
