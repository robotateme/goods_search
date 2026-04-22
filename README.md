# Goods Search

Тестовое Laravel-приложение с HTTP API для поиска товаров.

## Задание

Нужно было реализовать поиск по товарам с фильтрами и сортировкой через HTTP endpoint `GET /api/products`.

Требования к товару:
- `id`
- `name`
- `price`
- `category_id`
- `in_stock`
- `rating`
- `created_at`
- `updated_at`

Фильтры через query-параметры:
- `q`
- `price_from`
- `price_to`
- `category_id`
- `in_stock`
- `rating_from`

Сортировка через параметр `sort`:
- `price_asc`
- `price_desc`
- `rating_desc`
- `newest`

Дополнительные требования:
- обязательная пагинация
- решение в git-репозитории
- качество решений на уровне production-подхода

Что выбрано в этой реализации:
- поиск остаётся синхронным и соответствует `GET /api/products`
- очередь используется только для индексации и обслуживания search-инфраструктуры
- поисковая логика вынесена из Laravel boundary в `Domain` / `Application` / `Infrastructure`

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
- фабрики и сиды для наполнения каталога
- `k6`-сценарий для нагрузочного тестирования поиска

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
- query factory: [src/Application/Queries/SearchProductsQueryFactory.php](src/Application/Queries/SearchProductsQueryFactory.php)
- handler: [src/Application/Handlers/SearchProductsHandler.php](src/Application/Handlers/SearchProductsHandler.php)
- response DTOs: [app/Http/Responses/ProductResponseData.php](app/Http/Responses/ProductResponseData.php), [app/Http/Responses/ProductPageResponseData.php](app/Http/Responses/ProductPageResponseData.php)
- порт поиска: [src/Application/Contracts/Search/ProductSearch.php](src/Application/Contracts/Search/ProductSearch.php)
- порт индексирования: [src/Application/Contracts/Search/ProductSearchIndexer.php](src/Application/Contracts/Search/ProductSearchIndexer.php)
- контракт репозитория: [src/Application/Contracts/Repositories/ProductRepositoryInterface.php](src/Application/Contracts/Repositories/ProductRepositoryInterface.php)
- database query adapter: [src/Infrastructure/Persistence/ProductSearchQueryAdapter.php](src/Infrastructure/Persistence/ProductSearchQueryAdapter.php)
- model mapper: [src/Infrastructure/Persistence/ProductModelMapper.php](src/Infrastructure/Persistence/ProductModelMapper.php)
- Eloquent-репозиторий: [src/Infrastructure/Persistence/ProductRepository.php](src/Infrastructure/Persistence/ProductRepository.php)
- Meilisearch search adapter: [src/Infrastructure/Search/MeilisearchProductSearch.php](src/Infrastructure/Search/MeilisearchProductSearch.php)
- Meilisearch indexer: [src/Infrastructure/Search/MeilisearchProductSearchIndexer.php](src/Infrastructure/Search/MeilisearchProductSearchIndexer.php)
- database fallback search: [src/Infrastructure/Search/DatabaseProductSearch.php](src/Infrastructure/Search/DatabaseProductSearch.php)
- document mapper: [src/Infrastructure/Search/ProductSearchDocumentMapper.php](src/Infrastructure/Search/ProductSearchDocumentMapper.php)
- observer для автоиндексации: [src/Infrastructure/Search/ProductObserver.php](src/Infrastructure/Search/ProductObserver.php)

Очереди:

- порт: [src/Application/Contracts/Queue/QueueBus.php](src/Application/Contracts/Queue/QueueBus.php)
- реализация: [src/Infrastructure/Queue/LaravelQueueBus.php](src/Infrastructure/Queue/LaravelQueueBus.php)
- queued indexing jobs: [app/Jobs/IndexProductInSearchJob.php](app/Jobs/IndexProductInSearchJob.php), [app/Jobs/RemoveProductFromSearchJob.php](app/Jobs/RemoveProductFromSearchJob.php), [app/Jobs/SyncProductSearchSettingsJob.php](app/Jobs/SyncProductSearchSettingsJob.php), [app/Jobs/ImportProductsToSearchJob.php](app/Jobs/ImportProductsToSearchJob.php)

