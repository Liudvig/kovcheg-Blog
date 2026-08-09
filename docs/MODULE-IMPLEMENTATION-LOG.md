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
| Posts | ACTIVE | публичные `/post/{slug}`, Studio `/studio/posts` |
| Pages | ACTIVE | публичные `/page/{slug}`, Studio `/studio/pages` |
| Categories | ACTIVE | относятся к Posts |
| Menus | ACTIVE | позиции header/left/right/footer |
| Media | ACTIVE | Studio media library |
| Users / roles | ACTIVE | управление пользователями и правами Studio |
| Account | ACTIVE | `/account`, без старого social profile runtime |
| Comments / reactions | ACTIVE | взаимодействия только с CMS content entries |
| Branding | ACTIVE | логотип и фон авторизации/регистрации |
| Widgets / layout zones | ACTIVE | управляемые зоны и размещения |
| Themes | ACTIVE | KOVCHEG Portal, Editorial, Portfolio |
| SEO / growth | ACTIVE | sitemap, robots, RSS, redirects, scheduled publishing |
| PWA | ACTIVE | `manifest.webmanifest`, `service-worker.js` |
| Cron | ACTIVE | публикация по расписанию, cleanup, webhook/system jobs |
| CI | ACTIVE | единый `.github/workflows/ci.yml`, run #414 SUCCESS после полной view-cleanup |
| Core Cleanup audit | ACTIVE | `scripts/audit-core-cleanup-3.9.php`, migration/assets/social-view regression guards |
| Legacy VK/X view templates | REMOVED | `views/templates/vk` и `views/templates/x` удалены |
| Root social views | REMOVED | корень `views/` очищен от feed/messenger/profile/channel/wall/people/settings/weather social UI |
| Social helpers in app/functions.php | LEGACY / REVIEW | chat/profile/channel/colleague/push helpers не удалять до карты вызовов |
| Social DB baseline | LEGACY / REVIEW | `database/schema.php` ещё содержит social tables; production data не удалять без backup |
| Historical CSS naming | ACTIVE / REVIEW | часть старых имён реально используется account/Studio layouts, массово не удалять |

## Удалено из активного продукта в 3.9.0

- дублирующие route bundles;
- старые Builder/Demo PHP classes и Studio views;
- старые VK/X CSS themes и fixes;
- старые social JavaScript layers;
- legacy `views/templates/vk` и `views/templates/x`;
- весь доказанно недостижимый root social presentation-layer;
- profile-banner и vk-media helpers;
- дублирующие GitHub Actions workflows;
- старые внешние названия Studio и редакторов;
- социальные задачи cron.

После view-cleanup корень `views/` содержит только:

- `account-shell.php`;
- `layout.php`;
- `login.php`;
- `register.php`;
- `studio/`.

## Исправления текущего этапа

- stale VK asset references в `app/modern-ui.php` удалены;
- legacy page migration больше не меняет Posts в Pages;
- `registration_mode=manual` снова является штатным режимом;
- HTTP smoke workflow больше не получает ложный `curl: (23)` при `pipefail`;
- cleanup audit проверяет отсутствие VK/X template-layer, root social views и migration regressions.

## Остаточный legacy, требующий отдельного анализа

Presentation-layer социальной версии из `views/` удалён полностью после проверки активного `index.php`, routes, `Blog::render()`, hooks и модулей.

`app/functions.php` всё ещё содержит большой набор социальных функций. Их нужно разделить на реально используемые system/CMS helpers и полностью устаревшие chat/profile/channel/colleague/push helpers. Удаление всего блока без call-map запрещено.

`database/schema.php` всё ещё создаёт часть social tables на чистой установке. Baseline новой установки нужно очищать отдельно. Для уже существующей production базы destructive DROP нельзя делать без backup, row-count и проверки фактических данных.

Часть CSS-файлов имеет исторические названия, но реально используется актуальными layouts. Например, `blog-profile-portal.css` обслуживает новый `/account`, а Studio всё ещё подключает несколько старых CSS-слоёв. Это отдельный visual/dependency cleanup, а не повод удалять их по названию.

Исторические audit-скрипты и release/development документация сохраняются как журнал проекта. Исторический журнал не переписывается ради терминологического lint.

## Migration safety

`20260806_z_page_category_core.sql` сохранён под историческим именем для совместимости с таблицей `migrations`, но с 2026-08-09 больше не меняет `post` на `page`.

`20260809_content_model_cleanup.sql` выполняет идемпотентный cleanup: `portfolio` -> `page`, category links удаляются у Pages.

Имена уже применённых migrations нельзя менять без анализа production-таблицы `migrations`.

## Проверки 2026-08-09

GitHub Actions run #406 — SUCCESS.

GitHub Actions run #408 — SUCCESS после удаления VK/X view templates.

GitHub Actions run #410 — SUCCESS после синхронизации документации.

GitHub Actions run #412 — SUCCESS после первого пакета root social view cleanup.

GitHub Actions run #414 — SUCCESS после удаления оставшихся social views и усиления audit.

Подтверждены:

- PHP syntax;
- JavaScript syntax;
- JSON validation;
- Core Cleanup audit;
- MariaDB 11 migration validation;
- MySQL 8.4 migration validation;
- HTTP smoke: public Posts/Pages, login/register, robots/sitemap/RSS, compatibility blog/portfolio, account/Studio redirects;
- отсутствие секретов и отслеживаемых runtime data.

Production deploy 3.9.0 пока не подтверждён и не должен считаться выполненным только по зелёному CI.
