@extends('layout.default')

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

@section('content')
    <div class="staff-dashboard" x-data="{ activePanel: 'overview' }">
        {{-- Left Sidebar Navigation --}}
        <nav class="staff-dashboard__sidebar">
            <div class="staff-dashboard__sidebar-header">
                <i class="{{ config('other.font-awesome') }} fa-shield-alt"></i>
                <span>Admin Panel</span>
            </div>

            <ul class="staff-dashboard__nav">
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'overview' }"
                        x-on:click="activePanel = 'overview'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-tachometer-alt"></i>
                        <span>Overview</span>
                    </button>
                </li>
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'links' }"
                        x-on:click="activePanel = 'links'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-link"></i>
                        <span>{{ __('staff.links') }}</span>
                    </button>
                </li>
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'chat' }"
                        x-on:click="activePanel = 'chat'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-comment-dots"></i>
                        <span>{{ __('staff.chat-tools') }}</span>
                    </button>
                </li>
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'general' }"
                        x-on:click="activePanel = 'general'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-wrench"></i>
                        <span>{{ __('staff.general-tools') }}</span>
                    </button>
                </li>
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'torrents' }"
                        x-on:click="activePanel = 'torrents'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-download"></i>
                        <span>{{ __('staff.torrent-tools') }}</span>
                    </button>
                </li>
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'users' }"
                        x-on:click="activePanel = 'users'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-users"></i>
                        <span>{{ __('staff.user-tools') }}</span>
                        @if ($pendingApplicationsCount > 0)
                            <span class="staff-dashboard__badge">{{ $pendingApplicationsCount }}</span>
                        @endif
                    </button>
                </li>
                <li>
                    <button
                        class="staff-dashboard__nav-item"
                        :class="{ 'staff-dashboard__nav-item--active': activePanel === 'logs' }"
                        x-on:click="activePanel = 'logs'"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file-alt"></i>
                        <span>{{ __('staff.logs') }}</span>
                        @if ($unsolvedReportsCount > 0)
                            <span class="staff-dashboard__badge">{{ $unsolvedReportsCount }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </nav>

        {{-- Main Content Area --}}
        <div class="staff-dashboard__content">
            {{-- Overview Panel --}}
            <div x-show="activePanel === 'overview'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-tachometer-alt"></i>
                    Dashboard Overview
                </h2>

                @if ($pendingUsers->isNotEmpty())
                    <section class="staff-dashboard__card staff-dashboard__card--alert">
                        <h3 class="staff-dashboard__card-title">
                            <i class="{{ config('other.font-awesome') }} fa-user-clock"></i>
                            Users Pending Verification
                        </h3>
                        <div class="staff-dashboard__card-body">
                            @foreach ($pendingUsers as $pendingUser)
                                <div class="staff-dashboard__pending-user">
                                    <a href="{{ route('users.show', ['user' => $pendingUser]) }}">{{ $pendingUser->username }}</a>
                                    <form method="POST" action="{{ route('staff.users.verify', ['user' => $pendingUser]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="staff-dashboard__action-btn" type="submit">
                                            Verify
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="staff-dashboard__stats-grid">
                    <section class="staff-dashboard__stat-card">
                        <div class="staff-dashboard__stat-icon staff-dashboard__stat-icon--torrents">
                            <i class="{{ config('other.font-awesome') }} fa-download"></i>
                        </div>
                        <div class="staff-dashboard__stat-info">
                            <h3>Torrents</h3>
                            <span class="staff-dashboard__stat-number">{{ $torrents->total }}</span>
                        </div>
                        <dl class="staff-dashboard__stat-details">
                            <div><dt>Pending</dt><dd>{{ $torrents->pending }}</dd></div>
                            <div><dt>Approved</dt><dd>{{ $torrents->approved }}</dd></div>
                            <div><dt>Postponed</dt><dd>{{ $torrents->postponed }}</dd></div>
                            <div><dt>Rejected</dt><dd>{{ $torrents->rejected }}</dd></div>
                        </dl>
                    </section>
                    <section class="staff-dashboard__stat-card">
                        <div class="staff-dashboard__stat-icon staff-dashboard__stat-icon--peers">
                            <i class="{{ config('other.font-awesome') }} fa-exchange-alt"></i>
                        </div>
                        <div class="staff-dashboard__stat-info">
                            <h3>Peers</h3>
                            <span class="staff-dashboard__stat-number">{{ $peers->total }}</span>
                        </div>
                        <dl class="staff-dashboard__stat-details">
                            <div><dt>Active</dt><dd>{{ $peers->active }}</dd></div>
                            <div><dt>Inactive</dt><dd>{{ $peers->inactive }}</dd></div>
                            <div><dt>Seeds</dt><dd>{{ $peers->seeders }}</dd></div>
                            <div><dt>Leeches</dt><dd>{{ $peers->leechers }}</dd></div>
                        </dl>
                    </section>
                    <section class="staff-dashboard__stat-card">
                        <div class="staff-dashboard__stat-icon staff-dashboard__stat-icon--users">
                            <i class="{{ config('other.font-awesome') }} fa-users"></i>
                        </div>
                        <div class="staff-dashboard__stat-info">
                            <h3>Users</h3>
                            <span class="staff-dashboard__stat-number">{{ $users->total }}</span>
                        </div>
                        <dl class="staff-dashboard__stat-details">
                            <div><dt>Validating</dt><dd>{{ $users->validating }}</dd></div>
                            <div><dt>Banned</dt><dd>{{ $users->banned }}</dd></div>
                        </dl>
                    </section>
                    <section class="staff-dashboard__stat-card">
                        <div class="staff-dashboard__stat-icon staff-dashboard__stat-icon--system">
                            <i class="{{ config('other.font-awesome') }} fa-memory"></i>
                        </div>
                        <div class="staff-dashboard__stat-info">
                            <h3>RAM</h3>
                            <span class="staff-dashboard__stat-number">{{ $ram['used'] }}</span>
                        </div>
                        <dl class="staff-dashboard__stat-details">
                            <div><dt>Total</dt><dd>{{ $ram['total'] }}</dd></div>
                            <div><dt>Free</dt><dd>{{ $ram['available'] }}</dd></div>
                        </dl>
                    </section>
                    <section class="staff-dashboard__stat-card">
                        <div class="staff-dashboard__stat-icon staff-dashboard__stat-icon--disk">
                            <i class="{{ config('other.font-awesome') }} fa-hdd"></i>
                        </div>
                        <div class="staff-dashboard__stat-info">
                            <h3>Disk</h3>
                            <span class="staff-dashboard__stat-number">{{ $disk['used'] }}</span>
                        </div>
                        <dl class="staff-dashboard__stat-details">
                            <div><dt>Total</dt><dd>{{ $disk['total'] }}</dd></div>
                            <div><dt>Free</dt><dd>{{ $disk['free'] }}</dd></div>
                        </dl>
                    </section>
                    <section class="staff-dashboard__stat-card">
                        <div class="staff-dashboard__stat-icon staff-dashboard__stat-icon--load">
                            <i class="{{ config('other.font-awesome') }} fa-chart-line"></i>
                        </div>
                        <div class="staff-dashboard__stat-info">
                            <h3>Load Avg</h3>
                            <span class="staff-dashboard__stat-number">{{ $avg['1-minute'] ?? 'N/A' }}</span>
                        </div>
                        <dl class="staff-dashboard__stat-details">
                            <div><dt>5 min</dt><dd>{{ $avg['5-minute'] ?? 'N/A' }}</dd></div>
                            <div><dt>15 min</dt><dd>{{ $avg['15-minute'] ?? 'N/A' }}</dd></div>
                        </dl>
                    </section>
                </div>

                <div class="staff-dashboard__info-grid">
                    <section class="staff-dashboard__card">
                        <h3 class="staff-dashboard__card-title">
                            <i class="{{ config('other.font-awesome') }} fa-lock"></i>
                            SSL Certificate
                        </h3>
                        <dl class="staff-dashboard__card-body staff-dashboard__kv-list">
                            <div><dt>URL</dt><dd>{{ config('app.url') }}</dd></div>
                            @if (request()->secure())
                                <div><dt>Connection</dt><dd>Secure</dd></div>
                                <div><dt>Issued by</dt><dd>{{ ! is_string($certificate) ? $certificate->getIssuer() : 'No certificate info found' }}</dd></div>
                                <div><dt>Expires</dt><dd>{{ ! is_string($certificate) ? $certificate->expirationDate()->diffForHumans() : 'No certificate info found' }}</dd></div>
                            @else
                                <div><dt>Connection</dt><dd><strong>Not secure</strong></dd></div>
                                <div><dt>Issued by</dt><dd>N/A</dd></div>
                                <div><dt>Expires</dt><dd>N/A</dd></div>
                            @endif
                        </dl>
                    </section>
                    <section class="staff-dashboard__card">
                        <h3 class="staff-dashboard__card-title">
                            <i class="{{ config('other.font-awesome') }} fa-server"></i>
                            Server Information
                        </h3>
                        <dl class="staff-dashboard__card-body staff-dashboard__kv-list">
                            <div><dt>OS</dt><dd>{{ $basic['os'] }}</dd></div>
                            <div><dt>PHP</dt><dd>{{ $basic['php'] }}</dd></div>
                            <div><dt>Database</dt><dd>{{ $basic['database'] }}</dd></div>
                            <div><dt>Laravel</dt><dd>{{ $basic['laravel'] }}</dd></div>
                            <div><dt>{{ config('unit3d.codebase') }}</dt><dd>{{ config('unit3d.version') }}</dd></div>
                        </dl>
                    </section>
                    <section class="staff-dashboard__card">
                        <h3 class="staff-dashboard__card-title">
                            <i class="{{ config('other.font-awesome') }} fa-folder-open"></i>
                            Directory Permissions
                        </h3>
                        <div class="staff-dashboard__card-body">
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
                                                        <i class="{{ config('other.font-awesome') }} fa-check-circle"></i>
                                                    @else
                                                        <i class="{{ config('other.font-awesome') }} fa-times-circle"></i>
                                                    @endif
                                                    {{ $permission['permission'] }}
                                                </td>
                                                <td>{{ $permission['recommended'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                @if (config('announce.external_tracker.is_enabled'))
                    @if ($externalTrackerStats === true)
                        <section class="staff-dashboard__card">
                            <h3 class="staff-dashboard__card-title">External Tracker Stats</h3>
                            <div class="staff-dashboard__card-body">External tracker not enabled.</div>
                        </section>
                    @elseif ($externalTrackerStats === false)
                        <section class="staff-dashboard__card">
                            <h3 class="staff-dashboard__card-title">External Tracker Stats</h3>
                            <div class="staff-dashboard__card-body">Stats endpoint not found.</div>
                        </section>
                    @elseif ($externalTrackerStats === [])
                        <section class="staff-dashboard__card">
                            <h3 class="staff-dashboard__card-title">External Tracker Stats</h3>
                            <div class="staff-dashboard__card-body">Tracker returned an error.</div>
                        </section>
                    @else
                        <section class="staff-dashboard__card">
                            <h3 class="staff-dashboard__card-title">External Tracker Stats</h3>
                            <div class="staff-dashboard__card-body">
                                @php
                                    $createdAt = \Illuminate\Support\Carbon::createFromTimestampUTC($externalTrackerStats['created_at']);
                                    $lastRequestAt = \Illuminate\Support\Carbon::createFromTimestampUTC($externalTrackerStats['last_request_at']);
                                    $lastAnnounceResponseAt = \Illuminate\Support\Carbon::createFromTimestampUTC($externalTrackerStats['last_announce_response_at']);
                                @endphp

                                <dl class="staff-dashboard__kv-list">
                                    <div>
                                        <dt>{{ __('torrent.started') }}</dt>
                                        <dd>
                                            <time title="{{ $createdAt->format('Y-m-d h:i:s') }}" datetime="{{ $createdAt->format('Y-m-d h:i:s') }}">
                                                {{ $createdAt->diffForHumans() }}
                                            </time>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Last request at</dt>
                                        <dd>
                                            <time title="{{ $lastRequestAt->format('Y-m-d h:i:s') }}" datetime="{{ $lastRequestAt->format('Y-m-d h:i:s') }}">
                                                {{ $lastRequestAt->diffForHumans() }}
                                            </time>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Last successful response at</dt>
                                        <dd>
                                            <time title="{{ $lastAnnounceResponseAt->format('Y-m-d h:i:s') }}" datetime="{{ $lastAnnounceResponseAt->format('Y-m-d h:i:s') }}">
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
                            </div>
                        </section>
                    @endif
                @endif
            </div>

            {{-- Links Panel --}}
            <div x-show="activePanel === 'links'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-link"></i>
                    {{ __('staff.links') }}
                </h2>
                <div class="staff-dashboard__links-grid">
                    <a class="staff-dashboard__link-card" href="{{ route('home.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        <span>{{ __('staff.frontend') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.dashboard.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-tachometer-alt"></i>
                        <span>{{ __('staff.staff-dashboard') }}</span>
                    </a>
                    @if (auth()->user()->group->is_owner)
                        <a class="staff-dashboard__link-card" href="{{ route('staff.backups.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-hdd"></i>
                            <span>{{ __('backup.backup') }} {{ __('backup.manager') }}</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.commands.index') }}">
                            <i class="fab fa-laravel"></i>
                            <span>Commands</span>
                        </a>
                        @if (config('donation.is_enabled'))
                            <a class="staff-dashboard__link-card" href="{{ route('staff.donations.index') }}">
                                <i class="{{ config('other.font-awesome') }} fa-money-bill"></i>
                                <span>Donations</span>
                            </a>
                            <a class="staff-dashboard__link-card" href="{{ route('staff.gateways.index') }}">
                                <i class="{{ config('other.font-awesome') }} fa-money-bill"></i>
                                <span>Gateways</span>
                            </a>
                            <a class="staff-dashboard__link-card" href="{{ route('staff.packages.index') }}">
                                <i class="{{ config('other.font-awesome') }} fa-money-bill"></i>
                                <span>Packages</span>
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Chat Tools Panel --}}
            <div x-show="activePanel === 'chat'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-comment-dots"></i>
                    {{ __('staff.chat-tools') }}
                </h2>
                <div class="staff-dashboard__links-grid">
                    <a class="staff-dashboard__link-card" href="{{ route('staff.statuses.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-comment-dots"></i>
                        <span>{{ __('staff.statuses') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.chatrooms.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-comments"></i>
                        <span>{{ __('staff.rooms') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.bots.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-robot"></i>
                        <span>{{ __('staff.bots') }}</span>
                    </a>
                </div>
                <div class="staff-dashboard__actions-section">
                    <h3 class="staff-dashboard__section-subtitle">Actions</h3>
                    <form
                        method="POST"
                        action="{{ route('staff.flush.chat') }}"
                        x-data="confirmation"
                    >
                        @csrf
                        <button
                            x-on:click.prevent="confirmAction"
                            data-b64-deletion-message="{{ base64_encode('Are you sure you want to delete all chatbox messages in all chatrooms (including private chatbox messages)?') }}"
                            class="staff-dashboard__danger-btn"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-broom"></i>
                            {{ __('staff.flush-chat') }}
                        </button>
                    </form>
                </div>
            </div>


            {{-- General Tools Panel --}}
            <div x-show="activePanel === 'general'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-wrench"></i>
                    {{ __('staff.general-tools') }}
                </h2>
                <div class="staff-dashboard__links-grid">
                    @if (auth()->user()->group->is_admin)
                        <a class="staff-dashboard__link-card" href="{{ route('staff.site_settings.edit') }}">
                            <i class="{{ config('other.font-awesome') }} fa-cog"></i>
                            <span>Site Settings</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.theme_builder.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-palette"></i>
                            <span>Theme Builder</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.groups.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-user-shield"></i>
                            <span>Group Permissions</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.users.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-user-plus"></i>
                            <span>New User</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.forum_categories.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-list-ul"></i>
                            <span>{{ __('staff.forums') }}</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.forums.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-comments"></i>
                            <span>New Forum</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.wikis.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-book"></i>
                            <span>New Wiki Article</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.categories.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-plus-square"></i>
                            <span>New Torrent Category</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.types.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-plus-square"></i>
                            <span>New Torrent Type</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.resolutions.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-plus-square"></i>
                            <span>New Resolution</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.regions.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-map-marker-alt"></i>
                            <span>New Region</span>
                        </a>
                        <a class="staff-dashboard__link-card" href="{{ route('staff.distributors.create') }}">
                            <i class="{{ config('other.font-awesome') }} fa-industry"></i>
                            <span>New Distributor</span>
                        </a>
                    @endif
                    @if (auth()->user()->group->is_owner)
                        <a class="staff-dashboard__link-card" href="{{ route('staff.migrations.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-database"></i>
                            <span>Database Migration</span>
                        </a>
                    @endif
                    <a class="staff-dashboard__link-card" href="{{ route('staff.articles.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-newspaper"></i>
                        <span>{{ __('staff.articles') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.events.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-calendar-star"></i>
                        <span>{{ __('event.events') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.pages.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        <span>{{ __('staff.pages') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.polls.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-chart-pie"></i>
                        <span>{{ __('staff.polls') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.wiki_categories.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-book"></i>
                        <span>Wikis</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.ticket_categories.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-tags"></i>
                        <span>{{ __('staff.ticket-categories') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.ticket_priorities.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-exclamation-triangle"></i>
                        <span>{{ __('staff.ticket-priorities') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.bon_exchanges.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-coins"></i>
                        <span>{{ __('staff.bon-exchange') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.bon_earnings.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-coins"></i>
                        <span>{{ __('staff.bon-earnings') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.blacklisted_clients.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-ban"></i>
                        <span>{{ __('common.blacklist') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.whitelisted_image_urls.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-globe"></i>
                        <span>Whitelisted Image URLs</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.blocked_ips.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-ban"></i>
                        <span>{{ __('staff.blocked-ips') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.playlist_categories.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-list"></i>
                        <span>Playlist Categories</span>
                    </a>
                </div>
            </div>

            {{-- Torrent Tools Panel --}}
            <div x-show="activePanel === 'torrents'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-download"></i>
                    {{ __('staff.torrent-tools') }}
                </h2>
                <div class="staff-dashboard__links-grid">
                    <a class="staff-dashboard__link-card" href="{{ route('staff.moderation.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-gavel"></i>
                        <span>{{ __('staff.torrent-moderation') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.categories.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-th-list"></i>
                        <span>{{ __('staff.torrent-categories') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.types.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-file-video"></i>
                        <span>{{ __('staff.torrent-types') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.resolutions.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-tv"></i>
                        <span>{{ __('staff.torrent-resolutions') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.regions.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-globe-americas"></i>
                        <span>Torrent Regions</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.distributors.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-industry"></i>
                        <span>Torrent Distributors</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.automatic_torrent_freeleeches.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-gift"></i>
                        <span>Auto Freeleeches</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.peers.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-network-wired"></i>
                        <span>Peers</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.histories.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-history"></i>
                        <span>Histories</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.torrent_downloads.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-cloud-download-alt"></i>
                        <span>Downloads</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.torrent_trumps.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-trophy"></i>
                        <span>Trumps</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.unregistered_info_hashes.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-question-circle"></i>
                        <span>Unregistered Info Hashes</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.rss.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-rss"></i>
                        <span>{{ __('staff.rss') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.media_languages.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-language"></i>
                        <span>{{ __('common.media-languages') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.cheated_torrents.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-exclamation-triangle"></i>
                        <span>Cheated Torrents</span>
                    </a>
                    @if (config('announce.log_announces'))
                        <a class="staff-dashboard__link-card" href="{{ route('staff.announces.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-chart-bar"></i>
                            <span>Announces</span>
                        </a>
                    @endif
                </div>
                @if (! config('announce.external_tracker.is_enabled'))
                    <div class="staff-dashboard__actions-section">
                        <h3 class="staff-dashboard__section-subtitle">Actions</h3>
                        <form
                            method="POST"
                            action="{{ route('staff.flush.peers') }}"
                            x-data="confirmation"
                        >
                            @csrf
                            <button
                                x-on:click.prevent="confirmAction"
                                data-b64-deletion-message="{{ base64_encode('Are you sure you want to delete all ghost peers?') }}"
                                class="staff-dashboard__danger-btn"
                            >
                                <i class="{{ config('other.font-awesome') }} fa-ghost"></i>
                                {{ __('staff.flush-ghost-peers') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- User Tools Panel --}}
            <div x-show="activePanel === 'users'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-users"></i>
                    {{ __('staff.user-tools') }}
                </h2>
                <div class="staff-dashboard__links-grid">
                    <a class="staff-dashboard__link-card" href="{{ route('staff.applications.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-list"></i>
                        <span>{{ __('staff.applications') }}</span>
                        @if ($pendingApplicationsCount > 0)
                            <span class="staff-dashboard__badge">{{ $pendingApplicationsCount }}</span>
                        @endif
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.users.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-search"></i>
                        <span>{{ __('staff.user-search') }}</span>
                    </a>
                    @if (auth()->user()->group->is_admin)
                        <a class="staff-dashboard__link-card" href="{{ route('staff.groups.index') }}">
                            <i class="{{ config('other.font-awesome') }} fa-users-cog"></i>
                            <span>{{ __('staff.groups') }}</span>
                        </a>
                    @endif
                    <a class="staff-dashboard__link-card" href="{{ route('staff.mass_private_message.create') }}">
                        <i class="{{ config('other.font-awesome') }} fa-envelope-square"></i>
                        <span>{{ __('staff.mass-pm') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.mass_email.create') }}">
                        <i class="{{ config('other.font-awesome') }} fa-paper-plane"></i>
                        <span>{{ __('staff.mass-email') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.apikeys.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        <span>{{ __('user.apikeys') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.passkeys.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        <span>{{ __('staff.passkeys') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.rsskeys.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        <span>{{ __('user.rsskeys') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.email_updates.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-envelope"></i>
                        <span>{{ __('user.email-updates') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.password_reset_histories.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-redo"></i>
                        <span>{{ __('user.password-resets') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.watchlist.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-eye"></i>
                        <span>Watchlist</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.cheaters.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-exclamation-triangle"></i>
                        <span>{{ __('staff.possible-leech-cheaters') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.leakers.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-faucet-drip"></i>
                        <span>Leakers</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.seedboxes.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-server"></i>
                        <span>{{ __('staff.seedboxes') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.uploaders.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-upload"></i>
                        <span>{{ __('torrent.uploader') }} {{ __('common.stats') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.internals.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-magic"></i>
                        <span>Internals</span>
                    </a>
                </div>
                <div class="staff-dashboard__actions-section">
                    <h3 class="staff-dashboard__section-subtitle">Actions</h3>
                    <form
                        method="GET"
                        action="{{ route('staff.mass-actions.validate') }}"
                        x-data="confirmation"
                    >
                        @csrf
                        <button
                            x-on:click.prevent="confirmAction"
                            data-b64-deletion-message="{{ base64_encode('Are you sure you want to automatically validate all users even if their email address isn\'t confirmed?') }}"
                            class="staff-dashboard__danger-btn"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-history"></i>
                            {{ __('staff.mass-validate-users') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Logs Panel --}}
            <div x-show="activePanel === 'logs'" x-cloak>
                <h2 class="staff-dashboard__panel-title">
                    <i class="{{ config('other.font-awesome') }} fa-file-alt"></i>
                    {{ __('staff.logs') }}
                </h2>
                <div class="staff-dashboard__links-grid">
                    <a class="staff-dashboard__link-card" href="{{ route('staff.audits.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-clipboard-list"></i>
                        <span>{{ __('staff.audit-log') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.reports.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-flag"></i>
                        <span>{{ __('staff.reports-log') }}</span>
                        @if ($unsolvedReportsCount > 0)
                            <span class="staff-dashboard__badge">{{ $unsolvedReportsCount }}</span>
                        @endif
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.warnings.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-exclamation-circle"></i>
                        <span>{{ __('staff.warnings-log') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.bans.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-ban"></i>
                        <span>{{ __('staff.bans-log') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.authentications.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-sign-in-alt"></i>
                        <span>{{ __('staff.failed-login-log') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.gifts.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-gift"></i>
                        <span>{{ __('staff.gifts-log') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.invites.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-envelope-open"></i>
                        <span>{{ __('staff.invites-log') }}</span>
                    </a>
                    <a class="staff-dashboard__link-card" href="{{ route('staff.notes.index') }}">
                        <i class="{{ config('other.font-awesome') }} fa-sticky-note"></i>
                        <span>{{ __('staff.user-notes') }}</span>
                    </a>
                    @if (auth()->user()->group->is_owner)
                        <a class="staff-dashboard__link-card" href="{{ route('staff.laravel-log.index') }}">
                            <i class="fa fa-file-code"></i>
                            <span>{{ __('staff.laravel-log') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
