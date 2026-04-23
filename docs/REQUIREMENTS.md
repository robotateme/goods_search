# Requirements

## Staging Без Docker

Ниже требования для отдельного staging-окружения, где приложение запускается напрямую на сервере без Docker и Sail.

### Системные требования

- Linux-сервер
- PHP `8.3+`
- Composer `2+`
- Node.js `20+`
- npm `10+`
- PostgreSQL `16+`
- Redis `7+`
- Meilisearch `1.16+`
- Nginx или Apache для отдачи `public/`
- `systemd` или `supervisor` для фоновых процессов

### Требуемые PHP extensions

Минимально нужны:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `intl`
- `json`
- `mbstring`
- `openssl`
- `pcntl`
- `pdo_pgsql`
- `redis`
- `tokenizer`
- `xml`

### Внешние сервисы

На staging должны быть доступны:

- PostgreSQL для основных данных и Laravel migrations
- Redis для `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` и search cache
- Meilisearch для полнотекстового поиска по `q`

Если `SEARCH_DRIVER=meilisearch`, приложение ожидает, что Meilisearch доступен до запуска индексации и очередей.

### Обязательные env-переменные

Базовый набор для staging:

```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.example.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=goods_search
DB_USERNAME=goods_search
DB_PASSWORD=secret

SESSION_DRIVER=file
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90

SEARCH_DRIVER=meilisearch
SEARCH_PRODUCTS_INDEX=products
SEARCH_CACHE_ENABLED=true
SEARCH_CACHE_TTL_SECONDS=300
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
```

Дополнительно нужно заполнить:

- `APP_KEY`
- `LOG_CHANNEL`
- `MAIL_*`, если на staging нужна реальная почта
- `MEILISEARCH_KEY`, если Meilisearch закрыт ключом

### Что должно быть на сервере

Директории проекта должны быть доступны пользователю, под которым работает PHP-FPM и queue worker.

Нужно обеспечить права записи в:

- `storage/`
- `bootstrap/cache/`

Докрутить веб-сервер нужно так, чтобы document root указывал на `public/`.

### Установка и первый запуск

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan search:products:sync
php artisan search:products:import
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Если каталог на staging не нужен тестовыми данными, шаг `php artisan db:seed --force` можно пропустить.

### Обязательные фоновые процессы

Для staging недостаточно только PHP-FPM. Должны работать отдельные процессы:

1. Queue worker:

```bash
php artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=90
```

2. По желанию scheduler, если появятся scheduled tasks:

```bash
php artisan schedule:work
```

Сейчас критичен именно `queue:work`, потому что через очередь идут:

- индексация Meilisearch
- фоновые search jobs
- deduplicated dispatch для индексации

### Что проверить после деплоя

- `php artisan about`
- `php artisan migrate:status`
- `php artisan scout:status` при использовании Scout/Meilisearch
- `curl "https://staging.example.com/api/products?page=1&per_page=20"`
- наличие активного queue worker процесса
- доступность Redis и Meilisearch с сервера приложения

### Типовые причины падения staging

- `CACHE_STORE=database` без таблицы `cache`
- `SESSION_DRIVER=database` без таблицы `sessions`
- не запущен `queue:work`
- `MEILISEARCH_HOST` недоступен с сервера
- нет прав на `storage/` и `bootstrap/cache/`
- не выполнены `search:products:sync` и `search:products:import` после миграций/сидов

### Короткий чек-лист

- PHP `8.3+` с `pdo_pgsql` и `redis`
- PostgreSQL поднят и доступен
- Redis поднят и доступен
- Meilisearch поднят и доступен
- собраны фронтенд-ассеты
- выполнены миграции
- запущен queue worker
- `APP_DEBUG=false`
