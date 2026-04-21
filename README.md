# Goods Search

Тестовое Laravel-приложение с HTTP API для поиска товаров.

Реализовано:
- асинхронный поиск через очередь
- `POST /api/product-searches`
- `GET /api/product-searches/{id}`
- фильтрация по `q`, `price_from`, `price_to`, `category_id`, `in_stock`, `rating_from`
- сортировка `price_asc`, `price_desc`, `rating_desc`, `newest`
- обязательная пагинация
- отдельный `Domain` слой для поиска товаров
- `declare(strict_types=1);` во всем проектном PHP-коде
- Redis-очереди
- `QueueBus` как порт в `Application` и реализация в `Infrastructure`
- поиск по `q` через Meilisearch
- индексирование, вынесенное в `Infrastructure`

## Стек

- PHP 8.3+
- Laravel 13
- PostgreSQL
- Redis
- Meilisearch
- Docker Compose / Laravel Sail

## API

Endpoint:

```text
POST /api/product-searches
```

Параметры запроса:
- `q` — полнотекстовый поисковый запрос через Meilisearch
- `price_from`
- `price_to`
- `category_id`
- `in_stock=true|false`
- `rating_from`
- `sort=price_asc|price_desc|rating_desc|newest`
- `page`
- `per_page`

Создание search job:

```text
POST /api/product-searches
{
  "q": "mouse",
  "price_from": 100,
  "price_to": 300,
  "category_id": 2,
  "in_stock": "true",
  "rating_from": 4,
  "sort": "price_asc",
  "page": 1,
  "per_page": 20
}
```

Ответ на создание:

```json
{
  "id": "9d9020f5-ec7c-46b1-b72e-7f47f0c8d9d1",
  "status": "pending",
  "status_url": "http://localhost/api/product-searches/9d9020f5-ec7c-46b1-b72e-7f47f0c8d9d1"
}
```

Проверка статуса и результата:

```text
GET /api/product-searches/{id}
```

Когда job завершён, endpoint возвращает `status=completed` и `result` с полями:
- `current_page`
- `data`
- `from`
- `last_page`
- `per_page`
- `to`
- `total`

Поля товара в `result.data`:
- `id`
- `name`
- `price`
- `category_id`
- `in_stock`
- `rating`
- `created_at`
- `updated_at`

Если `q` передан, фоновой job использует инфраструктурный Meilisearch-адаптер. Если `q` пустой, используется database fallback.

## Архитектура

Поиск и индексирование вынесены из контроллера и модели в отдельные слои:

- domain entity: [src/Domain/Product/Product.php](src/Domain/Product/Product.php)
- domain criteria: [src/Domain/Product/ProductSearchCriteria.php](src/Domain/Product/ProductSearchCriteria.php)
- domain sort enum: [src/Domain/Product/ProductSort.php](src/Domain/Product/ProductSort.php)
- domain page result: [src/Domain/Product/ProductPage.php](src/Domain/Product/ProductPage.php)
- query: [src/Application/Queries/SearchProductsQuery.php](src/Application/Queries/SearchProductsQuery.php)
- handler: [src/Application/Handlers/SearchProductsHandler.php](src/Application/Handlers/SearchProductsHandler.php)
- queued search job: [app/Jobs/RunProductSearchJob.php](app/Jobs/RunProductSearchJob.php)
- порт поиска: [src/Application/Contracts/Search/ProductSearch.php](src/Application/Contracts/Search/ProductSearch.php)
- порт индексирования: [src/Application/Contracts/Search/ProductSearchIndexer.php](src/Application/Contracts/Search/ProductSearchIndexer.php)
- контракт репозитория: [src/Application/Contracts/Repositories/ProductRepositoryInterface.php](src/Application/Contracts/Repositories/ProductRepositoryInterface.php)
- Eloquent-репозиторий: [src/Infrastructure/Persistence/ProductRepository.php](src/Infrastructure/Persistence/ProductRepository.php)
- Meilisearch search adapter: [src/Infrastructure/Search/MeilisearchProductSearch.php](src/Infrastructure/Search/MeilisearchProductSearch.php)
- Meilisearch indexer: [src/Infrastructure/Search/MeilisearchProductSearchIndexer.php](src/Infrastructure/Search/MeilisearchProductSearchIndexer.php)
- database fallback search: [src/Infrastructure/Search/DatabaseProductSearch.php](src/Infrastructure/Search/DatabaseProductSearch.php)
- document mapper: [src/Infrastructure/Search/ProductSearchDocumentMapper.php](src/Infrastructure/Search/ProductSearchDocumentMapper.php)
- observer для автоиндексации: [src/Infrastructure/Search/ProductObserver.php](src/Infrastructure/Search/ProductObserver.php)

