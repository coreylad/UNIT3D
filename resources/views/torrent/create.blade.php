@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Upload - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('torrents.index') }}" class="breadcrumb__link">
            {{ __('torrent.torrents') }}
        </a>
    </li>
    <li class="breadcrumb--active">
        {{ __('common.upload') }}
    </li>
@endsection

@section('nav-tabs')
    <li class="nav-tabV2">
        <a class="nav-tab__link" href="{{ route('torrents.index') }}">
            {{ __('torrent.search') }}
        </a>
    </li>
    <li class="nav-tabV2">
        <a class="nav-tab__link" href="{{ route('trending.index') }}">
            {{ __('common.trending') }}
        </a>
    </li>
    <li class="nav-tabV2">
        <a class="nav-tab__link" href="{{ route('rss.index') }}">
            {{ __('rss.rss') }}
        </a>
    </li>
    <li class="nav-tab--active">
        <a class="nav-tab--active__link" href="{{ route('torrents.create') }}">
            {{ __('common.upload') }}
        </a>
    </li>
@endsection

@section('page', 'page__torrent--create')

@section('main')
    <section
        class="upload panelV2"
        x-data="{
            cat: {{ old('category_id', (int) $category_id) }},
            cats: JSON.parse(atob('{{ base64_encode(json_encode($categories)) }}')),
            tmdb_movie_exists: true,
            tmdb_tv_exists: true,
            imdb_title_exists: true,
            tvdb_tv_exists: true,
            mal_anime_exists: true,
            igdb_game_exists: true,
            showMetadata: false,
            showTechnical: false,
            showAdvanced: false,
        }"
    >
        <h2 class="upload-title panel__heading">
            <i class="{{ config('other.font-awesome') }} fa-file"></i>
            {{ __('torrent.torrent') }}
        </h2>
        <div class="panel__body">
            <form
                name="upload"
                class="upload-form form"
                id="upload-form"
                method="POST"
                action="{{ route('torrents.store') }}"
                enctype="multipart/form-data"
            >
                @csrf
                <!-- REQUIRED SECTION: Core Torrent Information -->
                <fieldset class="form__fieldset" style="margin-bottom: 2em;">
                    <legend class="form__legend">
                        <i class="{{ config('other.font-awesome') }} fa-star" style="color: #ff9800;"></i>
                        Core Information
                    </legend>
                    
                    <p class="form__group">
                        <label for="torrent" class="form__label">
                            Torrent {{ __('torrent.file') }} <span style="color: red;">*</span>
                        </label>
                        <input
                            class="upload-form-file form__file"
                            type="file"
                            accept=".torrent"
                            name="torrent"
                            id="torrent"
                            required
                            @change="uploadExtension.hook(); cat = $refs.catId.value"
                        />
                    </p>

                    <p class="form__group">
                        <input
                            type="text"
                            name="name"
                            id="title"
                            class="form__text"
                            value="{{ $title ?: old('name') }}"
                            required
                        />
                        <label class="form__label form__label--floating" for="title">
                            {{ __('torrent.title') }} <span style="color: red;">*</span>
                        </label>
                    </p>

                    <p class="form__group">
                        <select
                            x-ref="catId"
                            name="category_id"
                            id="autocat"
                            class="form__select"
                            required
                            x-model="cat"
                            @change="cats[cat].type = cats[$event.target.value].type;"
                        >
                            <option hidden selected disabled value=""></option>
                            @foreach ($categories as $id => $category)
                                <option class="form__option" value="{{ $id }}">
                                    {{ $category['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <label class="form__label form__label--floating" for="autocat">
                            {{ __('torrent.category') }} <span style="color: red;">*</span>
                        </label>
                    </p>

                    <p class="form__group">
                        <select name="type_id" id="autotype" class="form__select" required>
                            <option hidden disabled selected value=""></option>
                            @foreach ($types as $type)
                                <option
                                    value="{{ $type->id }}"
                                    @selected(old('type_id') == $type->id)
                                >
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <label class="form__label form__label--floating" for="autotype">
                            {{ __('torrent.type') }} <span style="color: red;">*</span>
                        </label>
                    </p>

                    <p class="form__group">
                        <select name="resolution_id" id="autores" class="form__select">
                            <option hidden disabled selected value=""></option>
                            @foreach ($resolutions as $resolution)
                                <option
                                    value="{{ $resolution->id }}"
                                    @selected(old('resolution_id') == $resolution->id)
                                >
                                    {{ $resolution->name }}
                                </option>
                            @endforeach
                        </select>
                        <label class="form__label form__label--floating" for="autores">
                            {{ __('torrent.resolution') }}
                        </label>
                    </p>

                    @livewire('bbcode-input', ['name' => 'description', 'label' => __('common.description'), 'required' => true])

                    <p class="form__group">
                        <input
                            type="text"
                            name="keywords"
                            id="autokeywords"
                            class="form__text"
                            value="{{ old('keywords') }}"
                            placeholder=" "
                        />
                        <label class="form__label form__label--floating" for="autokeywords">
                            {{ __('torrent.keywords') }} (
                            <i>{{ __('torrent.keywords-example') }}</i>
                            )
                        </label>
                    </p>
                </fieldset>

                <!-- OPTIONAL SECTION: Files & Artwork -->
                <fieldset class="form__fieldset" style="margin-top: 2em; margin-bottom: 2em;">
                    <legend class="form__legend">
                        <button
                            type="button"
                            class="form__legend-toggle"
                            @click="showTechnical = !showTechnical"
                            style="background: none; border: none; cursor: pointer; color: inherit; font: inherit; padding: 0;"
                        >
                            <i class="{{ config('other.font-awesome') }}" :class="showTechnical ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            Files & Artwork
                        </button>
                        <span class="form__legend-hint" style="font-size: 0.85em; font-weight: normal; margin-left: 0.5em;">Optional — Upload supplementary files and images</span>
                    </legend>

                    <div x-show="showTechnical" style="display: none; padding-top: 1em;">
                        <p class="form__group">
                            <label for="nfo" class="form__label">
                                NFO {{ __('torrent.file') }} ({{ __('torrent.optional') }})
                            </label>
                            <input
                                id="nfo"
                                class="upload-form-file form__file"
                                type="file"
                                accept=".nfo"
                                name="nfo"
                            />
                            <span class="form__hint">Text file with release information</span>
                        </p>

                        <h4 style="margin-top: 1.5em; margin-bottom: 0.5em; font-size: 0.95em; font-weight: 600;">
                            <i class="{{ config('other.font-awesome') }} fa-image"></i>
                            Box Art & Artwork
                        </h4>

                        <p class="form__group">
                            <label for="torrent-cover" class="form__label">
                                Poster / Box Art ({{ __('torrent.optional') }})
                            </label>
                            <input
                                id="torrent-cover"
                                class="upload-form-file form__file"
                                type="file"
                                accept=".jpg, .jpeg, .png"
                                name="torrent-cover"
                            />
                            <span class="form__hint">Cover art, poster, or box art image (JPG/PNG). Recommended for all content types</span>
                        </p>

                        <p class="form__group">
                            <label for="torrent-banner" class="form__label">
                                Backdrop / Banner ({{ __('torrent.optional') }})
                            </label>
                            <input
                                id="torrent-banner"
                                class="upload-form-file form__file"
                                type="file"
                                accept=".jpg, .jpeg, .png"
                                name="torrent-banner"
                            />
                            <span class="form__hint">Wide banner or backdrop image (JPG/PNG). Great for featured displays</span>
                        </p>
                    </div>
                </fieldset>
                <!-- OPTIONAL SECTION: Episode & Release Details -->
                <fieldset class="form__fieldset" x-show="cats[cat].type === 'movie' || cats[cat].type === 'tv'" style="display: none; margin-top: 2em; margin-bottom: 2em;">
                    <legend class="form__legend">
                        <button
                            type="button"
                            class="form__legend-toggle"
                            @click="showAdvanced = !showAdvanced"
                            style="background: none; border: none; cursor: pointer; color: inherit; font: inherit; padding: 0;"
                        >
                            <i class="{{ config('other.font-awesome') }}" :class="showAdvanced ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            Episode & Release Details
                        </button>
                    </legend>

                    <div x-show="showAdvanced" style="display: none; padding-top: 1em;">
                            <h4 style="margin-top: 0; margin-bottom: 1em; font-size: 0.95em; font-weight: 600;">
                                {{ __('torrent.season-number') }} & {{ __('torrent.episode-number') }}
                            </h4>
                            <div class="form__group--horizontal">
                                <p class="form__group">
                                    <input
                                        type="text"
                                        name="season_number"
                                        id="season_number"
                                        class="form__text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        value="{{ old('season_number') }}"
                                        x-bind:required="cats[cat].type === 'tv'"
                                    />
                                    <label class="form__label form__label--floating" for="season_number">
                                        {{ __('torrent.season-number') }}
                                    </label>
                                    <span class="form__hint">
                                        Numeric digits only. Use 0 for specials and complete packs.
                                    </span>
                                </p>
                                <p class="form__group">
                                    <input
                                        type="text"
                                        name="episode_number"
                                        id="episode_number"
                                        class="form__text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        value="{{ old('episode_number') }}"
                                        x-bind:required="cats[cat].type === 'tv'"
                                    />
                                    <label class="form__label form__label--floating" for="episode_number">
                                        {{ __('torrent.episode-number') }}
                                    </label>
                                    <span class="form__hint">
                                        Numeric digits only. Use 0 for season/complete packs.
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Distributor & Region -->
                        <div>
                            <h4 style="margin-top: 1em; margin-bottom: 1em; font-size: 0.95em; font-weight: 600;">
                                {{ __('torrent.distributor') }} & {{ __('torrent.region') }}
                            </h4>
                            <div class="form__group--horizontal">
                                <p class="form__group">
                                    <select
                                        name="distributor_id"
                                        id="autodis"
                                        class="form__select"
                                        x-data="{ distributor: '' }"
                                        x-model="distributor"
                                        x-bind:class="distributor === '' ? 'form__select--default' : ''"
                                    >
                                        <option value="">{{ __('common.other') }}</option>
                                        <option selected disabled hidden value=""></option>
                                        @foreach ($distributors as $distributor)
                                            <option
                                                value="{{ $distributor->id }}"
                                                @selected(old('distributor_id') == $distributor->id)
                                            >
                                                {{ $distributor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label class="form__label form__label--floating" for="autodis">
                                        {{ __('torrent.distributor') }} <span style="font-size: 0.85em;">(full disc only)</span>
                                    </label>
                                </p>
                                <p class="form__group">
                                    <select
                                        name="region_id"
                                        id="autoreg"
                                        class="form__select"
                                        x-data="{ region: '' }"
                                        x-model="region"
                                        x-bind:class="region === '' ? 'form__select--default' : ''"
                                    >
                                        <option value="">{{ __('common.other') }}</option>
                                        <option selected disabled hidden value=""></option>
                                        @foreach ($regions as $region)
                                            <option
                                                value="{{ $region->id }}"
                                                @selected(old('region_id') == $region->id)
                                            >
                                                {{ $region->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label class="form__label form__label--floating" for="autoreg">
                                        {{ __('torrent.region') }} <span style="font-size: 0.85em;">(full disc only)</span>
                                    </label>
                                </p>
                            </div>
                        </div>
                    </div>
                </fieldset>
                <!-- OPTIONAL SECTION: Media Metadata -->
                <fieldset class="form__fieldset" x-show="cats[cat].type === 'movie' || cats[cat].type === 'tv' || cats[cat].type === 'game'" style="display: none; margin-top: 2em; margin-bottom: 2em;">
                    <legend class="form__legend">
                        <button
                            type="button"
                            class="form__legend-toggle"
                            @click="showMetadata = !showMetadata"
                            style="background: none; border: none; cursor: pointer; color: inherit; font: inherit; padding: 0;"
                        >
                            <i class="{{ config('other.font-awesome') }}" :class="showMetadata ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            Media Metadata
                        </button>
                        <span class="form__legend-hint" style="font-size: 0.85em; font-weight: normal; margin-left: 0.5em;">Optional — For movies, TV shows, and games</span>
                    </legend>

                    <div x-show="showMetadata" style="display: none; padding-top: 1em;">
                        <!-- TMDB Movie -->
                        <div class="form__group--vertical" x-show="cats[cat].type === 'movie'">
                            <p class="form__group">
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="movie_exists_on_tmdb"
                                    name="movie_exists_on_tmdb"
                                    value="1"
                                    @checked(old('movie_exists_on_tmdb', true))
                                    x-model="tmdb_movie_exists"
                                />
                                <label class="form__label" for="movie_exists_on_tmdb">
                                    This movie exists on TMDB
                                </label>
                                <output name="apimatch" id="apimatch" for="torrent"></output>
                            </p>
                            <p class="form__group" x-show="tmdb_movie_exists">
                                <input type="hidden" name="tmdb_movie_id" value="0" />
                                <input
                                    type="text"
                                    name="tmdb_movie_id"
                                    id="auto_tmdb_movie"
                                    class="form__text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    placeholder=" "
                                    x-bind:value="cats[cat].type === 'movie' && tmdb_movie_exists ? '{{ old('tmdb_movie_id', $movieId) }}' : ''"
                                    x-bind:required="cats[cat].type === 'movie' && tmdb_movie_exists"
                                />
                                <label class="form__label form__label--floating" for="auto_tmdb_movie">
                                    TMDB Movie ID
                                </label>
                                <span class="form__hint">Numeric digits only.</span>
                            </p>
                        </div>

                        <!-- TMDB TV -->
                        <div class="form__group--vertical" x-show="cats[cat].type === 'tv'">
                            <p class="form__group">
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="tv_exists_on_tmdb"
                                    name="tv_exists_on_tmdb"
                                    value="1"
                                    @checked(old('tv_exists_on_tmdb', true))
                                    x-model="tmdb_tv_exists"
                                />
                                <label class="form__label" for="tv_exists_on_tmdb">
                                    This TV show exists on TMDB
                                </label>
                                <output name="apimatch" id="apimatch" for="torrent"></output>
                            </p>
                            <p class="form__group" x-show="tmdb_tv_exists">
                                <input type="hidden" name="tmdb_tv_id" value="0" />
                                <input
                                    type="text"
                                    name="tmdb_tv_id"
                                    id="auto_tmdb_tv"
                                    class="form__text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    placeholder=" "
                                    x-bind:value="cats[cat].type === 'tv' && tmdb_tv_exists ? '{{ old('tmdb_tv_id', $tvId) }}' : ''"
                                    x-bind:required="cats[cat].type === 'tv' && tmdb_tv_exists"
                                />
                                <label class="form__label form__label--floating" for="auto_tmdb_tv">
                                    TMDB TV ID
                                </label>
                                <span class="form__hint">Numeric digits only.</span>
                            </p>
                        </div>

                        <!-- IMDB -->
                        <div class="form__group--vertical" x-show="cats[cat].type === 'movie' || cats[cat].type === 'tv'">
                            <p class="form__group">
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="title_exists_on_imdb"
                                    name="title_exists_on_imdb"
                                    value="1"
                                    @checked(old('title_exists_on_imdb', true))
                                    x-model="imdb_title_exists"
                                />
                                <label class="form__label" for="title_exists_on_imdb">
                                    This title exists on IMDB
                                </label>
                            </p>
                            <p class="form__group" x-show="imdb_title_exists">
                                <input type="hidden" name="imdb" value="0" />
                                <input
                                    type="text"
                                    name="imdb"
                                    id="autoimdb"
                                    class="form__text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    placeholder=" "
                                    x-bind:value="
                                        (cats[cat].type === 'movie' || cats[cat].type === 'tv') && imdb_title_exists
                                            ? '{{ old('imdb', $imdb) }}'
                                            : ''
                                    "
                                    x-bind:required="(cats[cat].type === 'movie' || cats[cat].type === 'tv') && imdb_title_exists"
                                    x-on:paste="
                                        matches = $event.clipboardData.getData('text').match(/tt0*(\d{7,})/);

                                        if (matches !== null) {
                                            $el.value = Number(matches[1]);
                                            $event.preventDefault();
                                        }
                                    "
                                />
                                <label class="form__label form__label--floating" for="autoimdb">
                                    IMDB ID
                                </label>
                                <span class="form__hint">Numeric digits only. Paste IMDb links to auto-extract ID.</span>
                            </p>
                        </div>

                        <!-- TVDB -->
                        <div class="form__group--vertical" x-show="cats[cat].type === 'tv'">
                            <p class="form__group">
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="tv_exists_on_tvdb"
                                    name="tv_exists_on_tvdb"
                                    value="1"
                                    @checked(old('tv_exists_on_tvdb', true))
                                    x-model="tvdb_tv_exists"
                                />
                                <label class="form__label" for="tv_exists_on_tvdb">
                                    This TV show exists on TVDB
                                </label>
                            </p>
                            <p class="form__group" x-show="tvdb_tv_exists">
                                <input type="hidden" name="tvdb" value="0" />
                                <input
                                    type="text"
                                    name="tvdb"
                                    id="autotvdb"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    placeholder=" "
                                    x-bind:value="cats[cat].type === 'tv' && tvdb_tv_exists ? '{{ old('tvdb', $tvdb) }}' : ''"
                                    class="form__text"
                                    x-bind:required="cats[cat].type === 'tv' && tvdb_tv_exists"
                                />
                                <label class="form__label form__label--floating" for="autotvdb">
                                    TVDB ID
                                </label>
                                <span class="form__hint">Numeric digits only.</span>
                            </p>
                        </div>

                        <!-- MAL -->
                        <div class="form__group--vertical" x-show="cats[cat].type === 'movie' || cats[cat].type === 'tv'">
                            <p class="form__group">
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="anime_exists_on_mal"
                                    name="anime_exists_on_mal"
                                    value="1"
                                    @checked(old('anime_exists_on_mal', true))
                                    x-model="mal_anime_exists"
                                />
                                <label class="form__label" for="anime_exists_on_mal">
                                    This anime exists on MyAnimeList
                                </label>
                            </p>
                            <p class="form__group" x-show="mal_anime_exists">
                                <input type="hidden" name="mal" value="0" />
                                <input
                                    type="text"
                                    name="mal"
                                    id="automal"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    x-bind:value="
                                        (cats[cat].type === 'movie' || cats[cat].type === 'tv') && mal_anime_exists
                                            ? '{{ old('mal', $mal) }}'
                                            : ''
                                    "
                                    x-bind:required="(cats[cat].type === 'movie' || cats[cat].type === 'tv') && mal_anime_exists"
                                    class="form__text"
                                    placeholder=" "
                                />
                                <label class="form__label form__label--floating" for="automal">
                                    MAL ID
                                </label>
                                <span class="form__hint">Numeric digits only.</span>
                            </p>
                        </div>

                        <!-- IGDB -->
                        <div class="form__group--vertical" x-show="cats[cat].type === 'game'">
                            <p class="form__group">
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="game_exists_on_igdb"
                                    name="game_exists_on_igdb"
                                    value="1"
                                    @checked(old('game_exists_on_igdb', true))
                                    x-model="igdb_game_exists"
                                />
                                <label class="form__label" for="game_exists_on_igdb">
                                    This game exists on IGDB
                                </label>
                            </p>
                            <p class="form__group" x-show="igdb_game_exists">
                                <input
                                    type="text"
                                    name="igdb"
                                    id="autoigdb"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    x-bind:value="cats[cat].type === 'game' && igdb_game_exists ? '{{ old('igdb', $igdb) }}' : ''"
                                    class="form__text"
                                    x-bind:required="cats[cat].type === 'game' && igdb_game_exists"
                                />
                                <label class="form__label form__label--floating" for="autoigdb">
                                    IGDB ID
                                    <b>({{ __('torrent.required-games') }})</b>
                                </label>
                            </p>
                        </div>
                    </div>
                </fieldset>

                <!-- OPTIONAL SECTION: Technical Details -->
                <fieldset class="form__fieldset" x-show="cats[cat].type === 'movie' || cats[cat].type === 'tv'" style="display: none; margin-top: 2em; margin-bottom: 2em;">
                    <legend class="form__legend">
                        <button
                            type="button"
                            class="form__legend-toggle"
                            @click="showAdvanced = !showAdvanced"
                            style="background: none; border: none; cursor: pointer; color: inherit; font: inherit; padding: 0;"
                        >
                            <i class="{{ config('other.font-awesome') }}" :class="showAdvanced ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            Technical Details
                        </button>
                        <span class="form__legend-hint" style="font-size: 0.85em; font-weight: normal; margin-left: 0.5em;">Optional — MediaInfo and BDInfo for video content</span>
                    </legend>

                    <div x-show="showAdvanced" style="display: none; padding-top: 1em;">
                        <p class="form__group">
                            <textarea
                                id="upload-form-mediainfo"
                                name="mediainfo"
                                class="form__textarea"
                                placeholder=" "
                            >
{{ old('mediainfo') }}</textarea
                            >
                            <label class="form__label form__label--floating" for="upload-form-mediainfo">
                                {{ __('torrent.media-info-parser') }}
                            </label>
                            <span class="form__hint">Paste MediaInfo output for video/audio codec details</span>
                        </p>

                        <p class="form__group">
                            <textarea
                                id="upload-form-bdinfo"
                                name="bdinfo"
                                class="form__textarea"
                                placeholder=" "
                            >
{{ old('bdinfo') }}</textarea
                            >
                            <label class="form__label form__label--floating" for="upload-form-bdinfo">
                                BDInfo (quick summary)
                            </label>
                            <span class="form__hint">For Blu-ray disc content analysis</span>
                        </p>
                    </div>
                </fieldset>
                <!-- OPTIONAL SECTION: Advanced Options -->
                <fieldset class="form__fieldset" style="margin-top: 2em; margin-bottom: 2em;">
                    <legend class="form__legend">
                        <button
                            type="button"
                            class="form__legend-toggle"
                            @click="showMetadata = !showMetadata"
                            style="background: none; border: none; cursor: pointer; color: inherit; font: inherit; padding: 0;"
                        >
                            <i class="{{ config('other.font-awesome') }}" :class="showMetadata ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            Advanced Options
                        </button>
                    </legend>

                    <div x-show="showMetadata" style="display: none; padding-top: 1em;">
                        <p class="form__group">
                            <input type="hidden" name="anon" value="0" />
                            <input
                                type="checkbox"
                                class="form__checkbox"
                                id="anon"
                                name="anon"
                                value="1"
                                @checked(old('anon'))
                            />
                            <label class="form__label" for="anon">
                                <i class="{{ config('other.font-awesome') }} fa-mask"></i>
                                {{ __('common.anonymous') }}
                                <span class="form__hint-inline">Upload anonymously without revealing your username</span>
                            </label>
                        </p>

                        <p class="form__group">
                            <input type="hidden" name="personal_release" value="0" />
                            <input
                                type="checkbox"
                                class="form__checkbox"
                                id="personal_release"
                                name="personal_release"
                                value="1"
                                @checked(old('personal_release'))
                            />
                            <label class="form__label" for="personal_release">
                                <i class="{{ config('other.font-awesome') }} fa-user"></i>
                                Personal release
                                <span class="form__hint-inline">Mark as your own personal project or remix</span>
                            </label>
                        </p>

                        @if (auth()->user()->group->is_trusted)
                            <p class="form__group">
                                <input type="hidden" name="mod_queue_opt_in" value="0" />
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="mod_queue_opt_in"
                                    name="mod_queue_opt_in"
                                    value="1"
                                    @checked(old('mod_queue_opt_in'))
                                />
                                <label class="form__label" for="mod_queue_opt_in">
                                    <i class="{{ config('other.font-awesome') }} fa-list-check"></i>
                                    Opt in to moderation queue
                                    <span class="form__hint-inline">This will require moderator approval before being visible</span>
                                </label>
                            </p>
                        @endif

                        @if (auth()->user()->group->is_modo || auth()->user()->internals()->exists())
                            <hr style="margin: 1.5em 0; opacity: 0.2;">
                            <h4 style="margin-top: 1em; margin-bottom: 1em; font-size: 0.95em; font-weight: 600;">
                                <i class="{{ config('other.font-awesome') }} fa-gavel"></i>
                                Moderator Options
                            </h4>

                            <p class="form__group">
                                <input type="hidden" name="internal" value="0" />
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="internal"
                                    name="internal"
                                    value="1"
                                    @checked(old('internal'))
                                />
                                <label class="form__label" for="internal">
                                    {{ __('torrent.internal') }} release
                                </label>
                            </p>

                            <p class="form__group">
                                <input type="hidden" name="refundable" value="0" />
                                <input
                                    type="checkbox"
                                    class="form__checkbox"
                                    id="refundable"
                                    name="refundable"
                                    value="1"
                                    @checked(old('refundable'))
                                />
                                <label class="form__label" for="refundable">
                                    {{ __('torrent.refundable') }}
                                </label>
                            </p>

                            <p class="form__group">
                                <select name="free" id="free" class="form__select">
                                    <option
                                        value="0"
                                        @selected(old('free') === '0' || old('free') === null)
                                    >
                                        {{ __('common.no') }} freeleech
                                    </option>
                                    <option value="25" @selected(old('free') === '25')>25% {{ __('torrent.freeleech') }}</option>
                                    <option value="50" @selected(old('free') === '50')>50% {{ __('torrent.freeleech') }}</option>
                                    <option value="75" @selected(old('free') === '75')>75% {{ __('torrent.freeleech') }}</option>
                                    <option value="100" @selected(old('free') === '100')>100% {{ __('torrent.freeleech') }}</option>
                                </select>
                                <label class="form__label form__label--floating" for="free">
                                    {{ __('torrent.freeleech') }}
                                </label>
                            </p>
                        @endif
                    </div>
                </fieldset>

                <p class="form__group">
                    <button
                        type="submit"
                        class="form__button form__button--filled"
                        name="post"
                        value="true"
                        id="post"
                    >
                        {{ __('common.submit') }}
                    </button>
                </p>
            </form>
        </div>
    </section>
@endsection

@if ($user->can_upload ?? $user->group->can_upload)
    @section('sidebar')
        <section class="panelV2">
            <h2 class="panel__heading">
                <i class="{{ config('other.font-awesome') }} fa-info"></i>
                {{ __('common.info') }}
            </h2>
            <div class="panel__body">
                <p>
                    {{ __('torrent.announce-url') }}:
                    <a
                        x-data="upload"
                        data-announce-url="{{ route('announce', ['passkey' => $user->passkey]) }}"
                        x-on:click.prevent="copy"
                        href="{{ route('announce', ['passkey' => $user->passkey]) }}"
                    >
                        {{ route('announce', ['passkey' => $user->passkey]) }}
                    </a>
                </p>
                <p>
                    {{ __('torrent.announce-url-desc', ['source' => config('torrent.source')]) }}
                </p>
                <a href="{{ config('other.upload-guide_url') }}">
                    {{ __('torrent.announce-url-desc-url') }}
                </a>
            </div>
        </section>
    @endsection
@endif

@section('javascripts')
    <script src="{{ asset('build/unit3d/tmdb.js') }}" crossorigin="anonymous"></script>
    <script src="{{ asset('build/unit3d/parser.js') }}" crossorigin="anonymous"></script>
    <script src="{{ asset('build/unit3d/helper.js') }}" crossorigin="anonymous"></script>
    <script src="{{ asset('build/unit3d/imgbb.js') }}" crossorigin="anonymous"></script>
    <script nonce="{{ HDVinnie\SecureHeaders\SecureHeaders::nonce('script') }}">
        document.addEventListener('alpine:init', () => {
            Alpine.data('upload', () => ({
                copy() {
                    navigator.clipboard.writeText(this.$el.dataset.announceUrl);
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        icon: 'success',
                        title: 'Copied to clipboard!',
                    });
                },
            }));
        });
    </script>
@endsection
