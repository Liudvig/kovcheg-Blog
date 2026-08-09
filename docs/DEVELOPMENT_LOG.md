# KOVCHEG Blog Development Log

Автор: Ланцет Семён Борисович

Copyright: Ланцет Семён Борисович

License: proprietary / all rights reserved

---

## 2026-08-09 — KOVCHEG Blog 3.9.0 Deep Social Helper Cleanup

<!-- KOVCHEG_3_9_SOCIAL_HELPER_CLEANUP_FINAL -->
Версия: KOVCHEG Blog 3.9.0 Core Cleanup
Ветка: feature/core-cleanup-3.9.0

Что выполнено:
- после удаления social presentation-layer и fresh social DB baseline построен call graph глобальных helpers;
- анализ `app/functions.php` подтвердил отсутствие reachable social candidates;
- недостижимый social helper cluster удалён автоматическим token-based prune, который abort'ится при любой reachable цели;
- после prune выполнены PHP lint и повторный reachability audit;
- постоянный function-usage guard теперь запрещает возвращение social helper definitions;
- Active Runtime Social-Free audit запрещает обращения текущих routes/themes/modules/views/cron/app к retired social DB и URL helpers;
- удалены бесхозные legacy assets и orphan Essential Widgets component;
- исправлен stale require удалённого Essential Widgets в `routes/blog-layout.php`, найденный диагностическим HTTP smoke;
- Portal Media Widgets очищен от VK Video/portfolio и обновлён до 1.0.1;
- production legacy tables не удалялись.

Проверка перед записью журнала:
- `php -l app/functions.php`;
- `php scripts/audit-core-cleanup-3.9.php`;
- `php scripts/audit-active-runtime-social-free.php`;
- `php scripts/report-function-usage-3.9.php`.

Production:
3.9.0 пока не деплоился; server/FastPanel/production DB verification остаётся следующим отдельным этапом.

Статус:
SOCIAL RUNTIME CLEANUP COMPLETE — PRODUCTION DEPLOY PENDING

---

## 2026-08-09 — KOVCHEG Blog 3.9.0 Social View Layer Cleanup Completed

Версия:
KOVCHEG Blog 3.9.0 Core Cleanup

Ветка:
feature/core-cleanup-3.9.0

Что выполнено:
- построена карта активного runtime через `index.php`, текущие routes, `app/Blog.php`, hooks и modules;
- подтверждено, что старые VK/X и root social views больше не вызываются текущей CMS;
- удалены `views/templates/vk` и `views/templates/x`;
- удалён первый пакет из 21 старого root social view: feed, messenger, profile, channel, wall, avatar/reaction и conversation presentation layers;
- после отдельной проверки содержимого удалены `views/people.php`, `views/settings.php`, `views/mobile-navigation.php`, `views/search.php`, `views/site-sidebar.php`, `views/weather-widget.php`, `views/weather.php`;
- `scripts/audit-core-cleanup-3.9.php` расширен regression guards для всех удалённых social views;
- после cleanup корень `views/` содержит только `account-shell.php`, `layout.php`, `login.php`, `register.php` и `studio/`;
- выполнен dependency-аудит CSS/JS: массовое удаление исторически названных assets отложено, потому что часть из них реально подключается актуальными account/Studio layouts.

Commits этапа:
- `877f6472` — Remove dead social root views;
- `5b1e4752` — Guard removed social root views;
- `a4fc7528` — Remove remaining legacy social views;
- `7292daca` — Guard remaining legacy social views;
- `bcfc5c93` — Update 3.9 cleanup README state;
- `564ff6fe` — Update 3.9 cleanup project memory;
- `5e47ddc7` — Update 3.9 module cleanup log;
- `1e5f21d3` — Update 3.9 core cleanup changelog.

CI:
- run #412 — SUCCESS после первого пакета root social view cleanup;
- run #414 — SUCCESS после полной очистки оставшихся root social views;
- подтверждены PHP syntax, JavaScript syntax, JSON validation, Core Cleanup audit, MariaDB 11, MySQL 8.4, HTTP smoke и security/runtime-data checks.

Что осталось:
- `app/functions.php` всё ещё содержит старые chat/profile/channel/colleague/push helpers; удалять их можно только после call-map активных routes/themes/modules/cron/bin;
- `database/schema.php` всё ещё содержит social baseline; baseline новой установки нужно очищать отдельно от production migration;
- destructive DROP production social tables запрещён до backup и анализа фактических данных;
- часть CSS имеет исторические имена, но реально используется текущими layouts, поэтому требует отдельного visual/dependency cleanup;
- production 3.9.0 не деплоился и не проверялся изнутри сервера.

Следующий этап:
1. Call-map social helpers в `app/functions.php`.
2. Малые commits по удалению только доказанно неиспользуемых helpers.
3. Отдельный audit schema/migrations и безопасный cleanup baseline новой установки.
4. После зелёного CI — controlled production deploy GitHub -> server -> FastPanel с backup, migrations, cache clear и HTTP verification.

Статус:
SOCIAL VIEW LAYER CLEANUP COMPLETE — CI GREEN — HELPERS/DB CLEANUP PENDING — PRODUCTION DEPLOY PENDING

---

## 2026-08-09 — KOVCHEG Blog 3.9.0 Core Cleanup Audit and Repair

Версия:
KOVCHEG Blog 3.9.0 Core Cleanup

Ветка:
feature/core-cleanup-3.9.0

