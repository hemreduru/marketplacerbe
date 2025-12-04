@if (count($questions) > 0)
    @foreach ($questions as $question)
        <div class="card card-flush mb-5">
            <div class="card-header align-items-center pt-5">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-45px symbol-circle me-3">
                        <span class="symbol-label bg-warning text-white fs-6 fw-bolder">T</span>
                    </div>
                    <div class="d-flex flex-column">
                        <span
                            class="text-gray-900 fw-bold fs-6">{{ $question['userName'] ?? __('common.customer') }}</span>
                        <span
                            class="text-gray-400 fw-semibold fs-7">{{ \Carbon\Carbon::createFromTimestampMs($question['creationDate'])->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
                <div class="card-toolbar gap-3">
                    <span
                        class="badge badge-light-warning fw-bold border border-warning text-warning">{{ $marketplaceName }}</span>
                    @if ($question['status'] == 'WAITING_FOR_ANSWER')
                        <span class="badge badge-light-warning">{{ __('common.waiting_answer') }}</span>
                    @elseif($question['status'] == 'ANSWERED')
                        <span class="badge badge-light-success">{{ __('common.answered') }}</span>
                    @elseif($question['status'] == 'REPORTED')
                        <span class="badge badge-light-danger">{{ __('common.reported') }}</span>
                    @else
                        <span class="badge badge-light-secondary">{{ $question['status'] }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body pt-5">
                <p class="text-gray-800 fw-normal fs-6 mb-5">
                    {{ $question['text'] }}
                </p>

                <!-- Product Info -->
                <div class="d-flex align-items-center bg-light-secondary rounded p-3 mb-5">
                    <div class="symbol symbol-50px me-3">
                        <span class="symbol-label"
                            style="background-image:url({{ $question['imageUrl'] ?? '' }});"></span>
                    </div>
                    <div class="d-flex flex-column">
                        <a href="{{ $question['webUrl'] ?? '#' }}" target="_blank"
                            class="text-gray-800 text-hover-primary fw-bold fs-7">{{ $question['productName'] ?? __('common.unknown_product') }}</a>
                    </div>
                </div>

                @if ($question['status'] == 'WAITING_FOR_ANSWER')
                    <!-- Answer Form (Toggle) -->
                    <div class="separator mb-5"></div>
                    <button class="btn btn-sm btn-light-primary mb-3 toggle-answer-btn"
                        data-target="#answer_form_{{ $question['id'] }}">
                        <i class="ki-duotone ki-message-text-2 fs-2"><span class="path1"></span><span
                                class="path2"></span><span class="path3"></span></i>
                        {{ __('common.reply') }}
                    </button>

                    <div class="d-none" id="answer_form_{{ $question['id'] }}">
                        <textarea class="form-control form-control-solid mb-3" rows="3"
                            placeholder="{{ __('common.write_your_answer') }}"></textarea>

                        @include('components.production-warning')

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary btn-sm send-answer-btn" data-id="{{ $question['id'] }}">
                                {{ __('common.send') }}
                            </button>
                        </div>
                    </div>
                @endif

                @if (isset($question['answer']) && !empty($question['answer']))
                    <div class="separator mb-5"></div>
                    <div class="bg-light-success rounded p-4">
                        <span class="fw-bold text-gray-800 d-block mb-2">{{ __('common.answer') }}:</span>
                        <p class="text-gray-700 m-0">{{ $question['answer']['text'] }}</p>
                        <span
                            class="text-gray-400 fs-8 mt-2 d-block">{{ \Carbon\Carbon::createFromTimestampMs($question['answer']['creationDate'])->format('d.m.Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@else
    <div class="card">
        <div class="card-body text-center py-10">
            <span class="text-gray-400 fs-4">{{ __('common.no_data') }}</span>
        </div>
    </div>
@endif

<!-- Pagination -->
@if ($totalPages > 1)
    <div class="d-flex justify-content-center mt-5">
        <ul class="pagination">
            @if ($page > 0)
                <li class="page-item previous"><a
                        href="{{ route('questions.index', ['status' => $status, 'page' => $page - 1]) }}"
                        class="page-link"><i class="previous"></i></a></li>
            @endif

            <li class="page-item active"><a href="#" class="page-link">{{ $page + 1 }}</a></li>

            @if ($page < $totalPages - 1)
                <li class="page-item next"><a
                        href="{{ route('questions.index', ['status' => $status, 'page' => $page + 1]) }}"
                        class="page-link"><i class="next"></i></a></li>
            @endif
        </ul>
    </div>
@endif
