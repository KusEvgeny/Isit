<?php
// app/helpers.php — небольшие вспомогательные функции для всего приложения.

declare(strict_types=1);

// Читает настройку из config/app.php: config('name'), config('debug')
function config(string $key): mixed
{
    static $config = null;                                 // файл читается один раз
    $config ??= require BASE_PATH . '/config/app.php';
    return $config[$key] ?? null;
}

// Безопасный вывод текста в HTML: заменяет символы < > & " на безопасные коды.
// Защита от XSS: данные пользователя в шаблонах всегда выводим через e(...), а не напрямую.
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Показывает страницу: шапка + шаблон + подвал.
// view('tasks/index', ['tasks' => $tasks]) подключает app/Views/tasks/index.php,
// а внутри шаблона будет доступна переменная $tasks.
function view(string $template, array $data = []): void
{
    extract($data);                                        // ключи массива становятся переменными
    require APP_PATH . '/Views/layout/header.php';
    require APP_PATH . '/Views/' . $template . '.php';
    require APP_PATH . '/Views/layout/footer.php';
}

// Перенаправляет браузер на другой адрес и завершает работу скрипта.
function redirect(string $path): never
{
    header('Location: ' . $path);                          // 302: «ваша страница теперь по этому адресу»
    exit;
}

// Показывает страницу «Не найдено» с кодом ответа 404 и завершает работу скрипта.
function notFound(): never
{
    http_response_code(404);
    view('errors/404', ['path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)]);
    exit;
}

// Проверяет, начинается ли текущий адрес с /api/
function is_api_request(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    return str_starts_with($path, '/api/');
}

// Отправляет данные в формате JSON и завершает работу скрипта
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Отправляет ошибку в формате JSON и завершает работу скрипта.
// $details — необязательный массив с подробностями (например, ошибки валидации полей)
function json_error(string $message, int $status = 400, array $details = []): never
{
    $body = ['error' => $message];
    if (!empty($details)) {
        $body['details'] = $details;
    }
    json_response($body, $status);
}

// Читает тело запроса и превращает JSON в массив.
// Если тело пустое или это не JSON — возвращает 400 Bad Request.
function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        json_error('Пустое тело запроса. Ожидается JSON.', 400);
    }
    
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_error('Некорректный JSON: ' . json_last_error_msg(), 400);
    }
    
    return $data;
}

// Отдаёт ответ 204 No Content (успех, но без тела — например, после DELETE)
function no_content(): never
{
    http_response_code(204);
    exit;
}