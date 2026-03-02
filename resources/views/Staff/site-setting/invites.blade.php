@extends('layout.with-main')

@section('title')
    <title>Invite System - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Invite System Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">Invite System</li>
@endsection

@section('page', 'page__staff-site-setting--invites')

@section('styles')
    <style>
        .page__staff-site-setting--invites {
            padding: 2rem 0 3rem;
        }
        .ss-hero {
            background: rgba(0, 137, 123, 0.12);
            border: 1px solid rgba(0, 137, 123, 0.15);
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
            background: rgba(0, 137, 123, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #00897B;
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
            border-left: 4px solid #00897B;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.02);
            margin-bottom: 3rem;
        }
        .ss-grid {
            display: grid;
            grid-template-columns: 1fr;
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
            <i class="{{ config('other.font-awesome') }} fa-envelope-open"></i>
        </div>
        <div class="ss-hero__text">
            <h1>Invite System</h1>
            <p>Configure invite distribution, restrictions, and application signups.</p>
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
        <input type="hidden" name="_section" value="invites" />

        <label class="ss-toggle" for="invite_only">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Invite Only Mode</span>
                <span class="ss-toggle__desc">New accounts require an invitation code</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="invite_only" value="0" />
                <input id="invite_only" type="checkbox" name="invite_only" value="1" @checked(old('invite_only', $siteSetting->invite_only ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <label class="ss-toggle" for="application_signups">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Application Signups</span>
                <span class="ss-toggle__desc">Allow users to apply for an account</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="application_signups" value="0" />
                <input id="application_signups" type="checkbox" name="application_signups" value="1" @checked(old('application_signups', $siteSetting->application_signups ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <div class="ss-divider"></div>

        <div class="ss-field">
            <p class="form__group">
                <input id="invite_expire" class="form__text" name="invite_expire" type="number" min="1" max="365" value="{{ old('invite_expire', $siteSetting->invite_expire) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="invite_expire">Invite Expiry (days)</label>
                <span class="form__hint">Number of days before unused invites expire</span>
            </p>
        </div>

        <div class="ss-field">
            <p class="form__group">
                <input id="max_unused_user_invites" class="form__text" name="max_unused_user_invites" type="number" min="0" max="100" value="{{ old('max_unused_user_invites', $siteSetting->max_unused_user_invites) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="max_unused_user_invites">Max Unused Invites per User</label>
                <span class="form__hint">Maximum number of pending invites a user can have</span>
            </p>
        </div>

        <label class="ss-toggle" for="invites_restricted">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Restrict Invites</span>
                <span class="ss-toggle__desc">Limit invite distribution to specific groups</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="invites_restricted" value="0" />
                <input id="invites_restricted" type="checkbox" name="invites_restricted" value="1" @checked(old('invites_restricted', $siteSetting->invites_restricted ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        {{-- Submit --}}
        <div class="ss-submit">
            <button class="form__button form__button--filled" type="submit">
                <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                {{ __('common.save') }} Changes
            </button>
        </div>
    </form>
@endsection
