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
- отдельный `Domain` слой для поиска товаров, разбитый на `Entity` / `ValueObject` / `Search`
- `declare(strict_types=1);` во всем проектном PHP-коде
- Redis-очереди
- `QueueBus` как порт в `Application` и реализация в `Infrastructure`
- поиск по `q` через Meilisearch
- индексирование, вынесенное в `Infrastructure`
- фабрики и сиды для наполнения каталога
- `k6`-сценарий для нагрузочного тестирования поиска
- OpenAPI/Swagger-нотации прямо в API-слое

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

OpenAPI-нотации размещены рядом с API-кодом:

- [OpenApiSpec.php](app/OpenApi/OpenApiSpec.php)
- [ProductIndexController.php](app/Http/Controllers/ProductIndexController.php)
- [ProductResponseData.php](app/Http/Responses/ProductResponseData.php)
- [ProductPageResponseData.php](app/Http/Responses/ProductPageResponseData.php)

Для генерации спецификации используется `swagger-php`.

## Архитектура

Поиск и индексирование вынесены из контроллера и модели в отдельные слои:

- domain entity: [src/Domain/Product/Entity/Product.php](src/Domain/Product/Entity/Product.php)
- value objects: [src/Domain/Product/ValueObject/Price.php](src/Domain/Product/ValueObject/Price.php), [src/Domain/Product/ValueObject/Rating.php](src/Domain/Product/ValueObject/Rating.php), [src/Domain/Product/ValueObject/ProductId.php](src/Domain/Product/ValueObject/ProductId.php), [src/Domain/Product/ValueObject/CategoryId.php](src/Domain/Product/ValueObject/CategoryId.php), [src/Domain/Product/ValueObject/Page.php](src/Domain/Product/ValueObject/Page.php), [src/Domain/Product/ValueObject/PerPage.php](src/Domain/Product/ValueObject/PerPage.php)
- search criteria: [src/Domain/Product/Search/ProductSearchCriteria.php](src/Domain/Product/Search/ProductSearchCriteria.php)
- search sort enum: [src/Domain/Product/Search/ProductSort.php](src/Domain/Product/Search/ProductSort.php)
- search page result: [src/Domain/Product/Search/ProductPage.php](src/Domain/Product/Search/ProductPage.php)
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
- file-based infrastructure scripts: [src/Infrastructure/Scripts](src/Infrastructure/Scripts)
- scripts resolver: [src/Infrastructure/Support/ScriptResolver.php](src/Infrastructure/Support/ScriptResolver.php)

Очереди:

- порт: [src/Application/Contracts/Queue/QueueBus.php](src/Application/Contracts/Queue/QueueBus.php)
- реализация: [src/Infrastructure/Queue/LaravelQueueBus.php](src/Infrastructure/Queue/LaravelQueueBus.php)
- queued indexing jobs: [app/Jobs/IndexProductInSearchJob.php](app/Jobs/IndexProductInSearchJob.php), [app/Jobs/RemoveProductFromSearchJob.php](app/Jobs/RemoveProductFromSearchJob.php), [app/Jobs/SyncProductSearchSettingsJob.php](app/Jobs/SyncProductSearchSettingsJob.php), [app/Jobs/ImportProductsToSearchJob.php](app/Jobs/ImportProductsToSearchJob.php)

Границы слоев:

- `app/` содержит Laravel boundary: HTTP/controllers, queued jobs, Eloquent models, service providers
- `src/Domain` содержит типизированные доменные объекты поиска без зависимости на Laravel
- `src/Application` содержит query/handler и порты
- `src/Infrastructure` содержит Eloquent, Redis и Meilisearch адаптеры

Внутри `src/Domain/Product` структура дополнительно разделена по ролям:

- `Entity` — доменные сущности продукта
- `ValueObject` — typed ids, деньги, рейтинг, pagination primitives
- `Search` — критерии поиска, сортировка и paginated result

Роль `Domain` в этом проекте:

- `Domain` изолирует бизнес-типы поиска от Laravel, Eloquent и Meilisearch
- `Domain` задаёт стабильный контракт между слоями через `Entity`, `ValueObject` и `Search`
- домен здесь сознательно тонкий: он фиксирует предметные типы и инварианты поискового сценария, но не превращается в тяжёлый DDD-модуль

В домене выделены предметные value objects и typed concepts:

- `price` представлен через `Price`
- `rating` представлен через `Rating`
- идентификаторы представлены через `ProductId` и `CategoryId`
- пагинация представлена через `Page` и `PerPage`

Технические преобразования Laravel-модели, такие как `boolean` и `datetime`, оставлены в Eloquent cast layer, потому что они относятся к адаптации ORM, а не к самостоятельным предметным value object.

