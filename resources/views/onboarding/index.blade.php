@extends('layouts.master')

@section('title', __('onboarding.title'))

@section('content')
@php
    $meta = [
        'account' => ['route' => null, 'cta' => null],
        'subscription' => ['route' => route('subscription.select'), 'cta' => __('onboarding.step_subscription_cta')],
        'marketplace' => ['route' => route('marketplace.settings'), 'cta' => __('onboarding.step_marketplace_cta')],
        'first_sync' => ['route' => route('products.index'), 'cta' => __('onboarding.step_sync_cta')],
    ];
@endphp
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid">
            <h1 class="page-title d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                {{ __('onboarding.title') }}
                <span class="text-gray-500 fw-semibold fs-7 mt-1">{{ __('onboarding.subtitle') }}</span>
            </h1>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid" style="max-width: 800px;">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold text-gray-800">{{ __('onboarding.progress') }}</span>
                        <span class="text-muted">{{ $completedCount }}/{{ $totalCount }}</span>
                    </div>
                    <div class="progress mb-8" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar"
                            style="width: {{ $totalCount ? ($completedCount / $totalCount) * 100 : 0 }}%"></div>
                    </div>

                    @foreach($steps as $key => $done)
                    <div class="d-flex align-items-center border-bottom border-gray-200 py-4">
                        <span class="badge badge-circle {{ $done ? 'badge-success' : 'badge-secondary' }} me-4">
                            {{ $done ? '✓' : $loop->iteration }}
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-gray-900">{{ __('onboarding.step_'.$key.'_title') }}</div>
                            <div class="text-muted fs-7">{{ __('onboarding.step_'.$key.'_desc') }}</div>
                        </div>
                        @if($done)
                            <span class="badge badge-light-success">{{ __('onboarding.done') }}</span>
                        @elseif($meta[$key]['route'])
                            <a href="{{ $meta[$key]['route'] }}" class="btn btn-sm btn-primary">{{ $meta[$key]['cta'] }}</a>
                        @endif
                    </div>
                    @endforeach

                    @if($isComplete)
                    <div class="text-center mt-8">
                        <h3 class="text-success fw-bold">{{ __('onboarding.complete_title') }}</h3>
                        <p class="text-muted">{{ __('onboarding.complete_desc') }}</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-success">{{ __('onboarding.go_dashboard') }}</a>
                    </div>
                    @endif

                    <div class="separator my-6"></div>
                    <div class="d-flex flex-stack flex-wrap gap-3">
                        <div>
                            <div class="fw-bold text-gray-900">{{ __('demo.explore_title') }}</div>
                            <div class="text-muted fs-7">{{ __('demo.explore_desc') }}</div>
                        </div>
                        <form action="{{ route('demo.load') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-light-primary">{{ __('demo.load_button') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
