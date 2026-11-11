-- =====================================================================
-- Лабораторная работа №8. Часть 5. Анализ и оптимизация SQL-запросов
-- Запуск в psql:  \i /sql/06_optimization.sql   (после 05_test_data.sql)
-- =====================================================================

DROP INDEX IF EXISTS idx_equipment_category_id;
DROP INDEX IF EXISTS idx_equipment_status_title;
DROP INDEX IF EXISTS idx_requests_equipment_id;
-- Сюда мы допишем индексы для TODO 5.2
DROP INDEX IF EXISTS idx_equipment_status_created_at;

ANALYZE equipment;
ANALYZE requests;

\echo 'Индексы до оптимизации (только первичные ключи и UNIQUE):'
SELECT tablename, indexname FROM pg_indexes
WHERE tablename IN ('equipment', 'requests') ORDER BY tablename, indexname;


-- ---------------------------------------------------------------------
-- Запрос 1. Оборудование категории (ГОТОВО, образец)
-- ---------------------------------------------------------------------
\echo '=== Запрос 1. Оборудование категории: до ==='
EXPLAIN ANALYZE
SELECT id, title, status FROM equipment WHERE category_id = 500 ORDER BY id;

CREATE INDEX idx_equipment_category_id ON equipment (category_id);

\echo '=== Запрос 1. Оборудование категории: после индекса ==='
EXPLAIN ANALYZE
SELECT id, title, status FROM equipment WHERE category_id = 500 ORDER BY id;


-- ---------------------------------------------------------------------
-- TODO 5.1  Запрос 2. Оборудование в ремонте, закреплённое за сотрудником № 42:
-- ---------------------------------------------------------------------
\echo '=== Запрос 2. Ремонт у сотрудника: до ==='
EXPLAIN ANALYZE
SELECT id, title, inventory_number FROM equipment
WHERE employee_id = 42 AND status = 'in_repair'
ORDER BY title;

CREATE INDEX idx_equipment_employee_status ON equipment (employee_id, status);

\echo '=== Запрос 2. Ремонт у сотрудника: после индекса ==='
EXPLAIN ANALYZE
SELECT id, title, inventory_number FROM equipment
WHERE employee_id = 42 AND status = 'in_repair'
ORDER BY title;


-- ---------------------------------------------------------------------
-- TODO 5.2  Запрос 3. Последние поступления (сортировка и лимит):
-- ---------------------------------------------------------------------
\echo '=== Запрос 3. Последние поступления: до ==='
EXPLAIN ANALYZE
SELECT id, title, created_at FROM equipment 
WHERE status = 'in_use' 
ORDER BY created_at DESC LIMIT 20;

CREATE INDEX idx_equipment_status_created_at ON equipment (status, created_at DESC);

\echo '=== Запрос 3. Последние поступления: после индекса ==='
EXPLAIN ANALYZE
SELECT id, title, created_at FROM equipment 
WHERE status = 'in_use' 
ORDER BY created_at DESC LIMIT 20;


-- ---------------------------------------------------------------------
-- TODO 5.3  Запрос 4. Заявки на оборудование № 2 с именами авторов:
-- ---------------------------------------------------------------------
\echo '=== Запрос 4. Заявки на оборудование: до ==='
EXPLAIN ANALYZE
SELECT r.id, r.description, e.name AS author
FROM requests r
JOIN employees e ON e.id = r.author_id
WHERE r.equipment_id = 2
ORDER BY r.created_at;

CREATE INDEX idx_requests_equipment_id ON requests (equipment_id);

\echo '=== Запрос 4. Заявки на оборудование: после индекса ==='
EXPLAIN ANALYZE
SELECT r.id, r.description, e.name AS author
FROM requests r
JOIN employees e ON e.id = r.author_id
WHERE r.equipment_id = 2
ORDER BY r.created_at;


-- ---------------------------------------------------------------------
-- TODO 5.4  Когда индекс НЕ помогает (в обоих случаях Seq Scan)
-- ---------------------------------------------------------------------
\echo '=== Запрос 5. Подсчет большинства (индекс не поможет) ==='
EXPLAIN ANALYZE
SELECT COUNT(*) FROM equipment WHERE status = 'in_use'; 

\echo '=== Запрос 6. Поиск подстроки (индекс не поможет) ==='
EXPLAIN ANALYZE
SELECT id, title FROM equipment WHERE title ILIKE '%задача №4999%';


-- ---------------------------------------------------------------------
-- Цена индексов: место на диске
-- ---------------------------------------------------------------------
\echo '=== Размер индексов ==='
SELECT indexrelname AS index_name,
       pg_size_pretty(pg_relation_size(indexrelid)) AS size
FROM pg_stat_user_indexes
WHERE relname IN ('equipment', 'requests')
ORDER BY pg_relation_size(indexrelid) DESC;