Что проверено:
- фактический HEAD рабочей ветки, история commits и отличие от `main`;
- наличие заявленных удалений и новых файлов Core Cleanup;
- `README.md`, `SECURITY.md`, `CHANGELOG.md`, `PROJECT_MEMORY.md`, `docs/DEVELOPMENT_LOG.md`, `docs/MODULE-IMPLEMENTATION-LOG.md`;
- единый runtime через `index.php` и активные route-файлы;
- GitHub Actions, PHP/JS/JSON syntax, MariaDB/MySQL migrations, HTTP smoke и security checks;
- оставшиеся VK/X/social views, social helpers и legacy database schema;
- безопасность порядка и повторного применения SQL migrations;
- состояние production-документации и возможность проверки GitHub -> server -> FastPanel.

Что обнаружено и исправлено:
- `app/modern-ui.php` подключал уже удалённые `vk-structural-fix.css/js`; ссылки удалены;
- `20260806_z_page_category_core.sql` на чистой установке преобразовывал Posts в Pages; миграция исправлена и теперь преобразует только старый `portfolio` в Page;
- cleanup audit был недостаточно точным: усилены проверки migration safety и stale assets, при этом историческая документация не переписывается ради терминологического lint;
- Studio и CI использовали `registration_mode=manual`, а auth runtime воспринимал его как закрытую регистрацию; добавлена совместимость `manual` и старого `email_approval` alias;
- HTTP smoke ошибочно падал из-за `set -o pipefail` и `curl | grep -q`; проверка переведена на временные файлы;
- удалены полностью недостижимые legacy каталоги `views/templates/vk` и `views/templates/x`;
- cleanup audit теперь запрещает возврат этих VK/X presentation templates.

Commits текущего аудита и ремонта:
- `f08ea2a4` — Fix 3.9 cleanup runtime assets;
- `292a8b31` — Preserve posts in legacy migration;
- `09c49896` — Harden 3.9 cleanup audit;
- `597dc00b` — Document KOVCHEG Blog 3.9 cleanup state;
- `7050ba89` — Fix manual registration mode;
- `bb944842` — Fix CI smoke pipe handling;
- `33b50bc2` — Remove dead VK and X view templates;
- `9ec885a4` — Guard against legacy social templates.

CI:
GitHub Actions run #406 завершён SUCCESS после исправления registration/runtime/CI проблем. Run #408 после удаления VK/X view templates и усиления audit также завершён SUCCESS. Успешно прошли PHP syntax, JavaScript syntax, JSON validation, Core Cleanup audit, MariaDB 11, MySQL 8.4, HTTP smoke и проверка секретов/runtime data.

Оставшийся legacy:
- корневые `feed`, `messenger`, `profile`, `channel`, `wall` и reaction views ещё требуют отдельной проверки ссылок перед удалением;
- `app/functions.php` всё ещё содержит старые chat/profile/channel/colleague/push helpers;
- `database/schema.php` для чистой установки всё ещё содержит часть social-таблиц (`chats`, `messages`, follows/colleagues и связанные структуры);
- production-таблицы нельзя удалять вслепую: перед destructive migration нужен backup и проверка наличия старых пользовательских данных;
- исторические audit/release документы сохраняются как история разработки и не считаются активным runtime.

Production:
KOVCHEG Blog 3.9.0 в рамках этого этапа НЕ деплоился и изнутри сервера не проверялся. В доступном GitHub-коннекторе нет SSH/FastPanel shell, поэтому права файлов, production PHP-FPM, production DB, фактические migrations, cache и production HTTP нельзя считать подтверждёнными. Исторический успешный deploy 3.5.5 не является подтверждением 3.9.0.

Следующий этап:
1. Построить карту использования оставшихся root social views и helpers.
2. Отдельными небольшими commits удалить только доказанно неиспользуемый runtime legacy.
3. Разделить cleanup baseline новой установки и безопасную migration-стратегию для существующей production БД.
4. После полностью зелёного CI выполнить controlled deploy GitHub -> server -> FastPanel с backup, migrations, cache clear и HTTP smoke.

Статус:
CORE CLEANUP AUDIT PASSED — CI GREEN — DEEP SOCIAL/DB CLEANUP AND PRODUCTION DEPLOY PENDING

---

## 2026-08-06

Версия:
3.5.4

Ветка:
main

Что изменено:
Создан обязательный журнал разработки проекта.

Файлы:
docs/DEVELOPMENT_LOG.md

Почему изменено:
Добавлен единый журнал фиксации всех дальнейших изменений проекта.

Ошибки исправлены:
Нет.

Проверка:
Файл создан через GitHub.

Commit:
a1339891b4fea79a55659f55b5514fd6dbd0999c

Deploy:
Не выполнялся. Изменений кода нет.

Статус:
AUDIT STARTED

---

## 2026-08-06 — Portal UI Repair

Версия:
3.5.5

Ветка:
feature/portal-ui-repair-3.5.5

Что изменено:
Выполнен минимальный ремонт публичной темы KOVCHEG Portal после анализа production-скриншота. Добавлен отдельный слой стилей для Layout Matrix, который загружается после общего Widget Engine CSS и исправляет только внешний вид Portal без изменения ядра, рендереров виджетов и базы данных.

Какие файлы изменены:
- themes/kovcheg-portal/assets/portal-ui-repair.css
- themes/kovcheg-portal/layout.php
- app/bootstrap.php
- scripts/audit-portal-ui.php
- scripts/audit-login-routing.php
- scripts/audit-account-actions.php
- scripts/audit-unified-portal.php
- scripts/audit-modern-portal-widgets.php
- scripts/audit-visual-zone-builder.php
- .github/workflows/portal-ui.yml
- docs/releases/KOVCHEG_BLOG_3.5.5.md
- docs/DEVELOPMENT_LOG.md

