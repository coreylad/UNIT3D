@extends('layout.with-main')

@section('title')
    <title>
        {{ __('migration.database-migration') }} - {{ __('staff.staff-dashboard') }}
        - {{ config('other.title') }}
    </title>
@endsection

@section('meta')
    <meta
        name="description"
        content="{{ __('migration.database-migration') }} - {{ __('staff.staff-dashboard') }}"
    />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">{{ __('migration.database-migration') }}</li>
@endsection

@section('page', 'page__staff-migration-manager--index')

@section('main')
    @livewire('migration-panel')
@endsection
