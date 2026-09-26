<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $name = getenv('DB_NAME') ?: 'pharma_platform';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $dsn  = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB connect: ' . $e->getMessage());
                http_response_code(500);
                exit('Database connection error.');
            }
        }
        return self::$pdo;
    }
}

/** Shortcut helpers (safe since they always use prepared statements) */
function db(): PDO { return Database::pdo(); }

function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
function db_one(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row ?: null;
}
function db_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}
function db_insert(string $table, array $data): int {
    $cols = array_keys($data);
    $ph   = array_map(fn($c) => ':' . $c, $cols);
    $sql  = sprintf('INSERT INTO %s (%s) VALUES (%s)',
        $table, implode(',', $cols), implode(',', $ph));
    db_query($sql, $data);
    return (int) db()->lastInsertId();
}
function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $sets = implode(',', array_map(fn($c) => "$c = :$c", array_keys($data)));
    $sql  = "UPDATE $table SET $sets WHERE $where";
    return db_query($sql, array_merge($data, $whereParams))->rowCount();
}