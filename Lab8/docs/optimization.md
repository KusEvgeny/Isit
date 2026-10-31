# Анализ и оптимизация SQL-запросов

Все результаты получены командой `\i /sql/06_optimization.sql` на PostgreSQL 16 после скриптов `01`–`05`
(500 007 единиц оборудования, 1 000 005 заявок, 1004 категории, 204 сотрудника).
`EXPLAIN ANALYZE` выполняет запрос по-настоящему и показывает план и фактическое время.
Время зависит от компьютера: важно, во сколько раз оно изменилось.

Как читать план (снизу вверх, от вложенных шагов к внешним):

| В плане | Что это значит |
|---------|----------------|
| `Seq Scan` / `Parallel Seq Scan` | полный перебор таблицы (параллельно в нескольких процессах) |
| `Rows Removed by Filter` | сколько строк прочитано зря и отброшено условием `WHERE` |
| `Index Scan` | поиск по индексу, затем чтение найденных строк |
| `Bitmap Index Scan` + `Bitmap Heap Scan` | по индексу собирается «карта» нужных страниц таблицы, затем они читаются по порядку |
| `Sort` | отдельная сортировка результата |
| `Limit` | остановка после нужного числа строк |
| `cost=...` | оценка планировщика в условных единицах (не миллисекунды) |
| `actual time=...` | фактическое время в миллисекундах |
| `Execution Time` | общее время выполнения запроса |

## Исходное состояние

До оптимизации у таблиц есть только индексы первичных ключей. PostgreSQL **не создаёт индексы для внешних ключей**
автоматически, поэтому `equipment.category_id`, `equipment.employee_id` и `requests.equipment_id` ищутся перебором.

## Запрос 1. Оборудование категории

Используется в `GET /api/categories/{id}/equipment` и на странице категории.

```sql
SELECT id, title, status FROM equipment WHERE category_id = 500 ORDER BY id;
```

**До.** Перебор всех 500 тысяч строк, чтобы найти нужные:

```text
Parallel Seq Scan on equipment
  Filter: (category_id = 500)
  Rows Removed by Filter: 166505
Execution Time: 14.943 ms
```

**Решение:** индекс на внешний ключ.

```sql
CREATE INDEX idx_equipment_category_id ON equipment (category_id);
```

**После.** База находит строки по индексу:

```text
Bitmap Heap Scan on equipment
  Recheck Cond: (category_id = 500)
  ->  Bitmap Index Scan on idx_equipment_category_id
Execution Time: 0.562 ms
```

## Запрос 2. Оборудование в ремонте у сотрудника

Список оборудования сотрудника с фильтром по статусу.

```sql
SELECT id, title, inventory_number FROM equipment
WHERE employee_id = 42 AND status = 'in_repair'
ORDER BY title;
```

**До.** Перебор всей таблицы:

```text
Parallel Seq Scan on equipment
  Filter: ((employee_id = 42) AND ((status)::text = 'in_repair'::text))
  Rows Removed by Filter: 166478
Execution Time: 16.125 ms
```

**Решение:** составной индекс по двум столбцам из `WHERE`.

```sql
CREATE INDEX idx_equipment_employee_status ON equipment (employee_id, status);
```

**После.** База использует индекс:

```text
Bitmap Heap Scan on equipment
  Recheck Cond: ((employee_id = 42) AND ((status)::text = 'in_repair'::text))
  ->  Bitmap Index Scan on idx_equipment_employee_status
Execution Time: 0.824 ms
```

## Запрос 3. Последние поступления

Список в API с фильтром, сортировкой и ограничением: `GET /api/equipment?status=in_use&limit=20`.

```sql
SELECT id, title, created_at FROM equipment 
WHERE status = 'in_use' 
ORDER BY created_at DESC LIMIT 20;
```

**До.** Нужно найти все подходящие записи, отсортировать их и только потом взять первые 20:

