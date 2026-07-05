<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title') · {{ config('app.name', 'Cirotik') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/cirotik-logo.png') }}" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-body">
    <div class="d-flex flex-column flex-root">
        <div class="app-container container py-10" style="max-width: 900px;">
            <div class="mb-8">
                <a href="{{ url('/') }}">
                    <img alt="Logo" src="{{ asset('assets/media/logos/cirotik-logo.png') }}" class="h-50px" />
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <h1 class="fw-bold text-gray-900 mb-6">@yield('title')</h1>

                    <div class="alert alert-warning">{{ __('legal.counsel_note') }}</div>

                    <div class="text-gray-700 fs-6 lh-lg">
                        @yield('content')
                    </div>
                </div>
            </div>

            <div class="d-flex flex-stack text-muted mt-6 fs-8">
                <span>{{ date('Y') }} &copy; {{ config('app.name', 'Cirotik') }}</span>
                <a href="{{ url('/') }}" class="link-primary">{{ __('legal.back_home') }}</a>
            </div>
        </div>
    </div>
</body>
</html>
