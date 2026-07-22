# Развёртывание KOVCHEG Blog 3.1 в FASTPANEL

Эта инструкция рассчитана на сайт `kovchegcms.ru` с каталогом:

```text
/var/www/kovchegcms_r_usr/data/www/kovchegcms.ru
```

В FASTPANEL для сайта должен быть включён PHP FastCGI 8.3. Если PHP-файлы скачиваются браузером, сайт настроен как статический и обновление выполнять нельзя до включения PHP-обработчика.

## Перед обновлением

Работайте от `root`, но файлы сайта должны принадлежать пользователю FASTPANEL `kovchegcms_r_usr`.

```bash
sudo -i

WEBROOT="/var/www/kovchegcms_r_usr/data/www/kovchegcms.ru"
OWNER="kovchegcms_r_usr"
STAMP="$(date +%Y%m%d-%H%M%S)"

cd "$WEBROOT"

tar -czf "/root/kovcheg-blog-files-$STAMP.tar.gz" \
  --exclude='.git' \
  --exclude='storage/cache/*' \
  --exclude='storage/logs/*' \
  .

php -r '$c=require "config/config.php"; echo $c["database"]["name"]."\n";' 
```

Сделайте дамп базы, подставив имя базы и пользователя из `config/config.php`:

```bash
mysqldump --single-transaction --routines --triggers \
  -u ИМЯ_ПОЛЬЗОВАТЕЛЯ -p ИМЯ_БАЗЫ \
  > "/root/kovcheg-blog-db-$STAMP.sql"
```

Проверьте наличие резервных копий:

```bash
ls -lh "/root/kovcheg-blog-files-$STAMP.tar.gz" "/root/kovcheg-blog-db-$STAMP.sql"
```

## Получение обновления

После слияния релиза в ветку `main`:

```bash
cd "$WEBROOT"

git fetch origin
git checkout main
git pull --ff-only origin main
```

Файл `config/config.php` и содержимое `storage` исключены из Git и должны сохраниться.

## Runtime-каталоги и права

```bash
mkdir -p \
  "$WEBROOT/storage/uploads" \
  "$WEBROOT/storage/cache" \
  "$WEBROOT/storage/logs" \
  "$WEBROOT/storage/backups" \
  "$WEBROOT/storage/builds" \
  "$WEBROOT/modules"

chown -R "$OWNER:$OWNER" "$WEBROOT"
find "$WEBROOT" -type d -exec chmod 755 {} +
find "$WEBROOT" -type f -exec chmod 644 {} +
chmod 750 "$WEBROOT/config"
chmod 640 "$WEBROOT/config/config.php"
chmod -R 775 "$WEBROOT/storage"
```

## Проверка PHP

```bash
cd "$WEBROOT"

php -v | head -n 1
find app routes views themes bin -type f -name '*.php' -print0 | xargs -0 -n1 php -l
node --check assets/js/blog-studio.js
```

## Применение миграций

```bash
cd "$WEBROOT"
sudo -u "$OWNER" php bin/migrate.php
```

Ожидаемый результат:

```text
APPLY 20260721_blog_foundation.sql
APPLY 20260721_blog_studio.sql
DONE  Applied 2 migration(s), batch 1.
```

При повторном запуске миграции должны выводиться как `SKIP`, а итог — `Database is up to date`.

## Проверка сайта

```bash
curl -kI https://kovchegcms.ru/ | head -n 15
curl -kI https://kovchegcms.ru/blog | head -n 15
curl -kI https://kovchegcms.ru/studio | head -n 15
```

Главная и блог должны отвечать HTML. `/studio` без авторизации может перенаправить на вход.

После входа владельца откройте:

```text
https://kovchegcms.ru/studio
```

Создайте тестовый черновик, затем тестовую публикацию и проверьте комментарий обычного пользователя.

## Cron

Планировщик ядра нужен для очистки, Webhook, Push и других фоновых задач. Пример запуска раз в минуту:

```cron
* * * * * /usr/bin/php /var/www/kovchegcms_r_usr/data/www/kovchegcms.ru/cron.php >/dev/null 2>&1
```

Используйте путь к PHP, настроенному для сайта в FASTPANEL.

## Откат файлов

```bash
sudo -i
WEBROOT="/var/www/kovchegcms_r_usr/data/www/kovchegcms.ru"

find "$WEBROOT" -mindepth 1 -maxdepth 1 \
  ! -name '.well-known' \
  -exec rm -rf -- {} +

tar -xzf /root/kovcheg-blog-files-ДАТА.tar.gz -C "$WEBROOT"
chown -R kovchegcms_r_usr:kovchegcms_r_usr "$WEBROOT"
```

## Откат базы

Откат базы уничтожит данные, созданные после резервной копии. Выполняйте его только при действительно неработоспособной схеме:

```bash
mysql -u ИМЯ_ПОЛЬЗОВАТЕЛЯ -p ИМЯ_БАЗЫ \
  < /root/kovcheg-blog-db-ДАТА.sql
```

## После успешного обновления

- проверить главную, блог, портфолио и `/studio`;
- проверить создание и редактирование материала;
- проверить загрузку изображения;
- проверить регистрацию, комментарий и реакцию;
- проверить модерацию;
- не удалять резервные копии до окончания проверки релиза.

Автор и правообладатель: Ланцет Семён Борисович.  
Лицензия: proprietary / all rights reserved.
