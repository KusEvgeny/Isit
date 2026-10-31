-- sql/seed.sql — начальные данные

-- Пароль для всех: 123456 (хеш сгенерирован через password_hash)
INSERT INTO employees (name, email, password_hash, role) VALUES
    ('Анна Петрова',  'anna@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
    ('Игорь Смирнов', 'igor@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('Мария Волкова', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO categories (title) VALUES
    ('Компьютеры'),
    ('Мониторы'),
    ('Принтеры');

INSERT INTO equipment (category_id, employee_id, inventory_number, title, status) VALUES
    (1, 1, 'INV-001', 'Компьютер Lenovo', 'in_use'),
    (2, 1, 'INV-002', 'Монитор Dell', 'in_use'),
    (3, 2, 'INV-003', 'Принтер HP', 'in_repair'),
    (1, 3, 'INV-004', 'Компьютер HP', 'archived');