Очереди:

- порт: [src/Application/Contracts/Queue/QueueBus.php](src/Application/Contracts/Queue/QueueBus.php)
- реализация: [src/Infrastructure/Queue/LaravelQueueBus.php](src/Infrastructure/Queue/LaravelQueueBus.php)
- queued search API controllers: [app/Http/Controllers/ProductSearchStoreController.php](app/Http/Controllers/ProductSearchStoreController.php), [app/Http/Controllers/ProductSearchShowController.php](app/Http/Controllers/ProductSearchShowController.php)
- queued indexing jobs: [app/Jobs/IndexProductInSearchJob.php](app/Jobs/IndexProductInSearchJob.php), [app/Jobs/RemoveProductFromSearchJob.php](app/Jobs/RemoveProductFromSearchJob.php), [app/Jobs/SyncProductSearchSettingsJob.php](app/Jobs/SyncProductSearchSettingsJob.php), [app/Jobs/ImportProductsToSearchJob.php](app/Jobs/ImportProductsToSearchJob.php)

Границы слоев:

- `app/` содержит Laravel boundary: HTTP/controllers, queued jobs, Eloquent models, service providers
- `src/Domain` содержит типизированные доменные объекты поиска без зависимости на Laravel
- `src/Application` содержит query/handler и порты
- `src/Infrastructure` содержит Eloquent, Redis и Meilisearch адаптеры

DI-привязки разнесены по провайдерам:
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) — bootstrapping observer
- [app/Providers/RepositoryServiceProvider.php](app/Providers/RepositoryServiceProvider.php) — репозитории
- [app/Providers/PortServiceProvider.php](app/Providers/PortServiceProvider.php) — порты и инфраструктурные адаптеры

HTTP boundary не выполняет поиск синхронно: он валидирует входные параметры, сохраняет `product_search_requests`, ставит job в очередь и отдаёт клиенту идентификатор для polling.

## Запуск

Поднять окружение:

```bash
./vendor/bin/sail up -d
```

Установить зависимости:

```bash
composer install
```

Применить миграции:

```bash
./vendor/bin/sail artisan migrate
```

## Redis Queue

В `compose.yaml` добавлен отдельный сервис `queue`, который запускает worker:

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

## Meilisearch

Поисковый драйвер:

```env
SEARCH_DRIVER=meilisearch
SEARCH_PRODUCTS_INDEX=products
MEILISEARCH_HOST=http://meilisearch:7700
```

Настройки индекса лежат в [config/search.php](config/search.php).

После старта контейнеров нужно синхронизировать настройки индекса и импортировать товары:

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
```

Эти команды теперь не индексируют синхронно, а ставят соответствующие jobs в очередь. Автоиндексация `saved/deleted` тоже идёт через очередь.

## Тесты и проверка

Запуск тестов:

```bash
php artisan test
```

Статический анализ:

```bash
vendor/bin/phpstan analyse app src tests routes database --no-progress --memory-limit=512M
```

Тестовое окружение использует `SEARCH_DRIVER=database`, поэтому тесты не зависят от живого Meilisearch.

## Docker services

В `compose.yaml` описаны сервисы:
- `laravel.test`
- `pgsql`
- `redis`
- `meilisearch`
- `queue`

Проверить маршрут API:

```bash
php artisan route:list --path=api
```

## Полезные команды

```bash
php artisan search:products:sync
php artisan search:products:import
php artisan test
vendor/bin/phpstan analyse app src tests routes database --no-progress --memory-limit=512M
```
