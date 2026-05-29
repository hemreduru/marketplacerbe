@extends('layouts.master')

@section('title', __('reports.marketplace_comparison'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">
                {{ __('reports.marketplace_comparison') }}
                <span class="text-gray-500 fw-semibold fs-7 d-block mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
            @include('reports.partials.period-selector')
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="card">
                <div class="card-body p-0">
                    @if($rows->isNotEmpty() && $marketplaces->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gs-3 gy-2">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4" rowspan="2">SKU</th>
                                    <th rowspan="2">{{ __('reports.product') }}</th>
                                    @foreach($marketplaces as $mp)
                                        <th colspan="2" class="text-center border-start">{{ $mp->name }}</th>
                                    @endforeach
                                </tr>
                                <tr class="fw-bold text-muted bg-light fs-8">
                                    @foreach($marketplaces as $mp)
                                        <th class="text-center border-start">{{ __('reports.sales') }}</th>
                                        <th class="text-center">{{ __('reports.profit') }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    @foreach($marketplaces as $mp)
                                        @php($cell = $row['cells'][$mp->id])
                                        <td class="text-center border-start">{{ $cell['qty'] }}</td>
                                        <td class="text-center {{ (float)$cell['profit'] >= 0 ? 'text-success' : 'text-danger' }}">@money($cell['profit'])</td>
                                    @endforeach
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
