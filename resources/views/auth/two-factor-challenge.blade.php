<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.two_factor.challenge_title') }} - {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/cirotik-logo.png') }}" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="app-blank">
    <div class="d-flex flex-center min-vh-100 p-10">
        <div class="card w-100" style="max-width: 480px;">
            <div class="card-body p-10">
                <h1 class="text-dark fw-bolder mb-3 text-center">
                    {{ __('auth.two_factor.challenge_title') }}
                </h1>
                <p class="text-gray-500 fs-6 text-center mb-8">
                    {{ __('auth.two_factor.challenge_subtitle') }}
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger mb-6">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('two-factor.verify') }}">
                    @csrf
                    <div class="fv-row mb-8">
                        <input type="text" name="code" autocomplete="one-time-code" autofocus
                            class="form-control form-control-lg text-center"
                            placeholder="000000" inputmode="numeric" />
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        {{ __('auth.two_factor.verify_button') }}
                    </button>
                </form>

                <div class="text-center mt-6 fs-7 text-gray-500">
                    {{ __('auth.two_factor.recovery_hint') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
