@extends('layouts.master')

@section('title', __('claims.detail_title'))

@section('content')
@php
    $rows = [
        'order_number' => $claim->order_number,
        'customer' => $claim->customer_name,
        'status' => $claim->status,
        'claim_date' => $claim->claim_date?->format('d.m.Y H:i'),
        'item_count' => $claim->item_count,
        'return_reason' => $claim->return_reason,
        'return_tracking' => $claim->return_tracking_number,
        'refund_amount' => $claim->refund_amount !== null ? number_format((float) $claim->refund_amount, 2).' TL' : null,
        'approved_at' => $claim->approved_at?->format('d.m.Y H:i'),
        'restock' => $claim->restock ? __('claims.yes') : __('claims.no'),
        'restocked_at' => $claim->restocked_at?->format('d.m.Y H:i'),
        'resolution_notes' => $claim->resolution_notes,
    ];
@endphp
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title fw-bold fs-3">{{ __('claims.detail_title') }} — {{ $claim->order_number }}</h1>
            <a href="{{ route('claims.index') }}" class="btn btn-sm btn-light">{{ __('claims.back') }}</a>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid" style="max-width: 820px;">
            <div class="card">
                <div class="card-body">
                    <div class="row g-5">
                        @foreach($rows as $key => $value)
                        <div class="col-md-6">
                            <div class="text-muted fs-7">{{ __('claims.'.$key) }}</div>
                            <div class="fw-bold text-gray-900">{{ $value !== null && $value !== '' ? $value : __('claims.not_set') }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
