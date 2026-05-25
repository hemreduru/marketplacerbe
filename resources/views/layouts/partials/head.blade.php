<head>
    <base href="{{ url('/') }}" />
    <title>@yield('title', config('app.name', 'Marketplace'))</title>
    <meta charset="utf-8" />
    <meta name="description" content="@yield('description', 'Marketplace Management Panel')" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/media/logos/cirotik-logo.png') }}" />

    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

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

        /* NProgress Customization */
        #nprogress .bar {
            background: #3699FF !important;
            /* Metronic Primary Blue */
            height: 3px !important;
            z-index: 99999 !important;
        }

        #nprogress .peg {
            box-shadow: 0 0 10px #3699FF, 0 0 5px #3699FF !important;
        }
    </style>
    <!--end::Custom Styles-->

    @stack('styles')
</head>
