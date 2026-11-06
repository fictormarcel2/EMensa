<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if (in_array($path, ['/health','/api/health'], true)) {
        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            echo json_encode(['error' => ['code' => 'method_not_allowed','message' => 'Use GET for health checks.']]);
            return;
        }
        \MensaTogether\Infrastructure\Database::connect()->query('SELECT 1');
        echo json_encode(['status' => 'ok','service' => 'mensatogether-backend','database' => 'connected']);
        return;
    }
    session_name('mensatogether');
    session_set_cookie_params(['httponly' => true,'samesite' => 'Lax','secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
    session_start(['use_strict_mode' => true]);
    $raw = file_get_contents('php://input');
    if (strlen($raw) > 16384) {
        http_response_code(413);
        echo json_encode(['error' => ['code' => 'too_large','message' => 'Request is too large.']]);
        return;
    }
    $body = $raw === '' ? [] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($body) || (array_is_list($body) && $body !== [])) {
        throw new InvalidArgumentException('Use a JSON object.');
    }
    echo json_encode((new \MensaTogether\Api())->handle((string)$path, $method, $body), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException|JsonException $e) {
    http_response_code(422);
    echo json_encode(['error' => ['code' => 'invalid_input','message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Invalid JSON request.']]);
} catch (RuntimeException $e) {
    if (str_starts_with($e->getMessage(), '{"error":')) {
        echo $e->getMessage();
    } else {
        http_response_code(503);
        echo json_encode(in_array($path, ['/health','/api/health'], true) ? ['status' => 'unavailable','service' => 'mensatogether-backend'] : ['error' => ['code' => 'unavailable','message' => 'Service temporarily unavailable. Please retry.']]);
    }
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(in_array($path, ['/health','/api/health'], true) ? ['status' => 'unavailable','service' => 'mensatogether-backend'] : ['error' => ['code' => 'unavailable','message' => 'Service temporarily unavailable. Please retry.']]);
}
