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
                Upload banner and background assets. Images are auto-cropped, resized, and optimized to webp.
            </p>
        </div>

        <form class="staff-theme-editor__form" method="POST" action="{{ route('staff.dashboard.theme.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="staff-theme-editor__grid">
                <article class="staff-theme-editor__card" id="banner-editor">
                    <h3 class="staff-theme-editor__title">Site Banner</h3>
                    <p class="staff-theme-editor__guide">Best size: 2400 x 520 (wide panoramic). Accepted: JPG, PNG, WEBP, GIF, AVIF.</p>
                    <img class="staff-theme-editor__preview" src="{{ $themeAssets['banner_url'] }}?v={{ now()->timestamp }}" alt="Current banner preview" />
                    <label class="form__label" for="theme_banner">Upload Banner Image</label>
                    <input id="theme_banner" class="form__file" name="theme_banner" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" />
                </article>

                <article class="staff-theme-editor__card" id="background-editor">
                    <h3 class="staff-theme-editor__title">Site Background</h3>
                    <p class="staff-theme-editor__guide">Best size: 2560 x 1440 (16:9). Accepted: JPG, PNG, WEBP, GIF, AVIF.</p>
                    <img class="staff-theme-editor__preview" src="{{ $themeAssets['background_url'] }}?v={{ now()->timestamp }}" alt="Current background preview" />
                    <label class="form__label" for="theme_background">Upload Background Image</label>
                    <input id="theme_background" class="form__file" name="theme_background" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" />
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
