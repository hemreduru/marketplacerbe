@extends('layouts.master')

@section('title', __('reports.order_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                {{ __('reports.order_report') }}
                <span class="text-gray-500 fw-semibold fs-7 mt-1">{{ $from }} — {{ $to }}</span>
            </h1>
            <a href="{{ route('reports.order.export', request()->query()) }}" class="btn btn-sm btn-light-primary">
                {{ __('reports.export_csv') }}
            </a>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">

            @foreach (['success', 'info', 'error'] as $flash)
                @if (session($flash))
                    <div class="alert alert-{{ $flash === 'error' ? 'danger' : $flash }}">{{ session($flash) }}</div>
                @endif
            @endforeach

            {{-- Filtreler --}}
            <div class="card mb-5">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fs-8 text-muted">{{ __('reports.period') }}</label>
                            <select name="period" class="form-select form-select-sm">
                                @foreach(\App\Services\Reports\ReportPeriod::availableKeys() as $key)
                                    <option value="{{ $key }}" @selected($period === $key)>{{ __('reports.period_' . $key) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-8 text-muted">{{ __('reports.from') }}</label>
                            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-8 text-muted">{{ __('reports.to') }}</label>
                            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-8 text-muted">{{ __('common.marketplace') }}</label>
                            <select name="marketplace_id" class="form-select form-select-sm">
                                <option value="">{{ __('reports.all') }}</option>
                                @foreach($marketplaces as $mp)
                                    <option value="{{ $mp->id }}" @selected((string)($filters['marketplace_id'] ?? '') === (string)$mp->id)>{{ $mp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-8 text-muted">{{ __('reports.status') }}</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">{{ __('reports.all') }}</option>
                                @foreach($statuses as $st)
                                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-8 text-muted">{{ __('reports.search') }}</label>
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('reports.order_or_customer') }}" />
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('reports.apply') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Toplu işlem + tablo --}}
            <form method="POST" action="{{ route('reports.order.bulk') }}">
                @csrf
                <div class="card">
                    <div class="card-header align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <select name="action" class="form-select form-select-sm w-auto">
                                <option value="status">{{ __('reports.bulk_update_status') }}</option>
                                <option value="invoice">{{ __('reports.bulk_invoice') }}</option>
                                <option value="cargo">{{ __('reports.bulk_cargo') }}</option>
                            </select>
                            <input type="text" name="new_status" class="form-control form-control-sm w-auto" placeholder="{{ __('reports.new_status') }}" />
                            <button type="submit" class="btn btn-sm btn-light-primary">{{ __('reports.apply_bulk') }}</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($orders->total() > 0)
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-3 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4" style="width:32px"><input type="checkbox" id="checkAll" class="form-check-input" /></th>
                                        <th>{{ __('reports.order_no') }}</th>
                                        <th>{{ __('common.marketplace') }}</th>
                                        <th>{{ __('reports.date') }}</th>
                                        <th>{{ __('reports.customer') }}</th>
                                        <th>{{ __('reports.city') }}</th>
                                        <th>{{ __('reports.items') }}</th>
                                        <th>{{ __('reports.amount') }}</th>
                                        <th>{{ __('reports.status') }}</th>
                                        <th>{{ __('reports.net_profit') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                    <tr>
                                        <td class="ps-4"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="form-check-input row-check" /></td>
                                        <td class="fw-bold">{{ $order->order_number }}</td>
                                        <td>{{ $order->marketplace?->name }}</td>
                                        <td>{{ $order->order_date?->format('d.m.Y H:i') }}</td>
                                        <td>{{ $order->customer_first_name }} {{ \Illuminate\Support\Str::of($order->customer_last_name ?? '')->substr(0, 1)->upper() }}.</td>
                                        <td>{{ $order->shipping_city }}</td>
                                        <td>{{ $order->items_count }}</td>
                                        <td>@money($order->total_amount)</td>
                                        <td><span class="badge badge-light">{{ $order->status }}</span></td>
                                        @php($np = (float)($netProfitMap[$order->id] ?? 0))
                                        <td class="fw-bold {{ $np >= 0 ? 'text-success' : 'text-danger' }}">@money($np)</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end p-4">
                            {{ $orders->links() }}
                        </div>
                        @else
                        <div class="text-center py-10"><p class="text-gray-500">{{ __('reports.no_data') }}</p></div>
                        @endif
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    document.getElementById('checkAll')?.addEventListener('change', function (e) {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
    });
</script>
@endsection
