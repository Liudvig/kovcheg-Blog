# KOVCHEG Blog / KOVCHEG CMS 3.9.0 — Social Runtime Cleanup Audit

Дата: 2026-08-09
Ветка: `feature/core-cleanup-3.9.0`

Автор и правообладатель: Ланцет Семён Борисович
Лицензия: proprietary / all rights reserved

## Цель

Зафиксировать завершение удаления runtime-наследия старой социальной версии из KOVCHEG Blog / KOVCHEG CMS 3.9.0 без destructive удаления production-данных и без переписывания архитектуры ядра.

## Проверенный результат

### Presentation layer

Удалены:
- VK/X template layer;
- feed/messenger/profile/channel/wall/people/settings/social-search/weather social views;
- legacy modern-ui repair layer;
- orphan Essential Widgets presentation/component layer;
- подтверждённо неиспользуемые legacy CSS/JS assets.

Корень `views/` содержит только актуальную CMS/auth/account/Studio оболочку.

### Fresh database model

Новая установка 3.9.0 не создаёт:
- chats/messages;
- profile posts/walls;
- follows/colleague requests;
- stories/push structures;
- VK media tables;
- retired Builder pattern/preset tables;
- tag tables;
- demo categories;
- обязательные Blog/Portfolio menu items;
- portfolio settings.

Fresh baseline сохраняет необходимые CMS/system/auth структуры, включая `user_remember_tokens`.

Historical migration filenames сохранены для совместимости с таблицей `migrations`; retired migrations превращены в безопасные compatibility markers вместо переименования или destructive DROP.

### Active runtime dependency audit

`scripts/audit-active-runtime-social-free.php` проверяет текущие routes, themes, modules, views, cron и активные app-компоненты и запрещает зависимости от retired social tables и social URL helpers.

Проверяемые классы зависимостей включают chats/messages/chat_members/chat_events, profile posts, follows/colleagues, stories, push и старые social navigation helpers.

### Global helper call graph

Для `app/functions.php` создан token-based анализатор `scripts/report-function-usage-3.9.php`.

Перед physical prune call graph показал:
- 164 глобальных function definitions;
- 52 reachable функции;
- 112 unreachable функции;
- 0 reachable social candidates.

Social cleanup выполнялся только после проверки достижимости. Первый проход удалил exact-name social cluster; второй проход удалял только функции, одновременно удовлетворяющие двум условиям:
1. имя относится к social helper pattern;
2. функция отсутствует в transitive runtime call graph.

Любая reachable social-функция приводила бы к abort без изменения `app/functions.php`.

После prune выполнялись:
- `php -l app/functions.php`;
- повторный function usage audit;
- `git diff --check`;
- полный KOVCHEG CMS CI.

`report-function-usage-3.9.php` теперь является постоянным fail-guard и завершает CI ошибкой, если social helper definitions появляются снова.

### Portal Media Widgets

Модуль очищен от:
- VK Video runtime;
- retired `portfolio` content type.

Поддерживаемое видео:
- YouTube;
- Rutube;
- Vimeo.

Content slider:
- Posts;
- Pages;
- All.

Версия модуля: `1.0.1`.
Минимальный core: `3.9.0`.

Добавлена безопасная metadata migration для существующих установок без destructive изменений данных.

### Asset cleanup

Добавлен `scripts/audit-asset-usage-3.9.php`.

CI запрещает:
- возврат явно retired assets;
- бесхозные CSS/JS без ссылок из active source tree.

Удалён старый `assets/css/kovcheg-core.css` и другие подтверждённо неиспользуемые legacy styles/scripts.

## Что НЕ выполнялось

Не выполнялся автоматический DROP старых production social/VK/Builder/tag таблиц.

Причина: production может содержать исторические пользовательские данные. Перед destructive cleanup обязательны:
1. backup файлов;
2. dump production DB;
3. row-count legacy tables;
4. проверка полезных данных;
5. решение об экспорте/архиве;
6. отдельная migration strategy.

## Production

KOVCHEG Blog / KOVCHEG CMS 3.9.0 на момент этого аудита не считается production-deployed.

Следующий отдельный этап:
- проверить production repository state;
- backup файлов и БД;
- fast-forward deploy через GitHub -> server -> FastPanel;
- `php bin/migrate.php`;
- cache clear;
- права storage/uploads;
- PHP/DB verification;
- HTTP smoke public/auth/account/Studio;
- только после этого рассматривать audit существующих legacy production tables.

## Статус

SOCIAL RUNTIME CLEANUP COMPLETE

FRESH CMS MODEL CLEAN

PRODUCTION DEPLOY PENDING
