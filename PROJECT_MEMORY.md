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
- KOVCHEG Studio для управления контентом и сайтом;
- темы: `themes/kovcheg-portal`, `themes/kovcheg-editorial`, `themes/kovcheg-portfolio`;
- миграции: `bin/migrate.php` + SQL-файлы из `migrations/`, сортируемые по имени;
- CI: `.github/workflows/ci.yml`;
- production-схема: GitHub -> server -> FastPanel -> site.

## Что выполнено в Core Cleanup 3.9.0

- удалены старые дублирующие route bundles и оставлен единый runtime через `index.php`;
- удалены `BlogBuilder` и `BlogDemoSite`, старые Studio views и builder assets;
- удалены старые VK/X CSS/JS assets и социальные runtime helpers;
- удалены недостижимые legacy-каталоги `views/templates/vk` и `views/templates/x`;
- общий `views/layout.php` заменён на CMS shell;
- Studio переведён на собственные названия и текущие редакторы;
- обновлены PWA manifest и service worker;
- очищен cron от старых социальных задач;
- добавлена миграция `20260809_content_model_cleanup.sql`;
- GitHub Actions объединены в единый `ci.yml`;
- добавлен и усилен `scripts/audit-core-cleanup-3.9.php`;
- README и SECURITY переведены на KOVCHEG Blog / KOVCHEG CMS 3.9;
- создана обязательная проектная память и module implementation log.

## Исправления, найденные аудитом 2026-08-09

- commit `f08ea2a4`: активный `app/modern-ui.php` больше не подключает уже удалённые `vk-structural-fix.css/js`;
- commit `292a8b31`: исправлена опасная legacy-миграция `20260806_z_page_category_core.sql`; Posts больше не преобразуются в Pages, сохраняется только совместимое преобразование старого `portfolio` в Page;
- commit `09c49896`: cleanup audit усилен regression-проверками миграций и stale assets; исторические журналы и старые audit-файлы исключены только из проверки терминологии, чтобы не переписывать историю проекта;
- commit `597dc00b`: созданы `PROJECT_MEMORY.md` и `docs/MODULE-IMPLEMENTATION-LOG.md`, обновлён CHANGELOG 3.9.0;
- commit `7050ba89`: исправлен штатный режим регистрации `manual`, старое `email_approval` сохранено как alias;
- commit `bb944842`: устранён ложный HTTP smoke failure из-за `pipefail` и `curl | grep -q`;
- commit `33b50bc2`: удалены полностью недостижимые VK/X view templates;
- commit `9ec885a4`: Core Cleanup audit теперь запрещает возврат `views/templates/vk` и `views/templates/x`;
- commit `95c8559f`: `docs/DEVELOPMENT_LOG.md` дополнен полным аудитом и состоянием 3.9.0.

## Проверенное состояние CI

GitHub Actions run #406 завершён SUCCESS после исправления migration/auth/runtime/CI проблем.

GitHub Actions run #408 завершён SUCCESS после удаления VK/X template-layer и усиления cleanup audit.

В обоих итоговых прогонах подтверждены:

- PHP syntax;
- JavaScript syntax;
- JSON validation;
- KOVCHEG Core Cleanup audit;
- MariaDB 11 migrations;
- MySQL 8.4 migrations;
- HTTP smoke основных public/auth/account/Studio маршрутов;
- проверка секретов и runtime data.

## Важные правила миграций

`bin/migrate.php` определяет уже применённые миграции по имени файла. Исторические имена SQL нельзя переименовывать или удалять без анализа production-таблицы `migrations`.

Новая `20260809_content_model_cleanup.sql` идемпотентна: старый `portfolio` становится Page, категории удаляются у Pages, Posts должны оставаться Posts.

Production social-таблицы нельзя удалять автоматически только потому, что они больше не нужны новой CMS-модели: сначала backup и проверка наличия пользовательских данных, затем отдельная migration strategy.

## Что ещё не завершено

- корневые старые social views (`feed`, `messenger`, `profile`, `channel`, `wall`, reaction layers) ещё находятся в дереве и требуют отдельной проверки ссылок;
- `app/functions.php` всё ещё содержит старые chat/profile/channel/colleague/push helpers; удалять их нужно только после карты реальных вызовов;
- `database/schema.php` для чистой установки всё ещё содержит social baseline (`chats`, `messages`, follows/colleagues и связанные таблицы); baseline нужно очистить отдельно от production migration;
- исторические audit-скрипты и документация прошлых этапов сохраняются как история и не являются активным runtime;
- production 3.9.0 ещё не подтверждён через SSH/FastPanel: нужны backup, `git pull`/fast-forward, `php bin/migrate.php`, cache clear, права storage, PHP/DB и реальные HTTP checks.

## Обязательный порядок дальнейшей работы

1. Проверить HEAD и CI перед каждым новым пакетом.
2. Построить карту использования оставшихся root social views/helpers.
3. Удалять доказанно неиспользуемый legacy небольшими пакетами с отдельными commits.
4. Очистить schema baseline новой установки без destructive production migration.
5. После каждого пакета запускать CI и обновлять журналы.
6. После полностью зелёного CI выполнить controlled production deploy через GitHub -> server -> FastPanel, без ручного копирования файлов.
7. После deploy проверить migrations, права, cache, public routes и Studio.

## Нельзя ломать

- существующие Posts и Pages;
- пользовательские медиа и uploads;
- меню, виджеты и зоны;
- роли и доступ Studio;
- branding;
- темы и публичные canonical URL;
- историю уже применённых migration filenames;
- production-данные старых social-таблиц до backup и анализа.
