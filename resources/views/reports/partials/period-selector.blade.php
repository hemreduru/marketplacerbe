{{-- Paylaşılan rapor periyot seçici. $period = çözümlenmiş anahtar. --}}
<form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
    <select name="period" class="form-select form-select-sm w-auto" onchange="if(this.value !== 'custom'){this.form.submit();}">
        @foreach(\App\Services\Reports\ReportPeriod::availableKeys() as $key)
            <option value="{{ $key }}" @selected(($period ?? 'this_month') === $key)>{{ __('reports.period_' . $key) }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm w-auto" />
    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm w-auto" />
    <button type="submit" class="btn btn-sm btn-light-primary">{{ __('reports.apply') }}</button>
</form>
