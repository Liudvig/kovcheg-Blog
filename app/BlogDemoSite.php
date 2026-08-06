<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\DB;

require_once __DIR__.'/ClassicEditor.php';

final class DemoSite
{
    public static function install(int $userId): array
    {
        Studio::require('site');

        $createdCategories = 0;
        $createdEntries = 0;
        $createdMenuItems = 0;

        foreach ([
            ['Новости','novosti','Новости проекта, релизы и важные обновления.'],
            ['Разработка','razrabotka','Ход разработки KOVCHEG CMS и технические решения.'],
            ['Документация','dokumentaciya','Руководства, возможности и материалы проекта.'],
        ] as [$name,$slug,$description]) {
            if (DB::one('SELECT id FROM content_categories WHERE slug=?', [$slug])) continue;
            DB::insert('INSERT INTO content_categories (name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$name,$slug,$description]);
            $createdCategories++;
        }

        $categoryMap=[];
        foreach(DB::all("SELECT id,slug FROM content_categories WHERE slug IN ('novosti','razrabotka','dokumentaciya')") as $category){
            $categoryMap[(string)$category['slug']]=(int)$category['id'];
        }

        $items = [
            [
                'type'=>'page','title'=>'О проекте KOVCHEG CMS','slug'=>'o-proekte-kovcheg-cms',
                'excerpt'=>'KOVCHEG CMS — собственная модульная система для блога, портфолио, корпоративного и информационного сайта.',
                'html'=>'<h2>Собственная система управления сайтом</h2><p><strong>KOVCHEG CMS</strong> создаётся как понятная и независимая платформа, которую можно установить на собственный сервер и развивать под реальные задачи.</p><p>В системе уже работают публикации, страницы, портфолио, медиатека, рубрики, теги, меню, роли, комментарии, темы, виджеты и KOVCHEG Studio.</p><blockquote><p>Главный принцип проекта — владелец контролирует код, данные, сервер и развитие сайта.</p></blockquote><h3>Для каких сайтов подходит</h3><ul><li>личный и профессиональный блог;</li><li>сайт разработчика или проекта;</li><li>портфолио;</li><li>сайт организации;</li><li>новостной и информационный портал.</li></ul>',
                'categories'=>['dokumentaciya'],'tags'=>'KOVCHEG CMS, о проекте','featured'=>1,
            ],
            [
                'type'=>'page','title'=>'Возможности KOVCHEG Blog','slug'=>'vozmozhnosti-kovcheg-blog',
                'excerpt'=>'Основные возможности движка и KOVCHEG Studio.',
                'html'=>'<h2>Публикации без лишней сложности</h2><p>Классический редактор позволяет писать материал так же привычно, как в старом WordPress: заголовки, жирный текст, курсив, списки, цитаты, ссылки, изображения и HTML-режим находятся в одном окне.</p><h3>Управление сайтом</h3><ul><li>материалы, страницы и портфолио;</li><li>обложки и медиатека;</li><li>рубрики и теги;</li><li>меню и переносимые виджеты;</li><li>черновики, планирование и история изменений;</li><li>SEO, sitemap, robots.txt и RSS;</li><li>пользователи, роли и модерация.</li></ul><h3>Два режима редактора</h3><p>Для обычной статьи используется классический редактор. Для сложной посадочной страницы остаётся конструктор секций.</p>',
                'categories'=>['dokumentaciya'],'tags'=>'возможности, документация','featured'=>0,
            ],
            [
                'type'=>'post','title'=>'KOVCHEG Blog 3.5.6: классический редактор','slug'=>'kovcheg-blog-3-5-6-classic-editor',
                'excerpt'=>'В KOVCHEG Studio появился обычный редактор в стиле классического WordPress и готовый демонстрационный сайт.',
                'html'=>'<p>Редактор материалов полностью переработан для повседневной работы.</p><h2>Что изменилось</h2><ul><li>классический визуальный редактор стал основным;</li><li>добавлены вкладки «Визуально» и «Текст»;</li><li>работают форматирование, ссылки, списки, цитаты и выравнивание;</li><li>изображения вставляются прямо из медиатеки;</li><li>сохранились автосохранение, ревизии и предпросмотр;</li><li>конструктор секций оставлен дополнительным режимом.</li></ul><p>Обновление делает KOVCHEG Blog удобнее для реального блога и демонстрационного сайта проекта.</p>',
                'categories'=>['novosti','razrabotka'],'tags'=>'релиз, редактор, KOVCHEG Blog','featured'=>1,
            ],
            [
                'type'=>'page','title'=>'Контакты и сотрудничество','slug'=>'kontakty',
                'excerpt'=>'Контактная страница демонстрационного сайта.',
                'html'=>'<h2>Связаться с автором проекта</h2><p>На этой странице владелец сайта может разместить рабочий email, телефон, ссылки на социальные сети и форму обратной связи.</p><p><strong>Автор и правообладатель:</strong> Ланцет Семён Борисович.</p><p>Перед публичным запуском замените демонстрационный текст реальными контактными данными в KOVCHEG Studio.</p>',
                'categories'=>[],'tags'=>'контакты','featured'=>0,
            ],
        ];

        foreach ($items as $item) {
            if (DB::one('SELECT id FROM content_entries WHERE slug=? AND deleted_at IS NULL', [$item['slug']])) continue;
            $categoryIds=[];
            foreach($item['categories'] as $slug)if(isset($categoryMap[$slug]))$categoryIds[]=$categoryMap[$slug];
            $payload=json_encode([['id'=>'classic-demo-'.substr(sha1($item['slug']),0,10),'type'=>'classic','data'=>['html'=>$item['html']]]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            Studio32::saveEntry([
                'type'=>$item['type'],'status'=>'published','visibility'=>'public','title'=>$item['title'],'slug'=>$item['slug'],
                'excerpt'=>$item['excerpt'],'content_json'=>$payload,'category_ids'=>$categoryIds,'tags'=>$item['tags'],
                'comments_enabled'=>0,'reactions_enabled'=>0,'is_featured'=>$item['featured'],'sort_order'=>0,
                'seo_title'=>$item['title'],'seo_description'=>$item['excerpt'],'layout_width'=>'normal','accent'=>'#2271b1',
            ],$userId);
            $createdEntries++;
        }

        foreach([
            'site_name'=>'KOVCHEG CMS',
            'blog_theme'=>'kovcheg-portal',
            'blog_tagline'=>'Разработка · релизы · документация',
            'blog_description'=>'Официальный демонстрационный сайт KOVCHEG CMS: новости разработки, возможности и документация.',
            'portfolio_description'=>'Сайты, модули и цифровые продукты, созданные на платформе KOVCHEG.',
            'blog_footer_text'=>'Демонстрационный сайт работает на KOVCHEG Blog.',
            'seo_description'=>'KOVCHEG CMS — модульная система управления блогом, портфолио и информационным сайтом.',
            'seo_robots_index'=>'0',
            'search_indexing'=>'0',
            'demo_site_seeded_at'=>date('c'),
        ] as $key=>$value) Studio::setSetting($key,$value);
        DB::run('UPDATE themes SET is_active=(slug=?)',['kovcheg-portal']);

        $menu=DB::one("SELECT id FROM navigation_menus WHERE slug='demo-main' LIMIT 1");
        $menuId=$menu?(int)$menu['id']:DB::insert("INSERT INTO navigation_menus (name,slug,location,is_active,created_at,updated_at) VALUES ('Главное меню','demo-main','header',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");
        DB::run("UPDATE navigation_menus SET is_active=(id=?),updated_at=CURRENT_TIMESTAMP WHERE location='header'",[$menuId]);
        $menuItems=[
            ['Главная','/',0],
            ['О проекте','/page/o-proekte-kovcheg-cms',10],
            ['Возможности','/page/vozmozhnosti-kovcheg-blog',20],
            ['Новости','/blog',30],
            ['Портфолио','/portfolio',40],
            ['Контакты','/page/kontakty',50],
        ];
        foreach($menuItems as [$label,$url,$sort]){
            if(DB::one('SELECT id FROM navigation_items WHERE menu_id=? AND url=? LIMIT 1',[$menuId,$url]))continue;
            DB::insert("INSERT INTO navigation_items (menu_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at) VALUES (?,?,?,'custom',NULL,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",[$menuId,$label,$url,$sort]);
            $createdMenuItems++;
        }

        audit('blog.demo.install','site_demo',null,['entries'=>$createdEntries,'categories'=>$createdCategories,'menu_items'=>$createdMenuItems]);
        return ['entries'=>$createdEntries,'categories'=>$createdCategories,'menu_items'=>$createdMenuItems];
    }
}
