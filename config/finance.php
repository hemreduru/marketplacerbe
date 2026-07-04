<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mutabakat (Reconciliation) Ayarları
    |--------------------------------------------------------------------------
    |
    | return_window_days: Sipariş tarihinden itibaren iade penceresi. Bir kalem
    | ancak fee bileşenleri settlement kaynaklı OLUP bu pencere de kapandıysa
    | "settled" statüsüne geçer (iade hâlâ gelebilecekken kesinleşmez).
    |
    | deviation_alert_threshold: Tahmin ↔ gerçek sapma yüzdesi bu eşiği aşarsa
    | mutabakat raporunda anomali olarak işaretlenir.
    |
    */

    'return_window_days' => env('FINANCE_RETURN_WINDOW_DAYS', 15),

    'deviation_alert_threshold' => env('FINANCE_DEVIATION_THRESHOLD', 2.0),
];