DI-привязки разнесены по провайдерам:
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) — bootstrapping observer
- [app/Providers/RepositoryServiceProvider.php](app/Providers/RepositoryServiceProvider.php) — репозитории
- [app/Providers/PortServiceProvider.php](app/Providers/PortServiceProvider.php) — порты и инфраструктурные адаптеры

Контроллер остаётся тонким: получает уже провалидированные query params из form request, передаёт их в `SearchProductsQueryFactory`, вызывает `SearchProductsHandler` и отдаёт ответ через отдельные HTTP response DTO.

Для database fallback логика фильтрации и сортировки вынесена в `ProductSearchQueryAdapter`, а преобразование Eloquent-модели в доменный объект — в `ProductModelMapper`.

## Локальный деплой

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

Данные PostgreSQL, Redis и Meilisearch в `compose.yaml` вынесены в bind mounts внутри проекта:

- `.docker/pgsql`
- `.docker/redis`
- `.docker/meilisearch`

За счёт этого удаление docker named volumes не сбрасывает локальное состояние сервисов.

3. Сгенерировать ключ приложения:

```bash
./vendor/bin/sail artisan key:generate
```

4. Применить миграции и заполнить каталог:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

Если локальные данные сервисов были удалены вместе с каталогами `.docker/*`, нужно повторно выполнить миграции и сидирование после старта контейнеров.

5. Для режима `SEARCH_DRIVER=meilisearch` синхронизировать индекс:

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

Если нужен только backend без Docker, достаточно поднять PostgreSQL, Redis и Meilisearch локально, настроить `.env`, затем выполнить:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
php artisan queue:work redis --queue=default
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

Lua-скрипты инфраструктуры хранятся в отдельных файлах внутри `src/Infrastructure/Scripts`, а загрузка идёт через `ScriptResolver`. Сейчас это используется для Redis sliding-window rate limit.

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
./vendor/bin/sail artisan db:seed
```

Быстрое наполнение каталога под нагрузочные проверки:

```bash
./vendor/bin/sail artisan catalog:seed 5000 12
./vendor/bin/sail artisan catalog:seed 50000 20
```

После большого импорта товаров для режима `SEARCH_DRIVER=meilisearch` имеет смысл отдельно поставить bulk-индексацию:

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
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

Почему выбран `k6`:

- он хорошо подходит для API-нагрузки без лишней обвязки
- сценарий хранится в обычном js-файле и легко читается на ревью
- из коробки даёт понятные метрики по latency, throughput и error rate
- его удобно запускать локально и адаптировать под CI

Сценарий покрывает типовые чтения:

- листинг без фильтров
- сортировку
- фильтрацию по цене, наличию, рейтингу и категории
- запросы с `q`

## Benchmarks

Отдельно от load test добавлен benchmark-сценарий:

- [benchmarks/products-benchmark.k6.js](benchmarks/products-benchmark.k6.js)
- [benchmarks/README.md](benchmarks/README.md)

Он нужен не для общего stress/load поведения, а для контролируемого сравнения фиксированных сценариев:

- обычный list endpoint без `q`
- list endpoint с фильтрами
- search endpoint с `q`

Пример запуска:

```bash
BASE_URL=http://localhost/api/products \
SEARCH_DRIVER=database \
SEARCH_CACHE=off \
k6 run benchmarks/products-benchmark.k6.js
```

Такой benchmark удобен для сравнения:

- `database` против `meilisearch`
- Redis cache выключен против включён
- разных объёмов каталога (`10k`, `100k`, `500k`)

Подробная методика и шаблон фиксации результатов описаны в:

- [benchmarks/README.md](benchmarks/README.md)

## Тесты и проверка

Запуск тестов:

```bash
./vendor/bin/sail artisan test
```

Статический анализ:

```bash
vendor/bin/phpstan analyse app src tests routes database bootstrap --no-progress --memory-limit=512M --level=8
```

Генерация OpenAPI spec:

```bash
composer docs:openapi
```

После генерации файл будет лежать в:

```text
storage/api-docs/openapi.yaml
```

Тестовое окружение использует `SEARCH_DRIVER=database`, поэтому тесты не зависят от живого Meilisearch.

## Docker services

В `compose.yaml` описаны сервисы:
- `laravel.test`
- `pgsql`
- `redis`
- `meilisearch`
- `queue`

Stateful data хранится не в docker named volumes, а в локальных bind mounts:

- `.docker/pgsql`
- `.docker/redis`
- `.docker/meilisearch`

Проверить маршрут API:

```bash
./vendor/bin/sail artisan route:list --path=api
```

## Полезные команды

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
./vendor/bin/sail artisan test
vendor/bin/phpstan analyse app src tests routes database bootstrap --no-progress --memory-limit=512M --level=8
```
