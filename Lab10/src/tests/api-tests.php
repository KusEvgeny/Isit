<?php
// tests/api-tests.php — автоматические тесты API (Оборудование + Кеш).

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$root = dirname(__DIR__);

define('BASE_PATH', $root);
define('APP_PATH', $root . '/app');
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

function call(string $method, string $path, ?array $body = null, ?string $token = null, ?string $rawBody = null, array $extraHeaders = []): array
{
    global $base;
    $headers = ['Accept: application/json', ...$extraHeaders];
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $content = $rawBody ?? ($body !== null ? json_encode($body, JSON_UNESCAPED_UNICODE) : null);
    if ($content !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    $context = stream_context_create(['http' => [
        'method' => $method, 'header' => implode("\r\n", $headers), 'content' => $content ?? '',
        'ignore_errors' => true, 'timeout' => 10,
    ]]);
    $text = @file_get_contents($base . $path, false, $context);
    if ($text === false) {
        fwrite(STDERR, "Не удалось подключиться к $base.\n");
        exit(2);
    }
    $status = 0;
    $responseHeaders = [];
    foreach ($http_response_header as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
            $status = (int) $m[1];
        } elseif (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[strtolower(trim($name))] = trim($value);
        }
    }
    return ['status' => $status, 'headers' => $responseHeaders, 'json' => json_decode($text, true), 'body' => $text];
}

function login(string $email, string $password = '123456'): string
{
    $r = call('POST', '/api/auth/login', ['email' => $email, 'password' => $password]);
    return $r['json']['token'] ?? '';
}

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $details = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  \033[32mPASS\033[0m  $name\n";
    } else {
        $failed++;
        echo "  \033[31mFAIL\033[0m  $name" . ($details !== '' ? "  ($details)" : '') . "\n";
    }
}

function expectStatus(string $name, int $expected, array $r): void
{
    check($name . ' → ' . $expected, $r['status'] === $expected, 'получен код ' . $r['status']);
}

function section(string $title): void
{
    echo "\n$title\n";
}

function rows(array $r): array
{
    return is_array($r['json']) && array_is_list($r['json']) ? $r['json'] : [];
}

$devNull = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/bin/reset-db.php') . ' > ' . $devNull);

$manager = login('anna@example.com');
$user = login('igor@example.com');

// ---------------------------------------------------------------------
section('1. Вход в систему');
// ---------------------------------------------------------------------
$r = call('POST', '/api/auth/login', ['email' => 'anna@example.com', 'password' => '123456']);
expectStatus('вход с верными данными', 200, $r);
check('в ответе есть токен', strlen($r['json']['token'] ?? '') === 64);
check('в ответе есть участник с ролью manager', ($r['json']['user']['role'] ?? '') === 'manager');
check('в ответе НЕТ хеша пароля', !str_contains($r['body'], 'password_hash') && !str_contains($r['body'], '$2y$'));

expectStatus('вход с неверным паролем', 401, call('POST', '/api/auth/login', ['email' => 'anna@example.com', 'password' => 'wrong']));
expectStatus('вход с пустыми полями', 422, call('POST', '/api/auth/login', ['email' => '', 'password' => '']));
expectStatus('GET на адрес входа', 405, call('GET', '/api/auth/login'));

// ---------------------------------------------------------------------
section('2. Защита адресов API');
// ---------------------------------------------------------------------
expectStatus('GET /api/equipment без токена', 401, call('GET', '/api/equipment'));
expectStatus('POST /api/equipment без токена', 401, call('POST', '/api/equipment', ['title' => 'Без входа']));
expectStatus('GET /api/equipment с неверным токеном', 401, call('GET', '/api/equipment', null, str_repeat('a', 64)));
expectStatus('GET /api/equipment с действительным токеном', 200, call('GET', '/api/equipment', null, $manager));
expectStatus('GET /api/auth/me', 200, call('GET', '/api/auth/me', null, $user));
expectStatus('несуществующий адрес с токеном', 404, call('GET', '/api/unknown', null, $manager));

// ---------------------------------------------------------------------
section('3. Роли и права');
// ---------------------------------------------------------------------
$created = call('POST', '/api/equipment', ['title' => 'Оборудование для проверки', 'inventory_number' => 'INV-100', 'category_id' => 1], $user);
expectStatus('user создаёт оборудование', 201, $created);
$id = $created['json']['id'] ?? 0;
expectStatus('user удаляет оборудование', 403, call('DELETE', "/api/equipment/$id", null, $user));
expectStatus('manager удаляет оборудование', 204, call('DELETE', "/api/equipment/$id", null, $manager));

// ---------------------------------------------------------------------
section('4. Оборудование: полный цикл');
// ---------------------------------------------------------------------
$r = call('POST', '/api/equipment', ['title' => 'МФУ Canon', 'inventory_number' => 'INV-555', 'category_id' => 3, 'status' => 'in_use'], $manager);
expectStatus('создание оборудования', 201, $r);
check('в ответе есть Location', str_starts_with($r['headers']['location'] ?? '', '/api/equipment/'));
check('статус по умолчанию — in_use', ($r['json']['status'] ?? '') === 'in_use');
$id = $r['json']['id'] ?? 0;

$r = call('PATCH', "/api/equipment/$id", ['status' => 'in_repair'], $manager);
expectStatus('изменение статуса', 200, $r);
check('изменился только статус', ($r['json']['status'] ?? '') === 'in_repair' && ($r['json']['title'] ?? '') === 'МФУ Canon');

