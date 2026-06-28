<?php
define('USD_TO_INR_RATE', 83);

function formatINR($amount) {
    $amount = round($amount, 2);
    if ($amount >= 10000000) {
        return '₹' . number_format($amount / 10000000, 2) . ' Cr';
    } elseif ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 2) . ' L';
    }
    $whole = floor($amount);
    $decimal = $amount - $whole;
    if ($decimal > 0) {
        $thousands = floor($whole / 1000);
        $hundreds = $whole % 1000;
        if ($thousands > 0) {
            return '₹' . $thousands . ',' . str_pad($hundreds, 3, '0', STR_PAD_LEFT) . '.' . str_pad(round($decimal * 100), 2, '0', STR_PAD_LEFT);
        }
        return '₹' . number_format($amount, 2);
    }
    if ($whole >= 1000) {
        $thousands = floor($whole / 1000);
        $hundreds = $whole % 1000;
        return '₹' . $thousands . ',' . str_pad($hundreds, 3, '0', STR_PAD_LEFT);
    }
    return '₹' . $whole;
}

// function formatPriceINR($amountInUSD) {
//     return formatINR(convertToINR($amountInUSD));
// }
