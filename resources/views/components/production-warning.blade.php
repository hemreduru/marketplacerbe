@if (config('app.debug'))
    <div class="alert alert-danger d-flex align-items-center p-2 mb-2">
        <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-3"><span class="path1"></span><span
                class="path2"></span><span class="path3"></span></i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-danger">{{ __('common.debug_mode_active') }}</h4>
            <span>{{ __('common.action_simulated') }}</span>
        </div>
    </div>
@endif
