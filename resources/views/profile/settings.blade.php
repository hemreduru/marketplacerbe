@extends('layouts.master')

@section('title', __('common.settings'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
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

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
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
                                <input type="text" id="profile_name" name="name" class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.name') }}" required />
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Email-->
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.email') }}</label>
                            <div class="col-lg-8">
                                <input type="email" id="profile_email" name="email" class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.email') }}" required />
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Actions-->
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="submit" id="profile_submit" class="btn btn-primary">
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
                                <select id="preferred_language_id" name="preferred_language_id" class="form-select form-select-lg form-select-solid" required>
                                    <option value="">{{ __('settings.select_language') }}</option>
                                </select>
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Theme-->
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.theme') }}</label>
                            <div class="col-lg-8">
                                <select id="theme" name="theme" class="form-select form-select-lg form-select-solid" required>
                                    <option value="light">{{ __('settings.light_theme') }}</option>
                                    <option value="dark">{{ __('settings.dark_theme') }}</option>
                                    <option value="system">{{ __('settings.system_theme') }}</option>
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
                                    <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" />
                                    <label class="form-check-label" for="dark_mode">
                                        {{ __('settings.enable_dark_mode') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Actions-->
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="submit" id="preferences_submit" class="btn btn-primary">
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
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.current_password') }}</label>
                            <div class="col-lg-8">
                                <input type="password" id="current_password" name="current_password" class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.current_password') }}" required />
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - New Password-->
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.new_password') }}</label>
                            <div class="col-lg-8">
                                <input type="password" id="new_password" name="new_password" class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.new_password') }}" required minlength="8" />
                                <div class="form-text">{{ __('settings.password_min_length') }}</div>
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Confirm Password-->
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('settings.confirm_password') }}</label>
                            <div class="col-lg-8">
                                <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control form-control-lg form-control-solid" placeholder="{{ __('settings.confirm_password') }}" required />
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Actions-->
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="submit" id="password_submit" class="btn btn-primary">
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
        </div>
    </div>
    <!--end::Content-->
</div>

{{-- Settings Page Scripts --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load languages
        loadLanguages();

        // Load current user data and settings
        loadUserData();
        loadUserSettings();

        // Profile form submit
        document.getElementById('profile_form').addEventListener('submit', handleProfileSubmit);

        // Preferences form submit
        document.getElementById('preferences_form').addEventListener('submit', handlePreferencesSubmit);

        // Password form submit
        document.getElementById('password_form').addEventListener('submit', handlePasswordSubmit);
    });

    // Load available languages
    function loadLanguages() {
        axios.get('/languages')
            .then(response => {
                if (response.data.success) {
                    const select = document.getElementById('preferred_language_id');
                    response.data.data.forEach(lang => {
                        const option = document.createElement('option');
                        option.value = lang.id;
                        option.textContent = `${lang.native_name} (${lang.code.toUpperCase()})`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                // Silent fail
            });
    }

    // Load current user data
    function loadUserData() {
        axios.get('/profile')
            .then(response => {
                if (response.data.success) {
                    const user = response.data.data;
                    document.getElementById('profile_name').value = user.name || '';
                    document.getElementById('profile_email').value = user.email || '';
                }
            })
            .catch(error => {
                // Silent fail
            });
    }

    // Load user settings
    function loadUserSettings() {
        axios.get('/settings-data')
            .then(response => {
                if (response.data.success) {
                    const settings = response.data.data;

                    // Set language
                    if (settings.preferred_language_id) {
                        document.getElementById('preferred_language_id').value = settings.preferred_language_id;
                    }

                    // Set theme
                    if (settings.theme) {
                        document.getElementById('theme').value = settings.theme;
                    }

                    // Set dark mode
                    document.getElementById('dark_mode').checked = settings.dark_mode || false;
                }
            })
            .catch(error => {
                // Silent fail
            });
    }

    // Handle profile update
    function handleProfileSubmit(e) {
        e.preventDefault();

        const submitButton = document.getElementById('profile_submit');
        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        const formData = {
            name: document.getElementById('profile_name').value,
            email: document.getElementById('profile_email').value
        };

        axios.put('/profile', formData)
            .then(response => {
                if (response.data.success) {
                    showSuccess(response.data.message);

                    // Update sessionStorage user_data
                    const userData = JSON.parse(sessionStorage.getItem('user_data') || '{}');
                    userData.name = formData.name;
                    userData.email = formData.email;
                    sessionStorage.setItem('user_data', JSON.stringify(userData));

                    // Update header display
                    const usernameEl = document.getElementById('header-username');
                    const emailEl = document.getElementById('header-email');
                    if (usernameEl) usernameEl.textContent = formData.name;
                    if (emailEl) emailEl.textContent = formData.email;
                } else {
                    showError(response.data.message);
                }
            })
            .catch(error => {
                if (error.response && error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    showError(errors.join('<br>'));
                }
            })
            .finally(() => {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
            });
    }

    // Handle preferences update
    function handlePreferencesSubmit(e) {
        e.preventDefault();

        const submitButton = document.getElementById('preferences_submit');
        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        const formData = {
            preferred_language_id: document.getElementById('preferred_language_id').value,
            theme: document.getElementById('theme').value,
            dark_mode: document.getElementById('dark_mode').checked
        };

        axios.put('/settings-data', formData)
            .then(response => {
                if (response.data.success) {
                    showSuccess(response.data.message);

                    // Show confirmation to reload page for changes to take effect
                    showConfirm('{{ __("settings.reload_page_for_changes") }}', function() {
                        window.location.reload();
                    });
                } else {
                    showError(response.data.message);
                }
            })
            .catch(error => {
                if (error.response && error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    showError(errors.join('<br>'));
                }
            })
            .finally(() => {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
            });
    }

    // Handle password update
    function handlePasswordSubmit(e) {
        e.preventDefault();

        const submitButton = document.getElementById('password_submit');
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('new_password_confirmation').value;

        // Check if passwords match
        if (newPassword !== confirmPassword) {
            showError('{{ __("settings.passwords_not_match") }}');
            return;
        }

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        const formData = {
            current_password: document.getElementById('current_password').value,
            new_password: newPassword,
            new_password_confirmation: confirmPassword
        };

        axios.put('/profile/password', formData)
            .then(response => {
                if (response.data.success) {
                    showSuccess(response.data.message);

                    // Clear password fields
                    document.getElementById('password_form').reset();
                } else {
                    showError(response.data.message);
                }
            })
            .catch(error => {
                if (error.response && error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    showError(errors.join('<br>'));
                }
            })
            .finally(() => {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
            });
    }
</script>
@endsection
