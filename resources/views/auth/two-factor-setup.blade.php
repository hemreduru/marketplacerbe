@extends('layouts.master')

@section('title', __('auth.two_factor.setup_title'))

@section('content')
<div class="row">
    <div class="col-xl-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('auth.two_factor.setup_title') }}</h3>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <p class="text-gray-600 mb-6">{{ __('auth.two_factor.setup_intro') }}</p>

                <div class="mb-6 text-center">
                    {{-- QR code: data URI'a gerek yok, otpauth URI'sini Google Charts API ile basit render --}}
                    <img alt="QR" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUri) }}" />
                    <div class="mt-3 fs-7 text-gray-500">{{ __('auth.two_factor.manual_code_label') }}: <code>{{ $secret }}</code></div>
                </div>

                <form method="POST" action="{{ route('two-factor.confirm') }}">
                    @csrf
                    <div class="fv-row mb-6">
                        <label class="form-label">{{ __('auth.two_factor.code_label') }}</label>
                        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                            class="form-control" placeholder="000000" required />
                    </div>
                    <button type="submit" class="btn btn-primary">
                        {{ __('auth.two_factor.confirm_button') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
