<div class="card card-flush h-xl-100 mb-5 mb-xl-10">
    <div class="card-header pt-5">
        <div class="card-title d-flex flex-column">
            <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ number_format($value, 2) }} ₺</span>
            <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ $title }}</span>
        </div>
    </div>
    <div class="card-body pt-2 pb-4 d-flex flex-wrap align-items-center">
        <div class="d-flex flex-center me-5 pt-2">
            <span class="badge badge-light-{{ $color }} fs-base">
                <i class="ki-duotone {{ $icon }} fs-5 text-{{ $color }} ms-n1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                {{ $title }}
            </span>
        </div>

        @if (isset($growth))
            <div class="d-flex align-items-center flex-column mt-3 w-100">
                <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                    <span class="fw-semibold fs-6 text-gray-400">{{ __('common.vs_previous_period') }} <span
                            class="fs-8">({{ $prevPeriodLabel ?? '' }})</span></span>
                    <span class="fw-bold fs-6 {{ $growth >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                    </span>
                </div>
                <div class="h-8px mx-3 w-100 bg-light-{{ $growth >= 0 ? 'success' : 'danger' }} rounded">
                    <div class="bg-{{ $growth >= 0 ? 'success' : 'danger' }} rounded h-8px" role="progressbar"
                        style="width: {{ min(abs($growth), 100) }}%" aria-valuenow="{{ abs($growth) }}"
                        aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        @endif
    </div>
</div>
