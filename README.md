# Goods Search

Тестовое Laravel-приложение с HTTP API для поиска товаров.

Реализовано:
- `GET /api/products`
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
GET /api/products
```

Поля товара:
- `id`
- `name`
- `price`
- `category_id`
- `in_stock`
- `rating`
- `created_at`
- `updated_at`

Query-параметры:
- `q` — полнотекстовый поисковый запрос через Meilisearch
- `price_from`
- `price_to`
- `category_id`
- `in_stock=true|false`
- `rating_from`
- `sort=price_asc|price_desc|rating_desc|newest`
- `page`
- `per_page`

Пример:

```text
GET /api/products?q=mouse&price_from=100&price_to=300&category_id=2&in_stock=true&rating_from=4&sort=price_asc&page=1&per_page=20
```

Если `q` передан, поиск идет через инфраструктурный Meilisearch-адаптер. Если `q` пустой, используется database fallback.

## Архитектура

Поиск и индексирование вынесены из контроллера и модели в отдельные слои:

- domain entity: [src/Domain/Product/Product.php](src/Domain/Product/Product.php)
- domain criteria: [src/Domain/Product/ProductSearchCriteria.php](src/Domain/Product/ProductSearchCriteria.php)
- domain sort enum: [src/Domain/Product/ProductSort.php](src/Domain/Product/ProductSort.php)
- domain page result: [src/Domain/Product/ProductPage.php](src/Domain/Product/ProductPage.php)
- query: [src/Application/Queries/SearchProductsQuery.php](src/Application/Queries/SearchProductsQuery.php)
- handler: [src/Application/Handlers/SearchProductsHandler.php](src/Application/Handlers/SearchProductsHandler.php)
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

Границы слоев:

- `app/` содержит Laravel boundary: HTTP/controllers, Eloquent models, service providers
- `src/Domain` содержит типизированные доменные объекты поиска без зависимости на Laravel
- `src/Application` содержит query/handler и порты
- `src/Infrastructure` содержит Eloquent, Redis и Meilisearch адаптеры

DI-привязки разнесены по провайдерам:
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) — bootstrapping observer
- [app/Providers/RepositoryServiceProvider.php](app/Providers/RepositoryServiceProvider.php) — репозитории
- [app/Providers/PortServiceProvider.php](app/Providers/PortServiceProvider.php) — порты и инфраструктурные адаптеры

Контроллер не содержит поисковую бизнес-логику: он валидирует HTTP query params, маппит их в `ProductSearchCriteria`, вызывает `SearchProductsHandler` и сериализует `ProductPage` обратно в JSON.

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

Эти команды используют инфраструктурный индексатор, а не код контроллера или модели.

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
