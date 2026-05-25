<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('auth.reset_password_title') }} - {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/cirotik-logo.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_body" class="app-blank">
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-500px p-10">
                        <form class="form w-100" method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}" />

                            <div class="text-center mb-11">
                                <h1 class="text-dark fw-bolder mb-3">{{ __('auth.reset_password_title') }}</h1>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                                    <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    <div class="d-flex flex-column">
                                        <h4 class="mb-1 text-danger">{{ __('auth.error') }}</h4>
                                        <span>{{ $errors->first() }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="fv-row mb-8">
                                <input type="email"
                                       placeholder="{{ __('auth.email') }}"
                                       name="email"
                                       value="{{ old('email', $email ?? '') }}"
                                       autocomplete="email"
                                       class="form-control bg-transparent @error('email') is-invalid @enderror"
                                       required />
                            </div>

                            <div class="fv-row mb-8">
                                <input type="password"
                                       placeholder="{{ __('auth.new_password') }}"
                                       name="password"
                                       autocomplete="new-password"
                                       class="form-control bg-transparent @error('password') is-invalid @enderror"
                                       required />
                            </div>

                            <div class="fv-row mb-8">
                                <input type="password"
                                       placeholder="{{ __('auth.new_password_confirm') }}"
                                       name="password_confirmation"
                                       autocomplete="new-password"
                                       class="form-control bg-transparent"
                                       required />
                            </div>

                            <div class="d-grid mb-10">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    {{ __('auth.reset_password_btn') }}
                                </button>
                            </div>

                            <div class="text-gray-500 text-center fw-semibold fs-6">
                                <a href="{{ route('login') }}" class="link-primary">{{ __('auth.back_to_login') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2" style="background-image: url({{ asset('assets/media/misc/auth-bg.png') }})">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <a href="{{ route('login') }}" class="mb-0 mb-lg-12">
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
        </div>
    </div>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
</body>
</html>
