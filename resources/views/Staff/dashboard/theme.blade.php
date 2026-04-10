@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Theme Editor - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Theme Editor" />
@endsection

@section('breadcrumbs')
    <li>
        <a href="{{ route('staff.dashboard.index') }}">{{ __('staff.staff-dashboard') }}</a>
    </li>
    <li class="breadcrumb--active">Theme Editor</li>
@endsection

@section('page', 'page__staff-dashboard--services')

@section('main')
    <section class="panelV2 staff-theme-editor">
        <div class="staff-theme-editor__head">
            <h2 class="panel__heading">Theme Editor</h2>
            <p class="staff-theme-editor__subtitle">
                Upload banner and background assets. Images are auto-cropped, resized, and optimized when image libraries are available.
            </p>
        </div>

        @if (session('theme_upload_message'))
            <div class="panel__body" style="margin-bottom: 1rem; border: 1px solid rgba(197, 142, 70, 0.5);">
                <strong>Upload Diagnostics:</strong> {{ session('theme_upload_message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="panel__body" style="margin-bottom: 1rem; border: 1px solid rgba(192, 92, 92, 0.55);">
                <strong>Detailed Upload Errors</strong>
                <ul style="margin: 0.75rem 0 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('theme_upload_debug'))
            <details class="panel__body" style="margin-bottom: 1rem; border: 1px solid rgba(86, 115, 174, 0.45);">
                <summary style="cursor: pointer; font-weight: 600;">Show Upload Debug Payload</summary>
                <pre style="white-space: pre-wrap; word-break: break-word; margin-top: 0.75rem;">{{ json_encode(session('theme_upload_debug'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        @endif

        <form class="staff-theme-editor__form" method="POST" action="{{ route('staff.dashboard.theme.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="staff-theme-editor__grid">
                <article class="staff-theme-editor__card" id="banner-editor">
                    <h3 class="staff-theme-editor__title">Site Banner</h3>
                    <p class="staff-theme-editor__guide">Best size: 2400 x 520 (wide panoramic). Accepted: JPG, PNG, WEBP, GIF, BMP.</p>
                    <img class="staff-theme-editor__preview" src="{{ $themeAssets['banner_url'] }}?v={{ now()->timestamp }}" alt="Current banner preview" />
                    <label class="form__label" for="theme_banner">Upload Banner Image</label>
                    <input id="theme_banner" class="form__file" name="theme_banner" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp" />
                </article>

                <article class="staff-theme-editor__card" id="background-editor">
                    <h3 class="staff-theme-editor__title">Site Background</h3>
                    <p class="staff-theme-editor__guide">Best size: 2560 x 1440 (16:9). Accepted: JPG, PNG, WEBP, GIF, BMP.</p>
                    <img class="staff-theme-editor__preview" src="{{ $themeAssets['background_url'] }}?v={{ now()->timestamp }}" alt="Current background preview" />
                    <label class="form__label" for="theme_background">Upload Background Image</label>
                    <input id="theme_background" class="form__file" name="theme_background" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp" />
                </article>
            </div>

            <div class="staff-theme-editor__actions">
                <button class="form__button form__button--filled" type="submit">Save Theme Assets</button>
            </div>
        </form>
    </section>
@endsection

@section('sidebar')
    <section class="panelV2 staff-side-menu-panel">
        <h2 class="panel__heading">Theme Navigation</h2>
        <div class="panel__body">
            <nav class="staff-side-menu" aria-label="Theme editor navigation">
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.index', ['tools' => 'platform']) }}">Back to Platform Tools</a>
                <a class="staff-side-menu__link" href="{{ route('staff.dashboard.services.index') }}">Open Site Services</a>
                <a class="staff-side-menu__link" href="#banner-editor">Jump to Banner</a>
                <a class="staff-side-menu__link" href="#background-editor">Jump to Background</a>
            </nav>
        </div>
    </section>
@endsection
