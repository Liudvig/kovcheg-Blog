# Production Deploy — KOVCHEG Blog 3.5.7

Дата: 2026-08-06  
Production: https://kovchegcms.ru  
Каталог: `/var/www/kovchegcms_r_usr/data/www/kovchegcms.ru`

Автор и правообладатель: **Ланцет Семён Борисович**  
Лицензия: **proprietary / all rights reserved**

## Развёрнутая версия

- версия приложения: `3.5.7`;
- asset revision: `3.5.7-studio-compact-fixes`;
- production commit: `e0c99be8fabfc2c143cd08dc96e9658b9b713e4f`;
- ветка: `main`.

## Резервные копии

Перед обновлением созданы:

- файлы: `/root/kovcheg-blog-backup-20260806-053443/files.tar.gz`;
- база данных: `/root/kovcheg-blog-backup-20260806-053443/database.sql.gz`.

## Миграции

Все существующие миграции отмечены `SKIP`. Результат:

```text
DONE  Database is up to date.
```

Новые миграции для версии 3.5.7 не требовались.

## Проверки на production

Успешно выполнены:

- PHP lint `app/bootstrap.php`;
- PHP lint `routes/blog-ux-fixes.php`;
- PHP lint `views/studio/layout.php`;
- PHP lint `scripts/audit-classic-editor.php`;
- PHP lint `scripts/audit-studio-compact.php`;
- `Classic editor and demo audit OK`;
- `Studio compact UX audit OK`;
- `Portal UI audit OK`.

Проверены HTTP-маршруты:

- `/` — HTTP 200;
- `/blog` — HTTP 200;
- `/portfolio` — HTTP 200;
- `/studio` — HTTP 200;
- `/assets/css/blog-studio-compact.css` — HTTP 200;
- `/assets/js/blog-classic-editor.js` — HTTP 200.

## Проверка исправления 404 портфолио

Первоначальная проверка через `curl -I` вернула HTTP 404, потому что ключ `-I` отправляет запрос методом `HEAD`, а прикладной маршрут зарегистрирован для `GET`. Это была ошибка smoke-команды, а не ошибка сайта.

Повторная проверка обычным GET-запросом подтвердила исправление:

```text
GET /portfolio/__missing-portfolio-test-357__
HTTP/1.1 302 Found
```

После следования перенаправлению:

```text
HTTP 200
FINAL https://kovchegcms.ru/portfolio
```

Таким образом, несуществующая или устаревшая ссылка работы портфолио больше не оставляет посетителя на тупиковой странице `404 — Работа портфолио не найдена`, а переводит в архив портфолио.

## Примечание по Git

Команда `git log` от пользователя `root` вернула `detected dubious ownership`, поскольку production-репозиторий принадлежит пользователю FASTPANEL `kovchegcms_r_usr`. Это не ошибка репозитория и не влияет на сайт. Git-команды в production следует запускать так:

```bash
sudo -u kovchegcms_r_usr -H git -C /var/www/kovchegcms_r_usr/data/www/kovchegcms.ru log -1 --oneline
```

Глобальное добавление каталога в `safe.directory` для root не требуется.

## Итог

Статус: **DEPLOY SUCCESS**

Версия 3.5.7 развёрнута, база актуальна, основные страницы и статические файлы доступны, а исправление маршрута портфолио подтверждено реальным GET-запросом.
