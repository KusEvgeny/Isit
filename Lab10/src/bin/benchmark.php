<?php
// bin/benchmark.php — сравнение скорости API без кеша и с кешем.

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

// ---------------------------------------------------------------------
// Настройки
// ---------------------------------------------------------------------
$options = getopt('', ['url::', 'n::', 'c::', 'd::']);
$base = rtrim((string) ($options['url'] ?? 'http://localhost:8000'), '/');
$n = max(5, (int) ($options['n'] ?? 40));
$c = max(1, (int) ($options['c'] ?? 8));
$d = max(1, (int) ($options['d'] ?? 3));

// Сценарии: что именно измеряем. Каждый — название и адрес.
$scenarios = [
    ['Список оборудования',          '/api/equipment'],
    ['Карточка оборудования',        '/api/equipment/250000'],
    ['Отчёт по статусам',            '/api/equipment/summary'],
    
    // TODO 5.1  Свои сценарии измерений
    ['Оборудование в ремонте',       '/api/equipment?status=in_repair'],
    ['Оборудование в архиве',        '/api/equipment?status=archived'],
];

// ---------------------------------------------------------------------
// Инструменты (ГОТОВО)
// ---------------------------------------------------------------------
function request(string $url, ?string $token, bool $bypass = false): array
{
    $headers = ['Accept: application/json'];
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if ($bypass) {
        $headers[] = 'X-Cache-Mode: off';
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 60]);
    $start = hrtime(true);
    $response = curl_exec($ch);
    $ms = (hrtime(true) - $start) / 1e6;
    if ($response === false) {
        fwrite(STDERR, "Не удалось подключиться к $url. Запущен ли сервер?\n");
        exit(2);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $head = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
    curl_close($ch);
    preg_match('/^x-cache:\s*(\S+)/mi', $head, $m);
    preg_match('/^x-total-count:\s*(\d+)/mi', $head, $t);
    return ['ms' => $ms, 'status' => $status, 'cache' => $m[1] ?? '-', 'total' => isset($t[1]) ? (int) $t[1] : null,
            'body' => substr($response, strlen($head))];
}

function sequential(string $url, ?string $token, int $count, bool $bypass): array
{
    $times = $caches = [];
    for ($i = 0; $i < $count; $i++) {
        $r = request($url, $token, $bypass);
        if ($r['status'] !== 200) {
            fwrite(STDERR, "Запрос $url вернул код {$r['status']}\n" . substr($r['body'], 0, 300) . "\n");
            exit(2);
        }
        $times[] = $r['ms'];
        $caches[] = $r['cache'];
    }
    return [$times, $caches];
}

function parallel(string $url, ?string $token, float $seconds, int $concurrency, bool $bypass): float
{
    $headers = ['Accept: application/json', 'Authorization: Bearer ' . $token] + ($bypass ? [2 => 'X-Cache-Mode: off'] : []);
    $multi = curl_multi_init();
    $add = function () use ($url, $headers, $multi): void {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 120]);
        curl_multi_add_handle($multi, $ch);
    };
    $done = 0;
    $inFlight = 0;
    $start = hrtime(true);
    $deadline = $start + (int) ($seconds * 1e9);
    for ($i = 0; $i < $concurrency; $i++) {
        $add();
        $inFlight++;
    }
    while ($inFlight > 0) {
        curl_multi_exec($multi, $running);
        curl_multi_select($multi, 0.05);
        while ($info = curl_multi_info_read($multi)) {
            curl_multi_remove_handle($multi, $info['handle']);
            curl_close($info['handle']);
            $done++;
            $inFlight--;
            if (hrtime(true) < $deadline) {
                $add();
                $inFlight++;
            }
        }
    }
    $elapsed = (hrtime(true) - $start) / 1e9;
    curl_multi_close($multi);
    return $done / $elapsed;
}

