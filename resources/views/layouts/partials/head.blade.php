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

    <!--begin::App Custom Styles-->
    @vite(['resources/css/app.css'])
    <!--end::App Custom Styles-->

    @stack('styles')
</head>
