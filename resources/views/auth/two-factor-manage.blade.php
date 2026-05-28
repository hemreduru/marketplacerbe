@extends('layouts.master')

@section('title', __('auth.two_factor.manage_title'))

@section('content')
<div class="row">
    <div class="col-xl-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('auth.two_factor.manage_title') }}</h3>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="alert alert-info">{{ __('auth.two_factor.enabled') }}</div>

                <h4 class="mt-6">{{ __('auth.two_factor.recovery_codes_title') }}</h4>
                <p class="text-gray-600 fs-7">{{ __('auth.two_factor.recovery_codes_intro') }}</p>
                <pre class="bg-light p-4 rounded">@foreach ($recoveryCodes as $code){{ $code }}
@endforeach</pre>

                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-8">
                    @csrf
                    @method('DELETE')
                    <h4>{{ __('auth.two_factor.disable_title') }}</h4>
                    <p class="text-gray-600 fs-7">{{ __('auth.two_factor.disable_intro') }}</p>
                    <div class="fv-row mb-4">
                        <input type="password" name="password" class="form-control"
                            placeholder="{{ __('auth.password') }}" required />
                    </div>
                    <button type="submit" class="btn btn-danger">{{ __('auth.two_factor.disable_button') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
