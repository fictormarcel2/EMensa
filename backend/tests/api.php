<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use MensaTogether\Infrastructure\Database;
use MensaTogether\Domain\SlotPolicy;

$db = Database::connect();
$ids = [];
$checks = 0;
function assertApi(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    } $checks++;
    echo "PASS $message\n";
}
final class Client
{
    public string $cookie = '';
    public string $csrf = '';
    public function request(string $path, string $method = 'GET', ?array $body = null, bool $token = true): array
    {
        $headers = ['Content-Type: application/json','Cookie: '.$this->cookie];
        if ($token) {
            $headers[] = 'X-CSRF-Token: '.$this->csrf;
        }
        $context = stream_context_create(['http' => ['method' => $method,'header' => implode("\r\n", $headers),'content' => $body === null ? '' : json_encode($body),'ignore_errors' => true,'timeout' => 45]]);
        $raw = file_get_contents('http://127.0.0.1:8000'.$path, false, $context);
        $code = 0;
        foreach ($http_response_header as $line) {
            if (preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) {
                $code = (int)$m[1];
            } if (preg_match('/^Set-Cookie: ([^;]+)/i', $line, $m)) {
                $this->cookie = $m[1];
            }
        }
        $json = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (isset($json['csrfToken'])) {
            $this->csrf = $json['csrfToken'];
        } return [$code,$json];
    }
}
try {
    $suffix = bin2hex(random_bytes(6));
    $emails = [];
    foreach (['Owner','Friend'] as $name) {
        $email = strtolower($name)."-$suffix@example.test";
        $emails[] = $email;
        $db->execute_query('INSERT INTO users(alias,email,password_hash) VALUES(?,?,?)', ["Test $name $suffix",$email,password_hash('Test!Password19', PASSWORD_DEFAULT)]);
        $ids[] = $db->insert_id;
    }
    $a = new Client();
    $b = new Client();
    $guest = new Client();
    [$status,$boot] = $a->request('/api/bootstrap');
    assertApi($status === 200 && $boot['user'] === null && strlen($a->csrf) > 20, 'Guest bootstrap and CSRF');
    $guestSession = $a->cookie;
    [$status] = $a->request('/api/auth/login', 'POST', ['email' => $emails[0],'password' => 'Test!Password19'], false);
    assertApi($status === 403, 'CSRF enforced on login');
    [$status,$login] = $a->request('/api/auth/login', 'POST', ['email' => $emails[0],'password' => 'Test!Password19']);
    assertApi($status === 200 && $login['user']['alias'] === "Test Owner $suffix", 'Session login');
    assertApi($a->cookie !== $guestSession, 'Login rotates the session identifier');
    $b->request('/api/bootstrap');
    $b->request('/api/auth/login', 'POST', ['email' => $emails[1],'password' => 'Test!Password19']);
    [$status] = $guest->request('/api/plans');
    assertApi($status === 401, 'Guest owner plan denied');
    $policy = new SlotPolicy();
    $day = new DateTimeImmutable('tomorrow', new DateTimeZone('Europe/Berlin'));
    while (!$policy->slots('eupener', $day->format('Y-m-d'))) {
        $day = $day->modify('+1 day');
    } $date = $day->format('Y-m-d');
    $base = ['date' => $date,'mensaId' => 'eupener','slotId' => '11:15','meetingPointId' => 'eupener-entrance','visibility' => 'private','reminder' => false,'version' => 0,'requestId' => bin2hex(random_bytes(12))];
    [$status] = $a->request('/api/plans', 'PUT', [...$base,'slotId' => ['11:15','11:30']]);
    assertApi($status === 422, 'Reject multiple scalar slots');
    [$status] = $a->request('/api/plans', 'PUT', [...$base,'slotId' => '14:15']);
    assertApi($status === 422, 'Reject service break');
    [$status] = $a->request('/api/plans', 'PUT', [...$base, 'meetingPointId' => 'bayernallee-entrance']);
    assertApi($status === 422, 'Reject meeting point from another mensa');
    [$status,$saved] = $a->request('/api/plans', 'PUT', $base);
    assertApi($status === 200 && $saved['plan']['version'] === 1, 'Persist one plan');
    [$status,$retry] = $a->request('/api/plans', 'PUT', $base);
    assertApi($status === 200 && $retry === $saved, 'Idempotent retry');
    [$status] = $a->request('/api/plans', 'PUT', [...$base, 'slotId' => '11:30']);
    assertApi($status === 409, 'Retry ID cannot be reused for another intent');
    $storedStart = $db->execute_query('SELECT starts_at FROM lunch_plans WHERE user_id=?', [$ids[0]])->fetch_assoc()['starts_at'];
    $expectedUtc = (new DateTimeImmutable($date.' 11:15', new DateTimeZone('Europe/Berlin')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    assertApi($storedStart === $expectedUtc, 'Berlin arrival stored as UTC');
    $count = (int)$db->execute_query('SELECT COUNT(*) AS n FROM lunch_plans WHERE user_id=? AND date=?', [$ids[0],$date])->fetch_assoc()['n'];
    assertApi($count === 1, 'Unique daily plan row');
    [, $overview] = $guest->request('/api/overview?mensaId=eupener&date='.$date);
    $slot = array_values(array_filter($overview['slots'], fn ($s) => $s['id'] === '11:15'))[0];
    assertApi($slot['count'] >= 1 && $slot['friends'] === [] && !str_contains(json_encode($overview), "Test Owner $suffix"), 'Private submission counts without identity');
    [$status] = $a->request('/api/plans', 'PUT', [...$base,'slotId' => '11:30','requestId' => bin2hex(random_bytes(12))]);
    assertApi($status === 409, 'Stale tab conflict');
    [, $other] = $b->request('/api/plans?date='.$date);
    assertApi($other['plan'] === null, 'Other account cannot read owner plan');
    [, $invite] = $a->request('/api/invites', 'POST', []);
    [, $preview] = $b->request('/api/invites?code='.$invite['code']);
    assertApi($preview === ['alias' => "Test Owner $suffix"], 'Invite preview contains alias only');
    [$status] = $a->request('/api/friends', 'POST', ['code' => $invite['code']]);
    assertApi($status === 422, 'Self invitation rejected');
    [$status] = $b->request('/api/friends', 'POST', ['code' => $invite['code']]);
    assertApi($status === 200, 'Friend request sent');
    [, $pending] = $a->request('/api/friends?date='.$date);
    assertApi(count($pending['requests']) === 1 && $pending['friends'] === [], 'Request awaits acceptance');
    $a->request('/api/friends/accept', 'POST', ['id' => $ids[1]]);
    [, $friend] = $b->request('/api/friends?date='.$date);
    assertApi($friend['friends'][0]['sharedPlan'] === null, 'Accepted friend cannot see private existence');
    [, $duplicateInvite] = $a->request('/api/invites', 'POST', []);
    [$status] = $b->request('/api/friends', 'POST', ['code' => $duplicateInvite['code']]);
    assertApi($status === 409, 'Duplicate friendship request rejected');
    $edit = [...$base,'visibility' => 'friends','slotId' => '11:30','version' => 1,'requestId' => bin2hex(random_bytes(12))];
    [$status,$updated] = $a->request('/api/plans', 'PUT', $edit);
    assertApi($status === 200 && $updated['plan']['version'] === 2, 'Edit transfers single plan');
    [, $friend] = $b->request('/api/friends?date='.$date);
    assertApi($friend['friends'][0]['sharedPlan']['slotId'] === '11:30', 'Consenting accepted friend sees time');
    [, $privateOverview] = $guest->request('/api/overview?mensaId=eupener&date='.$date);
    assertApi(!str_contains(json_encode($privateOverview), "Test Owner $suffix"), 'Friends audience does not become public');
    [$status] = $a->request('/api/plans', 'PUT', [...$edit, 'visibility' => 'public', 'version' => 2, 'requestId' => bin2hex(random_bytes(12))]);
    [, $publicOverview] = $guest->request('/api/overview?mensaId=eupener&date='.$date);
    $publicSlot = array_values(array_filter($publicOverview['slots'], fn ($s) => $s['id'] === '11:30'))[0];
    assertApi($status === 200 && in_array(['alias' => "Test Owner $suffix"], $publicSlot['publicParticipants'], true), 'Explicit public audience shares alias only');
    $a->request('/api/plans', 'PUT', [...$edit, 'visibility' => 'private', 'version' => 3, 'requestId' => bin2hex(random_bytes(12))]);
    [, $revoked] = $b->request('/api/friends?date='.$date);
    assertApi($revoked['friends'][0]['sharedPlan'] === null, 'Visibility revocation removes previously shared details');
    $b->request('/api/friends', 'DELETE', ['id' => $ids[0]]);
    [, $removed] = $b->request('/api/overview?mensaId=eupener&date='.$date);
    assertApi(array_sum(array_map(fn ($s) => count($s['friends']), $removed['slots'])) === 0, 'Revocation removes friend badges');
    [, $code] = $a->request('/api/invites', 'POST', []);
    $a->request('/api/invites', 'DELETE', ['code' => $code['code']]);
    [$status] = $b->request('/api/invites?code='.$code['code']);
    assertApi($status === 422, 'Revoked code rejected');
    [, $code] = $a->request('/api/invites', 'POST', []);
    $db->execute_query('UPDATE friend_invites SET expires_at=UTC_TIMESTAMP()-INTERVAL 1 SECOND WHERE token_hash=?', [hash('sha256', $code['code'])]);
    [$status] = $b->request('/api/invites?code='.$code['code']);
    assertApi($status === 422, 'Expired code rejected');
    [$status,$cancelled] = $a->request('/api/plans', 'DELETE', ['date' => $date,'version' => 4,'requestId' => bin2hex(random_bytes(12))]);
    assertApi($status === 200 && $cancelled['plan']['status'] === 'cancelled', 'Owner cancellation persists');
    // A historical confirmed submission must remain in aggregate counts and become read-only.
    $db->execute_query("UPDATE lunch_plans SET status='confirmed',visibility='public',starts_at=UTC_TIMESTAMP()-INTERVAL 20 MINUTE,ends_at=UTC_TIMESTAMP()-INTERVAL 5 MINUTE WHERE user_id=?", [$ids[0]]);
    $db->execute_query("UPDATE friendships SET status='accepted' WHERE user_low=? AND user_high=?", [min($ids), max($ids)]);
    [, $expired] = $a->request('/api/plans?date='.$date);
    assertApi($expired['plan']['status'] === 'expired' && !$expired['plan']['editable'], 'Expired plan read-only');
    [$status] = $a->request('/api/plans', 'DELETE', ['date' => $date,'version' => 5,'requestId' => bin2hex(random_bytes(12))]);
    assertApi($status === 409, 'Cannot mutate started plan');
    [, $historical] = $guest->request('/api/overview?mensaId=eupener&date='.$date);
    assertApi(array_values(array_filter($historical['slots'], fn ($s) => $s['id'] === '11:30'))[0]['count'] >= 1, 'Expiry preserves aggregate count');
    [, $expiredView] = $b->request('/api/overview?mensaId=eupener&date='.$date);
    assertApi(!str_contains(json_encode($expiredView), "Test Owner $suffix"), 'Expiry removes friend badges and public participants');
    echo "API behavior checks passed: $checks.\n";
} finally {
    foreach ($ids as $id) {
        $db->execute_query('DELETE j FROM reminder_jobs j JOIN lunch_plans p ON p.id=j.plan_id WHERE p.user_id=?',[$id]);
        $db->execute_query('DELETE FROM push_subscriptions WHERE user_id=?',[$id]);
        $db->execute_query('DELETE FROM mutation_requests WHERE user_id=?',[$id]);
        $db->execute_query('DELETE FROM friendships WHERE user_low=? OR user_high=?',[$id,$id]);
        $db->execute_query('DELETE FROM friend_invites WHERE user_id=?',[$id]);
        $db->execute_query('DELETE FROM lunch_plans WHERE user_id=?',[$id]);
        $db->execute_query('DELETE FROM users WHERE id=?',[$id]);
    }
}
