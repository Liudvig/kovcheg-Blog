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
- обновлена версия приложения и ревизия статических файлов для сброса браузерного кеша.

Как проверено:
- php -l для themes/kovcheg-portal/layout.php;
- php -l для app/bootstrap.php;
- php -l для scripts/audit-portal-ui.php;
- статический аудит порядка подключения portal-ui-repair.css после blog-widgets.css;
- проверка обязательных CSS-правил и баланса фигурных скобок;
- добавлен GitHub Actions workflow KOVCHEG Portal UI checks;
- полный CI проекта запускается через pull request.

Commits:
- d4bce56dc20175929c38fdb7426ed6cf4abe4e81 — scoped Portal UI stylesheet;
- 42ff5f2e82ed6181a5144539a88757cf97d03045 — подключение stylesheet;
- b479a4917fbc391decdbeb71fc65c81b1a94e243 — версия 3.5.5 и cache revision;
- b11c4da03c7bc27fe988388be258ef3f7b2451b3 — regression audit;
- 7037e571f2696b3778db9cfaa2840d625c8d5aa7 — GitHub Actions workflow;
- e888701e4ddbc3cba52b6f07dca505a0d0c908c7 — release documentation.

Deploy:
Не выполнялся. Production VPS недоступен из текущего подключения; deploy допускается после CI и слияния в main.

Статус:
READY FOR PULL REQUEST
