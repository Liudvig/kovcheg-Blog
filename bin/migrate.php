<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__).'/app/bootstrap.php';

use Kovcheg\DB;

$pdo = DB::pdo();
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    batch INT NOT NULL DEFAULT 1,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$directory = dirname(__DIR__).'/migrations';
$files = glob($directory.'/*.sql') ?: [];
sort($files, SORT_STRING);

$currentBatch = (int)($pdo->query('SELECT COALESCE(MAX(batch),0) FROM migrations')->fetchColumn() ?: 0) + 1;
$applied = 0;

foreach ($files as $file) {
    $name = basename($file);
    $check = $pdo->prepare('SELECT id FROM migrations WHERE migration=? LIMIT 1');
    $check->execute([$name]);
    if ($check->fetchColumn()) {
        echo "SKIP  {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Migration is empty or unreadable: '.$name);
    }

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];

    try {
        // MySQL performs implicit commits around CREATE/ALTER statements. Wrapping
        // mixed DDL migrations in PDO transactions makes commit() fail even when
        // every statement succeeded, so migrations are applied sequentially and
        // recorded only after the complete file finishes without an exception.
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') $pdo->exec($statement);
        }

        $record = $pdo->prepare('INSERT INTO migrations (migration,batch,applied_at) VALUES (?,?,CURRENT_TIMESTAMP)');
        $record->execute([$name, $currentBatch]);
        $applied++;
        echo "APPLY {$name}\n";
    } catch (Throwable $error) {
        fwrite(STDERR, "FAIL  {$name}: {$error->getMessage()}\n");
        fwrite(STDERR, "The migration was not recorded. Idempotent statements may be safely retried after fixing the error.\n");
        exit(1);
    }
}

echo $applied > 0
    ? "DONE  Applied {$applied} migration(s), batch {$currentBatch}.\n"
    : "DONE  Database is up to date.\n";
