@extends('layouts.master')

@section('title', __('common.questions'))

@section('content')
    <x-guide-alert color="warning" icon="ki-information-5" :title="__('common.questions_management_guide')">
        <ul class="mb-0">
            <li>
                <i class="ki-duotone ki-message-text-2 fs-6 text-primary me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                <strong>{{ __('common.answer') }}:</strong> {{ __('common.question_answer_description') }}
            </li>
            <li>
                <i class="ki-duotone ki-arrows-circle fs-6 text-info me-1"><span class="path1"></span><span class="path2"></span></i>
                <strong>{{ __('common.sync_data') }}:</strong> {{ __('common.question_sync_description') }}
            </li>
        </ul>
    </x-guide-alert>

    <div class="d-flex flex-column flex-lg-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-lg-250px w-xl-300px mb-10 mb-lg-0">
            <div class="card card-flush">
                <div class="card-header pt-5" id="kt_chat_contacts_header">
                    <div class="card-title">
                        <h2>{{ __('common.filter') }}</h2>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-icon btn-light-primary" id="refresh_questions_btn"
                            data-bs-toggle="tooltip" title="{{ __('common.refresh') }}">
                            <i class="ki-duotone ki-arrows-circle fs-2"><span class="path1"></span><span
                                    class="path2"></span></i>
                        </button>
                    </div>
                </div>
                <div class="card-body pt-5">
                    <div class="d-flex flex-column gap-5">
                        <a href="{{ route('questions.index', ['status' => 'WAITING_FOR_ANSWER']) }}"
                            class="d-flex align-items-center text-gray-900 text-hover-primary me-5 mb-2 {{ $status == 'WAITING_FOR_ANSWER' ? 'active fw-bold text-primary' : '' }}">
                            <i
                                class="ki-duotone ki-sms fs-2 me-3 {{ $status == 'WAITING_FOR_ANSWER' ? 'text-primary' : 'text-gray-400' }}"><span
                                    class="path1"></span><span class="path2"></span></i>
                            <span class="fw-bold">{{ __('common.waiting_answer') }}</span>
                        </a>
                        <a href="{{ route('questions.index', ['status' => 'ANSWERED']) }}"
                            class="d-flex align-items-center text-gray-900 text-hover-primary me-5 mb-2 {{ $status == 'ANSWERED' ? 'active fw-bold text-primary' : '' }}">
                            <i
                                class="ki-duotone ki-check-circle fs-2 me-3 {{ $status == 'ANSWERED' ? 'text-primary' : 'text-gray-400' }}"><span
                                    class="path1"></span><span class="path2"></span></i>
                            <span class="fw-bold">{{ __('common.answered') }}</span>
                        </a>
                        <a href="{{ route('questions.index', ['status' => 'REPORTED']) }}"
                            class="d-flex align-items-center text-gray-900 text-hover-primary me-5 mb-2 {{ $status == 'REPORTED' ? 'active fw-bold text-primary' : '' }}">
                            <i
                                class="ki-duotone ki-flag fs-2 me-3 {{ $status == 'REPORTED' ? 'text-primary' : 'text-gray-400' }}"><span
                                    class="path1"></span><span class="path2"></span></i>
                            <span class="fw-bold">{{ __('common.reported') }}</span>
                        </a>
                        <a href="{{ route('questions.index', ['status' => 'REJECTED']) }}"
                            class="d-flex align-items-center text-gray-900 text-hover-primary me-5 mb-2 {{ $status == 'REJECTED' ? 'active fw-bold text-primary' : '' }}">
                            <i
                                class="ki-duotone ki-cross-circle fs-2 me-3 {{ $status == 'REJECTED' ? 'text-primary' : 'text-gray-400' }}"><span
                                    class="path1"></span><span class="path2"></span></i>
                            <span class="fw-bold">{{ __('common.rejected') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
            <!--begin::Questions List-->
            <div class="d-flex flex-column gap-5" id="questions_container">
                @include('questions.list')
            </div>
            <!--end::Questions List-->
        </div>
        <!--end::Content-->
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Toggle Answer Form (Delegated for AJAX content)
            $(document).on('click', '.toggle-answer-btn', function() {
                var target = $(this).data('target');
                $(target).toggleClass('d-none');
                $(this).hide(); // Hide reply button after clicking
            });

            // Send Answer (Delegated)
            $(document).on('click', '.send-answer-btn', function() {
                var btn = $(this);
                var id = btn.data('id');
                var container = btn.closest('div[id^="answer_form_"]');
                var text = container.find('textarea').val();

                if (!text || text.length < 2) {
                    toastr.warning('Lütfen bir cevap yazın.');
                    return;
                }

                btn.attr('data-kt-indicator', 'on');
                btn.prop('disabled', true);

                axios.post('{{ route('questions.answer') }}', {
                        question_id: id,
                        answer: text
                    })
                    .then(function(response) {
                        if (response.data.success) {
                            toastr.success(response.data.message);
                            setTimeout(function() {
                                // Refresh list instead of reload
                                $('#refresh_questions_btn').click();
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

            // Refresh Button: pull latest from the marketplace, then re-render the list.
            $('#refresh_questions_btn').click(function() {
                var btn = $(this);
                var icon = btn.find('i');

                icon.addClass('spin');
                btn.attr('data-kt-indicator', 'on');

                axios.post('{{ route('questions.sync') }}')
                    .then(function() {
                        return axios.get('{{ route('questions.index') }}', {
                            params: {
                                status: '{{ $status }}',
                                page: 0,
                                partial: 1
                            }
                        });
                    })
                    .then(function(response) {
                        $('#questions_container').html(response.data);
                        toastr.success('{{ __('common.updated') }}');
                    })
                    .catch(function(error) {
                        toastr.error('{{ __('common.error_occurred') }}');
                    })
                    .finally(function() {
                        btn.removeAttr('data-kt-indicator');
                        icon.removeClass('spin');
                    });
            });
        });
    </script>
@endpush
