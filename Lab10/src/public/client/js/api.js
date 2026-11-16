// api.js — запросы к серверу
function getToken() { return localStorage.getItem('token'); }
function currentUser() { try { return JSON.parse(localStorage.getItem('user')); } catch (e) { return null; } }

function authHeaders() {
  return {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + getToken()
  };
}

async function checkResponse(response, isLogin) {
  if (response.ok) return;

  let data = {};
  try { data = await response.json(); } catch (error) {}

  const error = new Error(data.error || 'Ошибка сервера: ' + response.status);
  error.status = response.status;
  error.fields = data.errors || {};

  if (response.status === 401 && !isLogin) {
    handleUnauthorized();
  }
  throw error;
}

async function login(email, password) {
  const response = await fetch(API_BASE + '/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: email, password: password })
  });
  await checkResponse(response, true);
  return await response.json();
}

async function me() {
  const response = await fetch(API_BASE + '/api/auth/me', { headers: authHeaders() });
  await checkResponse(response);
  return await response.json();
}

async function logout() {
  try { await fetch(API_BASE + '/api/auth/logout', { method: 'POST', headers: authHeaders() }); } catch (e) {}
}

async function getEquipment() {
  const response = await fetch(API_BASE + '/api/equipment', { headers: authHeaders() });
  await checkResponse(response);
  return await response.json();
}

async function createEquipment(item) {
  const response = await fetch(API_BASE + '/api/equipment', {
    method: 'POST',
    headers: authHeaders(),
    body: JSON.stringify(item)
  });
  await checkResponse(response);
  return await response.json();
}

async function deleteEquipment(id) {
  const response = await fetch(API_BASE + '/api/equipment/' + id, {
    method: 'DELETE',
    headers: authHeaders()
  });
  await checkResponse(response);
}