@extends('layouts.master')

@section('title', __('reports.reconciliation'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                {{ __('reports.reconciliation') }}
                <span class="text-gray-500 fw-semibold fs-7 mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            @php
                $deviation = (float) $portfolio['deviation_pct'];
                $withinTarget = abs($deviation) < (float) config('finance.deviation_alert_threshold', 2.0);
            @endphp
            <div class="row g-6">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-gray-700">{{ __('reports.cirotik_estimated') }}</h5>
                            <div class="fs-2 fw-bold">{{ number_format((float) $portfolio['estimated'], 2) }} TL</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-gray-700">{{ __('reports.actual_net_profit') }}</h5>
                            <div class="fs-2 fw-bold">{{ number_format((float) $portfolio['actual'], 2) }} TL</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-gray-700">{{ __('reports.deviation_pct') }}</h5>
                            <div class="fs-2 fw-bold {{ $withinTarget ? 'text-success' : 'text-warning' }}">%{{ $portfolio['deviation_pct'] }}</div>
                            <span class="text-gray-500 fs-8">{{ $portfolio['settled_items'] }} {{ __('reports.settled_items') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-6">
                <div class="card-header">
                    <h3 class="card-title">{{ __('reports.sku_profit') }}</h3>
                </div>
                <div class="card-body p-0">
                    @if($bySku->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-2">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4">SKU</th>
                                    <th>{{ __('reports.product') }}</th>
                                    <th>{{ __('reports.cirotik_estimated') }}</th>
                                    <th>{{ __('reports.actual_net_profit') }}</th>
                                    <th>{{ __('reports.deviation_pct') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bySku as $row)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td>{{ number_format((float) $row['estimated_net_profit'], 2) }} TL</td>
                                    <td>{{ number_format((float) $row['actual_net_profit'], 2) }} TL</td>
                                    <td class="fw-bold {{ abs((float) $row['deviation_pct']) < (float) config('finance.deviation_alert_threshold', 2.0) ? 'text-success' : 'text-warning' }}">
                                        %{{ $row['deviation_pct'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-10">
                        <p class="text-gray-500">{{ __('reports.no_data') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
