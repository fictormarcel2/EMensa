<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use MensaTogether\Domain\SlotPolicy;

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    } echo "PASS $message\n";
}
$policy = new SlotPolicy();
$thursday = $policy->slots('eupener', '2026-11-12', new DateTimeImmutable('2026-11-11T10:00:00Z'));
$ids = array_column($thursday, 'id');
foreach (['11:15','14:00','14:30','16:45'] as $slot) {
    check(in_array($slot, $ids, true), "Thursday $slot allowed");
}
foreach (['14:15','17:00'] as $slot) {
    check(!in_array($slot, $ids, true), "Thursday $slot rejected");
}
$friday = array_column($policy->slots('eupener', '2026-11-13', new DateTimeImmutable('2026-11-11T10:00:00Z')), 'id');
check(in_array('13:45', $friday, true) && !in_array('14:00', $friday, true), 'Friday closing boundary');
check($policy->slots('eupener', '2026-11-14') === [], 'Weekend closed');
check($policy->slots('eupener', '2026-12-25') === [], 'Public holiday closed');
foreach ([
    '2026-04-03' => 'Good Friday',
    '2026-04-06' => 'Easter Monday',
    '2026-05-14' => 'Ascension Day',
    '2026-06-04' => 'Corpus Christi',
] as $date => $name) {
    check($policy->slots('eupener', $date) === [], "$name closed");
}

// The same local arrival hour changes its UTC instant at the DST boundaries.
$springBefore = $policy->slots('eupener', '2026-03-30', new DateTimeImmutable('2026-03-30T09:14:59Z'));
$springAt = $policy->slots('eupener', '2026-03-30', new DateTimeImmutable('2026-03-30T09:15:00Z'));
check($springBefore[0]['id'] === '11:15' && $springBefore[0]['selectable'], 'After spring DST 11:15 Berlin is still future at 09:14:59 UTC');
check(!$springAt[0]['selectable'], 'After spring DST slot closes exactly at 09:15 UTC');
$autumnBefore = $policy->slots('eupener', '2026-10-26', new DateTimeImmutable('2026-10-26T10:14:59Z'));
$autumnAt = $policy->slots('eupener', '2026-10-26', new DateTimeImmutable('2026-10-26T10:15:00Z'));
check($autumnBefore[0]['id'] === '11:15' && $autumnBefore[0]['selectable'], 'After autumn DST 11:15 Berlin is still future at 10:14:59 UTC');
check(!$autumnAt[0]['selectable'], 'After autumn DST slot closes exactly at 10:15 UTC');
$past = $policy->slots('eupener', '2026-11-12', new DateTimeImmutable('2026-11-12T12:00:00+01:00'));
check(!$past[0]['selectable'], 'Past slot disabled');
