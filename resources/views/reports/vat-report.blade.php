@extends('layouts.master')

@section('title', __('reports.vat_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">
                {{ __('reports.vat_report') }}
                <span class="text-gray-500 fw-semibold fs-7 d-block mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm w-auto" onchange="this.form.submit()" />
                </form>
                <a href="{{ route('reports.vat.export', ['month' => $month]) }}" class="btn btn-sm btn-light-primary">{{ __('reports.export_csv') }}</a>
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="row g-5 mb-5">
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold text-danger">@money($totals['sale_vat'])</div>
                    <div class="text-gray-500 fs-7">{{ __('reports.sale_vat') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold text-success">@money($totals['commission_vat'])</div>
                    <div class="text-gray-500 fs-7">{{ __('reports.commission_vat') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold text-success">@money($totals['shipping_vat'])</div>
                    <div class="text-gray-500 fs-7">{{ __('reports.shipping_vat') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold {{ (float)$totals['net'] >= 0 ? 'text-danger' : 'text-success' }}">@money($totals['net'])</div>
                    <div class="text-gray-500 fs-7">{{ __('reports.net_vat') }} ({{ (float)$totals['net'] >= 0 ? __('reports.vat_payable') : __('reports.vat_refund') }})</div></div></div></div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if($rows->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-3 gy-2">
                            <thead><tr class="fw-bold text-muted bg-light">
                                <th class="ps-4">SKU</th><th>{{ __('reports.product') }}</th>
                                <th>{{ __('reports.sale_vat') }}</th><th>{{ __('reports.purchase_vat') }}</th>
                                <th>{{ __('reports.commission_vat') }}</th><th>{{ __('reports.shipping_vat') }}</th><th>{{ __('reports.net_vat') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach($rows as $row)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td>@money($row['sale_vat'])</td>
                                    <td>@money($row['purchase_vat'])</td>
                                    <td>@money($row['commission_vat'])</td>
                                    <td>@money($row['shipping_vat'])</td>
                                    <td class="fw-bold">@money($row['net'])</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-10"><p class="text-gray-500">{{ __('reports.no_data') }}</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
