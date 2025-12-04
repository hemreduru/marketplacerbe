@extends('layouts.master')

@section('title', __('common.settings'))

@section('content')
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('settings.account_settings') }}
                </h1>
            </div>
            <!--end::Page title-->
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Profile Details-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">{{ __('settings.profile_details') }}</h3>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Content-->
        <div class="card-body border-top p-9">
            <!--begin::Form-->
            <form id="profile_form">
                <!--begin::Input group - Name-->
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.name') }}</label>
                    <div class="col-lg-8">
                        <input type="text" id="profile_name" name="name"
                            class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.name') }}"
                            value="{{ $user->name }}" required />
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Input group - Email-->
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.email') }}</label>
                    <div class="col-lg-8">
                        <input type="email" id="profile_email" name="email"
                            class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.email') }}"
                            value="{{ $user->email }}" required />
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Actions-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="submit" id="profile_submit" class="btn btn-sm btn-primary">
                        <span class="indicator-label">{{ __('common.save') }}</span>
                        <span class="indicator-progress">{{ __('common.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
                <!--end::Actions-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Profile Details-->

    <!--begin::Preferences-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">{{ __('settings.preferences') }}</h3>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Content-->
        <div class="card-body border-top p-9">
            <!--begin::Form-->
            <form id="preferences_form">
                <!--begin::Input group - Language-->
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.language') }}</label>
                    <div class="col-lg-8">
                        <select id="preferred_language_id" name="preferred_language_id"
                            class="form-select form-select-lg form-select-solid"
                            data-placeholder="{{ __('settings.select_language') }}" required>
                            <option value="">{{ __('settings.select_language') }}</option>
                            @foreach ($languages as $lang)
                                <option value="{{ $lang->id }}"
                                    {{ $settings && $settings->preferred_language_id == $lang->id ? 'selected' : '' }}>
                                    {{ $lang->native_name }} ({{ strtoupper($lang->code) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Input group - Theme-->
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.theme') }}</label>
                    <div class="col-lg-8">
                        <select id="theme" name="theme" class="form-select form-select-lg form-select-solid"
                            data-placeholder="{{ __('settings.select_theme') }}" required>
                            <option value="light" {{ $settings && $settings->theme == 'light' ? 'selected' : '' }}>
                                {{ __('settings.light_theme') }}</option>
                            <option value="dark" {{ $settings && $settings->theme == 'dark' ? 'selected' : '' }}>
                                {{ __('settings.dark_theme') }}</option>
                            <option value="system" {{ $settings && $settings->theme == 'system' ? 'selected' : '' }}>
                                {{ __('settings.system_theme') }}</option>
                        </select>
                        <div class="form-text">{{ __('settings.theme_help') }}</div>
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Input group - Dark Mode-->
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('settings.dark_mode') }}</label>
                    <div class="col-lg-8">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode"
                                {{ $settings && $settings->dark_mode ? 'checked' : '' }} />
                            <label class="form-check-label" for="dark_mode">
                                {{ __('settings.enable_dark_mode') }}
                            </label>
                        </div>
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Actions-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="submit" id="preferences_submit" class="btn btn-sm btn-primary">
                        <span class="indicator-label">{{ __('common.save') }}</span>
                        <span class="indicator-progress">{{ __('common.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
                <!--end::Actions-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Preferences-->

    <!--begin::Change Password-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">{{ __('settings.change_password') }}</h3>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Content-->
        <div class="card-body border-top p-9">
            <!--begin::Form-->
            <form id="password_form">
                <!--begin::Input group - Current Password-->
                <div class="row mb-6">
                    <label
                        class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.current_password') }}</label>
                    <div class="col-lg-8">
                        <input type="password" id="current_password" name="current_password"
                            class="form-control form-control-lg form-control-solid"
                            placeholder="{{ __('settings.current_password') }}" required autocomplete="current-password" />
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Input group - New Password-->
                <div class="row mb-6">
                    <label
                        class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.new_password') }}</label>
                    <div class="col-lg-8">
                        <input type="password" id="new_password" name="new_password"
                            class="form-control form-control-lg form-control-solid"
                            placeholder="{{ __('settings.new_password') }}" required minlength="8"
                            autocomplete="new-password" />
                        <div class="form-text">{{ __('settings.password_min_length') }}</div>
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Input group - Confirm Password-->
                <div class="row mb-6">
                    <label
                        class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.confirm_password') }}</label>
                    <div class="col-lg-8">
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                            class="form-control form-control-lg form-control-solid"
                            placeholder="{{ __('settings.confirm_password') }}" required autocomplete="new-password" />
                    </div>
                </div>
                <!--end::Input group-->

                <!--begin::Actions-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="submit" id="password_submit" class="btn btn-sm btn-primary">
                        <span class="indicator-label">{{ __('settings.update_password') }}</span>
                        <span class="indicator-progress">{{ __('common.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
                <!--end::Actions-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Change Password-->
@endsection

@push('scripts')
    @include('profile.scripts')
@endpush
