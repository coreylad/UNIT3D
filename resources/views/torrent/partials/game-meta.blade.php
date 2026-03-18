@php
    $sourceTorrent = $torrent ?? null;
    $sourceTorrentId = $sourceTorrent?->id;
    $sourceIgdbId = $igdb ?? $meta?->id ?? $sourceTorrent?->igdb;
@endphp

<section class="meta">
    @if ($sourceTorrentId !== null && Storage::disk('torrent-banners')->exists("torrent-banner_{$sourceTorrentId}.jpg"))
        <img
            class="meta__backdrop"
            src="{{ route('authenticated_images.torrent_banner', ['id' => $sourceTorrentId]) }}"
            alt=""
        />
    @elseif (isset($meta) && $meta->first_artwork_image_id)
        <img
            class="meta__backdrop"
            src="https://images.igdb.com/igdb/image/upload/t_screenshot_big/{{ $meta->first_artwork_image_id }}.jpg"
            alt=""
        />
    @endif

    <a
        class="meta__title-link"
        href="{{ $sourceIgdbId ? route('torrents.similar', ['category_id' => $category->id, 'tmdb' => $sourceIgdbId]) : '#' }}"
    >
        <h1 class="meta__title">
            {{ $meta->name ?? 'No meta found' }}
            ({{ substr($meta->first_release_date ?? '', 0, 4) ?? '' }})
        </h1>
    </a>
    <a
        class="meta__poster-link"
        href="{{ $sourceIgdbId ? route('torrents.similar', ['category_id' => $category->id, 'tmdb' => $sourceIgdbId]) : '#' }}"
    >
        <img
            src="{{
                $meta?->cover_image_id
                    ? 'https://images.igdb.com/igdb/image/upload/t_original/' . $meta->cover_image_id . '.jpg'
                    : ($sourceTorrentId !== null && Storage::disk('torrent-covers')->exists("torrent-cover_{$sourceTorrentId}.jpg")
                        ? route('authenticated_images.torrent_cover', ['id' => $sourceTorrentId])
                        : 'https://via.placeholder.com/400x600')
            }}"
            class="meta__poster"
        />
    </a>
    <div class="meta__actions">
        <a class="meta__dropdown-button" href="#">
            <i class="{{ config('other.font-awesome') }} fa-ellipsis-v"></i>
        </a>
        <ul class="meta__dropdown">
            <li>
                <a
                    href="{{
                        route('torrents.create', [
                            'category_id' => $category->id,
                            'title' => rawurlencode(($meta?->name ?? '') . ' ' . ($meta?->first_release_date?->format('Y') ?? '')),
                            'imdb' => $sourceTorrent?->imdb ?? '',
                            'tmdb_movie_id' => $sourceTorrent?->tmdb_movie_id ?? '',
                            'tmdb_tv_id' => $sourceTorrent?->tmdb_tv_id ?? '',
                            'mal' => $sourceTorrent?->mal ?? '',
                            'tvdb' => $sourceTorrent?->tvdb ?? '',
                            'igdb' => $sourceIgdbId ?? '',
                        ])
                    }}"
                >
                    {{ __('common.upload') }}
                </a>
            </li>
            <li>
                <a
                    href="{{
                        route('requests.create', [
                            'category_id' => $category->id,
                            'title' => rawurlencode(($meta?->name ?? '') . ' ' . ($meta?->first_release_date?->format('Y') ?? '')),
                            'imdb' => $sourceTorrent?->imdb ?? '',
                            'tmdb_movie_id' => $sourceTorrent?->tmdb_movie_id ?? '',
                            'tmdb_tv_id' => $sourceTorrent?->tmdb_tv_id ?? '',
                            'mal' => $sourceTorrent?->mal ?? '',
                            'tvdb' => $sourceTorrent?->tvdb ?? '',
                            'igdb' => $sourceIgdbId ?? '',
                        ])
                    }}"
                >
                    Request similar
                </a>
            </li>
            @if ($meta?->id || $sourceIgdbId)
                <li>
                    <form
                        action="{{ route('torrents.similar.update', ['category' => $category, 'metaId' => $meta?->id ?? $sourceIgdbId]) }}"
                        method="post"
                    >
                        @csrf
                        @method('PATCH')

                        <button style="cursor: pointer">
                            Update metadata
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </div>
    <ul class="meta__ids">
        @if (($sourceIgdbId ?? 0) > 0 && $meta?->url)
            <li class="meta__igdb">
                <a
                    class="meta-id-tag"
                    href="{{ $meta->url }}"
                    title="IGDB: {{ $sourceIgdbId }}"
                    target="_blank"
                >
                    <img src="{{ url('/img/meta/igdb.svg') }}" />
                </a>
            </li>
        @endif
    </ul>
    <p class="meta__description">{{ $meta?->summary ?? '' }}</p>
    <div class="meta__chips">
        <section class="meta__chip-container">
            <h2 class="meta__heading">Platforms</h2>
            @foreach ($meta?->platforms ?? [] as $platform)
                <article class="meta-chip-wrapper meta-chip">
                    @if ($platform->image_id)
                        <img
                            class="meta-chip__image"
                            src="https://images.igdb.com/igdb/image/upload/t_logo_med/{{ $platform->image_id }}.png"
                            alt=""
                        />
                    @else
                        <i class="{{ config('other.font-awesome') }} fa-user meta-chip__icon"></i>
                    @endif
                    <h2 class="meta-chip__name">Platform</h2>
                    <h3 class="meta-chip__value">{{ $platform->name }}</h3>
                </article>
            @endforeach
        </section>
        <section class="meta__chip-container">
            <h2 class="meta__heading">Companies</h2>
            @foreach ($meta?->companies ?? [] as $company)
                <article class="meta__company">
                    <a class="meta-chip" href="{{ $company->url }}" target="_blank">
                        @if ($company->logo_image_id)
                            <img
                                class="meta-chip__image"
                                style="object-fit: scale-down"
                                src="https://images.igdb.com/igdb/image/upload/t_logo_med/{{ $company->logo_image_id }}.png"
                                alt=""
                            />
                        @else
                            <i
                                class="{{ config('other.font-awesome') }} fa-camera-movie meta-chip__icon"
                            ></i>
                        @endif
                        <h2 class="meta-chip__name">Company</h2>
                        <h3 class="meta-chip__value">{{ $company->name }}</h3>
                    </a>
                </article>
            @endforeach
        </section>
        <section class="meta__chip-container">
            <h2 class="meta__heading">Extra information</h2>
            <article class="meta-chip-wrapper meta-chip">
                <i class="{{ config('other.font-awesome') }} fa-star meta-chip__icon"></i>
                <h2 class="meta-chip__name">{{ __('torrent.rating') }}</h2>
                <h3 class="meta-chip__value">
                    {{ $meta->rating ?? 0 }}% ({{ $meta->rating_count ?? 0 }}
                    {{ __('torrent.votes') }})
                </h3>
            </article>
            @isset($meta?->first_video_video_id)
                <article class="meta__trailer show-trailer">
                    <a class="meta-chip" href="#">
                        <i
                            class="{{ config('other.font-awesome') }} fa-external-link meta-chip__icon"
                        ></i>
                        <h2 class="meta-chip__name">Trailer</h2>
                        <h3 class="meta-chip__value">View</h3>
                    </a>
                </article>
            @endisset

            @if ($meta?->genres !== [] && $meta?->genres !== null)
                <article class="meta__genres meta-chip">
                    <i
                        class="{{ config('other.font-awesome') }} fa-theater-masks meta-chip__icon"
                    ></i>
                    <h2 class="meta-chip__name">Genres</h2>
                    <h3 class="meta-chip__value">
                        {{ $meta->genres->pluck('name')->join(' / ') }}
                    </h3>
                </article>
            @endif
        </section>
    </div>
</section>

@if ($meta?->first_video_video_id)
    <script nonce="{{ HDVinnie\SecureHeaders\SecureHeaders::nonce() }}">
        document.getElementsByClassName('show-trailer')[0].addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                showConfirmButton: false,
                showCloseButton: true,
                background: 'rgb(35,35,35)',
                width: 970,
                html: '<iframe width="930" height="523" src="https://www.youtube-nocookie.com/embed/{{ $meta->first_video_video_id }}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>',
                title: '<i style="color: #a5a5a5;">{{ $meta->name }} trailer</i>',
                text: '',
            });
        });
    </script>
@endif
