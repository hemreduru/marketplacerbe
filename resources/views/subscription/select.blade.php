@extends('layouts.master')

@section('title', __('subscription.title'))

@section('content')
    @if(session('error'))
        <div class="alert alert-dismissible bg-light-danger border border-danger d-flex align-items-center p-5 mb-8" style="border-radius: 1rem;">
            <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div class="d-flex flex-column">
                <h4 class="mb-1 text-danger fw-bold">{{ __('common.error') }}</h4>
                <span class="fw-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                <i class="ki-duotone ki-cross fs-1 text-danger"><span class="path1"></span><span class="path2"></span></i>
            </button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-dismissible bg-light-success border border-success d-flex align-items-center p-5 mb-8" style="border-radius: 1rem;">
            <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
            <div class="d-flex flex-column">
                <span class="fw-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                <i class="ki-duotone ki-cross fs-1 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        </div>
    @endif

    <div class="card card-flush">
        <div class="card-body p-lg-17">
            <div class="d-flex flex-column">
                <!--begin::Heading-->
                <div class="text-center mb-13">
                    <h1 class="fs-2hx fw-bold text-gray-900 mb-5">{{ __('subscription.title') }}</h1>
                    <div class="text-gray-500 fw-semibold fs-5">{{ __('subscription.subtitle') }}</div>
                </div>
                <!--end::Heading-->

                <!--begin::Trial Card-->
                @if(!$hasUsedTrial && !$user->hasActiveSubscription())
                <div class="row justify-content-center mb-13">
                    <div class="col-xl-8">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-5 border border-success border-2 bg-light-success rounded-3 px-10 py-8 shadow-sm">
                            <div class="d-flex align-items-center gap-5">
                                <span class="badge badge-success badge-circle p-5 fs-2">
                                    <i class="ki-duotone ki-rocket fs-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <div>
                                    <h3 class="text-success fw-bold mb-1">{{ __('subscription.trial_card_title') }}</h3>
                                    <div class="text-gray-700 fw-semibold fs-6">{{ __('subscription.trial_card_desc') }}</div>
                                </div>
                            </div>
                            <form action="{{ route('subscription.trial') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success px-8 py-3 fw-bold fs-6 hover-scale">
                                    <i class="ki-duotone ki-gift fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                    {{ __('subscription.trial_card_btn') }}
                                </button>
                            </form>
                        </div>
                        <div class="text-center mt-3">
                            <span class="text-muted fs-7">
                                <i class="ki-duotone ki-shield-tick fs-6 text-success me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ __('subscription.no_credit_card') }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif
                <!--end::Trial Card-->

                <!--begin::Billing Toggle-->
                <div class="d-flex justify-content-center align-items-center mb-13">
                    <span class="fw-semibold text-gray-700 me-3" id="label-monthly">{{ __('subscription.billing_monthly') }}</span>
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" id="billing-toggle" value="" style="width: 50px; height: 26px;" />
                    </div>
                    <span class="fw-semibold text-gray-700 ms-3" id="label-yearly">
                        {{ __('subscription.billing_yearly') }}
                        <span class="badge badge-light-success ms-2">{{ __('subscription.yearly_discount') }}</span>
                    </span>
                </div>
                <!--end::Billing Toggle-->

                <!--begin::Plans Row-->
                <div class="row g-10 justify-content-center">
                    @foreach($plans as $plan)
                        @php
                            $isPopular = $plan->is_popular;
                            $isPro = $plan->name === 'pro';
                            $cardClass = $isPopular
                                ? 'border border-primary border-2 bg-light-primary bg-opacity-30 shadow-lg'
                                : ($isPro ? 'border border-warning border-dashed border-2 bg-light-warning bg-opacity-20 shadow-md' : 'border border-dashed border-gray-300 bg-body shadow-sm');
                            $btnClass = $isPopular ? 'btn-primary' : ($isPro ? 'btn-warning text-dark fw-bold' : 'btn-light-primary');
                            $checkColor = $isPopular ? 'primary' : ($isPro ? 'warning' : 'success');
                            $titleColor = $isPopular ? 'text-primary' : ($isPro ? 'text-warning' : 'text-gray-900');
                            $priceColor = $isPopular ? 'text-primary' : ($isPro ? 'text-warning' : 'text-gray-900');

                            $featureLabels = [
                                'analytics' => __('subscription.feature_analytics'),
                                'claims'    => __('subscription.feature_claims'),
                                'repricing' => __('subscription.feature_repricing'),
                            ];
                        @endphp

                        <div class="col-xl-4">
                            <div class="d-flex flex-column h-100 align-items-center rounded p-10 position-relative hover-elevate-up {{ $cardClass }}"
                                style="transition: all 0.3s ease;">

                                @if($isPopular)
                                    <span class="position-absolute top-0 start-50 translate-middle badge badge-primary fs-7 fw-bold px-4 py-2 shadow pulse pulse-primary">
                                        <span class="pulse-ring" style="border-color: var(--bs-primary);"></span>
                                        {{ __('subscription.popular') }}
                                    </span>
                                @elseif($isPro)
                                    <span class="position-absolute top-0 start-50 translate-middle badge badge-warning text-dark fs-7 fw-bold px-4 py-2 shadow pulse pulse-warning">
                                        <span class="pulse-ring" style="border-color: var(--bs-warning);"></span>
                                        VIP GOLD
                                    </span>
                                @endif

                                <!--begin::Heading-->
                                <div class="text-center mb-8">
                                    <h2 class="{{ $titleColor }} mb-2 fw-bolder">{{ $plan->display_name }}</h2>
                                    @if($plan->description)
                                        <div class="text-gray-500 fw-semibold fs-6">{{ $plan->description }}</div>
                                    @endif
                                </div>
                                <!--end::Heading-->

                                <!--begin::Price-->
                                <div class="d-flex align-items-center mb-10">
                                    <span class="fs-3x fw-bold {{ $priceColor }} price-amount"
                                        data-monthly="{{ number_format($plan->price_monthly, 0, ',', '.') }}"
                                        data-yearly="{{ number_format($plan->price_yearly, 0, ',', '.') }}">
                                        ₺{{ number_format($plan->price_monthly, 0, ',', '.') }}
                                    </span>
                                    <span class="text-gray-500 fs-7 fw-semibold ms-2 price-period">{{ __('subscription.price_monthly') }}</span>
                                </div>
                                <!--end::Price-->

                                <!--begin::Features-->
                                <div class="w-100 mb-10">
                                    {{-- Marketplaces limit --}}
                                    <div class="d-flex align-items-center mb-5">
                                        <span class="badge badge-circle badge-light-{{ $checkColor }} p-3 me-3">
                                            <i class="ki-duotone ki-check fs-5 text-{{ $checkColor }}"></i>
                                        </span>
                                        <span class="text-gray-800 fw-semibold fs-6">
                                            @if($plan->isUnlimited('marketplaces'))
                                                {{ __('subscription.unlimited_marketplaces') }}
                                            @elseif($plan->getLimit('marketplaces') === 1)
                                                {{ __('subscription.one_marketplace') }}
                                            @else
                                                {{ $plan->getLimit('marketplaces') }} {{ __('subscription.marketplaces') }}
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Orders limit --}}
                                    <div class="d-flex align-items-center mb-5">
                                        <span class="badge badge-circle badge-light-{{ $checkColor }} p-3 me-3">
                                            <i class="ki-duotone ki-check fs-5 text-{{ $checkColor }}"></i>
                                        </span>
                                        <span class="text-gray-800 fw-semibold fs-6">
                                            @if($plan->isUnlimited('orders'))
                                                {{ __('subscription.unlimited') }} {{ __('common.orders') }}
                                            @else
                                                {{ number_format($plan->getLimit('orders'), 0, ',', '.') }} {{ __('subscription.orders_per_month') }}
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Products limit --}}
                                    <div class="d-flex align-items-center mb-5">
                                        <span class="badge badge-circle badge-light-{{ $checkColor }} p-3 me-3">
                                            <i class="ki-duotone ki-check fs-5 text-{{ $checkColor }}"></i>
                                        </span>
                                        <span class="text-gray-800 fw-semibold fs-6">
                                            @if($plan->isUnlimited('products'))
                                                {{ __('subscription.unlimited') }} {{ __('common.products') }}
                                            @else
                                                {{ number_format($plan->getLimit('products'), 0, ',', '.') }} {{ __('common.products') }}
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Boolean features --}}
                                    @foreach($featureLabels as $key => $label)
                                        @php $hasIt = $plan->hasFeature($key); @endphp
                                        <div class="d-flex align-items-center mb-5 {{ !$hasIt ? 'opacity-50' : '' }}">
                                            <span class="badge badge-circle badge-light-{{ $hasIt ? $checkColor : 'secondary' }} p-3 me-3">
                                                <i class="ki-duotone ki-{{ $hasIt ? 'check' : 'cross' }} fs-5 text-{{ $hasIt ? $checkColor : 'secondary' }}"></i>
                                            </span>
                                            <span class="fw-semibold fs-6 {{ $hasIt ? 'text-gray-800' : 'text-gray-600 text-decoration-line-through' }}">
                                                {{ $label }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <!--end::Features-->

                                <!--begin::Subscribe button-->
                                <form action="{{ route('subscription.subscribe') }}" method="POST" class="w-100 mt-auto">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $plan->name }}" />
                                    <input type="hidden" name="billing_period" value="monthly" class="billing-period-input" />
                                    <button type="submit" class="btn {{ $btnClass }} w-100 hover-scale">
                                        {{ __('subscription.try_now') }}
                                    </button>
                                </form>
                                <!--end::Subscribe button-->
                            </div>
                        </div>
                    @endforeach
                </div>
                <!--end::Plans Row-->

                {{-- Cancel subscription link for already subscribed users --}}
                @auth
                    @if($user->hasActiveSubscription())
                        <div class="text-center mt-10">
                            <form action="{{ route('subscription.cancel') }}" method="POST"
                                onsubmit="return confirm('{{ __('subscription.cancel_confirm') }}')">
                                @csrf
                                <button type="submit" class="btn btn-link text-muted fs-7">
                                    {{ __('subscription.cancel_subscription') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>
@push('scripts')
<script>
    document.getElementById('billing-toggle').addEventListener('change', function () {
        const isYearly = this.checked;
        const period = isYearly ? 'yearly' : 'monthly';
        const periodLabel = isYearly ? '{{ __('subscription.price_yearly') }}' : '{{ __('subscription.price_monthly') }}';

        document.querySelectorAll('.price-amount').forEach(el => {
            el.textContent = '₺' + (isYearly ? el.dataset.yearly : el.dataset.monthly);
        });

        document.querySelectorAll('.price-period').forEach(el => {
            el.textContent = periodLabel;
        });

        document.querySelectorAll('.billing-period-input').forEach(el => {
            el.value = period;
        });
    });
</script>
@endpush

@endsection
