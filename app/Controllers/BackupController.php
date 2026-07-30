<?php

class BackupController extends Controller
{
    public function index()
    {
        $this->requirePermission('admin.backup');

        $_SESSION['backup_download_token'] = bin2hex(random_bytes(32));

        $databaseInfo = $this->db->fetch(
            "SELECT COUNT(*) AS nb_tables,
                    COALESCE(SUM(table_rows), 0) AS nb_lignes_estime,
                    COALESCE(SUM(data_length + index_length), 0) AS taille_octets
             FROM information_schema.tables
             WHERE table_schema = :database_name",
            ['database_name' => DB_NAME]
        );

        $this->view('admin/backup', [
            'databaseInfo' => $databaseInfo ?: [],
            'downloadToken' => $_SESSION['backup_download_token'],
        ]);
    }

    public function download()
    {
        $this->requirePermission('admin.backup');

        $tokenSession = (string) ($_SESSION['backup_download_token'] ?? '');
        $tokenRecu = (string) ($_POST['backup_token'] ?? '');
        if ($tokenSession === '' || $tokenRecu === '' || !hash_equals($tokenSession, $tokenRecu)) {
            http_response_code(419);
            exit('La demande de sauvegarde a expire. Rechargez la page puis recommencez.');
        }
        unset($_SESSION['backup_download_token']);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $filename = 'backup_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', DB_NAME)
            . '_' . date('Y-m-d_H-i-s') . '.sql';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $pdo = $this->db->getConnection();
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

        $this->write("-- Sauvegarde de la base Bralima Logistique\n");
        $this->write('-- Generee le ' . date('Y-m-d H:i:s T') . "\n");
        $this->write("-- Cette sauvegarde contient des donnees confidentielles.\n\n");
        $this->write("SET NAMES utf8mb4;\n");
        $this->write("SET FOREIGN_KEY_CHECKS=0;\n");
        $this->write("SET UNIQUE_CHECKS=0;\n");
        $this->write("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
        $this->write("START TRANSACTION;\n\n");

        $objects = $this->db->fetchAll('SHOW FULL TABLES');
        $tables = [];
        $views = [];
        foreach ($objects as $object) {
            $values = array_values($object);
            $name = (string) ($values[0] ?? '');
            $type = strtoupper((string) ($values[1] ?? 'BASE TABLE'));
            if ($name === '') {
                continue;
            }
            if ($type === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $createRow = $this->db->fetch('SHOW CREATE TABLE ' . $quotedTable);
            $createSql = (string) ($createRow['Create Table'] ?? array_values($createRow ?: [])[1] ?? '');

            $this->write("-- --------------------------------------------------------\n");
            $this->write('-- Structure de la table ' . $quotedTable . "\n");
            $this->write('DROP TABLE IF EXISTS ' . $quotedTable . ";\n");
            $this->write($createSql . ";\n\n");

            $columns = $this->db->fetchAll('SHOW COLUMNS FROM ' . $quotedTable);
            $insertableColumns = [];
            foreach ($columns as $column) {
                if (stripos((string) ($column['Extra'] ?? ''), 'GENERATED') !== false) {
                    continue;
                }
                $insertableColumns[] = (string) $column['Field'];
            }

            if (empty($insertableColumns)) {
                continue;
            }

            $quotedColumns = array_map([$this, 'quoteIdentifier'], $insertableColumns);
            $statement = $pdo->query(
                'SELECT ' . implode(', ', $quotedColumns) . ' FROM ' . $quotedTable
            );

            $batch = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $values = [];
                foreach ($insertableColumns as $column) {
                    $values[] = $this->quoteValue($pdo, $row[$column] ?? null);
                }
                $batch[] = '(' . implode(', ', $values) . ')';

                if (count($batch) >= 100) {
                    $this->writeInsertBatch($quotedTable, $quotedColumns, $batch);
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $this->writeInsertBatch($quotedTable, $quotedColumns, $batch);
            }
            $statement->closeCursor();
            $this->write("\n");
        }

        foreach ($views as $view) {
            $quotedView = $this->quoteIdentifier($view);
            $createRow = $this->db->fetch('SHOW CREATE VIEW ' . $quotedView);
            $createSql = (string) ($createRow['Create View'] ?? array_values($createRow ?: [])[1] ?? '');

            $this->write("-- --------------------------------------------------------\n");
            $this->write('-- Structure de la vue ' . $quotedView . "\n");
            $this->write('DROP VIEW IF EXISTS ' . $quotedView . ";\n");
            $this->write($createSql . ";\n\n");
        }

        $this->write("COMMIT;\n");
        $this->write("SET UNIQUE_CHECKS=1;\n");
        $this->write("SET FOREIGN_KEY_CHECKS=1;\n");
        $this->write("-- Fin de la sauvegarde\n");
        $pdo->exec('COMMIT');
        exit;
    }

    private function writeInsertBatch(string $table, array $columns, array $rows): void
    {
        $this->write(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ") VALUES\n"
            . implode(",\n", $rows) . ";\n"
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(PDO $pdo, $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return $pdo->quote((string) $value);
    }

    private function write(string $content): void
    {
        echo $content;
        if (function_exists('flush')) {
            flush();
        }
    }
}