expectStatus('удаление оборудования', 204, call('DELETE', "/api/equipment/$id", null, $manager));
expectStatus('повторное удаление', 404, call('DELETE', "/api/equipment/$id", null, $manager));
expectStatus('GET удалённого оборудования', 404, call('GET', "/api/equipment/$id", null, $manager));

// ---------------------------------------------------------------------
section('5. Завершение сессии и срок действия токена');
// ---------------------------------------------------------------------
$token = login('maria@example.com');
expectStatus('токен работает до выхода', 200, call('GET', '/api/equipment', null, $token));
expectStatus('POST /api/auth/logout', 204, call('POST', '/api/auth/logout', null, $token));
expectStatus('после выхода токен не работает', 401, call('GET', '/api/equipment', null, $token));

$token = login('maria@example.com');
try {
    App\Core\Database::connection()->exec("UPDATE auth_tokens SET expires_at = now() - interval '1 hour'");
} catch (Throwable $e) {
    check('подключение к базе данных из теста', false, $e->getMessage());
}
expectStatus('токен с истёкшим сроком', 401, call('GET', '/api/equipment', null, $token));

// ---------------------------------------------------------------------
section('6. Кеширование в Redis');
// ---------------------------------------------------------------------
$redis = App\Core\Cache::redis();
if ($redis === null) {
    check('Redis доступен тесту (php bin/redis-check.php)', false, 'не удалось подключиться к Redis');
} else {
    $manager = login('anna@example.com');
    $hit = fn(array $r): string => $r['headers']['x-cache'] ?? '-';
    App\Core\Cache::flushApp();

    // --- Список оборудования
    $a = call('GET', '/api/equipment', null, $manager);
    $b = call('GET', '/api/equipment', null, $manager);
    check('список: первый запрос — MISS, второй — HIT', $hit($a) === 'MISS' && $hit($b) === 'HIT', 'получено ' . $hit($a) . ', ' . $hit($b));
    check('список: данные из кеша совпадают с данными из базы', $a['json'] === $b['json']);
    
    // --- Карточка оборудования и инвалидация при изменении
    $a = call('GET', '/api/equipment/1', null, $manager);
    $b = call('GET', '/api/equipment/1', null, $manager);
    check('карточка: первый запрос — MISS, второй — HIT', $hit($a) === 'MISS' && $hit($b) === 'HIT', 'получено ' . $hit($a) . ', ' . $hit($b));
    
    call('PATCH', '/api/equipment/1', ['title' => 'Название после изменения'], $manager);
    $c = call('GET', '/api/equipment/1', null, $manager);
    check('после PATCH карточка пересоздана (MISS) и содержит новое название', $hit($c) === 'MISS' && ($c['json']['title'] ?? '') === 'Название после изменения', 'получено ' . $hit($c) . ', ' . ($c['json']['title'] ?? ''));

    // --- Отчёт и инвалидация при добавлении оборудования
    $a = call('GET', '/api/equipment/summary', null, $manager);
    $b = call('GET', '/api/equipment/summary', null, $manager);
    check('отчёт: первый запрос — MISS, второй — HIT', $hit($a) === 'MISS' && $hit($b) === 'HIT', 'получено ' . $hit($a) . ', ' . $hit($b));
    
    $created = call('POST', '/api/equipment', ['title' => 'Для кеша', 'inventory_number' => 'INV-777', 'category_id' => 1], $manager);
    $newId = $created['json']['id'] ?? 0;
    
    $report = call('GET', '/api/equipment/summary', null, $manager);
    check('после POST отчёт пересчитан (MISS)', $hit($report) === 'MISS');

    // --- Удаление и проверка, что карточка не «залипла»
    expectStatus('DELETE оборудования', 204, call('DELETE', "/api/equipment/$newId", null, $manager));
    $gone = call('GET', "/api/equipment/$newId", null, $manager);
    expectStatus('после DELETE карточка не «залипла» в кеше', 404, $gone);
}

// ---------------------------------------------------------------------
section('7. Свои проверки кеша');
// ---------------------------------------------------------------------
if ($redis !== null) {
    // 1. Проверка обхода кеша (BYPASS)
    $bypass = call('GET', '/api/equipment', null, $manager, null, ['X-Cache-Mode: off']);
    check('заголовок X-Cache-Mode: off обходит кеш (BYPASS)', ($bypass['headers']['x-cache'] ?? '-') === 'BYPASS');

    // 2. Проверка, что разные ID дают разные ключи
    $item2 = call('GET', '/api/equipment/2', null, $manager);
    check('карточка ID=2 имеет свой ключ и статус MISS', ($item2['headers']['x-cache'] ?? '-') === 'MISS');
    
    // 3. Проверка, что список выдается из кеша
    $list1 = call('GET', '/api/equipment', null, $manager);
    $list2 = call('GET', '/api/equipment', null, $manager);
    check('список отдается из кеша (HIT)', ($list1['headers']['x-cache'] ?? '') === 'MISS' && ($list2['headers']['x-cache'] ?? '') === 'HIT');
}

// ---------------------------------------------------------------------
echo "\nИтого: $passed прошло, $failed не прошло\n";
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/bin/reset-db.php') . ' > ' . $devNull);
exit($failed === 0 ? 0 : 1);