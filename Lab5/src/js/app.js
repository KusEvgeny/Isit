// app.js — клиентская логика страницы: события, работа с DOM, запросы к серверу.
// Читайте комментарии: в них написано, что делает код и что нужно дописать (пометки TODO).

// ============================================================
// Находим нужные элементы страницы (по id из index.html). Готово.
// ============================================================
const equipmentForm = document.getElementById('equipment-form');
const titleInput = document.getElementById('equipment-title');
const categorySelect = document.getElementById('equipment-category');
const statusFilter = document.getElementById('status-filter');
const searchInput = document.getElementById('search-input');
const refreshButton = document.getElementById('refresh-button');
const infoBox = document.getElementById('info');
const equipmentTable = document.getElementById('equipment-table');
const equipmentBody = document.getElementById('equipment-body');
const counter = document.getElementById('counter');

// В данных хранятся английские слова, а пользователю показываем русские подписи
const STATUS_NAMES = { in_use: 'В работе', in_repair: 'На ремонте', archived: 'В архиве' };
const CATEGORY_NAMES = { computer: 'Компьютер', monitor: 'Монитор', printer: 'Принтер', peripheral: 'Периферия' };

// ============================================================
// Готовые вспомогательные функции (менять не нужно)
// ============================================================

// Показать сообщение в области информации (isError = true — красным цветом)
function showInfo(text, isError) {
  infoBox.textContent = text;
  infoBox.className = isError ? 'message error-message' : 'message';
  infoBox.hidden = false;
}

// Обновить счётчик и показать/скрыть таблицу в зависимости от числа строк
function updateCounter() {
  const count = equipmentBody.children.length;
  counter.textContent = count;
  equipmentTable.hidden = (count === 0);
}

// Добавить в строку таблицы ячейку с текстом и вернуть эту ячейку
function addCell(row, text) {
  const cell = document.createElement('td');   // создаём новый элемент <td>
  cell.textContent = text;                     // записываем в него текст
  row.appendChild(cell);                       // добавляем в строку
  return cell;
}

// ============================================================
// ШАГ 3. Работа с DOM
// ============================================================

// Создаёт строку таблицы для одной единицы оборудования. Позже её будут использовать
// и загрузка с сервера, и добавление оборудования через форму.
function createEquipmentRow(equipment) {
  const row = document.createElement('tr');

  // TODO 3.1. Ячейки с данными (порядок как в HTML: №, Номер, Название, Категория, Статус)
  addCell(row, equipment.id);
  addCell(row, equipment.number);
  addCell(row, equipment.title);
  addCell(row, CATEGORY_NAMES[equipment.category] || equipment.category);
  addCell(row, STATUS_NAMES[equipment.status] || equipment.status);

  // TODO 3.2. Ячейка с кнопками действий
  const actions = document.createElement('td');

  const archiveButton = document.createElement('button');
  archiveButton.textContent = 'В архив';
  archiveButton.addEventListener('click', onArchiveClick);
  actions.appendChild(archiveButton);

  const deleteButton = document.createElement('button');
  deleteButton.textContent = 'Удалить';
  deleteButton.className = 'danger';
  deleteButton.addEventListener('click', onDeleteClick);
  actions.appendChild(deleteButton);

  row.appendChild(actions);

  // TODO 3.3. Запоминаем id записи в атрибуте строки
  row.setAttribute('data-id', equipment.id);

  // TODO 3.4. Если оборудование в архиве — выделяем строку и меняем текст кнопки
  if (equipment.status === 'archived') {
    row.classList.add('archived');
    archiveButton.textContent = 'Вернуть из архива';
  }

  return row;
}

// Событие click на кнопке «В архив»
function onArchiveClick(event) {
  const button = event.target;
  const row = button.closest('tr');

  row.classList.toggle('archived');
  const isArchived = row.classList.contains('archived');

  row.children[4].textContent = isArchived ? 'В архиве' : 'В работе';

  button.textContent = isArchived ? 'Вернуть из архива' : 'В архив';

  button.setAttribute('title', isArchived ? 'Вернуть в работу' : 'Отправить в архив');

  console.log('Нажата кнопка «В архив»', row);
}

// Событие click на кнопке «Удалить»
function onDeleteClick(event) {
  const row = event.target.closest('tr');

  if (!confirm('Удалить оборудование?')) {
    return;
  }

  row.remove();
  updateCounter();

  console.log('Нажата кнопка «Удалить»', row);
}

// ============================================================
// ШАГ 2. Обработка событий
// ============================================================

// Событие submit у формы: добавление новой задачи в таблицу
function onFormSubmit(event) {
  event.preventDefault();

  const title = titleInput.value.trim();
  console.log('Форма отправлена:', title, categorySelect.value);

  const errorTitle = document.getElementById('error-title');

  if (title.length < 3) {
    errorTitle.textContent = 'Название должно содержать минимум 3 символа';
    return;
  }

  errorTitle.textContent = '';

  const equipment = {
    id: '—',
    number: '—',
    title: title,
    category: categorySelect.value,
    status: 'in_use'
  };

  equipmentBody.insertBefore(createEquipmentRow(equipment), equipmentBody.firstChild);
  updateCounter();
  infoBox.hidden = true;
  equipmentForm.reset();
}

// ============================================================
// ШАГ 4. Асинхронные запросы (fetch)
// ============================================================

// Получает с сервера список задач. Слово async позволяет внутри использовать await.
async function loadEquipment(status, text) {
  const url = '/api/equipment?status=' + status + '&q=' + encodeURIComponent(text);
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error('Ошибка сервера: ' + response.status);
  }

  return await response.json();
}
// Загружает список с сервера и выводит его на страницу
async function refreshList() {
  showInfo('Загрузка…', false);
  refreshButton.disabled = true;

  try {
    const equipment = await loadEquipment(statusFilter.value, searchInput.value.trim());

    equipmentBody.innerHTML = '';

    for (let i = 0; i < equipment.length; i++) {
      equipmentBody.appendChild(createEquipmentRow(equipment[i]));
    }

    updateCounter();

    if (equipment.length === 0) {
      showInfo('Оборудование не найдено', false);
    } else {
      showInfo('Загружено единиц: ' + equipment.length, false);
    }
  } catch (error) {
    equipmentBody.innerHTML = '';
    updateCounter();

    if (error.message === 'Failed to fetch') {
      showInfo('Нет связи с сервером', true);
    } else {
      showInfo('Не удалось загрузить список. ' + error.message, true);
    }
  } finally {
    refreshButton.disabled = false;
  }
}

// ============================================================
// ШАГ 2 (продолжение). Подключаем обработчики событий
// ============================================================
// «Когда произойдёт ЭТО событие у ЭТОГО элемента — вызвать ЭТУ функцию».
// Пример (готов): форма вызывает onFormSubmit при отправке.
equipmentForm.addEventListener('submit', onFormSubmit);

refreshButton.addEventListener('click', refreshList);
statusFilter.addEventListener('change', refreshList);
searchInput.addEventListener('input', refreshList);

// ============================================================
// Запуск страницы
// ============================================================
refreshList();
