<?php

namespace App\Support\Enums;

/**
 * order_item_financials.reconciliation_status — mutabakat state machine.
 *
 * estimated → partially_settled → settled (+ reopened_return iade sonrası)
 * `settled`: komisyon+kargo+hizmet bedeli settlement kaynaklı VE iade
 * penceresi (finance.return_window_days) kapanmış demektir.
 */
enum ReconciliationStatus: string
{
    case Estimated = 'estimated';
    case PartiallySettled = 'partially_settled';
    case Settled = 'settled';
    case ReopenedReturn = 'reopened_return';
}
