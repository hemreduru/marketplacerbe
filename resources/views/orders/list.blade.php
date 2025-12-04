@if (count($orders) > 0)
    @foreach ($orders as $order)
        <div class="card card-flush mb-5">
            <div class="card-header align-items-center pt-5">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-45px symbol-circle me-3">
                        <span class="symbol-label bg-warning text-white fs-6 fw-bolder">T</span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-gray-900 fw-bold fs-6">{{ __('common.order_number') }}:
                            {{ $order['orderNumber'] }}</span>
                        <span
                            class="text-gray-400 fw-semibold fs-7">{{ \Carbon\Carbon::createFromTimestampMs($order['orderDate'])->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
                <div class="card-toolbar gap-3">
                    <span
                        class="badge badge-light-warning fw-bold border border-warning text-warning">{{ $marketplaceName }}</span>
                    <span class="badge badge-light-primary">{{ $order['status'] }}</span>
                </div>
            </div>
            <div class="card-body pt-5">
                <div class="d-flex flex-wrap gap-5 mb-5">
                    <div class="flex-grow-1">
                        <h5 class="text-gray-800">{{ __('common.customer') }}</h5>
                        <span class="text-gray-600">{{ $order['customerFirstName'] }}
                            {{ $order['customerLastName'] }}</span>
                        <br>
                        <span class="text-gray-400 fs-7">{{ $order['shipmentAddress']['fullAddress'] ?? '' }}</span>
                    </div>
                    <div class="flex-grow-1 text-end">
                        <h5 class="text-gray-800">{{ __('common.amount') }}</h5>
                        <span class="text-success fw-bold fs-4">{{ number_format($order['totalPrice'], 2) }}
                            {{ $order['currencyCode'] }}</span>
                    </div>
                </div>

                <div class="separator mb-5"></div>

                <h5 class="mb-3">{{ __('common.items') }}</h5>
                @foreach ($order['lines'] as $line)
                    <div class="d-flex align-items-center bg-light-secondary rounded p-3 mb-2">
                        <div class="symbol symbol-50px me-3">
                            <!-- Assuming first image from array if available, or placeholder -->
                            <!-- Trendyol API structure for lines usually has 'images' array or similar, simplified here -->
                            <span
                                class="symbol-label bg-light-primary text-primary fw-bold">{{ substr($line['productName'], 0, 1) }}</span>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="text-gray-800 fw-bold fs-7">{{ $line['productName'] }}</span>
                            <span class="text-gray-400 fs-8">SKU: {{ $line['merchantSku'] }} | Qty:
                                {{ $line['quantity'] }}</span>
                        </div>
                        <span class="fw-bold text-gray-800">{{ number_format($line['price'], 2) }}
                            {{ $line['currencyCode'] }}</span>
                    </div>
                @endforeach

                <div class="separator mb-5 mt-5"></div>

                <!-- Actions -->
                <div class="d-flex flex-column">
                    @include('components.production-warning')

                    <div class="d-flex gap-3 justify-content-end">
                        @if ($order['status'] == 'Created')
                            <button class="btn btn-sm btn-light-primary update-status-btn"
                                data-id="{{ $order['id'] }}" data-status="Picking">
                                {{ __('common.picking') }}
                            </button>
                        @endif

                        @if ($order['status'] == 'Picking')
                            <button class="btn btn-sm btn-light-info update-status-btn" data-id="{{ $order['id'] }}"
                                data-status="Invoiced">
                                {{ __('common.invoiced') }}
                            </button>
                        @endif

                        @if (!empty($order['cargoTrackingNumber']))
                            <button class="btn btn-sm btn-secondary get-label-btn"
                                data-tracking="{{ $order['cargoTrackingNumber'] }}">
                                <i class="ki-duotone ki-document fs-2"><span class="path1"></span><span
                                        class="path2"></span></i>
                                {{ __('common.get_label') }}
                            </button>
                        @endif
                    </div>
                </div>
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
                        href="{{ route('orders.index', ['status' => $status, 'page' => $page - 1]) }}"
                        class="page-link"><i class="previous"></i></a></li>
            @endif

            <li class="page-item active"><a href="#" class="page-link">{{ $page + 1 }}</a></li>

            @if ($page < $totalPages - 1)
                <li class="page-item next"><a
                        href="{{ route('orders.index', ['status' => $status, 'page' => $page + 1]) }}"
                        class="page-link"><i class="next"></i></a></li>
            @endif
        </ul>
    </div>
@endif
