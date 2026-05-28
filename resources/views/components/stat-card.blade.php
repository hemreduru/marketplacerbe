@props([
    'value',
    'title',
    'icon',
    'color' => 'primary',
    'growth' => null,
    'change' => null,
    'prevPeriodLabel' => null,
    'link' => null,
    'linkLabel' => null,
    'format' => 'currency',
])

<div class="card card-flush h-xl-100">
    <div class="card-header pt-5">
        <div class="card-title d-flex flex-column">
            <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">
                @if($format === 'integer')
                    {{ number_format($value, 0) }}
                @elseif($format === 'pct')
                    {{ $value }}%
                @else
                    @money($value)
                @endif
            </span>
            <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ $title }}</span>
        </div>
    </div>
    <div class="card-body pt-2 pb-4 d-flex flex-wrap align-items-center">
        <div class="d-flex flex-center me-5 pt-2">
            @if($link)
                <a href="{{ $link }}" class="badge badge-light-{{ $color }} fs-base">
            @else
                <span class="badge badge-light-{{ $color }} fs-base">
            @endif
                <i class="ki-duotone {{ $icon }} fs-5 text-{{ $color }} ms-n1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                {{ $linkLabel ?? $title }}
            @if($link)
                </a>
            @else
                </span>
            @endif
        </div>

        @php $changeValue = $growth ?? $change; @endphp
        @if($changeValue !== null)
            <div class="d-flex align-items-center flex-column mt-3 w-100">
                <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                    <span class="fw-semibold fs-6 text-gray-400">
                        {{ __('common.vs_previous_period') }}
                        @if($prevPeriodLabel)
                            <span class="fs-8">({{ $prevPeriodLabel }})</span>
                        @endif
                    </span>
                    <span class="fw-bold fs-6 {{ $changeValue >= 0 ? 'text-success' : 'text-danger' }}">


                        {{ $changeValue >= 0 ? '+' : '' }}{{ $changeValue }}%

                    </span>
                </div>
                <div class="h-8px mx-3 w-100 bg-light-{{ $changeValue >= 0 ? 'success' : 'danger' }} rounded">
                    <div class="bg-{{ $changeValue >= 0 ? 'success' : 'danger' }} rounded h-8px"
                        role="progressbar"
                        style="width: {{ min(abs((int)$changeValue), 100) }}%"
                        aria-valuenow="{{ abs((int)$changeValue) }}"
                        aria-valuemin="0"
                        aria-valuemax="100">
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