Почему изменено:
На публичной странице логотип и аватар не имели ограничений внутри новых matrix-ячеек и растягивали шапку примерно до 320 пикселей. Общие стили Widget Engine использовали legacy-селекторы, поэтому меню, рубрики, поиск и подписка отображались без корректного оформления в portal-matrix контейнерах.

Какие ошибки исправлены:
- ограничены размеры логотипа и аватара;
- выровнены название, меню и профиль в шапке;
- устранено наложение виджетов левой колонки;
- боковые слоты переведены с четырёх равных строк на высоту по содержимому;
- исправлено переполнение поиска в правой колонке;
- пустое состояние главной страницы оформлено как полноценный hero-блок;
- форма подписки больше не раздувает подвал;
- исправлено мобильное отображение шапки, меню, колонок и footer;
- обновлена версия приложения и ревизия статических файлов для сброса браузерного кеша;
- audit-скрипты больше не блокируют patch-релизы из-за требования точного номера 3.5.4;
- ASSET_REVISION теперь проверяется на соответствие текущему APP_VERSION.

Как проверено:
- php -l для themes/kovcheg-portal/layout.php;
- php -l для app/bootstrap.php;
- php -l для scripts/audit-portal-ui.php и обновлённых audit-скриптов;
- статический аудит порядка подключения portal-ui-repair.css после blog-widgets.css;
- проверка обязательных CSS-правил и баланса фигурных скобок;
- первый запуск старых workflow выявил жёстко прошитую версию 3.5.4;
- проверки login, account, unified Portal, modern widgets и Layout Matrix переведены на минимальную совместимую версию 3.5.4+;
- повторный CI pull request #29 завершён успешно:
  - KOVCHEG Blog checks — success;
  - KOVCHEG Blog login routing — success;
  - KOVCHEG Blog account actions — success;
  - KOVCHEG Blog Layout Matrix — success;
  - KOVCHEG Portal UI checks — success.

Commits:
- d4bce56dc20175929c38fdb7426ed6cf4abe4e81 — scoped Portal UI stylesheet;
- 42ff5f2e82ed6181a5144539a88757cf97d03045 — подключение stylesheet;
- b479a4917fbc391decdbeb71fc65c81b1a94e243 — версия 3.5.5 и cache revision;
- b11c4da03c7bc27fe988388be258ef3f7b2451b3 — regression audit;
- 7037e571f2696b3778db9cfaa2840d625c8d5aa7 — GitHub Actions workflow;
- e888701e4ddbc3cba52b6f07dca505a0d0c908c7 — release documentation;
- 6dd379d5381edff167732a48ae621fb40fe66422 — release-safe login audit;
- 9266858f595f19d5f4d11f5ee9201388472ee83b — release-safe account audit;
- c63d785acb71d46b51b9a0cdacbeb36a36cf9a76 — release-safe unified Portal audit;
- e4120e960fb4792e326015a35d289e5fe3415ae7 — release-safe modern widgets audit;
- 013591035bafadb129eeebaa87d02096eaba4af0 — release-safe Layout Matrix audit;
- b884983fab6a4110269f9ba4efabd132f7fd63db — updated release documentation;
- df3822a5c25f5f9c59be5f203ea0747392a96435 — CI status and audit history.

Pull request:
#29 — KOVCHEG Blog 3.5.5 — Portal UI Repair

Deploy:
Не выполнялся на момент завершения разработки; выполнен отдельным этапом ниже.

Статус:
MERGED TO MAIN

---

## 2026-08-06 — Production Deploy 3.5.5

Версия:
3.5.5

Ветка:
main

Production:
https://kovchegcms.ru

Каталог:
/var/www/kovchegcms_r_usr/data/www/kovchegcms.ru

Что выполнено:
- проверено состояние production-репозитория;
- подтверждено отсутствие локальных изменений в отслеживаемых файлах;
- создана резервная копия файлов;
- создан дамп production-базы данных;
- выполнен fast-forward с b42e91376eee9e943c4507dead4e6844be18e7df до 23ca73a4c9d7263e94623e0ec074e9165d452759;
- восстановлены и проверены runtime-каталоги и права storage;
- очищен runtime cache;
- повторно запущены миграции;
- выполнены PHP lint и Portal UI audit;
- проверены публичные HTTP-маршруты и новый CSS-файл.

Миграции:
Все миграции отмечены SKIP. База данных актуальна: Database is up to date.

Проверка:
- app/bootstrap.php — синтаксис корректен;
- themes/kovcheg-portal/layout.php — синтаксис корректен;
- scripts/audit-portal-ui.php — синтаксис корректен;
- Portal UI audit OK;
- APP_VERSION = 3.5.5;
- ASSET_REVISION = 3.5.5-portal-ui-repair;
- / — HTTP 200;
- /blog — HTTP 200;
- /portfolio — HTTP 200;
- /studio — HTTP 200;
- /themes/kovcheg-portal/assets/portal-ui-repair.css — HTTP 200.

Production commit:
23ca73a4c9d7263e94623e0ec074e9165d452759

Резервная копия:
/root/kovcheg-blog-backup-20260806-034835

Статус:
DEPLOY SUCCESS

---

## 2026-08-06 — Production Readiness and MOO Audit

Версия:
3.5.5

Ветка:
docs/site-readiness-audit-2026-08-06

