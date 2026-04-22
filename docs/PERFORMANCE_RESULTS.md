# Performance Results

Текущие локальные замеры для `goods_search`.

Условия:

- `Docker Compose`
- `SEARCH_DRIVER=meilisearch`
- каталог `50_000` товаров
- PostgreSQL, Redis и Meilisearch запущены локально

## Load Test

Сценарий: `loadtests/products-search.k6.js`

- профиль: `ramping-vus`
- разгон: `1 -> 10 -> 25 -> 0`
- длительность: `2m`
- внутри итерации: `sleep(1)`

### Обычный режим

Параметры:

- `search cache`: `on`
- `rate limit`: `on`

Результаты:

| avg | p95 | req/s | errors |
|---:|---:|---:|---:|
| `26.03ms` | `67.94ms` | `12.70` | `95.95%` |

Что это значит:

- кэш после исправления сериализации больше не даёт `500`
- большая часть ошибок теперь связана с активным `rate limit`
- это замер обычного защищённого режима, а не чистой скорости поиска

### Режим без ограничений

Параметры:

- `SEARCH_CACHE_ENABLED=false`
- `PRODUCTS_RATE_LIMIT_ENABLED=false`

Результаты:

| avg | p95 | req/s | errors |
|---:|---:|---:|---:|
| `65.20ms` | `194.08ms` | `12.21` | `0%` |

Что это значит:

- это самый полезный замер для чистой скорости поиска
- на текущем каталоге `p95` остаётся ниже целевого `500ms`

## Benchmark

Сценарий: `benchmarks/products-benchmark.k6.js`

Параметры:

- `SEARCH_DRIVER=meilisearch`
- `SEARCH_CACHE_ENABLED=false`
- `PRODUCTS_RATE_LIMIT_ENABLED=false`
- каталог `50_000` товаров

Итог:

- `1947` запросов за `30s`
- `64.55 req/s`
- `0%` ошибок
- `avg=230.75ms`
- `p95=537.72ms`

Подробно:

| Сценарий | Описание | avg | p95 | Запросов | req/s |
|---|---|---:|---:|---:|---:|
| `database_listing` | листинг без `q` | `231.14ms` | `537.64ms` | `647` | `21.57` |
| `filtered_listing` | фильтры по категории, цене, наличию и рейтингу | `233.98ms` | `543.85ms` | `640` | `21.33` |
| `fulltext_listing` | полнотекстовый запрос `q=Mouse` | `227.22ms` | `532.94ms` | `660` | `22.00` |

Что видно:

- `error rate < 1%` выполняется для всех трёх сценариев
- пороги по времени ответа из `benchmarks/products-benchmark.k6.js` на локальном стенде не выполняются
- после добавления индексов ошибок нет, но по latency ещё нужны либо оптимизации, либо другие пороги для локального стенда

## SQL Explain Analyze

Замеры сняты через `EXPLAIN (ANALYZE, BUFFERS)` в PostgreSQL.

### Базовый листинг

Запрос:

```sql
SELECT * FROM products
ORDER BY created_at DESC, id DESC
LIMIT 20 OFFSET 0;
```

План:

- `Index Scan Backward using products_created_at_id_idx`
- `Execution Time: 0.090 ms`

Что видно:

- индекс `products_created_at_id_idx` хорошо подходит для базового листинга

### Полный count каталога

Запрос:

```sql
SELECT count(*) FROM products;
```

План:

- `Index Only Scan using products_in_stock_index`
- `Execution Time: 5.222 ms`

Что видно:

- count не уходит в полный проход по таблице

### Фильтрованный count

Запрос:

```sql
SELECT count(*)
FROM products
WHERE category_id = 3
  AND in_stock = true
  AND price >= 50
  AND price <= 250
  AND rating >= 4;
```

План:

- `Bitmap Index Scan on products_category_stock_price_id_idx`
- `Bitmap Heap Scan`
- `Execution Time: 1.255 ms`

Что видно:

- составной индекс `category_id + in_stock + price` реально используется

### Фильтрованный листинг с сортировкой по дате

Запрос:

```sql
SELECT * FROM products
WHERE category_id = 3
  AND in_stock = true
  AND price >= 50
  AND price <= 250
  AND rating >= 4
ORDER BY created_at DESC, id DESC
LIMIT 20 OFFSET 0;
```

План:

- `Index Scan Backward using products_created_at_id_idx`
- post-filter по условиям
- `Rows Removed by Filter: 5875`
- `Execution Time: 0.880 ms`

Что видно:

- сортировка идёт по индексу
- фильтрация ещё не идеально подходит под такую сортировку

### Глубокая страница через offset

Запрос:

```sql
SELECT * FROM products
ORDER BY created_at DESC, id DESC
LIMIT 20 OFFSET 10000;
```

План:

- `Index Scan Backward using products_created_at_id_idx`
- прочитано `10020` строк ради `20`
- `Execution Time: 1.499 ms`

Что видно:

- даже с хорошим индексом `OFFSET` становится дороже на глубоких страницах
- это главный аргумент в пользу будущей keyset pagination, если она понадобится

### SQL fallback по `LIKE '%Mouse%'`

Count-запрос:

```sql
SELECT count(*) FROM products WHERE name LIKE '%Mouse%';
```

План:

- `Bitmap Index Scan on products_name_trgm_idx`
- `Execution Time: 6.537 ms`

Листинг:

```sql
SELECT * FROM products
WHERE name LIKE '%Mouse%'
ORDER BY price DESC, id ASC
LIMIT 20 OFFSET 0;
```

План:

- `Index Scan Backward using products_price_index`
- `Incremental Sort`
- `Execution Time: 0.753 ms`

Что видно:

- trigram index помогает поиску по имени

## Пагинация

Текущий статус:

- в коде и API поддерживается только пагинация через `page/per_page`
- идея keyset pagination оставлена как возможное улучшение, но не как часть текущего контракта
