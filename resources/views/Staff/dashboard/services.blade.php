@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Site Services - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Site Services" />
@endsection

@section('breadcrumbs')
    <li>
        <a href="{{ route('staff.dashboard.index') }}">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb--active">Site Services</li>
@endsection

@section('page', 'page__staff-dashboard--services')

@section('main')
    <section class="panelV2 staff-services" id="site-services">
        <div class="staff-services__head">
            <h2 class="panel__heading">Site Services</h2>
            <p class="staff-services__subtitle">
                Update site identity and mail transport from one focused page.
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
                <span class="staff-services__pill-value">{{ strtoupper($siteServices['mail_mailer']) }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Owner Email</span>
                <span class="staff-services__pill-value">{{ $siteServices['owner_email'] ?: 'Not set' }}</span>
            </span>
            <span class="staff-services__pill">
                <span class="staff-services__pill-label">Mail Host</span>
                <span class="staff-services__pill-value">{{ $siteServices['mail_host'] ?: 'Not set' }}</span>
            </span>
        </div>

        <form class="staff-services__form" method="POST" action="{{ route('staff.dashboard.services.update') }}"
            x-data="{
                mailer: @js(old('mail_mailer', $siteServices['mail_mailer'])),
                host: @js(old('mail_host', $siteServices['mail_host'])),
                port: @js(old('mail_port', $siteServices['mail_port'])),
                encryption: @js(old('mail_encryption', $siteServices['mail_encryption'])),
                applyPreset(preset) {
                    const map = {
                        'plesk-25': { mailer: 'smtp', host: 'localhost', port: '25', encryption: '' },
                        'plesk-587': { mailer: 'smtp', host: 'localhost', port: '587', encryption: 'tls' },
                        'plesk-465': { mailer: 'smtp', host: 'localhost', port: '465', encryption: 'ssl' },
                        'sendmail': { mailer: 'sendmail' },
                    };
                    if (!map[preset]) {
                        return;
                    }
                    this.mailer = map[preset].mailer;
                    if (map[preset].host !== undefined) this.host = map[preset].host;
                    if (map[preset].port !== undefined) this.port = map[preset].port;
                    if (map[preset].encryption !== undefined) this.encryption = map[preset].encryption;
                }
            }">
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
                    <label class="form__label" for="owner_email">Owner Email</label>
                    <input id="owner_email" class="form__text" name="owner_email" type="email" value="{{ old('owner_email', $siteServices['owner_email']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label">Mail Preset</label>
                    <select class="form__select" @change="applyPreset($event.target.value)">
                        <option value="">Custom</option>
                        <option value="plesk-25">Plesk Local Port 25</option>
                        <option value="plesk-587">Plesk Local Port 587 (STARTTLS)</option>
                        <option value="plesk-465">Plesk Local Port 465 (SSL)</option>
                        <option value="sendmail">Sendmail</option>
                    </select>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_mailer">Mail Driver</label>
                    <select id="mail_mailer" class="form__select" name="mail_mailer" x-model="mailer" required>
                        <option value="smtp">SMTP</option>
                        <option value="sendmail">Sendmail</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="ses">Amazon SES</option>
                        <option value="postmark">Postmark</option>
                        <option value="log">Log</option>
                        <option value="array">Array</option>
                        <option value="failover">Failover</option>
                    </select>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_host">Mail Host</label>
                    <input id="mail_host" class="form__text" name="mail_host" type="text" x-model="host" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_port">Mail Port</label>
                    <input id="mail_port" class="form__text" name="mail_port" type="number" min="1" max="65535" x-model="port" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_encryption">Mail Encryption</label>
                    <select id="mail_encryption" class="form__select" name="mail_encryption" x-model="encryption">
                        <option value="">None</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_username">Mail Username</label>
                    <input id="mail_username" class="form__text" name="mail_username" type="text" value="{{ old('mail_username', $siteServices['mail_username']) }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_password">Mail Password</label>
                    <input id="mail_password" class="form__text" name="mail_password" type="password"
                        placeholder="{{ $siteServices['mail_password_set'] ? '(stored - leave blank to keep)' : 'Enter password' }}" />
                </p>
                <p class="staff-services__field">
                    <label class="form__label" for="mail_sendmail_path">Sendmail Path</label>
                    <input id="mail_sendmail_path" class="form__text" name="mail_sendmail_path" type="text"
                        value="{{ old('mail_sendmail_path', $siteServices['mail_sendmail_path']) }}" />
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
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">Site Services</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Site services navigation">
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.index') }}">Back to Dashboard</a>
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.services.index') }}">Reload Services</a>
            </nav>
        </div>
    </section>
@endsection
