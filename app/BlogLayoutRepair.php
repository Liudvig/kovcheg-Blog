<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\DB;
use RuntimeException;
use Throwable;

final class LayoutRepair
{
    private const MIGRATION = '20260722_blog_layout_widgets.sql';
    private const TABLES = [
        'site_layouts',
        'site_widget_instances',
        'site_widget_placements',
        'site_layout_revisions',
    ];

    public static function ensure(): void
    {
        if (self::storageReady()) return;

        $file = BASE_PATH.'/migrations/'.self::MIGRATION;
        if (!is_file($file) || !is_readable($file)) {
            throw new RuntimeException('Не найден файл восстановления Widget Engine: '.self::MIGRATION);
        }

        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Файл восстановления Widget Engine пуст или недоступен.');
        }

        self::ensureMigrationTable();
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') DB::pdo()->exec($statement);
        }

        DB::run(
            'INSERT INTO migrations (migration,batch,applied_at) VALUES (?,1,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE applied_at=applied_at',
            [self::MIGRATION]
        );

        if (!self::storageReady()) {
            throw new RuntimeException('Таблицы Widget Engine не удалось восстановить полностью.');
        }
    }

    public static function diagnose(): array
    {
        $result = ['ready'=>false,'tables'=>[],'error'=>''];
        try {
            foreach (self::TABLES as $table) {
                $result['tables'][$table] = self::tableExists($table);
            }
            $result['ready'] = !in_array(false, $result['tables'], true) && self::seedReady();
        } catch (Throwable $error) {
            $result['error'] = $error->getMessage();
        }
        return $result;
    }

    private static function storageReady(): bool
    {
        foreach (self::TABLES as $table) if (!self::tableExists($table)) return false;
        return self::seedReady();
    }

    private static function tableExists(string $table): bool
    {
        $row = DB::one(
            'SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?',
            [$table]
        );
        return (int)($row['c'] ?? 0) === 1;
    }

    private static function seedReady(): bool
    {
        if (!self::tableExists('site_layouts') || !self::tableExists('site_widget_instances')) return false;
        $layout = DB::one("SELECT id FROM site_layouts WHERE slug='default' LIMIT 1");
        $widgets = DB::one("SELECT COUNT(*) c FROM site_widget_instances WHERE system_key IN ('default-logo','default-menu','default-account','default-subscription')");
        return $layout !== null && (int)($widgets['c'] ?? 0) === 4;
    }

    private static function ensureMigrationTable(): void
    {
        DB::pdo()->exec("CREATE TABLE IF NOT EXISTS migrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(190) NOT NULL UNIQUE,
            batch INT NOT NULL DEFAULT 1,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
