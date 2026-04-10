@extends('layout.with-main-and-sidebar')

@section('title')
    <title>{{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="{{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumb--active">
        {{ __('staff.staff-dashboard') }}
    </li>
@endsection

@section('page', 'page__staff-dashboard--index')

@section('main')
    <section class="staff-command-center panelV2">
        <div class="staff-command-center__head">
            <p class="staff-command-center__kicker">Operations Core</p>
            <h2 class="staff-command-center__title">Staff Command Center</h2>
            <p class="staff-command-center__subtitle">
                High-priority queues and moderation shortcuts for fast daily control.
            </p>
        </div>
        <div class="staff-command-center__status-strip">
            <a class="staff-status-chip" href="{{ route('staff.reports.index') }}">
                <span class="staff-status-chip__label">Open Reports</span>
                <span class="staff-status-chip__value">{{ $unsolvedReportsCount }}</span>
            </a>
            <a class="staff-status-chip" href="{{ route('staff.applications.index') }}">
                <span class="staff-status-chip__label">Pending Applications</span>
                <span class="staff-status-chip__value">{{ $pendingApplicationsCount }}</span>
            </a>
            <a class="staff-status-chip" href="{{ route('staff.moderation.index') }}">
                <span class="staff-status-chip__label">Pending Torrents</span>
                <span class="staff-status-chip__value">{{ $torrents->pending }}</span>
            </a>
            <a class="staff-status-chip" href="{{ route('staff.peers.index') }}">
                <span class="staff-status-chip__label">Active Peers</span>
                <span class="staff-status-chip__value">{{ $peers->active }}</span>
            </a>
        </div>
        <div class="staff-command-grid" aria-label="Quick staff actions">
            <a class="staff-command-grid__tool" href="{{ route('staff.moderation.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-shield-alt"></i>
                <span>Moderation Queue</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.reports.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-flag"></i>
                <span>Reports Desk</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.applications.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-user-check"></i>
                <span>Applications</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.users.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-users"></i>
                <span>User Control</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.watchlist.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-eye"></i>
                <span>Watchlist</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.seedboxes.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-server"></i>
                <span>Seedbox Intel</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.cheaters.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-user-secret"></i>
                <span>Cheater Detection</span>
            </a>
            <a class="staff-command-grid__tool" href="{{ route('staff.audits.index') }}">
                <i class="{{ config('other.font-awesome') }} fa-clipboard-list"></i>
                <span>Audit Timeline</span>
            </a>
        </div>
    </section>

    @php
        $activeTools = request()->query('tools', 'links');
        $toolGroups = ['links', 'communications', 'platform', 'torrent', 'user', 'logs'];

        if (! in_array($activeTools, $toolGroups, true)) {
            $activeTools = 'links';
        }
    @endphp

    <section class="panelV2 staff-tools-workspace">
        <div class="staff-tools-workspace__head">
            <h2 class="panel__heading">Tool Workspace</h2>
            <p class="staff-tools-workspace__hint">Select a top-level group in Control Navigator to reveal its tool pills.</p>
        </div>

        @if ($activeTools === 'links')
            <section class="staff-tools-group">
            <h3 class="staff-tools-group__title">Links</h3>
            <div class="staff-tools-pills">
                <a class="staff-tools-pill" href="{{ route('home.index') }}">Frontend</a>
                <a class="staff-tools-pill" href="{{ route('staff.dashboard.index') }}">Staff Dashboard</a>
                @if (auth()->user()->group->is_owner)
                    <a class="staff-tools-pill" href="{{ route('staff.backups.index') }}">Backups</a>
                    <a class="staff-tools-pill" href="{{ route('staff.commands.index') }}">Commands</a>
                @endif
            </div>
            </section>
        @endif

        @if ($activeTools === 'communications')
            <section class="staff-tools-group">
            <h3 class="staff-tools-group__title">Communications Control</h3>
            <div class="staff-tools-pills">
                <a class="staff-tools-pill" href="{{ route('staff.statuses.index') }}">Statuses</a>
                <a class="staff-tools-pill" href="{{ route('staff.chatrooms.index') }}">Chatrooms</a>
                <a class="staff-tools-pill" href="{{ route('staff.bots.index') }}">Bots</a>
                <a class="staff-tools-pill" href="{{ route('staff.mass_private_message.create') }}">Mass PM</a>
                <a class="staff-tools-pill" href="{{ route('staff.mass_email.create') }}">Mass Email</a>
            </div>
            </section>
        @endif

        @if ($activeTools === 'platform')
            <section class="staff-tools-group">
            <h3 class="staff-tools-group__title">Platform and Content Control</h3>
            <div class="staff-tools-pills">
                <a class="staff-tools-pill" href="{{ route('staff.dashboard.services.index') }}">Site Services</a>
                <a class="staff-tools-pill" href="{{ route('staff.dashboard.theme.index') }}#banner-editor">Site Banner Editor</a>
                <a class="staff-tools-pill" href="{{ route('staff.dashboard.theme.index') }}">Theme Editor</a>
                <a class="staff-tools-pill" href="{{ route('staff.articles.index') }}">Articles</a>
                <a class="staff-tools-pill" href="{{ route('staff.events.index') }}">Events</a>
                <a class="staff-tools-pill" href="{{ route('staff.pages.index') }}">Pages</a>
                <a class="staff-tools-pill" href="{{ route('staff.polls.index') }}">Polls</a>
                <a class="staff-tools-pill" href="{{ route('staff.ticket_categories.index') }}">Ticket Categories</a>
                <a class="staff-tools-pill" href="{{ route('staff.ticket_priorities.index') }}">Ticket Priorities</a>
                <a class="staff-tools-pill" href="{{ route('staff.wiki_categories.index') }}">Wikis</a>
                <a class="staff-tools-pill" href="{{ route('staff.playlist_categories.index') }}">Playlist Categories</a>
            </div>
            </section>
        @endif

        @if ($activeTools === 'torrent')
            <section class="staff-tools-group">
            <h3 class="staff-tools-group__title">Torrent Operations</h3>
            <div class="staff-tools-pills">
                <a class="staff-tools-pill" href="{{ route('staff.moderation.index') }}">Moderation Queue</a>
                <a class="staff-tools-pill" href="{{ route('staff.categories.index') }}">Categories</a>
                <a class="staff-tools-pill" href="{{ route('staff.types.index') }}">Types</a>
                <a class="staff-tools-pill" href="{{ route('staff.resolutions.index') }}">Resolutions</a>
                <a class="staff-tools-pill" href="{{ route('staff.regions.index') }}">Regions</a>
                <a class="staff-tools-pill" href="{{ route('staff.distributors.index') }}">Distributors</a>
                <a class="staff-tools-pill" href="{{ route('staff.peers.index') }}">Peers</a>
                <a class="staff-tools-pill" href="{{ route('staff.rss.index') }}">RSS</a>
            </div>
            </section>
        @endif

        @if ($activeTools === 'user')
            <section class="staff-tools-group">
            <h3 class="staff-tools-group__title">User and Security Operations</h3>
            <div class="staff-tools-pills">
                <a class="staff-tools-pill" href="{{ route('staff.dashboard.twofactor.index') }}">2FA Policy</a>
                <a class="staff-tools-pill" href="{{ route('staff.applications.index') }}">Applications ({{ $pendingApplicationsCount }})</a>
                <a class="staff-tools-pill" href="{{ route('staff.users.index') }}">User Search</a>
                <a class="staff-tools-pill" href="{{ route('staff.apikeys.index') }}">API Keys</a>
                <a class="staff-tools-pill" href="{{ route('staff.passkeys.index') }}">Passkeys</a>
                <a class="staff-tools-pill" href="{{ route('staff.watchlist.index') }}">Watchlist</a>
                <a class="staff-tools-pill" href="{{ route('staff.cheaters.index') }}">Cheater Detection</a>
                <a class="staff-tools-pill" href="{{ route('staff.seedboxes.index') }}">Seedboxes</a>
                @if (auth()->user()->group->is_admin)
                    <a class="staff-tools-pill" href="{{ route('staff.groups.index') }}">Groups</a>
                @endif
            </div>
            </section>
        @endif

        @if ($activeTools === 'logs')
            <section class="staff-tools-group">
            <h3 class="staff-tools-group__title">Compliance and Logs</h3>
            <div class="staff-tools-pills">
                <a class="staff-tools-pill" href="{{ route('staff.audits.index') }}">Audit Log</a>
                <a class="staff-tools-pill" href="{{ route('staff.bans.index') }}">Bans Log</a>
                <a class="staff-tools-pill" href="{{ route('staff.authentications.index') }}">Failed Login Log</a>
                <a class="staff-tools-pill" href="{{ route('staff.gifts.index') }}">Gifts Log</a>
                <a class="staff-tools-pill" href="{{ route('staff.invites.index') }}">Invites Log</a>
                <a class="staff-tools-pill" href="{{ route('staff.notes.index') }}">User Notes</a>
                <a class="staff-tools-pill" href="{{ route('staff.reports.index') }}">Reports ({{ $unsolvedReportsCount }})</a>
                <a class="staff-tools-pill" href="{{ route('staff.warnings.index') }}">Warnings</a>
                @if (auth()->user()->group->is_owner)
                    <a class="staff-tools-pill" href="{{ route('staff.laravel-log.index') }}">Laravel Log</a>
                @endif
            </div>
            </section>
        @endif
    </section>
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">Control Navigator</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Staff side navigation">
                <a class="staff-side-menu__top @if ($activeTools === 'links') is-active @endif" href="{{ route('staff.dashboard.index', ['tools' => 'links']) }}">
                    Links
                </a>
                <a class="staff-side-menu__top @if ($activeTools === 'communications') is-active @endif" href="{{ route('staff.dashboard.index', ['tools' => 'communications']) }}">
                    Communications Control
                </a>
                <a class="staff-side-menu__top @if ($activeTools === 'platform') is-active @endif" href="{{ route('staff.dashboard.index', ['tools' => 'platform']) }}">
                    Platform and Content Control
                </a>
                <a class="staff-side-menu__top @if ($activeTools === 'torrent') is-active @endif" href="{{ route('staff.dashboard.index', ['tools' => 'torrent']) }}">
                    Torrent Operations
                </a>
                <a class="staff-side-menu__top @if ($activeTools === 'user') is-active @endif" href="{{ route('staff.dashboard.index', ['tools' => 'user']) }}">
                    User and Security Operations
                </a>
                <a class="staff-side-menu__top @if ($activeTools === 'logs') is-active @endif" href="{{ route('staff.dashboard.index', ['tools' => 'logs']) }}">
                    Compliance and Logs
                </a>

                <div class="staff-side-menu__quick">
                    <a class="staff-side-menu__link" href="{{ route('staff.reports.index') }}">
                        Reports
                        <span class="staff-side-menu__meta">{{ $unsolvedReportsCount }}</span>
                    </a>
                    <a class="staff-side-menu__link" href="{{ route('staff.applications.index') }}">
                        Applications
                        <span class="staff-side-menu__meta">{{ $pendingApplicationsCount }}</span>
                    </a>
                    <a class="staff-side-menu__link" href="{{ route('staff.moderation.index') }}">
                        Pending Torrents
                        <span class="staff-side-menu__meta">{{ $torrents->pending }}</span>
                    </a>
                </div>
            </nav>
        </div>
    </section>

    <section class="panelV2">
        <h2 class="panel__heading">SSL certificate</h2>
        <dl class="key-value">
            <div class="key-value__group">
                <dt>URL</dt>
                <dd>{{ config('app.url') }}</dd>
            </div>
            @if (request()->secure())
                <div class="key-value__group">
                    <dt>Connection</dt>
                    <dd>Secure</dd>
                </div>
                <div class="key-value__group">
                    <dt>Issued by</dt>
                    <dd>
                        {{ ! is_string($certificate) ? $certificate->getIssuer() : 'No certificate info found' }}
                    </dd>
                </div>
                <div class="key-value__group">
                    <dt>Expires</dt>
                    <dd>
                        {{ ! is_string($certificate) ? $certificate->expirationDate()->diffForHumans() : 'No certificate info found' }}
                    </dd>
                </div>
            @else
                <div class="key-value__group">
                    <dt>Connection</dt>
                    <dd>
                        <strong>Not secure</strong>
                    </dd>
                </div>
                <div class="key-value__group">
                    <dt>Issued by</dt>
                    <dd>N/A</dd>
                </div>
                <div class="key-value__group">
                    <dt>Expires</dt>
                    <dd>N/A</dd>
                </div>
            @endif
        </dl>
    </section>
    <section class="panelV2">
        <h2 class="panel__heading">Server information</h2>
        <dl class="key-value">
            <div class="key-value__group">
                <dt>OS</dt>
                <dd>{{ $basic['os'] }}</dd>
            </div>
            <div class="key-value__group">
                <dt>PHP</dt>
                <dd>{{ $basic['php'] }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Database</dt>
                <dd>{{ $basic['database'] }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Laravel</dt>
                <dd>{{ $basic['laravel'] }}</dd>
            </div>
            <div class="key-value__group">
                <dt>{{ config('unit3d.codebase') }}</dt>
                <dd>{{ config('unit3d.version') }}</dd>
            </div>
        </dl>
    </section>
    <div class="dashboard__stats">
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">Torrents</h2>
            <dl class="key-value">
                <div class="key-value__group">
                    <dt>Total</dt>
                    <dd>{{ $torrents->total }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Pending</dt>
                    <dd>{{ $torrents->pending }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Approved</dt>
                    <dd>{{ $torrents->approved }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Postponed</dt>
                    <dd>{{ $torrents->postponed }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Rejected</dt>
                    <dd>{{ $torrents->rejected }}</dd>
                </div>
            </dl>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">Peers</h2>
            <dl class="key-value">
                <div class="key-value__group">
                    <dt>Total</dt>
                    <dd>{{ $peers->total }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Active</dt>
                    <dd>{{ $peers->active }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Inactive</dt>
                    <dd>{{ $peers->inactive }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Seeds</dt>
                    <dd>{{ $peers->seeders }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Leeches</dt>
                    <dd>{{ $peers->leechers }}</dd>
                </div>
            </dl>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">Users</h2>
            <dl class="key-value">
                <div class="key-value__group">
                    <dt>Total</dt>
                    <dd>{{ $users->total }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Validating</dt>
                    <dd>{{ $users->validating }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Banned</dt>
                    <dd>{{ $users->banned }}</dd>
                </div>
            </dl>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">RAM</h2>
            <dl class="key-value">
                <div class="key-value__group">
                    <dt>Total</dt>
                    <dd>{{ $ram['total'] }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Used</dt>
                    <dd>{{ $ram['used'] }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Free</dt>
                    <dd>{{ $ram['available'] }}</dd>
                </div>
            </dl>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">Disk</h2>
            <dl class="key-value">
                <div class="key-value__group">
                    <dt>Total</dt>
                    <dd>{{ $disk['total'] }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Used</dt>
                    <dd>{{ $disk['used'] }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>Free</dt>
                    <dd>{{ $disk['free'] }}</dd>
                </div>
            </dl>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">Load average</h2>
            <dl class="key-value">
                <div class="key-value__group">
                    <dt>1 minute</dt>
                    <dd>{{ $avg['1-minute'] ?? 'N/A' }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>5 minutes</dt>
                    <dd>{{ $avg['5-minute'] ?? 'N/A' }}</dd>
                </div>
                <div class="key-value__group">
                    <dt>15 minutes</dt>
                    <dd>{{ $avg['15-minute'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </section>
    </div>
    <section class="panelV2">
        <h2 class="panel__heading">Directory permissions</h2>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Directory</th>
                        <th>Current</th>
                        <th><abbr title="Recommended">Rec.</abbr></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($file_permissions as $permission)
                        <tr>
                            <td>{{ $permission['directory'] }}</td>
                            <td>
                                @if ($permission['permission'] === $permission['recommended'])
                                    <i
                                        class="{{ config('other.font-awesome') }} fa-check-circle"
                                    ></i>
                                    {{ $permission['permission'] }}
                                @else
                                    <i
                                        class="{{ config('other.font-awesome') }} fa-times-circle"
                                    ></i>
                                    {{ $permission['permission'] }}
                                @endif
                            </td>
                            <td>{{ $permission['recommended'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @if (config('announce.external_tracker.is_enabled'))
        @if ($externalTrackerStats === true)
            <section class="panelV2">
                <h2 class="panel__heading">External tracker stats</h2>
                <div class="panel__body">External tracker not enabled.</div>
        @elseif ($externalTrackerStats === false)
            <section class="panelV2">
                <h2 class="panel__heading">External tracker stats</h2>
                <div class="panel__body">Stats endpoint not found.</div>
            </section>
        @elseif ($externalTrackerStats === [])
            <section class="panelV2">
                <h2 class="panel__heading">External tracker stats</h2>
                <div class="panel__body">Tracker returned an error.</div>
            </section>
        @else
            <section class="panelV2">
                <h2 class="panel__heading">External tracker stats</h2>
                <dl class="key-value">
                    @php
                        $createdAt = \Illuminate\Support\Carbon::createFromTimestampUTC($externalTrackerStats['created_at']);
                        $lastRequestAt = \Illuminate\Support\Carbon::createFromTimestampUTC($externalTrackerStats['last_request_at']);
                        $lastAnnounceResponseAt = \Illuminate\Support\Carbon::createFromTimestampUTC($externalTrackerStats['last_announce_response_at']);
                    @endphp

                    <div class="key-value__group">
                        <dt>{{ __('torrent.started') }}</dt>
                        <dd>
                            <time
                                title="{{ $createdAt->format('Y-m-d h:i:s') }}"
                                datetime="{{ $createdAt->format('Y-m-d h:i:s') }}"
                            >
                                {{ $createdAt->diffForHumans() }}
                            </time>
                        </dd>
                    </div>
                    <div class="key-value__group">
                        <dt>Last request at</dt>
                        <dd>
                            <time
                                title="{{ $lastRequestAt->format('Y-m-d h:i:s') }}"
                                datetime="{{ $lastRequestAt->format('Y-m-d h:i:s') }}"
                            >
                                {{ $lastRequestAt->diffForHumans() }}
                            </time>
                        </dd>
                    </div>
                    <div class="key-value__group">
                        <dt>Last successful response at</dt>
                        <dd>
                            <time
                                title="{{ $lastAnnounceResponseAt->format('Y-m-d h:i:s') }}"
                                datetime="{{ $lastAnnounceResponseAt->format('Y-m-d h:i:s') }}"
                            >
                                {{ $lastAnnounceResponseAt->diffForHumans() }}
                            </time>
                        </dd>
                    </div>
                </dl>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="text-align: right">Interval (s)</th>
                            <th style="text-align: right">In (req/s)</th>
                            <th style="text-align: right">Out (req/s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([1, 10, 60, 900, 7200] as $interval)
                            <tr>
                                <td style="text-align: right">{{ $interval }}</td>
                                <td style="text-align: right">
                                    {{ \number_format($externalTrackerStats['requests_per_' . $interval . 's'], 0, null, "\u{202F}") }}
                                </td>
                                <td style="text-align: right">
                                    {{ \number_format($externalTrackerStats['announce_responses_per_' . $interval . 's'], 0, null, "\u{202F}") }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    @endif
@endsection
