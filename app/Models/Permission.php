<?php

class Permission extends Model
{
    protected $table = 'permissions';
    protected $fillable = ['code', 'module', 'action', 'description'];

    public function getAllGroupedByModule()
    {
        $all = $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY module, action"
        );
        $grouped = [];
        foreach ($all as $p) {
            $grouped[$p['module']][] = $p;
        }
        return $grouped;
    }

    public function codeExists($code, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE code = :code";
        $params = ['code' => $code];
        if ($excludeId) {
            $sql .= " AND id != :eid";
            $params['eid'] = $excludeId;
        }
        return $this->db->fetchColumn($sql, $params) > 0;
    }
}
