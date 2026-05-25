<?php

namespace App\Services\Contracts;

interface FinanceServiceContract
{
    /**
     * Perform an incremental financial sync, determining the window from the
     * latest stored transaction for the given credential.
     */
    public function syncSmart(int $credentialId, ?int $startYear = null, ?callable $onProgress = null): void;
}
