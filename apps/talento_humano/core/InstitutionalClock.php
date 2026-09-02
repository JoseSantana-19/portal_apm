<?php
declare(strict_types=1);

/**
 * Fuente unica de fecha institucional.
 *
 * Las pruebas pueden fijar PORTAL_TEST_TODAY=YYYY-MM-DD sin alterar el reloj
 * del sistema operativo ni SQL Server.
 */
final class InstitutionalClock
{
    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(Config::timezone());
    }

    public static function now(): DateTimeImmutable
    {
        $fixed = trim((string)(getenv('PORTAL_TEST_TODAY') ?: ''));
        if ($fixed !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fixed, self::timezone());
            if ($date instanceof DateTimeImmutable && $date->format('Y-m-d') === $fixed) {
                return $date;
            }
        }
        return new DateTimeImmutable('now', self::timezone());
    }

    public static function today(): DateTimeImmutable
    {
        return self::now()->setTime(0, 0, 0);
    }

    public static function todayIso(): string
    {
        return self::today()->format('Y-m-d');
    }

    /** @return array{date:DateTimeImmutable,days:int,label:string} */
    public static function nextBirthday(DateTimeInterface|string $birthDate): array
    {
        $birth = $birthDate instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($birthDate)->setTimezone(self::timezone())
            : new DateTimeImmutable((string)$birthDate, self::timezone());
        $today = self::today();
        $year = (int)$today->format('Y');
        $month = (int)$birth->format('m');
        $day = (int)$birth->format('d');

        // En anos no bisiestos, el 29 de febrero se notifica el 28 de febrero.
        if ($month === 2 && $day === 29 && !checkdate(2, 29, $year)) {
            $day = 28;
        }
        $next = $today->setDate($year, $month, $day);
        if ($next < $today) {
            $year++;
            $day = ($month === 2 && (int)$birth->format('d') === 29 && !checkdate(2, 29, $year)) ? 28 : (int)$birth->format('d');
            $next = $today->setDate($year, $month, $day);
        }
        $days = (int)$today->diff($next)->format('%a');
        return [
            'date' => $next,
            'days' => $days,
            'label' => $days === 0 ? 'HOY' : ($days === 1 ? 'MAÑANA' : "EN {$days} DÍAS"),
        ];
    }
}
