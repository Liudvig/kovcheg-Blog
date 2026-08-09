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
- общий `views/layout.php` заменён на CMS shell;
- Studio переведён на собственные названия и текущие редакторы;
- обновлены PWA manifest и service worker;
- очищен cron от старых социальных задач;
- добавлена миграция `20260809_content_model_cleanup.sql`;
- GitHub Actions объединены в единый `ci.yml`;
- добавлен `scripts/audit-core-cleanup-3.9.php`;
- README и SECURITY переведены на KOVCHEG Blog / KOVCHEG CMS 3.9.

## Исправления, найденные аудитом 2026-08-09

- commit `f08ea2a4`: активный `app/modern-ui.php` больше не подключает уже удалённые `vk-structural-fix.css/js`;
- commit `292a8b31`: исправлена опасная legacy-миграция `20260806_z_page_category_core.sql`; Posts больше не преобразуются в Pages, сохраняется только совместимое преобразование старого `portfolio` в Page;
- commit `09c49896`: cleanup audit усилен regression-проверками миграций и stale assets; исторические журналы и старые audit-файлы исключены только из проверки терминологии, чтобы не переписывать историю проекта.

## Важные правила миграций

`bin/migrate.php` определяет уже применённые миграции по имени файла. Исторические имена SQL нельзя переименовывать или удалять без анализа production-таблицы `migrations`.

Новая `20260809_content_model_cleanup.sql` идемпотентна: старый `portfolio` становится Page, категории удаляются у Pages, Posts должны оставаться Posts.

## Что ещё не завершено

- в дереве остаются старые социальные views, включая `views/templates/vk`, `views/templates/x`, feed/messenger/profile/channel/wall файлы; перед удалением нужно подтвердить отсутствие активных ссылок;
- остаются исторические audit-скрипты и документация прошлых этапов; историю разработки не стирать, а активные obsolete tools удалять только после проверки ссылок;
- production 3.9.0 ещё не подтверждён через SSH/FastPanel: нужны `git pull`, `php bin/migrate.php`, cache clear, права storage, PHP/DB и HTTP smoke;
- фактический CI статус нужно проверять после каждого следующего commit.

## Обязательный порядок дальнейшей работы

1. Проверить HEAD и CI.
2. Проверить использование оставшихся social views/assets/routes.
3. Удалять legacy небольшими пакетами с отдельными commit.
4. После каждого пакета запускать CI и обновлять журналы.
5. После зелёного CI выполнить controlled production deploy через GitHub -> server -> FastPanel, без ручного копирования файлов.
6. После deploy проверить миграции, права, cache, public routes и Studio.

## Нельзя ломать

- существующие Posts и Pages;
- пользовательские медиа и uploads;
- меню, виджеты и зоны;
- роли и доступ Studio;
- branding;
- темы и публичные canonical URL;
- историю уже применённых migration filenames.
