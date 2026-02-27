@extends('layout.with-main')

@section('title')
    <title>Commands - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Commands - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">Commands</li>
@endsection

@section('page', 'page__staff-command--index')

@section('styles')
    <style>
        .cmd-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }
        .cmd-grid .panelV2 {
            min-width: 0;
            overflow: hidden;
        }
        /* Prevent legacy import panel from blowing out the grid */
        .cmd-grid .panelV2 input.form__text {
            width: 100%;
            box-sizing: border-box;
            min-width: 0;
        }
        /* Make legacy import span full width on its own row when grid wraps */
        .cmd-panel--full {
            grid-column: 1 / -1;
        }
    </style>
@endsection

@section('main')
    <div class="cmd-grid">
        <section class="panelV2">
            <h2 class="panel__heading">Maintenance mode</h2>
            <div class="panel__body">
                <div class="form__group form__group--horizontal">
                    <form
                        role="form"
                        method="POST"
                        action="{{ url('/dashboard/commands/maintenance-enable') }}"
                    >
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands enables maintenance mode while whitelisting only your IP Address."
                        >
                            Enable maintenance mode
                        </button>
                    </form>
                </div>
                <div class="form__group form__group--horizontal">
                    <form
                        role="form"
                        method="POST"
                        action="{{ url('/dashboard/commands/maintenance-disable') }}"
                    >
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands disables maintenance mode. Bringing the site backup for all to access."
                        >
                            Disable maintenance mode
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <section class="panelV2">
            <h2 class="panel__heading">Caching</h2>
            <div class="panel__body">
                <div class="form__group form__group--horizontal">
                    <form method="POST" action="{{ url('/dashboard/commands/clear-cache') }}">
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands clears your sites cache. This cache depends on what driver you are using."
                        >
                            Clear cache
                        </button>
                    </form>
                </div>
                <div class="form__group form__group--horizontal">
                    <form method="POST" action="{{ url('/dashboard/commands/clear-view-cache') }}">
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands clears your sites compiled views cache."
                        >
                            Clear view cache
                        </button>
                    </form>
                </div>
                <div class="form__group form__group--horizontal">
                    <form
                        method="POST"
                        action="{{ url('/dashboard/commands/clear-route-cache') }}"
                    >
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands clears your sites compiled routes cache."
                        >
                            Clear route cache
                        </button>
                    </form>
                </div>
                <div class="form__group form__group--horizontal">
                    <form
                        method="POST"
                        action="{{ url('/dashboard/commands/clear-config-cache') }}"
                    >
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands clears your sites compiled configs cache."
                        >
                            Clear config cache
                        </button>
                    </form>
                </div>
                <div class="form__group form__group--horizontal">
                    <form method="POST" action="{{ url('/dashboard/commands/clear-all-cache') }}">
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands clears ALL of your sites cache."
                        >
                            Clear all cache
                        </button>
                    </form>
                </div>
                <div class="form__group form__group--horizontal">
                    <form method="POST" action="{{ url('/dashboard/commands/set-all-cache') }}">
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands sets ALL of your sites cache."
                        >
                            Set all cache
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <section class="panelV2">
            <h2 class="panel__heading">Email</h2>
            <div class="panel__body">
                <div class="form__group form__group--horizontal">
                    <form method="POST" action="{{ url('/dashboard/commands/test-email') }}">
                        @csrf
                        <button
                            class="form__button form__button--text"
                            title="This commands tests your email configuration."
                        >
                            Send test email
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <section class="panelV2 cmd-panel--full">
            <h2 class="panel__heading">Legacy database import</h2>
            <div class="panel__body">
                <form method="POST" action="{{ url('/dashboard/commands/import-legacy-sql') }}">
                    @csrf
                    <div class="form__group">
                        <label class="form__label" for="dump_path">SQL dump absolute path</label>
                        <input
                            id="dump_path"
                            name="dump_path"
                            type="text"
                            class="form__text"
                            required
                            placeholder="D:\\Database\\admin_TSSE8_2026-02-08_15-30-47.sql"
                        />
                    </div>

                    <div class="form__group">
                        <p style="margin: 0 0 0.5rem;">Schema strategy</p>
                        <label style="display: block; margin-bottom: 0.35rem;">
                            <input type="checkbox" name="fresh" value="1" /> Run migrate:fresh before import
                        </label>
                        <label style="display: block; margin-bottom: 0.35rem;">
                            <input type="checkbox" name="skip_migrate" value="1" /> Skip migrate before import
                        </label>
                        <label style="display: block; margin-bottom: 0.35rem;">
                            <input type="checkbox" name="truncate" value="1" /> Truncate imported tables before first insert
                        </label>
                        <label style="display: block; margin-bottom: 0.35rem;">
                            <input type="checkbox" name="allow_unknown_tables" value="1" checked /> Skip unknown legacy tables
                        </label>
                    </div>

                    <div class="form__group form__group--horizontal">
                        <button
                            class="form__button form__button--text"
                            title="Runs artisan db:import-legacy with selected options and shows command output in flash messages."
                        >
                            Import legacy SQL dump
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
