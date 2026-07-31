<?php

test('finansal dashboard KDV pass-through notunu ve VAT raporu linkini gösterir (K.8)', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)->get(route('financial.index'))
        ->assertOk()
        ->assertSee(__('reports.vat_report_cta'))
        ->assertSee(route('reports.vat'));
});
