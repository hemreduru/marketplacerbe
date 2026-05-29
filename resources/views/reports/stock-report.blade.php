@extends('layouts.master')

@section('title', __('reports.stock_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">{{ __('reports.stock_report') }}</h1>
            <a href="{{ route('reports.stock.po') }}" class="btn btn-sm btn-light-primary">{{ __('reports.generate_po') }}</a>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="card mb-5">
                <div class="card-body py-3">
                    <div class="btn-group btn-group-sm" role="group">
                        @foreach(['all', 'critical', 'zero', 'dead'] as $f)
                            <a href="{{ route('reports.stock', ['filter' => $f]) }}"
                               class="btn btn-sm {{ $filter === $f ? 'btn-primary' : 'btn-light' }}">
                                {{ __('reports.stock_filter_' . $f) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if($rows->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-3 gy-2">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4">SKU</th>
                                    <th>{{ __('reports.product') }}</th>
                                    <th>{{ __('reports.current_stock') }}</th>
                                    <th>{{ __('reports.listed_stock') }}</th>
                                    <th>{{ __('reports.sales_velocity') }}</th>
                                    <th>{{ __('reports.days_to_depletion') }}</th>
                                    <th>{{ __('reports.last_sale') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}
                                        @if($row['is_dead'])<span class="badge badge-light-warning ms-1">{{ __('reports.dead_stock') }}</span>@endif
                                    </td>
                                    <td class="fw-bold {{ $row['is_critical'] ? 'text-danger' : '' }}">
                                        {{ $row['current_stock'] }}
                                        @if($row['is_critical'])<span class="badge badge-light-danger ms-1">{{ __('reports.critical') }}</span>@endif
                                    </td>
                                    <td>{{ $row['listed_stock'] }}</td>
                                    <td>{{ $row['velocity'] }}</td>
                                    <td>{{ $row['days_to_depletion'] !== null ? $row['days_to_depletion'] : '—' }}</td>
                                    <td>{{ $row['last_sale']?->format('d.m.Y') ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-10"><p class="text-gray-500">{{ __('reports.no_data') }}</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
