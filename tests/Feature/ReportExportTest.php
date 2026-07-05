<?php

use Maatwebsite\Excel\Facades\Excel;

test('SKU kâr raporu Excel (xlsx) olarak indirilir', function () {
    Excel::fake();
    [$user] = userWithTrendyol();
    $from = now()->startOfMonth()->toDateString();
    $to = now()->toDateString();

    $this->actingAs($user)->get(route('reports.sku-profit.export', ['format' => 'xlsx']));

    Excel::assertDownloaded("sku-profit-{$from}_{$to}.xlsx");
});

test('SKU kâr raporu PDF olarak indirilir', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)->get(route('reports.sku-profit.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertDownload();
});

test('geçersiz export formatı 404 döner', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)->get('/reports/sku-profit/export/csv')->assertNotFound();
});
