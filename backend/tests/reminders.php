<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use MensaTogether\Delivery\PushPolicy;

$passed = 0;
$check = static function (bool $result, string $label) use (&$passed): void {
    if (!$result) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
    $passed++;
};
$encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
$payload = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/example', 'keys' => [
    'p256dh' => $encode(chr(4) . str_repeat('a', 64)), 'auth' => $encode(str_repeat('b', 16)),
]];
$check(PushPolicy::validate($payload) === $payload, 'Browser-format subscription accepted');
foreach (['http://fcm.googleapis.com/send', 'https://127.0.0.1/', 'https://fcm.googleapis.com.attacker.test/', 'https://fcm.googleapis.com:8443/', 'https://user@fcm.googleapis.com/', 'https://notify.windows.com.attacker.test/'] as $endpoint) {
    $rejected = false;
    try {
        PushPolicy::validate(array_replace($payload, ['endpoint' => $endpoint]));
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    $check($rejected, 'Reject non-provider, insecure or credential-bearing endpoint');
}
$invalid = $payload;
$invalid['keys']['auth'] = $encode('short');
try {
    PushPolicy::validate($invalid);
    $check(false, 'Reject invalid key length');
} catch (InvalidArgumentException) {
    $check(true, 'Reject invalid key length');
}
$now = time();
$job = ['status' => 'confirmed', 'reminder' => 1, 'active' => 1, 'version' => 2, 'plan_version' => 2, 'starts_at' => gmdate('Y-m-d H:i:s', $now + 900)];
$check(PushPolicy::current($job, $now), 'Current version dispatches');
foreach ([['status' => 'cancelled'], ['reminder' => 0], ['active' => 0], ['version' => 3], ['starts_at' => gmdate('Y-m-d H:i:s', $now - 1)]] as $change) {
    $check(!PushPolicy::current(array_replace($job, $change), $now), 'Cancel, opt-out, revoked device, edit or late job suppresses dispatch');
}
echo "$passed reminder policy checks passed.\n";

if (in_array('--database', $argv, true)) {
    $db = MensaTogether\Infrastructure\Database::connect();
    $service = new MensaTogether\Delivery\ReminderService($db);
    $db->begin_transaction();
    try {
        $db->execute_query('INSERT INTO users(alias,email,password_hash) VALUES(?,?,?)', ['Reminder test', bin2hex(random_bytes(8)) . '@example.invalid', 'unused-test-hash']);
        $userId = $db->insert_id;
        $db->execute_query('INSERT INTO push_subscriptions(user_id,endpoint,endpoint_hash,p256dh,auth) VALUES(?,?,?,?,?)', [$userId, $payload['endpoint'], hash('sha256', $payload['endpoint']), $payload['keys']['p256dh'], $payload['keys']['auth']]);
        $db->execute_query("INSERT INTO lunch_plans(user_id,date,mensa_id,slot_id,reminder,status,version,starts_at,ends_at) VALUES(?,UTC_DATE(),'eupener','12:00',1,'confirmed',1,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 75 MINUTE))", [$userId]);
        $planId = $db->insert_id;
        $service->syncPlan($planId);
        $service->syncPlan($planId);
        $jobs = $db->execute_query('SELECT *,TIMESTAMPDIFF(MINUTE,due_at,(SELECT starts_at FROM lunch_plans WHERE id=?)) AS lead_minutes FROM reminder_jobs WHERE plan_id=?', [$planId, $planId])->fetch_all(MYSQLI_ASSOC);
        $check(count($jobs) === 1 && (int) $jobs[0]['lead_minutes'] === 15, 'Persistent T15 job is idempotent');
        $db->execute_query('UPDATE lunch_plans SET version=2,starts_at=DATE_ADD(starts_at,INTERVAL 15 MINUTE) WHERE id=?', [$planId]);
        $service->syncPlan($planId);
        $jobs = $db->execute_query('SELECT status,plan_version FROM reminder_jobs WHERE plan_id=? ORDER BY plan_version', [$planId])->fetch_all(MYSQLI_ASSOC);
        $check(count($jobs) === 2 && $jobs[0]['status'] === 'cancelled' && $jobs[1]['status'] === 'pending', 'Edit cancels previous version and schedules replacement');
        $db->execute_query("UPDATE lunch_plans SET version=3,status='cancelled' WHERE id=?", [$planId]);
        $service->syncPlan($planId);
        $remaining = $db->execute_query("SELECT COUNT(*) AS total FROM reminder_jobs WHERE plan_id=? AND status='pending'", [$planId])->fetch_assoc();
        $check((int) $remaining['total'] === 0, 'Cancellation invalidates all pending jobs');
        $db->execute_query("UPDATE lunch_plans SET version=4,status='confirmed' WHERE id=?", [$planId]);
        $service->syncPlan($planId);
        $service->unsubscribe($userId, $payload['endpoint']);
        $remaining = $db->execute_query("SELECT COUNT(*) AS total FROM reminder_jobs WHERE plan_id=? AND status='pending'", [$planId])->fetch_assoc();
        $check((int) $remaining['total'] === 0, 'Device unsubscribe invalidates pending jobs');
        $db->execute_query('UPDATE push_subscriptions SET active=1 WHERE user_id=?', [$userId]);
        $db->execute_query("UPDATE lunch_plans SET version=5,status='confirmed',starts_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE) WHERE id=?", [$planId]);
        $service->syncPlan($planId);
        $lateJobs = $db->execute_query('SELECT COUNT(*) AS total FROM reminder_jobs WHERE plan_id=? AND plan_version=5', [$planId])->fetch_assoc();
        $check((int) $lateJobs['total'] === 0, 'Save or edit at T10 never creates a retrospective T15 reminder');
        $oldPublicKey = getenv('VAPID_PUBLIC_KEY');
        $oldPrivateKey = getenv('VAPID_PRIVATE_KEY');
        try {
            putenv('VAPID_PUBLIC_KEY=test-configured');
            putenv('VAPID_PRIVATE_KEY=test-configured');
            $service->subscribe($userId, $payload);
            $lateJobs = $db->execute_query('SELECT COUNT(*) AS total FROM reminder_jobs WHERE plan_id=? AND plan_version=5', [$planId])->fetch_assoc();
            $check((int) $lateJobs['total'] === 0, 'Subscribing at T10 never backfills a retrospective reminder');
        } finally {
            putenv($oldPublicKey === false ? 'VAPID_PUBLIC_KEY' : 'VAPID_PUBLIC_KEY=' . $oldPublicKey);
            putenv($oldPrivateKey === false ? 'VAPID_PRIVATE_KEY' : 'VAPID_PRIVATE_KEY=' . $oldPrivateKey);
        }

        $db->execute_query('UPDATE lunch_plans SET starts_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR) WHERE id=?', [$planId]);
        // Exercise the real worker query/locking/state machine, with only the network boundary replaced.
        $db->execute_query('UPDATE push_subscriptions SET active=1 WHERE user_id=?', [$userId]);
        $db->execute_query('DELETE FROM reminder_jobs WHERE plan_id=?', [$planId]);
        $service->syncPlan($planId);
        $due = static function () use ($db, $planId): void {
            $db->execute_query('UPDATE reminder_jobs SET due_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE plan_id=?', [$planId]);
        };
        $read = static fn(): array => $db->execute_query('SELECT * FROM reminder_jobs WHERE plan_id=? ORDER BY id DESC LIMIT 1', [$planId])->fetch_assoc();
        $calls = 0;
        $success = new MensaTogether\Delivery\ReminderWorker($db, static function () use (&$calls): array {
            $calls++;
            return ['success' => true, 'expired' => false];
        }, false, $planId);
        $db->execute_query('UPDATE lunch_plans SET starts_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE) WHERE id=?', [$planId]);
        $service->syncPlan($planId);
        $due();
        $success->run();
        $success->run();
        $check($calls === 1 && $read()['status'] === 'sent' && (int) $read()['attempts'] === 1, 'Previously scheduled delayed job still dispatches once at T10');
        $db->execute_query('UPDATE lunch_plans SET starts_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR) WHERE id=?', [$planId]);
        $reset = static function () use ($db, $planId, $service, $due): void {
            $db->execute_query('DELETE FROM reminder_jobs WHERE plan_id=?', [$planId]);
            $db->execute_query("UPDATE lunch_plans SET status='confirmed',reminder=1 WHERE id=?", [$planId]);
            $db->execute_query('UPDATE push_subscriptions SET active=1 WHERE user_id=(SELECT user_id FROM lunch_plans WHERE id=?)', [$planId]);
            $service->syncPlan($planId);
            $due();
        };
        $reset();
        $failureCalls = 0;
        $failure = new MensaTogether\Delivery\ReminderWorker($db, static function () use (&$failureCalls): array {
            $failureCalls++;
            return ['success' => false, 'expired' => false];
        }, false, $planId);
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $due();
            $failure->run();
        }
        $check($failureCalls === 3 && $read()['status'] === 'failed' && (int) $read()['attempts'] === 3, 'Actual worker exhausts transient retries after three attempts');
        $reset();
        $expired = new MensaTogether\Delivery\ReminderWorker($db, static fn(): array => ['success' => false, 'expired' => true], false, $planId);
        $expired->run();
        $active = $db->execute_query('SELECT active FROM push_subscriptions WHERE user_id=?', [$userId])->fetch_assoc();
        $check($read()['status'] === 'failed' && !(bool) $active['active'], 'Actual worker deactivates expired 410 subscription');
        foreach (['version=version+1', "status='cancelled'"] as $mutation) {
            $reset();
            $db->execute_query('UPDATE lunch_plans SET ' . $mutation . ' WHERE id=?', [$planId]);
            $before = $calls;
            $success->run();
            $check($calls === $before && $read()['status'] === 'cancelled', 'Actual worker suppresses stale edit or cancelled plan without transport');
        }
    } finally {
        $db->rollback();
    }
    echo "Reminder database lifecycle passed; fixtures rolled back.\n";
}
