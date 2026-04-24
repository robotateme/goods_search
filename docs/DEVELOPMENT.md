# Development

## Нагрузочное тестирование

В репозитории есть `k6`-сценарий:

- [loadtests/products-search.k6.js](../loadtests/products-search.k6.js)

Пример запуска:

```bash
k6 run loadtests/products-search.k6.js
```

С указанием базового URL:

```bash
BASE_URL=http://localhost/api/products k6 run loadtests/products-search.k6.js
```

Почему выбран `k6`:

- он хорошо подходит для API
- сценарий хранится в обычном js-файле и его легко читать
- он сразу показывает понятные метрики по времени ответа, скорости и ошибкам
- его удобно запускать локально и в CI

Актуальные результаты нагрузочных тестов, benchmark и `EXPLAIN ANALYZE` вынесены в отдельный документ:

- [PERFORMANCE_RESULTS.md](PERFORMANCE_RESULTS.md)

Сценарий проверяет основные варианты запросов:

- листинг без фильтров
- сортировку
- фильтрацию по цене, наличию, рейтингу и категории
- запросы с `q`

## Benchmarks

Отдельно есть benchmark-сценарий:

- [products-benchmark.k6.js](../benchmarks/products-benchmark.k6.js)
- [benchmarks/README.md](../benchmarks/README.md)

Он нужен, чтобы сравнивать одни и те же сценарии:

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
- кэш Redis выключен против включён
- разных объёмов каталога (`10k`, `100k`, `500k`)

Подробная методика и шаблон фиксации результатов описаны в:

- [benchmarks/README.md](../benchmarks/README.md)

## Предложения по улучшениям

IMPORTANT:
Что ещё можно улучшить:

- зафиксировать эффект добавленных SQL-индексов в benchmark-таблицах на нескольких размерах каталога
- добавить отдельную интеграционную проверку кэша поиска через Redis

Что сознательно оставлено вне ТЗ или не подходит под текущий API:

- настоящий keyset pagination вместо текущей пагинации через `page/per_page`
- нормальная стратегия сброса кэша и версионирования выдачи
- асинхронный поиск товаров через очередь
- полный уход на индекс и отказ от поиска через базу
- замена offset pagination на cursor-only или другой несовместимый контракт

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
./vendor/bin/sail composer docs:openapi
```

После генерации файлы будут лежать в:

```text
storage/api-docs/openapi.yaml
storage/api-docs/openapi.json
```

Laravel отдает спецификацию по адресам:

- `GET /openapi.yaml`
- `GET /openapi.json`

В тестах используется `SEARCH_DRIVER=database`, поэтому живой Meilisearch не нужен.

## Полезные команды

```bash
./vendor/bin/sail artisan search:products:sync
./vendor/bin/sail artisan search:products:import
./vendor/bin/sail artisan test
vendor/bin/phpstan analyse app src tests routes database bootstrap --no-progress --memory-limit=512M --level=8
make help
```