Что выполнено:
- проведена оценка технической работоспособности production после деплоя;
- сопоставлены фактические production-проверки, GitHub Actions, README, roadmap и открытые задачи проекта;
- составлен перечень функций, которые ещё не проверены вручную на production;
- составлен перечень функций и работ, которые ещё не завершены до статуса Stable;
- отдельно оценена возможность использования движка для сайта МОО «ЕДИНСТВО»;
- проверено наличие юридико-технических элементов для форм регистрации и подписки;
- изучены актуальные требования Минюста к отчётности НКО с 2026 года;
- изучены требования к публикации политики обработки персональных данных и уведомлению Роскомнадзора.

Создан файл:
docs/AUDIT-2026-08-06.md

Оценка:
- техническая работоспособность production — 8/10;
- статус продукта — рабочая beta / release candidate для контролируемой эксплуатации владельцем;
- для массового распространения как Stable ещё не готов;
- для создания информационного сайта МОО «ЕДИНСТВО» уже пригоден после отдельной настройки, наполнения и добавления юридических страниц;
- публичную регистрацию, подписку, комментарии, обращения и пожертвования нельзя включать для МОО без отдельной настройки обработки персональных данных.

Основные незавершённые направления:
- Module SDK 3;
- единая Safe Embed Platform;
- медиатека Stable-уровня;
- расширенный SEO Manager и Schema.org;
- полная модульность социальных функций;
- внешний security audit;
- accessibility и performance audit;
- восстановление из backup на отдельном стенде;
- двухфакторная авторизация администратора;
- восстановление доступа к учётной записи;
- установочный архив, Git tag, контрольные суммы и rollback-пакет;
- актуализация README и полного релизного комплекта;
- политика обработки персональных данных, согласия и журнал согласий для публичных форм.

Юридическая оценка для МОО:
- с 2026 года отчётность НКО подаётся через Портал Минюста;
- собственный сайт МОО не заменяет обязательную подачу через личный кабинет Минюста;
- собственный сайт можно использовать для публикации устава, документов, новостей и отчётов в целях прозрачности;
- при сборе персональных данных через сайт должна быть доступна политика обработки персональных данных;
- необходимость уведомления Роскомнадзора должна быть проверена до начала автоматизированной обработки;
- текущие формы регистрации и подписки не содержат полноценного юридического блока согласия.

Код изменён:
Нет. Изменена только документация.

Deploy:
Не требуется. Изменений runtime-кода и базы данных нет.

Статус:
AUDIT DOCUMENTED — READY FOR REVIEW

---

## 2026-08-06 — Classic Editor and Demo Site

Версия:
3.5.6

Ветка:
feature/classic-editor-3.5.6

Что изменено:
- классический визуальный редактор в стиле старого WordPress сделан основным режимом создания публикаций, страниц и работ портфолио;
- добавлены отдельные вкладки «Визуально» и «Текст»;
- добавлены заголовки, жирный, курсив, подчёркивание, зачёркивание, списки, цитаты, выравнивание, ссылки, горизонтальная линия, очистка форматирования, отмена и повтор;
- добавлены полноэкранный режим, счётчик слов и знаков, горячие клавиши и встроенный предпросмотр;
- изображения можно вставлять прямо из существующей медиатеки;
- реализовано автосохранение классического содержимого и восстановление автокопии;
- история ревизий сохранена;
- конструктор секций не удалён и оставлен дополнительной вкладкой для сложных посадочных страниц;
- добавлен серверный allowlist-санитайзер HTML, удаляющий script, iframe, обработчики событий и опасные URL;
- старые материалы продолжают открываться через ранее сохранённый content_html;
- добавлен безопасный повторяемый установщик демонстрационного сайта KOVCHEG CMS;
- демонстрационный сайт создаёт только отсутствующие рубрики, страницы, релизную публикацию и главное меню, не удаляя существующие данные;
- после создания демо индексация остаётся выключенной до замены демонстрационного содержания;
- версия приложения повышена до 3.5.6, обновлена ревизия статических файлов;
- добавлены отдельный audit-скрипт, GitHub Actions workflow и release notes.

Файлы:
- app/ClassicEditor.php
- app/BlogStudio32.php
- app/BlogDemoSite.php
- app/bootstrap.php
- assets/js/blog-classic-editor.js
- assets/css/blog-classic-editor.css
- views/studio/editor.php
- views/studio/layout.php
- views/studio/presets.php
- routes/blog-demo.php
- index.php
- scripts/audit-classic-editor.php
- .github/workflows/classic-editor.yml
- docs/releases/KOVCHEG_BLOG_3.5.6.md
- docs/DEVELOPMENT_LOG.md

Почему изменено:
Текущий блочный конструктор оказался неудобен для обычного ведения блога. Для ежедневной публикации новостей и статей нужен простой редактор одного документа, знакомый по классическому WordPress. Одновременно проекту требовался готовый демонстрационный сайт, который можно развернуть без ручного создания стартовых страниц и меню.

База данных:
Новые миграции не требуются. Используются существующие таблицы материалов, метаданных, рубрик, меню, настроек и ревизий.

Проверка перед отправкой в GitHub:
- PHP lint всех новых и изменённых PHP-файлов — успешно;
- node --check assets/js/blog-classic-editor.js — успешно;
- php scripts/audit-classic-editor.php — Classic editor and demo audit OK;
- проверена очистка script, onclick и javascript URL;
- проверено сохранение разрешённых h2, strong и em;
- проверены подключение CSS/JS, маршрут демо-сайта и версия 3.5.6.

Deploy:
Не выполнялся. Допускается после успешного CI, слияния pull request в main и отдельной резервной копии production.

