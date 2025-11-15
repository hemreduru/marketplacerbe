@extends('layouts.master')

@section('title', __('common.dashboard'))

@section('content')
    <!--begin::Welcome Card-->
    <div class="card">
        <div class="card-body p-lg-17">
            <div class="row">
                <div class="col-lg-8">
                    <!--begin::Heading-->
                    <div class="mb-5">
                        <h1 class="fs-2hx fw-bold text-gray-800 mb-5">
                            {{ __('dashboard.welcome_message', ['name' => Auth::user()->name ?? '']) }}!
                        </h1>
                        <div class="fs-5 fw-semibold text-gray-600">
                            {{ __('dashboard.welcome_description') }}
                            <br />
                            {{ __('dashboard.welcome_subtitle') }}
                        </div>
                    </div>
                    <!--end::Heading-->
                </div>
                <div class="col-lg-4">
                    <img src="{{ asset('assets/media/illustrations/sketchy-1/17.png') }}" class="mw-100 mh-300px" alt="" />
                </div>
            </div>
        </div>
    </div>
    <!--end::Welcome Card-->
@endsection
