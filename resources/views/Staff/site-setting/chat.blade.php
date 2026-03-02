@extends('layout.with-main')

@section('title')
    <title>Chat Settings - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Chat Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">Chat Settings</li>
@endsection

@section('page', 'page__staff-site-setting--chat')

@section('styles')
    <style>
        .page__staff-site-setting--chat {
            padding: 2rem 0 3rem;
        }
        .ss-hero {
            background: rgba(0, 172, 193, 0.12);
            border: 1px solid rgba(0, 172, 193, 0.15);
            border-radius: 12px;
            padding: 3rem;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .ss-hero__icon {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            background: rgba(0, 172, 193, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #00ACC1;
            flex-shrink: 0;
        }
        .ss-hero__text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 0.4rem;
        }
        .ss-hero__text p {
            font-size: 0.9rem;
            opacity: 0.6;
            margin: 0;
            line-height: 1.5;
        }
        .ss-card {
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 12px;
            border-left: 4px solid #00ACC1;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.02);
            margin-bottom: 3rem;
        }
        .ss-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2.5rem;
        }
        .ss-grid--single {
            grid-template-columns: 1fr;
        }
        .ss-field {
            margin-bottom: 2.5rem;
        }
        .ss-field:last-child {
            margin-bottom: 0;
        }
        .ss-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
            margin: 3rem 0;
        }
        .ss-subheading {
            font-size: 0.78rem;
            font-weight: 700;
            opacity: 0.5;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 2rem;
            margin-top: 0;
        }
        .ss-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .ss-toggle:last-child {
            margin-bottom: 0;
        }
        .ss-toggle:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .ss-toggle__info {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .ss-toggle__title {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .ss-toggle__desc {
            font-size: 0.82rem;
            opacity: 0.5;
            line-height: 1.4;
        }
        .ss-switch {
            position: relative;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        .ss-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
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
        .ss-switch input:checked + .ss-switch__track {
            background: #4caf50;
        }
        .ss-switch input:checked + .ss-switch__track::before {
            transform: translateX(22px);
        }
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
        .ss-alert__icon {
            font-size: 1.2rem;
            margin-top: 0.1em;
            flex-shrink: 0;
        }
        .ss-alert__body {
            flex: 1;
        }
        .ss-alert__title {
            font-weight: 700;
            margin-bottom: 0.4rem;
        }
        .ss-alert ul {
            margin: 0.5rem 0 0;
            padding-left: 1.25em;
        }
        .ss-alert li {
            margin-bottom: 0.25rem;
        }
        .ss-submit {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        .form__group {
            margin-bottom: 0;
        }
        .form__hint {
            margin-top: 0.75rem;
            display: block;
        }
        @media (max-width: 767px) {
            .ss-grid {
                grid-template-columns: 1fr;
            }
            .ss-card {
                padding: 2rem;
            }
            .ss-hero {
                flex-direction: column;
                text-align: center;
                padding: 2rem;
            }
        }
    </style>
@endsection

@section('main')
    {{-- Hero Banner --}}
    <div class="ss-hero">
        <div class="ss-hero__icon">
            <i class="{{ config('other.font-awesome') }} fa-comments"></i>
        </div>
        <div class="ss-hero__text">
            <h1>Chat Settings</h1>
            <p>Configure the chat system, NerdBot, and external chat platform link.</p>
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

    <form class="form ss-card" method="POST" action="{{ route('staff.site_settings.update') }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_section" value="chat" />

        {{-- NerdBot Toggle --}}
        <label class="ss-toggle" for="nerd_bot">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">NerdBot</span>
                <span class="ss-toggle__desc">Enable NerdBot automated chat messages</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="nerd_bot" value="0" />
                <input id="nerd_bot" type="checkbox" name="nerd_bot" value="1" @checked(old('nerd_bot', $siteSetting->nerd_bot ?? true)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <div class="ss-divider"></div>

        {{-- System Chatroom & Message Limit --}}
        <div class="ss-grid">
            <p class="form__group">
                <input id="system_chatroom" class="form__text" name="system_chatroom" type="text" maxlength="100" value="{{ old('system_chatroom', $siteSetting->system_chatroom) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="system_chatroom">System Chatroom</label>
                <span class="form__hint">Chatroom for system messages (name or ID)</span>
            </p>
            <p class="form__group">
                <input id="chat_message_limit" class="form__text" name="chat_message_limit" type="number" min="10" max="1000" value="{{ old('chat_message_limit', $siteSetting->chat_message_limit) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="chat_message_limit">Message History Limit</label>
                <span class="form__hint">Messages to keep per chatroom</span>
            </p>
        </div>

        <div class="ss-divider"></div>

        {{-- External Chat Platform --}}
        <p class="ss-subheading">External Chat Platform</p>

        <div class="ss-grid">
            <p class="form__group">
                <input id="chat_link_name" class="form__text" name="chat_link_name" type="text" maxlength="50" value="{{ old('chat_link_name', $siteSetting->chat_link_name) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="chat_link_name">Platform Name</label>
            </p>
            <p class="form__group">
                <input id="chat_link_icon" class="form__text" name="chat_link_icon" type="text" maxlength="50" value="{{ old('chat_link_icon', $siteSetting->chat_link_icon) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="chat_link_icon">Icon Class</label>
                <span class="form__hint">Font Awesome class e.g. fab fa-discord</span>
            </p>
        </div>

        <div class="ss-field">
            <p class="form__group">
                <input id="chat_link_url" class="form__text" name="chat_link_url" type="url" maxlength="500" value="{{ old('chat_link_url', $siteSetting->chat_link_url) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="chat_link_url">Platform URL</label>
            </p>
        </div>

        {{-- Submit --}}
        <div class="ss-submit">
            <button class="form__button form__button--filled" type="submit">
                <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                {{ __('common.save') }} Changes
            </button>
        </div>
    </form>
@endsection
