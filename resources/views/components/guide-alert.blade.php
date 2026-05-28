@props(['color', 'icon', 'title'])

<div class="alert alert-dismissible bg-light-{{ $color }} d-flex flex-column flex-sm-row p-5 mb-5">
    <i class="ki-duotone {{ $icon }} fs-2hx text-{{ $color }} me-4 mb-5 mb-sm-0">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
    </i>
    <div class="d-flex flex-column pe-0 pe-sm-10">
        <h4 class="mb-2 text-dark">{{ $title }}</h4>
        {{ $slot }}
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
