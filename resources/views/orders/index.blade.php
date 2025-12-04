@extends('layouts.master')

@section('title', __('common.orders'))

@section('content')
@section('content')
    {{-- User Guidance Section --}}
    <div class="alert alert-dismissible bg-light-info d-flex flex-column flex-sm-row p-5 mb-5">
        <i class="ki-duotone ki-information-5 fs-2hx text-info me-4 mb-5 mb-sm-0">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="d-flex flex-column pe-0 pe-sm-10">
            <h4 class="mb-2 text-dark">{{ __('common.order_actions_guide') }}</h4>
            <ul class="mb-0">
                <li><i class="ki-duotone ki-check fs-6 text-success me-1"><span class="path1"></span><span
                            class="path2"></span></i> <strong>{{ __('common.picking') }}:</strong>
                    {{ __('common.picking_description') }}</li>
                <li><i class="ki-duotone ki-file fs-6 text-warning me-1"><span class="path1"></span><span
                            class="path2"></span></i> <strong>{{ __('common.invoiced') }}:</strong>
                    {{ __('common.invoiced_description') }}</li>
                <li><i class="ki-duotone ki-printer fs-6 text-primary me-1"><span class="path1"></span><span
                            class="path2"></span><span class="path3"></span><span class="path4"></span><span
                            class="path5"></span></i> <strong>{{ __('common.get_label') }}:</strong>
                    {{ __('common.get_label_description') }}</li>
                <li><i class="ki-duotone ki-arrows-circle fs-6 text-info me-1"><span class="path1"></span><span
                            class="path2"></span></i> <strong>{{ __('common.sync_data') }}:</strong>
                    {{ __('common.sync_description') }}</li>
            </ul>
        </div>
        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
            data-bs-dismiss="alert">
            <i class="ki-duotone ki-cross fs-1 text-info"><span class="path1"></span><span class="path2"></span></i>
        </button>
    </div>

    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4"><span class="path1"></span><span
                            class="path2"></span></i>
                    <input type="text" data-kt-order-table-filter="search"
                        class="form-control form-control-solid w-250px ps-12" placeholder="{{ __('common.search') }}" />
                </div>
                <!--end::Search-->
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <button type="button" class="btn btn-light-primary" data-bs-toggle="collapse"
                    data-bs-target="#kt_advanced_search_form">
                    <i class="ki-duotone ki-filter fs-2"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('common.filter') }}
                </button>
                <button type="button" class="btn btn-primary" id="sync_orders_btn">
                    <i class="ki-duotone ki-arrows-circle fs-2"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('common.sync_data') }}
                </button>
            </div>
        </div>

        <div class="collapse" id="kt_advanced_search_form">
            <div class="card-body py-0">
                <div class="row g-5 mb-5">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                            data-placeholder="{{ __('common.status') }}" data-kt-order-table-filter="status">
                            <option></option>
                            <option value="All">{{ __('common.all') }}</option>
                            <option value="Created">{{ __('common.created') }}</option>
                            <option value="Picking">{{ __('common.picking') }}</option>
                            <option value="Invoiced">{{ __('common.invoiced') }}</option>
                            <option value="Shipped">{{ __('common.shipped') }}</option>
                            <option value="Cancelled">{{ __('common.cancelled') }}</option>
                            <option value="Delivered">{{ __('common.delivered') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.date_range') }}</label>
                        <input class="form-control form-control-solid" placeholder="{{ __('common.pick_date_range') }}"
                            id="kt_daterangepicker_1" />
                    </div>
                    <div class="col-md-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                            data-kt-order-table-filter="reset">{{ __('common.reset') }}</button>
                        <button type="button" class="btn btn-primary fw-semibold px-6"
                            data-kt-order-table-filter="filter">{{ __('common.apply') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_orders_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-100px">{{ __('common.order_number') }}</th>
                        <th class="min-w-100px">{{ __('common.marketplace') }}</th>
                        <th class="min-w-100px">{{ __('common.date') }}</th>
                        <th class="min-w-150px">{{ __('common.customer') }}</th>
                        <th class="min-w-250px">{{ __('common.items') }}</th>
                        <th class="min-w-100px">{{ __('common.sku') }}</th>
                        <th class="min-w-80px">{{ __('common.quantity') }}</th>
                        <th class="min-w-100px">{{ __('common.amount') }}</th>
                        <th class="min-w-100px">{{ __('common.status') }}</th>
                        <th class="text-end min-w-100px">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @include('layouts.partials.scripts.datatables')
    <script>
        $(document).ready(function() {
            var table = $('#kt_orders_table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('orders.data') }}",
                    data: function(d) {
                        d.status = $('[data-kt-order-table-filter="status"]').val();
                        var dateRange = $('#kt_daterangepicker_1').val();
                        if (dateRange) {
                            var dates = dateRange.split(' - ');
                            d.start_date = dates[0];
                            d.end_date = dates[1];
                        }
                    }
                },
                columns: [{
                        data: 0
                    }, // Order Number
                    {
                        data: 1,
                        orderable: false
                    }, // Marketplace
                    {
                        data: 2
                    }, // Date
                    {
                        data: 3,
                        orderable: false
                    }, // Customer
                    {
                        data: 4,
                        orderable: false
                    }, // Items
                    {
                        data: 5,
                        orderable: false
                    }, // SKU
                    {
                        data: 6
                    }, // Quantity
                    {
                        data: 7
                    }, // Amount
                    {
                        data: 8
                    }, // Status
                    {
                        data: 9,
                        orderable: false,
                        className: 'text-end'
                    }, // Actions
                ],
                order: [
                    [2, 'desc']
                ], // Order by date desc (index 2 is Date)
                language: {
                    url: "{{ asset('assets/js/custom/datatables-localization.js') }}"
                }
            });

            // Date Range Picker with Turkish localization
            $('#kt_daterangepicker_1').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: '{{ __('common.clear') }}',
                    applyLabel: '{{ __('common.apply') }}',
                    fromLabel: '{{ __('common.from') }}',
                    toLabel: '{{ __('common.to') }}',
                    customRangeLabel: '{{ __('common.custom') }}',
                    daysOfWeek: ['{{ __('common.sun') }}', '{{ __('common.mon') }}',
                        '{{ __('common.tue') }}', '{{ __('common.wed') }}', '{{ __('common.thu') }}',
                        '{{ __('common.fri') }}', '{{ __('common.sat') }}'
                    ],
                    monthNames: ['{{ __('common.january') }}', '{{ __('common.february') }}',
                        '{{ __('common.march') }}', '{{ __('common.april') }}',
                        '{{ __('common.may') }}', '{{ __('common.june') }}',
                        '{{ __('common.july') }}', '{{ __('common.august') }}',
                        '{{ __('common.september') }}', '{{ __('common.october') }}',
                        '{{ __('common.november') }}', '{{ __('common.december') }}'
                    ],
                    firstDay: 1,
                    format: 'DD.MM.YYYY'
                },
                ranges: {
                    '{{ __('common.today') }}': [moment(), moment()],
                    '{{ __('common.yesterday') }}': [moment().subtract(1, 'days'), moment().subtract(1,
                        'days')],
                    '{{ __('common.last_7_days') }}': [moment().subtract(6, 'days'), moment()],
                    '{{ __('common.last_30_days') }}': [moment().subtract(29, 'days'), moment()],
                    '{{ __('common.this_month') }}': [moment().startOf('month'), moment().endOf('month')],
                    '{{ __('common.last_month') }}': [moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month')
                    ]
                }
            });

            $('#kt_daterangepicker_1').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format(
                    'DD.MM.YYYY'));
                table.draw();
            });

            $('#kt_daterangepicker_1').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Filter Apply
            $('[data-kt-order-table-filter="filter"]').click(function() {
                table.draw();
            });

            // Filter Reset
            $('[data-kt-order-table-filter="reset"]').click(function() {
                $('[data-kt-order-table-filter="form"] select').val('').trigger('change');
                $('#kt_daterangepicker_1').val('');
                table.draw();
            });

            // Search
            $('[data-kt-order-table-filter="search"]').on('keyup', function() {
                table.search($(this).val()).draw();
            });

            // Sync Button
            $('#sync_orders_btn').click(function() {
                var btn = $(this);
                btn.attr('data-kt-indicator', 'on');
                btn.prop('disabled', true);

                axios.post('{{ route('orders.sync') }}')
                    .then(function(response) {
                        toastr.success(response.data.message);
                        // Reload table after a delay to allow job to start processing
                        setTimeout(function() {
                            table.draw();
                        }, 2000);
                    })
                    .catch(function(error) {
                        toastr.error('{{ __('common.error_occurred') }}');
                    })
                    .finally(function() {
                        btn.removeAttr('data-kt-indicator');
                        btn.prop('disabled', false);
                    });
            });
        });
    </script>
@endpush
