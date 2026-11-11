-- =====================================================================
-- Лабораторная работа №10. Большой объём данных для измерения скорости
-- Добавляем 49 категорий и 500 000 единиц оборудования.
-- Загрузка: php bin/load-bigdata.php (несколько секунд).
-- =====================================================================

SELECT setseed(0.42);                                   -- одинаковое «случайное» заполнение при каждом запуске

-- Добавляем категории (если их вдруг не хватает)
INSERT INTO categories (title)
SELECT 'Категория №' || i
FROM generate_series(1, 49) AS i;

-- Добавляем 500 000 единиц оборудования
INSERT INTO equipment (category_id, employee_id, inventory_number, title, status, created_at)
SELECT c.ids[1 + floor(random() * cardinality(c.ids))::int],
       CASE WHEN random() < 0.1 THEN NULL ELSE 1 END, -- 10% без ответственного
       'INV-TEST-' || g.i,
       'Тестовое оборудование №' || g.i,
       CASE WHEN g.r < 0.60 THEN 'in_use'              -- 60% в работе
            WHEN g.r < 0.85 THEN 'in_repair'          -- 25% на ремонте
            ELSE 'archived' END,                      -- 15% в архиве
       TIMESTAMP '2025-01-01' + random() * INTERVAL '640 days'
FROM (SELECT i, random() AS r FROM generate_series(1, 500000) AS i) AS g,
     (SELECT array_agg(id) AS ids FROM categories) AS c;

ANALYZE;                                                -- обновить статистику для планировщика запросов