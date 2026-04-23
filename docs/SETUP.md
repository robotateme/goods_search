# Setup

## Локальный запуск

Вариант через Docker Compose / Sail:

1. Установить зависимости:

```bash
composer install
npm install
```

2. Поднять сервисы:

```bash
./vendor/bin/sail up -d
```

Данные PostgreSQL, Redis и Meilisearch хранятся в папках внутри проекта:

- `.docker/pgsql`
- `.docker/redis`
- `.docker/meilisearch`

Поэтому удаление docker volumes не стирает локальные данные проекта.

Для PostgreSQL путь данных зафиксирован через `PGDATA=/var/lib/postgresql/18/docker`, поэтому после перезапуска контейнера и после перезагрузки Docker данные остаются в `.docker/pgsql`.

3. Сгенерировать ключ приложения:

```bash
./vendor/bin/sail artisan key:generate
```

4. Применить миграции и заполнить каталог:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

Если папки `.docker/*` удалили, после старта контейнеров снова выполни миграции и сиды.

5. Если используется `SEARCH_DRIVER=meilisearch`, нужно подготовить индекс:

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
```

6. Собрать фронтенд-ассеты:

```bash
npm run build
```

7. Проверить API:

```bash
curl "http://localhost/api/products?page=1&per_page=20"
```

Если Docker не нужен, можно поднять PostgreSQL, Redis и Meilisearch отдельно, настроить `.env` и выполнить:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
php artisan queue:work redis --queue=default
```

## Redis Queue

В `compose.yaml` есть отдельный сервис `queue`, который запускает worker:

```bash
php artisan queue:work redis --queue=default --sleep=1 --tries=3
```

Основные переменные:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90
```

Lua-скрипты лежат в `src/Infrastructure/Redis/Scripts`. Они загружаются через `Infrastructure\Redis\ScriptResolver`. Сейчас они нужны для Redis rate limit и queue deduplication.

## Meilisearch

Поисковый драйвер:

```env
SEARCH_DRIVER=meilisearch
SEARCH_PRODUCTS_INDEX=products
MEILISEARCH_HOST=http://meilisearch:7700
```

Настройки индекса лежат в [config/search.php](../config/search.php).

После старта контейнеров нужно синхронизировать настройки индекса и импортировать товары:

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
```

Эти команды ставят jobs в очередь. Автоиндексация после `saved/deleted` тоже идёт через очередь.

## Фабрики и сиды

Для данных используются:

- [CategoryFactory.php](../database/factories/CategoryFactory.php)
- [ProductFactory.php](../database/factories/ProductFactory.php)
- [CatalogSeeder.php](../database/seeders/CatalogSeeder.php)

Базовое сидирование:

```bash
./vendor/bin/sail artisan db:seed
```

Быстрое наполнение каталога для тестов нагрузки:

```bash
./vendor/bin/sail artisan catalog:seed 5000 12
./vendor/bin/sail artisan catalog:seed 50000 20
```

После большого импорта товаров для `SEARCH_DRIVER=meilisearch` лучше отдельно запустить массовую индексацию:

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
```

Seeder отключает model events, чтобы не создавать тысячи отдельных jobs. Поэтому для полнотекстового индекса после сидирования используется отдельный импорт.

## Docker Services

В `compose.yaml` описаны сервисы:

- `laravel.test`
- `pgsql`
- `redis`
- `meilisearch`
- `queue`

Данные сервисов хранятся не в docker named volumes, а в локальных папках:

- `.docker/pgsql`
- `.docker/redis`
- `.docker/meilisearch`

Проверить маршрут API:

```bash
./vendor/bin/sail artisan route:list --path=api
```
