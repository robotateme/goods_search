# Architecture

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

Параметры запроса:

- `q` — текстовый поиск через Meilisearch
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

Если передан `q`, поиск идёт через Meilisearch. Если `q` нет, используется запрос в базу.

OpenAPI описан рядом с HTTP-кодом:

- [OpenApiSpec.php](../app/OpenApi/OpenApiSpec.php)
- [ProductIndexController.php](../app/Http/Controllers/ProductIndexController.php)
- [ProductResponseData.php](../app/Http/Responses/ProductResponseData.php)
- [ProductPageResponseData.php](../app/Http/Responses/ProductPageResponseData.php)

Спецификация собирается через `swagger-php`.

## Как устроено

Главные части:

- модель товара: [Product.php](../src/Domain/Product/Entity/Product.php)
- value objects: [Price.php](../src/Domain/Product/ValueObject/Price.php), [Rating.php](../src/Domain/Product/ValueObject/Rating.php), [ProductId.php](../src/Domain/Product/ValueObject/ProductId.php), [CategoryId.php](../src/Domain/Product/ValueObject/CategoryId.php), [Page.php](../src/Domain/Product/ValueObject/Page.php), [PerPage.php](../src/Domain/Product/ValueObject/PerPage.php)
- параметры поиска: [ProductSearchCriteria.php](../src/Domain/Product/Search/ProductSearchCriteria.php)
- сортировка: [ProductSort.php](../src/Domain/Product/Search/ProductSort.php)
- страница результата: [ProductPage.php](../src/Domain/Product/Search/ProductPage.php)
- запрос и обработчик: [SearchProductsQuery.php](../src/Application/Queries/SearchProductsQuery.php), [SearchProductsQueryFactory.php](../src/Application/Queries/SearchProductsQueryFactory.php), [SearchProductsHandler.php](../src/Application/Handlers/SearchProductsHandler.php)
- команды индексации и их обработчики: [IndexProductInSearchCommand.php](../src/Application/Commands/IndexProductInSearchCommand.php), [RemoveProductInSearchCommand.php](../src/Application/Commands/RemoveProductInSearchCommand.php), [ImportProductsToSearchCommand.php](../src/Application/Commands/ImportProductsToSearchCommand.php), [SyncProductSearchSettingsCommand.php](../src/Application/Commands/SyncProductSearchSettingsCommand.php), [IndexProductInSearchHandler.php](../src/Application/Handlers/IndexProductInSearchHandler.php), [RemoveProductInSearchHandler.php](../src/Application/Handlers/RemoveProductInSearchHandler.php), [ImportProductsToSearchHandler.php](../src/Application/Handlers/ImportProductsToSearchHandler.php), [SyncProductSearchSettingsHandler.php](../src/Application/Handlers/SyncProductSearchSettingsHandler.php)
- контракты: [QueueBus.php](../src/Application/Contracts/Queue/QueueBus.php), [ProductSearch.php](../src/Application/Contracts/Search/ProductSearch.php), [ProductSearchIndexer.php](../src/Application/Contracts/Search/ProductSearchIndexer.php), [ProductRepositoryInterface.php](../src/Application/Contracts/Repositories/ProductRepositoryInterface.php)
- HTTP-ответы: [ProductResponseData.php](../app/Http/Responses/ProductResponseData.php), [ProductPageResponseData.php](../app/Http/Responses/ProductPageResponseData.php)
- поиск: [DatabaseProductSearch.php](../src/Infrastructure/Database/Search/DatabaseProductSearch.php), [MeilisearchProductSearch.php](../src/Infrastructure/Search/MeilisearchProductSearch.php)
- индексирование: [DatabaseProductSearchIndexer.php](../src/Infrastructure/Database/Search/DatabaseProductSearchIndexer.php), [MeilisearchProductSearchIndexer.php](../src/Infrastructure/Search/MeilisearchProductSearchIndexer.php)
- работа с БД: [ProductSearchQueryAdapter.php](../src/Infrastructure/Database/ProductSearchQueryAdapter.php), [ProductModelMapper.php](../src/Infrastructure/Database/ProductModelMapper.php), [ProductRepository.php](../src/Infrastructure/Database/ProductRepository.php), [Product.php](../src/Infrastructure/Database/Eloquent/Product.php), [Category.php](../src/Infrastructure/Database/Eloquent/Category.php), [User.php](../src/Infrastructure/Database/Eloquent/User.php)
- кэш поиска: [CachedProductSearch.php](../src/Infrastructure/Search/CachedProductSearch.php), [ProductPageCacheSerializer.php](../src/Infrastructure/Search/ProductPageCacheSerializer.php)
- остальное: [ProductSearchDocumentMapper.php](../src/Infrastructure/Search/ProductSearchDocumentMapper.php), [ProductObserver.php](../src/Infrastructure/Search/ProductObserver.php), [src/Infrastructure/Redis/Scripts](../src/Infrastructure/Redis/Scripts), [ScriptResolver.php](../src/Infrastructure/Redis/ScriptResolver.php)

