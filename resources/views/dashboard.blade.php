@extends('layouts.master')

@section('title', __('common.dashboard'))

@section('content')
    @unless ($hasCredential)
        <!--begin::Welcome / Connect Card-->
        <div class="card">
            <div class="card-body p-lg-17">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="fs-2hx fw-bold text-gray-800 mb-5">
                            {{ __('dashboard.welcome_message', ['name' => $user->name ?? '']) }}
                        </h1>
                        <div class="fs-5 fw-semibold text-gray-600 mb-7">
                            {{ __('dashboard.connect_prompt') }}
                        </div>
                        <a href="{{ route('marketplace.settings') }}" class="btn btn-primary">
                            {{ __('dashboard.connect_now') }}
                        </a>
                    </div>
                    <div class="col-lg-4">
                        <img src="{{ asset('assets/media/illustrations/sketchy-1/17.png') }}" class="mw-100 mh-300px" alt="" />
                    </div>
                </div>
            </div>
        </div>
        <!--end::Welcome / Connect Card-->
    @else
        <!--begin::KPI Row-->
        <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
            <div class="col-md-4 col-xl-2">
                <x-stat-card
                    :value="$kpis['revenue']"
                    :title="__('dashboard.revenue')"
                    icon="ki-basket"
                    color="primary"
                    :change="$kpis['revenue_change']"
                />
            </div>
            <div class="col-md-4 col-xl-2">
                <x-stat-card
                    :value="$kpis['net_profit']"
                    :title="__('dashboard.net_profit')"
                    icon="ki-dollar"
                    color="success"
                    :change="$kpis['profit_change']"
                />
            </div>
            <div class="col-md-4 col-xl-2">
                <x-stat-card
                    :value="$kpis['order_count']"
                    :title="__('dashboard.order_count')"
                    icon="ki-time"
                    color="warning"
                    format="integer"
                    :change="$kpis['order_change']"
                    :link="route('orders.index')"
                />
            </div>
            <div class="col-md-4 col-xl-2">
                <x-stat-card
                    :value="$kpis['margin']"
                    :title="__('dashboard.margin')"
                    icon="ki-graph"
                    color="info"
                    format="pct"
                />
            </div>
            <div class="col-md-4 col-xl-2">
                <x-stat-card
                    :value="$kpis['return_rate']"
                    :title="__('dashboard.return_rate')"
                    icon="ki-arrow-cycle"
                    color="secondary"
                    format="pct"
                />
            </div>
            <div class="col-md-4 col-xl-2">
                <x-stat-card
                    :value="$kpis['critical_stock']"
                    :title="__('dashboard.critical_stock')"
                    icon="ki-cross-circle"
                    color="danger"
                    format="integer"
                    :link="route('products.index')"
                />
            </div>
        </div>
        <!--end::KPI Row-->

        <!--begin::Chart + Attention Row-->
        <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
            <div class="col-xl-8">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title fw-bold text-gray-900">{{ __('dashboard.sales_net_trend') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-muted fs-7">
                                {{ __('dashboard.last_sync') }}:
                                {{ $lastSyncAt ? $lastSyncAt->diffForHumans() : __('dashboard.never_synced') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body pt-5">
                        <div id="kt_dashboard_sales_chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title fw-bold text-gray-900">{{ __('dashboard.needs_attention') }}</h3>
                    </div>
                    <div class="card-body pt-5">
                        <div class="d-flex flex-stack mb-5">
                            <a href="{{ route('questions.index') }}" class="text-gray-700 text-hover-primary fw-semibold fs-6">{{ __('common.waiting_answer') }}</a>
                            <span class="badge badge-light-warning fs-6 fw-bold">{{ $kpis['waiting_questions'] }}</span>
                        </div>
                        <div class="separator separator-dashed my-3"></div>
                        <div class="d-flex flex-stack mb-5">
                            <a href="{{ route('orders.index') }}" class="text-gray-700 text-hover-primary fw-semibold fs-6">{{ __('dashboard.pending_orders_label') }}</a>
                            <span class="badge badge-light-primary fs-6 fw-bold">{{ $kpis['waiting_orders'] }}</span>
                        </div>
                        <div class="separator separator-dashed my-3"></div>
                        <div class="d-flex flex-stack mb-5">
                            <a href="{{ route('products.index') }}" class="text-gray-700 text-hover-primary fw-semibold fs-6">{{ __('dashboard.low_stock') }}</a>
                            <span class="badge badge-light-warning fs-6 fw-bold">{{ $kpis['low_stock'] }}</span>
                        </div>
                        <div class="separator separator-dashed my-3"></div>
                        <div class="d-flex flex-stack">
                            <a href="{{ route('products.index') }}" class="text-gray-700 text-hover-primary fw-semibold fs-6">{{ __('dashboard.out_of_stock') }}</a>
                            <span class="badge badge-light-danger fs-6 fw-bold">{{ $kpis['out_of_stock'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Chart + Attention Row-->

        <!--begin::Recent Orders-->
        <div class="card card-flush">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold text-gray-900">{{ __('dashboard.recent_orders') }}</h3>
                <div class="card-toolbar">
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.view_all') }}</a>
                </div>
            </div>
            <div class="card-body pt-5">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>{{ __('dashboard.order_id') }}</th>
                                <th>{{ __('common.customer') ?? 'Customer' }}</th>
                                <th>{{ __('dashboard.date') }}</th>
                                <th class="text-end">{{ __('dashboard.amount') }}</th>
                                <th class="text-end">{{ __('dashboard.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="text-gray-900 fw-bold">{{ $order->order_number }}</td>
                                    <td>{{ trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: '-' }}</td>
                                    <td>{{ $order->order_date ? $order->order_date->format('d.m.Y') : '-' }}</td>
                                    <td class="text-end">{{ number_format($order->total_amount, 2) }} {{ $order->currency_code }}</td>
                                    <td class="text-end"><span class="badge badge-light-primary">{{ __('common.' . strtolower($order->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-10">{{ __('common.no_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Recent Orders-->
    @endunless
@endsection

@if ($hasCredential)
    @push('scripts')
        <script>
            $(document).ready(function() {
                var el = document.getElementById('kt_dashboard_sales_chart');
                if (!el || typeof ApexCharts === 'undefined') {
                    return;
                }

                var options = {
                    series: [{
                        name: '{{ __('common.gross_sales') }}',
                        data: @json($chartData['sales'])
                    }, {
                        name: '{{ __('common.net_profit') }}',
                        data: @json($chartData['net'])
                    }],
                    chart: {
                        type: 'area',
                        height: 350,
                        toolbar: { show: false }
                    },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: {
                        categories: @json($chartData['dates'])
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) { return val.toFixed(0) + ' ₺'; }
                        }
                    },
                    tooltip: {
                        y: { formatter: function(val) { return val.toFixed(2) + ' ₺'; } }
                    },
                    colors: ['#7239ea', '#50cd89'],
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.0 } },
                    grid: { borderColor: '#EFF2F5', strokeDashArray: 4 }
                };

                new ApexCharts(el, options).render();
            });
        </script>
    @endpush
@endif