function percentile(array $values, float $p): float
{
    sort($values);
    return $values[(int) min(count($values) - 1, floor($p * count($values)))];
}
function avg(array $values): float
{
    return array_sum($values) / max(1, count($values));
}
function fmt(float $ms): string
{
    return $ms >= 10 ? number_format($ms, 0, ',', '') : number_format($ms, 1, ',', '');
}

// ---------------------------------------------------------------------
// Измерения
// ---------------------------------------------------------------------
$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => json_encode(['email' => 'anna@example.com', 'password' => '123456']),
        'ignore_errors' => true
    ]
];
$context = stream_context_create($opts);
$response = @file_get_contents($base . '/api/auth/login', false, $context);
$login = json_decode($response, true);
$token = $login['token'] ?? null;

if ($token === null) {
    fwrite(STDERR, "Не удалось войти. Запущен ли сервер ($base) и загружена ли база (php bin/reset-db.php)?\n");
    exit(2);
}

$check = request($base . '/api/equipment', $token);
$total = $check['total'] ?? 0;
echo "Сервер: $base. Оборудования в базе: $total. Запросов на сценарий: $n, нагрузка: $c одновременно в течение $d с.\n";
if ($total < 100000) {
    echo "ВНИМАНИЕ: записей меньше 100 000, различия будут почти незаметны. Загрузите данные: php bin/load-bigdata.php\n";
}
if (App\Core\Cache::redis() === null) {
    echo "ВНИМАНИЕ: Redis недоступен для этого скрипта.\n";
}
echo "\n";

$rows = [];
foreach ($scenarios as [$name, $path]) {
    $url = $base . $path;
    echo "измеряю: $name ...\n";

    sequential($url, $token, 3, true);

    [$plain, ] = sequential($url, $token, $n, true);

    App\Core\Cache::flushApp();
    [$cached, $caches] = sequential($url, $token, $n, false);
    $cold = $cached[0];
    $warm = array_slice($cached, 1);
    $hits = count(array_filter(array_slice($caches, 1), fn(string $s): bool => $s === 'HIT'));

    $rpsPlain = parallel($url, $token, $d, $c, true);
    $rpsCached = parallel($url, $token, $d, $c, false);

    $rows[] = [
        'name' => $name, 'plain_avg' => avg($plain), 'plain_p95' => percentile($plain, 0.95),
        'cold' => $cold, 'warm_avg' => avg($warm), 'warm_p95' => percentile($warm, 0.95),
        'speedup' => avg($plain) / max(0.001, avg($warm)), 'rps_plain' => $rpsPlain, 'rps_cached' => $rpsCached,
        'hits' => $hits, 'warm_count' => count($warm), 'first' => $caches[0],
    ];
}

echo "\n| Сценарий | Без кеша, среднее (p95), мс | С кешем: первый запрос, мс | С кешем, среднее (p95), мс | Ускорение | Запросов/с без кеша | Запросов/с с кешем |\n";
echo "|----------|----------------------------|----------------------------|----------------------------|----------:|--------------------:|-------------------:|\n";
foreach ($rows as $r) {
    printf("| %s | %s (%s) | %s | %s (%s) | ×%s | %s | %s |\n",
        $r['name'], fmt($r['plain_avg']), fmt($r['plain_p95']), fmt($r['cold']), fmt($r['warm_avg']), fmt($r['warm_p95']),
        number_format($r['speedup'], 1, ',', ''), number_format($r['rps_plain'], 0, ',', ''), number_format($r['rps_cached'], 0, ',', ''));
}
echo "\nПроверка кеша: ";
$bad = array_filter($rows, fn(array $r): bool => $r['hits'] < $r['warm_count'] || $r['first'] !== 'MISS');
if ($bad) {
    echo "у сценариев «" . implode('», «', array_column($bad, 'name')) . "» не все повторные запросы дали HIT.\n";
} else {
    echo "у всех сценариев первый запрос — MISS, остальные — HIT.\n";
}