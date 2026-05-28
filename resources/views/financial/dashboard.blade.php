@extends('layouts.master')

@section('title', 'Financial Dashboard')

@section('content')
    <x-guide-alert color="primary" icon="ki-information-5" :title="__('common.financial_management_guide')">
        <ul class="mb-0">
            <li>
                <i class="ki-duotone ki-calendar fs-6 text-success me-1"><span class="path1"></span><span class="path2"></span></i>
                <strong>{{ __('common.date_range') }}:</strong> {{ __('common.financial_filter_description') }}
            </li>
            <li>
                <i class="ki-duotone ki-arrows-circle fs-6 text-info me-1"><span class="path1"></span><span class="path2"></span></i>
                <strong>{{ __('common.sync_data') }}:</strong> {{ __('common.financial_sync_description') }}
            </li>
        </ul>
    </x-guide-alert>

    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="d-flex flex-stack w-100">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!-- Title moved to header -->
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!--begin::Filter-->
                <form action="{{ route('financial.index') }}" method="GET" class="d-flex align-items-center">
                    <div class="position-relative me-3">
                        <i class="ki-duotone ki-calendar-8 fs-2 position-absolute top-50 translate-middle-y ms-4">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span
                                class="path4"></span><span class="path5"></span><span class="path6"></span>
                        </i>
                        <input class="form-control form-control-solid ps-12" placeholder="Pick date range"
                            id="kt_daterangepicker_1" name="date_range" value="{{ $startDate }} - {{ $endDate }}" />
                        <input type="hidden" name="start_date" id="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" id="end_date" value="{{ $endDate }}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('common.filter') }}</button>
                </form>
                <!--end::Filter-->
                <button type="button" class="btn btn-sm fw-bold btn-primary" id="sync_data_btn">
                    {{ __('common.sync_data') }}
                </button>
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Row-->
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            <x-stat-card
                :value="$summaryStats['gross_sales']"
                :title="__('common.gross_sales')"
                icon="ki-basket"
                color="primary"
                :growth="$summaryStats['growth']['gross_sales'] ?? 0"
                :prev-period-label="$prevPeriodLabel"
            />
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            <x-stat-card
                :value="$summaryStats['net_profit']"
                :title="__('common.net_profit')"
                icon="ki-wallet"
                color="success"
                :growth="$summaryStats['growth']['net_profit'] ?? 0"
                :prev-period-label="$prevPeriodLabel"
            />
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-info">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span
                            class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ number_format($summaryStats['profit_margin'], 1) }}%</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('common.profit_margin') }}</span>
                    </div>
                </div>
                <div class="card-body pt-2 pb-4 d-flex align-items-center">
                    <div class="d-flex flex-center me-5 pt-2">
                        <div id="kt_card_widget_17_chart" style="min-width: 70px; min-height: 70px" data-kt-size="70"
                            data-kt-line="11"></div>
                    </div>
                    <div class="d-flex flex-column content-justify-center w-100">
                        <div class="d-flex fs-6 fw-semibold align-items-center">
                            <div class="bullet w-8px h-3px rounded-2 bg-success me-3"></div>
                            <div class="text-gray-500 flex-grow-1 me-4">{{ __('common.avg_daily_sales') }}</div>
                            <div class="fw-bolder text-gray-700 text-xxl-end">
                                {{ number_format($summaryStats['avg_daily_sales'], 2) }} ₺</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            <x-stat-card
                :value="$summaryStats['total_expense']"
                :title="__('common.total_expense')"
                icon="ki-graph-down"
                color="danger"
                :growth="$summaryStats['growth']['total_expense'] ?? 0"
                :prev-period-label="$prevPeriodLabel"
            />
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->

    <!--begin::Charts Row-->
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <!--begin::Col-->
        <div class="col-xl-8 mb-5 mb-xl-10">
            <div class="card card-flush h-xl-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">{{ __('common.sales_trend') }}</span>
                    </h3>
                </div>
                <div class="card-body pt-0 pb-5">
                    <div id="kt_charts_widget_3_chart" style="height: 350px"></div>
                </div>
            </div>
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xl-4 mb-5 mb-xl-10">
            <div class="card card-flush h-xl-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">{{ __('common.expense_breakdown') }}</span>
                    </h3>
                </div>
                <div class="card-body pt-0 pb-5">
                    <div id="kt_charts_widget_4_chart" style="height: 350px"></div>
                </div>
            </div>
        </div>
        <!--end::Col-->
    </div>
    <!--end::Charts Row-->
    <!--end::Content-->
@endsection