Статус:
IMPLEMENTED — CI PENDING

---

## 2026-08-06 — Studio Compact UX Fixes

Версия:
3.5.7

Ветка:
feature/studio-compact-fixes-3.5.7

Что исправлено:
- убрана из интерфейса надпись «Обычный редактор в стиле классического WordPress: пишите и оформляйте текст как в документе»;
- исправлена кнопка «Добавить медиафайл»: обработчик теперь работает для кнопок вне панели форматирования;
- в окно медиатеки редактора добавлена прямая загрузка JPEG, PNG и WebP;
- загруженный файл сразу появляется в медиатеке редактора и может быть вставлен в текст;
- исправлена кнопка встроенного предпросмотра;
- предпросмотр переведён в sandbox iframe, CSP дополнена локальным frame-src;
- добавлен защищённый Studio Preview для черновиков, приватных материалов и будущих публикаций;
- устранён тупиковый ответ «404 — Работа портфолио не найдена»: опубликованные работы открываются штатно, неопубликованные доступны редактору через Studio Preview, устаревшие ссылки возвращают посетителя в архив портфолио;
- KOVCHEG Studio сделан компактнее во всех разделах;
- боковая панель уменьшена до 224 пикселей;
- верхняя панель уменьшена до 54 пикселей;
- уменьшены карточки, поля, кнопки, таблицы, формы, списки, медиатека, темы, настройки, меню и Widget Studio;
- высота классического редактора уменьшена примерно вдвое до 225 пикселей с внутренней прокруткой;
- правая колонка редактора уменьшена до 278 пикселей;
- нижняя панель уменьшена и скрывается на небольших мобильных экранах;
- версия приложения повышена до 3.5.7 и изменена ревизия assets.

Файлы:
- app/bootstrap.php
- assets/js/blog-classic-editor.js
- assets/css/blog-studio-compact.css
- views/studio/layout.php
- routes/blog-ux-fixes.php
- index.php
- scripts/audit-classic-editor.php
- scripts/audit-studio-compact.php
- .github/workflows/studio-compact.yml
- docs/releases/KOVCHEG_BLOG_3.5.7.md
- docs/DEVELOPMENT_LOG.md

База данных:
Миграции не требуются.

Проверка:
- php -l app/bootstrap.php — успешно;
- php -l routes/blog-ux-fixes.php — успешно;
- php -l views/studio/layout.php — успешно;
- php -l scripts/audit-classic-editor.php — успешно;
- php -l scripts/audit-studio-compact.php — успешно;
- node --check assets/js/blog-classic-editor.js — успешно;
- php scripts/audit-classic-editor.php — Classic editor and demo audit OK;
- php scripts/audit-studio-compact.php — Studio compact UX audit OK;
- GitHub Actions KOVCHEG Studio compact UX checks — success.

Основной commit разработки:
d71dfcbf72687da7fe93e91db63affbb579a2338

Deploy:
Не выполнялся. Разрешён после повторного CI для документационного commit, pull request, слияния в main и резервной копии production.

Статус:
IMPLEMENTED — READY FOR PULL REQUEST

---

## 2026-08-06 — Simple Blog UI

Версия:
3.5.8

Ветка:
feature/simple-blog-ui-3.5.8

Что изменено:
- публичная тема KOVCHEG Portal переведена на компактную блоговую ленту;
- огромный ведущий материал удалён с главной и архивных страниц;
- карточки публикаций используют небольшую миниатюру слева и текст справа, а на телефоне — изображение над текстом;
- ограничены размеры обложек отдельных публикаций и работ портфолио;
- уменьшены общая ширина сайта, боковые колонки, отступы, подвал и карточки;
- портфолио на главной выводится компактным списком;
- в обычном меню Studio скрыты конструктор, пресеты и демо, виджеты и модули;
- старые служебные маршруты перенаправляются в стандартные разделы без физического удаления совместимого кода;
- редактор оставлен только классический, без вкладки конструктора секций;
- редкие поля материала свёрнуты в раздел «Дополнительно»;
- ручная сортировка при добавлении пункта меню убрана, порядок назначается автоматически;
- личный кабинет получил светлую оболочку в стиле KOVCHEG Portal;
- профиль переведён на компактную двухколоночную сетку сайта с сохранением аватара, статуса и стены;
- версия приложения повышена до 3.5.8 и обновлена ревизия статических файлов.

Файлы:
- app/bootstrap.php
- index.php
- routes/blog-simple-mode.php
- themes/kovcheg-portal/home.php
- themes/kovcheg-portal/archive.php
- themes/kovcheg-portal/entry.php
- themes/kovcheg-portal/author.php
- themes/kovcheg-portal/assets/blog-compact.css
- views/studio/layout.php
- views/studio/editor.php
- views/studio/menus.php
- views/account-shell.php
- views/profile.php
- assets/css/blog-studio-simple.css
- assets/css/blog-profile-portal.css
- scripts/audit-simple-blog.php
- scripts/audit-classic-editor.php
- scripts/audit-studio-compact.php
- .github/workflows/simple-blog.yml
- docs/releases/KOVCHEG_BLOG_3.5.8.md
- docs/DEVELOPMENT_LOG.md

База данных:
Миграции не требуются. Схема и существующие данные не изменяются.

Проверка:
- PHP lint изменённых PHP-файлов — успешно;
- Simple blog UI audit — успешно;
- Classic editor audit — успешно;
- Studio compact UX audit — успешно;
- Portal UI audit — успешно;
- GitHub Actions KOVCHEG Simple Blog UI checks — success.

