# KOVCHEG Blog — модуль формата 2

Модуль формата 2 добавляет функции в KOVCHEG без перезаписи файлов ядра. Установка выполняется через **KOVCHEG Studio → Модули**.

## Структура ZIP

```text
manifest.json
bootstrap.php
src/
routes/
views/
assets/
migrations/
```

Обязательны `manifest.json` и `bootstrap.php`. Остальные каталоги создаются по необходимости.

## Пример manifest.json

```json
{
  "slug": "contact-form",
  "name": "Контактная форма",
  "version": "1.0.0",
  "description": "Форма заявки для страниц сайта",
  "min_core": "3.2.0",
  "min_php": "8.1",
  "author": "Имя автора",
  "copyright": "Имя правообладателя",
  "license": "proprietary",
  "extensions": ["mbstring"],
  "migrations": ["migrations/001_create_contact_requests.sql"]
}
```

## bootstrap.php

`bootstrap.php` загружается при каждом запросе, когда модуль включён. Он должен только подключать классы, регистрировать хуки и подготавливать маршруты.

Пример:

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/src/ContactModule.php';

\Kovcheg\Hooks::on('routes', static function ($router) {
    require __DIR__.'/routes/web.php';
    return $router;
});
```

## Разрешённые PHP-файлы

- `bootstrap.php`;
- `src/*.php` — классы и сервисы;
- `routes/*.php` — маршруты;
- `views/*.php` — шаблоны модуля.

PHP-файлы в других каталогах установщик отклоняет.

## Разрешённые типы файлов

- PHP;
- JSON;
- SQL;
- CSS и JavaScript;
- SVG, PNG, JPEG, WebP и GIF;
- WOFF и WOFF2;
- TXT, Markdown, HTML, XML и CSV.

Скрипты оболочки, бинарные библиотеки, PHAR, PHTML, CGI, EXE и аналогичные исполняемые файлы запрещены.

## SQL-миграции

Миграции перечисляются в `manifest.json`. Каждая миграция выполняется один раз и записывается в таблицу `module_migrations`.

Миграция должна быть обычным SQL-файлом:

```sql
CREATE TABLE IF NOT EXISTS contact_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Автоматического удаления таблиц при удалении модуля нет. Это предотвращает случайную потерю пользовательских данных.

## Проверки безопасности

Установщик проверяет:

- максимальный размер ZIP;
- максимальное число файлов;
- максимальный распакованный размер;
- обязательные поля manifest.json;
- минимальную версию KOVCHEG и PHP;
- необходимые расширения PHP;
- пути с `..`, абсолютные пути и обратные слеши;
- символические ссылки;
- расположение PHP-файлов;
- запрещённые исполняемые расширения;
- использование `eval`, `shell_exec`, `passthru`, `proc_open` и `popen`.

Эти проверки снижают риск, но модуль всё равно является серверным кодом. Устанавливайте пакеты только из доверенного источника.

## Обновление

Повторная установка пакета с тем же `slug` заменяет файлы модуля. Старая версия временно сохраняется до успешной записи новой версии в базу.

Уже применённые SQL-миграции повторно не выполняются.

## Включение и отключение

Отключение модуля сохраняет его файлы и данные, но `bootstrap.php` перестаёт загружаться.

Удаление стирает файлы модуля и запись о нём. Таблицы и пользовательские данные модуля остаются в базе.

Автор и правообладатель KOVCHEG Blog: **Ланцет Семён Борисович**.  
Лицензия ядра: **proprietary / all rights reserved**.