@push('scripts')
    <script>
        $("#kt_daterangepicker_1").daterangepicker({
            startDate: moment("{{ $startDate }}"),
            endDate: moment("{{ $endDate }}"),
            locale: {
                applyLabel: '{{ __('common.apply') }}',
                cancelLabel: '{{ __('common.cancel') }}',
                fromLabel: '{{ __('common.from') }}',
                toLabel: '{{ __('common.to') }}',
                customRangeLabel: '{{ __('common.custom') }}',
                daysOfWeek: ['{{ __('common.sun') }}', '{{ __('common.mon') }}', '{{ __('common.tue') }}',
                    '{{ __('common.wed') }}', '{{ __('common.thu') }}', '{{ __('common.fri') }}',
                    '{{ __('common.sat') }}'
                ],
                monthNames: ['{{ __('common.january') }}', '{{ __('common.february') }}',
                    '{{ __('common.march') }}', '{{ __('common.april') }}', '{{ __('common.may') }}',
                    '{{ __('common.june') }}', '{{ __('common.july') }}', '{{ __('common.august') }}',
                    '{{ __('common.september') }}', '{{ __('common.october') }}',
                    '{{ __('common.november') }}', '{{ __('common.december') }}'
                ],
                firstDay: 1,
                format: 'DD.MM.YYYY'
            },
            ranges: {
                '{{ __('common.today') }}': [moment(), moment()],
                '{{ __('common.yesterday') }}': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '{{ __('common.last_7_days') }}': [moment().subtract(6, 'days'), moment()],
                '{{ __('common.last_30_days') }}': [moment().subtract(29, 'days'), moment()],
                '{{ __('common.this_month') }}': [moment().startOf('month'), moment().endOf('month')],
                '{{ __('common.last_month') }}': [moment().subtract(1, 'month').startOf('month'), moment()
                    .subtract(1, 'month').endOf('month')
                ],
                '{{ __('common.all_time') }}': [moment('{{ $minDate }}'), moment()]
            }
        }, function(start, end, label) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
            $('#kt_daterangepicker_1').val(start.format('DD.MM.YYYY') + ' - ' + end.format('DD.MM.YYYY'));
            $('#kt_daterangepicker_1').closest('form').submit();
        });

        var element1 = document.getElementById('kt_charts_widget_3_chart');
        var element2 = document.getElementById('kt_charts_widget_4_chart');

        // Sales Trend Chart
        if (element1) {
            var options1 = {
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
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {},
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                fill: {
                    type: 'solid',
                    opacity: 0.1
                },
                stroke: {
                    curve: 'smooth',
                    show: true,
                    width: 3,
                    colors: ['#7239ea', '#50cd89']
                },
                xaxis: {
                    categories: @json($chartData['dates']),
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: '#A1A5B7',
                            fontSize: '12px'
                        }
                    },
                    crosshairs: {
                        position: 'front',
                        stroke: {
                            color: '#7239ea',
                            width: 1,
                            dashArray: 3
                        }
                    },
                    tooltip: {
                        enabled: true,
                        formatter: undefined,
                        offsetY: 0,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#A1A5B7',
                            fontSize: '12px'
                        }
                    }
                },
                states: {
                    normal: {
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    },
                    hover: {
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    },
                    active: {
                        allowMultipleDataPointsSelection: false,
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    }
                },
                tooltip: {
                    style: {
                        fontSize: '12px'
                    },
                    y: {
                        formatter: function(val) {
                            return val + ' ₺'
                        }
                    }
                },
                colors: ['#7239ea', '#50cd89'],
                grid: {
                    borderColor: '#EFF2F5',
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                markers: {
                    strokeColor: ['#7239ea', '#50cd89'],
                    strokeWidth: 3
                }
            };

            var chart1 = new ApexCharts(element1, options1);
            chart1.render();
        }

        // Expense Breakdown Chart
        if (element2) {
            var options2 = {
                series: [
                    {{ $chartData['expenses']['commission'] }},
                    {{ $chartData['expenses']['shipping'] }},
                    {{ $chartData['expenses']['platform'] }},
                    {{ $chartData['expenses']['intl_ops'] }},
                    {{ $chartData['expenses']['intl_service'] }},
                    {{ $chartData['expenses']['penalty'] }},
                    {{ $chartData['expenses']['other'] }}
                ],
                chart: {
                    type: 'donut',
                    height: 350
                },
                labels: [
                    '{{ __('common.commission') }}',
                    '{{ __('common.shipping') }}',
                    '{{ __('common.platform') }}',
                    '{{ __('common.intl_ops') }}',
                    '{{ __('common.intl_service') }}',
                    '{{ __('common.penalty') }}',
                    '{{ __('common.other') }}'
                ],
                colors: ['#F1416C', '#FFC700', '#7239ea', '#009EF7', '#50cd89', '#181C32', '#E4E6EF'],
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(2) + ' ₺'
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom'
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            var chart2 = new ApexCharts(element2, options2);
            chart2.render();
        }
    </script>
@endpush

@push('scripts')
    <script>
        $('#sync_data_btn').click(function() {
            var btn = $(this);
            btn.attr('data-kt-indicator', 'on');
            btn.prop('disabled', true);

            axios.post('{{ route('financial.sync') }}')
                .then(function(response) {
                    if (response.data.success) {
                        toastr.success(response.data.message);
                        // Optional: Reload page after a delay or show a message that it's running in background
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        toastr.error(response.data.message);
                        btn.removeAttr('data-kt-indicator');
                        btn.prop('disabled', false);
                    }
                })
                .catch(function(error) {
                    toastr.error('{{ __('common.error_occurred') }}');
                    btn.removeAttr('data-kt-indicator');
                    btn.prop('disabled', false);
                });
        });
    </script>
@endpush
