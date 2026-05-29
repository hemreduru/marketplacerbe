@extends('layouts.master')

@section('title', __('reports.return_analysis'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">
                {{ __('reports.return_analysis') }}
                <span class="text-gray-500 fw-semibold fs-7 d-block mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
            @include('reports.partials.period-selector')
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">

            <div class="row g-5 mb-5">
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body text-center">
                    <div class="fs-2hx fw-bold">{{ $summary['sales_qty'] }}</div>
                    <div class="text-gray-500">{{ __('reports.sales_qty') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body text-center">
                    <div class="fs-2hx fw-bold">{{ $summary['return_qty'] }}</div>
                    <div class="text-gray-500">{{ __('reports.return_qty') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body text-center">
                    <div class="fs-2hx fw-bold text-danger">%{{ $summary['return_rate'] }}</div>
                    <div class="text-gray-500">{{ __('reports.return_rate') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body text-center">
                    <div class="fs-2hx fw-bold">@money($summary['return_cost'])</div>
                    <div class="text-gray-500">{{ __('reports.return_cost') }}</div></div></div></div>
            </div>

            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.top_reasons') }}</h3></div>
                        <div class="card-body p-0">
                            @if($byReason->isNotEmpty())
                            <table class="table table-row-dashed align-middle gs-4 gy-2">
                                <thead><tr class="fw-bold text-muted bg-light"><th class="ps-4">{{ __('reports.top_reasons') }}</th><th>{{ __('reports.return_qty') }}</th><th>{{ __('reports.return_cost') }}</th></tr></thead>
                                <tbody>
                                    @foreach($byReason as $r)
                                    <tr>
                                        <td class="ps-4">{{ \Illuminate\Support\Str::headline($r['reason']) }}</td>
                                        <td>{{ $r['qty'] }} <span class="text-muted">({{ $r['claim_count'] }})</span></td>
                                        <td>@money($r['refund'])</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else<div class="text-center py-10 text-gray-500">{{ __('reports.no_data') }}</div>@endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.return_analysis') }} — SKU</h3></div>
                        <div class="card-body p-0">
                            @if($bySku->isNotEmpty())
                            <table class="table table-row-dashed align-middle gs-4 gy-2">
                                <thead><tr class="fw-bold text-muted bg-light"><th class="ps-4">SKU</th><th>{{ __('reports.product') }}</th><th>{{ __('reports.sales_qty') }}</th><th>{{ __('reports.return_qty') }}</th><th>{{ __('reports.return_rate') }}</th></tr></thead>
                                <tbody>
                                    @foreach($bySku as $row)
                                    <tr>
                                        <td class="ps-4 fw-bold">{{ $row['sku'] }}</td>
                                        <td>{{ $row['title'] }}</td>
                                        <td>{{ $row['sales_qty'] }}</td>
                                        <td>{{ $row['return_qty'] }}</td>
                                        <td class="fw-bold {{ $row['return_rate'] > 10 ? 'text-danger' : '' }}">%{{ $row['return_rate'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else<div class="text-center py-10 text-gray-500">{{ __('reports.no_data') }}</div>@endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
