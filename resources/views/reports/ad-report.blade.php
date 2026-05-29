@extends('layouts.master')

@section('title', __('reports.ad_performance'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">
                {{ __('reports.ad_performance') }}
                <span class="text-gray-500 fw-semibold fs-7 d-block mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
            <div class="d-flex gap-2 align-items-center">
                @include('reports.partials.period-selector')
                <form method="POST" action="{{ route('reports.ads.sync') }}">@csrf
                    <button class="btn btn-sm btn-light-primary">{{ __('reports.sync_ads') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

            <div class="row g-5 mb-5">
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold">@money($totals['spend'])</div><div class="text-gray-500 fs-7">{{ __('reports.spend') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold">@money($totals['revenue'])</div><div class="text-gray-500 fs-7">{{ __('reports.attributed_revenue') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold {{ $totals['roas'] >= 1 ? 'text-success' : 'text-danger' }}">{{ $totals['roas'] }}x</div><div class="text-gray-500 fs-7">{{ __('reports.roas') }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush"><div class="card-body text-center">
                    <div class="fs-3 fw-bold">%{{ $totals['acos'] }}</div><div class="text-gray-500 fs-7">{{ __('reports.acos') }}</div></div></div></div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if($campaigns->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-3 gy-2">
                            <thead><tr class="fw-bold text-muted bg-light">
                                <th class="ps-4">{{ __('reports.campaign') }}</th><th>{{ __('common.marketplace') }}</th>
                                <th>{{ __('reports.spend') }}</th><th>{{ __('reports.attributed_revenue') }}</th>
                                <th>{{ __('reports.roas') }}</th><th>{{ __('reports.acos') }}</th><th>{{ __('reports.profit_contribution') }}</th><th></th>
                            </tr></thead>
                            <tbody>
                                @foreach($campaigns as $c)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $c['name'] }}</td>
                                    <td>{{ $c['marketplace_code'] }}</td>
                                    <td>@money($c['spend'])</td>
                                    <td>@money($c['revenue'])</td>
                                    <td class="fw-bold {{ $c['roas'] >= 1 ? 'text-success' : 'text-danger' }}">{{ $c['roas'] }}x</td>
                                    <td>%{{ $c['acos'] }}</td>
                                    <td class="{{ (float)$c['contribution'] >= 0 ? 'text-success' : 'text-danger' }}">@money($c['contribution'])</td>
                                    <td>
                                        @if($c['profitable'])
                                            <span class="badge badge-light-success">{{ __('reports.profitable') }}</span>
                                        @else
                                            <span class="badge badge-light-danger">{{ __('reports.unprofitable') }}</span>
                                        @endif
                                    </td>
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
