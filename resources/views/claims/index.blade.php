@extends('layouts.master')

@section('title', __('common.claims'))

@section('content')
    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4"><span class="path1"></span><span
                            class="path2"></span></i>
                    <input type="text" data-kt-claim-table-filter="search"
                        class="form-control form-control-solid w-250px ps-12" placeholder="{{ __('common.search') }}" />
                </div>
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <select class="form-select form-select-solid w-200px" data-control="select2" data-hide-search="true"
                    data-placeholder="{{ __('common.status') }}" data-kt-claim-table-filter="status">
                    <option value="All">{{ __('common.all') }}</option>
                    <option value="Accepted">{{ __('common.accepted') }}</option>
                    <option value="Cancelled">{{ __('common.cancelled') }}</option>
                    <option value="Rejected">{{ __('common.rejected') }}</option>
                </select>
                <button type="button" class="btn btn-primary" id="sync_claims_btn">
                    <i class="ki-duotone ki-arrows-circle fs-2"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('common.sync_data') }}
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_claims_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-100px">{{ __('common.order_number') }}</th>
                        <th class="min-w-150px">{{ __('common.customer') }}</th>
                        <th class="min-w-80px">{{ __('common.quantity') }}</th>
                        <th class="min-w-100px">{{ __('common.status') }}</th>
                        <th class="min-w-100px">{{ __('common.date') }}</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @include('layouts.partials.scripts.datatables')
    <script>
        $(document).ready(function() {
            var table = $('#kt_claims_table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('claims.data') }}",
                    data: function(d) {
                        d.status = $('[data-kt-claim-table-filter="status"]').val();
                    }
                },
                columns: [
                    { data: 0 },
                    { data: 1, orderable: false },
                    { data: 2 },
                    { data: 3 },
                    { data: 4 },
                ],
                order: [[4, 'desc']],
                language: {
                    url: "{{ asset('assets/js/custom/datatables-localization.js') }}"
                }
            });

            $('[data-kt-claim-table-filter="status"]').on('change', function() {
                table.draw();
            });

            $('[data-kt-claim-table-filter="search"]').on('keyup', function() {
                table.search($(this).val()).draw();
            });

            $('#sync_claims_btn').click(function() {
                var btn = $(this);
                btn.attr('data-kt-indicator', 'on');
                btn.prop('disabled', true);

                axios.post('{{ route('claims.sync') }}')
                    .then(function(response) {
                        toastr.success(response.data.message);
                        table.draw();
                    })
                    .catch(function() {
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
