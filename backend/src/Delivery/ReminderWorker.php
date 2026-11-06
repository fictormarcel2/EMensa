<?php

declare(strict_types=1);

namespace MensaTogether\Delivery;

use mysqli;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;
use Closure;

final class ReminderWorker
{
    public function __construct(private mysqli $db, private ?Closure $transport = null, private bool $manageTransactions = true, private ?int $onlyPlanId = null)
    {
    }

    public function run(): array
    {
        $counts = ['accepted' => 0, 'cancelled' => 0, 'failed' => 0];
        if ($this->transport === null && !(new ReminderService($this->db))->configuration()['supported']) {
            return $counts + ['configured' => false];
        }
        // One database-wide sender prevents duplicate dispatch from overlapping workers.
        if ((int) $this->db->query("SELECT GET_LOCK('mensatogether-reminders',0) AS acquired")->fetch_assoc()['acquired'] !== 1) {
            return $counts + ['busy' => true];
        }
        try {
            $push = $this->transport === null ? new WebPush(['VAPID' => [
                'subject' => getenv('VAPID_SUBJECT') ?: 'mailto:admin@example.invalid',
                'publicKey' => getenv('VAPID_PUBLIC_KEY'), 'privateKey' => getenv('VAPID_PRIVATE_KEY'),
            ]], ['urgency' => 'normal'], 8, ['allow_redirects' => false]) : null;
            $filter = $this->onlyPlanId === null ? '' : ' AND plan_id=' . $this->onlyPlanId;
            $ids = $this->db->query("SELECT id FROM reminder_jobs WHERE status='pending' AND due_at<=UTC_TIMESTAMP()" . $filter . ' ORDER BY due_at LIMIT 50');
            foreach ($ids as $row) {
                if ($this->manageTransactions) {
                    $this->db->begin_transaction();
                }
                try {
                    // Hold the plan lock until dispatch finishes so edit/cancel cannot race the final stale check.
                    $job = $this->db->execute_query('SELECT j.id,j.status AS job_status,j.plan_version,j.attempts,p.version,p.status,p.reminder,p.starts_at,s.active,s.id AS subscription_id,s.endpoint,s.p256dh,s.auth FROM reminder_jobs j JOIN lunch_plans p ON p.id=j.plan_id JOIN push_subscriptions s ON s.id=j.subscription_id WHERE j.id=? FOR UPDATE', [$row['id']])->fetch_assoc();
                    if (!$job || $job['job_status'] !== 'pending') {
                        if ($this->manageTransactions) {
                            $this->db->commit();
                        }
                        continue;
                    }
                    if (!$job || !PushPolicy::current($job, time())) {
                        $this->db->execute_query("UPDATE reminder_jobs SET status='cancelled' WHERE id=?", [$row['id']]);
                        $counts['cancelled']++;
                    } else {
                        $payload = PushPolicy::validate(['endpoint' => $job['endpoint'], 'keys' => ['p256dh' => $job['p256dh'], 'auth' => $job['auth']]]);
                        $ttl = max(1, min(900, strtotime($job['starts_at'] . ' UTC') - time()));
                        $message = json_encode([
                            'title' => 'Lunch is coming up', 'body' => 'Your lunch plan starts soon. Open your current plan for details.',
                            'tag' => 'lunch-reminder', 'url' => '/?view=plan',
                        ], JSON_THROW_ON_ERROR);
                        if ($this->transport !== null) {
                            $outcome = ($this->transport)($payload, $message, $ttl);
                        } else {
                            $report = $push->sendOneNotification(Subscription::create($payload), $message, ['TTL' => $ttl, 'topic' => 'lunch-reminder']);
                            $outcome = ['success' => $report->isSuccess(), 'expired' => $report->isSubscriptionExpired()];
                        }
                        if ($outcome['success']) {
                            $this->db->execute_query("UPDATE reminder_jobs SET status='sent',attempts=attempts+1,sent_at=UTC_TIMESTAMP(),last_error=NULL WHERE id=?", [$row['id']]);
                            $counts['accepted']++;
                        } else {
                            if ($outcome['expired']) {
                                $this->db->execute_query('UPDATE push_subscriptions SET active=0 WHERE id=?', [$job['subscription_id']]);
                            }
                            $status = $outcome['expired'] || (int) $job['attempts'] >= 2 ? 'failed' : 'pending';
                            $this->db->execute_query('UPDATE reminder_jobs SET status=?,attempts=attempts+1,last_error=?,due_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 SECOND) WHERE id=?', [$status, 'Push service rejected delivery', $row['id']]);
                            $counts['failed']++;
                        }
                    }
                    if ($this->manageTransactions) {
                        $this->db->commit();
                    }
                } catch (Throwable) {
                    if ($this->manageTransactions) {
                        $this->db->rollback();
                    }
                    $this->db->execute_query("UPDATE reminder_jobs SET status='failed',attempts=attempts+1,last_error='Dispatch failed' WHERE id=?", [$row['id']]);
                    $counts['failed']++;
                }
            }
        } finally {
            $this->db->query("SELECT RELEASE_LOCK('mensatogether-reminders')");
        }
        return $counts + ['configured' => true];
    }
}
