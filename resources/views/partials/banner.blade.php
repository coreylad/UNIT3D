@php
    $bannerImagePath = $siteSetting->header_image && file_exists(public_path('img/' . $siteSetting->header_image))
        ? url('img/' . $siteSetting->header_image) . '?v=' . filemtime(public_path('img/' . $siteSetting->header_image))
        : url('img/banner.jpg');
    $bannerStyle = $siteSetting->homepage_banner_style === 'full' ? 'full' : 'compact';
@endphp

<div class="banner banner--{{ $bannerStyle }} {{ Route::is('home.index') ? 'banner--homepage' : 'banner--inner' }}">
    <img class="banner__image" src="{{ $bannerImagePath }}" alt="{{ $siteSetting->title }}" />
    @if (Route::is('home.index'))
        <div class="banner__content">
            <span class="banner__eyebrow">Welcome to {{ $siteSetting->title }}</span>
            <h1 class="banner__title">{{ $siteSetting->sub_title }}</h1>
            <p class="banner__description">{{ $siteSetting->meta_description }}</p>
        </div>
    @endif
</div>
