# Goods Search

Небольшое Laravel-приложение с API для поиска товаров.

## Кратко

Здесь реализован поиск товаров через `GET /api/products`.

В текущей реализации:

- поиск синхронный
- очередь используется только для индексации и фоновых задач
- поиск вынесен из контроллера в отдельные слои
- операции индексации вынесены в application-команды и handlers
- Laravel jobs оставлены тонкими адаптерами очереди
- есть поиск через базу, Meilisearch, Redis queue и кэш поиска
- кэш хранит не PHP-объекты, а обычный payload
- добавлены SQL-индексы под основные фильтры и сортировки

## Стек

- PHP 8.5+
- Node.js 20+
- Laravel 13
- PostgreSQL
- Redis
- Meilisearch
- Docker Compose / Laravel Sail

## Документация

- архитектура и API: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- локальный запуск и инфраструктура: [docs/SETUP.md](docs/SETUP.md)
- тесты, benchmark-и и идеи на будущее: [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)
- результаты нагрузочных тестов и `EXPLAIN ANALYZE`: [docs/PERFORMANCE_RESULTS.md](docs/PERFORMANCE_RESULTS.md)

## Быстрый старт

```bash
composer install
npm install
./vendor/bin/sail up -d
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
./vendor/bin/sail composer docs:openapi
curl "http://localhost/api/products?page=1&per_page=20"
```

Для обзора доступных команд можно использовать:

```bash
make help
```

## Requirements

Полные требования для staging без Docker вынесены в [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md).

## Структура решения

- синхронный `GET /api/products`
- фильтрация по `q`, `price_from`, `price_to`, `category_id`, `in_stock`, `rating_from`
- сортировка `price_asc`, `price_desc`, `rating_desc`, `newest`
- обязательная пагинация через `page/per_page`
- Redis queue для индексации и фоновых задач
- поиск по `q` через Meilisearch
- поиск через базу, если индекс не нужен
- application-команды для индексации и отдельный mapper команд в Laravel jobs
- Eloquent-модели лежат в `src/Infrastructure/Database/Eloquent`
- кэш поиска с сохранением `ProductPage` в виде обычных данных
- явные SQL-индексы под основные фильтры и сортировки

## Проверка

```bash
./vendor/bin/sail artisan test
vendor/bin/phpstan analyse app src tests routes database bootstrap --no-progress --memory-limit=512M --level=8
./vendor/bin/sail composer docs:openapi
```

После генерации файлы лежат в `storage/api-docs/openapi.yaml` и `storage/api-docs/openapi.json`.

Laravel отдает спецификацию по адресам `GET /openapi.yaml` и `GET /openapi.json`.
