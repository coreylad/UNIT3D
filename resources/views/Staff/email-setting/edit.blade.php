@extends('layout.with-main')

@section('title')
    <title>Email Settings - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Email Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumbV2">
        <a href="{{ route('staff.site_settings.edit') }}" class="breadcrumb__link">
            Site Settings
        </a>
    </li>
    <li class="breadcrumb--active">
        Email & SMTP
    </li>
@endsection

@section('page', 'page__staff-email-setting--edit')

@section('styles')
    <style>
        /* ── Hero banner ── */
        .es-hero {
            background: linear-gradient(135deg, rgba(0, 188, 212, 0.12) 0%, rgba(74, 158, 255, 0.08) 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .es-hero__icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #00bcd4, #4a9eff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #fff;
            flex-shrink: 0;
        }
        .es-hero__text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 0.4rem;
            letter-spacing: -0.01em;
        }
        .es-hero__text p {
            font-size: 0.9rem;
            opacity: 0.6;
            margin: 0;
            line-height: 1.5;
        }

        /* ── Alerts ── */
        .es-alert {
            padding: 1.25rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .es-alert--success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.25);
            color: #66bb6a;
        }
        .es-alert--error {
            background: rgba(255, 107, 107, 0.08);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        .es-alert__icon { font-size: 1.2rem; margin-top: 0.1em; flex-shrink: 0; }
        .es-alert__body { flex: 1; }
        .es-alert__title { font-weight: 700; margin-bottom: 0.4rem; }
        .es-alert ul { margin: 0.5rem 0 0; padding-left: 1.25em; }
        .es-alert li { margin-bottom: 0.25rem; }

        /* ── Section cards ── */
        .es-card {
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 12px;
            margin-bottom: 2rem;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.2s ease;
        }
        .es-card:hover {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.03);
        }

        .es-card__header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .es-card__icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            background: rgba(0, 188, 212, 0.15);
            color: #00bcd4;
        }
        .es-card__title {
            font-weight: 700;
            font-size: 1rem;
            flex: 1;
        }
        .es-card__subtitle {
            font-size: 0.85rem;
            opacity: 0.5;
        }

        .es-card__body {
            padding: 2rem;
        }

        /* ── Two-column and three-column grids ── */
        .es-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .es-grid--single {
            grid-template-columns: 1fr;
        }
        .es-grid--three {
            grid-template-columns: repeat(3, 1fr);
        }

        @media (max-width: 1024px) {
            .es-grid--three {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .es-grid,
            .es-grid--three {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* ── Section spacing ── */
        .es-section {
            margin-bottom: 3rem;
        }
        .es-section:last-child {
            margin-bottom: 0;
        }

        /* ── Subheading ── */
        .es-subheading {
            font-size: 0.85rem;
            font-weight: 700;
            opacity: 0.6;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── Info callout ── */
        .es-callout {
            padding: 1.25rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            background: rgba(0, 188, 212, 0.07);
            border: 1px solid rgba(0, 188, 212, 0.15);
        }
        .es-callout__icon {
            color: #00bcd4;
            margin-top: 0.15em;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .es-callout strong {
            color: #00bcd4;
        }
        .es-callout code {
            background: rgba(0, 188, 212, 0.1);
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            color: #00bcd4;
        }

        /* ── Divider ── */
        .es-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
            margin: 2.5rem 0;
        }

        /* ── Submit area ── */
        .es-submit {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* ── Form fields ── */
        .es-field {
            margin-bottom: 0;
        }

        /* ── Test button ── */
        .es-test-button {
            margin-top: 2rem;
        }

        /* ── Enhanced form field spacing ── */
        .es-card__body .form__group {
            margin-bottom: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .es-card__body .form__text,
        .es-card__body .form__select {
            padding: 0.875rem 1rem !important;
            font-size: 0.95rem;
            line-height: 1.4;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
        }

        .es-card__body .form__text:focus,
        .es-card__body .form__select:focus {
            background: rgba(0, 188, 212, 0.08) !important;
            border-color: rgba(0, 188, 212, 0.3) !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1) !important;
        }

        .es-card__body .form__label {
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0.8;
            text-transform: none;
        }

        .es-card__body .form__label--floating {
            opacity: 0.6;
            font-size: 0.85rem;
        }

        .es-card__body .form__hint {
            font-size: 0.8rem;
            opacity: 0.5;
            margin-top: 0.25rem;
            line-height: 1.4;
            font-style: italic;
        }

        /* ── Grid item spacing ── */
        .es-grid .form__group {
            width: 100%;
        }

        /* ── Callout improvements ── */
        .es-callout {
            background: linear-gradient(135deg, rgba(0, 188, 212, 0.08) 0%, rgba(74, 158, 255, 0.06) 100%);
            border: 1px solid rgba(0, 188, 212, 0.2);
        }

        /* ── Button area improvements ── */
        .es-submit {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .es-submit .form__button {
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .es-submit .form__button--filled {
            background: linear-gradient(135deg, #00bcd4, #4a9eff);
            color: white;
            border: none;
        }

        .es-submit .form__button--filled:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 188, 212, 0.3);
        }

        .es-submit .form__button--secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
        }

        .es-submit .form__button--secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* ── Responsive improvements ── */
        @media (max-width: 768px) {
            .es-hero {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .es-card__body {
                padding: 1.5rem;
            }

            .es-submit {
                justify-content: center;
                flex-direction: column-reverse;
            }

            .es-submit .form__button {
                width: 100%;
            }
        }
    </style>
@endsection

@section('main')
    {{-- Hero Banner --}}
    <div class="es-hero">
        <div class="es-hero__icon">
            <i class="{{ config('other.font-awesome') }} fa-envelope"></i>
        </div>
        <div class="es-hero__text">
            <h1>Email & SMTP Configuration</h1>
            <p>Configure your SMTP server settings and email delivery options for automated notifications.</p>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="es-alert es-alert--success">
            <span class="es-alert__icon"><i class="{{ config('other.font-awesome') }} fa-circle-check"></i></span>
            <div class="es-alert__body">{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="es-alert es-alert--error">
            <span class="es-alert__icon"><i class="{{ config('other.font-awesome') }} fa-triangle-exclamation"></i></span>
            <div class="es-alert__body">
                <div class="es-alert__title">Please fix the following errors:</div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form class="form" method="POST" action="{{ route('staff.email_settings.update') }}">
        @csrf
        @method('PATCH')

        {{-- ═══ SMTP SERVER ═══ --}}
        <div class="es-section">
            <div class="es-card">
                <div class="es-card__header">
                    <span class="es-card__icon"><i class="{{ config('other.font-awesome') }} fa-server"></i></span>
                    <div>
                        <div class="es-card__title">SMTP Server Configuration</div>
                        <div class="es-card__subtitle">Configure your mail server connection settings</div>
                    </div>
                </div>
                <div class="es-card__body">
                    <div class="es-callout">
                        <span class="es-callout__icon"><i class="{{ config('other.font-awesome') }} fa-circle-info"></i></span>
                        <span>Settings here <strong>override</strong> the MAIL_* values in your <code>.env</code> file. Leave all fields blank to use .env defaults.</span>
                    </div>

                    {{-- SMTP Host & Port --}}
                    <div class="es-grid">
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_host">SMTP Host</label>
                            <input
                                id="smtp_host"
                                class="form__text"
                                name="smtp_host"
                                type="text"
                                maxlength="255"
                                value="{{ old('smtp_host', $siteSetting->smtp_host) }}"
                                placeholder="e.g. smtp.gmail.com"
                                autocomplete="off"
                            />
                            <span class="form__hint">e.g. smtp.gmail.com, smtp.sendgrid.net, smtp.mailgun.org</span>
                        </div>
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_port">SMTP Port</label>
                            <input
                                id="smtp_port"
                                class="form__text"
                                name="smtp_port"
                                type="number"
                                min="1"
                                max="65535"
                                value="{{ old('smtp_port', $siteSetting->smtp_port ?? 587) }}"
                                placeholder="587"
                            />
                            <span class="form__hint">Common: 587 (TLS), 465 (SSL), 25 (plain)</span>
                        </div>
                    </div>

                    {{-- Encryption & Username --}}
                    <div class="es-grid">
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_encryption">Encryption Type</label>
                            <select id="smtp_encryption" name="smtp_encryption" class="form__select">
                                <option value="" @selected(old('smtp_encryption', $siteSetting->smtp_encryption ?? '') === '')>None</option>
                                <option value="tls" @selected(old('smtp_encryption', $siteSetting->smtp_encryption ?? '') === 'tls')>TLS (STARTTLS)</option>
                                <option value="ssl" @selected(old('smtp_encryption', $siteSetting->smtp_encryption ?? '') === 'ssl')>SSL/TLS</option>
                            </select>
                            <span class="form__hint">Use TLS (587) or SSL (465)</span>
                        </div>
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_username">SMTP Username</label>
                            <input
                                id="smtp_username"
                                class="form__text"
                                name="smtp_username"
                                type="text"
                                maxlength="255"
                                value="{{ old('smtp_username', $siteSetting->smtp_username) }}"
                                placeholder="your@email.com"
                                autocomplete="off"
                            />
                            <span class="form__hint">Usually your email address</span>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="es-grid es-grid--single">
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_password">SMTP Password</label>
                            <input
                                id="smtp_password"
                                class="form__text"
                                name="smtp_password"
                                type="password"
                                maxlength="255"
                                value="{{ old('smtp_password', $siteSetting->smtp_password) }}"
                                placeholder="••••••••••"
                                autocomplete="new-password"
                            />
                            <span class="form__hint">Leave blank to keep the existing saved password. Use app-specific passwords for Gmail.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ SENDER IDENTITY ═══ --}}
        <div class="es-section">
            <div class="es-card">
                <div class="es-card__header">
                    <span class="es-card__icon"><i class="{{ config('other.font-awesome') }} fa-at"></i></span>
                    <div>
                        <div class="es-card__title">Sender Identity</div>
                        <div class="es-card__subtitle">Configure from address and display name for outgoing emails</div>
                    </div>
                </div>
                <div class="es-card__body">
                    <div class="es-callout">
                        <span class="es-callout__icon"><i class="{{ config('other.font-awesome') }} fa-lightbulb"></i></span>
                        <span>These settings determine how your emails appear in recipients' inboxes.</span>
                    </div>

                    <div class="es-grid">
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_from_address">From Email Address</label>
                            <input
                                id="smtp_from_address"
                                class="form__text"
                                name="smtp_from_address"
                                type="email"
                                maxlength="255"
                                value="{{ old('smtp_from_address', $siteSetting->smtp_from_address) }}"
                                placeholder="noreply@yourtracker.com"
                            />
                            <span class="form__hint">e.g. noreply@yourtracker.com</span>
                        </div>
                        <div class="form__group es-field">
                            <label class="form__label" for="smtp_from_name">From Display Name</label>
                            <input
                                id="smtp_from_name"
                                class="form__text"
                                name="smtp_from_name"
                                type="text"
                                maxlength="255"
                                value="{{ old('smtp_from_name', $siteSetting->smtp_from_name) }}"
                                placeholder="{{ config('other.title') }} Notifications"
                            />
                            <span class="form__hint">e.g. {{ config('other.title') }} Notifications</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="es-submit">
            <a href="{{ route('staff.site_settings.edit') }}" class="form__button form__button--secondary">
                <i class="{{ config('other.font-awesome') }} fa-arrow-left"></i>
                Back to Site Settings
            </a>
            <button class="form__button form__button--filled" type="submit">
                <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                Save Email Settings
            </button>
        </div>
    </form>
@endsection
