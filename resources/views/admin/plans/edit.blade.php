@extends('layouts.master')

@section('title', 'Plan Düzenle: ' . $plan->display_name)

@section('content')
    <div class="card card-flush">
        <div class="card-header align-items-center py-5">
            <div class="card-title">
                <h2 class="fw-bold">{{ $plan->display_name }} — Planı Düzenle</h2>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('admin.plans.index') }}" class="btn btn-light btn-sm">
                    <i class="ki-duotone ki-arrow-left fs-4"><span class="path1"></span><span class="path2"></span></i>
                    Geri Dön
                </a>
            </div>
        </div>

        <div class="card-body pt-0">
            @if($errors->any())
                <div class="alert alert-danger mb-6">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-9 mb-8">
                    <!--begin::Col-->
                    <div class="col-lg-6">
                        <label class="form-label required fw-semibold">Görünen İsim</label>
                        <input type="text" name="display_name" class="form-control form-control-solid"
                            value="{{ old('display_name', $plan->display_name) }}" required />
                    </div>
                    <!--end::Col-->

                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">Açıklama</label>
                        <input type="text" name="description" class="form-control form-control-solid"
                            value="{{ old('description', $plan->description) }}" />
                    </div>
                </div>

                <!--begin::Pricing-->
                <div class="separator separator-dashed my-6"></div>
                <h4 class="fw-bold text-gray-700 mb-6">Fiyatlandırma</h4>

                <div class="row g-9 mb-8">
                    <div class="col-lg-4">
                        <label class="form-label required fw-semibold">Aylık Fiyat (₺)</label>
                        <input type="number" name="price_monthly" step="0.01" min="0"
                            class="form-control form-control-solid"
                            value="{{ old('price_monthly', $plan->price_monthly) }}" required />
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Yıllık Fiyat (₺)</label>
                        <input type="number" name="price_yearly" step="0.01" min="0"
                            class="form-control form-control-solid"
                            value="{{ old('price_yearly', $plan->price_yearly) }}" />
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label required fw-semibold">Trial Süresi (gün)</label>
                        <input type="number" name="trial_days" min="0"
                            class="form-control form-control-solid"
                            value="{{ old('trial_days', $plan->trial_days) }}" required />
                    </div>
                </div>
                <!--end::Pricing-->

                <!--begin::Limits-->
                <div class="separator separator-dashed my-6"></div>
                <h4 class="fw-bold text-gray-700 mb-2">Limitler</h4>
                <p class="text-muted fs-7 mb-6">Sınırsız için <strong>-1</strong> girin.</p>

                <div class="row g-9 mb-8">
                    <div class="col-lg-4">
                        <label class="form-label required fw-semibold">Pazaryeri Limiti</label>
                        <input type="number" name="marketplaces_limit" min="-1"
                            class="form-control form-control-solid"
                            value="{{ old('marketplaces_limit', $plan->marketplaces_limit) }}" required />
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label required fw-semibold">Sipariş/Ay Limiti</label>
                        <input type="number" name="orders_limit" min="-1"
                            class="form-control form-control-solid"
                            value="{{ old('orders_limit', $plan->orders_limit) }}" required />
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label required fw-semibold">Ürün Limiti</label>
                        <input type="number" name="products_limit" min="-1"
                            class="form-control form-control-solid"
                            value="{{ old('products_limit', $plan->products_limit) }}" required />
                    </div>
                </div>
                <!--end::Limits-->

                <!--begin::Features-->
                <div class="separator separator-dashed my-6"></div>
                <h4 class="fw-bold text-gray-700 mb-6">Özellikler</h4>

                <div class="row g-9 mb-8">
                    @php
                        $allFeatures = [
                            'analytics' => 'Analitik & Raporlama',
                            'claims'    => 'Şikayet Yönetimi',
                            'repricing' => 'Otomatik Fiyatlandırma',
                        ];
                    @endphp

                    @foreach($allFeatures as $key => $label)
                        <div class="col-lg-4">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox"
                                    name="features[{{ $key }}]"
                                    value="1"
                                    {{ ($plan->features[$key] ?? false) ? 'checked' : '' }} />
                                <span class="form-check-label fw-semibold text-gray-700">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <!--end::Features-->

                <!--begin::Display settings-->
                <div class="separator separator-dashed my-6"></div>
                <h4 class="fw-bold text-gray-700 mb-6">Görünüm Ayarları</h4>

                <div class="row g-9 mb-8">
                    <div class="col-lg-3">
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                value="1" {{ $plan->is_active ? 'checked' : '' }} />
                            <span class="form-check-label fw-semibold text-gray-700">Aktif</span>
                        </label>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_popular"
                                value="1" {{ $plan->is_popular ? 'checked' : '' }} />
                            <span class="form-check-label fw-semibold text-gray-700">Popüler Rozeti</span>
                        </label>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label required fw-semibold">Sıralama</label>
                        <input type="number" name="sort_order" min="0"
                            class="form-control form-control-solid"
                            value="{{ old('sort_order', $plan->sort_order) }}" required />
                    </div>
                </div>
                <!--end::Display settings-->

                <div class="d-flex justify-content-end mt-8">
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-light me-3">İptal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-duotone ki-check fs-4"><span class="path1"></span><span class="path2"></span></i>
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
