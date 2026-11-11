# Документация REST API «Система учёта офисного оборудования»

Базовый адрес: `http://localhost:8000`. Формат данных: JSON (UTF-8). Все адреса начинаются с `/api`.
Запуск: `cd lab7/src && php -S localhost:8000 -t public`.

## Сводная таблица

| Метод | URL | Назначение | Успешный код |
|-------|-----|------------|--------------|
| GET | `/api/equipment` | список оборудования (фильтры `status`, `employee_id`) | 200 |
| GET | `/api/equipment/{id}` | оборудование по номеру | 200 |
| POST | `/api/equipment` | создать оборудование | 201 |
| PUT, PATCH | `/api/equipment/{id}` | изменить оборудование | 200 |
| DELETE | `/api/equipment/{id}` | удалить оборудование | 204 |
| GET | `/api/employees` | список сотрудников | 200 |
| GET | `/api/employees/{id}` | сотрудник по номеру | 200 |
| GET | `/api/employees/{id}/equipment` | оборудование сотрудника | 200 |
| POST | `/api/employees` | создать сотрудника | 201 |
| PUT, PATCH | `/api/employees/{id}` | изменить сотрудника | 200 |
| DELETE | `/api/employees/{id}` | удалить сотрудника | 204 |
| GET | `/api/categories` | список категорий | 200 |
| GET | `/api/categories/{id}` | категория по номеру | 200 |
| POST | `/api/categories` | добавить категорию | 201 |
| PUT, PATCH | `/api/categories/{id}` | изменить категорию | 200 |
| DELETE | `/api/categories/{id}` | удалить категорию | 204 |

## Общие правила

- Тело запросов `POST`, `PUT`, `PATCH` — JSON-объект.
- Ошибки возвращаются в едином формате: `{"error": "текст"}`, а для ошибок проверки ещё и `"errors": {"поле": "текст"}`.
- `PUT` и `PATCH` обрабатываются одинаково: присланные поля заменяют старые, остальные остаются. Поле `id` изменить нельзя.

| Код | Когда |
|-----|-------|
| 400 | тело запроса — не корректный JSON-объект |
| 404 | нет записи или адреса |
| 405 | адрес есть, но не для этого метода (в заголовке `Allow` разрешённые) |
| 409 | удаление сотрудника, за которым закреплено оборудование |
| 422 | данные не прошли проверку |

---

## Ресурс «Оборудование»

Поля: `id` (число), `employee_id` (число, существующий сотрудник или 0), `number` (строка, инвентарный номер), `title` (строка, от 3 символов), `category` (`computer`, `monitor`, `printer`, `peripheral`), `status` (`in_use`, `in_repair`, `archived`).

### GET /api/equipment — список оборудования

Параметры строки запроса (необязательные): `status` — фильтр по статусу; `employee_id` — фильтр по сотруднику.

Запрос: `GET /api/equipment?status=in_use`

Ответ `200 OK`:
```json
[
    { "id": 1, "employee_id": 1, "number": "INV-001", "title": "Компьютер Lenovo", "category": "computer", "status": "in_use" },
    { "id": 2, "employee_id": 1, "number": "INV-002", "title": "Монитор Dell 17\"", "category": "monitor", "status": "in_use" }
]
```

### GET /api/equipment/{id} — оборудование по номеру

Запрос: `GET /api/equipment/3`

Ответ `200 OK`:
```json
{ "id": 3, "employee_id": 2, "number": "INV-003", "title": "Принтер HP-201", "category": "printer", "status": "in_repair" }
```
Ошибка `404`: `{ "error": "Оборудование не найдено" }`

### POST /api/equipment — создать оборудование

Тело (JSON): обязательные `title`, `number`; необязательные `employee_id` (по умолчанию `0`), `category` (по умолчанию `computer`), `status` (по умолчанию `in_use`).

Запрос:
```http
POST /api/equipment
Content-Type: application/json

{ "title": "Новый принтер", "number": "INV-888", "category": "printer", "employee_id": 2 }
```
Ответ `201 Created`, заголовок `Location: /api/equipment/9`:
```json
{ "id": 9, "employee_id": 2, "number": "INV-888", "title": "Новый принтер", "category": "printer", "status": "in_use" }
```
Ошибки: `400` (не JSON), `422`:
```json
{
    "error": "Ошибка проверки данных",
    "errors": {
        "title": "Название — не короче 3 символов",
        "number": "Укажите инвентарный номер",
        "employee_id": "Сотрудника с таким номером нет"
    }
}
```

