<?php

namespace App\Support;

/**
 * Sipariş seviyesindeki bir tutarı kalemlere kayıpsız dağıtır.
 *
 * Ağırlık payına göre 4 hane hassasiyetle böler; yuvarlama artığını
 * en büyük ağırlıklı kaleme ekler — böylece SUM(paylar) == toplam
 * her zaman tam sağlanır (settlement mutabakatı için şart).
 */
class MoneyAllocator
{
    private const INTERNAL_SCALE = 6;

    private const RESULT_SCALE = 4;

    /**
     * @param  array<int|string, float|int|string>  $weights
     * @return list<string> Ağırlık sırasıyla paylar (scale 4)
     */
    public static function distribute(string $total, array $weights): array
    {
        if ($weights === []) {
            return [];
        }

        $weights = array_map(static fn (float|int|string $w): string => (string) $w, array_values($weights));

        $weightSum = '0';
        foreach ($weights as $weight) {
            $weightSum = bcadd($weightSum, $weight, self::INTERNAL_SCALE);
        }

        // Tüm ağırlıklar sıfırsa eşit dağıt
        if (bccomp($weightSum, '0', self::INTERNAL_SCALE) === 0) {
            $weights = array_fill(0, count($weights), '1');
            $weightSum = (string) count($weights);
        }

        $shares = [];
        $allocated = '0.0000';

        foreach ($weights as $weight) {
            $share = bcround(
                bcdiv(bcmul($total, $weight, self::INTERNAL_SCALE), $weightSum, self::INTERNAL_SCALE),
                self::RESULT_SCALE
            );
            $shares[] = $share;
            $allocated = bcadd($allocated, $share, self::RESULT_SCALE);
        }

        // Yuvarlama artığını en büyük ağırlıklı kaleme ekle
        $residue = bcsub($total, $allocated, self::RESULT_SCALE);

        if (bccomp($residue, '0', self::RESULT_SCALE) !== 0) {
            $largestIndex = 0;
            foreach ($weights as $index => $weight) {
                if (bccomp($weight, $weights[$largestIndex], self::INTERNAL_SCALE) === 1) {
                    $largestIndex = $index;
                }
            }

            $shares[$largestIndex] = bcadd($shares[$largestIndex], $residue, self::RESULT_SCALE);
        }

        return $shares;
    }
}
