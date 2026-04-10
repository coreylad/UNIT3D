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

    <section class="panelV2 staff-services" id="site-services">
        <div class="staff-services__head">
            <h2 class="panel__heading">Site Services</h2>
            <p class="staff-services__subtitle">
                Update core site identity and mail transport settings without leaving dashboard.
            </p>
        </div>

        <div class="staff-services__pill-row">
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Site Name</span>
                <span class="staff-services__pill-value">{{ $siteServices['site_name'] }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Site Title</span>
                <span class="staff-services__pill-value">{{ $siteServices['site_title'] }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Mailer</span>
                <span class="staff-services__pill-value">{{ $siteServices['mail_mailer'] }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Owner Email</span>
                <span class="staff-services__pill-value">{{ $siteServices['owner_email'] ?: 'Not set' }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Mail Host</span>
                <span class="staff-services__pill-value">{{ $siteServices['mail_host'] ?: 'Not set' }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">From Address</span>
                <span class="staff-services__pill-value">{{ $siteServices['mail_from_address'] ?: 'Not set' }}</span>
            </span>
        </div>

        <form class="staff-services__form" method="POST" action="{{ route('staff.dashboard.services.update') }}">
            @csrf
            <div class="staff-services__fields">
                <p class="staff-services__field">
                    <label class="form__label" for="site_name">Site Name</label>
                    <input id="site_name" class="form__text" name="site_name" type="text" value="{{ old('site_name', $siteServices['site_name']) }}" required />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="site_title">Site Title</label>
                    <input id="site_title" class="form__text" name="site_title" type="text" value="{{ old('site_title', $siteServices['site_title']) }}" required />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="site_subtitle">Site Subtitle</label>
                    <input id="site_subtitle" class="form__text" name="site_subtitle" type="text" value="{{ old('site_subtitle', $siteServices['site_subtitle']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="site_url">Site URL</label>
                    <input id="site_url" class="form__text" name="site_url" type="url" value="{{ old('site_url', $siteServices['site_url']) }}" required />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_mailer">Mail Driver</label>
                    <select id="mail_mailer" class="form__select" name="mail_mailer" required>
                        @foreach (['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array', 'failover'] as $mailer)
                            <option value="{{ $mailer }}" @selected(old('mail_mailer', $siteServices['mail_mailer']) === $mailer)>
                                {{ strtoupper($mailer) }}
                            </option>
                        @endforeach
                    </select>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="owner_email">Owner Email</label>
                    <input id="owner_email" class="form__text" name="owner_email" type="email" value="{{ old('owner_email', $siteServices['owner_email']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_host">Mail Host</label>
                    <input id="mail_host" class="form__text" name="mail_host" type="text" value="{{ old('mail_host', $siteServices['mail_host']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_port">Mail Port</label>
                    <input id="mail_port" class="form__text" name="mail_port" type="number" min="1" max="65535" value="{{ old('mail_port', $siteServices['mail_port']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_encryption">Mail Encryption</label>
                    <select id="mail_encryption" class="form__select" name="mail_encryption">
                        <option value="" @selected(old('mail_encryption', $siteServices['mail_encryption']) === '')>None</option>
                        <option value="tls" @selected(old('mail_encryption', $siteServices['mail_encryption']) === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('mail_encryption', $siteServices['mail_encryption']) === 'ssl')>SSL</option>
                    </select>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_username">Mail Username</label>
                    <input id="mail_username" class="form__text" name="mail_username" type="text" value="{{ old('mail_username', $siteServices['mail_username']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_password">Mail Password</label>
                    <input id="mail_password" class="form__text" name="mail_password" type="text" value="{{ old('mail_password', $siteServices['mail_password']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_from_address">From Address</label>
                    <input id="mail_from_address" class="form__text" name="mail_from_address" type="email" value="{{ old('mail_from_address', $siteServices['mail_from_address']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_from_name">From Name</label>
                    <input id="mail_from_name" class="form__text" name="mail_from_name" type="text" value="{{ old('mail_from_name', $siteServices['mail_from_name']) }}" />
                </p>
            </div>
            <div class="staff-services__actions">
                <button class="form__button form__button--filled" type="submit">Save Site Services</button>
            </div>
        </form>
    </section>

    <div class="dashboard__menus">
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-link"></i>
                {{ __('staff.links') }}
            </h2>
            <div class="panel__body">
                <p class="form__group form__group--horizontal">
                    <a class="form__button form__button--text" href="{{ route('home.index') }}">
                        {{ __('staff.frontend') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.dashboard.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        {{ __('staff.staff-dashboard') }}
                    </a>
                </p>
                @if (auth()->user()->group->is_owner)
                    <p class="form__group form__group--horizontal">
                        <a
                            class="form__button form__button--text"
                            href="{{ route('staff.backups.index') }}"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-hdd"></i>
                            {{ __('backup.backup') }}
                            {{ __('backup.manager') }}
                        </a>
                    </p>
                    <p class="form__group form__group--horizontal">
                        <a
                            class="form__button form__button--text"
                            href="{{ route('staff.commands.index') }}"
                        >
                            <i class="fab fa-laravel"></i>
                            Commands
                        </a>
                    </p>

                    @if (config('donation.is_enabled'))
                        <p class="form__group form__group--horizontal">
                            <a
                                class="form__button form__button--text"
                                href="{{ route('staff.donations.index') }}"
                            >
                                <i class="{{ config('other.font-awesome') }} fa-money-bill"></i>
                                Donations
                            </a>
                        </p>
                        <p class="form__group form__group--horizontal">
                            <a
                                class="form__button form__button--text"
                                href="{{ route('staff.gateways.index') }}"
                            >
                                <i class="{{ config('other.font-awesome') }} fa-money-bill"></i>
                                Gateways
                            </a>
                        </p>
                        <p class="form__group form__group--horizontal">
                            <a
                                class="form__button form__button--text"
                                href="{{ route('staff.packages.index') }}"
                            >
                                <i class="{{ config('other.font-awesome') }} fa-money-bill"></i>
                                Packages
                            </a>
                        </p>
                    @endif
                @endif
            </div>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-wrench"></i>
                Communications Control
            </h2>
            <div class="panel__body">
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.statuses.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-comment-dots"></i>
                        {{ __('staff.statuses') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.chatrooms.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-comment-dots"></i>
                        {{ __('staff.rooms') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.bots.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-robot"></i>
                        {{ __('staff.bots') }}
                    </a>
                </p>
                <div class="form__group form__group--horizontal">
                    <form
                        method="POST"
                        action="{{ route('staff.flush.chat') }}"
                        x-data="confirmation"
                    >
                        @csrf
                        <button
                            x-on:click.prevent="confirmAction"
                            data-b64-deletion-message="{{ base64_encode('Are you sure you want to delete all chatbox messages in all chatrooms (including private chatbox messages)?') }}"
                            class="form__button form__button--text"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-broom"></i>
                            {{ __('staff.flush-chat') }}
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-wrench"></i>
                Platform and Content Control
            </h2>
            <div class="panel__body">
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.articles.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-newspaper"></i>
                        {{ __('staff.articles') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.events.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-calendar-star"></i>
                        {{ __('event.events') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.bon_exchanges.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-coins"></i>
                        {{ __('staff.bon-exchange') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.bon_earnings.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-coins"></i>
                        {{ __('staff.bon-earnings') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.blacklisted_clients.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-ban"></i>
                        {{ __('common.blacklist') }}
                    </a>
                </p>
                @if (auth()->user()->group->is_admin)
                    <p class="form__group form__group--horizontal">
                        <a
                            class="form__button form__button--text"
                            href="{{ route('staff.forum_categories.index') }}"
                        >
                            <i class="fab fa-wpforms"></i>
                            {{ __('staff.forums') }}
                        </a>
                    </p>
                @endif

                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.pages.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.pages') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.polls.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-chart-pie"></i>
                        {{ __('staff.polls') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.ticket_categories.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-tags"></i>
                        {{ __('staff.ticket-categories') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.ticket_priorities.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-exclamation-triangle"></i>
                        {{ __('staff.ticket-priorities') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.whitelisted_image_urls.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-globe"></i>
                        Whitelisted image URLs
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.wiki_categories.index') }}"
                    >
                        <i class="fab fa-wikipedia-w"></i>
                        Wikis
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.blocked_ips.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-ban"></i>
                        {{ __('staff.blocked-ips') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.playlist_categories.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-list"></i>
                        Playlist categories
                    </a>
                </p>
            </div>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-wrench"></i>
                Torrent Operations
            </h2>
            <div class="panel__body">
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.moderation.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        {{ __('staff.torrent-moderation') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.categories.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        {{ __('staff.torrent-categories') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.types.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        {{ __('staff.torrent-types') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.resolutions.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        {{ __('staff.torrent-resolutions') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.regions.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Torrent regions
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.distributors.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Torrent distributors
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.automatic_torrent_freeleeches.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Automatic torrent freeleeches
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.peers.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Peers
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.histories.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Histories
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.torrent_downloads.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Downloads
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.torrent_trumps.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Trumps
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.unregistered_info_hashes.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        Unregistered info hashes
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.rss.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-rss"></i>
                        {{ __('staff.rss') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.media_languages.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-columns"></i>
                        {{ __('common.media-languages') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.cheated_torrents.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-question"></i>
                        Cheated torrents
                    </a>
                </p>
                @if (config('announce.log_announces'))
                    <p class="form__group form__group--horizontal">
                        <a
                            class="form__button form__button--text"
                            href="{{ route('staff.announces.index') }}"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-chart-bar"></i>
                            Announces
                        </a>
                    </p>
                @endif

                @if (! config('announce.external_tracker.is_enabled'))
                    <div class="form__group form__group--horizontal">
                        <form
                            method="POST"
                            action="{{ route('staff.flush.peers') }}"
                            x-data="confirmation"
                        >
                            @csrf
                            <button
                                x-on:click.prevent="confirmAction"
                                data-b64-deletion-message="{{ base64_encode('Are you sure you want to delete all ghost peers?') }}"
                                class="form__button form__button--text"
                            >
                                <i class="{{ config('other.font-awesome') }} fa-ghost"></i>
                                {{ __('staff.flush-ghost-peers') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-wrench"></i>
                User and Security Operations
            </h2>
            <div class="panel__body">
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.applications.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-list"></i>
                        {{ __('staff.applications') }} ({{ $pendingApplicationsCount }})
                        @if ($pendingApplicationsCount > 0)
                            <x-animation.notification />
                        @endif
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.users.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-users"></i>
                        {{ __('staff.user-search') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.apikeys.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        {{ __('user.apikeys') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.passkeys.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        {{ __('staff.passkeys') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.rsskeys.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        {{ __('user.rsskeys') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.email_updates.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        {{ __('user.email-updates') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.password_reset_histories.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-key"></i>
                        {{ __('user.password-resets') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.watchlist.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-eye"></i>
                        Watchlist
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.mass_private_message.create') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-envelope-square"></i>
                        {{ __('staff.mass-pm') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.mass_email.create') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-paper-plane"></i>
                        {{ __('staff.mass-email') }}
                    </a>
                </p>
                <div class="form__group form__group--horizontal">
                    <form
                        method="GET"
                        action="{{ route('staff.mass-actions.validate') }}"
                        x-data="confirmation"
                    >
                        @csrf
                        <button
                            x-on:click.prevent="confirmAction"
                            data-b64-deletion-message="{{ base64_encode('Are you sure you want to automatically validate all users even if their email address isn\'t confirmed?') }}"
                            class="form__button form__button--text"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-history"></i>
                            {{ __('staff.mass-validate-users') }}
                        </button>
                    </form>
                </div>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.cheaters.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-question"></i>
                        {{ __('staff.possible-leech-cheaters') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.leakers.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-faucet-drip"></i>
                        Leakers
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.seedboxes.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-server"></i>
                        {{ __('staff.seedboxes') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.uploaders.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-upload"></i>
                        {{ __('torrent.uploader') }} {{ __('common.stats') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.internals.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-magic"></i>
                        Internals
                    </a>
                </p>
                @if (auth()->user()->group->is_admin)
                    <p class="form__group form__group--horizontal">
                        <a
                            class="form__button form__button--text"
                            href="{{ route('staff.groups.index') }}"
                        >
                            <i class="{{ config('other.font-awesome') }} fa-users"></i>
                            {{ __('staff.groups') }}
                        </a>
                    </p>
                @endif
            </div>
        </section>
        <section class="panelV2 panel--grid-item">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-file"></i>
                Compliance and Logs
            </h2>
            <div class="panel__body">
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.audits.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.audit-log') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.bans.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.bans-log') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.authentications.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.failed-login-log') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.gifts.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.gifts-log') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.invites.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.invites-log') }}
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.notes.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.user-notes') }}
                    </a>
                </p>
                @if (auth()->user()->group->is_owner)
                    <p class="form__group form__group--horizontal">
                        <a
                            class="form__button form__button--text"
                            href="{{ route('staff.laravel-log.index') }}"
                        >
                            <i class="fa fa-file"></i>
                            {{ __('staff.laravel-log') }}
                        </a>
                    </p>
                @endif

                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.reports.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.reports-log') }} ({{ $unsolvedReportsCount }})
                        @if ($unsolvedReportsCount > 0)
                            <x-animation.notification />
                        @endif
                    </a>
                </p>
                <p class="form__group form__group--horizontal">
                    <a
                        class="form__button form__button--text"
                        href="{{ route('staff.warnings.index') }}"
                    >
                        <i class="{{ config('other.font-awesome') }} fa-file"></i>
                        {{ __('staff.warnings-log') }}
                    </a>
                </p>
            </div>
        </section>
    </div>
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">Control Navigator</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Staff side navigation">
                <details class="staff-side-menu__group" open>
                    <summary class="staff-side-menu__summary">
                        Priority Queue
                        <span class="staff-side-menu__badge">{{ $unsolvedReportsCount + $pendingApplicationsCount }}</span>
                    </summary>
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
                </details>

                <details class="staff-side-menu__group">
                    <summary class="staff-side-menu__summary">Moderation Tools</summary>
                    <a class="staff-side-menu__link" href="{{ route('staff.users.index') }}">User Search</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.watchlist.index') }}">Watchlist</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.cheaters.index') }}">Cheater Detection</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.seedboxes.index') }}">Seedboxes</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.warnings.index') }}">Warnings Log</a>
                </details>

                <details class="staff-side-menu__group">
                    <summary class="staff-side-menu__summary">Content and Tracker</summary>
                    <a class="staff-side-menu__link" href="#site-services">Site Services</a>
                    <a class="staff-side-menu__link" href="#site-banner-editor">Banner Editor</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.articles.index') }}">Articles</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.events.index') }}">Events</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.categories.index') }}">Categories</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.types.index') }}">Types</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.resolutions.index') }}">Resolutions</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.peers.index') }}">Peers</a>
                </details>

                <details class="staff-side-menu__group">
                    <summary class="staff-side-menu__summary">Communications and Logs</summary>
                    <a class="staff-side-menu__link" href="{{ route('staff.statuses.index') }}">Statuses</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.chatrooms.index') }}">Chatrooms</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.bots.index') }}">Bots</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.audits.index') }}">Audits</a>
                    <a class="staff-side-menu__link" href="{{ route('staff.authentications.index') }}">Authentications</a>
                </details>
            </nav>
        </div>
    </section>

    <section class="panelV2 staff-services" id="site-services">
        <h2 class="panel__heading">
            <i class="{{ config('other.font-awesome') }} fa-server"></i> Site Services
        </h2>
        <form class="panel__body staff-services__body" method="POST" action="{{ route('staff.dashboard.services.update') }}">
            @csrf

            {{-- Site Identity --}}
            <details class="staff-services__group" open>
                <summary class="staff-services__summary">
                    <i class="{{ config('other.font-awesome') }} fa-globe"></i> Site Identity
                </summary>
                <div class="staff-services__fields">
                    <label class="form__label" for="ssp_site_name">Site Name</label>
                    <input id="ssp_site_name" class="form__text" name="site_name" type="text"
                        value="{{ old('site_name', $siteServices['site_name']) }}" required />

                    <label class="form__label" for="ssp_site_title">Title</label>
                    <input id="ssp_site_title" class="form__text" name="site_title" type="text"
                        value="{{ old('site_title', $siteServices['site_title']) }}" required />

                    <label class="form__label" for="ssp_site_subtitle">Subtitle</label>
                    <input id="ssp_site_subtitle" class="form__text" name="site_subtitle" type="text"
                        value="{{ old('site_subtitle', $siteServices['site_subtitle']) }}" />

                    <label class="form__label" for="ssp_site_url">Site URL</label>
                    <input id="ssp_site_url" class="form__text" name="site_url" type="url"
                        value="{{ old('site_url', $siteServices['site_url']) }}" required />

                    <label class="form__label" for="ssp_owner_email">Owner Email</label>
                    <input id="ssp_owner_email" class="form__text" name="owner_email" type="email"
                        value="{{ old('owner_email', $siteServices['owner_email']) }}" />
                </div>
            </details>

            {{-- Mail Configuration --}}
            <details class="staff-services__group"
                x-data="{
                    mailer: @js(old('mail_mailer', $siteServices['mail_mailer'])),
                    host: @js(old('mail_host', $siteServices['mail_host'])),
                    port: @js(old('mail_port', $siteServices['mail_port'])),
                    encryption: @js(old('mail_encryption', $siteServices['mail_encryption'])),
                    username: @js(old('mail_username', $siteServices['mail_username'])),
                    password: '',
                    fromAddress: @js(old('mail_from_address', $siteServices['mail_from_address'])),
                    fromName: @js(old('mail_from_name', $siteServices['mail_from_name'])),
                    sendmailPath: @js(old('mail_sendmail_path', $siteServices['mail_sendmail_path'])),
                    get isSmtp() { return this.mailer === 'smtp'; },
                    get isSendmail() { return this.mailer === 'sendmail'; },
                    applyPreset(p) {
                        const map = {
                            'plesk-25':  { mailer: 'smtp', host: 'localhost', port: '25',  encryption: '' },
                            'plesk-587': { mailer: 'smtp', host: 'localhost', port: '587', encryption: 'tls' },
                            'plesk-465': { mailer: 'smtp', host: 'localhost', port: '465', encryption: 'ssl' },
                            'sendmail':  { mailer: 'sendmail' },
                            'log':       { mailer: 'log' },
                        };
                        const preset = map[p];
                        if (!preset) return;
                        if (preset.mailer !== undefined) this.mailer = preset.mailer;
                        if (preset.host !== undefined) this.host = preset.host;
                        if (preset.port !== undefined) this.port = preset.port;
                        if (preset.encryption !== undefined) this.encryption = preset.encryption;
                    }
                }">
                <summary class="staff-services__summary">
                    <i class="{{ config('other.font-awesome') }} fa-envelope"></i> Mail Configuration
                    <span class="staff-services__mailer-badge" x-text="mailer.toUpperCase()"></span>
                </summary>
                <div class="staff-services__fields">

                    <label class="form__label">Quick Preset</label>
                    <select class="form__select staff-services__preset-select"
                        @change="applyPreset($event.target.value)">
                        <option value="">— Custom —</option>
                        <optgroup label="Plesk Built-in Mail">
                            <option value="plesk-25">Plesk Local &middot; Port 25</option>
                            <option value="plesk-587">Plesk Local &middot; Port 587 (STARTTLS)</option>
                            <option value="plesk-465">Plesk Local &middot; Port 465 (SSL)</option>
                        </optgroup>
                        <optgroup label="Other">
                            <option value="sendmail">Sendmail / Postfix</option>
                            <option value="log">Log Only (Testing)</option>
                        </optgroup>
                    </select>

                    <label class="form__label" for="ssp_mail_mailer">Driver</label>
                    <select id="ssp_mail_mailer" class="form__select" name="mail_mailer"
                        x-model="mailer" required>
                        <option value="smtp">SMTP</option>
                        <option value="sendmail">Sendmail</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="ses">Amazon SES</option>
                        <option value="postmark">Postmark</option>
                        <option value="log">Log</option>
                        <option value="array">Array (Disabled)</option>
                    </select>

                    <div x-show="isSendmail" class="staff-services__smtp-group">
                        <label class="form__label" for="ssp_sendmail_path">Sendmail Path</label>
                        <input id="ssp_sendmail_path" class="form__text" name="mail_sendmail_path"
                            type="text" x-model="sendmailPath" />
                    </div>

                    <div x-show="isSmtp" class="staff-services__smtp-group">
                        <label class="form__label" for="ssp_mail_host">Host</label>
                        <input id="ssp_mail_host" class="form__text" name="mail_host"
                            type="text" x-model="host" />

                        <label class="form__label" for="ssp_mail_port">Port</label>
                        <input id="ssp_mail_port" class="form__text" name="mail_port"
                            type="number" min="1" max="65535" x-model="port" />

                        <label class="form__label" for="ssp_mail_encryption">Encryption</label>
                        <select id="ssp_mail_encryption" class="form__select" name="mail_encryption"
                            x-model="encryption">
                            <option value="">None</option>
                            <option value="tls">TLS (STARTTLS)</option>
                            <option value="ssl">SSL</option>
                        </select>

                        <label class="form__label" for="ssp_mail_username">Username</label>
                        <input id="ssp_mail_username" class="form__text" name="mail_username"
                            type="text" x-model="username" autocomplete="off" />

                        <label class="form__label" for="ssp_mail_password">Password</label>
                        <input id="ssp_mail_password" class="form__text" name="mail_password"
                            type="password" x-model="password" autocomplete="new-password"
                            placeholder="{{ $siteServices['mail_password_set'] ? '(stored — leave blank to keep)' : 'Enter password' }}" />
                    </div>

                    <label class="form__label" for="ssp_mail_from_address">From Address</label>
                    <input id="ssp_mail_from_address" class="form__text" name="mail_from_address"
                        type="email" x-model="fromAddress" />

                    <label class="form__label" for="ssp_mail_from_name">From Name</label>
                    <input id="ssp_mail_from_name" class="form__text" name="mail_from_name"
                        type="text" x-model="fromName" />
                </div>
            </details>

            <div class="staff-services__actions">
                <button class="form__button form__button--filled" type="submit">
                    <i class="{{ config('other.font-awesome') }} fa-save"></i> Save Services
                </button>
            </div>
        </form>
    </section>
    <section class="panelV2 staff-banner-editor" id="site-banner-editor">
        <h2 class="panel__heading">Site Banner Editor</h2>
        <div class="panel__body">
            <p class="staff-banner-editor__text">
                Upload a PNG banner to replace the header image shown above the site navigation.
            </p>
            <img
                class="staff-banner-editor__preview"
                src="{{ asset('img/auth/The_Void_Login_Page.png') }}?v={{ file_exists(public_path('img/auth/The_Void_Login_Page.png')) ? filemtime(public_path('img/auth/The_Void_Login_Page.png')) : now()->timestamp }}"
                alt="Current site banner preview"
            />
            <form
                class="staff-banner-editor__form"
                method="POST"
                action="{{ route('staff.dashboard.banner.update') }}"
                enctype="multipart/form-data"
            >
                @csrf
                <label class="form__label" for="site_banner">Upload PNG (max 12 MB)</label>
                <input
                    id="site_banner"
                    class="form__file"
                    name="site_banner"
                    type="file"
                    accept="image/png"
                    required
                />
                <button class="form__button form__button--filled" type="submit">
                    Save Banner
                </button>
            </form>
        </div>
    </section>

    <section class="panelV2">
                        <a class="staff-side-menu__link" href="{{ route('staff.authentications.index') }}">Authentications</a>
        <h2 class="panel__heading">SSL certificate</h2>
        <dl class="key-value">
            <div class="key-value__group">
                <dt>URL</dt>
                <dd>{{ config('app.url') }}</dd>
            </div>
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
