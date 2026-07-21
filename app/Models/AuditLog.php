<?php

class AuditLog
{
    public static function registerRequest(string $route): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        register_shutdown_function(static function () use ($route, $method): void {
            try {
                $db = Database::getInstance();
                $tableExists = (bool) $db->fetchColumn(
                    "SELECT COUNT(*) FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs'"
                );
                if (!$tableExists) return;

                $db->insert('audit_logs', [
                    'user_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
                    'methode' => $method,
                    'route' => substr($route, 0, 255),
                    'statut_http' => http_response_code() ?: 200,
                    'adresse_ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                    'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
                ]);
            } catch (Throwable $e) {
                error_log('Audit log error: ' . $e->getMessage());
            }
        });
    }
}
