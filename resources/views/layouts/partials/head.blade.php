<head>
    <base href="{{ url('/') }}"/>
    <title>@yield('title', config('app.name', 'Marketplace'))</title>
    <meta charset="utf-8" />
    <meta name="description" content="@yield('description', 'Marketplace Management Panel')" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />

    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

    <!--begin::Toastr CSS (from Metronic)-->
    <link href="{{ asset('metronic_html_v8.2.0_demo1/demo1/src/plugins/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Toastr CSS-->

    <!--begin::Custom Styles-->
    <style>
        /* Theme mode icons */
        [data-bs-theme="light"] .theme-light-show {
            display: inline-block !important;
        }
        [data-bs-theme="light"] .theme-dark-show {
            display: none !important;
        }
        [data-bs-theme="dark"] .theme-light-show {
            display: none !important;
        }
        [data-bs-theme="dark"] .theme-dark-show {
            display: inline-block !important;
        }
    </style>
    <!--end::Custom Styles-->

    @stack('styles')
</head>
