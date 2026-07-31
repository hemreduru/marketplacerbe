@extends('layouts.master')

@section('title', __('master_products.cost_entry_title'))

@section('content')
    @if(session('success'))
        <div class="alert alert-success d-flex flex-stack flex-wrap gap-3 mb-6">
            <span class="fw-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="card">
        <div class="card-header align-items-center">
            <div class="card-title flex-column">
                <h3 class="fw-bold mb-1">{{ __('master_products.cost_entry_title') }}</h3>
                <span class="text-muted fs-7">{{ __('master_products.cost_entry_subtitle') }}</span>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="card-body">
                <div class="text-center py-10">
                    <div class="fs-3 fw-bold text-gray-800 mb-2">{{ __('master_products.all_costs_entered') }}</div>
                    <div class="text-muted">{{ __('master_products.all_costs_entered_hint') }}</div>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('cost-entry.update') }}">
                @csrf
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gy-3 mb-0">
                            <thead>
                                <tr class="fw-semibold text-muted fs-7 text-uppercase">
                                    <th>{{ __('master_products.product') }}</th>
                                    <th>{{ __('master_products.sku') }}</th>
                                    <th class="text-end">{{ __('master_products.current_price') }}</th>
                                    <th class="text-end w-175px">{{ __('master_products.cost_price') }} (₺)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td class="fw-semibold text-gray-800">{{ $product->title ?: '—' }}</td>
                                        <td class="text-muted">{{ $product->sku ?: $product->barcode }}</td>
                                        <td class="text-end fw-semibold" style="font-variant-numeric: tabular-nums;">
                                            {{ number_format((float) $product->current_price, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end">
                                            <label class="visually-hidden" for="cost-{{ $product->id }}">
                                                {{ __('master_products.cost_price') }} — {{ $product->sku }}
                                            </label>
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                id="cost-{{ $product->id }}"
                                                name="costs[{{ $product->id }}]"
                                                class="form-control text-end @error('costs.'.$product->id) is-invalid @enderror"
                                                style="font-variant-numeric: tabular-nums;"
                                                placeholder="0,00" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-stack flex-wrap gap-3 pt-6">
                        <div class="text-muted fs-7">{{ __('master_products.cost_help') }}</div>
                        <button type="submit" class="btn btn-primary">{{ __('master_products.save') }}</button>
                    </div>
                </div>
            </form>

            <div class="card-footer d-flex justify-content-end">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
