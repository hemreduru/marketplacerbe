<!--begin::Footer-->
<div id="kt_app_footer" class="app-footer">
    <!--begin::Footer container-->
    <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
        <!--begin::Copyright-->
        <div class="text-dark order-2 order-md-1">
            <span class="text-muted fw-semibold me-1">{{ date('Y') }}&copy;</span>
            <a href="#" target="_blank" class="text-gray-800 text-hover-primary">{{ config('app.name', 'Marketplace') }}</a>
        </div>
        <!--end::Copyright-->

        <!--begin::Menu-->
        <ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
            <li class="menu-item">
                <a href="{{ route('legal.show', 'privacy') }}" class="menu-link px-2">{{ __('legal.privacy_title') }}</a>
            </li>
            <li class="menu-item">
                <a href="{{ route('legal.show', 'terms') }}" class="menu-link px-2">{{ __('legal.terms_title') }}</a>
            </li>
            <li class="menu-item">
                <a href="{{ route('legal.show', 'distance-sales') }}" class="menu-link px-2">{{ __('legal.distance_sales_title') }}</a>
            </li>
        </ul>
        <!--end::Menu-->
    </div>
    <!--end::Footer container-->
</div>
<!--end::Footer-->
