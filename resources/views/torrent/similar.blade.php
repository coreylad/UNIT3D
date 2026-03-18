@extends('layout.with-main')

@php
    $similarTitle = $meta->title ?? $meta->name ?? 'Unknown';
    $similarDate = $meta->release_date ?? $meta->first_air_date ?? $meta->first_release_date ?? '';
    $similarYear = $similarDate !== '' ? \substr((string) $similarDate, 0, 4) : '----';
@endphp

@section('title')
    <title>
        {{ __('common.similar') }} - {{ $similarTitle }}
        ({{ $similarYear }}) -
        {{ config('other.title') }}
    </title>
@endsection

@section('meta')
    <meta
        name="description"
        content="{{ __('common.similar') }} - {{ $similarTitle }} ({{ $similarYear }})"
    />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('torrents.index') }}" class="breadcrumb__link">
            {{ __('torrent.torrents') }}
        </a>
    </li>
    <li class="breadcrumb--active">
        {{ __('common.similar') }} - {{ $similarTitle }}
        ({{ $similarYear }})
    </li>
@endsection

@section('page', 'page__torrent-similar--index')

@section('main')
    @switch(true)
        @case($category->movie_meta)
            @include('torrent.partials.movie-meta')

            @break
        @case($category->tv_meta)
            @include('torrent.partials.tv-meta')

            @break
        @case($category->game_meta)
            @include('torrent.partials.game-meta')

            @break
        @default
            @include('torrent.partials.no-meta')

            @break
    @endswitch
    @livewire('similar-torrent', ['category' => $category, 'tmdbId' => $tmdb, 'igdbId' => $igdb, 'work' => $meta])
@endsection
