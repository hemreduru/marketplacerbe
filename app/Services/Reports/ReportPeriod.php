<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;

/**
 * Rapor periyodu çözümleyici.
 *
 * Period anahtarını (today, this_month, custom, ...) somut [from, to] aralığına
 * ve aynı uzunlukta bir önceki döneme çevirir. Tüm Faz 4 raporları bunu paylaşır.
 */
final class ReportPeriod
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly CarbonImmutable $previousFrom,
        public readonly CarbonImmutable $previousTo,
        public readonly string $key,
    ) {}

    /**
     * Seçicide gösterilen geçerli period anahtarları.
     *
     * @return list<string>
     */
    public static function availableKeys(): array
    {
        return ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'custom'];
    }

    public static function fromRequest(string $key, ?string $from = null, ?string $to = null): self
    {
        $now = CarbonImmutable::now();

        [$start, $end] = match ($key) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'this_week' => [$now->startOfWeek(), $now->endOfWeek()],
            'last_week' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [
                $from !== null && $from !== '' ? CarbonImmutable::parse($from)->startOfDay() : $now->startOfMonth(),
                $to !== null && $to !== '' ? CarbonImmutable::parse($to)->endOfDay() : $now->endOfDay(),
            ],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };

        $resolvedKey = in_array($key, self::availableKeys(), true) ? $key : 'this_month';

        $lengthSeconds = (int) $start->diffInSeconds($end);
        $previousTo = $start->subSecond();
        $previousFrom = $previousTo->subSeconds($lengthSeconds);

        return new self($start, $end, $previousFrom, $previousTo, $resolvedKey);
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }
}
