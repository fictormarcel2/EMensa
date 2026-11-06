<?php

declare(strict_types=1);

namespace MensaTogether\Domain;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class SlotPolicy
{
    public const VERIFIED = '2026-11-05';
    public function mensas(): array
    {
        $names = ['eupener' => 'Mensa Eupener Straße','bayernallee' => 'Mensa Bayernallee','academica' => 'Mensa Academica','vita' => 'Mensa Vita','ahorn' => 'Mensa Ahornstraße','templer' => 'Mensa Templergraben','kma' => 'Mensa KMAC'];
        $result = [];
        foreach ($names as $id => $name) {
            $result[] = ['id' => $id,'name' => $name,'hours' => $id === 'eupener' ? 'Mon–Thu 11:15–14:15, 14:30–17:00 · Fri 11:15–14:00' : 'See selected date for regular service hours','meetingPoints' => [['id' => $id.'-entrance','name' => 'Main entrance','label' => 'Main entrance'],['id' => $id.'-foyer','name' => 'Foyer meeting point','label' => 'Foyer meeting point']]];
        }
        return $result;
    }
    public function date(string $date): DateTimeImmutable
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Europe/Berlin'));
        if (!$day || $day->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Choose a valid date.');
        }
        return $day;
    }
    public function windows(string $mensa, string $date): array
    {
        if (!in_array($mensa, array_column($this->mensas(), 'id'), true)) {
            throw new InvalidArgumentException('Unknown mensa.');
        }
        $day = $this->date($date);
        $weekday = (int)$day->format('N');
        $overrides = json_decode(file_get_contents(dirname(__DIR__, 2).'/database/closures.json'), true, 32, JSON_THROW_ON_ERROR)['overrides'];
        if (array_key_exists($date, $overrides[$mensa] ?? [])) {
            return $overrides[$mensa][$date];
        }
        $year = (int)$day->format('Y');
        // Gregorian computus, independent of PHP's optional calendar extension.
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $dayOfMonth = (($h + $l - 7 * $m + 114) % 31) + 1;
        $easter = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $dayOfMonth), new DateTimeZone('Europe/Berlin'));
        $holidays = ["$year-01-01","$year-05-01","$year-10-03","$year-11-01","$year-12-25","$year-12-26"];
        foreach ([-2,1,39,50,60] as $offset) {
            $holidays[] = $easter->modify("+$offset days")->format('Y-m-d');
        }
        if ($weekday > 5 || in_array($date, $holidays, true)) {
            return [];
        }
        if ($mensa === 'eupener') {
            return $weekday === 5 ? [['start' => '11:15','end' => '14:00']] : [['start' => '11:15','end' => '14:15'],['start' => '14:30','end' => '17:00']];
        }
        if ($mensa === 'academica') {
            return [['start' => '11:30','end' => $weekday === 5 ? '14:30' : '15:00']];
        }
        if ($mensa === 'vita') {
            return [['start' => '11:30','end' => '14:30']];
        }
        if ($mensa === 'templer') {
            return [['start' => '10:30','end' => $weekday === 5 ? '14:30' : '18:00']];
        }
        return [['start' => '11:30','end' => $weekday === 5 ? '14:00' : '14:30']];
    }
    public function slots(string $mensa, string $date, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $result = [];
        foreach ($this->windows($mensa, $date) as $window) {
            $at = new DateTimeImmutable("$date {$window['start']}", new DateTimeZone('Europe/Berlin'));
            $close = new DateTimeImmutable("$date {$window['end']}", new DateTimeZone('Europe/Berlin'));
            while (($end = $at->modify('+15 minutes')) <= $close) {
                $result[] = ['id' => $at->format('H:i'),'time' => $at->format('H:i'),'end' => $end->format('H:i'),'selectable' => $at > $now,'count' => 0,'friends' => [],'publicParticipants' => []];
                $at = $end;
            }
        }
        return $result;
    }
}
