@extends('layout.with-main')

@section('title')
    <title>User Defaults - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="User Defaults Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">User Defaults</li>
@endsection

@section('page', 'page__staff-site-setting--user-defaults')

@section('styles')
    <style>
        .page__staff-site-setting--user-defaults {
            padding: 2rem 0 3rem;
        }
        .ss-hero {
            background: rgba(96, 125, 139, 0.12);
            border: 1px solid rgba(96, 125, 139, 0.15);
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
            background: rgba(96, 125, 139, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #607D8B;
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
            border-left: 4px solid #607D8B;
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
            <i class="{{ config('other.font-awesome') }} fa-user-cog"></i>
        </div>
        <div class="ss-hero__text">
            <h1>User Defaults</h1>
            <p>Configure default values for newly registered accounts.</p>
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
        <input type="hidden" name="_section" value="user-defaults" />

        <div class="ss-grid">
            <p class="form__group">
                <input id="default_download_slots" class="form__text" name="default_download_slots" type="number" min="1" max="999" value="{{ old('default_download_slots', $siteSetting->default_download_slots) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="default_download_slots">Default Download Slots</label>
                <span class="form__hint">Simultaneous downloads allowed for new accounts</span>
            </p>
            <p class="form__group">
                <input id="default_upload" class="form__text" name="default_upload" type="number" min="0" value="{{ old('default_upload', $siteSetting->default_upload) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="default_upload">Default Upload Credit (bytes)</label>
                <span class="form__hint">Initial upload credit in bytes. 53687091200 = 50 GiB</span>
            </p>
        </div>

        <div class="ss-grid">
            <p class="form__group">
                <input id="default_download" class="form__text" name="default_download" type="number" min="0" value="{{ old('default_download', $siteSetting->default_download) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="default_download">Default Download (bytes)</label>
                <span class="form__hint">Initial download amount in bytes. 1073741824 = 1 GiB</span>
            </p>
            <p class="form__group">
                <input id="default_style" class="form__text" name="default_style" type="number" min="0" max="50" value="{{ old('default_style', $siteSetting->default_style) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="default_style">Default Theme</label>
                <span class="form__hint">Theme ID for new accounts (0-12)</span>
            </p>
        </div>

        <div class="ss-divider"></div>

        <div class="ss-field">
            <p class="form__group">
                <select id="font_awesome" class="form__select" name="font_awesome">
                    <option value="fas" @selected(old('font_awesome', $siteSetting->font_awesome) === 'fas')>Solid</option>
                    <option value="far" @selected(old('font_awesome', $siteSetting->font_awesome) === 'far')>Regular</option>
                    <option value="fal" @selected(old('font_awesome', $siteSetting->font_awesome) === 'fal')>Light</option>
                </select>
                <label class="form__label form__label--floating" for="font_awesome">Icon Style</label>
                <span class="form__hint">Font Awesome icon weight</span>
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
