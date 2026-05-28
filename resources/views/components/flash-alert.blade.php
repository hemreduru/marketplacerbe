@props(['type' => 'info', 'message'])

@php
    $iconMap = [
        'success' => 'ki-shield-tick',
        'danger'  => 'ki-shield-cross',
        'error'   => 'ki-shield-cross',
        'warning' => 'ki-information-5',
        'info'    => 'ki-information-5',
    ];
    $colorMap = ['error' => 'danger'];
    $color = $colorMap[$type] ?? $type;
    $icon  = $iconMap[$type] ?? 'ki-information-5';
@endphp

<div class="alert alert-dismissible bg-light-{{ $color }} border border-{{ $color }} d-flex align-items-center p-5 mb-8"
    style="border-radius: 1rem;">
    <i class="ki-duotone {{ $icon }} fs-2hx text-{{ $color }} me-4">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
    </i>
    <div class="d-flex flex-column">
        <span class="fw-semibold">{{ $message }}</span>
    </div>
    <button type="button"
        class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
        data-bs-dismiss="alert">
        <i class="ki-duotone ki-cross fs-1 text-{{ $color }}">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </button>
</div>
