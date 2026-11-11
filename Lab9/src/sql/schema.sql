-- sql/schema.sql — схема базы данных для учёта оборудования

DROP TABLE IF EXISTS auth_tokens CASCADE;
DROP TABLE IF EXISTS equipment CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS employees CASCADE;

-- 1. Сотрудники (вместо users)
CREATE TABLE employees (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'user' CHECK (role IN ('manager', 'user')),
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- 2. Категории (вместо projects)
CREATE TABLE categories (
    id    SERIAL PRIMARY KEY,
    title VARCHAR(150) NOT NULL UNIQUE
);

-- 3. Оборудование (вместо tasks)
CREATE TABLE equipment (
    id               SERIAL PRIMARY KEY,
    category_id      INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    employee_id      INTEGER REFERENCES employees(id) ON DELETE SET NULL,
    inventory_number VARCHAR(50) NOT NULL UNIQUE,
    title            VARCHAR(200) NOT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'in_use' CHECK (status IN ('in_use', 'in_repair', 'archived')),
    created_at       TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 4. Токены для входа
CREATE TABLE auth_tokens (
    token_hash VARCHAR(64) PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    expires_at TIMESTAMP NOT NULL
);