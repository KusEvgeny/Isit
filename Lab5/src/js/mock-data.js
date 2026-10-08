// mock-data.js — учебный «сервер»: какие данные он хранит и что отвечает на запросы.
//
// ЭТОТ ФАЙЛ ВЫ РЕДАКТИРУЕТЕ под свою предметную область (Шаг 1).
// Саму работу сервера выполняет mock-api.js — его менять не нужно.
//
// Файл состоит из двух частей:
//   1. Данные — несколько массивов с записями (как таблицы в базе данных).
//   2. Таблица ROUTES — «на такой запрос сервер отвечает такими данными».

// ============================================================
// 1. ДАННЫЕ
// У каждой записи есть уникальный номер id. Остальные поля — любые.
// ============================================================

const equipment = [
  { id: 1, number: 'INV-001', title: 'Компьютер Lenovo',    category: 'computer',   status: 'in_use'    },
  { id: 2, number: 'INV-002', title: 'Монитор Dell 17"',    category: 'monitor',    status: 'in_use'    },
  { id: 3, number: 'INV-003', title: 'Принтер HP-201',      category: 'printer',    status: 'in_repair' },
  { id: 4, number: 'INV-004', title: 'Клавиатура Logitech', category: 'peripheral', status: 'archived'  },
  { id: 5, number: 'INV-005', title: 'Компьютер HP',        category: 'computer',   status: 'in_use'    },
  { id: 6, number: 'INV-006', title: 'Монитор Samsung 24"', category: 'monitor',    status: 'in_repair' },
  { id: 7, number: 'INV-007', title: 'МФУ Canon',           category: 'printer',    status: 'in_use'    }
];

// Вторая сущность. Нужна, если в вашей предметной области несколько видов объектов
// (например, оборудование и сотрудники). В базовой работе страница её не использует.
const users = [
  { id: 1, name: 'Анна Петрова', role: 'Руководитель' },
  { id: 2, name: 'Иван Сидоров', role: 'Администратор' }
];

// ============================================================
// 2. ТАБЛИЦА ЗАПРОСОВ
// Слева — запрос (метод и адрес), справа — функция, которая возвращает ответ.
// Параметр query — параметры из адреса после знака ? (например, { status: 'in_use', q: 'HP' }).
//
// Примеры того, как страница будет получать данные:
//   GET /api/equipment                  ->  всё оборудование
//   GET /api/equipment?status=in_use    ->  только оборудование в работе
//   GET /api/equipment?q=HP             ->  оборудование, в тексте которого есть «HP»
//   GET /api/users                      ->  все пользователи
//   любой другой запрос                 ->  ошибка 404 (для него нет строки в таблице)
// ============================================================

const ROUTES = {
  'GET /api/equipment': function (query) {
    return filterList(equipment, query);     // filterList выбирает записи по параметрам из query
  },

  'GET /api/users': function () {
    return users;                            // отдаём весь массив без фильтров
  }

  // ----- Для заданий повышенной сложности -----
  // Уберите // в начале строк, поставьте запятую после предыдущего пункта — и сервер научится
  // создавать, получать по номеру и удалять оборудование. Изменения хранятся до перезагрузки страницы.
  //
  // 'GET /api/equipment/:id': function (query, body, params) {
  //   const item = equipment.find(function (e) { return e.id === Number(params.id); });
  //   return item || reply(404, { error: 'Оборудование не найдено' });
  // },
  //
  // 'POST /api/equipment': function (query, body) {
  //   if (!body.title) {
  //     return reply(422, { error: 'Введите название оборудования' });
  //   }
  //   const item = { id: Date.now(), number: '—', title: body.title, category: body.category, status: 'in_use' };
  //   equipment.push(item);
  //   return reply(201, item);              // 201 — «создано»
  // },
  //
  // 'DELETE /api/equipment/:id': function (query, body, params) {
  //   const index = equipment.findIndex(function (e) { return e.id === Number(params.id); });
  //   if (index === -1) {
  //     return reply(404, { error: 'Оборудование не найдено' });
  //   }
  //   equipment.splice(index, 1);
  //   return reply(204);                    // 204 — «выполнено, ответа нет»
  // }
};