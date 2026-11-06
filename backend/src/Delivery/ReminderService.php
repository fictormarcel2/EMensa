<?php

declare(strict_types=1);

namespace MensaTogether\Delivery;

use mysqli;
use RuntimeException;

final class ReminderService
{
    public function __construct(private mysqli $db)
    {
    }

    public function configuration(): array
    {
        $key = getenv('VAPID_PUBLIC_KEY') ?: null;
        return ['supported' => $key !== null && (bool) getenv('VAPID_PRIVATE_KEY'), 'publicKey' => $key];
    }

    public function subscribe(int $userId, array $payload): array
    {
        if (!$this->configuration()['supported']) {
            throw new RuntimeException('Push delivery has not been configured.');
        }
        $payload = PushPolicy::validate($payload);
        $endpoint = $payload['endpoint'];
        $hash = hash('sha256', $endpoint);
        $existing = $this->db->execute_query('SELECT user_id FROM push_subscriptions WHERE endpoint_hash=?', [$hash])->fetch_assoc();
        if ($existing && (int) $existing['user_id'] !== $userId) {
            throw new RuntimeException('This browser subscription belongs to another account. Create a fresh browser subscription before switching accounts.');
        }
        $this->db->execute_query(
            'INSERT INTO push_subscriptions(user_id,endpoint,endpoint_hash,p256dh,auth) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE p256dh=VALUES(p256dh),auth=VALUES(auth),active=1',
            [$userId, $endpoint, $hash, $payload['keys']['p256dh'], $payload['keys']['auth']]
        );
        $plans = $this->db->execute_query("SELECT id FROM lunch_plans WHERE user_id=? AND starts_at>UTC_TIMESTAMP()", [$userId]);
        foreach ($plans as $plan) {
            $this->syncPlan((int) $plan['id']);
        }
        return ['subscribed' => true];
    }

    public function unsubscribe(int $userId, string $endpoint): array
    {
        $this->db->execute_query('UPDATE push_subscriptions SET active=0 WHERE user_id=? AND endpoint_hash=?', [$userId, hash('sha256', $endpoint)]);
        $this->db->execute_query("UPDATE reminder_jobs j JOIN push_subscriptions s ON s.id=j.subscription_id SET j.status='cancelled' WHERE s.user_id=? AND s.active=0 AND j.status='pending'", [$userId]);
        return ['subscribed' => false];
    }

    public function syncPlan(int $planId): void
    {
        $this->db->execute_query("UPDATE reminder_jobs j JOIN lunch_plans p ON p.id=j.plan_id SET j.status='cancelled' WHERE p.id=? AND j.status='pending' AND (j.plan_version<>p.version OR p.status<>'confirmed' OR p.reminder=0)", [$planId]);
        $this->db->execute_query("INSERT INTO reminder_jobs(plan_id,plan_version,subscription_id,due_at)
            SELECT p.id,p.version,s.id,DATE_SUB(p.starts_at,INTERVAL 15 MINUTE) FROM lunch_plans p
            JOIN push_subscriptions s ON s.user_id=p.user_id AND s.active=1
            WHERE p.id=? AND p.status='confirmed' AND p.reminder=1 AND DATE_SUB(p.starts_at,INTERVAL 15 MINUTE)>UTC_TIMESTAMP()
            ON DUPLICATE KEY UPDATE status=IF(reminder_jobs.status='cancelled','pending',reminder_jobs.status)", [$planId]);
    }
}
