# Benchmarks

В проекте benchmark-и отделены от общего load test.

## Цель

`loadtests/products-search.k6.js` отвечает на вопрос, как API ведёт себя под смешанной нагрузкой.

`benchmarks/products-benchmark.k6.js` нужен для более контролируемого сравнения фиксированных сценариев:

- листинг без `q`
- фильтрованный листинг
- запрос с `q`

## Что сравнивать

Минимальный набор сравнений:

- `SEARCH_DRIVER=database`
- `SEARCH_DRIVER=meilisearch`
- Redis cache выключен
- Redis cache включён

Рекомендуемые размеры каталога:

- `10k`
- `100k`
- `500k`

## Как запускать

1. Подготовить данные:

```bash
php artisan migrate:fresh --seed
php artisan catalog:seed 10000 12
```

2. Для `meilisearch` отдельно синхронизировать индекс:

```bash
php artisan search:products:sync
php artisan search:products:import
```

3. Запустить benchmark:

```bash
BASE_URL=http://localhost/api/products \
SEARCH_DRIVER=database \
SEARCH_CACHE=off \
k6 run benchmarks/products-benchmark.k6.js
```

## Как фиксировать результат

Для каждого прогона полезно сохранять:

- размер датасета
- `SEARCH_DRIVER`
- `SEARCH_CACHE_STORE`
- включён ли Redis search cache
- `avg`, `p50`, `p95`, `req/s`, `error rate`

Пример таблицы:

| dataset | driver | cache | scenario | p95 | req/s | errors |
|---|---|---|---|---|---|---|
| 10k | database | off | database_listing | 120ms | 180 | 0% |
| 10k | database | on | database_listing | 80ms | 240 | 0% |
| 10k | meilisearch | off | fulltext_listing | 65ms | 300 | 0% |

## Интерпретация

- `database_listing` лучше показывает поведение обычного SQL-поиска и пагинации
- `filtered_listing` полезен для проверки индексов и селективности фильтров
- `fulltext_listing` нужен для сравнения database fallback против Meilisearch
