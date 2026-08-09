# История изменений KOVCHEG Blog

## 3.9.0 — Core Cleanup

Дата: 2026-08-09

KOVCHEG Blog / KOVCHEG CMS очищается от наследия старой социальной версии и переводится на самостоятельную CMS-модель без переписывания архитектуры и без удаления пользовательского контента.

### Ядро и runtime

- единым front controller оставлен `index.php`;
- удалены старые дублирующие route bundles;
- удалены `BlogBuilder` и `BlogDemoSite` и связанные старые Studio views;
- общий `views/layout.php` заменён на чистую CMS shell-оболочку;
- удалены старые VK/X CSS/JS assets;
- удалены legacy-каталоги `views/templates/vk` и `views/templates/x`;
- после проверки активных routes, renderer, hooks и modules удалён весь недостижимый root social presentation-layer: feed/messenger/profile/channel/wall/people/settings/mobile-navigation/social-search/weather views;
- после cleanup корень `views/` содержит только актуальные `layout`, `account`, `login`, `register` и `studio`;
- удалён глобальный legacy `app/modern-ui.php` и связанные `modern-upload`, `template-polish`, `layout-repair` CSS/JS;
- VK/VK Video удалены из CSP;
- cleanup audit запрещает возврат удалённых social views и modern-ui assets в активное дерево.

### Studio, PWA и cron

- Studio переведён на собственные названия и текущий content editor;
- добавлен `assets/css/blog-studio-content.css`;
- удалены мёртвые preset methods из `BlogStudio32`;
- Studio больше не синхронизирует retired tag model;
- обновлены `manifest.webmanifest` и `service-worker.js`;
- cron очищен от старых социальных задач, сохранены scheduled publishing, cleanup, auth/system и webhook jobs;
- исправлен режим регистрации `manual`, старое значение `email_approval` сохранено как совместимый alias.

### Fresh install и база данных

- installer переведён с устаревшей версии 3.0 на KOVCHEG CMS 3.9.0;
- `install.php` теперь применяет текущую цепочку `migrations/*.sql` и фиксирует migration filenames до завершения установки;
- installer больше не создаёт старые social `user_permissions`;
- `database/schema.php` заменён на минимальный CMS/system baseline вместо старого social baseline;
- fresh baseline создаёт users/settings/roles, `user_remember_tokens`, modules/API/webhook infrastructure, admin notifications, audit и auth rate-limit;
- fresh baseline больше не создаёт chats/messages, walls/profile posts, follows/colleague requests, stories/push и другие social structures;
- добавлена отсутствовавшая таблица `user_remember_tokens`, используемая постоянным входом;
- старая content-model миграция заменена на `20260809_content_model_cleanup.sql`;
- legacy `portfolio` преобразуется в Page, категории сохраняются только у Posts;
- исправлена `20260806_z_page_category_core.sql`: она больше не преобразует все Posts в Pages;
- исторические migration filenames сохраняются, потому что `bin/migrate.php` учитывает применённые миграции по имени файла;
- `20260719_vk_media_library.sql` сохранена по имени, но превращена в безопасный compatibility marker и больше не создаёт VK tables;
- `20260722_blog_builder.sql` сохраняет нужные media/autosave/module compatibility structures, но больше не создаёт `content_patterns` и `site_preset_history`;
- fresh install больше не создаёт `content_tags` и `content_entry_tags`;
- fresh install больше не создаёт demo categories, обязательные пункты меню «Блог»/«Портфолио» и `portfolio_description`;
- historical Visual Zone Builder migration сохранена по имени как retired compatibility marker и больше не создаёт старый setting;
- destructive cleanup существующей production БД не выполняется: старые production-таблицы можно удалять только после backup, row-count и анализа данных.

### CI и безопасность

