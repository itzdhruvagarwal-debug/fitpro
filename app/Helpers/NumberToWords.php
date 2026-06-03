<?php

namespace App\Helpers;

class NumberToWords
{
    /**
     * Supports values up to 99,99,99,999.99 (Indian format).
     */
    public static function toIndianCurrencyWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $rupeesPart = self::convertIndianNumber($rupees);
        $paisePart = $paise > 0 ? self::convertIndianNumber($paise) : 'Zero';

        return sprintf(
            'Rupees %s and Paise %s Only',
            $rupeesPart,
            $paisePart
        );
    }

    private static function convertIndianNumber(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $units = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $segments = [
            'Crore' => 10000000,
            'Lakh' => 100000,
            'Thousand' => 1000,
            'Hundred' => 100,
        ];

        $words = [];
        foreach ($segments as $label => $value) {
            if ($number >= $value) {
                $count = intdiv($number, $value);
                $number %= $value;
                $words[] = self::convertIndianNumber($count).' '.$label;
            }
        }

        if ($number > 0) {
            if ($number < 20) {
                $words[] = $units[$number];
            } else {
                $words[] = trim($tens[intdiv($number, 10)].' '.$units[$number % 10]);
            }
        }

        return implode(' ', array_filter($words));
    }
}
