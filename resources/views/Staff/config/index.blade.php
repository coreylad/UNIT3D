@extends('layout.default')

@section('title')
    <title>{{ __('staff.config-manager') }} - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb__item">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb--active">{{ __('staff.config-manager') }}</li>
@endsection

@section('content')
    <h1>{{ __('staff.config-manager') }}</h1>
    <p>Manage system configuration for each tool below.</p>

    <div class="staff-dashboard__links-grid">
        @foreach($tools as $tool)
            <a class="staff-dashboard__link-card" href="{{ route('staff.config.show', ['tool' => $tool['key']]) }}">
                <i class="{{ config('other.font-awesome') }} {{ $tool['icon'] }}"></i>
                <span>{{ $tool['label'] }}</span>
            </a>
        @endforeach
    </div>
@endsection
