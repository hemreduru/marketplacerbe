@extends('layouts.master')

@section('title', __('reports.buybox_tracker'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">{{ __('reports.buybox_tracker') }}</h1>
            <form method="POST" action="{{ route('reports.buybox.sync') }}">@csrf
                <button class="btn btn-sm btn-light-primary">{{ __('reports.sync_buybox') }}</button>
            </form>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

            @if($lostCount > 0)
            <div class="alert alert-warning">
                {{ __('reports.lost_buybox_count') }}: <strong>{{ $lostCount }}</strong>
            </div>
            @endif

            <div class="card">
                <div class="card-body p-0">
                    @if($rows->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-3 gy-2">
                            <thead><tr class="fw-bold text-muted bg-light">
                                <th class="ps-4">SKU</th><th>{{ __('reports.product') }}</th>
                                <th>{{ __('reports.has_buybox') }}</th><th>{{ __('reports.our_price') }}</th>
                                <th>{{ __('reports.competitor_price') }}</th><th>{{ __('reports.date') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach($rows as $row)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $row['sku'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td>
                                        @if($row['has_buybox'])
                                            <span class="badge badge-light-success">{{ __('reports.has_buybox') }}</span>
                                        @else
                                            <span class="badge badge-light-danger">{{ __('reports.lost_buybox') }}</span>
                                        @endif
                                    </td>
                                    <td>@money($row['our_price'])</td>
                                    <td>{{ $row['competitor_price'] !== null ? '₺'.number_format((float)$row['competitor_price'], 2, ',', '.') : '—' }}</td>
                                    <td>{{ $row['checked_at']?->format('d.m.Y H:i') ?? '—' }}</td>
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
