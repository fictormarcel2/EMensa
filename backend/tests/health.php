<?php

declare(strict_types=1);

$base = rtrim(getenv('SMOKE_BASE_URL') ?: 'http://127.0.0.1:8000', '/');
$cases = [
    ['GET', '/health', 200],
    ['GET', '/api/health', 200],
    ['POST', '/health', 405],
    ['GET', '/anmeldung', 404],
    ['GET', '/demo', 404],
    ['GET', '/passwort.php', 404],
];

foreach ($cases as [$method, $path, $expected]) {
    $context = stream_context_create(['http' => [
        'method' => $method,
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 10,
    ]]);
    $body = @file_get_contents($base . $path, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('/^HTTP\/\S+\s+(\d+)/', $headers[0] ?? '', $match);
    $status = (int) ($match[1] ?? 0);
    $data = json_decode($body === false ? '' : $body, true);
    if ($status !== $expected || !is_array($data)) {
        fwrite(STDERR, "FAIL: {$method} {$path}, expected JSON/{$expected}, got {$status}\n");
        exit(1);
    }
    if ($expected === 200 && (($data['status'] ?? '') !== 'ok' || ($data['database'] ?? '') !== 'connected')) {
        fwrite(STDERR, "FAIL: database readiness was not confirmed.\n");
        exit(1);
    }
    echo "PASS: {$method} {$path} -> {$status}\n";
}
echo "Backend foundation smoke checks passed.\n";
