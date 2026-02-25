@extends('layout.with-main')

@section('title')
    <title>Branding & Identity - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Site Branding Settings - {{ __('staff.staff-dashboard') }}" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">Branding & Identity</li>
@endsection

@section('page', 'page__staff-site-setting--branding')

@section('styles')
    <style>
        .page__staff-site-setting--branding {
            padding: 2rem 0 3rem;
        }
        .ss-hero {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.12) 0%, rgba(255, 152, 0, 0.08) 100%);
            border: 1px solid rgba(255, 152, 0, 0.15);
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
            background: rgba(255, 152, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ff9800;
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
            border-left: 4px solid #ff9800;
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
        .ss-subheading {
            font-size: 0.78rem;
            font-weight: 700;
            opacity: 0.5;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 2rem;
            margin-top: 0;
        }
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
        .ss-upload-preview__meta {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .ss-upload-preview__remove {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            cursor: pointer;
            color: #ff6b6b;
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
            <i class="{{ config('other.font-awesome') }} fa-paintbrush"></i>
        </div>
        <div class="ss-hero__text">
            <h1>Branding & Identity</h1>
            <p>Configure your site's name, tagline, and visual identity.</p>
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

    <form class="form ss-card" method="POST" action="{{ route('staff.site_settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

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

        <div class="ss-field">
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
                        <i class="{{ config('other.font-awesome') }} fa-trash-can"></i> Remove
                    </label>
                </div>
            </div>
        @endif

        <p class="form__group">
            <input id="header_image" class="form__file" type="file" name="header_image" accept="image/jpeg,image/png,image/gif,image/webp" />
            <span class="form__hint">JPG, PNG, GIF or WebP — max 2 MB</span>
        </p>

        {{-- Submit --}}
        <div class="ss-submit">
            <button class="form__button form__button--filled" type="submit">
                <i class="{{ config('other.font-awesome') }} fa-floppy-disk"></i>
                {{ __('common.save') }} Changes
            </button>
        </div>
    </form>
@endsection
