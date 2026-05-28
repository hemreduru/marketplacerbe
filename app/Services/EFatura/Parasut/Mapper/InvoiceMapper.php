<?php

namespace App\Services\EFatura\Parasut\Mapper;

class InvoiceMapper
{
    /**
     * @param  array<string, mixed>  $invoiceData
     * @return array<string, mixed>
     */
    public function toParasutPayload(array $invoiceData): array
    {
        return [
            'data' => [
                'type' => 'e_invoices',
                'attributes' => [
                    'item_type' => $invoiceData['item_type'] ?? 'invoice',
                    'description' => $invoiceData['description'] ?? '',
                    'invoice_date' => $invoiceData['invoice_date'] ?? now()->toDateString(),
                    'invoice_series' => $invoiceData['invoice_series'] ?? 'CIRO',
                    'net_total' => (string) ($invoiceData['net_total'] ?? 0),
                    'total_vat' => (string) ($invoiceData['total_vat'] ?? 0),
                    'gross_total' => (string) ($invoiceData['gross_total'] ?? 0),
                    'withholding_amount' => $invoiceData['withholding_amount'] ?? null,
                    'excise_duty' => $invoiceData['excise_duty'] ?? null,
                    'vat_withholding_amount' => $invoiceData['vat_withholding_amount'] ?? null,
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'id' => $invoiceData['contact_id'] ?? '',
                            'type' => 'contacts',
                        ],
                    ],
                    'details' => [
                        'data' => $this->mapItems($invoiceData['items'] ?? []),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(array $items): array
    {
        return array_map(function (array $item, int $index): array {
            return [
                'id' => (string) ($index + 1),
                'type' => 'invoice_details',
                'attributes' => [
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => (string) ($item['unit_price'] ?? 0),
                    'vat_rate' => $item['vat_rate'] ?? 20,
                    'description' => $item['description'] ?? '',
                ],
            ];
        }, $items, array_keys($items));
    }
}