Основной commit разработки:
5cc5a387fa2b0993b2b7d6cecd4d45d211d30009

Deploy:
Не выполнялся. Разрешён после слияния pull request в main и резервной копии production.

Статус:
IMPLEMENTED — CI PASSED

---

## 2026-08-06 — Unified Public Material Routing Repair

Версия:
3.5.8

Ветка:
feature/simple-blog-ui-3.5.8

Обнаруженная ошибка:
Публикации, страницы и работы портфолио не открывались по публичным URL и возвращали 404. Только портфолио переводило владельца в предварительный просмотр, поскольку для него существовал отдельный обходной маршрут.

Корневая причина:
- KOVCHEG Studio считала материал доступным для просмотра только по полю status=published;
- публичный слой дополнительно требовал visibility=public и наступившую дату published_at;
- для публикаций, страниц и портфолио использовались неодинаковые обработчики;
- отдельный portfolio workaround маскировал ошибку маршрутизации и состояния материала;
- ядро Router корректно разбирало динамические URL, проблема находилась в несогласованной логике доступа и выборе обработчика.

Что исправлено:
- добавлен единый обработчик `/blog/{slug}`, `/page/{slug}` и `/portfolio/{slug}`;
- введена общая проверка доступности материала по status, visibility, published_at и роли пользователя;
- материалы visibility=users доступны авторизованным пользователям;
- опубликованные материалы visibility=private доступны владельцу, администратору и редактору;
- черновики, статус private и будущая дата публикации открываются редактору только через защищённый Studio Preview;
- при изменении типа материала старый URL перенаправляется на канонический адрес;
- удалён отдельный portfolio-only workaround;
- несуществующее портфолио больше не маскируется переходом в архив;
- кнопка в Studio показывает «Просмотр» только для реально доступного публичного URL, иначе показывает «Предпросмотр»;
- добавлен отдельный regression audit с реальной проверкой динамических маршрутов ядра.

Файлы:
- app/Blog.php
- index.php
- routes/blog-entry-routing.php
- routes/blog-ux-fixes.php
- views/studio/content-index.php
- scripts/audit-entry-routing.php
- scripts/audit-studio-compact.php
- scripts/audit-unified-portal.php
- scripts/audit-account-actions.php
- .github/workflows/simple-blog.yml
- docs/releases/KOVCHEG_BLOG_3.5.8.md
- docs/DEVELOPMENT_LOG.md

База данных:
Миграции не требуются. Данные и схема базы не изменяются.

Проверка:
- PHP lint изменённых маршрутов, ядра Blog и шаблонов — успешно;
- проверка Router для `/blog/test-material` — успешно;
- проверка Router для `/page/test-page` — успешно;
- проверка Router для `/portfolio/test-work` — успешно;
- audit проверяет отсутствие конфликтующего portfolio workaround;
- старые audit-скрипты адаптированы к единому маршруту и Portal-стилю кабинета;
- итоговый GitHub Actions CI выполняется повторно после исправления регрессионных проверок.

Основные commits:
- 2568aef7e3a8cba15ae3c65cfa42309f2654370b — единая логика видимости и доступности;
- ca9b98fe4ea7d821e2a6aef0821da83254e7002b — единые публичные маршруты;
- a76c9550e381e5ff188fec9346e111b5b8fa7567 — регистрация маршрутов до legacy handlers;
- b6fcf65c9e2ef406b880709fb3ef2aab0574fc5f — удаление portfolio-only workaround;
- 771478ab994022f847ee20cfc67f69d1117caa3d — безопасные кнопки просмотра в Studio;
- c4bb26ab2234ac7d9107e4b7a553314f9ed311f2 — CI маршрутов;
- 31c3a067e9557736a435b6722323a9b86dbdbca1 — совместимость Studio audit;
- 14d105517a9af690eb3c3d3bb99bb65ac565e11f — совместимость unified Portal audit;
- 7b0bb92121532707578daeb678cdf72de5022f20 — исправление literal-проверки regression audit.

Pull request:
#34 — KOVCHEG Blog 3.5.8 — Simple Blog UI

Deploy:
Не выполнялся. Исправление ещё не установлено на production.

Статус:
IMPLEMENTED — FINAL CI RUNNING

---

## 2026-08-06 — Final Page View and Permalink Repair

Версия:
3.5.9

Ветка:
feature/page-final-view-3.5.9

Обнаруженные ошибки:
- полноэкранный Studio Preview не прокручивался из-за фиксированной высоты оболочки KOVCHEG Portal и `overflow:hidden` у документа;
- встроенный предпросмотр классического редактора не обеспечивал устойчивую вертикальную прокрутку iframe;
- опубликованная страница при открытии через старую preview-ссылку оставалась в режиме предварительного просмотра;
- в редакторе отсутствовало понятное постоянное место с итоговым URL материала;
- переход от страницы к добавлению в меню требовал ручного поиска и повторного ввода названия;
- статус «Опубликовано» мог фактически оставлять материал будущим, если в поле даты сохранялось будущее значение.

