# KOVCHEG Blog Development Log

Автор: Ланцет Семён Борисович

Copyright: Ланцет Семён Борисович

License: proprietary / all rights reserved

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
