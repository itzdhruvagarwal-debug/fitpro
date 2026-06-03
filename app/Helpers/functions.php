<?php

use App\Helpers\NumberToWords;

if (! function_exists('number_to_words_indian')) {
    function number_to_words_indian(float $amount): string
    {
        return NumberToWords::toIndianCurrencyWords($amount);
    }
}
