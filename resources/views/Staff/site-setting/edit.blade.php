@extends('layout.with-main')

@section('title')
    <title>Site Settings - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Site Settings - {{ __('staff.staff-dashboard') }}" />
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
        /* ── Layout improvements ── */
        .page__staff-site-setting--edit {
            padding-bottom: 3rem;
        }

        /* ── Hero improvements ── */
        .ss-hero {
            margin-bottom: 4rem !important;
        }

        /* ── Card improvements ── */
        .ss-card {
            margin-bottom: 3rem !important;
        }

        /* ── Body spacing ── */
        .ss-card__body {
            padding: 2.5rem !important;
        }

        /* ── Grid improvements ── */
        .ss-grid {
            gap: 3rem 2.5rem !important;
            margin-bottom: 2.5rem !important;
        }

        /* ── Field spacing ── */
        .ss-field {
            margin-bottom: 2.5rem !important;
        }

        .ss-field:last-child {
            margin-bottom: 0 !important;
        }

        /* ── Section dividers ── */
        .ss-divider {
            margin: 3rem 0 !important;
        }

        /* ── Subheading spacing ── */
        .ss-subheading {
            margin-bottom: 2rem !important;
        }

        /* ── Submit area spacing ── */
        .ss-submit {
            margin-top: 4rem !important;
            padding-top: 2.5rem !important;
        }

        /* ── Alerts spacing ── */
        .ss-alert {
            margin-bottom: 2.5rem !important;
        }

        /* ── Callout spacing ── */
        .ss-callout {
            margin-bottom: 2.5rem !important;
        }

        /* ── Form group fixes ── */
        .form__group {
            margin-bottom: 0 !important;
        }

        /* ── Hint text spacing ── */
        .form__hint {
            margin-top: 0.75rem !important;
            display: block !important;
        }

        /* ── Toggle list spacing ── */
        .ss-toggle {
            margin-bottom: 1.5rem !important;
        }

        .ss-toggle:last-child {
            margin-bottom: 0 !important;
        }
    
        /* ── Hero banner ── */
        .ss-hero {
            background: linear-gradient(135deg, rgba(74, 158, 255, 0.12) 0%, rgba(156, 39, 176, 0.08) 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 4rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .ss-hero__icon {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            background: linear-gradient(135deg, #4a9eff, #9c27b0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            flex-shrink: 0;
        }
        .ss-hero__text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 0.4rem;
            letter-spacing: -0.01em;
        }
        .ss-hero__text p {
            font-size: 0.9rem;
            opacity: 0.6;
            margin: 0;
            line-height: 1.5;
        }

        /* ── Alerts ── */
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
        .ss-alert__icon { font-size: 1.2rem; margin-top: 0.1em; flex-shrink: 0; }
        .ss-alert__body { flex: 1; }
        .ss-alert__title { font-weight: 700; margin-bottom: 0.4rem; }
        .ss-alert ul { margin: 0.5rem 0 0; padding-left: 1.25em; }
        .ss-alert li { margin-bottom: 0.25rem; }

        /* ── Section cards ── */
        .ss-card {
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 12px;
            margin-bottom: 3rem;
            overflow: hidden;
            transition: border-color 0.2s;
            background: rgba(255, 255, 255, 0.02);
        }
        .ss-card:hover { 
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.03);
        }
        .ss-card--orange  { border-left: 4px solid #ff9800; }
        .ss-card--cyan    { border-left: 4px solid #00bcd4; }
        .ss-card--green   { border-left: 4px solid #4caf50; }
        .ss-card--purple  { border-left: 4px solid #9c27b0; }
        .ss-card--pink    { border-left: 4px solid #e91e8c; }

        /* ── Section header (clickable) ── */
        .ss-card__header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem 2rem;
            cursor: pointer;
            user-select: none;
            background: rgba(255, 255, 255, 0.025);
            transition: background 0.15s;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .ss-card__header:hover { background: rgba(255, 255, 255, 0.055); }
        .ss-card__icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .ss-card--orange  .ss-card__icon { background: rgba(255, 152, 0, 0.15); color: #ff9800; }
        .ss-card--cyan    .ss-card__icon { background: rgba(0, 188, 212, 0.15); color: #00bcd4; }
        .ss-card--green   .ss-card__icon { background: rgba(76, 175, 80, 0.15); color: #4caf50; }
        .ss-card--purple  .ss-card__icon { background: rgba(156, 39, 176, 0.15); color: #9c27b0; }
        .ss-card--pink    .ss-card__icon { background: rgba(233, 30, 140, 0.15); color: #e91e8c; }
        .ss-card__title { font-weight: 700; font-size: 0.95rem; flex: 1; }
        .ss-card__badge {
            font-size: 0.75rem;
            opacity: 0.45;
            background: rgba(255, 255, 255, 0.06);
            padding: 0.2em 0.65em;
            border-radius: 20px;
        }
        .ss-card__chevron {
            opacity: 0.4;
            font-size: 0.85rem;
            transition: transform 0.25s ease;
        }
        .ss-card__chevron--open { transform: rotate(90deg); }

        /* ── Section body ── */
        .ss-card__body {
            padding: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* ── Two-column grid ── */
        .ss-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem 2.5rem;
            margin-bottom: 2.5rem;
        }
        .ss-grid--single { grid-template-columns: 1fr; }
        @media (max-width: 767px) { .ss-grid { grid-template-columns: 1fr; } }

        /* ── Field spacing within the body ── */
        .ss-field { margin-bottom: 2.5rem; }
        .ss-field:last-child { margin-bottom: 0; }

        /* ── Info callout ── */
        .ss-callout {
            padding: 1.25rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .ss-callout--info {
            background: rgba(0, 188, 212, 0.07);
            border: 1px solid rgba(0, 188, 212, 0.15);
        }
        .ss-callout__icon { color: #00bcd4; margin-top: 0.15em; flex-shrink: 0; }

        /* ── Divider ── */
        .ss-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
            margin: 3rem 0;
        }

        /* ── Sub-heading inside body ── */
        .ss-subheading {
            font-size: 0.78rem;
            font-weight: 700;
            opacity: 0.5;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 2rem;
            margin-top: 0;
        }

        /* ── Toggle row ── */
        .ss-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .ss-toggle:last-child { margin-bottom: 0; }
        .ss-toggle:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .ss-toggle__info { display: flex; flex-direction: column; gap: 0.4rem; }
        .ss-toggle__title { font-weight: 600; font-size: 0.95rem; }
        .ss-toggle__desc { font-size: 0.82rem; opacity: 0.5; line-height: 1.4; }

        /* ── Toggle switch ── */
        .ss-switch { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
        .ss-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
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
        .ss-switch input:checked + .ss-switch__track { background: #4caf50; }
        .ss-switch input:checked + .ss-switch__track::before { transform: translateX(22px); }

        /* ── Upload preview ── */
        .ss-upload-preview {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .ss-upload-preview img {
            max-height: 60px;
            max-width: 250px;
            border-radius: 6px;
            object-fit: contain;
        }
        .ss-upload-preview__meta { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .ss-upload-preview__remove {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            cursor: pointer;
            color: #ff6b6b;
        }

        /* ── Submit area ── */
        .ss-submit {
            margin-top: 4rem;
            padding-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* ── Form fields ── */
        .form__group {
            margin-bottom: 0;
        }

        .form__hint {
            margin-top: 0.75rem;
            display: block;
        }
    </style>
@endsection

@section('main')
    <div x-data="{ open: 'branding', toggle(s) { this.open = this.open === s ? null : s; } }">

        {{-- Hero Banner --}}
        <div class="ss-hero">
            <div class="ss-hero__icon">
                <i class="{{ config('other.font-awesome') }} fa-sliders"></i>
            </div>
            <div class="ss-hero__text">
                <h1>Site Settings</h1>
                <p>Configure your tracker&rsquo;s branding, email delivery, access controls, and social links.</p>
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

        <form class="form" method="POST" action="{{ route('staff.site_settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            {{-- ═══ BRANDING ═══ --}}
            <div class="ss-card ss-card--orange">
                <div class="ss-card__header" @click="toggle('branding')">
                    <span class="ss-card__icon"><i class="{{ config('other.font-awesome') }} fa-paintbrush"></i></span>
                    <span class="ss-card__title">Branding &amp; Identity</span>
                    <span class="ss-card__badge">5 fields</span>
                    <i class="{{ config('other.font-awesome') }} fa-chevron-right ss-card__chevron" :class="open === 'branding' && 'ss-card__chevron--open'"></i>
                </div>
                <div class="ss-card__body" x-show="open === 'branding'" x-cloak>
                    <div class="ss-grid">
                        <p class="form__group">
                            <input id="title" class="form__text" name="title" required type="text" maxlength="100" value="{{ old('title', $siteSetting->title) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="title">Site Name *</label>
                        </p>
                        <p class="form__group">
                            <input id="sub_title" class="form__text" name="sub_title" required type="text" maxlength="200" value="{{ old('sub_title', $siteSetting->sub_title) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="sub_title">Site Subtitle *</label>
                        </p>
                    </div>
                    <div class="ss-field" style="margin-top: 1.5rem;">
                        <p class="form__group">
                            <textarea id="meta_description" class="form__textarea" name="meta_description" required maxlength="500" rows="2" placeholder=" ">{{ old('meta_description', $siteSetting->meta_description) }}</textarea>
                            <label class="form__label form__label--floating" for="meta_description">SEO Meta Description *</label>
                        </p>
                    </div>
                    <div class="ss-field">
                        <p class="form__group">
                            <textarea id="login_message" class="form__textarea" name="login_message" maxlength="1000" rows="3" placeholder=" ">{{ old('login_message', $siteSetting->login_message) }}</textarea>
                            <label class="form__label form__label--floating" for="login_message">Login Page Message (optional)</label>
                        </p>
                    </div>

                    <div class="ss-divider"></div>

                    <p class="ss-subheading">
                        <i class="{{ config('other.font-awesome') }} fa-image" style="margin-right: 0.35em;"></i>
                        Header Banner Image
                    </p>
                    @if ($siteSetting->header_image && file_exists(public_path('img/' . $siteSetting->header_image)))
                        <div class="ss-upload-preview">
                            <img src="{{ url('img/' . $siteSetting->header_image) }}?v={{ filemtime(public_path('img/' . $siteSetting->header_image)) }}" alt="Current header banner" />
                            <div>
                                <div class="ss-upload-preview__meta">{{ $siteSetting->header_image }}</div>
                                <label class="ss-upload-preview__remove">
                                    <input type="checkbox" name="remove_header_image" value="1" class="form__checkbox" />
                                    <i class="{{ config('other.font-awesome') }} fa-trash-can"></i> Remove image
                                </label>
                            </div>
                        </div>
                    @endif
                    <p class="form__group">
                        <input id="header_image" class="form__file" type="file" name="header_image" accept="image/jpeg,image/png,image/gif,image/webp" />
                        <span class="form__hint" style="margin-top: 0.5rem; display: block;">Replaces the site name text in the header nav. JPG, PNG, GIF or WebP &mdash; max 2 MB.</span>
                    </p>
                </div>
            </div>

            {{-- ═══ MAIL / SMTP ═══ --}}
            <div class="ss-card ss-card--cyan">
                <div class="ss-card__header">
                    <span class="ss-card__icon"><i class="{{ config('other.font-awesome') }} fa-envelope"></i></span>
                    <span class="ss-card__title">Email & SMTP Configuration</span>
                    <i class="{{ config('other.font-awesome') }} fa-arrow-up-right ss-card__chevron" style="opacity: 0.6;"></i>
                </div>
                <div class="ss-card__body">
                    <p style="margin: 0 0 1.5rem; opacity: 0.7;">
                        Email settings have been moved to their own dedicated configuration page for better organization.
                    </p>
                    <a href="{{ route('staff.email_settings.edit') }}" class="form__button form__button--filled" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="{{ config('other.font-awesome') }} fa-envelope"></i>
                        Go to Email Settings
                    </a>
                </div>
            </div>

            {{-- ═══ REGISTRATION ═══ --}}
            <div class="ss-card ss-card--green">
                <div class="ss-card__header" @click="toggle('registration')">
                    <span class="ss-card__icon"><i class="{{ config('other.font-awesome') }} fa-user-plus"></i></span>
                    <span class="ss-card__title">Registration &amp; Access</span>
                    <span class="ss-card__badge">3 fields</span>
                    <i class="{{ config('other.font-awesome') }} fa-chevron-right ss-card__chevron" :class="open === 'registration' && 'ss-card__chevron--open'"></i>
                </div>
                <div class="ss-card__body" x-show="open === 'registration'" x-cloak>
                    <label class="ss-toggle" for="registration_open">
                        <span class="ss-toggle__info">
                            <span class="ss-toggle__title">Open Registration</span>
                            <span class="ss-toggle__desc">Allow new users to register without an invite</span>
                        </span>
                        <label class="ss-switch">
                            <input type="hidden" name="registration_open" value="0" />
                            <input id="registration_open" type="checkbox" name="registration_open" value="1" @checked(old('registration_open', $siteSetting->registration_open ?? true)) />
                            <span class="ss-switch__track"></span>
                        </label>
                    </label>
                    <label class="ss-toggle" for="invite_only">
                        <span class="ss-toggle__info">
                            <span class="ss-toggle__title">Invite Only Mode</span>
                            <span class="ss-toggle__desc">New accounts require an invitation code from an existing member</span>
                        </span>
                        <label class="ss-switch">
                            <input type="hidden" name="invite_only" value="0" />
                            <input id="invite_only" type="checkbox" name="invite_only" value="1" @checked(old('invite_only', $siteSetting->invite_only ?? false)) />
                            <span class="ss-switch__track"></span>
                        </label>
                    </label>

                    <div class="ss-divider"></div>

                    <div class="ss-grid ss-grid--single">
                        <p class="form__group">
                            <input id="default_download_slots" class="form__text" name="default_download_slots" type="number" min="1" max="999" value="{{ old('default_download_slots', $siteSetting->default_download_slots ?? 8) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="default_download_slots">Default Download Slots</label>
                            <span class="form__hint">Number of simultaneous downloads allowed for new accounts</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- ═══ TRACKER ═══ --}}
            <div class="ss-card ss-card--purple">
                <div class="ss-card__header" @click="toggle('tracker')">
                    <span class="ss-card__icon"><i class="{{ config('other.font-awesome') }} fa-server"></i></span>
                    <span class="ss-card__title">Tracker Settings</span>
                    <span class="ss-card__badge">2 fields</span>
                    <i class="{{ config('other.font-awesome') }} fa-chevron-right ss-card__chevron" :class="open === 'tracker' && 'ss-card__chevron--open'"></i>
                </div>
                <div class="ss-card__body" x-show="open === 'tracker'" x-cloak>
                    <label class="ss-toggle" for="category_filter_enabled">
                        <span class="ss-toggle__info">
                            <span class="ss-toggle__title">Category Filter Bar</span>
                            <span class="ss-toggle__desc">Show the quick-filter category bar on the torrents listing page</span>
                        </span>
                        <label class="ss-switch">
                            <input type="hidden" name="category_filter_enabled" value="0" />
                            <input id="category_filter_enabled" type="checkbox" name="category_filter_enabled" value="1" @checked(old('category_filter_enabled', $siteSetting->category_filter_enabled ?? true)) />
                            <span class="ss-switch__track"></span>
                        </label>
                    </label>

                    <div class="ss-divider"></div>

                    <div class="ss-grid ss-grid--single">
                        <p class="form__group">
                            <input id="announce_interval" class="form__text" name="announce_interval" type="number" min="60" max="86400" value="{{ old('announce_interval', $siteSetting->announce_interval ?? 1800) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="announce_interval">Announce Interval (seconds)</label>
                            <span class="form__hint">How often clients re-announce to the tracker. 1 800 = 30 min (recommended).</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- ═══ SOCIAL ═══ --}}
            <div class="ss-card ss-card--pink">
                <div class="ss-card__header" @click="toggle('social')">
                    <span class="ss-card__icon"><i class="{{ config('other.font-awesome') }} fa-share-nodes"></i></span>
                    <span class="ss-card__title">Social Links</span>
                    <span class="ss-card__badge">3 fields</span>
                    <i class="{{ config('other.font-awesome') }} fa-chevron-right ss-card__chevron" :class="open === 'social' && 'ss-card__chevron--open'"></i>
                </div>
                <div class="ss-card__body" x-show="open === 'social'" x-cloak>
                    <div class="ss-grid">
                        <p class="form__group">
                            <input id="discord_url" class="form__text" name="discord_url" type="url" maxlength="500" value="{{ old('discord_url', $siteSetting->discord_url) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="discord_url">Discord Invite URL</label>
                        </p>
                        <p class="form__group">
                            <input id="twitter_url" class="form__text" name="twitter_url" type="url" maxlength="500" value="{{ old('twitter_url', $siteSetting->twitter_url) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="twitter_url">Twitter / X Profile URL</label>
                        </p>
                    </div>
                    <div class="ss-grid ss-grid--single" style="margin-top: 1.5rem;">
                        <p class="form__group">
                            <input id="github_url" class="form__text" name="github_url" type="url" maxlength="500" value="{{ old('github_url', $siteSetting->github_url) }}" placeholder=" " />
                            <label class="form__label form__label--floating" for="github_url">GitHub Repository URL</label>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="ss-submit">
                <button class="form__button form__button--filled" type="submit">
                    <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                    {{ __('common.save') }} Changes
                </button>
            </div>
        </form>
    </div>
@endsection