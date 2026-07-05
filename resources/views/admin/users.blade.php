@extends('layouts.master')

@section('title', __('admin.users'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid">
            <h1 class="page-title fw-bold fs-3">{{ __('admin.users') }}</h1>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-row-dashed align-middle gs-0 gy-2">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-4">{{ __('admin.name') }}</th>
                                <th>{{ __('admin.email') }}</th>
                                <th>{{ __('admin.marketplaces') }}</th>
                                <th>{{ __('admin.role') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->marketplace_credentials_count }}</td>
                                <td>
                                    @if($u->is_admin)
                                        <span class="badge badge-light-danger">{{ __('admin.role_admin') }}</span>
                                    @else
                                        <span class="badge badge-light">{{ __('admin.role_user') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @unless($u->is_admin)
                                    <form action="{{ route('admin.impersonate', $u) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-primary">{{ __('admin.impersonate') }}</button>
                                    </form>
                                    @endunless
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</div>
@endsection
