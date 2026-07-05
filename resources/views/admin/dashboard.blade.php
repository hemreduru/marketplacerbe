@extends('layouts.master')

@section('title', __('admin.dashboard'))

@section('content')
@php
    $cards = [
        'users' => ['admin.metric_users', 'primary'],
        'active_subscriptions' => ['admin.metric_active_subs', 'success'],
        'sync_success_24h' => ['admin.metric_sync_ok', 'info'],
        'sync_failed_24h' => ['admin.metric_sync_failed', 'warning'],
        'failed_jobs' => ['admin.metric_failed_jobs', 'danger'],
    ];
@endphp
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid">
            <h1 class="page-title fw-bold fs-3">{{ __('admin.dashboard') }}</h1>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="row g-5 mb-5">
                @foreach($cards as $key => [$label, $color])
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body text-center py-6">
                            <div class="fs-2 fw-bold text-{{ $color }}">{{ $metrics[$key] }}</div>
                            <div class="text-muted fs-7 mt-1">{{ __($label) }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('admin.recent_payments') }}</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-row-dashed align-middle gy-2 mb-0">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4">{{ __('admin.user') }}</th>
                                        <th>{{ __('admin.amount') }}</th>
                                        <th>{{ __('admin.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentPayments as $p)
                                    <tr>
                                        <td class="ps-4">{{ $p->user->email ?? '—' }}</td>
                                        <td>{{ number_format((float) $p->amount, 2) }} {{ $p->currency }}</td>
                                        <td><span class="badge badge-light-{{ $p->status === 'success' ? 'success' : ($p->status === 'failed' ? 'danger' : 'warning') }}">{{ $p->status }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-6">{{ __('admin.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('admin.recent_activity') }}</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-row-dashed align-middle gy-2 mb-0">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4">{{ __('admin.event') }}</th>
                                        <th>{{ __('admin.subject') }}</th>
                                        <th>{{ __('admin.when') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentActivity as $a)
                                    <tr>
                                        <td class="ps-4">{{ $a->description }}</td>
                                        <td>{{ $a->log_name }}</td>
                                        <td class="text-muted fs-8">{{ $a->created_at?->diffForHumans() }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-6">{{ __('admin.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
