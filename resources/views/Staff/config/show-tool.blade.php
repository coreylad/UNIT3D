@extends('layout.default')

@section('title')
    <title>{{ $toolLabel }} - {{ __('staff.config-manager') }} - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb__item">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb__item">
        <a href="{{ route('staff.config.index') }}" class="breadcrumb__link">{{ __('staff.config-manager') }}</a>
    </li>
    <li class="breadcrumb--active">{{ $toolLabel }}</li>
@endsection

@section('content')
    <h1>{{ $toolLabel }} {{ __('staff.config-manager') }}</h1>

    @if (session('success'))
        <div class="alert alert--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="staff-dashboard__config-grid">
        @forelse($config as $key => $value)
            <div class="staff-dashboard__config-item">
                <h3>{{ ucfirst(str_replace('_', ' ', $key)) }}</h3>
                
                @if(is_array($value))
                    <details class="staff-dashboard__config-details">
                        <summary>View nested configuration</summary>
                        <div class="staff-dashboard__config-nested">
                            @foreach($value as $nestedKey => $nestedValue)
                                <div class="staff-dashboard__config-pair">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $nestedKey)) }}:</strong>
                                    <code>{{ is_array($nestedValue) ? json_encode($nestedValue) : (is_bool($nestedValue) ? ($nestedValue ? 'true' : 'false') : $nestedValue) }}</code>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @else
                    <div class="staff-dashboard__config-value">
                        <code>
                            {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}
                        </code>
                    </div>
                @endif
            </div>
        @empty
            <p>No configuration values found.</p>
        @endforelse
    </div>

    <a href="{{ route('staff.config.index') }}" class="form__button form__button--secondary">{{ __('common.back') }}</a>
@endsection

@push('styles')
    <style>
        .staff-dashboard__config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .staff-dashboard__config-item {
            background: var(--color-bg-secondary, #1a1a1a);
            border: 1px solid var(--color-border, #333);
            border-radius: 4px;
            padding: 1rem;
        }

        .staff-dashboard__config-item h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.95rem;
            color: var(--color-text-primary);
        }

        .staff-dashboard__config-value code {
            display: block;
            background: var(--color-bg-tertiary, #0f0f0f);
            padding: 0.5rem;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 0.85rem;
            word-break: break-all;
        }

        .staff-dashboard__config-details {
            cursor: pointer;
        }

        .staff-dashboard__config-details summary {
            padding: 0.5rem;
            background: var(--color-bg-tertiary, #0f0f0f);
            border-radius: 3px;
            font-size: 0.9rem;
        }

        .staff-dashboard__config-nested {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: var(--color-bg-tertiary, #0f0f0f);
            border-radius: 3px;
        }

        .staff-dashboard__config-pair {
            margin: 0.25rem 0;
            font-size: 0.85rem;
        }

        .staff-dashboard__config-pair strong {
            display: inline-block;
            min-width: 120px;
        }

        .staff-dashboard__config-pair code {
            background: transparent;
            padding: 0;
            font-size: inherit;
        }
    </style>
@endpush
