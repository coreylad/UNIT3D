@extends('layout.default')

@section('title')
    <title>Edit {{ $toolLabel }} - {{ __('staff.config-manager') }} - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb__item">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb__item">
        <a href="{{ route('staff.config.index') }}" class="breadcrumb__link">{{ __('staff.config-manager') }}</a>
    </li>
    <li class="breadcrumb__item">
        <a href="{{ route('staff.config.show', ['tool' => $tool]) }}" class="breadcrumb__link">{{ $toolLabel }}</a>
    </li>
    <li class="breadcrumb--active">Edit</li>
@endsection

@section('content')
    <h1>Edit {{ $toolLabel }} {{ __('staff.config-manager') }}</h1>

    @if (session('success'))
        <div class="alert alert--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="form-wrapper">
        @forelse($config as $key => $value)
            @if(is_array($value))
                <details class="form__fieldset">
                    <summary>{{ ucfirst(str_replace('_', ' ', $key)) }}</summary>
                    <div class="form__fieldset-content">
                        @foreach($value as $nestedKey => $nestedValue)
                            <form method="POST" action="{{ route('staff.config.update', ['tool' => $tool, 'key' => $key]) }}" class="form">
                                @csrf
                                @method('PATCH')
                                
                                <div class="form__group">
                                    <label>{{ ucfirst(str_replace('_', ' ', $nestedKey)) }}</label>
                                    @if(is_bool($nestedValue))
                                        <select name="value[{{ $nestedKey }}]" class="form__select">
                                            <option value="1" {{ $nestedValue ? 'selected' : '' }}>Enabled</option>
                                            <option value="0" {{ !$nestedValue ? 'selected' : '' }}>Disabled</option>
                                        </select>
                                    @else
                                        <input type="text" name="value[{{ $nestedKey }}]" value="{{ is_array($nestedValue) ? json_encode($nestedValue) : $nestedValue }}" class="form__input" />
                                    @endif
                                </div>
                                
                                <button type="submit" class="form__button form__button--small">Save</button>
                            </form>
                        @endforeach
                    </div>
                </details>
            @else
                <form method="POST" action="{{ route('staff.config.update', ['tool' => $tool, 'key' => $key]) }}" class="form form__card">
                    @csrf
                    @method('PATCH')
                    
                    <div class="form__group">
                        <label>{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                        @if(is_bool($value))
                            <select name="value" class="form__select">
                                <option value="1" {{ $value ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ !$value ? 'selected' : '' }}>Disabled</option>
                            </select>
                        @else
                            <input type="text" name="value" value="{{ $value }}" class="form__input" />
                        @endif
                    </div>
                    
                    <button type="submit" class="form__button">Save</button>
                </form>
            @endif
        @empty
            <p>No configuration values found.</p>
        @endforelse
    </div>

    <a href="{{ route('staff.config.show', ['tool' => $tool]) }}" class="form__button form__button--secondary">Back</a>
@endsection

@push('styles')
    <style>
        .form-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .form__fieldset {
            background: var(--color-bg-secondary, #1a1a1a);
            border: 1px solid var(--color-border, #333);
            border-radius: 4px;
            padding: 1rem;
        }

        .form__fieldset summary {
            cursor: pointer;
            font-weight: 600;
            padding: 0.5rem;
            margin: -0.5rem;
        }

        .form__fieldset[open] summary {
            margin-bottom: 1rem;
        }

        .form__fieldset-content {
            display: grid;
            gap: 1rem;
        }

        .form__card {
            background: var(--color-bg-secondary, #1a1a1a);
            border: 1px solid var(--color-border, #333);
            border-radius: 4px;
            padding: 1rem;
        }

        .form__button--small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
    </style>
@endpush
