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
| CI | ACTIVE | единый `.github/workflows/ci.yml` |
| Core Cleanup audit | ACTIVE | `scripts/audit-core-cleanup-3.9.php` |

## Удалено из активного продукта в 3.9.0

- дублирующие route bundles;
- старые Builder/Demo PHP classes и Studio views;
- старые VK/X CSS themes и fixes;
- старые social JavaScript layers;
- profile-banner и vk-media helpers;
- дублирующие GitHub Actions workflows;
- старые внешние названия Studio и редакторов;
- социальные задачи cron.

## Остаточный legacy, требующий отдельного анализа

В репозитории всё ещё присутствуют старые view-файлы социальной версии, в том числе каталоги `views/templates/vk` и `views/templates/x`, а также часть `feed`, `messenger`, `profile`, `channel`, `wall` и reaction views.

Они не должны удаляться массово без проверки ссылок. Следующий cleanup-пакет должен подтвердить, какие из них полностью недостижимы из текущего `index.php` и активных route-файлов, после чего удалить только доказанно неиспользуемые файлы.

Исторические audit-скрипты и release/development документация сохраняются до отдельного анализа. Исторический журнал не переписывается ради терминологического lint.

## Migration safety

`20260806_z_page_category_core.sql` сохранён под историческим именем для совместимости с таблицей `migrations`, но с 2026-08-09 больше не меняет `post` на `page`.

`20260809_content_model_cleanup.sql` выполняет идемпотентный cleanup: `portfolio` -> `page`, category links удаляются у Pages.

## Проверки 2026-08-09

- PHP syntax: проходит в GitHub Actions;
- JavaScript syntax: проходит;
- JSON validation: проходит;
- core cleanliness audit: усилен и повторно запущен после commit `09c49896`;
- MariaDB/MySQL migration validation и HTTP smoke должны считаться подтверждёнными только после завершения текущего CI run;
- production deploy 3.9.0 пока не подтверждён.
