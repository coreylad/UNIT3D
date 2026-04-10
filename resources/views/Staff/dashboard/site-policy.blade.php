@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Site Policy - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Site policy settings" />
@endsection

@section('breadcrumbs')
    <li>
        <a href="{{ route('staff.dashboard.index') }}">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb--active">Site Policy</li>
@endsection

@section('page', 'page__staff-dashboard--services')

@section('main')
    <section class="panelV2 staff-services">
        <div class="staff-services__head">
            <h2 class="panel__heading">Registration and Freeleech Policy</h2>
            <p class="staff-services__subtitle">
                Control site-wide open registration and global freeleech behavior.
            </p>
        </div>

        <form class="staff-services__form" method="POST" action="{{ route('staff.dashboard.sitepolicy.update') }}">
            @csrf
            <div class="staff-services__fields">
                <p class="staff-services__field">
                    <label class="form__label" for="open_registration">Open Registration</label>
                    <input id="open_registration" name="open_registration" type="checkbox" value="1"
                        @checked(old('open_registration', $sitePolicySettings['open_registration'])) />
                    <small class="staff-theme-editor__guide">Enabled means new users can register without invite codes.</small>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="freeleech">Global Freeleech</label>
                    <input id="freeleech" name="freeleech" type="checkbox" value="1"
                        @checked(old('freeleech', $sitePolicySettings['freeleech'])) />
                    <small class="staff-theme-editor__guide">Enabled means downloads are globally freeleech for all users.</small>
                </p>
            </div>

            <div class="staff-services__actions">
                <button class="form__button form__button--filled" type="submit">Save Site Policy</button>
            </div>
        </form>
    </section>
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">Policy Navigation</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Site policy navigation">
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.index', ['tools' => 'user']) }}">Back to User and Security</a>
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.sitepolicy.index') }}">Reload Site Policy</a>
            </nav>
        </div>
    </section>
@endsection