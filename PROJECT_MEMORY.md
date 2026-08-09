# KOVCHEG Blog / KOVCHEG CMS — PROJECT MEMORY

Дата актуализации: 2026-08-09
Версия: KOVCHEG Blog 3.9.0 Core Cleanup
Репозиторий: Liudvig/kovcheg-Blog
Рабочая ветка: feature/core-cleanup-3.9.0
Основная ветка: main

Автор и правообладатель: Ланцет Семён Борисович
Лицензия: proprietary / all rights reserved

## Цель продукта

KOVCHEG Blog / KOVCHEG CMS — самостоятельный блоговый и корпоративный CMS-движок для блогов, сайтов организаций, порталов и корпоративных сайтов.

Текущий этап удаляет наследие старой социальной версии, VK/X-шаблоны, демо-слои и чужую CMS-терминологию из активного продукта без переписывания архитектуры и без потери пользовательского контента.

## Активная архитектура

- единый front controller: `index.php`;
- bootstrap: `app/bootstrap.php`;
- публичный контент: Posts и Pages;
- рубрики относятся только к Posts;
- KOVCHEG Studio для управления контентом и сайтом;
- темы: `themes/kovcheg-portal`, `themes/kovcheg-editorial`, `themes/kovcheg-portfolio`;
- installer: `install.php` версии 3.9.0 + `database/schema.php` + SQL migration-chain;
- миграции: `bin/migrate.php` + SQL-файлы из `migrations/`, сортируемые по имени;
- CI: `.github/workflows/ci.yml`;
- production-схема: GitHub -> server -> FastPanel -> site.

## Что выполнено в Core Cleanup 3.9.0

- удалены старые дублирующие route bundles и оставлен единый runtime через `index.php`;
- удалены `BlogBuilder` и `BlogDemoSite`, старые Studio views и builder assets;
- удалены старые VK/X CSS/JS assets и social JavaScript layers;
- удалены `views/templates/vk`, `views/templates/x` и весь доказанно недостижимый root social presentation-layer;
- удалён legacy `app/modern-ui.php` и связанные modern-upload/template-polish/layout-repair assets;
- корень `views/` содержит только `account-shell.php`, `layout.php`, `login.php`, `register.php` и каталог `studio`;
- общий `views/layout.php` заменён на CMS shell;
- Studio переведён на собственные названия и текущие редакторы;
- обновлены PWA manifest и service worker;
- cron очищен от старых социальных задач и сейчас содержит только scheduled publishing, cleanup, auth/system и webhook jobs;
- GitHub Actions объединены в единый `ci.yml`;
- добавлен и усилен `scripts/audit-core-cleanup-3.9.php`;
- README и SECURITY переведены на KOVCHEG Blog / KOVCHEG CMS 3.9;
- создана обязательная проектная память и module implementation log.

## Исправления и cleanup-пакеты 2026-08-09

Ранние пакеты аудита:
- `f08ea2a4` — исправлены stale VK asset references;
- `292a8b31` — Posts больше не превращаются в Pages старой миграцией;
- `09c49896` — усилен cleanup audit;
- `7050ba89` — исправлен `registration_mode=manual`;
- `bb944842` — исправлен HTTP smoke pipe handling;
- `33b50bc2` / `9ec885a4` — удалены и запрещены VK/X view templates;
- `877f6472`, `5b1e4752`, `a4fc7528`, `7292daca` — удалён и запрещён весь root social presentation-layer.

Глубокая очистка runtime и fresh install:
- `b92ab7d7` — удалены `app/modern-ui.php` и пять legacy wall/VK/X assets;
- `58d7cb97` — audit запрещает возврат modern-ui layer;
- `5dec692e` / `7f57a5df` — VK/VK Video удалены из CSP и добавлен regression guard;
- `75f71d5d` / `1398483b` — installer переведён на 3.9.0, применяет migration-chain и больше не создаёт legacy `user_permissions`;
- `ca626ee1` — `database/schema.php` заменён на минимальный CMS/system baseline без social-таблиц; добавлен `user_remember_tokens`;
- `3ba1007d` — историческая `20260719_vk_media_library.sql` превращена в безопасный compatibility marker без CREATE TABLE;
- `604ee4a4` / `70501a03` — audit/CI проверяют чистый CMS baseline на MariaDB и MySQL;
- `1d02ecbf` — из будущих установок удалены `content_patterns`, `site_preset_history` и demo media folder «Портфолио», нужные autosave/media/module части сохранены;
- `393570ef` / `515cfc0d` — regression guards для retired Builder tables;
- `9ac9769c` / `ad730daa` — удалены мёртвые preset methods из Studio32 и исправлен найденный при lint синтаксис storeMedia;
- `33b69f97` / `6c092b43` — fresh install больше не создаёт tag tables, Studio32 больше не синхронизирует tags;
- `f21ce3b5` / `f197d215` — tag model закреплена audit/CI;
- `48649b7c` / `591fa891` — fresh install больше не создаёт demo categories, обязательные Blog/Portfolio menu items и portfolio settings;
- `19bc41ae` — CI проверяет отсутствие demo content после полной migration-chain.

## Проверенное состояние CI

