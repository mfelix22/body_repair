<?php

namespace App\Helpers;

class NumberToIndonesian
{
    private static $ones = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan'
    ];

    private static $teens = [
        'sepuluh',
        'sebelas',
        'dua belas',
        'tiga belas',
        'empat belas',
        'lima belas',
        'enam belas',
        'tujuh belas',
        'delapan belas',
        'sembilan belas'
    ];

    private static $tens = [
        '',
        '',
        'dua puluh',
        'tiga puluh',
        'empat puluh',
        'lima puluh',
        'enam puluh',
        'tujuh puluh',
        'delapan puluh',
        'sembilan puluh'
    ];

    private static $scales = [
        '',
        'ribu',
        'juta',
        'miliar',
        'triliun'
    ];

    public static function convert($number)
    {
        if ($number == 0) {
            return 'nol';
        }

        if ($number < 0) {
            return 'negatif ' . self::convert(-$number);
        }

        $number = (int) $number;

        // Split number into groups of three digits
        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = (int) ($number / 1000);
        }

        $result = [];
        $scale_count = count($groups) - 1;

        for ($i = $scale_count; $i >= 0; $i--) {
            if ($groups[$i] > 0) {
                $result[] = self::convertHundreds($groups[$i]);
                if ($i > 0) {
                    $result[] = self::$scales[$i];
                }
            }
        }

        return ucfirst(implode(' ', $result));
    }

    private static function convertHundreds($number)
    {
        $hundreds = (int) ($number / 100);
        $remainder = $number % 100;

        $result = [];

        if ($hundreds > 0) {
            if ($hundreds == 1) {
                $result[] = 'seratus';
            } else {
                $result[] = self::$ones[$hundreds] . ' ratus';
            }
        }

        if ($remainder >= 10 && $remainder <= 19) {
            $result[] = self::$teens[$remainder - 10];
        } else {
            $tens_digit = (int) ($remainder / 10);
            $ones_digit = $remainder % 10;

            if ($tens_digit > 0) {
                $result[] = self::$tens[$tens_digit];
            }

            if ($ones_digit > 0) {
                if ($ones_digit == 1 && $tens_digit == 0 && $hundreds == 0) {
                    $result[] = 'satu';
                } else {
                    $result[] = self::$ones[$ones_digit];
                }
            }
        }

        return implode(' ', $result);
    }
}
