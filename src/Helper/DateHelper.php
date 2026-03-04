<?php

declare(strict_types=1);

namespace App\Helper;

use Carbon\Carbon;

class DateHelper
{
    public static function getDate(string $date): ?Carbon
    {
        if (strlen($date) === 4) {
            $year = (int) $date;
            if ($year < 1900 || $year > 2020) {
                return null;
            }
            $result = Carbon::createFromDate($year, 1, 1); // 01/01 as unknown
        } else {
            try {
                $result = Carbon::createFromFormat('d.m.Y', $date);
                if ($result->year < 100) {
                    $result->year += 1900;
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return $result;
    }
}
