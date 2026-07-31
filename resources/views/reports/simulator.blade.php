@extends('layouts.master')

@section('title', __('reports.simulator_title'))

@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-fluid">
        <div class="row g-5 g-xl-8">
            <!--begin::Form-->
            <div class="col-xl-5 mb-5 mb-xl-0">
                <div class="card">
                    <div class="card-header align-items-center">
                        <div class="card-title flex-column">
                            <h3 class="fw-bold mb-1">{{ __('reports.simulator_title') }}</h3>
                            <span class="text-muted fs-7">{{ __('reports.simulator_hint') }}</span>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('reports.simulator') }}">
                        <div class="card-body">
                            <div class="mb-5">
                                <label class="form-label required" for="master_product_id">{{ __('reports.simulator_product') }}</label>
                                <select name="master_product_id" id="master_product_id" class="form-select" required>
                                    <option value="">—</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" @selected((int) request('master_product_id') === (int) $p->id)>
                                            {{ $p->title ?: $p->sku }} ({{ $p->sku }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label required" for="price">{{ __('reports.simulator_price') }} (₺)</label>
                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                    name="price" id="price"
                                    class="form-control @error('price') is-invalid @enderror"
                                    value="{{ request('price') }}" placeholder="0,00" required />
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{{ __('reports.simulator_submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            <!--end::Form-->

            <!--begin::Result-->
            <div class="col-xl-7">
                @if($result)
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <h3 class="fw-bold">{{ optional($selected)->title ?: optional($selected)->sku }}</h3>
                            </div>
                        </div>
                        <div class="card-body" style="font-variant-numeric: tabular-nums;">
                            <div class="d-flex flex-stack py-2 border-bottom border-gray-200">
                                <span class="text-muted">{{ __('reports.net_revenue') }}</span>
                                <span class="fw-bold text-gray-900">{{ number_format((float) $result->netRevenue, 2) }} ₺</span>
                            </div>
                            @foreach($result->deductions as $key => $amount)
                                <div class="d-flex flex-stack py-2 border-bottom border-gray-200">
                                    <span class="text-muted">− {{ __('reports.'.$key) }}</span>
                                    <span class="text-gray-700">{{ number_format((float) $amount, 2) }} ₺</span>
                                </div>
                            @endforeach
                            <div class="d-flex flex-stack py-3 mt-2">
                                <span class="fw-bold fs-5">{{ __('reports.net_profit') }}</span>
                                <span class="fw-bold fs-3 {{ (float) $result->netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format((float) $result->netProfit, 2) }} ₺
                                </span>
                            </div>
                            <div class="d-flex flex-stack py-1">
                                <span class="text-muted">{{ __('reports.margin') }}</span>
                                <span class="fw-semibold">%{{ $result->margin }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card h-100">
                        <div class="card-body d-flex flex-center text-center py-15">
                            <span class="text-muted fs-6">{{ __('reports.simulator_empty') }}</span>
                        </div>
                    </div>
                @endif
            </div>
            <!--end::Result-->
        </div>
    </div>
</div>
@endsection