Границы слоев:

- `app/` содержит Laravel boundary: HTTP/controllers, queued jobs, Eloquent models, service providers
- `src/Domain` содержит типизированные доменные объекты поиска без зависимости на Laravel
- `src/Application` содержит query/handler и порты
- `src/Infrastructure` содержит Eloquent, Redis и Meilisearch адаптеры

Роль `Domain` в этом проекте:

- `Domain` изолирует бизнес-типы поиска от Laravel, Eloquent и Meilisearch
- `Domain` задаёт стабильный контракт между слоями через `Product`, `ProductSearchCriteria`, `ProductSort` и `ProductPage`
- `Application` работает с доменными типами и не зависит от деталей HTTP, SQL и внешнего search backend
- `Infrastructure` адаптирует базу данных и Meilisearch к этим типам, не протаскивая framework-specific детали вверх

При этом домен здесь намеренно тонкий:

- это не rich domain model и не полноценный DDD-модуль
- в `Domain` почти нет сложных бизнес-правил, он в первую очередь фиксирует предметные типы и инварианты поискового сценария
- для объёма этого задания такой уровень изоляции достаточен и помогает держать границы слоёв явными

DI-привязки разнесены по провайдерам:
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) — bootstrapping observer
- [app/Providers/RepositoryServiceProvider.php](app/Providers/RepositoryServiceProvider.php) — репозитории
- [app/Providers/PortServiceProvider.php](app/Providers/PortServiceProvider.php) — порты и инфраструктурные адаптеры

Контроллер не содержит поисковую бизнес-логику: он получает уже провалидированные HTTP query params из form request, передаёт их в `SearchProductsQueryFactory`, вызывает `SearchProductsHandler` и отдаёт ответ через отдельные HTTP response DTO.

Почему response DTO лежат в `app/Http`, а не в `Application`:

- они описывают внешний JSON-контракт HTTP API, а не application-level результат use case
- они знают о presentation-деталях, например о snake_case полях и структуре ответа для `response()->json(...)`
- `Application` не должен зависеть от конкретного transport-формата, чтобы тот же use case можно было переиспользовать вне HTTP boundary

Для database fallback логика фильтрации и сортировки вынесена из репозитория в отдельный `ProductSearchQueryAdapter`, а преобразование Eloquent-модели в доменный объект вынесено в `ProductModelMapper`. За счёт этого `ProductRepository` не содержит “жирный” search-метод и остаётся focused на операциях чтения, нужных для индексатора и восстановления документов по id.

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

## Фабрики и сиды

Для генерации данных используются:

- [database/factories/CategoryFactory.php](database/factories/CategoryFactory.php)
- [database/factories/ProductFactory.php](database/factories/ProductFactory.php)
- [database/seeders/CatalogSeeder.php](database/seeders/CatalogSeeder.php)

Базовое сидирование:

```bash
php artisan db:seed
```

Быстрое наполнение каталога под нагрузочные проверки:

```bash
php artisan catalog:seed 5000 12
php artisan catalog:seed 50000 20
```

После большого импорта товаров для режима `SEARCH_DRIVER=meilisearch` имеет смысл отдельно поставить bulk-индексацию:

```bash
php artisan search:products:sync
php artisan search:products:import
```

Seeder отключает model events, чтобы массовое наполнение не порождало тысячи одиночных indexing jobs. Для полнотекстового индекса после сидирования используется отдельный bulk import.

## Нагрузочное тестирование

В репозитории есть `k6`-сценарий:

- [loadtests/products-search.k6.js](loadtests/products-search.k6.js)

Пример запуска:

```bash
k6 run loadtests/products-search.k6.js
```

С указанием базового URL:

```bash
BASE_URL=http://localhost/api/products k6 run loadtests/products-search.k6.js
```

Сценарий покрывает типовые чтения:

- листинг без фильтров
- сортировку
- фильтрацию по цене, наличию, рейтингу и категории
- запросы с `q`

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