Очереди и индексация:

- application-команды: [IndexProductInSearchCommand.php](../src/Application/Commands/IndexProductInSearchCommand.php), [RemoveProductInSearchCommand.php](../src/Application/Commands/RemoveProductInSearchCommand.php), [ImportProductsToSearchCommand.php](../src/Application/Commands/ImportProductsToSearchCommand.php), [SyncProductSearchSettingsCommand.php](../src/Application/Commands/SyncProductSearchSettingsCommand.php)
- application handlers: [IndexProductInSearchHandler.php](../src/Application/Handlers/IndexProductInSearchHandler.php), [RemoveProductInSearchHandler.php](../src/Application/Handlers/RemoveProductInSearchHandler.php), [ImportProductsToSearchHandler.php](../src/Application/Handlers/ImportProductsToSearchHandler.php), [SyncProductSearchSettingsHandler.php](../src/Application/Handlers/SyncProductSearchSettingsHandler.php)
- интерфейс очереди: [QueueBus.php](../src/Application/Contracts/Queue/QueueBus.php)
- реализация: [LaravelQueueBus.php](../src/Infrastructure/Ports/Queue/LaravelQueueBus.php), [DeduplicatingQueueBus.php](../src/Infrastructure/Ports/Queue/DeduplicatingQueueBus.php), [QueueCommandJobMapper.php](../src/Infrastructure/Ports/Queue/QueueCommandJobMapper.php), [CommandDeduplicationKeyResolver.php](../src/Infrastructure/Ports/Queue/CommandDeduplicationKeyResolver.php), [RedisQueueDeduplicator.php](../src/Infrastructure/Redis/Queue/RedisQueueDeduplicator.php)
- маппинг application-команд в Laravel jobs: [LaravelQueueCommandMapper.php](../app/Jobs/LaravelQueueCommandMapper.php)
- jobs как thin wrappers: [IndexProductInSearchJob.php](../app/Jobs/IndexProductInSearchJob.php), [RemoveProductFromSearchJob.php](../app/Jobs/RemoveProductFromSearchJob.php), [SyncProductSearchSettingsJob.php](../app/Jobs/SyncProductSearchSettingsJob.php), [ImportProductsToSearchJob.php](../app/Jobs/ImportProductsToSearchJob.php)

По папкам:

- `app/` — контроллеры, jobs, service providers и Eloquent-модели инфраструктуры
- `src/Domain` — доменные классы поиска
- `src/Application` — query, commands, handlers и application-level контракты
- `src/Infrastructure/Database` — всё, что относится к БД и поиску по БД
- `src/Infrastructure/Redis` — Redis rate limit, queue deduplication и lua-скрипты
- `src/Infrastructure/Ports` — адаптеры инфраструктуры для application ports и transport mapping
- `src/Infrastructure/Search` — кэш поиска, observer и Meilisearch-специфичные части

## Почему так

Для важных полей используются отдельные типы:

- `price` представлен через `Price`
- `rating` представлен через `Rating`
- идентификаторы представлены через `ProductId` и `CategoryId`
- пагинация представлена через `Page` и `PerPage`

Преобразование Laravel-модели в объект поиска вынесено отдельно, чтобы поиск не зависел от Eloquent напрямую.

Laravel queue не используется как место для прикладной логики: observer и console command отправляют application-команды, а преобразование этих команд в конкретные Laravel jobs остается на стороне infrastructure adapter.

## Провайдеры

- [AppServiceProvider.php](../app/Providers/AppServiceProvider.php) — observer
- [RepositoryServiceProvider.php](../app/Providers/RepositoryServiceProvider.php) — репозитории
- [PortServiceProvider.php](../app/Providers/PortServiceProvider.php) — application-контракты и инфраструктурные адаптеры

Контроллер только принимает параметры, вызывает handler и возвращает ответ.
