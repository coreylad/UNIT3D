@extends('layout.with-main')

@section('title')
    <title>Site Settings - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">
        Site Settings
    </li>
@endsection

@section('page', 'page__staff-site-setting--edit')

@section('styles')
    <style>
        .site-settings-section {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            margin-bottom: 1.25em;
            overflow: hidden;
        }
        .site-settings-section--orange { border-left: 4px solid #ff9800; }
        .site-settings-section--blue   { border-left: 4px solid #4a9eff; }
        .site-settings-section--cyan   { border-left: 4px solid #00bcd4; }
        .site-settings-section--purple { border-left: 4px solid #9c27b0; }
        .site-settings-section--green  { border-left: 4px solid #4caf50; }
        .site-settings-section--red    { border-left: 4px solid #ff6b6b; }
        .site-settings-section--pink   { border-left: 4px solid #e91e8c; }
        .site-settings-section__header {
            display: flex;
            align-items: center;
            gap: 0.75em;
            padding: 0.9em 1.25em;
            cursor: pointer;
            user-select: none;
            background: rgba(255, 255, 255, 0.03);
        }
        .site-settings-section__header:hover { background: rgba(255, 255, 255, 0.06); }
        .site-settings-section__icon { font-size: 1.1em; width: 1.4em; text-align: center; }
        .site-settings-section__title { font-weight: 700; font-size: 0.95em; flex: 1; }
        .site-settings-section__hint { font-size: 0.8em; opacity: 0.5; }
        .site-settings-section__chevron { opacity: 0.5; transition: transform 0.2s; }
        .site-settings-section__chevron--open { transform: rotate(90deg); }
        .site-settings-section__body { padding: 1.25em 1.5em; border-top: 1px solid rgba(255,255,255,0.06); }
        .settings-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.25em; }
        @media (max-width: 767px) { .settings-row { grid-template-columns: 1fr; } }
        .settings-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65em 0.9em;
            border-radius: 6px;
            background: rgba(255,255,255,0.04);
            margin-bottom: 0.75em;
            cursor: pointer;
        }
        .settings-toggle__label { display: flex; flex-direction: column; gap: 0.2em; }
        .settings-toggle__title { font-weight: 600; font-size: 0.9em; }
        .settings-toggle__desc { font-size: 0.78em; opacity: 0.5; }
        .toggle-switch { position: relative; width: 42px; height: 22px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-switch__slider {
            position: absolute; inset: 0; border-radius: 22px;
            background: rgba(255,255,255,0.15); transition: background 0.2s; cursor: pointer;
        }
        .toggle-switch__slider::before {
            content: ''; position: absolute; width: 16px; height: 16px;
            left: 3px; top: 3px; border-radius: 50%; background: #fff; transition: transform 0.2s;
        }
        .toggle-switch input:checked + .toggle-switch__slider { background: #4caf50; }
        .toggle-switch input:checked + .toggle-switch__slider::before { transform: translateX(20px); }
    </style>
@endsection

@section('main')
    <section
        class="panelV2"
        x-data="{ open: 'branding', toggle(s) { this.open = this.open === s ? null : s; } }"
    >
        <h2 class="panel__heading">
            <i class="{{ config('other.font-awesome') }} fa-cog"></i>
            Site Settings
        </h2>
        <div class="panel__body">
            @if (session('success'))
                <div class="form__group" style="margin-bottom: 1em;">
                    <span class="badge badge--success">
                        <i class="{{ config('other.font-awesome') }} fa-check"></i>
                        {{ session('success') }}
                    </span>
                </div>
            @endif
            @if ($errors->any())
                <div style="margin-bottom: 1.25em; padding: 0.75em 1em; border-radius: 6px; border-left: 4px solid #ff6b6b; background: rgba(255,107,107,0.08);">
                    <p style="font-weight: 700; margin-bottom: 0.4em; color: #ff6b6b; font-size: 0.9em;">
                        <i class="{{ config('other.font-awesome') }} fa-triangle-exclamation"></i>
                        Please fix the following errors:
                    </p>
                    <ul style="margin: 0; padding-left: 1.25em;">
                        @foreach ($errors->all() as $error)
                            <li style="font-size: 0.88em; margin-bottom: 0.2em;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form class="form" method="POST" action="{{ route('staff.site_settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- BRANDING --}}
                <div class="site-settings-section site-settings-section--orange">
                    <div class="site-settings-section__header" @click="toggle('branding')">
                        <span class="site-settings-section__icon" style="color:#ff9800;"><i class="{{ config('other.font-awesome') }} fa-paintbrush"></i></span>
                        <span class="site-settings-section__title">Branding &amp; Identity</span>
                        <span class="site-settings-section__hint">Site name, subtitle, header image, login message</span>
                        <i class="{{ config('other.font-awesome') }} fa-chevron-right site-settings-section__chevron" :class="open === 'branding' && 'site-settings-section__chevron--open'"></i>
                    </div>
                    <div class="site-settings-section__body" x-show="open === 'branding'" x-cloak>
                        <div class="settings-row">
                            <p class="form__group">
                                <input id="title" class="form__text" name="title" required type="text" maxlength="100" value="{{ old('title', $siteSetting->title) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="title">Site Name *</label>
                            </p>
                            <p class="form__group">
                                <input id="sub_title" class="form__text" name="sub_title" required type="text" maxlength="200" value="{{ old('sub_title', $siteSetting->sub_title) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="sub_title">Site Subtitle *</label>
                            </p>
                        </div>
                        <p class="form__group">
                            <textarea id="meta_description" class="form__textarea" name="meta_description" required maxlength="500" rows="2" placeholder=" ">{{ old('meta_description', $siteSetting->meta_description) }}</textarea>
                            <label class="form__label form__label--floating" for="meta_description">SEO Meta Description *</label>
                        </p>
                        <p class="form__group">
                            <textarea id="login_message" class="form__textarea" name="login_message" maxlength="1000" rows="3" placeholder=" ">{{ old('login_message', $siteSetting->login_message) }}</textarea>
                            <label class="form__label form__label--floating" for="login_message">Login Page Message (optional)</label>
                        </p>
                        <p class="form__group">
                            <label class="form__label" for="header_image" style="display:block;margin-bottom:0.5em;">Header Banner Image <small style="opacity:0.5;">(optional)</small></label>
                            @if ($siteSetting->header_image && file_exists(public_path('img/' . $siteSetting->header_image)))
                                <div style="display:flex;align-items:center;gap:1em;margin-bottom:0.75em;padding:0.75em;border-radius:6px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                    <img src="{{ url('img/' . $siteSetting->header_image) }}?v={{ filemtime(public_path('img/' . $siteSetting->header_image)) }}" alt="Current header banner" style="max-height:50px;max-width:200px;border-radius:4px;object-fit:contain;" />
                                    <div>
                                        <p style="font-size:0.85em;font-weight:600;margin-bottom:0.3em;">{{ $siteSetting->header_image }}</p>
                                        <label style="display:flex;align-items:center;gap:0.4em;font-size:0.85em;cursor:pointer;color:#ff6b6b;">
                                            <input type="checkbox" name="remove_header_image" value="1" class="form__checkbox" /> Remove this image
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <input id="header_image" class="form__file" type="file" name="header_image" accept="image/jpeg,image/png,image/gif,image/webp" />
                            <span class="form__hint">Replaces the site name text in the header nav. JPG, PNG, GIF or WebP &mdash; max 2 MB.</span>
                        </p>
                    </div>
                </div>

                {{-- MAIL / SMTP --}}
                <div class="site-settings-section site-settings-section--cyan">
                    <div class="site-settings-section__header" @click="toggle('mail')">
                        <span class="site-settings-section__icon" style="color:#00bcd4;"><i class="{{ config('other.font-awesome') }} fa-envelope"></i></span>
                        <span class="site-settings-section__title">Mail / SMTP</span>
                        <span class="site-settings-section__hint">Outbound email &mdash; overrides .env MAIL_* values when set</span>
                        <i class="{{ config('other.font-awesome') }} fa-chevron-right site-settings-section__chevron" :class="open === 'mail' && 'site-settings-section__chevron--open'"></i>
                    </div>
                    <div class="site-settings-section__body" x-show="open === 'mail'" x-cloak>
                        <div style="padding:0.6em 0.9em;border-radius:6px;background:rgba(0,188,212,0.08);border-left:3px solid #00bcd4;margin-bottom:1.25em;font-size:0.85em;">
                            <i class="{{ config('other.font-awesome') }} fa-circle-info" style="color:#00bcd4;"></i>
                            Settings here <strong>override</strong> the MAIL_* values in your <code>.env</code>. Leave all blank to use .env defaults.
                        </div>
                        <div class="settings-row">
                            <p class="form__group">
                                <input id="smtp_host" class="form__text" name="smtp_host" type="text" maxlength="255" value="{{ old('smtp_host', $siteSetting->smtp_host) }}" placeholder=" " autocomplete="off" />
                                <label class="form__label form__label--floating" for="smtp_host">SMTP Host</label>
                                <span class="form__hint">e.g. smtp.gmail.com, smtp.sendgrid.net</span>
                            </p>
                            <p class="form__group">
                                <input id="smtp_port" class="form__text" name="smtp_port" type="number" min="1" max="65535" value="{{ old('smtp_port', $siteSetting->smtp_port ?? 587) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="smtp_port">SMTP Port</label>
                                <span class="form__hint">Common: 587 (TLS) &mdash; 465 (SSL) &mdash; 25</span>
                            </p>
                        </div>
                        <div class="settings-row">
                            <p class="form__group">
                                <select id="smtp_encryption" name="smtp_encryption" class="form__select">
                                    <option value="" @selected(old('smtp_encryption', $siteSetting->smtp_encryption ?? '') === '')>None</option>
                                    <option value="tls" @selected(old('smtp_encryption', $siteSetting->smtp_encryption ?? '') === 'tls')>TLS (STARTTLS)</option>
                                    <option value="ssl" @selected(old('smtp_encryption', $siteSetting->smtp_encryption ?? '') === 'ssl')>SSL</option>
                                </select>
                                <label class="form__label form__label--floating" for="smtp_encryption">Encryption</label>
                            </p>
                            <p class="form__group">
                                <input id="smtp_username" class="form__text" name="smtp_username" type="text" maxlength="255" value="{{ old('smtp_username', $siteSetting->smtp_username) }}" placeholder=" " autocomplete="off" />
                                <label class="form__label form__label--floating" for="smtp_username">SMTP Username</label>
                            </p>
                        </div>
                        <div class="settings-row">
                            <p class="form__group">
                                <input id="smtp_password" class="form__text" name="smtp_password" type="password" maxlength="255" value="{{ old('smtp_password', $siteSetting->smtp_password) }}" placeholder=" " autocomplete="new-password" />
                                <label class="form__label form__label--floating" for="smtp_password">SMTP Password</label>
                                <span class="form__hint">Leave blank to keep existing saved password.</span>
                            </p>
                        </div>
                        <div style="height:1px;background:rgba(255,255,255,0.06);margin:0.5em 0 1.25em;"></div>
                        <p style="font-size:0.82em;font-weight:700;opacity:0.6;margin-bottom:0.75em;text-transform:uppercase;letter-spacing:0.05em;">From Address</p>
                        <div class="settings-row">
                            <p class="form__group">
                                <input id="smtp_from_address" class="form__text" name="smtp_from_address" type="email" maxlength="255" value="{{ old('smtp_from_address', $siteSetting->smtp_from_address) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="smtp_from_address">From Email Address</label>
                                <span class="form__hint">e.g. noreply@yourtracker.com</span>
                            </p>
                            <p class="form__group">
                                <input id="smtp_from_name" class="form__text" name="smtp_from_name" type="text" maxlength="255" value="{{ old('smtp_from_name', $siteSetting->smtp_from_name) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="smtp_from_name">From Display Name</label>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- REGISTRATION --}}
                <div class="site-settings-section site-settings-section--green">
                    <div class="site-settings-section__header" @click="toggle('registration')">
                        <span class="site-settings-section__icon" style="color:#4caf50;"><i class="{{ config('other.font-awesome') }} fa-user-plus"></i></span>
                        <span class="site-settings-section__title">Registration &amp; Access</span>
                        <span class="site-settings-section__hint">Open registration, invite mode, download slots</span>
                        <i class="{{ config('other.font-awesome') }} fa-chevron-right site-settings-section__chevron" :class="open === 'registration' && 'site-settings-section__chevron--open'"></i>
                    </div>
                    <div class="site-settings-section__body" x-show="open === 'registration'" x-cloak>
                        <label class="settings-toggle" for="registration_open">
                            <span class="settings-toggle__label">
                                <span class="settings-toggle__title">Open Registration</span>
                                <span class="settings-toggle__desc">Allow new users to register without an invite</span>
                            </span>
                            <label class="toggle-switch">
                                <input type="hidden" name="registration_open" value="0" />
                                <input id="registration_open" type="checkbox" name="registration_open" value="1" @checked(old('registration_open', $siteSetting->registration_open ?? true)) />
                                <span class="toggle-switch__slider"></span>
                            </label>
                        </label>
                        <label class="settings-toggle" for="invite_only">
                            <span class="settings-toggle__label">
                                <span class="settings-toggle__title">Invite Only Mode</span>
                                <span class="settings-toggle__desc">New accounts require an invitation code from an existing member</span>
                            </span>
                            <label class="toggle-switch">
                                <input type="hidden" name="invite_only" value="0" />
                                <input id="invite_only" type="checkbox" name="invite_only" value="1" @checked(old('invite_only', $siteSetting->invite_only ?? false)) />
                                <span class="toggle-switch__slider"></span>
                            </label>
                        </label>
                        <div class="settings-row" style="margin-top:0.5em;">
                            <p class="form__group">
                                <input id="default_download_slots" class="form__text" name="default_download_slots" type="number" min="1" max="999" value="{{ old('default_download_slots', $siteSetting->default_download_slots ?? 8) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="default_download_slots">Default Download Slots</label>
                                <span class="form__hint">Simultaneous downloads allowed per new account</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- TRACKER --}}
                <div class="site-settings-section site-settings-section--purple">
                    <div class="site-settings-section__header" @click="toggle('tracker')">
                        <span class="site-settings-section__icon" style="color:#9c27b0;"><i class="{{ config('other.font-awesome') }} fa-server"></i></span>
                        <span class="site-settings-section__title">Tracker Settings</span>
                        <span class="site-settings-section__hint">Announce interval, category filter bar</span>
                        <i class="{{ config('other.font-awesome') }} fa-chevron-right site-settings-section__chevron" :class="open === 'tracker' && 'site-settings-section__chevron--open'"></i>
                    </div>
                    <div class="site-settings-section__body" x-show="open === 'tracker'" x-cloak>
                        <label class="settings-toggle" for="category_filter_enabled">
                            <span class="settings-toggle__label">
                                <span class="settings-toggle__title">Category Filter Bar</span>
                                <span class="settings-toggle__desc">Show the quick-filter category bar on the torrents listing page</span>
                            </span>
                            <label class="toggle-switch">
                                <input type="hidden" name="category_filter_enabled" value="0" />
                                <input id="category_filter_enabled" type="checkbox" name="category_filter_enabled" value="1" @checked(old('category_filter_enabled', $siteSetting->category_filter_enabled ?? true)) />
                                <span class="toggle-switch__slider"></span>
                            </label>
                        </label>
                        <div class="settings-row" style="margin-top:0.5em;">
                            <p class="form__group">
                                <input id="announce_interval" class="form__text" name="announce_interval" type="number" min="60" max="86400" value="{{ old('announce_interval', $siteSetting->announce_interval ?? 1800) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="announce_interval">Announce Interval (seconds)</label>
                                <span class="form__hint">How often clients re-announce to the tracker. 1800 = 30 min (recommended).</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- SOCIAL --}}
                <div class="site-settings-section site-settings-section--pink">
                    <div class="site-settings-section__header" @click="toggle('social')">
                        <span class="site-settings-section__icon" style="color:#e91e8c;"><i class="{{ config('other.font-awesome') }} fa-share-nodes"></i></span>
                        <span class="site-settings-section__title">Social Links</span>
                        <span class="site-settings-section__hint">Discord, Twitter/X, GitHub</span>
                        <i class="{{ config('other.font-awesome') }} fa-chevron-right site-settings-section__chevron" :class="open === 'social' && 'site-settings-section__chevron--open'"></i>
                    </div>
                    <div class="site-settings-section__body" x-show="open === 'social'" x-cloak>
                        <div class="settings-row">
                            <p class="form__group">
                                <input id="discord_url" class="form__text" name="discord_url" type="url" maxlength="500" value="{{ old('discord_url', $siteSetting->discord_url) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="discord_url">Discord Invite URL</label>
                            </p>
                            <p class="form__group">
                                <input id="twitter_url" class="form__text" name="twitter_url" type="url" maxlength="500" value="{{ old('twitter_url', $siteSetting->twitter_url) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="twitter_url">Twitter / X Profile URL</label>
                            </p>
                        </div>
                        <div class="settings-row">
                            <p class="form__group">
                                <input id="github_url" class="form__text" name="github_url" type="url" maxlength="500" value="{{ old('github_url', $siteSetting->github_url) }}" placeholder=" " />
                                <label class="form__label form__label--floating" for="github_url">GitHub Repository URL</label>
                            </p>
                        </div>
                    </div>
                </div>

                <p class="form__group" style="margin-top:1.5em;">
                    <button class="form__button form__button--filled" type="submit">
                        <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                        {{ __('common.save') }} Changes
                    </button>
                </p>
            </form>
        </div>
    </section>
@endsection