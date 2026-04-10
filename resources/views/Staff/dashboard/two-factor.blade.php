@extends('layout.with-main-and-sidebar')

@section('title')
    <title>2FA Policy - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Two-factor policy settings" />
@endsection

@section('breadcrumbs')
    <li>
        <a href="{{ route('staff.dashboard.index') }}">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb--active">2FA Policy</li>
@endsection

@section('page', 'page__staff-dashboard--services')

@section('main')
    <section class="panelV2 staff-services">
        <div class="staff-services__head">
            <h2 class="panel__heading">2FA Policy</h2>
            <p class="staff-services__subtitle">
                Control forced two-factor authentication and the issuer name shown in authenticator apps.
            </p>
        </div>

        <form class="staff-services__form" method="POST" action="{{ route('staff.dashboard.twofactor.update') }}">
            @csrf
            <div class="staff-services__fields">
                <p class="staff-services__field">
                    <label class="form__label" for="issuer">Authenticator App Name</label>
                    <input id="issuer" class="form__text" name="issuer" type="text"
                        value="{{ old('issuer', $twoFactorSettings['issuer']) }}" required />
                    <small class="staff-theme-editor__guide">Example: THE VOID Tracker</small>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="force_2fa">Force 2FA For All Users</label>
                    <input id="force_2fa" name="force_2fa" type="checkbox" value="1"
                        @checked(old('force_2fa', $twoFactorSettings['force_2fa'])) />
                    <small class="staff-theme-editor__guide">When enabled, users without confirmed 2FA are redirected to setup before accessing the site.</small>
                </p>
            </div>

            <div class="staff-services__actions">
                <button class="form__button form__button--filled" type="submit">Save 2FA Policy</button>
            </div>
        </form>
    </section>
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">2FA Navigation</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Two-factor navigation">
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.index', ['tools' => 'user']) }}">Back to User and Security</a>
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.twofactor.index') }}">Reload 2FA Policy</a>
            </nav>
        </div>
    </section>
@endsection
