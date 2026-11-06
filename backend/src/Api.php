<?php

declare(strict_types=1);

namespace MensaTogether;

use MensaTogether\Domain\SlotPolicy;
use MensaTogether\Infrastructure\Database;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use mysqli;

final class Api
{
    private mysqli $db;
    private SlotPolicy $policy;
    public function __construct()
    {
        $this->db = Database::connect();
        $this->policy = new SlotPolicy();
    }
    private function rows(string $sql, array $args = []): array
    {
        return $this->db->execute_query($sql, $args)->fetch_all(MYSQLI_ASSOC);
    }
    private function fail(int $status, string $code, string $message): never
    {
        http_response_code($status);
        throw new RuntimeException(json_encode(['error' => ['code' => $code,'message' => $message]]));
    }
    private function owner(): int
    {
        return $_SESSION['user_id'] ?? $this->fail(401, 'login_required', 'Sign in with an evaluation account to continue.');
    }
    private function user(): ?array
    {
        return isset($_SESSION['user_id']) ? ($this->rows('SELECT id,alias FROM users WHERE id=?', [$_SESSION['user_id']])[0] ?? null) : null;
    }
    private function rate(string $action, int $limit): void
    {
        $bucket = hash('sha256', $action.($_SERVER['REMOTE_ADDR'] ?? '').($_SESSION['user_id'] ?? ''));
        $this->db->execute_query('INSERT INTO rate_limits(bucket,attempts,window_at) VALUES(?,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE attempts=IF(window_at < UTC_TIMESTAMP()-INTERVAL 10 MINUTE,1,attempts+1),window_at=IF(window_at < UTC_TIMESTAMP()-INTERVAL 10 MINUTE,UTC_TIMESTAMP(),window_at)', [$bucket]);
        if ((int)$this->rows('SELECT attempts FROM rate_limits WHERE bucket=?', [$bucket])[0]['attempts'] > $limit) {
            $this->fail(429, 'rate_limited', 'Too many attempts. Please try again in ten minutes.');
        }
    }
    public function handle(string $path, string $method, array $body): array
    {
        $path = match ($path) {
            '/api/push/config' => '/api/reminders/status',
            '/api/push/subscriptions' => '/api/reminders/subscription',
            default => $path,
        };
        $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
        $routes = ['/api/bootstrap' => ['GET'],'/api/auth/login' => ['POST'],'/api/auth/logout' => ['POST'],'/api/me' => ['GET'],'/api/overview' => ['GET'],'/api/plans' => ['GET','PUT','DELETE'],'/api/friends' => ['GET','POST','DELETE'],'/api/friends/accept' => ['POST'],'/api/invites' => ['GET','POST','DELETE'],'/api/reminders/subscription' => ['POST','DELETE'],'/api/reminders/status' => ['GET']];
        if (!isset($routes[$path])) {
            $this->fail(404, 'not_found', 'This endpoint does not exist.');
        }
        if (!in_array($method, $routes[$path], true)) {
            header('Allow: '.implode(', ', $routes[$path]));
            $this->fail(405, 'method_not_allowed', 'This method is not allowed.');
        }
        if ($method !== 'GET' && !hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            $this->fail(403, 'csrf', 'Refresh the page before trying again.');
        }
        $date = (string)($_GET['date'] ?? (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format('Y-m-d'));
        if ($path === '/api/bootstrap') {
            $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
            return ['csrfToken' => $_SESSION['csrf'],'user' => $this->user(),'mensas' => $this->policy->mensas(),'today' => $today,'demoAccounts' => array_map(fn ($s) => ['alias' => ucfirst($s),'email' => "$s@example.test",'password' => 'CampusLunch!2026'], ['alex','sam','jules'])];
        }
        if ($path === '/api/me') {
            return ['user' => $this->user(),'csrfToken' => $_SESSION['csrf']];
        }
        if ($path === '/api/auth/login') {
            $this->rate('login', 15);
            $account = $this->rows('SELECT * FROM users WHERE email=?', [(string)($body['email'] ?? '')])[0] ?? null;
            if (!$account || !password_verify((string)($body['password'] ?? ''), $account['password_hash'])) {
                $this->fail(401, 'invalid_credentials', 'Email or password is incorrect.');
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            return ['user' => $this->user(),'csrfToken' => $_SESSION['csrf']];
        }
        if ($path === '/api/auth/logout') {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            return ['user' => null,'csrfToken' => $_SESSION['csrf']];
        }
        if ($path === '/api/overview') {
            return $this->overview((string)($_GET['mensaId'] ?? 'eupener'), $date);
        }
        if ($path === '/api/plans') {
            $uid = $this->owner();
            if ($method === 'GET') {
                $this->policy->date($date);
                return ['plan' => $this->plan($uid, $date)];
            } return $this->mutatePlan($uid, $method, $body);
        }
        if ($path === '/api/friends' || $path === '/api/friends/accept') {
            return $this->friends($path, $method, $body, $date);
        }
        if ($path === '/api/invites') {
            return $this->invites($method, $body);
        }
        $uid = $this->owner();
        $service = new \MensaTogether\Delivery\ReminderService($this->db);
        if ($path === '/api/reminders/status') {
            return $service->configuration();
        }
        if ($method === 'DELETE') {
            $service->unsubscribe($uid, (string)($body['endpoint'] ?? ''));
            return ['success' => true];
        }
        return $service->subscribe($uid, $body);
    }
    private function plan(int $uid, string $date): ?array
    {
        $p = $this->rows('SELECT * FROM lunch_plans WHERE user_id=? AND date=?', [$uid,$date])[0] ?? null;
        if (!$p) {
            return null;
        }
        $started = strtotime($p['starts_at'].' UTC') <= time();
        $expired = strtotime($p['ends_at'].' UTC') <= time();
        return ['id' => (int)$p['id'],'date' => $p['date'],'mensaId' => $p['mensa_id'],'slotId' => $p['slot_id'],'time' => $p['slot_id'],'end' => (new DateTimeImmutable($p['ends_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('H:i'),'meetingPointId' => $p['meeting_point_id'],'visibility' => $p['visibility'],'reminder' => (bool)$p['reminder'],'reminderStatus' => !$p['reminder'] ? 'off' : (strtotime($p['starts_at'].' UTC') - 900 <= time() ? 'too_late' : 'requires_device_subscription'),'status' => $p['status'] === 'confirmed' && $expired ? 'expired' : $p['status'],'version' => (int)$p['version'],'editable' => !$started];
    }
    private function mutatePlan(int $uid, string $method, array $b): array
    {
        $date = $b['date'] ?? null;
        if (!is_string($date)) {
            $this->fail(422, 'invalid_date', 'Choose a date.');
        } $this->policy->date($date);
        if (!is_int($b['version'] ?? null) || !is_string($b['requestId'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{8,80}$/', $b['requestId'])) {
            $this->fail(422, 'invalid_request', 'A plan version and unique request ID are required.');
        }
        $this->db->begin_transaction();
        try {
            $this->rows('SELECT id FROM users WHERE id=? FOR UPDATE', [$uid]);
            $intent = $b;
            unset($intent['requestId']);
            ksort($intent);
            $fingerprint = hash('sha256', $method.json_encode($intent));
            $cached = $this->rows('SELECT response_json, request_hash FROM mutation_requests WHERE user_id=? AND request_id=?', [$uid,$b['requestId']])[0] ?? null;
            if ($cached) {
                if ($cached['request_hash'] !== $fingerprint) {
                    $this->fail(409, 'request_conflict', 'That retry ID belongs to a different change. Refresh and try again.');
                }
                $this->db->commit();
                return json_decode($cached['response_json'], true);
            }
            $old = $this->rows('SELECT * FROM lunch_plans WHERE user_id=? AND date=? FOR UPDATE', [$uid,$date])[0] ?? null;
            if (($old ? (int)$old['version'] : 0) !== $b['version']) {
                $this->fail(409, 'version_conflict', 'Your plan changed in another tab. Refresh before editing.');
            }
            if ($old && strtotime($old['starts_at'].' UTC') <= time()) {
                $this->fail(409, 'plan_started', 'This plan has already started and is read-only.');
            }
            if ($method === 'DELETE') {
                if (!$old) {
                    $this->fail(404, 'plan_missing', 'No plan exists for this date.');
                }
                $this->db->execute_query("UPDATE lunch_plans SET status='cancelled',reminder=0,version=version+1 WHERE id=?", [$old['id']]);
            } else {
                if (!is_string($b['slotId'] ?? null) || !is_string($b['mensaId'] ?? null)) {
                    $this->fail(422, 'single_slot_required', 'Choose exactly one time slot.');
                }
                $slot = null;
                foreach ($this->policy->slots($b['mensaId'], $date) as $s) {
                    if ($s['id'] === $b['slotId'] && $s['selectable']) {
                        $slot = $s;
                    }
                }
                if (!$slot) {
                    $this->fail(422, 'invalid_slot', 'That time is closed, in the past or no longer available.');
                }
                $max = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->modify('+90 days')->format('Y-m-d');
                if ($date > $max) {
                    $this->fail(422, 'date_range', 'Choose a date within the next 90 days.');
                }
                if (!in_array($b['visibility'] ?? null, ['private','friends','public'], true) || !is_bool($b['reminder'] ?? null)) {
                    $this->fail(422, 'invalid_settings', 'Choose valid visibility and reminder settings.');
                }
                $point = $b['meetingPointId'] ?? null;
                if ($point !== null && !in_array($point, [$b['mensaId'].'-entrance',$b['mensaId'].'-foyer'], true)) {
                    $this->fail(422, 'invalid_meeting_point', 'Choose a meeting point for this mensa.');
                }
                $start = (new DateTimeImmutable("$date {$slot['time']}", new DateTimeZone('Europe/Berlin')))->setTimezone(new DateTimeZone('UTC'));
                $args = [$b['mensaId'],$slot['id'],$point,$b['visibility'],(int)$b['reminder'],$start->format('Y-m-d H:i:s'),$start->modify('+15 minutes')->format('Y-m-d H:i:s')];
                if ($old) {
                    $this->db->execute_query("UPDATE lunch_plans SET mensa_id=?,slot_id=?,meeting_point_id=?,visibility=?,reminder=?,starts_at=?,ends_at=?,status='confirmed',version=version+1 WHERE id=?", [...$args,$old['id']]);
                } else {
                    $this->db->execute_query('INSERT INTO lunch_plans(mensa_id,slot_id,meeting_point_id,visibility,reminder,starts_at,ends_at,user_id,date) VALUES(?,?,?,?,?,?,?,?,?)', [...$args,$uid,$date]);
                }
            }
            $result = ['plan' => $this->plan($uid, $date)];
            if (class_exists(\MensaTogether\Delivery\ReminderService::class)) {
                (new \MensaTogether\Delivery\ReminderService($this->db))->syncPlan($result['plan']['id']);
            }
            $this->db->execute_query('INSERT INTO mutation_requests(user_id,request_id,response_json,request_hash) VALUES(?,?,?,?)', [$uid,$b['requestId'],json_encode($result),$fingerprint]);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    private function overview(string $mensa, string $date): array
    {
        $slots = $this->policy->slots($mensa, $date);
        $uid = $_SESSION['user_id'] ?? 0;
        $plans = $this->rows("SELECT p.*,u.alias FROM lunch_plans p JOIN users u ON u.id=p.user_id WHERE p.date=? AND p.mensa_id=? AND p.status='confirmed'", [$date,$mensa]);
        $friends = array_column($this->rows("SELECT IF(user_low=?,user_high,user_low) AS id FROM friendships WHERE (user_low=? OR user_high=?) AND status='accepted'", [$uid,$uid,$uid]), 'id');
        foreach ($slots as &$slot) {
            foreach ($plans as $p) {
                if ($p['slot_id'] === $slot['id']) {
                    $slot['count']++;
                    $upcoming = strtotime($p['ends_at'].' UTC') > time();
                    if ($upcoming && $p['visibility'] === 'public') {
                        $slot['publicParticipants'][] = ['alias' => $p['alias']];
                    } if ($upcoming && in_array($p['user_id'], $friends) && $p['visibility'] !== 'private') {
                        $slot['friends'][] = ['id' => (int)$p['user_id'],'alias' => $p['alias']];
                    }
                }
            }
        }
        unset($slot);
        $menu = [];
        foreach ([['tofu','Grilled tofu bowl','grilled-tofu.jpg','Plant-based sample dish',3.20,5.60],['lasagne','Vegetarian lasagne','vegetarian-lasagne.jpg','Sample vegetable lasagne',3.80,6.10],['soup','Potato soup','potato-soup.jpg','Sample seasonal soup',2.40,4.20],['skillet','Potato skillet','potato-skillet.jpg','Sample skillet meal',3.10,5.30]] as $dish) {
            $menu[] = ['id' => $dish[0],'name' => $dish[1],'description' => $dish[3],'image' => '/images/menu/'.$dish[2],'priceInternal' => $dish[4],'priceExternal' => $dish[5],'diet' => [],'allergens' => []];
        }
        foreach ($menu as &$entry) {
            $entry['diet'] = 'Unverified';
        }
        unset($entry);
        return ['mensaId' => $mensa,'date' => $date,'serviceWindows' => $this->policy->windows($mensa, $date),'hoursVerifiedAt' => SlotPolicy::VERIFIED,'notice' => 'Submitted plans, not measured occupancy. Regular hours; temporary operator closures may differ.','slots' => $slots,'menu' => $menu,'menuNotice' => 'Sample evaluation menu and prices, not today’s operator menu. Dietary and allergen information unverified; ask the operator.'];
    }
    private function friends(string $path, string $method, array $b, string $date): array
    {
        $uid = $this->owner();
        $this->policy->date($date);
        if ($method === 'GET') {
            $result = ['friends' => [],'requests' => [],'outgoing' => []];
            foreach ($this->rows("SELECT f.*,u.alias,u.id AS friend_id FROM friendships f JOIN users u ON u.id=IF(f.user_low=?,f.user_high,f.user_low) WHERE (f.user_low=? OR f.user_high=?) AND f.status IN ('accepted','requested')", [$uid,$uid,$uid]) as $f) {
                $friend = ['id' => (int)$f['friend_id'],'alias' => $f['alias']];
                if ($f['status'] === 'accepted') {
                    $p = $this->rows("SELECT date,mensa_id AS mensaId,slot_id AS slotId,meeting_point_id AS meetingPointId FROM lunch_plans WHERE user_id=? AND date=? AND visibility IN ('friends','public') AND status='confirmed' AND ends_at>UTC_TIMESTAMP()", [$f['friend_id'],$date])[0] ?? null;
                    $friend['sharedPlan'] = $p;
                    $result['friends'][] = $friend;
                } else {
                    $result[(int)$f['initiator_id'] === $uid ? 'outgoing' : 'requests'][] = $friend;
                }
            }
            return $result;
        }
        $this->rate('friend', 30);
        if ($path === '/api/friends/accept') {
            $id = (int)($b['id'] ?? 0);
            $this->db->execute_query("UPDATE friendships SET status='accepted' WHERE user_low=? AND user_high=? AND status='requested' AND initiator_id=?", [min($uid, $id),max($uid, $id),$id]);
            if (!$this->db->affected_rows) {
                $this->fail(404, 'request_missing', 'This incoming request no longer exists.');
            } return ['success' => true];
        }
        if ($method === 'DELETE') {
            $id = (int)($b['id'] ?? 0);
            $this->db->execute_query("UPDATE friendships SET status='revoked' WHERE user_low=? AND user_high=?", [min($uid, $id),max($uid, $id)]);
            return ['success' => true];
        }
        $invite = $this->lookupInvite($b['code'] ?? '');
        $other = (int)$invite['user_id'];
        if ($other === $uid) {
            $this->fail(422, 'self_invite', 'You cannot add yourself.');
        }
        $this->db->begin_transaction();
        try {
            $pair = $this->rows('SELECT * FROM friendships WHERE user_low=? AND user_high=? FOR UPDATE', [min($uid, $other),max($uid, $other)])[0] ?? null;
            if ($pair && $pair['status'] !== 'revoked') {
                $this->fail(409, 'duplicate_friend', 'A friendship or request already exists.');
            }
            $this->db->execute_query('UPDATE friend_invites SET used_by=? WHERE id=? AND used_by IS NULL AND revoked=0 AND expires_at>UTC_TIMESTAMP()', [$uid,$invite['id']]);
            if (!$this->db->affected_rows) {
                $this->fail(409, 'invite_used', 'This code has already been used.');
            }
            $this->db->execute_query("INSERT INTO friendships(user_low,user_high,initiator_id,status) VALUES(?,?,?,'requested') ON DUPLICATE KEY UPDATE initiator_id=VALUES(initiator_id),status='requested'", [min($uid, $other),max($uid, $other),$uid]);
            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    private function lookupInvite(mixed $code): array
    {
        if (!is_string($code) || !preg_match('/^[a-f0-9]{32}$/', $code)) {
            $this->fail(422, 'invalid_invite', 'This code is invalid, expired or revoked.');
        }
        $invite = $this->rows('SELECT i.*,u.alias FROM friend_invites i JOIN users u ON u.id=i.user_id WHERE token_hash=? AND revoked=0 AND used_by IS NULL AND expires_at>UTC_TIMESTAMP()', [hash('sha256', $code)])[0] ?? null;
        if (!$invite) {
            $this->fail(422, 'invalid_invite', 'This code is invalid, expired or revoked.');
        } return $invite;
    }
    private function invites(string $method, array $b): array
    {
        $uid = $this->owner();
        $this->rate('invite', 30);
        if ($method === 'GET') {
            return ['alias' => $this->lookupInvite($_GET['code'] ?? '')['alias']];
        }
        if ($method === 'DELETE') {
            $this->db->execute_query('UPDATE friend_invites SET revoked=1 WHERE user_id=? AND token_hash=?',[$uid,hash('sha256',(string)($b['code'] ?? ''))]);
            return ['success' => true];
        }
        $code = bin2hex(random_bytes(16));
        $expiry = (new DateTimeImmutable('+24 hours',new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $this->db->execute_query('INSERT INTO friend_invites(user_id,token_hash,expires_at) VALUES(?,?,?)',[$uid,hash('sha256',$code),$expiry]);
        return ['code' => $code,'expiresAt' => str_replace(' ','T',$expiry).'Z','url' => '/?view=friends&invite='.$code];
    }
}
