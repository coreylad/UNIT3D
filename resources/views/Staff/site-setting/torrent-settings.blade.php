@extends('layout.with-main')

@section('title')
    <title>Torrent Settings - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Torrent Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">Torrent Settings</li>
@endsection

@section('page', 'page__staff-site-setting--torrent')

@section('styles')
    <style>
        .page__staff-site-setting--torrent {
            padding: 2rem 0 3rem;
        }
        .ss-hero {
            background: rgba(67, 160, 71, 0.12);
            border: 1px solid rgba(67, 160, 71, 0.15);
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
            background: rgba(67, 160, 71, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #43A047;
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
            border-left: 4px solid #43A047;
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
            <i class="{{ config('other.font-awesome') }} fa-download"></i>
        </div>
        <div class="ss-hero__text">
            <h1>Torrent Settings</h1>
            <p>Configure torrent metadata, source tags, and magnet link support.</p>
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
        <input type="hidden" name="_section" value="torrent-settings" />

        <label class="ss-toggle" for="category_show_name">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Category Name Labels</span>
                <span class="ss-toggle__desc">Show category name text alongside images on the torrents page</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="category_show_name" value="0" />
                <input id="category_show_name" type="checkbox" name="category_show_name" value="1" @checked(old('category_show_name', $siteSetting->category_show_name ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <label class="ss-toggle" for="torrent_download_check_page">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Download Check Page</span>
                <span class="ss-toggle__desc">Show a confirmation page before downloading torrents</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="torrent_download_check_page" value="0" />
                <input id="torrent_download_check_page" type="checkbox" name="torrent_download_check_page" value="1" @checked(old('torrent_download_check_page', $siteSetting->torrent_download_check_page ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <label class="ss-toggle" for="torrent_magnet">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Magnet Links</span>
                <span class="ss-toggle__desc">Enable magnet link support</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="torrent_magnet" value="0" />
                <input id="torrent_magnet" type="checkbox" name="torrent_magnet" value="1" @checked(old('torrent_magnet', $siteSetting->torrent_magnet ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <div class="ss-divider"></div>

        <div class="ss-grid">
            <p class="form__group">
                <input id="torrent_source" class="form__text" name="torrent_source" type="text" maxlength="100" value="{{ old('torrent_source', $siteSetting->torrent_source) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="torrent_source">Source Tag</label>
                <span class="form__hint">Source value embedded in torrent files</span>
            </p>
            <p class="form__group">
                <input id="torrent_created_by" class="form__text" name="torrent_created_by" type="text" maxlength="255" value="{{ old('torrent_created_by', $siteSetting->torrent_created_by) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="torrent_created_by">Created By</label>
                <span class="form__hint">Creator tag for torrent files</span>
            </p>
        </div>

        <label class="ss-toggle" for="torrent_created_by_append">
            <span class="ss-toggle__info">
                <span class="ss-toggle__title">Append Created By</span>
                <span class="ss-toggle__desc">Append creator tag to existing value instead of replacing</span>
            </span>
            <label class="ss-switch">
                <input type="hidden" name="torrent_created_by_append" value="0" />
                <input id="torrent_created_by_append" type="checkbox" name="torrent_created_by_append" value="1" @checked(old('torrent_created_by_append', $siteSetting->torrent_created_by_append ?? false)) />
                <span class="ss-switch__track"></span>
            </label>
        </label>

        <div class="ss-field">
            <p class="form__group">
                <input id="torrent_comment" class="form__text" name="torrent_comment" type="text" maxlength="500" value="{{ old('torrent_comment', $siteSetting->torrent_comment) }}" placeholder=" " />
                <label class="form__label form__label--floating" for="torrent_comment">Torrent Comment</label>
                <span class="form__hint">Comment embedded in downloaded torrent files</span>
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
