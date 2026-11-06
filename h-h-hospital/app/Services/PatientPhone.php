<?php

namespace App\Services;

class PatientPhone
{
    public static function normalize(string $input): ?string
    {
        if (! preg_match('/\A[0-9+() .-]+\z/', $input)) {
            return null;
        }
        $number = preg_replace('/[() .-]/', '', $input);
        if (str_starts_with($number, '00')) {
            $number = '+'.substr($number, 2);
        } elseif (preg_match('/\A03[0-9]{9}\z/', $number)) {
            $number = '+92'.substr($number, 1);
        } elseif (preg_match('/\A923[0-9]{9}\z/', $number)) {
            $number = '+'.$number;
        }

        return preg_match('/\A\+[1-9][0-9]{7,14}\z/', $number) ? $number : null;
    }
}
