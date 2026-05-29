@extends('layouts.master')

@section('title', __('reports.analytics_extra'))

@php
    $maxHeat = 0;
    foreach ($heatmap as $row) { $maxHeat = max($maxHeat, max($row)); }
    $maxLtm = max(1, $ltm->max(fn ($r) => (float) $r['net']));
    $days = [1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'];
@endphp

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">
                {{ __('reports.analytics_extra') }}
                <span class="text-gray-500 fw-semibold fs-7 d-block mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
            @include('reports.partials.period-selector')
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="row g-5">

                {{-- Top cities --}}
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.top_cities') }}</h3></div>
                        <div class="card-body p-0">
                            @if($topCities->isNotEmpty())
                            <table class="table table-row-dashed align-middle gs-4 gy-2">
                                <thead><tr class="fw-bold text-muted bg-light"><th class="ps-4">{{ __('reports.city') }}</th><th>{{ __('reports.orders_count') }}</th><th>{{ __('reports.revenue') }}</th></tr></thead>
                                <tbody>
                                    @foreach($topCities as $c)
                                    <tr><td class="ps-4">{{ $c['city'] }}</td><td>{{ $c['orders'] }}</td><td>@money($c['revenue'])</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else<div class="text-center py-10 text-gray-500">{{ __('reports.no_data') }}</div>@endif
                        </div>
                    </div>
                </div>

                {{-- LTM trend --}}
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.ltm_trend') }}</h3></div>
                        <div class="card-body">
                            <div class="d-flex align-items-end justify-content-between" style="height:180px; gap:4px;">
                                @foreach($ltm as $m)
                                    @php($h = max(2, (int) (((float)$m['net'] / $maxLtm) * 160)))
                                    <div class="d-flex flex-column align-items-center" style="flex:1;">
                                        <div class="bg-primary rounded w-100" style="height:{{ $h }}px;" title="{{ $m['month'] }}: {{ $m['net'] }}"></div>
                                        <span class="text-muted fs-9 mt-1" style="transform:rotate(-45deg);">{{ \Illuminate\Support\Str::of($m['month'])->substr(2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hourly heatmap --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.hourly_heatmap') }}</h3></div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered text-center align-middle" style="font-size:11px;">
                                <thead><tr class="bg-light"><th></th>@for($h=0;$h<24;$h++)<th>{{ $h }}</th>@endfor</tr></thead>
                                <tbody>
                                    @foreach($heatmap as $weekday => $hours)
                                    <tr>
                                        <th class="bg-light">{{ $days[$weekday] }}</th>
                                        @foreach($hours as $count)
                                            @php($op = $maxHeat > 0 ? round($count / $maxHeat, 2) : 0)
                                            <td style="background-color: rgba(0,158,247,{{ $op }});">{{ $count > 0 ? $count : '' }}</td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Cohort --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.cohort') }}</h3></div>
                        <div class="card-body p-0">
                            @if($cohort->isNotEmpty())
                            <table class="table table-row-dashed align-middle gs-4 gy-2">
                                <thead><tr class="fw-bold text-muted bg-light"><th class="ps-4">{{ __('reports.month') }}</th><th>{{ __('reports.product') }}</th><th>{{ __('reports.sales_qty') }}</th></tr></thead>
                                <tbody>
                                    @foreach($cohort as $row)
                                    <tr><td class="ps-4">{{ $row['month'] }}</td><td>{{ $row['products'] }}</td><td>{{ $row['sales_qty'] }}</td></tr>
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
