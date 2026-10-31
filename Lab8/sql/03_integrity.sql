-- =====================================================================
-- Лабораторная работа №8. Часть 3. Проверка ссылочной целостности и ограничений
-- Каждый запрос ниже НАРУШАЕТ правило, и база должна его отклонить с ошибкой (ERROR).
-- Ошибка здесь — это успех: значит, ограничение работает.
-- Запуск в psql:  \i /sql/03_integrity.sql   (после 02_seed.sql)
-- =====================================================================

-- ГОТОВО (образец)
\echo '--- 1. Оборудование в несуществующей категории (FOREIGN KEY) ---'
INSERT INTO equipment (category_id, inventory_number, title) VALUES (99, 'INV-999', 'Оборудование без категории');
-- ожидаемый ответ: ERROR: ... violates foreign key constraint "equipment_category_id_fkey"

-- ГОТОВО (образец)
\echo '--- 2. Неизвестный статус оборудования (CHECK) ---'
INSERT INTO equipment (category_id, inventory_number, title, status) VALUES (1, 'INV-888', 'Проверить статус', 'broken');
-- ожидаемый ответ: ERROR: ... violates check constraint "equipment_status_check"

-- TODO 3.1  По образцу напишите ещё четыре проверки ограничений (для каждой \echo и один запрос):
--             3. оборудование с названием 'Да'                  -> equipment_title_check
--             4. оборудование без названия (title = NULL)       -> violates not-null constraint
--             5. сотрудник с почтой ivanov@example.com          -> employee_profiles_email_key (UNIQUE)
--             6. второй профиль у сотрудника № 1                -> employee_profiles_pkey (связь 1:1)

\echo '--- 3. Название оборудования слишком короткое (CHECK) ---'
INSERT INTO equipment (category_id, inventory_number, title) VALUES (1, 'INV-777', 'Да');
-- ожидаемый ответ: ERROR: ... violates check constraint "equipment_title_check"

\echo '--- 4. Оборудование без названия (NOT NULL) ---'
INSERT INTO equipment (category_id, inventory_number, title) VALUES (1, 'INV-666', NULL);
-- ожидаемый ответ: ERROR: ... null value in column "title" violates not-null constraint

\echo '--- 5. Повторяющийся email профиля (UNIQUE) ---'
INSERT INTO employee_profiles (employee_id, email) VALUES (4, 'ivanov@example.com');
-- ожидаемый ответ: ERROR: ... duplicate key value violates unique constraint "employee_profiles_email_key"

\echo '--- 6. Второй профиль у того же сотрудника (PRIMARY KEY 1:1) ---'
INSERT INTO employee_profiles (employee_id, email) VALUES (1, 'second@example.com');
-- ожидаемый ответ: ERROR: ... duplicate key value violates unique constraint "employee_profiles_pkey"


-- TODO 3.2  Проверка связи N:M: повесьте на оборудование № 1 тег № 1 ещё раз.
\echo '--- 7. Повторный тег у оборудования (N:M) ---'
INSERT INTO equipment_tags (equipment_id, tag_id) VALUES (3, 1);
-- ожидаемый ответ: ERROR: ... duplicate key value violates unique constraint "equipment_tags_pkey"


-- TODO 3.3  Проверка ON DELETE RESTRICT: попробуйте удалить категорию № 1 (в ней есть оборудование)
--           и сотрудника № 1 (он ответственный за категории и автор заявок):
\echo '--- 8. Удаление категории с оборудованием (RESTRICT) ---'
DELETE FROM categories WHERE id = 1;
-- ожидаемый ответ: ERROR: ... is still referenced from table ...

\echo '--- 9. Удаление сотрудника, у которого есть заявки (RESTRICT) ---'
DELETE FROM employees WHERE id = 1;
-- ожидаемый ответ: ERROR: ... is still referenced from table ...


-- TODO 3.4  Проверка ON DELETE CASCADE и SET NULL. Такие удаления выполняются успешно,
--           поэтому обернём их в транзакцию и отменим, чтобы не потерять данные:
\echo '--- 10. Удаление оборудования удаляет его заявки и теги (CASCADE) ---'
BEGIN;
SELECT COUNT(*) AS requests_before FROM requests WHERE equipment_id = 3;
DELETE FROM equipment WHERE id = 3;
SELECT COUNT(*) AS requests_after FROM requests WHERE equipment_id = 3;
ROLLBACK;

\echo '--- 11. Удаление сотрудника обнуляет ответственного у оборудования (SET NULL) ---'
BEGIN;
-- 1. Создаем временного сотрудника
INSERT INTO employees (id, name, position) VALUES (99, 'Временный Сотрудник', 'Стажер');

-- 2. Закрепляем за ним оборудование 5 и 6
UPDATE equipment SET employee_id = 99 WHERE id IN (5, 6);
SELECT id, title, employee_id FROM equipment WHERE id IN (5, 6);
-- (Здесь employee_id = 99)

-- 3. Удаляем сотрудника
DELETE FROM employees WHERE id = 99;

-- 4. Проверяем, что оборудование осталось, а employee_id стало NULL (пусто)
SELECT id, title, employee_id FROM equipment WHERE id IN (5, 6);
-- (Здесь employee_id должен быть пустым)

ROLLBACK;