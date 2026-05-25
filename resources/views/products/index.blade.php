@extends('layouts.master')

@section('title', __('common.products'))

@section('styles')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    {{-- User Guidance Section --}}
    <div class="alert alert-dismissible bg-light-success d-flex flex-column flex-sm-row p-5 mb-5">
        <i class="ki-duotone ki-information-5 fs-2hx text-success me-4 mb-5 mb-sm-0">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="d-flex flex-column pe-0 pe-sm-10">
            <h4 class="mb-2 text-dark">{{ __('common.product_management_guide') }}</h4>
            <ul class="mb-0">
                <li><i class="ki-duotone ki-filter fs-6 text-primary me-1"><span class="path1"></span><span
                            class="path2"></span></i> <strong>{{ __('common.filter') }}:</strong>
                    {{ __('common.product_filter_description') }}</li>
                <li><i class="ki-duotone ki-arrows-circle fs-6 text-info me-1"><span class="path1"></span><span
                            class="path2"></span></i> <strong>{{ __('common.sync_data') }}:</strong>
                    {{ __('common.product_sync_description') }}</li>
            </ul>
        </div>
        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
            data-bs-dismiss="alert">
            <i class="ki-duotone ki-cross fs-1 text-success"><span class="path1"></span><span class="path2"></span></i>
        </button>
    </div>

    <!--begin::Content-->
    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4"><span class="path1"></span><span
                            class="path2"></span></i>
                    <input type="text" data-kt-product-table-filter="search"
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
                <button type="button" class="btn btn-primary" id="sync_products_btn">
                    <i class="ki-duotone ki-arrows-circle fs-2"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('common.sync_data') }}
                </button>
            </div>
        </div>

        <div class="collapse" id="kt_advanced_search_form">
            <div class="card-body py-0">
                <div class="row g-5 mb-5">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.brand') }}</label>
                        <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                            data-placeholder="{{ __('common.brand') }}" data-kt-product-table-filter="brand">
                            <option></option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.category') }}</label>
                        <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                            data-placeholder="{{ __('common.category') }}" data-kt-product-table-filter="category"
                            multiple="multiple">
                            <option></option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.price') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="form-control form-control-solid"
                                placeholder="{{ __('common.min_price') }}" data-kt-product-table-filter="price_min" />
                            <span class="text-gray-400">-</span>
                            <input type="number" class="form-control form-control-solid"
                                placeholder="{{ __('common.max_price') }}" data-kt-product-table-filter="price_max" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select class="form-select form-select-solid" data-control="select2" data-hide-search="true"
                            data-placeholder="{{ __('common.status') }}" data-kt-product-table-filter="status">
                            <option></option>
                            <option value="active">{{ __('common.active') }}</option>
                            <option value="inactive">{{ __('common.inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.stock') }}</label>
                        <div class="d-flex align-items-center gap-2" data-bs-toggle="tooltip"
                            title="{{ __('common.stock_less_than_tooltip') }}">
                            <input type="number" class="form-control form-control-solid"
                                placeholder="{{ __('common.stock_less_than') }}"
                                data-kt-product-table-filter="stock_less_than" />
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-flex gap-4 mb-3">
                            <div class="form-check form-check-custom form-check-solid form-check-sm">
                                <input class="form-check-input" type="checkbox" value="1" id="has_stock_filter"
                                    data-kt-product-table-filter="has_stock" />
                                <label class="form-check-label text-nowrap" for="has_stock_filter">
                                    {{ __('common.has_stock') }}
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid form-check-sm">
                                <input class="form-check-input" type="checkbox" value="1" id="no_stock_filter"
                                    data-kt-product-table-filter="no_stock" />
                                <label class="form-check-label text-nowrap" for="no_stock_filter">
                                    {{ __('common.no_stock') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary"
                            id="apply_filters_btn">{{ __('common.filter') }}</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_products_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th>{{ __('common.product') }}</th>
                        <th>{{ __('common.sku') }}</th>
                        <th>{{ __('common.brand') }}</th>
                        <th>{{ __('common.category') }}</th>
                        <th>{{ __('common.price') }}</th>
                        <th>{{ __('common.stock') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    <!-- Data loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
    <!--end::Content-->

    <!--begin::Edit Price/Stock Modal-->
    <div class="modal fade" id="kt_edit_price_stock_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="edit_product_title">{{ __('common.edit') }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body">
                    @include('components.production-warning')
                    <input type="hidden" id="edit_product_id" />
                    <div class="mb-5">
                        <label class="form-label">{{ __('common.stock') }}</label>
                        <input type="number" min="0" class="form-control form-control-solid" id="edit_stock" />
                    </div>
                    <div class="mb-5">
                        <label class="form-label">{{ __('common.price') }}</label>
                        <input type="number" min="0" step="0.01" class="form-control form-control-solid" id="edit_sale_price" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('common.list_price') ?? 'List Price' }}</label>
                        <input type="number" min="0" step="0.01" class="form-control form-control-solid" id="edit_list_price" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="save_price_stock_btn">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Edit Price/Stock Modal-->
@endsection

@push('scripts')
    @include('layouts.partials.scripts.datatables')
    <script>
        $(document).ready(function() {
            // Init Datatable
            var table = $('#kt_products_table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('products.data') }}',
                    data: function(d) {
                        d.brand = $('[data-kt-product-table-filter="brand"]').val();
                        d.category = $('[data-kt-product-table-filter="category"]').val();
                        d.price_min = $('[data-kt-product-table-filter="price_min"]').val();
                        d.price_max = $('[data-kt-product-table-filter="price_max"]').val();
                        d.stock_less_than = $('[data-kt-product-table-filter="stock_less_than"]').val();
                        d.no_stock = $('[data-kt-product-table-filter="no_stock"]').is(':checked') ? 1 :
                            0;
                        d.has_stock = $('[data-kt-product-table-filter="has_stock"]').is(':checked') ?
                            1 : 0;
                        d.status = $('[data-kt-product-table-filter="status"]').val();
                    }
                },
                columns: [{
                        data: 0
                    },
                    {
                        data: 1
                    }, // SKU
                    {
                        data: 2
                    },
                    {
                        data: 3
                    },
                    {
                        data: 4
                    },
                    {
                        data: 5
                    },
                    {
                        data: 6
                    },
                    {
                        data: 7,
                        orderable: false,
                        className: 'text-end'
                    }
                ],
                info: true,
                order: [],
                pageLength: 10,
                lengthChange: false,
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    } // Disable ordering on first column
                ]
            });

            // Search
            $('[data-kt-product-table-filter="search"]').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Apply Filters Button
            $('#apply_filters_btn').click(function() {
                table.draw();
            });

            // Open edit modal (delegated for AJAX-rendered rows)
            var editModal = new bootstrap.Modal(document.getElementById('kt_edit_price_stock_modal'));
            $(document).on('click', '.edit-price-stock-btn', function() {
                var btn = $(this);
                $('#edit_product_id').val(btn.data('id'));
                $('#edit_product_title').text(btn.data('title'));
                $('#edit_stock').val(btn.data('stock'));
                $('#edit_sale_price').val(btn.data('sale-price'));
                $('#edit_list_price').val(btn.data('list-price'));
                editModal.show();
            });

            // Save price/stock
            $('#save_price_stock_btn').click(function() {
                var btn = $(this);
                btn.attr('data-kt-indicator', 'on');
                btn.prop('disabled', true);

                axios.post('{{ route('products.update-price-stock') }}', {
                        product_id: $('#edit_product_id').val(),
                        stock: $('#edit_stock').val(),
                        sale_price: $('#edit_sale_price').val(),
                        list_price: $('#edit_list_price').val()
                    })
                    .then(function(response) {
                        if (response.data.success) {
                            toastr.success(response.data.message);
                            editModal.hide();
                            table.ajax.reload();
                        } else {
                            toastr.error(response.data.message);
                        }
                    })
                    .catch(function() {
                        toastr.error('{{ __('common.error_occurred') }}');
                    })
                    .finally(function() {
                        btn.removeAttr('data-kt-indicator');
                        btn.prop('disabled', false);
                    });
            });

            // Sync Button
            $('#sync_products_btn').click(function() {
                var btn = $(this);
                btn.attr('data-kt-indicator', 'on');
                btn.prop('disabled', true);

                axios.post('{{ route('products.sync') }}')
                    .then(function(response) {
                        if (response.data.success) {
                            toastr.success(response.data.message);
                            setTimeout(function() {
                                table.ajax.reload();
                            }, 1000);
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
        });
    </script>
@endpush
