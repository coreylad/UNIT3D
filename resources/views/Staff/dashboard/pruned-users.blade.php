@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Purge Pruned Users - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Purge pruned users" />
@endsection

@section('breadcrumbs')
    <li>
        <a href="{{ route('staff.dashboard.index') }}">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb--active">Purge Pruned Users</li>
@endsection

@section('page', 'page__staff-dashboard--services')

@section('main')
    <section class="panelV2 staff-services">
        <div class="staff-services__head">
            <h2 class="panel__heading">Purge Pruned Users</h2>
            <p class="staff-services__subtitle">
                Permanently delete users that are already in the pruned group and soft-deleted.
            </p>
        </div>

        <div class="staff-services__pill-row">
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Pruned Users Ready To Purge</span>
                <span class="staff-services__pill-value">{{ $prunedUsersCount }}</span>
            </span>
        </div>

        <form class="staff-services__form" method="POST" action="{{ route('staff.dashboard.pruned.purge') }}">
            @csrf
            <div class="staff-services__fields">
                <p class="staff-services__field">
                    <label class="form__label" for="confirm_purge">Confirm Permanent Deletion</label>
                    <input id="confirm_purge" name="confirm_purge" type="checkbox" value="1" required />
                    <small class="staff-theme-editor__guide">This removes pruned users permanently and cannot be undone.</small>
                </p>
            </div>

            <div class="staff-services__actions">
                <button class="form__button form__button--filled" type="submit">Purge Pruned Users</button>
            </div>
        </form>
    </section>
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">Pruned User Tool</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Pruned users navigation">
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.index', ['tools' => 'user']) }}">Back to User and Security</a>
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.pruned.index') }}">Reload Pruned User Counts</a>
            </nav>
        </div>
    </section>
@endsection
