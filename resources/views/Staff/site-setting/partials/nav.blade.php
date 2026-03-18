<section class="panelV2">
    <h2 class="panel__heading">Site Settings</h2>
    <nav class="ss-settings-nav">
        <a href="{{ route('staff.site_settings.branding') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.branding') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-paintbrush"></i>
            Branding &amp; Identity
        </a>
        <a href="{{ route('staff.site_settings.registration') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.registration') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-user-plus"></i>
            Registration &amp; Access
        </a>
        <a href="{{ route('staff.site_settings.invites') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.invites') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-envelope-open"></i>
            Invite System
        </a>
        <a href="{{ route('staff.site_settings.user_defaults') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.user_defaults') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-user-cog"></i>
            User Defaults
        </a>
        <div class="ss-settings-nav__divider"></div>
        <a href="{{ route('staff.site_settings.tracker') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.tracker') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-server"></i>
            Tracker Settings
        </a>
        <a href="{{ route('staff.site_settings.torrent') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.torrent') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-download"></i>
            Torrent Settings
        </a>
        <a href="{{ route('staff.site_settings.hit_run') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.hit_run') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-triangle-exclamation"></i>
            Hit &amp; Run
        </a>
        <a href="{{ route('staff.site_settings.chat') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.chat') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-comments"></i>
            Chat Settings
        </a>
        <div class="ss-settings-nav__divider"></div>
        <a href="{{ route('staff.site_settings.economy') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.economy') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-coins"></i>
            Economy &amp; Freeleech
        </a>
        <a href="{{ route('staff.site_settings.donation') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.donation') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-hand-holding-dollar"></i>
            Donation System
        </a>
        <a href="{{ route('staff.site_settings.graveyard') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.graveyard') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-skull-crossbones"></i>
            Graveyard
        </a>
        <a href="{{ route('staff.site_settings.social') }}"
           class="ss-settings-nav__link {{ Route::is('staff.site_settings.social') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-share-nodes"></i>
            Social Links
        </a>
        <div class="ss-settings-nav__divider"></div>
        <a href="{{ route('staff.email_settings.edit') }}"
           class="ss-settings-nav__link {{ Route::is('staff.email_settings.edit') ? 'ss-settings-nav__link--active' : '' }}">
            <i class="{{ config('other.font-awesome') }} fa-envelope"></i>
            Email &amp; SMTP
        </a>
    </nav>
</section>