Что исправлено:
- для полноэкранного Studio Preview добавлен отдельный режим `blog-theme-preview` с естественной высотой документа и вертикальной прокруткой;
- модальный предпросмотр классического редактора получил прокручиваемый iframe и прокручиваемый внутренний документ;
- preview-маршрут опубликованного материала переводит на итоговый канонический URL;
- публикации, страницы и портфолио открываются по итоговым адресам `/blog/{slug}`, `/page/{slug}` и `/portfolio/{slug}`;
- сохранённый черновик можно проверить редактору на его будущем каноническом URL, но посетитель доступа к нему не получает;
- для редакторского просмотра закрытого или неопубликованного материала отправляется `X-Robots-Tag: noindex, nofollow,noarchive`;
- в редактор добавлен постоянный блок «Адрес страницы» с полным URL, slug, кнопкой копирования и кнопкой открытия итоговой страницы;
- URL автоматически обновляется при смене типа материала или slug;
- из редактора опубликованного публичного материала можно перейти в меню с уже выбранной страницей и заполненным названием;
- список материалов теперь отдельно показывает итоговую страницу и, когда требуется, защищённый предпросмотр;
- статус «Опубликовано» нормализует дату на текущий момент, а будущая дата используется только со статусом «Запланировано»;
- версия повышена до 3.5.9, ревизия assets изменена на `3.5.9-page-final-view`.

Файлы:
- app/BlogStudio32.php
- app/bootstrap.php
- routes/blog-entry-routing.php
- routes/blog-ux-fixes.php
- themes/kovcheg-portal/layout.php
- themes/kovcheg-portal/assets/layout-matrix.css
- views/studio/editor.php
- views/studio/content-index.php
- views/studio/menus.php
- assets/js/blog-classic-editor.js
- assets/css/blog-classic-editor.css
- scripts/audit-page-final-view.php
- scripts/audit-entry-routing.php
- scripts/audit-simple-blog.php
- .github/workflows/simple-blog.yml
- docs/releases/KOVCHEG_BLOG_3.5.9.md
- docs/DEVELOPMENT_LOG.md

База данных:
Миграции не требуются. Схема и существующие записи не изменяются.

Проверка:
- PHP syntax — success;
- JavaScript syntax — success;
- Page final view audit — success;
- Public Entry Routing audit — success;
- Simple Blog UI audit — success;
- Classic editor audit — success;
- Studio compact UX audit — success;
- Portal UI audit — success;
- KOVCHEG Blog checks — success;
- KOVCHEG Simple Blog UI checks — success;
- KOVCHEG Classic Editor checks — success;
- KOVCHEG Studio compact UX checks — success;
- KOVCHEG Portal UI checks — success;
- KOVCHEG Blog Layout Matrix — success;
- KOVCHEG Blog login routing — success;
- KOVCHEG Blog account actions — success.

Основные commits:
- b898e04345666fe0d390bd91688831e6a5b9607f — немедленная публикация при статусе published;
- 8561fad3cc5e9f566e5996c432a0938551a87c59 — итоговый рендер материалов на канонических URL;
- ef6de26c83697d26e6c073a3c7f3855d6fa8eb47 — перевод опубликованного preview на итоговую страницу;
- 0971697b22daddfb42cbe5acae6793e183f2a486 — класс полноэкранного preview;
- 8a7b84c8dd9317f91b15d102503528321911428d — восстановление прокрутки Portal Preview;
- ec4626cafbd98581113bf6fa35980ebe34f50157 — постоянная ссылка и меню в редакторе;
- 31b5c2fbf731feae7a1acb9b9855b8aaf474680f — синхронизация URL, копирование и прокрутка iframe;
- 74b9fb50315a3561f77f1dd39b9ec0abbc235d86 — стили permalink и preview;
- 614ec6c2ac5df9a307fe0a06ec4f7dd88500aa6a — итоговые ссылки в списке материалов;
- cad175fce476fc67c9ae4d1826dfdb4ca910a96e — автоматический выбор страницы в меню;
- 41176aef94caf60ef8c438a72087c94a1f36f966 — версия 3.5.9;
- 516a10333ab174e287fa96ccc0fa33a5641256ae — regression audit;
- 3f3d1ee7552af120217040a31195dc2ccf19ca65 — patch-safe simple blog audit;
- 5d09b0262b0c5ab30bbe917dacb3d0354aae9b4f — CI workflow;
- 2bfc66d9911d1fb059744b7ef894a6b73aae2a97 — release notes;
- 4164e6158b1749ca59d0570abf369721cafcb3f8 — обновление routing audit под итоговые страницы.

Pull request:
#35 — KOVCHEG Blog 3.5.9 — Final Page View

Deploy:
Не выполнялся. Разрешён после слияния pull request в main, резервной копии production и отдельной проверки реальных страниц.

Статус:
IMPLEMENTED — CI PASSED — READY TO MERGE

---

## 2026-08-06 — Public Material Page Scroll Repair

Версия:
3.5.10

Ветка:
feature/public-page-scroll-3.5.10

Обнаруженная ошибка:
Итоговые публикации, обычные страницы и работы портфолио открывались, но длинный материал на публичном сайте не прокручивался. Фиксированная desktop-оболочка KOVCHEG Portal блокировала `html` и `body` через `height:100vh` и `overflow:hidden`, а режим естественной прокрутки был включён только для Studio Preview.

Что исправлено:
- итоговое представление `entry` получает отдельный класс `blog-theme-document`;
- добавлен последний CSS-слой `public-page-scroll.css`, который не затрагивает главную и архивы;
- на итоговых материалах сняты ограничения фиксированной высоты и overflow-lock с `html` и `body`;
- viewport, центральная колонка, боковые панели и footer переведены на естественную высоту документа;
- включена прокрутка колёсиком мыши, тачпадом и сенсорным жестом;
- длинные публикации, страницы и портфолио прокручиваются до конца вместе с комментариями, связанными материалами и подвалом;
- шапка остаётся доступной при чтении длинного материала;
- версия приложения повышена до 3.5.10, ревизия assets изменена на `3.5.10-public-page-scroll`.

