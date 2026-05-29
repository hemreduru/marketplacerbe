@extends('layouts.master')

@section('title', __('reports.repricer'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-stack">
            <h1 class="page-title text-gray-900 fw-bold fs-3 my-0">{{ __('reports.repricer') }}</h1>
            <form method="POST" action="{{ route('repricer.run') }}">@csrf
                <button class="btn btn-sm btn-primary">{{ __('reports.run_repricer') }}</button>
            </form>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            @foreach (['success', 'info'] as $flash)
                @if (session($flash))<div class="alert alert-{{ $flash }}">{{ session($flash) }}</div>@endif
            @endforeach

            <div class="row g-5">
                {{-- Yeni kural --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.create_rule') }}</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('repricer.store') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">{{ __('reports.rule_name') }}</label>
                                    <input type="text" name="name" class="form-control form-control-sm" required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('reports.competitor_based') }} / {{ __('reports.target_margin') }}</label>
                                    <select name="strategy" class="form-select form-select-sm">
                                        <option value="fixed">fixed (min/max)</option>
                                        <option value="target_margin">{{ __('reports.target_margin') }}</option>
                                        <option value="undercut">{{ __('reports.competitor_based') }}</option>
                                    </select>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col"><label class="form-label fs-8">{{ __('reports.min_price') }}</label><input type="number" step="0.01" name="min_price" class="form-control form-control-sm" /></div>
                                    <div class="col"><label class="form-label fs-8">{{ __('reports.max_price') }}</label><input type="number" step="0.01" name="max_price" class="form-control form-control-sm" /></div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col"><label class="form-label fs-8">{{ __('reports.target_margin') }}</label><input type="number" step="0.01" name="target_margin" class="form-control form-control-sm" /></div>
                                    <div class="col"><label class="form-label fs-8">Undercut</label><input type="number" step="0.01" name="undercut_amount" class="form-control form-control-sm" /></div>
                                </div>
                                <input type="hidden" name="is_active" value="1" />
                                <button class="btn btn-sm btn-light-primary w-100">{{ __('reports.create_rule') }}</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Kural listesi --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('reports.repricer_rules') }}</h3></div>
                        <div class="card-body p-0">
                            @if($rules->isNotEmpty())
                            <table class="table table-row-dashed align-middle gs-4 gy-2">
                                <thead><tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4">{{ __('reports.rule_name') }}</th><th>{{ __('reports.competitor_based') }}</th>
                                    <th>{{ __('reports.min_price') }}</th><th>{{ __('reports.max_price') }}</th>
                                    <th>{{ __('reports.active') }}</th><th></th>
                                </tr></thead>
                                <tbody>
                                    @foreach($rules as $rule)
                                    <tr>
                                        <td class="ps-4 fw-bold">{{ $rule->name }}</td>
                                        <td>{{ $rule->strategy }}</td>
                                        <td>{{ $rule->min_price ? '₺'.number_format((float)$rule->min_price, 2, ',', '.') : '—' }}</td>
                                        <td>{{ $rule->max_price ? '₺'.number_format((float)$rule->max_price, 2, ',', '.') : '—' }}</td>
                                        <td>@if($rule->is_active)<span class="badge badge-light-success">{{ __('reports.active') }}</span>@else<span class="badge badge-light">—</span>@endif</td>
                                        <td>
                                            <form method="POST" action="{{ route('repricer.destroy', $rule) }}" onsubmit="return confirm('?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-icon btn-light-danger"><i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="text-center py-10"><p class="text-gray-500">{{ __('reports.no_rules') }}</p></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
