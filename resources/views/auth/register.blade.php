<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/cirotik-logo.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_body" class="app-blank">
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Body-->
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-600px p-10">
                        <!--begin::Form-->
                        <form class="form w-100" id="kt_sign_up_form" method="POST" action="{{ route('register.post') }}">
                            @csrf
                            <!--begin::Heading-->
                            <div class="text-center mb-11">
                                <h1 class="text-dark fw-bolder mb-3">{{ __('auth.register_title') }}</h1>
                                <div class="text-gray-500 fw-semibold fs-6">{{ __('auth.register_subtitle') }}</div>
                            </div>
                            <!--end::Heading-->

                            <!--begin::Alert for general errors-->
                            @if($errors->any())
                            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                                <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <div class="d-flex flex-column">
                                    <h4 class="mb-1 text-danger">{{ __('auth.error') }}</h4>
                                    <span>{{ $errors->first() }}</span>
                                </div>
                            </div>
                            @endif
                            <!--end::Alert-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-8">
                                <input type="text" 
                                       placeholder="{{ __('auth.name') }}" 
                                       name="name" 
                                       value="{{ old('name') }}"
                                       autocomplete="name" 
                                       class="form-control bg-transparent @error('name') is-invalid @enderror" 
                                       required />
                                @error('name')
                                <div class="fv-plugins-message-container invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-8">
                                <input type="text" 
                                       placeholder="{{ __('auth.username') }}" 
                                       name="username" 
                                       value="{{ old('username') }}"
                                       autocomplete="username" 
                                       class="form-control bg-transparent @error('username') is-invalid @enderror" 
                                       required />
                                @error('username')
                                <div class="fv-plugins-message-container invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-8">
                                <input type="email" 
                                       placeholder="{{ __('auth.email') }}" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       autocomplete="email" 
                                       class="form-control bg-transparent @error('email') is-invalid @enderror" 
                                       required />
                                @error('email')
                                <div class="fv-plugins-message-container invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-8">
                                <input type="password" 
                                       placeholder="{{ __('auth.password') }}" 
                                       name="password" 
                                       autocomplete="new-password" 
                                       class="form-control bg-transparent @error('password') is-invalid @enderror" 
                                       required />
                                @error('password')
                                <div class="fv-plugins-message-container invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-8">
                                <input type="password" 
                                       placeholder="{{ __('auth.password_confirmation') }}" 
                                       name="password_confirmation" 
                                       autocomplete="new-password" 
                                       class="form-control bg-transparent" 
                                       required />
                            </div>
                            <!--end::Input group-->

                            <!--begin::Submit button-->
                            <div class="d-grid mb-10">
                                <button type="submit" id="kt_sign_up_submit" class="btn btn-sm btn-primary">
                                    <span class="indicator-label">{{ __('auth.sign_up') }}</span>
                                    <span class="indicator-progress">{{ __('common.please_wait') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                            <!--end::Submit button-->

                            <!--begin::Sign in-->
                            <div class="text-gray-500 text-center fw-semibold fs-6">
                                {{ __('auth.already_member') }}
                                <a href="{{ route('login') }}" class="link-primary fw-semibold">{{ __('auth.sign_in') }}</a>
                            </div>
                            <!--end::Sign in-->
                        </form>
                        <!--end::Form-->
                    </div>
                </div>
            </div>
            <!--end::Body-->

            <!--begin::Aside-->
            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2" style="background-image: url({{ asset('assets/media/misc/auth-bg.png') }})">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <a href="#" class="mb-0 mb-lg-12">
                        <img alt="Logo" src="{{ asset('assets/media/logos/cirotik-logo.png') }}" class="h-60px h-lg-75px" />
                    </a>
                    <img class="d-none d-lg-block mx-auto w-275px w-md-50 w-xl-500px mb-10 mb-lg-20" src="{{ asset('assets/media/misc/auth-screens.png') }}" alt="" />
                    <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">
                        Fast, Efficient and Productive
                    </h1>
                    <div class="d-none d-lg-block text-white fs-base text-center">
                        Manage all your marketplace operations from a single dashboard.
                    </div>
                </div>
            </div>
            <!--end::Aside-->
        </div>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script>
        // Simple form submission handler for loading indicator
        document.getElementById('kt_sign_up_form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('kt_sign_up_submit');
            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;
        });
    </script>
</body>
</html>