Файлы:
- app/bootstrap.php
- themes/kovcheg-portal/layout.php
- themes/kovcheg-portal/assets/public-page-scroll.css
- scripts/audit-page-final-view.php
- docs/releases/KOVCHEG_BLOG_3.5.10.md
- docs/DEVELOPMENT_LOG.md

База данных:
Миграции не требуются. Схема и данные не изменяются.

Проверка:
- PHP lint `app/bootstrap.php`;
- PHP lint `themes/kovcheg-portal/layout.php`;
- PHP lint `scripts/audit-page-final-view.php`;
- проверка баланса CSS-скобок;
- Page final view audit проверяет класс документа, подключение CSS, снятие overflow-lock и touch scrolling;
- полный GitHub Actions CI запускается через pull request.

Deploy:
Не выполнялся. Разрешён после успешного CI, слияния pull request в `main`, резервной копии production и проверки реальной длинной страницы.

Статус:
IMPLEMENTED — CI PENDING

---

## 2026-08-06 — WordPress Simple Core

Версия:
3.6.0

Ветка:
feature/wordpress-simple-core-3.6.0

Цель:
Сделать KOVCHEG Blog обычным лёгким блоговым движком с понятной логикой WordPress, без смешивания публикаций, страниц, портфолио, тегов, пресетов и конструкторов в одном рабочем процессе.

Основная модель:
- Записи — новости и статьи, которые выводятся в хронологической ленте;
- Рубрики — тематические разделы только для записей;
- Страницы — постоянные материалы сайта, которые можно привязывать к меню.

Что изменено:
- в Studio созданы отдельные разделы «Записи», «Рубрики» и «Страницы»;
- универсальный список материалов заменён отдельными списками записей и страниц;
- тип материала теперь определяется выбранным разделом и не меняется вручную в редакторе;
- классический редактор документа оставлен единственным основным редактором;
- из редактора убраны портфолио, теги, конструктор секций, ручная сортировка и служебные поля;
- для записей доступны рубрики и краткое описание;
- страницы публикуются отдельно, имеют постоянный URL и кнопку добавления в меню;
- редактор меню принимает страницы, рубрики и произвольные ссылки;
- рубрики больше не прикрепляются к страницам;
- главная страница выводит только записи;
- публичные канонические маршруты оставлены только для `/blog/{slug}` и `/page/{slug}`;
- старый `/portfolio` обслуживается совместимым архивом записей, а старые ссылки работ переводятся на соответствующие страницы или в блог;
- теги убраны из обычной работы и старые адреса тегов переводятся в блог;
- демо-маршрут исключён из runtime;
- сложные конструкторы и Widget Engine скрыты из обычного меню Studio;
- Builder больше не загружается в bootstrap каждого запроса;
- Studio больше не загружает CSS и JavaScript конструкторов на обычных страницах;
- preview и публичный рендер не выполняют запросы тегов и portfolio metadata;
- сохранение материалов больше не зависит от Builder.

Сохранность данных:
- существующие записи и страницы не удаляются;
- существующие работы портфолио переводятся в обычные страницы;
- тексты, изображения, постоянные адреса, ревизии и метаданные сохраняются;
- связи рубрик со страницами удаляются, потому что рубрики относятся только к записям.

Миграция:
- migrations/20260806_wordpress_content_model.sql

Основные файлы:
- app/bootstrap.php
- app/BlogStudio32.php
- index.php
- routes/blog-wordpress-mode.php
- routes/blog-wordpress-compat.php
- routes/blog-entry-routing.php
- routes/blog-ux-fixes.php
- views/studio/layout.php
- views/studio/dashboard.php
- views/studio/entries-index.php
- views/studio/wp-editor.php
- views/studio/categories.php
- views/studio/menus.php
- assets/css/blog-studio-wordpress.css
- themes/kovcheg-portal/home.php
- themes/kovcheg-editorial/entry.php
- scripts/audit-wordpress-simple-core.php
- .github/workflows/wordpress-simple-core.yml
- docs/releases/KOVCHEG_BLOG_3.6.0.md
- docs/DEVELOPMENT_LOG.md

Проверка:
- PHP syntax новых и изменённых файлов — success;
- JavaScript syntax классического редактора — success;
- WordPress Simple Core audit — success;
- Simple Blog UI audit — success;
- Public Entry Routing audit — success;
- Final Page View audit — success;
- Classic Editor audit — success;
- Compact Studio audit — success;
- Portal UI audit — success;
- старые account, login и Layout Matrix проверки адаптированы к облегчённой Studio;
- полный CI повторно запускается после итогового обновления журнала.

Pull request:
#37 — KOVCHEG Blog 3.6.0 — WordPress Simple Core

Deploy:
Не выполнялся. Допускается после успешного итогового CI, слияния pull request в main, резервной копии production и применения миграции.

Статус:
IMPLEMENTED — FINAL CI RUNNING

## 2026-08-09 — Production audit compatibility fix 3.9.0

- Исправлен `scripts/audit-core-cleanup-3.9.php`: production `config/config.php` и `.env` теперь проверяются на отслеживание Git, а не на физическое существование.
- Причина: production-конфиг обязан существовать локально и исключён через `.gitignore`; прежняя `file_exists()`-проверка давала ложный отказ при deploy.
- Production deploy 3.9.0 до исправления корректно остановился и откатил файлы на `8984755a9afe5d0869563f4f83c57d301bd30a3a`; backup: `/root/kovcheg-blog-backup-3.9.0-20260809-115407`.
