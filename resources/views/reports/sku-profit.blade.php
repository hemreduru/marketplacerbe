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
            <div class="d-flex gap-2">
                <a href="{{ route('reports.sku-profit.export', ['format' => 'xlsx', 'period' => $period]) }}" class="btn btn-sm btn-light-success">{{ __('reports.export_excel') }}</a>
                <a href="{{ route('reports.sku-profit.export', ['format' => 'pdf', 'period' => $period]) }}" class="btn btn-sm btn-light-danger">{{ __('reports.export_pdf') }}</a>
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="card">
                <div class="card-body p-0">
                    @if(count($rows) > 0)
                    <div class="table-responsive">
                        {{-- K.6: net gelirden tüm kesintiler ayrı kolonlarda → "ciro − komisyon − kargo − stopaj − reklam − iade = net kâr" şeffaf --}}
                        <table class="table table-row-dashed align-middle gs-0 gy-2" style="font-variant-numeric: tabular-nums;">
                            <thead>
                                <tr class="fw-bold text-muted bg-light text-nowrap">
                                    <th>SKU</th>
                                    <th>{{ __('reports.product') }}</th>
                                    <th class="text-end">{{ __('reports.quantity') }}</th>
                                    <th class="text-end">{{ __('reports.net_revenue') }}</th>
                                    <th class="text-end">{{ __('reports.cost') }}</th>
                                    <th class="text-end">{{ __('reports.commission') }}</th>
                                    <th class="text-end">{{ __('reports.shipping') }}</th>
                                    <th class="text-end">{{ __('reports.stopaj') }}</th>
                                    <th class="text-end">{{ __('reports.ad_cost') }}</th>
                                    <th class="text-end">{{ __('reports.return_cost') }}</th>
                                    <th class="text-end">{{ __('reports.net_profit') }}</th>
                                    <th class="text-end">{{ __('reports.margin') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                <tr class="text-nowrap">
                                    <td class="fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td class="text-end">{{ $row['items'] }}</td>
                                    <td class="text-end">{{ number_format((float) $row['net_revenue'], 2) }}</td>
                                    <td class="text-end text-gray-600">{{ number_format((float) $row['cogs'], 2) }}</td>
                                    <td class="text-end text-gray-600">{{ number_format((float) $row['commission'], 2) }}</td>
                                    <td class="text-end text-gray-600">{{ number_format((float) $row['shipping'], 2) }}</td>
                                    <td class="text-end text-gray-600">{{ number_format((float) $row['stopaj'], 2) }}</td>
                                    <td class="text-end text-gray-600">{{ number_format((float) $row['ad_cost'], 2) }}</td>
                                    <td class="text-end text-gray-600">{{ number_format((float) $row['return_cost'], 2) }}</td>
                                    <td class="text-end fw-bold {{ (float) $row['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format((float) $row['net_profit'], 2) }}
                                    </td>
                                    <td class="text-end">%{{ $row['margin'] }}</td>
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
