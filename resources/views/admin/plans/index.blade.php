@extends('layouts.master')

@section('title', 'Plan Yönetimi')

@section('content')
    @if(session('success'))
        <div class="alert alert-dismissible bg-light-success border border-success d-flex align-items-center p-5 mb-8" style="border-radius: 1rem;">
            <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
            <div class="d-flex flex-column">
                <span class="fw-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                <i class="ki-duotone ki-cross fs-1 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        </div>
    @endif

    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2">
            <div class="card-title">
                <h2 class="fw-bold">Plan Yönetimi</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 min-w-200px rounded-start">Plan</th>
                            <th class="min-w-100px">Aylık Fiyat</th>
                            <th class="min-w-100px">Yıllık Fiyat</th>
                            <th class="min-w-80px">Pazaryeri</th>
                            <th class="min-w-80px">Sipariş/ay</th>
                            <th class="min-w-80px">Ürün</th>
                            <th class="min-w-80px">Özellikler</th>
                            <th class="min-w-80px">Trial (gün)</th>
                            <th class="min-w-80px">Durum</th>
                            <th class="min-w-80px text-end rounded-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $plan->display_name }}</span>
                                            <span class="text-muted fw-semibold fs-7">{{ $plan->name }}</span>
                                        </div>
                                        @if($plan->is_popular)
                                            <span class="badge badge-light-primary ms-2">Popüler</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="text-gray-800 fw-bold">₺{{ number_format($plan->price_monthly, 2, ',', '.') }}</span>
                                </td>
                                <td>
                                    @if($plan->price_yearly)
                                        <span class="text-gray-600">₺{{ number_format($plan->price_yearly, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($plan->isUnlimited('marketplaces'))
                                        <span class="badge badge-light-success">Sınırsız</span>
                                    @else
                                        <span class="fw-semibold">{{ $plan->getLimit('marketplaces') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($plan->isUnlimited('orders'))
                                        <span class="badge badge-light-success">Sınırsız</span>
                                    @else
                                        <span class="fw-semibold">{{ number_format($plan->getLimit('orders'), 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($plan->isUnlimited('products'))
                                        <span class="badge badge-light-success">Sınırsız</span>
                                    @else
                                        <span class="fw-semibold">{{ number_format($plan->getLimit('products'), 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($plan->features as $key => $value)
                                        @if($value)
                                            <span class="badge badge-light-primary me-1 mb-1">{{ $key }}</span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $plan->trial_days }}</span>
                                </td>
                                <td>
                                    @if($plan->is_active)
                                        <span class="badge badge-light-success">Aktif</span>
                                    @else
                                        <span class="badge badge-light-danger">Pasif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-light btn-active-light-primary btn-sm">
                                        Düzenle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
