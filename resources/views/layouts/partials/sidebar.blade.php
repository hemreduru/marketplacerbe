<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <a href="{{ route('dashboard') }}">
            <img alt="Logo" src="{{ asset('assets/media/logos/cirotik-logo.png') }}"
                class="h-35px app-sidebar-logo-default" />
            <img alt="Logo" src="{{ asset('assets/media/logos/cirotik-logo-mini.png') }}"
                class="h-25px app-sidebar-logo-minimize" />
        </a>
        <!--end::Logo image-->

        <!--begin::Sidebar toggle-->
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary body-bg h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
            data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-duotone ki-double-left fs-2 rotate-180">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </div>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->

    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
            data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">

            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu"
                data-kt-menu="true" data-kt-menu-expand="false">

                <!--begin:Menu item-->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-element-11 fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('common.dashboard') }}</span>
                    </a>
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item - Analytics (feature gated)-->
                <div class="menu-item">
                    @feature('analytics')
                        <a class="menu-link {{ request()->routeIs('financial.index') ? 'active' : '' }}"
                            href="{{ route('financial.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-chart-line-star fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                            <span class="menu-title">{{ __('common.financial') }}</span>
                        </a>
                    @else
                        <a class="menu-link opacity-50"
                            href="{{ route('subscription.select') }}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            title="{{ __('subscription.analytics_restricted') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-lock-2 fs-2 text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title text-gray-500">{{ __('common.financial') }}</span>
                        </a>
                    @endfeature
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item-->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('products.index') ? 'active' : '' }}"
                        href="{{ route('products.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-basket fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('common.products') }}</span>
                    </a>
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item-->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('orders.index') ? 'active' : '' }}"
                        href="{{ route('orders.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-basket fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('common.orders') }}</span>
                    </a>
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item - Claims (feature gated)-->
                <div class="menu-item">
                    @feature('claims')
                        <a class="menu-link {{ request()->routeIs('claims.index') ? 'active' : '' }}"
                            href="{{ route('claims.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-arrow-circle-left fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">{{ __('common.claims') }}</span>
                        </a>
                    @else
                        <a class="menu-link opacity-50"
                            href="{{ route('subscription.select') }}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            title="{{ __('subscription.claims_restricted') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-lock-2 fs-2 text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title text-gray-500">{{ __('common.claims') }}</span>
                        </a>
                    @endfeature
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item-->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('questions.index') ? 'active' : '' }}"
                        href="{{ route('questions.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-sms fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('common.questions') }}</span>
                    </a>
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item-->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('marketplace.settings') ? 'active' : '' }}"
                        href="{{ route('marketplace.settings') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-shop fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('settings.marketplace_settings') }}</span>
                    </a>
                </div>
                <!--end:Menu item-->

                <!--begin:Menu item - Reports (feature gated)-->
                @feature('analytics')
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('reports.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-chart-simple fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('reports.reports') }}</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        @php($reportLinks = [
                            'reports.order' => __('reports.order_report'),
                            'reports.sku-profit' => __('reports.sku_profit'),
                            'reports.stock' => __('reports.stock_report'),
                            'reports.returns' => __('reports.return_analysis'),
                            'reports.marketplace-comparison' => __('reports.marketplace_comparison'),
                            'reports.vat' => __('reports.vat_report'),
                            'reports.ads' => __('reports.ad_performance'),
                            'reports.analytics' => __('reports.analytics_extra'),
                            'reports.buybox' => __('reports.buybox_tracker'),
                            'reports.reconciliation' => __('reports.reconciliation'),
                        ])
                        @foreach($reportLinks as $routeName => $label)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs($routeName) ? 'active' : '' }}"
                                    href="{{ route($routeName) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">{{ $label }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('repricer.*') ? 'active' : '' }}"
                        href="{{ route('repricer.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-price-tag fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('reports.repricer') }}</span>
                    </a>
                </div>
                @endfeature
                <!--end:Menu item - Reports-->

                @auth
                    @if(auth()->user()->isAdmin())
                        <!--begin::Admin section-->
                        <div class="menu-item mt-5">
                            <div class="menu-content">
                                <span class="menu-heading fw-bold text-uppercase fs-7 text-muted">Admin</span>
                            </div>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"
                                href="{{ route('admin.plans.index') }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-price-tag fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Plan Yönetimi</span>
                            </a>
                        </div>
                        <!--end::Admin section-->
                    @endif
                @endauth

            </div>
            <!--end::Menu-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::sidebar menu-->
</div>
<!--end::Sidebar-->
