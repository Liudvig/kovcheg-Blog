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
| CI | ACTIVE | единый `.github/workflows/ci.yml`, итоговые run #406/#408 SUCCESS |
| Core Cleanup audit | ACTIVE | `scripts/audit-core-cleanup-3.9.php`, включая migration/VK-X regression guards |
| Legacy VK/X view templates | REMOVED | `views/templates/vk` и `views/templates/x` удалены в `33b50bc2` |
| Root social views | LEGACY / REVIEW | feed/messenger/profile/channel/wall/reaction layers ещё требуют проверки ссылок |
| Social helpers in app/functions.php | LEGACY / REVIEW | chat/profile/channel/colleague/push helpers не удалять до карты вызовов |
| Social DB baseline | LEGACY / REVIEW | `database/schema.php` ещё содержит social tables; production data не удалять без backup |

## Удалено из активного продукта в 3.9.0

- дублирующие route bundles;
- старые Builder/Demo PHP classes и Studio views;
- старые VK/X CSS themes и fixes;
- старые social JavaScript layers;
- legacy `views/templates/vk` и `views/templates/x`;
- profile-banner и vk-media helpers;
- дублирующие GitHub Actions workflows;
- старые внешние названия Studio и редакторов;
- социальные задачи cron.

## Исправления текущего этапа

- stale VK asset references в `app/modern-ui.php` удалены;
- legacy page migration больше не меняет Posts в Pages;
- `registration_mode=manual` снова является штатным режимом;
- HTTP smoke workflow больше не получает ложный `curl: (23)` при `pipefail`;
- cleanup audit проверяет отсутствие VK/X template-layer и migration regressions.

## Остаточный legacy, требующий отдельного анализа

В репозитории всё ещё присутствует часть старых root view-файлов социальной версии: `feed`, `messenger`, `profile`, `channel`, `wall`, avatar/reaction layers и связанные presentation helpers.

Они не должны удаляться массово без проверки ссылок из текущих routes, hooks и общих helpers. Активный `index.php` подключает только текущие CMS route-файлы, однако следующий cleanup-пакет должен документально подтвердить недостижимость каждого удаляемого root view.

`app/functions.php` содержит старые социальные функции. Их нужно разделить на реально используемые system/CMS helpers и полностью устаревшие chat/profile/channel/colleague/push helpers.

`database/schema.php` всё ещё создаёт часть social tables на чистой установке. Baseline новой установки нужно очищать отдельно. Для уже существующей production базы destructive DROP нельзя делать без backup и проверки данных.

Исторические audit-скрипты и release/development документация сохраняются как журнал проекта. Исторический журнал не переписывается ради терминологического lint.

## Migration safety

`20260806_z_page_category_core.sql` сохранён под историческим именем для совместимости с таблицей `migrations`, но с 2026-08-09 больше не меняет `post` на `page`.

`20260809_content_model_cleanup.sql` выполняет идемпотентный cleanup: `portfolio` -> `page`, category links удаляются у Pages.

Имена уже применённых migrations нельзя менять без анализа production-таблицы `migrations`.

## Проверки 2026-08-09

GitHub Actions run #406 — SUCCESS.

GitHub Actions run #408 после удаления VK/X view templates и усиления cleanup audit — SUCCESS.

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