Последняя полностью подтверждённая контрольная точка перед обновлением документации:

GitHub Actions run #444 на commit `19bc41aeb58d08abf4a04f656aabbefec9fde121` — SUCCESS.

Также SUCCESS: #424, #428, #429, #432, #437, #441.

CI подтверждает:
- PHP syntax;
- JavaScript syntax;
- JSON validation;
- KOVCHEG Core Cleanup audit;
- MariaDB 11 migration-chain;
- MySQL 8.4 migration-chain;
- HTTP smoke основных public/auth/account/Studio маршрутов;
- наличие `content_entries` и `user_remember_tokens`;
- отсутствие fresh social/VK/Builder/tag таблиц;
- отсутствие portfolio content после cleanup;
- отсутствие category links у Pages;
- отсутствие demo categories на новой базе;
- отсутствие обязательных `/blog`/`/portfolio` menu items;
- отсутствие `portfolio_description`;
- проверку секретов и runtime data.

## Fresh install 3.9.0

`install.php` устанавливает версию 3.9.0, создаёт базовый system/CMS schema и затем применяет migration-chain с регистрацией migration filenames.

Новая установка не создаёт:
- chats/messages;
- walls/profile posts;
- follows/colleague requests;
- stories/push;
- VK media tables;
- content patterns/preset history;
- tags/tag relations;
- демонстрационные рубрики;
- обязательные пункты меню Blog/Portfolio.

Новая установка создаёт необходимые system/auth таблицы, включая `user_remember_tokens`, roles, settings, audit, rate-limit, modules/API/webhook infrastructure, а migration-chain добавляет current CMS content/media/layout/growth schema.

## Важные правила миграций

`bin/migrate.php` определяет уже применённые миграции по имени файла. Исторические имена SQL нельзя переименовывать или удалять без анализа production-таблицы `migrations`.

Поэтому старые migration filenames сохраняются и при необходимости превращаются в безопасные compatibility migrations вместо удаления/переименования.

Никакая текущая 3.9 migration не выполняет destructive DROP старых production social/VK/Builder/tag таблиц.

Production legacy-таблицы нельзя удалять автоматически: сначала backup, row-count, проверка пользовательских данных и только затем отдельная migration strategy.

## Что ещё не завершено

- `app/functions.php` всё ещё содержит старые chat/profile/channel/colleague/push/wall helpers; файл смешивает их с нужными CMS/system helpers, поэтому массовое удаление без call-map запрещено;
- `app/Core.php` всё ещё содержит старый fallback выбора `site_template=vk/x`, хотя физические templates уже удалены; этот active-code хвост требует отдельного безопасного cleanup;
- `migrations/20260722_blog_visual_zone_builder.sql` хранит старые setting flags; нужно проверить реальные ссылки перед retirement;
- часть CSS-файлов имеет исторические имена, но реально подключается актуальными account/Studio layouts; их нельзя удалять по имени без dependency- и визуального аудита;
- production 3.9.0 ещё не подтверждён через SSH/FastPanel: нужны backup, fast-forward update, `php bin/migrate.php`, cache clear, права storage, PHP/DB и реальные HTTP checks.

## Обязательный порядок дальнейшей работы

1. Проверять HEAD и CI перед каждым новым пакетом.
2. Удалить оставшиеся небольшие active-code compatibility хвосты только после проверки использования.
3. Построить call-map для старых social helpers в `app/functions.php` через routes/themes/modules/cron/bin.
4. Удалять только доказанно неиспользуемые helpers небольшими пакетами.
5. Не создавать destructive DROP для production legacy tables без backup/data audit.
6. После каждого пакета запускать CI и синхронизировать документацию.
7. После полностью зелёного CI выполнить controlled production deploy GitHub -> server -> FastPanel без ручного копирования файлов.
8. После deploy проверить migrations, права, cache, public routes и Studio.

## Нельзя ломать

- существующие Posts и Pages;
- пользовательские медиа и uploads;
- меню, виджеты и зоны;
- роли и доступ Studio;
- branding;
- темы и публичные canonical URL;
- историю уже применённых migration filenames;
- production-данные старых social/VK/Builder/tag таблиц до backup и анализа.


## Завершение Social Helper Cleanup — 2026-08-09

<!-- KOVCHEG_3_9_SOCIAL_HELPER_CLEANUP_FINAL -->
- построен token-based call graph для `app/functions.php`;
- до prune было 164 глобальные функции: 52 reachable, 112 unreachable; social-pattern анализ подтвердил 0 reachable social candidates;
- exact-name и затем общий unreachable-social prune выполнялись только после проверки достижимости и `php -l`;
- удалён недостижимый social helper cluster из `app/functions.php`;
- постоянный `scripts/report-function-usage-3.9.php` теперь является fail-guard: наличие social helper definitions приводит к ошибке CI;
- `scripts/audit-active-runtime-social-free.php` отдельно запрещает активным routes/themes/modules/views/cron/app обращаться к chats/messages/wall/follows/stories/push и retired social URL helpers;
- удалены временные prune/report workflows и одноразовые инструменты;
- production legacy tables не удалялись и требуют отдельного backup/data audit перед destructive migration.

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