### PUT, PATCH /api/equipment/{id} — изменить оборудование

Тело (JSON): любые из полей оборудования.

Запрос: `PATCH /api/equipment/9` с телом `{ "status": "in_repair" }`

Ответ `200 OK`:
```json
{ "id": 9, "employee_id": 2, "number": "INV-888", "title": "Новый принтер", "category": "printer", "status": "in_repair" }
```
Ошибки: `404`, `400`, `422`.

### DELETE /api/equipment/{id} — удалить оборудование

Запрос: `DELETE /api/equipment/9`. Ответ `204 No Content` (тела нет). Повторный запрос: `404`, `{ "error": "Оборудование не найдено" }`.

---

## Ресурс «Сотрудники»

Поля: `id`, `name` (строка, от 2 символов), `position` (строка, должность).

### GET /api/employees — список сотрудников

Ответ `200 OK`:
```json
[
    { "id": 1, "name": "Иванов Иван Иванович", "position": "Главный специалист" },
    { "id": 2, "name": "Петров Пётр Петрович", "position": "Менеджер" },
    { "id": 3, "name": "Сидоров Сергей Сергеевич", "position": "Инженер" }
]
```

### GET /api/employees/{id} — сотрудник по номеру

Запрос: `GET /api/employees/2`. Ответ `200 OK`: `{ "id": 2, "name": "Петров Пётр Петрович", "position": "Менеджер" }`. Ошибка `404`: `{ "error": "Сотрудник не найден" }`.

### GET /api/employees/{id}/equipment — оборудование сотрудника

Запрос: `GET /api/employees/1/equipment`. Ответ `200 OK`: массив оборудования сотрудника 1 (формат как у `/api/equipment`). Ошибка `404`, если сотрудника нет.

### POST /api/employees — создать сотрудника

Запрос: `POST /api/employees` с телом `{ "name": "Смирнов Алексей", "position": "Системный администратор" }`.
Ответ `201 Created`, `Location: /api/employees/4`: `{ "id": 4, "name": "Смирнов Алексей", "position": "Системный администратор" }`.
Ошибки: `400`, `422` (`{"error": "Ошибка проверки данных", "errors": {"name": "Имя — не короче 2 символов"}}`).

### PUT, PATCH /api/employees/{id} — изменить сотрудника

Запрос: `PATCH /api/employees/4` с телом `{ "position": "Ведущий инженер" }`.
Ответ `200 OK`: `{ "id": 4, "name": "Смирнов Алексей", "position": "Ведущий инженер" }`. Ошибки: `404`, `400`, `422`.

### DELETE /api/employees/{id} — удалить сотрудника

Запрос: `DELETE /api/employees/4`. Ответ `204 No Content`.
Ошибки: `404`; `409` если за сотрудником закреплено оборудование: `{ "error": "Нельзя удалить сотрудника: за ним закреплено оборудование" }` (пример: `DELETE /api/employees/1`).

---

## Ресурс «Категории»

Поля: `id`, `name` (строка, от 2 символов).

### GET /api/categories — список

Ответ `200 OK`:
```json
[
    { "id": 1, "name": "Компьютеры" },
    { "id": 2, "name": "Мониторы" },
    { "id": 3, "name": "Принтеры" },
    { "id": 4, "name": "Периферия" }
]
```

### GET /api/categories/{id} — категория по номеру

Запрос: `GET /api/categories/2`. Ответ `200 OK`: `{ "id": 2, "name": "Мониторы" }`. Ошибка `404`: `{ "error": "Категория не найдена" }`.

### POST /api/categories — добавить категорию

Тело: `name` (обязательное).
Запрос: `POST /api/categories` с телом `{ "name": "Сетевое оборудование" }`.
Ответ `201 Created`, `Location: /api/categories/5`: `{ "id": 5, "name": "Сетевое оборудование" }`. Ошибки: `400`, `422`.

### PUT, PATCH /api/categories/{id} — изменить категорию

Запрос: `PATCH /api/categories/5` с телом `{ "name": "Сетевое и серверное оборудование" }`. Ответ `200 OK`: запись с новым названием. Ошибки: `404`, `400`, `422`.

### DELETE /api/categories/{id} — удалить категорию

Запрос: `DELETE /api/categories/5`. Ответ `204 No Content`. Ошибка `404`.