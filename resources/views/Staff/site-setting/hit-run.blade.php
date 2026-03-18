@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Hit & Run Settings - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Hit & Run Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumbV2">
        <a href="{{ route('staff.site_settings.branding') }}" class="breadcrumb__link">Site Settings</a>
    </li>
    <li class="breadcrumb--active">Hit &amp; Run Settings</li>
@endsection

@section('page', 'page__staff-site-setting--hit-run')

@section('stylesheets')
    <style>
        .page__staff-site-setting--hit-run {
            padding: 2rem 0 3rem;
        }
        .ss-hero {
            background: rgba(229, 57, 53, 0.12);
            border: 1px solid rgba(229, 57, 53, 0.15);
            border-radius: 12px;
            padding: 3rem;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .ss-hero__icon {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            background: rgba(229, 57, 53, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #E53935;
            flex-shrink: 0;
        }
        .ss-hero__text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 0.4rem;
        }
        .ss-hero__text p {
            font-size: 0.9rem;
            opacity: 0.6;
            margin: 0;
            line-height: 1.5;
        }
        .ss-card {
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 12px;
            border-left: 4px solid #E53935;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.02);
            margin-bottom: 3rem;
        }
        .ss-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2.5rem;
        }
        .ss-field {
            margin-bottom: 2.5rem;
        }
        .ss-field:last-child {
            margin-bottom: 0;
        }
        .ss-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
            margin: 3rem 0;
        }
        .ss-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .ss-toggle:last-child {
            margin-bottom: 0;
        }
        .ss-toggle:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .ss-toggle__info {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .ss-toggle__title {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .ss-toggle__desc {
            font-size: 0.82rem;
            opacity: 0.5;
            line-height: 1.4;
        }
        .ss-switch {
            position: relative;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        .ss-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .ss-switch__track {
            position: absolute;
            inset: 0;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.15);
            transition: background 0.25s;
            cursor: pointer;
        }
        .ss-switch__track::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            left: 3px;
            top: 3px;
            border-radius: 50%;
            background: #fff;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        .ss-switch input:checked + .ss-switch__track {
            background: #4caf50;
        }
        .ss-switch input:checked + .ss-switch__track::before {
            transform: translateX(22px);
        }
        .ss-alert {
            padding: 1.25rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .ss-alert--success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.25);
            color: #66bb6a;
        }
        .ss-alert--error {
            background: rgba(255, 107, 107, 0.08);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        .ss-alert__icon {
            font-size: 1.2rem;
            margin-top: 0.1em;
            flex-shrink: 0;
        }
        .ss-alert__body {
            flex: 1;
        }
        .ss-alert__title {
            font-weight: 700;
            margin-bottom: 0.4rem;
        }
        .ss-alert ul {
            margin: 0.5rem 0 0;
            padding-left: 1.25em;
        }
        .ss-alert li {
            margin-bottom: 0.25rem;
        }
        .ss-submit {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        .form__group {
            margin-bottom: 0;
        }
        .form__hint {
            margin-top: 0.75rem;
            display: block;
        }
        @media (max-width: 767px) {
            .ss-grid {
                grid-template-columns: 1fr;
            }
            .ss-card {
                padding: 2rem;
            }
            .ss-hero {
                flex-direction: column;
                text-align: center;
                padding: 2rem;
            }
        }
    </style>
@endsection

@section('main')
    {{-- Hero Banner --}}
    <div class="ss-hero">
        <div class="ss-hero__icon">
            <i class="{{ config('other.font-awesome') }} fa-exclamation-triangle"></i>
        </div>
        <div class="ss-hero__text">
            <h1>Hit & Run</h1>
            <p>Configure hit and run detection, penalties, and warning thresholds.</p>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="ss-alert ss-alert--success">
            <span class="ss-alert__icon"><i class="{{ config('other.font-awesome') }} fa-circle-check"></i></span>
            <div class="ss-alert__body">{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="ss-alert ss-alert--error">
            <span class="ss-alert__icon"><i class="{{ config('other.font-awesome') }} fa-triangle-exclamation"></i></span>
            <div class="ss-alert__body">
                <div class="ss-alert__title">Please fix the following errors:</div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form class="form ss-card" method="POST" action="{{ route('staff.site_settings.update') }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_section" value="hit-run" />

        <label class="ss-toggle" for="hitrun_enabled">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Hit & Run System</span>
                <span class="ss-toggle__desc">Enable hit and run detection and warnings</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="hitrun_enabled" value="0" />
                <input id="hitrun_enabled" type="checkbox" name="hitrun_enabled" value="1" @checked(old('hitrun_enabled', $siteSetting->hitrun_enabled)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <div class="ss-divider"></div>

        <div class="ss-grid">
            <p class="form__group">
                <input id="hitrun_seedtime" class="form__text" name="hitrun_seedtime" type="number" min="0" max="31536000" value="{{ old('hitrun_seedtime', $siteSetting->hitrun_seedtime) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="hitrun_seedtime">Minimum Seedtime (seconds)</label>
                <span class="form__hint">Required seed time. 604800 = 7 days</span>
            </p>
            <p class="form__group">
                <input id="hitrun_max_warnings" class="form__text" name="hitrun_max_warnings" type="number" min="1" max="100" value="{{ old('hitrun_max_warnings', $siteSetting->hitrun_max_warnings) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="hitrun_max_warnings">Max Warnings</label>
                <span class="form__hint">Warnings before download privileges are disabled</span>
            </p>
        </div>

        <div class="ss-grid">
            <p class="form__group">
                <input id="hitrun_grace" class="form__text" name="hitrun_grace" type="number" min="0" max="365" value="{{ old('hitrun_grace', $siteSetting->hitrun_grace) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="hitrun_grace">Grace Period (days)</label>
                <span class="form__hint">Days allowed for disconnection before warning</span>
            </p>
            <p class="form__group">
                <input id="hitrun_buffer" class="form__text" name="hitrun_buffer" type="number" min="0" max="100" value="{{ old('hitrun_buffer', $siteSetting->hitrun_buffer) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="hitrun_buffer">Buffer (%)</label>
                <span class="form__hint">Percentage buffer checked against actual download</span>
            </p>
        </div>

        <div class="ss-grid">
            <p class="form__group">
                <input id="hitrun_expire" class="form__text" name="hitrun_expire" type="number" min="1" max="365" value="{{ old('hitrun_expire', $siteSetting->hitrun_expire) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="hitrun_expire">Warning Expiry (days)</label>
                <span class="form__hint">Days before a warning expires</span>
            </p>
            <p class="form__group">
                <input id="hitrun_prewarn" class="form__text" name="hitrun_prewarn" type="number" min="0" max="365" value="{{ old('hitrun_prewarn', $siteSetting->hitrun_prewarn) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="hitrun_prewarn">Pre-warn Period (days)</label>
                <span class="form__hint">Days before sending a pre-warning PM</span>
            </p>
        </div>

        {{-- Submit --}}
        <div class="ss-submit">
            <button class="form__button form__button--filled" type="submit">
                <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                {{ __('common.save') }} Changes
            </button>
        </div>
    </form>
@endsection

@section('sidebar')
    @include('Staff.site-setting.partials.nav')
@endsection
