# KOVCHEG Blog 3.6.0 — Validation Steps

1. Выполнить PHP lint новых и изменённых файлов.
2. Выполнить `node --check assets/js/blog-classic-editor.js`.
3. Запустить `php scripts/audit-wordpress-simple-core.php` и совместимые продуктовые аудиты.
4. Проверить миграцию на MariaDB и MySQL.
5. Проверить маршруты `/studio/posts`, `/studio/pages`, `/studio/categories`, `/blog/{slug}`, `/page/{slug}`.
6. После успешного CI перенести итоговую запись в `docs/DEVELOPMENT_LOG.md`, удалить временные файлы этой ветки и слить pull request.
