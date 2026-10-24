<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Días y franjas de entrega disponibles para el reparto local. */
class DeliverySchedule
{
    /** @return list<array{date: string, label: string}> */
    public static function availableDates(?CarbonImmutable $now = null): array
    {
        $tz = config('rolobox.timezone');
        $now = ($now ?? CarbonImmutable::now())->setTimezone($tz);
        $closed = config('rolobox.delivery.closed_weekdays', []);
        $first = $now->startOfDay()->addDays(max(0, (int) config('rolobox.delivery.min_lead_days')));
        $days = (int) config('rolobox.delivery.max_days_ahead', 14);

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $first->addDays($i);
            if (in_array($day->dayOfWeek, $closed, true)) {
                continue;
            }
            $dates[] = [
                'date' => $day->toDateString(),
                'label' => mb_convert_case(mb_substr($day->locale('es')->isoFormat('ddd D [de] MMM'), 0, 1), MB_CASE_UPPER)
                    .mb_substr($day->locale('es')->isoFormat('ddd D [de] MMM'), 1),
            ];
        }

        return $dates;
    }

    /** @return list<string> */
    public static function availableDateStrings(?CarbonImmutable $now = null): array
    {
        return array_column(self::availableDates($now), 'date');
    }

    /** @return list<array{value: string, label: string}> */
    public static function slots(): array
    {
        $slots = [];
        foreach (config('rolobox.delivery.slots', []) as $value => $label) {
            $slots[] = ['value' => (string) $value, 'label' => $label];
        }

        return $slots;
    }
}
