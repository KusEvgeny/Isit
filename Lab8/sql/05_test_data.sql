-- =====================================================================
-- Лабораторная работа №8. Часть 5. Большой объём тестовых данных
-- Запуск в psql:  \i /sql/05_test_data.sql   (после 02_seed.sql; занимает 10–30 секунд)
-- =====================================================================

\timing on
SELECT setseed(0.42);

-- 1. Генерируем 200 сотрудников
INSERT INTO employees (name, position)
SELECT 'Сотрудник ' || i, 'Должность ' || i
FROM generate_series(1, 200) AS i;

-- 2. Генерируем 1000 категорий
-- (owner_id берем из массива существующих id сотрудников)
WITH e AS (SELECT array_agg(id) AS ids FROM employees)
INSERT INTO categories (title, description, owner_id)
SELECT 'Категория №' || i, 'Описание', e.ids[1 + floor(random() * cardinality(e.ids))::int]
FROM generate_series(1, 1000) AS i, e;

-- 3. Генерируем 500 000 единиц оборудования
-- (category_id и employee_id берем из массивов, чтобы не нарушить внешние ключи)
WITH c AS (SELECT array_agg(id) AS ids FROM categories),
     e AS (SELECT array_agg(id) AS ids FROM employees)
INSERT INTO equipment (category_id, employee_id, inventory_number, title, status, created_at)
SELECT c.ids[1 + floor(random() * cardinality(c.ids))::int],
       CASE WHEN random() < 0.1 THEN NULL  -- 10% оборудования без ответственного
            ELSE e.ids[1 + floor(random() * cardinality(e.ids))::int] END,
       'INV-' || g.i,
       'Тестовое оборудование №' || g.i,
       CASE WHEN g.r < 0.60 THEN 'in_use'     -- 60% в работе
            WHEN g.r < 0.85 THEN 'in_repair'   -- 25% в ремонте
            ELSE 'archived' END,               -- 15% в архиве
       TIMESTAMP '2025-01-01' + random() * INTERVAL '640 days'
FROM (SELECT i, random() AS r FROM generate_series(1, 500000) AS i) AS g, c, e;

-- 4. Генерируем 1 000 000 заявок на ремонт
WITH eq AS (SELECT array_agg(id) AS ids FROM equipment),
     e  AS (SELECT array_agg(id) AS ids FROM employees)
INSERT INTO requests (equipment_id, author_id, description, created_at)
SELECT eq.ids[1 + floor(random() * cardinality(eq.ids))::int],
       e.ids[1 + floor(random() * cardinality(e.ids))::int],
       'Тестовая заявка №' || i,
       TIMESTAMP '2025-01-01' + random() * INTERVAL '640 days'
FROM generate_series(1, 1000000) AS i, eq, e;

-- Обновляем статистику планировщика
ANALYZE;
\timing off

-- Проверяем, сколько строк создалось
SELECT 'employees' AS table_name, COUNT(*) FROM employees
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'equipment',  COUNT(*) FROM equipment
UNION ALL SELECT 'requests',   COUNT(*) FROM requests;