@extends('layouts.master')

@section('title', __('reports.sku_profit'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                {{ __('reports.sku_profit') }}
                <span class="text-gray-500 fw-semibold fs-7 mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="card">
                <div class="card-body p-0">
                    @if(count($rows) > 0)
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-2">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th>SKU</th>
                                    <th>{{ __('reports.product') }}</th>
                                    <th>{{ __('reports.quantity') }}</th>
                                    <th>{{ __('reports.revenue') }}</th>
                                    <th>{{ __('reports.net_revenue') }}</th>
                                    <th>{{ __('reports.cost') }}</th>
                                    <th>{{ __('reports.net_profit') }}</th>
                                    <th>{{ __('reports.margin') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td>{{ $row['qty'] }}</td>
                                    <td>{{ number_format($row['revenue'], 2) }} TL</td>
                                    <td>{{ number_format($row['net_revenue'], 2) }} TL</td>
                                    <td>{{ number_format($row['cost'], 2) }} TL</td>
                                    <td class="fw-bold {{ $row['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row['net_profit'], 2) }} TL
                                    </td>
                                    <td>%{{ $row['margin'] }}</td>
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
