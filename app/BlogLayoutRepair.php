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
        self::executeSql($sql);

        DB::run(
            'INSERT INTO migrations (migration,batch,applied_at) VALUES (?,1,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE applied_at=applied_at',
            [self::MIGRATION]
        );

        if (!self::storageReady()) {
            throw new RuntimeException('Таблицы Widget Engine не удалось восстановить полностью.');
        }
    }

    /**
     * Restores the minimum usable Portal Matrix without deleting custom widgets.
     * Existing user placements stay intact; only missing system widgets and
     * required default positions are created.
     */
    public static function repair(bool $force = false): array
    {
        self::ensure();

        $layout = DB::one("SELECT id FROM site_layouts WHERE slug='default' LIMIT 1");
        if (!$layout) {
            throw new RuntimeException('Основная схема сайта не найдена.');
        }
        $layoutId = (int)$layout['id'];

        $widgets = [
            'default-logo' => ['core.logo', 'Логотип и название', '{}'],
            'default-menu' => ['core.menu', 'Главное меню', '{"location":"header"}'],
            'default-account' => ['core.account', 'Профиль и вход', '{}'],
            'portal-default-categories' => ['core.categories', 'Рубрики', '{"limit":20}'],
            'portal-default-search' => ['core.search', 'Поиск', '{"placeholder":"Поиск по сайту"}'],
            'default-subscription' => ['core.subscription', 'Подписка', '{"title":"Получать обновления"}'],
            'portal-footer-note' => ['core.text', 'Информация в подвале', '{"text":"KOVCHEG CMS"}'],
        ];

        foreach ($widgets as $systemKey => [$type, $title, $settings]) {
            DB::run(
                'INSERT INTO site_widget_instances (system_key,widget_type,title,settings_json,is_enabled,created_at,updated_at)
                 VALUES (?,?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE widget_type=VALUES(widget_type),title=VALUES(title),is_enabled=1,updated_at=CURRENT_TIMESTAMP',
                [$systemKey,$type,$title,$settings]
            );
        }

        $placements = [
            'default-logo' => ['matrix.header.1',10],
            'default-menu' => ['matrix.header.3',20],
            'default-account' => ['matrix.header.5',30],
            'portal-default-categories' => ['matrix.left.1',10],
            'portal-default-search' => ['matrix.right.1',10],
            'default-subscription' => ['matrix.footer.2',10],
            'portal-footer-note' => ['matrix.footer.8',10],
        ];

        foreach ($placements as $systemKey => [$zone, $sortOrder]) {
            $widget = DB::one('SELECT id FROM site_widget_instances WHERE system_key=? LIMIT 1', [$systemKey]);
            if (!$widget) continue;
            $widgetId = (int)$widget['id'];

            if ($force) {
                DB::run(
                    "DELETE FROM site_widget_placements
                     WHERE layout_id=? AND widget_id=? AND zone IN ('header.main','footer.columns','layout.left','layout.right')",
                    [$layoutId,$widgetId]
                );
            }

            DB::run(
                'INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
                 VALUES (?,?,?,?,\'{}\',\'{}\',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order),updated_at=CURRENT_TIMESTAMP',
                [$layoutId,$widgetId,$zone,$sortOrder]
            );
        }

        Layout::bootCore();

        $result = self::diagnose();
        $result['matrix'] = [];
        foreach (['matrix.header.1','matrix.header.3','matrix.header.5','matrix.left.1','matrix.right.1','matrix.footer.2','matrix.footer.8'] as $zone) {
            $result['matrix'][$zone] = DB::one(
                'SELECT COUNT(*) c FROM site_widget_placements WHERE layout_id=? AND zone=?',
                [$layoutId,$zone]
            );
            $result['matrix'][$zone] = (int)($result['matrix'][$zone]['c'] ?? 0);
        }
        $result['ready'] = $result['ready'] && !in_array(0, $result['matrix'], true);

        return $result;
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

    private static function executeSql(string $sql): void
    {
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') DB::pdo()->exec($statement);
        }
    }
}
