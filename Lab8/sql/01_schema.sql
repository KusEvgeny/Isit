-- =====================================================================
-- Лабораторная работа №8. Часть 1. Структура базы данных
-- Предметная область: Система учёта офисного оборудования
-- Запуск в psql:  \i /sql/01_schema.sql
-- =====================================================================

SET client_min_messages = warning;
DROP TABLE IF EXISTS equipment_tags, tags, requests, equipment, categories, employee_profiles, employees CASCADE;

-- ---------------------------------------------------------------------
-- Сотрудники
-- ---------------------------------------------------------------------
CREATE TABLE employees (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    position    VARCHAR(100) NOT NULL DEFAULT 'Сотрудник',
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- TODO 1.1  Профиль сотрудника — связь «один к одному» с employees.
-- ---------------------------------------------------------------------
CREATE TABLE employee_profiles (
    employee_id INTEGER PRIMARY KEY REFERENCES employees (id) ON DELETE CASCADE,
    phone       VARCHAR(20),
    cabinet     VARCHAR(20) UNIQUE,
    email       VARCHAR(100) UNIQUE
);

-- ---------------------------------------------------------------------
-- TODO 1.2  Категории оборудования — связь «один ко многим» с employees 
-- (у ответственного сотрудника может быть много категорий, за которые он отвечает)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          SERIAL PRIMARY KEY,
    title       VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    owner_id    INTEGER      NOT NULL REFERENCES employees (id) ON DELETE RESTRICT,
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- TODO 1.3  Оборудование — основная сущность.
-- ---------------------------------------------------------------------
CREATE TABLE equipment (
    id               SERIAL PRIMARY KEY,
    category_id      INTEGER      NOT NULL REFERENCES categories (id) ON DELETE RESTRICT,
    employee_id      INTEGER      REFERENCES employees (id) ON DELETE SET NULL,
    inventory_number VARCHAR(50)  NOT NULL UNIQUE,
    title            VARCHAR(200) NOT NULL CHECK (char_length(title) >= 3),
    status           VARCHAR(20)  NOT NULL DEFAULT 'in_use' CHECK (status IN ('in_use', 'in_repair', 'archived')),
    created_at       TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- TODO 1.4  Заявки на ремонт оборудования.
-- ---------------------------------------------------------------------
CREATE TABLE requests (
    id          SERIAL PRIMARY KEY,
    equipment_id INTEGER NOT NULL REFERENCES equipment (id) ON DELETE CASCADE,
    author_id   INTEGER NOT NULL REFERENCES employees (id) ON DELETE RESTRICT,
    description TEXT    NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- Теги (ГОТОВО)
-- ---------------------------------------------------------------------
CREATE TABLE tags (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE
);

-- ---------------------------------------------------------------------
-- TODO 1.5  Связь «многие ко многим» оборудования и тегов.
-- ---------------------------------------------------------------------
CREATE TABLE equipment_tags (
    equipment_id INTEGER NOT NULL REFERENCES equipment (id) ON DELETE CASCADE,
    tag_id       INTEGER NOT NULL REFERENCES tags (id)  ON DELETE CASCADE,
    PRIMARY KEY (equipment_id, tag_id)
);