- дублирующие GitHub Actions workflows объединены в `.github/workflows/ci.yml`;
- CI проверяет PHP, JavaScript, JSON, MariaDB, MySQL, HTTP smoke и отсутствие секретов/runtime data;
- добавлен и последовательно усилен `scripts/audit-core-cleanup-3.9.php`;
- cleanup audit проверяет stale assets, social/VK views, fresh installer, database baseline, retired migrations, content migration safety и отсутствие demo/tag/Builder слоя;
- CI на MariaDB 11 и MySQL 8.4 после полной migration-chain проверяет наличие current CMS/auth tables и отсутствие social/VK/Builder/tag tables;
- CI отдельно проверяет отсутствие demo categories, `/blog`/`/portfolio` menu seeds и `portfolio_description` на fresh database;
- исторические release/development журналы не переписываются ради терминологического lint и отделены от проверки активного runtime;
- исправлен ложный HTTP smoke failure из-за `set -o pipefail` и `curl | grep -q`;
- GitHub Actions run #406 завершён SUCCESS;
- run #408 после удаления VK/X view templates завершён SUCCESS;
- run #414 после полной очистки root social views завершён SUCCESS;
- run #424 после удаления legacy modern-ui завершён SUCCESS;
- run #428 после исправления installer migration flow завершён SUCCESS;
- run #429 после замены fresh database baseline завершён SUCCESS;
- run #432 после anti-social DB assertions завершён SUCCESS;
- run #437 после Studio32 cleanup/hotfix завершён SUCCESS;
- run #441 после retirement tag model завершён SUCCESS;
- run #444 на commit `19bc41ae` после удаления demo content из fresh install завершён SUCCESS.

### Документация

- обновлены README и SECURITY под KOVCHEG Blog / KOVCHEG CMS 3.9;
- создан и актуализирован `PROJECT_MEMORY.md`;
- создан и актуализирован `docs/MODULE-IMPLEMENTATION-LOG.md`;
- обновляется `docs/DEVELOPMENT_LOG.md`;
- добавлен аудит `docs/audits/KOVCHEG_CMS_3.9.0_CORE_CLEANUP_AUDIT.md`.

### Осталось до завершения 3.9.0

- построить call-map и удалить только доказанно неиспользуемые social helpers из `app/functions.php`;
- отдельно убрать небольшие compatibility-хвосты active code, включая старый `site_template=vk/x` fallback, только безопасным точечным изменением;
- отдельно проверить исторически названные, но реально используемые CSS-слои account/Studio перед переименованием или консолидацией;
- провести data audit старых production social/VK/Builder/tag tables перед любым destructive SQL;
- проверить production deploy через GitHub -> server -> FastPanel;
- проверить production PHP, DB migrations, storage permissions, cache и HTTP routes.

Автор и правообладатель: Ланцет Семён Борисович.  
Лицензия: proprietary / all rights reserved.

## 3.4.1 — постоянный вход и обновлённая админка

Исправлен случайный возврат владельца на страницу входа и обновлена рабочая оболочка админки.

### Авторизация

- срок сессионной и постоянной cookies увеличен до 10 лет и продлевается при использовании Studio;
- устранена гонка параллельных запросов: remember-token больше не меняется при каждом восстановлении сессии;
- временная ошибка базы или хостинга больше не удаляет действующую постоянную cookie;
- HTTPS корректно определяется за FASTPANEL и другим reverse proxy;
- ручная кнопка выхода остаётся доступной и завершает сеанс явно.

### Админка

- левая колонка с меню остаётся неподвижной на компьютере;
- содержимое прокручивается отдельно от меню;
- полосы прокрутки скрыты в Chromium, WebKit и Firefox;
- на телефоне меню открывается отдельной выезжающей панелью;
- добавлен полноценный подвал рабочей области;
- подвал при короткой странице остаётся у нижнего края, а при длинной появляется после содержимого;
- старые пользовательские подписи `KOVCHEG CMS`, `Core` и формулировки про ядро заменяются на KOVCHEG Blog.

### Страница входа

- удалён старый экран универсальной закрытой платформы;
- страница теперь описывает статьи, страницы, портфолио, SEO, меню, виджеты и модули KOVCHEG Blog Studio.

### Контроль качества

- добавлен `scripts/audit-studio-shell.php`;
- аудит проверяет длительный вход, отсутствие rotation remember-token, fixed sidebar, скрытые scrollbars, подвал и новую терминологию.

## 3.4.0 — Layout & Widget Engine

Первый этап визуального управления структурой всего сайта без правки PHP-шаблонов.

### Зоны и размещение

- добавлены системные зоны шапки, боковых колонок, содержимого и подвала;
- виджеты перетаскиваются между зонами через KOVCHEG Studio;
- необязательный блок можно убрать с сайта, не удаляя его настройки;
- порядок внутри зоны меняется мышкой или кнопками вверх/вниз;
- перед публикацией схемы создаётся ревизия с возможностью восстановления;
- тема Editorial и основанная на ней Portfolio выводят зоны через единый Layout Engine;
- до применения миграции сохраняется безопасный fallback на старую шапку и подвал.

### Базовые виджеты

