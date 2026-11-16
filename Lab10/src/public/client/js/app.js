// app.js — логика страницы
const loginScreen = document.getElementById('login-screen');
const mainScreen = document.getElementById('main-screen');
const loginForm = document.getElementById('login-form');
const taskForm = document.getElementById('task-form');
const taskBody = document.getElementById('task-body');
const taskTable = document.getElementById('task-table');
const messageBox = document.getElementById('message');

function showScreen(name) {
  loginScreen.hidden = (name !== 'login');
  mainScreen.hidden = (name !== 'main');
}

function escapeHtml(text) {
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showMessage(text, isError) {
  messageBox.textContent = text;
  messageBox.className = isError ? 'message error-message' : 'message';
  messageBox.hidden = false;
}

function hideMessage() { messageBox.hidden = true; }

function setError(name, text) {
  const el = document.getElementById('error-' + name);
  if (el) el.textContent = text;
}

function clearErrors() {
  document.querySelectorAll('.error').forEach(el => el.textContent = '');
}

function handleUnauthorized() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  showScreen('login');
  setError('login', 'Сессия истекла. Войдите снова.');
}

function renderEquipment(items) {
  const user = currentUser();
  let html = '';

  const categories = { 1: 'Компьютеры', 2: 'Мониторы', 3: 'Принтеры' };
  const statuses = { 'in_use': 'В работе', 'in_repair': 'На ремонте', 'archived': 'В архиве' };

  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    let actions = '';

    // Кнопку удаления видит только руководитель
    if (user && user.role === 'manager') {
      actions += `<button class="danger delete-button" data-id="${item.id}">Удалить</button>`;
    }

    html += `
      <tr>
        <td>${item.id}</td>
        <td>${escapeHtml(item.inventory_number)}</td>
        <td>${escapeHtml(item.title)}</td>
        <td>${categories[item.category_id] || '—'}</td>
        <td>${statuses[item.status] || item.status}</td>
        <td>${actions}</td>
      </tr>`;
  }

  taskBody.innerHTML = html;
  taskTable.hidden = (items.length === 0);

  document.querySelectorAll('.delete-button').forEach(btn => {
    btn.addEventListener('click', onDeleteClick);
  });
}

async function refreshList() {
  showMessage('Загрузка…', false);
  try {
    const items = await getEquipment();
    renderEquipment(items);
    hideMessage();
  } catch (error) {
    renderEquipment([]);
    showMessage('Не удалось загрузить список: ' + error.message, true);
  }
}

async function onLoginSubmit(event) {
  event.preventDefault();
  clearErrors();
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;

  try {
    const result = await login(email, password);
    localStorage.setItem('token', result.token);
    localStorage.setItem('user', JSON.stringify(result.user));
    openMainScreen();
  } catch (error) {
    setError('login', error.message);
  }
}

async function onTaskSubmit(event) {
  event.preventDefault();
  clearErrors();

  const title = document.getElementById('task-title').value.trim();
  const inventory = document.getElementById('task-inventory').value.trim();
  const category_id = Number(document.getElementById('task-category').value);

  try {
    await createEquipment({ title, inventory_number: inventory, category_id, status: 'in_use' });
    taskForm.reset();
    await refreshList();
  } catch (error) {
    if (error.fields) {
       for (let name in error.fields) {
           let fieldName = name === 'inventory_number' ? 'inventory' : (name === 'category_id' ? 'category' : name);
           setError(fieldName, error.fields[name]);
       }
    } else {
       setError('form', error.message);
    }
  }
}

async function onDeleteClick(event) {
  const id = event.target.dataset.id;
  if (!confirm('Удалить оборудование №' + id + '?')) return;

  event.target.disabled = true;
  try {
    await deleteEquipment(id);
    await refreshList();
  } catch (error) {
    event.target.disabled = false;
    showMessage(error.message, true);
  }
}

async function onLogoutClick() {
  await logout();
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  showScreen('login');
}

loginForm.addEventListener('submit', onLoginSubmit);
taskForm.addEventListener('submit', onTaskSubmit);
document.getElementById('logout-button').addEventListener('click', onLogoutClick);
document.getElementById('refresh-button').addEventListener('click', refreshList);

async function openMainScreen() {
  const user = currentUser();
  document.getElementById('user-name').textContent = user.name;
  document.getElementById('user-role').textContent = user.role === 'manager' ? 'Руководитель' : 'Сотрудник';
  showScreen('main');
  await refreshList();
}

async function start() {
  if (!getToken()) { showScreen('login'); return; }
  try {
    const user = await me();
    localStorage.setItem('user', JSON.stringify(user));
    openMainScreen();
  } catch (error) {
    showScreen('login');
  }
}
start();