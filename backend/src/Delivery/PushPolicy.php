<?php

declare(strict_types=1);

namespace MensaTogether\Delivery;

use InvalidArgumentException;

final class PushPolicy
{
    public static function validate(array $payload): array
    {
        $endpoint = $payload['endpoint'] ?? null;
        if (!is_string($endpoint) || strlen($endpoint) > 2048 || preg_match('/[\x00-\x20\x7f]/', $endpoint)) {
            throw new InvalidArgumentException('Invalid push endpoint.');
        }
        $url = parse_url($endpoint);
        $host = strtolower($url['host'] ?? '');
        $allowed = $host === 'fcm.googleapis.com' || $host === 'updates.push.services.mozilla.com'
            || $host === 'web.push.apple.com' || str_ends_with($host, '.notify.windows.com');
        if (!$allowed || ($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass'])
            || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)) {
            throw new InvalidArgumentException('Unsupported push provider.');
        }
        $keys = $payload['keys'] ?? [];
        if (!is_array($keys)) {
            throw new InvalidArgumentException('Invalid push encryption keys.');
        }
        foreach (['p256dh' => 65, 'auth' => 16] as $key => $size) {
            $value = $keys[$key] ?? null;
            if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $value)) {
                throw new InvalidArgumentException('Invalid push encryption key.');
            }
            $decoded = base64_decode(strtr($value, '-_', '+/'), true);
            if ($decoded === false || strlen($decoded) !== $size || ($key === 'p256dh' && ord($decoded[0]) !== 4)) {
                throw new InvalidArgumentException('Invalid push encryption key.');
            }
        }
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => $keys['p256dh'], 'auth' => $keys['auth']]];
    }

    public static function current(array $job, int $now): bool
    {
        return $job['status'] === 'confirmed' && (bool) $job['reminder'] && (bool) $job['active']
            && (int) $job['version'] === (int) $job['plan_version']
            && strtotime($job['starts_at'] . ' UTC') > $now;
    }
}
