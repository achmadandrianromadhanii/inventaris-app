<?php

namespace App\Helpers;

class KondisiHelper
{
    public static function label(int $kondisi): string
    {
        return match (true) {
            $kondisi >= 80 => 'Baik',
            $kondisi >= 60 => 'Lumayan',
            $kondisi >= 35 => 'Rusak',
            default => 'Rusak Parah',
        };
    }

    public static function warna(int $kondisi): string
    {
        return match (true) {
            $kondisi >= 80 => 'emerald',
            $kondisi >= 60 => 'blue',
            $kondisi >= 35 => 'amber',
            default => 'red',
        };
    }
}
