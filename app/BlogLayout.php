<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;
use RuntimeException;
use Throwable;

final class Layout
{
    private static bool $booted = false;
    private static array $zones = [];
    private static array $widgets = [];

    public static function bootCore(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        foreach ([
            'header.top' => ['label'=>'Верхняя полоса','width'=>'wide'],
            'header.main' => ['label'=>'Основная шапка','width'=>'wide'],
            'header.bottom' => ['label'=>'Нижняя часть шапки','width'=>'wide'],
            'page.before' => ['label'=>'Над страницей','width'=>'wide'],
            'layout.left' => ['label'=>'Левая колонка','width'=>'sidebar'],
            'content.before' => ['label'=>'Над содержимым','width'=>'content'],
            'content.main' => ['label'=>'Основное содержимое','width'=>'content','reserved'=>true],
            'content.after' => ['label'=>'Под содержимым','width'=>'content'],
            'layout.right' => ['label'=>'Правая колонка','width'=>'sidebar'],
            'page.after' => ['label'=>'Под страницей','width'=>'wide'],
            'footer.top' => ['label'=>'Верх подвала','width'=>'wide'],
            'footer.columns' => ['label'=>'Колонки подвала','width'=>'wide'],
            'footer.bottom' => ['label'=>'Низ подвала','width'=>'wide'],
        ] as $id => $definition) self::registerZone($id, $definition);

        self::registerWidget('core.logo', [
            'label'=>'Логотип и название',
            'description'=>'Логотип, название и краткая подпись сайта.',
            'defaults'=>['show_tagline'=>1],
            'fields'=>[
                'show_tagline'=>['label'=>'Показывать подпись','type'=>'checkbox'],
            ],
            'render'=>static fn(array $settings, array $context, array $instance): string => self::renderLogo($settings),
        ]);
        self::registerWidget('core.menu', [
            'label'=>'Меню',
            'description'=>'Любое созданное меню в шапке, колонке или подвале.',
            'defaults'=>['menu_id'=>0,'location'=>'header'],
            'fields'=>[
                'menu_id'=>['label'=>'Меню','type'=>'menu'],
                'orientation'=>['label'=>'Ориентация','type'=>'select','options'=>['auto'=>'Авто','horizontal'=>'Горизонтально','vertical'=>'Вертикально']],
            ],
            'render'=>static fn(array $settings, array $context, array $instance): string => self::renderMenu($settings, $instance),
        ]);
        self::registerWidget('core.search', [
            'label'=>'Поиск',
            'description'=>'Форма поиска. Виджет можно полностью убрать или перенести.',
            'defaults'=>['placeholder'=>'Поиск по сайту'],
            'fields'=>[
                'placeholder'=>['label'=>'Подсказка','type'=>'text','maxlength'=>120],
            ],
            'render'=>static fn(array $settings): string => self::renderSearch($settings),
        ]);
        self::registerWidget('core.account', [
            'label'=>'Профиль и вход',
            'description'=>'Ссылки профиля, Studio, входа и регистрации.',
            'defaults'=>[],
            'fields'=>[],
            'render'=>static fn(): string => self::renderAccount(),
        ]);
        self::registerWidget('core.text', [
            'label'=>'Текстовый блок',
            'description'=>'Безопасный текст для колонки, шапки или подвала.',
            'defaults'=>['text'=>''],
            'fields'=>[
                'text'=>['label'=>'Текст','type'=>'textarea','maxlength'=>5000],
            ],
            'render'=>static fn(array $settings): string => self::renderText($settings),
        ]);
        self::registerWidget('core.image', [
            'label'=>'Изображение',
            'description'=>'Изображение со ссылкой и ALT-текстом.',
            'defaults'=>['url'=>'','alt'=>'','link'=>''],
            'fields'=>[
                'url'=>['label'=>'Адрес изображения','type'=>'url','maxlength'=>1000],
                'alt'=>['label'=>'ALT-текст','type'=>'text','maxlength'=>300],
                'link'=>['label'=>'Ссылка при нажатии','type'=>'url','maxlength'=>1000],
            ],
            'render'=>static fn(array $settings): string => self::renderImage($settings),
        ]);
        self::registerWidget('core.latest-posts', [
            'label'=>'Последние публикации',
            'description'=>'Автоматический список новых записей.',
            'defaults'=>['limit'=>5],
            'fields'=>[
                'limit'=>['label'=>'Количество','type'=>'number','min'=>1,'max'=>20],
            ],
            'render'=>static fn(array $settings): string => self::renderLatestPosts($settings),
        ]);
        self::registerWidget('core.categories', [
            'label'=>'Рубрики',
            'description'=>'Список рубрик с количеством материалов.',
            'defaults'=>['limit'=>20],
            'fields'=>[
                'limit'=>['label'=>'Количество','type'=>'number','min'=>1,'max'=>100],
            ],
            'render'=>static fn(array $settings): string => self::renderCategories($settings),
        ]);
        self::registerWidget('core.subscription', [
            'label'=>'Подписка',
            'description'=>'Форма подписки на новые публикации.',
            'defaults'=>['title'=>'Получать новые публикации'],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
            ],
            'render'=>static fn(array $settings): string => self::renderSubscription($settings),
        ]);
        self::registerWidget('core.social-links', [
            'label'=>'Социальные ссылки',
            'description'=>'Ссылки на социальные сети и внешние площадки.',
            'defaults'=>['links'=>''],
            'fields'=>[
                'links'=>['label'=>'По одной строке: Название | URL','type'=>'textarea','maxlength'=>5000],
            ],
            'render'=>static fn(array $settings): string => self::renderSocialLinks($settings),
        ]);
    }

    public static function registerZone(string $id, array $definition): void
    {
        if (!preg_match('/^[a-z][a-z0-9_.-]{2,119}$/', $id)) throw new RuntimeException('Некорректный идентификатор зоны: '.$id);
        self::$zones[$id] = array_merge(['label'=>$id,'width'=>'auto','reserved'=>false], $definition, ['id'=>$id]);
    }

    public static function registerWidget(string $type, array $definition): void
    {
        if (!preg_match('/^[a-z][a-z0-9_.-]{2,119}$/', $type)) throw new RuntimeException('Некорректный тип виджета: '.$type);
        if (!isset($definition['render']) || !is_callable($definition['render'])) throw new RuntimeException('Виджет '.$type.' не содержит renderer.');
        self::$widgets[$type] = array_merge([
            'label'=>$type,
            'description'=>'',
            'defaults'=>[],
            'fields'=>[],
            'module'=>'core',
        ], $definition, ['type'=>$type]);
    }

    public static function zones(): array
    {
        self::bootCore();
        return self::$zones;
    }

    public static function widgetTypes(): array
    {
        self::bootCore();
        return self::$widgets;
    }

    public static function widgetType(string $type): ?array
    {
        self::bootCore();
        return self::$widgets[$type] ?? null;
    }

    public static function publishedLayout(string $contextType = 'default'): ?array
    {
        try {
            return DB::one("SELECT * FROM site_layouts WHERE status='published' AND context_type IN (?, 'default') ORDER BY (context_type=?) DESC,id LIMIT 1", [$contextType,$contextType]);
        } catch (Throwable) {
            return null;
        }
    }

    public static function renderZone(string $zone, array $context = []): string
    {
        self::bootCore();
        if (!isset(self::$zones[$zone])) return '';
        $layout = self::publishedLayout((string)($context['page_type'] ?? 'default'));
        if (!$layout) return '';

        try {
            $rows = DB::all(
                'SELECT p.*,w.widget_type,w.title,w.settings_json,w.is_enabled FROM site_widget_placements p JOIN site_widget_instances w ON w.id=p.widget_id WHERE p.layout_id=? AND p.zone=? AND w.is_enabled=1 ORDER BY p.sort_order,p.id',
                [(int)$layout['id'],$zone]
            );
        } catch (Throwable) {
            return '';
        }

        $items = [];
        foreach ($rows as $row) {
            if (!self::visible($row, $context)) continue;
            $definition = self::$widgets[(string)$row['widget_type']] ?? null;
            if (!$definition) continue;
            try {
                $settings = self::decode((string)($row['settings_json'] ?? ''), (array)$definition['defaults']);
                $html = (string)call_user_func($definition['render'], $settings, $context, $row);
                if (trim($html) === '') continue;
                $typeClass = preg_replace('/[^a-z0-9-]+/', '-', str_replace('.', '-', (string)$row['widget_type'])) ?: 'widget';
                $deviceClasses = self::deviceClasses(self::decode((string)($row['visibility_json'] ?? ''), []));
                $items[] = '<section class="site-widget site-widget--'.\e($typeClass).' '.\e($deviceClasses).'" data-widget-id="'.(int)$row['widget_id'].'">'.$html.'</section>';
            } catch (Throwable $error) {
                \log_error($error);
                $items[] = '<!-- KOVCHEG widget '.\e((string)$row['widget_type']).' failed -->';
            }
        }

        if (!$items) return '';
        $zoneClass = preg_replace('/[^a-z0-9-]+/', '-', str_replace('.', '-', $zone)) ?: 'zone';
        return '<div class="layout-zone layout-zone--'.\e($zoneClass).'" data-layout-zone="'.\e($zone).'">'.implode("\n", $items).'</div>';
    }

    public static function hasZone(string $zone, array $context = []): bool
    {
        return trim(self::renderZone($zone, $context)) !== '';
    }

    public static function studioState(int $layoutId = 0): array
    {
        self::bootCore();
        $layouts = DB::all('SELECT * FROM site_layouts ORDER BY (status=\'published\') DESC,name,id');
        if (!$layouts) throw new RuntimeException('Не создана схема сайта. Примените миграции 3.4.');
        if ($layoutId < 1) $layoutId = (int)$layouts[0]['id'];
        $layout = DB::one('SELECT * FROM site_layouts WHERE id=?', [$layoutId]);
        if (!$layout) throw new RuntimeException('Схема сайта не найдена.');
        $instances = DB::all('SELECT * FROM site_widget_instances ORDER BY is_enabled DESC,title,id');
        $placements = DB::all('SELECT * FROM site_widget_placements WHERE layout_id=? ORDER BY zone,sort_order,id', [$layoutId]);
        $revisions = DB::all('SELECT r.id,r.created_at,u.display_name author_name FROM site_layout_revisions r LEFT JOIN users u ON u.id=r.created_by WHERE r.layout_id=? ORDER BY r.id DESC LIMIT 20', [$layoutId]);
        return compact('layouts','layout','instances','placements','revisions') + ['zones'=>self::$zones,'widgetTypes'=>self::$widgets];
    }

    public static function createWidget(string $type, string $title, array $settings, int $userId): int
    {
        self::bootCore();
        $definition = self::$widgets[$type] ?? null;
        if (!$definition) \abort(422, 'Неизвестный тип виджета.');
        $title = mb_substr(trim($title), 0, 180);
        if ($title === '') $title = (string)$definition['label'];
        $settings = self::normalizeSettings($definition, $settings);
        $module = (string)($definition['module'] ?? 'core');
        return DB::insert('INSERT INTO site_widget_instances (widget_type,module_slug,title,settings_json,is_enabled,created_by,created_at,updated_at) VALUES (?,?,?,?,1,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$type,$module==='core'?null:$module,$title,self::encode($settings),$userId?:null]);
    }

    public static function updateWidget(int $id, string $title, array $settings): void
    {
        self::bootCore();
        $row = DB::one('SELECT * FROM site_widget_instances WHERE id=?', [$id]);
        if (!$row) \abort(404, 'Виджет не найден.');
        $definition = self::$widgets[(string)$row['widget_type']] ?? null;
        if (!$definition) \abort(409, 'Тип виджета сейчас недоступен.');
        $title = mb_substr(trim($title), 0, 180) ?: (string)$definition['label'];
        DB::run('UPDATE site_widget_instances SET title=?,settings_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$title,self::encode(self::normalizeSettings($definition,$settings)),$id]);
    }

    public static function toggleWidget(int $id): void
    {
        if (!DB::one('SELECT id FROM site_widget_instances WHERE id=?', [$id])) \abort(404, 'Виджет не найден.');
        DB::run('UPDATE site_widget_instances SET is_enabled=IF(is_enabled=1,0,1),updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
    }

    public static function duplicateWidget(int $id, int $userId): int
    {
        $row = DB::one('SELECT * FROM site_widget_instances WHERE id=?', [$id]);
        if (!$row) \abort(404, 'Виджет не найден.');
        return DB::insert('INSERT INTO site_widget_instances (widget_type,module_slug,title,settings_json,is_enabled,created_by,created_at,updated_at) VALUES (?,?,?,?,1,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$row['widget_type'],$row['module_slug'],'Копия — '.mb_substr((string)$row['title'],0,160),$row['settings_json'],$userId?:null]);
    }

    public static function deleteWidget(int $id): void
    {
        if (!DB::one('SELECT id FROM site_widget_instances WHERE id=?', [$id])) \abort(404, 'Виджет не найден.');
        DB::run('DELETE FROM site_widget_instances WHERE id=?', [$id]);
    }

    public static function savePlacements(int $layoutId, array $placements, int $userId): void
    {
        self::bootCore();
        $layout = DB::one('SELECT * FROM site_layouts WHERE id=?', [$layoutId]);
        if (!$layout) \abort(404, 'Схема сайта не найдена.');
        $validWidgets = array_map('intval', array_column(DB::all('SELECT id FROM site_widget_instances'), 'id'));
        $validWidgetMap = array_fill_keys($validWidgets, true);
        $normalized = [];
        $seen = [];
        foreach (array_slice($placements, 0, 1000) as $placement) {
            if (!is_array($placement)) continue;
            $widgetId = (int)($placement['widget_id'] ?? 0);
            $zone = (string)($placement['zone'] ?? '');
            if ($widgetId < 1 || !isset($validWidgetMap[$widgetId]) || !isset(self::$zones[$zone]) || !empty(self::$zones[$zone]['reserved'])) continue;
            $key = $widgetId.'|'.$zone;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $normalized[] = ['widget_id'=>$widgetId,'zone'=>$zone,'sort_order'=>count($normalized)*10];
        }

        DB::pdo()->beginTransaction();
        try {
            self::createRevision($layoutId, $userId);
            DB::run('DELETE FROM site_widget_placements WHERE layout_id=?', [$layoutId]);
            foreach ($normalized as $item) {
                DB::run('INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at) VALUES (?,?,?,?,\'{}\',\'{}\',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$layoutId,$item['widget_id'],$item['zone'],$item['sort_order']]);
            }
            DB::run('UPDATE site_layouts SET updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$userId?:null,$layoutId]);
            DB::pdo()->commit();
        } catch (Throwable $error) {
            if (DB::pdo()->inTransaction()) DB::pdo()->rollBack();
            throw $error;
        }
    }

    public static function restoreRevision(int $revisionId, int $userId): int
    {
        $revision = DB::one('SELECT * FROM site_layout_revisions WHERE id=?', [$revisionId]);
        if (!$revision) \abort(404, 'Ревизия не найдена.');
        $snapshot = self::decode((string)$revision['snapshot_json'], []);
        $placements = is_array($snapshot['placements'] ?? null) ? $snapshot['placements'] : [];
        self::savePlacements((int)$revision['layout_id'], $placements, $userId);
        return (int)$revision['layout_id'];
    }

    public static function settingsFromInput(string $type, array $input): array
    {
        self::bootCore();
        $definition = self::$widgets[$type] ?? null;
        if (!$definition) return [];
        $settings = [];
        foreach ((array)$definition['fields'] as $key => $field) {
            $fieldType = (string)($field['type'] ?? 'text');
            if ($fieldType === 'checkbox') $settings[$key] = !empty($input[$key]) ? 1 : 0;
            elseif ($fieldType === 'number' || $fieldType === 'menu') $settings[$key] = (int)($input[$key] ?? 0);
            else $settings[$key] = trim((string)($input[$key] ?? ''));
        }
        return self::normalizeSettings($definition, $settings);
    }

    private static function createRevision(int $layoutId, int $userId): void
    {
        $layout = DB::one('SELECT * FROM site_layouts WHERE id=?', [$layoutId]);
        $placements = DB::all('SELECT widget_id,zone,sort_order,visibility_json,style_json FROM site_widget_placements WHERE layout_id=? ORDER BY zone,sort_order,id', [$layoutId]);
        DB::run('INSERT INTO site_layout_revisions (layout_id,snapshot_json,created_by,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)', [$layoutId,self::encode(['layout'=>$layout,'placements'=>$placements]),$userId?:null]);
        $old = DB::all('SELECT id FROM site_layout_revisions WHERE layout_id=? ORDER BY id DESC LIMIT 1000 OFFSET 50', [$layoutId]);
        foreach ($old as $item) DB::run('DELETE FROM site_layout_revisions WHERE id=?', [(int)$item['id']]);
    }

    private static function normalizeSettings(array $definition, array $settings): array
    {
        $result = (array)($definition['defaults'] ?? []);
        foreach ((array)($definition['fields'] ?? []) as $key => $field) {
            if (!array_key_exists($key, $settings)) continue;
            $type = (string)($field['type'] ?? 'text');
            if ($type === 'checkbox') $result[$key] = !empty($settings[$key]) ? 1 : 0;
            elseif ($type === 'number') $result[$key] = max((int)($field['min'] ?? -999999), min((int)($field['max'] ?? 999999), (int)$settings[$key]));
            elseif ($type === 'menu') $result[$key] = max(0, (int)$settings[$key]);
            elseif ($type === 'select') {
                $options = array_keys((array)($field['options'] ?? []));
                $result[$key] = in_array((string)$settings[$key], $options, true) ? (string)$settings[$key] : (string)($result[$key] ?? ($options[0] ?? ''));
            } else {
                $max = max(1, (int)($field['maxlength'] ?? 5000));
                $result[$key] = mb_substr(trim((string)$settings[$key]), 0, $max);
            }
        }
        return $result;
    }

    private static function visible(array $row, array $context): bool
    {
        $visibility = self::decode((string)($row['visibility_json'] ?? ''), []);
        if (!empty($visibility['hidden'])) return false;
        $pageTypes = array_values(array_filter((array)($visibility['page_types'] ?? []), 'is_string'));
        if ($pageTypes && !in_array((string)($context['page_type'] ?? 'default'), $pageTypes, true)) return false;
        $roles = array_values(array_filter((array)($visibility['roles'] ?? []), 'is_string'));
        $role = (string)(Auth::user()['role'] ?? 'guest');
        return !$roles || in_array($role, $roles, true);
    }

    private static function deviceClasses(array $visibility): string
    {
        $classes = [];
        if (!empty($visibility['hide_mobile'])) $classes[] = 'widget-hide-mobile';
        if (!empty($visibility['hide_tablet'])) $classes[] = 'widget-hide-tablet';
        if (!empty($visibility['hide_desktop'])) $classes[] = 'widget-hide-desktop';
        return implode(' ', $classes);
    }

    private static function decode(string $json, array $default): array
    {
        if (trim($json) === '') return $default;
        try {
            $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? array_replace($default, $value) : $default;
        } catch (Throwable) {
            return $default;
        }
    }

    private static function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }

    private static function renderLogo(array $settings): string
    {
        $name = (string)\setting('site_name', \cfg('app.name', 'KOVCHEG Blog'));
        $tagline = (string)\setting('blog_tagline', 'Разработки · проекты · опыт');
        return '<a class="site-brand" href="'.\e(\app_url('/')).'" aria-label="'.\e($name).'"><img src="'.\e(\app_url('/brand/logo?v='.rawurlencode(APP_VERSION))).'" alt="" class="site-brand__logo"><span class="site-brand__text"><b>'.\e($name).'</b>'.(!empty($settings['show_tagline'])?'<small>'.\e($tagline).'</small>':'').'</span></a>';
    }

    private static function renderMenu(array $settings, array $instance): string
    {
        $menuId = max(0, (int)($settings['menu_id'] ?? 0));
        if ($menuId > 0) {
            $items = DB::all('SELECT * FROM navigation_items WHERE menu_id=? AND is_enabled=1 ORDER BY sort_order,id', [$menuId]);
        } else {
            $items = Blog::menu((string)($settings['location'] ?? 'header'));
        }
        if (!$items) return '';
        $id = 'widget-menu-'.(int)($instance['widget_id'] ?? $instance['id'] ?? 0);
        $orientation = in_array((string)($settings['orientation'] ?? 'auto'), ['auto','horizontal','vertical'], true) ? (string)$settings['orientation'] : 'auto';
        $links = [];
        foreach ($items as $item) {
            $url = trim((string)($item['url'] ?? '/')) ?: '/';
            if (!preg_match('~^(?:https?:)?//~i', $url)) $url = \app_url('/'.ltrim($url, '/'));
            $links[] = '<a href="'.\e($url).'">'.\e((string)($item['label'] ?? 'Раздел')).'</a>';
        }
        return '<button class="site-menu-button" type="button" aria-expanded="false" aria-controls="'.\e($id).'" data-widget-menu-button>Меню</button><nav class="site-navigation site-navigation--'.\e($orientation).'" id="'.\e($id).'" aria-label="'.\e((string)($instance['title'] ?? 'Меню')).'" data-widget-menu>'.implode('', $links).'</nav>';
    }

    private static function renderSearch(array $settings): string
    {
        $placeholder = trim((string)($settings['placeholder'] ?? 'Поиск по сайту')) ?: 'Поиск по сайту';
        return '<form class="site-search-widget" method="get" action="'.\e(\app_url('/search')).'"><label><span class="sr-only">'.\e($placeholder).'</span><input type="search" name="q" value="'.\e((string)($_GET['q'] ?? '')).'" placeholder="'.\e($placeholder).'" maxlength="150"></label><button type="submit" aria-label="Найти">⌕</button></form>';
    }

    private static function renderAccount(): string
    {
        $user = Auth::user() ?? [];
        if (Auth::check()) {
            $studio = Studio::can('comments') ? '<a class="button button--quiet" href="'.\e(\app_url('/studio')).'">Studio</a>' : '';
            return '<div class="site-account"><a class="site-account__profile" href="'.\e(\app_url('/profile')).'">'.\avatar_html($user,'avatar-xs').' <span>'.\e((string)($user['display_name'] ?? 'Профиль')).'</span></a>'.$studio.'</div>';
        }
        return '<div class="site-account"><a href="'.\e(\app_url('/login')).'">Войти</a><a class="button button--dark" href="'.\e(\app_url('/register')).'">Регистрация</a></div>';
    }

    private static function renderText(array $settings): string
    {
        $text = trim((string)($settings['text'] ?? ''));
        return $text === '' ? '' : '<div class="widget-text">'.nl2br(\e($text)).'</div>';
    }

    private static function renderImage(array $settings): string
    {
        $url = self::safePublicUrl((string)($settings['url'] ?? ''));
        if ($url === '') return '';
        $image = '<img src="'.\e($url).'" alt="'.\e((string)($settings['alt'] ?? '')).'" loading="lazy">';
        $link = self::safePublicUrl((string)($settings['link'] ?? ''));
        return '<figure class="widget-image">'.($link !== ''?'<a href="'.\e($link).'">'.$image.'</a>':$image).'</figure>';
    }

    private static function renderLatestPosts(array $settings): string
    {
        $limit = max(1, min(20, (int)($settings['limit'] ?? 5)));
        $rows = Blog::entries('post', $limit);
        if (!$rows) return '';
        $items = [];
        foreach ($rows as $row) $items[] = '<li><a href="'.\e(Blog::entryUrl($row)).'">'.\e((string)$row['title']).'</a><small>'.\e(\human_time((string)($row['published_at'] ?? $row['created_at'] ?? ''))).'</small></li>';
        return '<div class="widget-list"><h2>Последние публикации</h2><ul>'.implode('', $items).'</ul></div>';
    }

    private static function renderCategories(array $settings): string
    {
        $limit = max(1, min(100, (int)($settings['limit'] ?? 20)));
        $rows = DB::all('SELECT c.name,c.slug,COUNT(ec.entry_id) total FROM content_categories c LEFT JOIN content_entry_categories ec ON ec.category_id=c.id GROUP BY c.id,c.name,c.slug ORDER BY c.sort_order,c.name LIMIT '.$limit);
        if (!$rows) return '';
        $items = [];
        foreach ($rows as $row) $items[] = '<li><a href="'.\e(\app_url('/category/'.rawurlencode((string)$row['slug']))).'">'.\e((string)$row['name']).'</a><span>'.(int)$row['total'].'</span></li>';
        return '<div class="widget-list"><h2>Рубрики</h2><ul>'.implode('', $items).'</ul></div>';
    }

    private static function renderSubscription(array $settings): string
    {
        if ((string)\setting('subscriptions_enabled','1') !== '1') return '';
        $title = trim((string)($settings['title'] ?? 'Получать новые публикации')) ?: 'Получать новые публикации';
        return '<form class="widget-subscription" method="post" action="'.\e(\app_url('/subscribe')).'">'.\csrf_field().'<input type="hidden" name="source" value="widget"><label><span>'.\e($title).'</span><input type="email" name="email" placeholder="email@example.com" required maxlength="190"></label><button class="button button--dark">Подписаться</button></form>';
    }

    private static function renderSocialLinks(array $settings): string
    {
        $links = [];
        foreach (preg_split('/\R/u', (string)($settings['links'] ?? '')) ?: [] as $line) {
            [$label,$url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $url = self::safePublicUrl($url);
            if ($label !== '' && $url !== '') $links[] = '<a href="'.\e($url).'" rel="noopener noreferrer">'.\e($label).'</a>';
        }
        return $links ? '<nav class="widget-social-links" aria-label="Социальные ссылки">'.implode('', $links).'</nav>' : '';
    }

    private static function safePublicUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (str_starts_with($url, '/')) return \app_url('/'.ltrim($url, '/'));
        return filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $url) ? $url : '';
    }
}