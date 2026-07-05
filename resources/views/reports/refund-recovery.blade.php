@extends('layouts.master')

@section('title', __('reports.refund_recovery'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                {{ __('reports.refund_recovery') }}
                <span class="text-gray-500 fw-semibold fs-7 mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="alert alert-info">{{ __('reports.refund_recovery_note') }}</div>

            <div class="card">
                <div class="card-body p-0">
                    @if($anomalies->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-2">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4">{{ __('reports.anomaly_type') }}</th>
                                    <th>{{ __('reports.order_no') }}</th>
                                    <th>SKU</th>
                                    <th>{{ __('reports.estimated_fee') }}</th>
                                    <th>{{ __('reports.actual_fee') }}</th>
                                    <th>{{ __('reports.recoverable') }}</th>
                                    <th>{{ __('reports.evidence') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($anomalies as $a)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge badge-light-warning">{{ __('reports.anomaly_'.$a['type']) }}</span>
                                    </td>
                                    <td>{{ $a['order_number'] ?? '—' }}</td>
                                    <td>{{ $a['sku'] ?? '—' }}</td>
                                    <td>{{ number_format((float) $a['estimated'], 2) }} TL</td>
                                    <td>{{ number_format((float) $a['actual'], 2) }} TL</td>
                                    <td class="fw-bold text-success">
                                        {{ number_format((float) $a['actual'] - (float) $a['estimated'], 2) }} TL
                                    </td>
                                    <td class="fs-8 text-muted">
                                        @foreach(($a['evidence'] ?? []) as $key => $value)
                                            <div>{{ $key }}: {{ $value }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-10">
                        <p class="text-gray-500">{{ __('reports.no_anomalies') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