- логотип и название;
- любое созданное меню;
- поиск;
- профиль, вход и ссылка Studio;
- безопасный текст;
- изображение;
- последние публикации;
- рубрики;
- форма подписки;
- социальные ссылки.

### Module First

- публичные методы `Layout::registerZone()` и `Layout::registerWidget()` позволяют модулю добавлять собственные зоны и типы блоков;
- тема больше не обязана знать о конкретном модуле;
- ошибка одного renderer изолируется и записывается в журнал вместо падения всей страницы;
- неизвестный или отключённый модульный виджет не ломает публичный сайт.

### База и качество

- добавлены таблицы layout, экземпляров, размещений и ревизий;
- миграция идемпотентна и создаёт совместимую начальную схему;
- CI проверяет MariaDB 11 и MySQL 8.4;
- проверяются повторное применение миграции, реестр зон и виджетов, публичный renderer и реальные HTTP-маршруты;
- версия приложения поднята до 3.4.0.

## 3.3.3 — FASTPANEL query route fallback

Добавлен резервный способ передачи исходного маршрута через служебный query-параметр для двухуровневого прокси FASTPANEL.

- фронт-контроллер принимает маршрут из `__kovcheg_route`, стандартных proxy-заголовков и переменных внутреннего редиректа;
- служебный маршрут используется только когда backend фактически открыл `/index.php`;
- значение проверяется по длине, начальному `/` и отсутствию переводов строк;
- служебный параметр удаляется из `$_GET` и `$_REQUEST` до обработки маршрута;
- сохраняются обычные query-параметры публичной страницы;
- CI проверяет прямой маршрут, proxy-заголовок и query fallback для HTML, sitemap, RSS и robots;
- версия приложения поднята до 3.3.3.

## 3.3.2 — FASTPANEL proxy routing hotfix

Исправлена работа красивых URL через двухуровневую схему FASTPANEL Nginx → backend.

- фронт-контроллер принимает исходный URI из служебного заголовка `X-Kovcheg-Original-URI`;
- `/blog`, `/portfolio`, `/sitemap.xml`, `/feed.xml` и `/robots.txt` корректно маршрутизируются через `index.php`;
- заголовок проверяется по длине, формату и отсутствию переводов строк;
- добавлен HTTP smoke test, имитирующий реальный proxy-запрос FASTPANEL;
- версия приложения поднята до 3.3.2.

## 3.3.1 — Runtime bootstrap hotfix

Критическое исправление загрузки ядра после релиза Growth Suite.

- восстановлена полная инициализация `app/bootstrap.php`;
- снова подключаются `Core.php`, функции, блог, Studio, Visual Builder, модули и Growth Suite;
- восстановлены сессии, CSP, подключение базы, авторизация и запуск модулей;
- CLI больше не зависит от внешнего `auto_prepend_file`;
- `bin/migrate.php` и `bin/publish-scheduled.php` проверяются реальным запуском в CI;
- HTTP-заголовки не отправляются в CLI-режиме;
- версия приложения поднята до 3.3.1.

## 3.3.0 — Growth Suite

Пакет для реального публичного блога и профессионального сайта: поисковая индексация, подписки, редиректы и автоматическая публикация.

### SEO и распространение

- динамический `sitemap.xml` для главной, блога, портфолио, страниц и материалов;
- управляемый `robots.txt`;
- RSS 2.0 по адресу `/feed.xml`;
- canonical URL и обновлённые Open Graph мета-теги;
- отдельное название и описание сайта для поисковиков;
- RSS discovery в публичных темах.

### Рост аудитории

- подписка читателей по email;
- форма подписки в подвале тем Editorial и Portfolio;
- реестр подписчиков в KOVCHEG Studio;
- возможность отключить подписки без удаления данных.

### Публикация и перенос адресов

- автоматическая публикация запланированных материалов;
- CLI-команда `php bin/publish-scheduled.php` для cron;
- ручной запуск публикации из Studio;
- журнал автоматически опубликованных материалов;
- редактор 301, 302, 307 и 308 редиректов;
- счётчик переходов по редиректам;
- поддержка внутренних и внешних адресов назначения.

### KOVCHEG Studio

- новый раздел «SEO и рост»;
- управление sitemap, RSS, robots и подписками;
- список запланированных публикаций;
- подписчики, редиректы и журнал публикации на одной странице.

Автор и правообладатель: Ланцет Семён Борисович.  
Лицензия: proprietary / all rights reserved.
