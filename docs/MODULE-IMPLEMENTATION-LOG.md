# KOVCHEG Blog / KOVCHEG CMS — MODULE IMPLEMENTATION LOG

Дата актуализации: 2026-08-09
Версия: KOVCHEG Blog 3.9.0 Core Cleanup
Ветка: `feature/core-cleanup-3.9.0`

Автор и правообладатель: Ланцет Семён Борисович
Лицензия: proprietary / all rights reserved

## Текущие компоненты

| Компонент | Статус | Примечание |
|---|---|---|
| Core / bootstrap | ACTIVE | `app/bootstrap.php`, KOVCHEG CMS 3.9.0 |
| Front controller / routing | ACTIVE | единый runtime через `index.php` |
| Installer | ACTIVE | `install.php` 3.9.0 + CMS baseline + migration-chain |
| Posts | ACTIVE | публичные `/post/{slug}`, Studio `/studio/posts` |
| Pages | ACTIVE | публичные `/page/{slug}`, Studio `/studio/pages` |
| Categories | ACTIVE | относятся только к Posts |
| Tags | REMOVED FROM FRESH MODEL | fresh install не создаёт tag tables, Studio не синхронизирует tags |
| Menus | ACTIVE | позиции header/left/right/footer |
| Media | ACTIVE | Studio media library |
| Users / roles | ACTIVE | управление пользователями и правами Studio |
| Persistent auth | ACTIVE | `user_remember_tokens` входит в fresh baseline |
| Account | ACTIVE | `/account`, без старого social profile runtime |
| Comments / reactions | ACTIVE | взаимодействия только с CMS content entries |
| Branding | ACTIVE | логотип и фон авторизации/регистрации |
| Widgets / layout zones | ACTIVE | управляемые зоны и размещения |
| Themes | ACTIVE | KOVCHEG Portal, Editorial, Portfolio |
| SEO / growth | ACTIVE | sitemap, robots, RSS, redirects, scheduled publishing |
| PWA | ACTIVE | `manifest.webmanifest`, `service-worker.js` |
| Cron | ACTIVE | публикация по расписанию, cleanup, auth/system, webhook jobs |
| CI | ACTIVE | единый `.github/workflows/ci.yml`, run #444 SUCCESS |
| Core Cleanup audit | ACTIVE | migration/assets/views/fresh-schema regression guards |
| Legacy VK/X view templates | REMOVED | `views/templates/vk` и `views/templates/x` удалены |
| Root social views | REMOVED | feed/messenger/profile/channel/wall/people/settings/weather social UI удалён |
| Legacy modern UI layer | REMOVED | `app/modern-ui.php` и modern-upload/template-polish/layout-repair assets удалены |
| Social DB baseline | REMOVED FROM FRESH INSTALL | fresh schema не создаёт chats/messages/wall/follows/stories/push |
| VK media schema | RETIRED | historical migration filename сохранён как no-create compatibility marker |
| Builder / presets schema | RETIRED FROM FRESH INSTALL | content_patterns/site_preset_history больше не создаются |
| Social helpers in app/functions.php | LEGACY / REVIEW | удалять только после call-map |
| Production legacy tables | PRESERVED / REVIEW | destructive DROP запрещён до backup и data audit |
| Historical CSS naming | ACTIVE / REVIEW | часть старых имён реально используется account/Studio layouts |

## Удалено из активного продукта в 3.9.0

- дублирующие route bundles;
- старые Builder/Demo PHP classes и Studio views;
- старые VK/X CSS themes и fixes;
- старые social JavaScript layers;
- legacy `views/templates/vk` и `views/templates/x`;
- весь доказанно недостижимый root social presentation-layer;
- legacy `app/modern-ui.php` и его wall/VK/X repair assets;
- profile-banner и vk-media runtime helpers;
- дублирующие GitHub Actions workflows;
- старые внешние названия Studio и редакторов;
- социальные задачи cron;
- VK/VK Video CSP permissions;
- tag model из fresh 3.9 database и Studio save flow;
- demo categories, обязательные Blog/Portfolio menu items и portfolio setting из fresh install.

После view-cleanup корень `views/` содержит только:

- `account-shell.php`;
- `layout.php`;
- `login.php`;
- `register.php`;
- `studio/`.

## Fresh install 3.9.0

`install.php`:
- сообщает версию 3.9.0;
- создаёт system/CMS baseline из `database/schema.php`;
- применяет отсортированные `migrations/*.sql`;
- записывает применённые migration filenames;
- создаёт владельца после подготовки текущей схемы;
- не создаёт старые social `user_permissions`.

Fresh baseline создаёт необходимые auth/system структуры: users, settings, roles, `user_remember_tokens`, modules, API tokens, webhooks, admin notifications, audit и auth rate-limit.

Fresh baseline и migration-chain не должны создавать:
- chats/messages;
- profile posts/walls;
- follows/colleague requests;
- stories/push;
- VK media tables;
- content_patterns/site_preset_history;
- content_tags/content_entry_tags;
- demo categories;
- обязательные `/blog`/`/portfolio` menu items;
- `portfolio_description`.