```text
Limit
  ->  Gather Merge
        ->  Sort
              Sort Key: created_at DESC
              ->  Parallel Seq Scan on equipment
                    Filter: ((status)::text = 'in_use'::text)
Execution Time: 22.716 ms
```

**Решение:** индекс, в котором строки уже упорядочены по `(status, created_at DESC)`.

```sql
CREATE INDEX idx_equipment_status_created_at ON equipment (status, created_at DESC);
```

**После.** Шага `Sort` больше нет: база читает первые 20 записей индекса и останавливается (`Limit`):

```text
Limit
  ->  Index Scan using idx_equipment_status_created_at on equipment
        Index Cond: ((status)::text = 'in_use'::text)
Execution Time: 0.035 ms
```

## Запрос 4. Заявки на оборудование

Страница оборудования: заявки с именами авторов.

```sql
SELECT r.id, r.description, e.name AS author
FROM requests r
JOIN employees e ON e.id = r.author_id
WHERE r.equipment_id = 2
ORDER BY r.created_at;
```

**До.** Перебор миллиона заявок ради нескольких:

```text
Parallel Seq Scan on requests r
  Filter: (equipment_id = 2)
  Rows Removed by Filter: 333333
Execution Time: 14.943 ms
```

**Решение:**

```sql
CREATE INDEX idx_requests_equipment_id ON requests (equipment_id);
```

**После:**

```text
Index Scan using idx_requests_equipment_id on requests r
  Index Cond: (equipment_id = 2)
Execution Time: 0.096 ms
```

## Запрос 5. Когда индекс не помогает

**5а. Подсчет большинства строк.** Статус `in_use` у 60% оборудования. Индекс `(status, created_at DESC)` есть,
но переходить по нему к 300 тысячам строк дороже, чем перебрать таблицу подряд, и планировщик его не использует:

```text
Parallel Index Only Scan using idx_equipment_employee_status on equipment
  Index Cond: ((status)::text = 'in_use'::text)
Execution Time: 8.685 ms
```

**5б. Поиск по части названия.** Обычный индекс (B-tree) упорядочивает значения с начала строки и не умеет
искать подстроку `%...%`. Для такого поиска нужен специальный индекс (расширение `pg_trgm`):

```text
Parallel Seq Scan on equipment
  Filter: ((title)::text ~~* '%задача №4999%'::text)
  Rows Removed by Filter: 166669
Execution Time: 82.508 ms
```

## Цена индексов

Индексы занимают место и замедляют запись: при каждой вставке, изменении и удалении базе нужно обновить все индексы таблицы.

```text
=== Размер индексов ===
        index_name         | size  
---------------------------+-------
 equipment_inventory_number_key | 26 MB
 requests_pkey             | 21 MB
 idx_equipment_status_created_at | 17 MB
 idx_requests_equipment_id | 16 MB
 equipment_pkey            | 11 MB
 idx_equipment_employee_status | 3640 kB
 idx_equipment_category_id | 3568 kB
```

## Итог

| № | Запрос | Индекс | До, мс | После, мс | Ускорение |
|---|--------|--------|-------:|----------:|----------:|
| 1 | оборудование категории | `(category_id)` | 14,943 | 0,562 | ×27 |
| 2 | ремонт у сотрудника | `(employee_id, status)` | 16,125 | 0,824 | ×20 |
| 3 | последние поступления | `(status, created_at DESC)` | 22,716 | 0,035 | ×649 |
| 4 | заявки на оборудование | `requests (equipment_id)` | 14,943 | 0,096 | ×155 |

Выводы:

- индексы нужны на внешние ключи и на столбцы, по которым часто фильтруют (`WHERE`) и сортируют (`ORDER BY`);
- составной индекс полезен, когда в запросе условие сразу по нескольким столбцам или условие плюс сортировка;
- индекс не помогает, если запрос читает большую часть таблицы или ищет подстроку `%...%`;
- каждый индекс занимает место (здесь 17 МБ на 500 тысяч строк) и замедляет запись, поэтому индексы создаются
  только для реально частых запросов и проверяются командой `EXPLAIN ANALYZE`.