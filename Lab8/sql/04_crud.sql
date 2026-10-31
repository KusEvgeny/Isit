-- =====================================================================
-- Лабораторная работа №8. Часть 4. CRUD-операции
-- Запуск в psql:  \i /sql/04_crud.sql   (после 02_seed.sql)
-- Рядом с каждым запросом указан адрес REST API из лабораторной работы №7,
-- который будет выполнять этот запрос, когда API подключится к базе.
-- =====================================================================

-- ---------------------------------------------------------------------
-- CREATE (INSERT) — ГОТОВО, образец
-- ---------------------------------------------------------------------
\echo '=== INSERT: создать оборудование (POST /api/equipment) ==='
-- RETURNING возвращает созданную строку сразу, отдельный SELECT не нужен.
-- \gset сохраняет номер нового оборудования в переменную psql :eq_id
INSERT INTO equipment (category_id, employee_id, inventory_number, title, status)
VALUES (1, 2, 'INV-777', 'Новый компьютер', 'in_use')
RETURNING id AS eq_id \gset
SELECT id, category_id, employee_id, inventory_number, title, status
FROM equipment WHERE id = :eq_id;

-- TODO 4.1  Добавьте новому оборудованию два тега одной командой
INSERT INTO equipment_tags (equipment_id, tag_id) VALUES (:eq_id, 1), (:eq_id, 2);
-- Проверка: INSERT 0 2.


-- ---------------------------------------------------------------------
-- READ (SELECT)
-- ---------------------------------------------------------------------

-- TODO 4.2  Список оборудования со статусом in_repair, отсортированный по названию (GET /api/equipment?status=in_repair):
SELECT id, inventory_number, title, status
FROM equipment
WHERE status = 'in_repair'
ORDER BY title;

-- И одна запись по номеру (GET /api/equipment/5):
SELECT id, inventory_number, title, status
FROM equipment WHERE id = 5;


-- TODO 4.3  Оборудование с названием категории и именем ответственного сотрудника.
--           Для сотрудника нужен LEFT JOIN, иначе оборудование без ответственного пропадёт из результата:
SELECT e.id, e.title, e.inventory_number, c.title AS category, emp.name AS employee, e.status
FROM equipment e
JOIN categories c ON c.id = e.category_id
LEFT JOIN employees emp ON emp.id = e.employee_id
ORDER BY e.id;


-- TODO 4.4  Оборудование без ответственного. Сравнение с NULL пишется через IS NULL:
SELECT id, title, inventory_number
FROM equipment
WHERE employee_id IS NULL;


-- TODO 4.5  Связь N:M: теги каждой единицы оборудования одной строкой. 
SELECT e.id, e.title, string_agg(t.name, ', ' ORDER BY t.name) AS tags
FROM equipment e
JOIN equipment_tags et ON et.equipment_id = e.id
JOIN tags t            ON t.id = et.tag_id
GROUP BY e.id, e.title
ORDER BY e.id;


-- TODO 4.6  Отчёт: сколько всего единиц оборудования и сколько из них в ремонте у каждого сотрудника.
SELECT emp.id, emp.name,
       COUNT(e.id) AS total_equipment,
       COUNT(e.id) FILTER (WHERE e.status = 'in_repair') AS in_repair_count
FROM employees emp
LEFT JOIN equipment e ON e.employee_id = emp.id
GROUP BY emp.id, emp.name
ORDER BY emp.id;


-- ---------------------------------------------------------------------
-- UPDATE
-- ---------------------------------------------------------------------

-- TODO 4.7  Переведите новое оборудование в ремонт (PATCH /api/equipment/{id}).
UPDATE equipment SET status = 'in_repair' WHERE id = :eq_id RETURNING id, title, status;

-- Затем измените у него сразу два поля: статус на 'archived' и добавьте к названию суффикс " (списано)".
UPDATE equipment 
SET status = 'archived', title = title || ' (списано)'
WHERE id = :eq_id RETURNING id, title, status;


-- ---------------------------------------------------------------------
-- DELETE
-- ---------------------------------------------------------------------

-- TODO 4.8  Удалите новое оборудование (DELETE /api/equipment/{id}) и убедитесь, что его теги удалились каскадом:
DELETE FROM equipment WHERE id = :eq_id RETURNING id, title;
SELECT COUNT(*) AS tags_left FROM equipment_tags WHERE equipment_id = :eq_id;

-- Выполните DELETE ещё раз. 
DELETE FROM equipment WHERE id = :eq_id;
-- Проверка: первый DELETE — 1 строка, tags_left = 0; повторный DELETE — DELETE 0.