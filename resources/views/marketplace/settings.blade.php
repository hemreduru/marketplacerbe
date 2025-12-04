@extends('layouts.master')

@section('title', __('settings.marketplace_settings'))

@section('content')
    <!--begin::Content-->
    <div class="card">
        <div class="card-header card-header-stretch overflow-auto">
            <ul class="nav nav-stretch nav-line-tabs fw-bold border-transparent flex-nowrap" role="tablist"
                id="marketplace_tabs">
                @foreach ($marketplaces as $index => $marketplace)
                    <li class="nav-item">
                        <a class="nav-link text-active-primary px-3 {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                            href="#kt_tab_pane_{{ $marketplace->id }}">
                            {{ $marketplace->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="marketplace_tab_content">
                @foreach ($marketplaces as $index => $marketplace)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                        id="kt_tab_pane_{{ $marketplace->id }}" role="tabpanel">
                        <form class="marketplace-form" data-marketplace-id="{{ $marketplace->id }}">
                            <div class="row mb-6">
                                <label
                                    class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('common.api_key') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="api_key"
                                        class="form-control form-control-lg form-control-solid"
                                        placeholder="{{ __('common.api_key') }}"
                                        value="{{ isset($credentials[$marketplace->id]) ? $credentials[$marketplace->id]->api_key : '' }}"
                                        required />
                                </div>
                            </div>

                            <div class="row mb-6">
                                <label
                                    class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('common.api_secret') }}</label>
                                <div class="col-lg-8">
                                    <input type="password" name="api_secret"
                                        class="form-control form-control-lg form-control-solid"
                                        placeholder="{{ __('common.api_secret') }}"
                                        value="{{ isset($credentials[$marketplace->id]) ? $credentials[$marketplace->id]->api_secret : '' }}"
                                        required />
                                </div>
                            </div>

                            <!-- Additional Credentials -->
                            @if ($marketplace->slug === 'trendyol')
                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">Seller ID</label>
                                    <div class="col-lg-8">
                                        <input type="text" name="additional_credentials[seller_id]"
                                            class="form-control form-control-lg form-control-solid" placeholder="Seller ID"
                                            value="{{ isset($credentials[$marketplace->id]) && isset($credentials[$marketplace->id]->additional_credentials['seller_id']) ? $credentials[$marketplace->id]->additional_credentials['seller_id'] : '' }}" />
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex justify-content-end py-6 px-9">
                                <button type="submit" class="btn btn-sm btn-primary submit-btn">
                                    <span class="indicator-label">{{ __('common.save') }}</span>
                                    <span class="indicator-progress">{{ __('common.please_wait') }}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!--end::Content-->
@endsection

@push('scripts')
    <script>
        const translations = {
            error_occurred: "{{ __('common.error_occurred') }}",
            request_failed: "{{ __('common.request_failed') }}"
        };
    </script>
    @include('marketplace.scripts')
@endpush