## Migration safety

`20260806_z_page_category_core.sql` сохранён под историческим именем и больше не меняет `post` на `page`.

`20260809_content_model_cleanup.sql` выполняет идемпотентный cleanup: `portfolio` -> `page`, category links удаляются у Pages.

`20260719_vk_media_library.sql` сохранён под историческим именем, но в 3.9 является compatibility marker и не создаёт VK tables.

`20260722_blog_builder.sql` сохранён под историческим именем; нужные media/autosave/module части остаются, retired content patterns/preset history больше не создаются.

Имена уже применённых migrations нельзя менять без анализа production-таблицы `migrations`.

Никакой текущий cleanup SQL не выполняет destructive DROP production legacy tables.

## Проверки 2026-08-09

Контрольные SUCCESS runs текущего глубокого cleanup:
- #424 — runtime без legacy modern-ui;
- #428 — installer migration flow;
- #429 — clean CMS baseline;
- #432 — anti-social database assertions;
- #437 — Studio32 cleanup/hotfix;
- #441 — retired tags;
- #444 — fresh install без demo content.

Run #444 на commit `19bc41aeb58d08abf4a04f656aabbefec9fde121` завершён SUCCESS.

Подтверждены:
- PHP syntax;
- JavaScript syntax;
- JSON validation;
- Core Cleanup audit;
- MariaDB 11 migration validation;
- MySQL 8.4 migration validation;
- HTTP smoke: public Posts/Pages, login/register, robots/sitemap/RSS, compatibility routes, account/Studio redirects;
- наличие `content_entries` и `user_remember_tokens`;
- отсутствие fresh social/VK/Builder/tag tables;
- отсутствие demo categories/menu/portfolio setting;
- отсутствие секретов и отслеживаемых runtime data.

## Остаточный legacy, требующий отдельного анализа

`app/functions.php` всё ещё содержит большой набор старых социальных функций, смешанных с нужными system/CMS helpers. Следующий крупный cleanup должен строиться на call-map из `index.php`, active routes, themes, modules, cron и bin scripts. Массовое удаление файла или блока без анализа запрещено.

`app/Core.php` всё ещё содержит compatibility fallback `site_template=vk/x`, хотя физические template directories удалены. Это небольшой active-code хвост для следующего безопасного пакета.

`migrations/20260722_blog_visual_zone_builder.sql` содержит старые setting flags и требует проверки реальных ссылок перед retirement.

Часть CSS-файлов имеет исторические названия, но реально используется актуальными layouts. Это отдельный visual/dependency cleanup.

Production legacy tables не удаляются автоматически. Перед любым destructive SQL нужны backup, row-count, анализ данных и решение о сохранении/экспорте.

Production deploy 3.9.0 пока не подтверждён и не должен считаться выполненным только по зелёному CI.


## Social Helper Runtime Cleanup

<!-- KOVCHEG_3_9_SOCIAL_HELPER_CLEANUP_FINAL -->
| Слой | Статус | Проверка |
|---|---|---|
| Active social views | REMOVED | Core Cleanup audit |
| Fresh social DB baseline | REMOVED | MariaDB/MySQL assertions |
| Active social DB dependencies | REMOVED | `audit-active-runtime-social-free.php` |
| Social helpers in `app/functions.php` | REMOVED | token call graph + `report-function-usage-3.9.php` |
| Production legacy data | PRESERVED | удалить только после backup/data audit |

Portal Media Widgets также очищен от VK Video и retired `portfolio` content type; bundled metadata обновлена до 1.0.1 / min core 3.9.0.

## Production 3.9.0 verified — 2026-08-09

<!-- KOVCHEG_3_9_PRODUCTION_DEPLOYED_2026_08_09 -->
- production domain: `https://kovchegcms.ru`;
- production commit: `d461a1b5dd66ebb658446cc0f95b0a7764101f90` (`Fix 3.9 audit for production config`);
- pre-deploy production commit: `8984755a9afe5d0869563f4f83c57d301bd30a3a`;
- backup: `/root/kovcheg-blog-backup-3.9.0-20260809-130820`;
- DB backup created successfully with MariaDB root socket authentication;
- migrations applied: `20260809_content_model_cleanup.sql`, `20260809_portal_media_widgets_1_0_1.sql`;
- repeat migration run: `DONE Database is up to date.`;
- HTTP verification: `/` 200, `/blog` 200, `/portfolio` 200, `/studio` 302;
- `assets/css/kovcheg-shell.css` -> 200; retired `assets/css/kovcheg-core.css` -> 404;
- service worker no longer contains legacy `kovcheg-assets-3.0-r1`;
- production `config/config.php` exists locally and is not tracked by Git;
- old production social/VK/Builder/tag tables were not destructively dropped.

Status: **KOVCHEG CMS 3.9.0 PRODUCTION DEPLOYED AND VERIFIED**.

