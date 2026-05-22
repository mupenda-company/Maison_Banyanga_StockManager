<?php

class RoleController extends Controller
{
    private $roleModel;
    private $permissionModel;

    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    public function index()
    {
        $this->requirePermission('admin.roles');

        $roles = $this->roleModel->getAllWithPermissions();
        $permissionsGrouped = $this->permissionModel->getAllGroupedByModule();

        if ($this->isAjax()) {
            return $this->success(['roles' => $roles, 'permissionsGrouped' => $permissionsGrouped]);
        }

        $this->view('admin/roles', [
            'roles' => $roles,
            'permissionsGrouped' => $permissionsGrouped
        ]);
    }

    public function store()
    {
        $this->requirePermission('admin.roles');

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'nom' => 'required',
            'description' => ''
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        if ($this->roleModel->nomExists($data['nom'])) {
            return $this->error('Ce nom de rôle existe déjà', 422);
        }

        $id = $this->roleModel->create([
            'nom' => trim($data['nom']),
            'description' => trim($data['description'] ?? ''),
            'is_system' => 0
        ]);

        $permIds = $data['permissionIds'] ?? $data['permissions'] ?? [];
        if (!empty($permIds) && is_array($permIds)) {
            $this->roleModel->syncPermissions($id, $permIds);
        }

        $role = $this->roleModel->find($id);
        $role['permissions'] = $this->roleModel->getPermissionCodes($id);

        return $this->success($role, 'Rôle créé avec succès');
    }

    public function update($id)
    {
        $this->requirePermission('admin.roles');

        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('Rôle non trouvé', 404);
        }

        $data = $this->getJsonInput();

        if (isset($data['nom']) && $data['nom'] !== $role['nom']) {
            if ($this->roleModel->nomExists($data['nom'], $id)) {
                return $this->error('Ce nom de rôle existe déjà', 422);
            }
        }

        $updateData = array_intersect_key($data, array_flip(['nom', 'description']));
        if (!empty($updateData)) {
            $this->roleModel->update($id, $updateData);
        }

        $permIds = $data['permissionIds'] ?? $data['permissions'] ?? [];
        if (!empty($permIds) && is_array($permIds)) {
            $this->roleModel->syncPermissions($id, $permIds);
        }

        // Rafraîchir les permissions en session si l'utilisateur courant a ce rôle
        $this->refreshSessionPermissions();

        $role = $this->roleModel->find($id);
        $role['permissions'] = $this->roleModel->getPermissionCodes($id);
        $role['_session_permissions'] = $_SESSION['user_permissions'] ?? [];

        return $this->success($role, 'Rôle mis à jour avec succès');
    }

    public function delete($id)
    {
        $this->requirePermission('admin.roles');

        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('Rôle non trouvé', 404);
        }

        if ($role['is_system']) {
            return $this->error('Les rôles système ne peuvent pas être supprimés', 400);
        }

        $this->roleModel->delete($id);

        return $this->success(null, 'Rôle supprimé avec succès');
    }

    public function syncUserRoles($userId)
    {
        try {
            $this->requirePermission('admin.utilisateurs');

            $data = $this->getJsonInput();

            if (!isset($data['role_ids']) || !is_array($data['role_ids'])) {
                return $this->error('role_ids est requis', 422);
            }

            $this->roleModel->syncUserRoles($userId, $data['role_ids']);

            // Rafraîchir les permissions en session si l'utilisateur courant est modifié
            if ($userId == ($_SESSION['user_id'] ?? null)) {
                $this->refreshSessionPermissions();
            }

            $userModel = new User();
            $user = $userModel->find($userId);
            if (!$user) {
                return $this->error('Utilisateur non trouvé', 404);
            }

            $roles = $this->roleModel->getUserRoles($userId);
            $permissionCodes = $this->roleModel->getUserPermissionCodes($userId);

            return $this->success([
                'roles' => $roles,
                'permissions' => $permissionCodes,
                '_session_permissions' => $_SESSION['user_permissions'] ?? []
            ], 'Rôles mis à jour');
        } catch (Exception $e) {
            error_log('syncUserRoles error: ' . $e->getMessage());
            return $this->error('Erreur lors de la synchronisation des rôles: ' . $e->getMessage(), 500);
        }
    }

    public function permissions()
    {
        $this->requirePermission('admin.roles');

        $grouped = $this->permissionModel->getAllGroupedByModule();
        return $this->success($grouped);
    }
}
