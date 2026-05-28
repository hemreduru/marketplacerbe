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
            <div class="row g-6">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-gray-700">{{ __('reports.cirotik_estimated') }}</h5>
                            <div class="fs-2 fw-bold">{{ number_format($estimated['net'], 2) }} TL</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-gray-700">{{ __('reports.gross_sales') }}</h5>
                            <div class="fs-2 fw-bold">{{ number_format($estimated['gross'], 2) }} TL</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-gray-700">{{ __('reports.commission') }}</h5>
                            <div class="fs-2 fw-bold">{{ number_format($estimated['commission'], 2) }} TL</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-6">
                <div class="card-body text-center py-5">
                    <p class="text-gray-500">{{ __('reports.reconciliation_note') }}</p>
                    <p class="text-gray-400 fs-8">{{ __('reports.reconciliation_detail') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
