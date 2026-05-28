@extends('layouts.master')

@section('title', $product->title . ' — ' . __('common.details'))

@section('content')
<div class="d-flex flex-column flex-lg-row mb-10">
    <div class="flex-lg-row-fluid">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-5">
                    <h1 class="mb-0 me-3">{{ $product->title }}</h1>
                    <span class="badge badge-light-primary fs-7">{{ $product->sku }}</span>
                </div>

                <div class="row g-5 mb-5">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless fs-7">
                            <tr>
                                <td class="text-gray-600 w-150px">{{ __('products.brand') }}</td>
                                <td class="fw-semibold">{{ $product->brand ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600">{{ __('products.barcode') }}</td>
                                <td class="fw-semibold">{{ $product->barcode ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600">{{ __('products.cost_price') }}</td>
                                <td class="fw-semibold">@money($product->cost_price)</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600">{{ __('products.vat_rate') }}</td>
                                <td class="fw-semibold">%{{ $product->vat_rate }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600">{{ __('products.weight') }}</td>
                                <td class="fw-semibold">{{ $product->weight_g }} g / {{ $product->desi }} desi</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless fs-7">
                            <tr>
                                <td class="text-gray-600 w-150px">{{ __('products.pricing_strategy') }}</td>
                                <td class="fw-semibold">{{ $product->pricing_strategy }}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600">{{ __('products.stock_buffer') }}</td>
                                <td class="fw-semibold">
                                    {{ $product->stock_buffer_strategy }}
                                    @if ($product->stock_buffer_value)
                                        ({{ $product->stock_buffer_value }})
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-gray-600">{{ __('products.packaging_cost') }}</td>
                                <td class="fw-semibold">@money($product->packaging_cost)</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h3 class="mb-4">{{ __('products.stock_views') }}</h3>
                <div class="row g-5 mb-5">
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-primary border-primary h-100">
                            <div class="card-body text-center">
                                <div class="text-primary fw-bold fs-7 mb-1">Cirotik</div>
                                <div class="fs-2x fw-bold mb-1">{{ $product->current_stock }}</div>
                                <div class="text-muted fs-8">@money($product->current_price)</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-warning border-warning h-100">
                            <div class="card-body text-center">
                                <div class="text-warning fw-bold fs-7 mb-1">{{ __('products.listed') }}</div>
                                <div class="fs-2x fw-bold mb-1">
                                    {{ $product->listings->sum('listed_stock') }}
                                </div>
                                <div class="text-muted fs-8">
                                    {{ $product->listings->count() }} {{ __('products.listings') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-success border-success h-100">
                            <div class="card-body text-center">
                                <div class="text-success fw-bold fs-7 mb-1">{{ __('products.live') }}</div>
                                <div class="fs-2x fw-bold mb-1">—</div>
                                <div class="text-muted fs-8">{{ __('products.click_refresh') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="mb-4">{{ __('products.marketplace_listings') }}</h3>
                <div class="row g-5">
                    @forelse ($product->listings as $listing)
                        <div class="col-xl-6">
                            <div class="card card-flush border">
                                <div class="card-header min-h-50px py-3">
                                    <div class="card-title d-flex align-items-center">
                                        <span class="symbol symbol-30px me-3">
                                            <span class="symbol-label bg-light">
                                                {{ strtoupper(substr($listing->credential?->marketplace?->name ?? '?', 0, 2)) }}
                                            </span>
                                        </span>
                                        <div>
                                            <span class="fs-6 fw-bold text-gray-800">
                                                {{ $listing->credential?->marketplace?->name ?? 'Unknown' }}
                                            </span>
                                            <span class="badge ms-2
                                                @if($listing->sync_status === 'synced') badge-light-success
                                                @elseif($listing->sync_status === 'failed') badge-light-danger
                                                @else badge-light-warning
                                                @endif fs-8">
                                                {{ $listing->sync_status }}
                                            </span>
                                        </div>
                                    </div>
                                    @if ($listing->listing_url)
                                        <a href="{{ $listing->listing_url }}" target="_blank"
                                           class="btn btn-sm btn-icon btn-light">
                                            <i class="ki-duotone ki-exit-right-corner fs-3">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </a>
                                    @endif
                                </div>
                                <div class="card-body py-3">
                                    <div class="row g-3 fs-7">
                                        <div class="col-6">
                                            <span class="text-gray-600">{{ __('products.sku') }}</span>
                                            <div class="fw-semibold">{{ $listing->remote_sku ?? '—' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-gray-600">{{ __('products.price') }}</span>
                                            <div class="fw-semibold">@money($listing->listed_price)</div>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-gray-600">{{ __('products.stock') }}</span>
                                            <div class="fw-semibold">
                                                <span class="badge
                                                    @if($listing->listed_stock > 10) badge-light-success
                                                    @elseif($listing->listed_stock > 0) badge-light-warning
                                                    @else badge-light-danger
                                                    @endif">
                                                    {{ $listing->listed_stock }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-gray-600">{{ __('products.status') }}</span>
                                            <div class="fw-semibold">
                                                <span class="badge
                                                    @if($listing->listing_status === 'active') badge-light-success
                                                    @else badge-light-danger
                                                    @endif">
                                                    {{ $listing->listing_status }}
                                                </span>
                                            </div>
                                        </div>
                                        @if ($listing->category_path)
                                            <div class="col-12">
                                                <span class="text-gray-600">{{ __('products.category') }}</span>
                                                <div class="fw-semibold text-truncate">{{ $listing->category_path }}</div>
                                            </div>
                                        @endif
                                        @if ($listing->last_synced_at)
                                            <div class="col-12">
                                                <span class="text-gray-600">{{ __('products.last_synced') }}</span>
                                                <div class="fw-semibold">{{ $listing->last_synced_at->diffForHumans() }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info">{{ __('products.no_listings') }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
