@extends('layout.with-main')

@section('page', 'page__home')

@section('main')
    @if (auth()->user()->settings->style === 18)
        <div class="classic-layout">
            <div class="classic-layout__main">
                <div class="classic-layout__left">
                    @include('blocks.chat')
                </div>
                <div class="classic-layout__right">
                    @include('blocks.login')
                </div>
            </div>
            <div class="classic-layout__bottom">
                @foreach ($blocks as $block)
                    @if ($block !== 'chat')
                        @switch($block)
                            @case('news')
                                @include('blocks.news')

                                @break
                            @case('featured')
                                @include('blocks.featured')

                                @break
                            @case('random_media')
                                @livewire('random-media')

                                @break
                            @case('poll')
                                @include('blocks.poll')

                                @break
                            @case('top_torrents')
                                @livewire('top-torrents')

                                @break
                            @case('top_users')
                                @livewire('top-users')

                                @break
                            @case('latest_topics')
                                @include('blocks.latest-topics')

                                @break
                            @case('latest_posts')
                                @include('blocks.latest-posts')

                                @break
                            @case('latest_comments')
                                @include('blocks.latest-comments')

                                @break
                            @case('online')
                                @include('blocks.online')

                                @break
                        @endswitch
                    @endif
                @endforeach
            </div>
        </div>
    @else
        @foreach ($blocks as $block)
            @switch($block)
                @case('news')
                    @include('blocks.news')

                    @break
                @case('chat')
                    @include('blocks.chat')

                    @break
                @case('featured')
                    @include('blocks.featured')

                    @break
                @case('random_media')
                    @livewire('random-media')

                    @break
                @case('poll')
                    @include('blocks.poll')

                    @break
                @case('top_torrents')
                    @livewire('top-torrents')

                    @break
                @case('top_users')
                    @livewire('top-users')

                    @break
                @case('latest_topics')
                    @include('blocks.latest-topics')

                    @break
                @case('latest_posts')
                    @include('blocks.latest-posts')

                    @break
                @case('latest_comments')
                    @include('blocks.latest-comments')

                    @break
                @case('online')
                    @include('blocks.online')

                    @break
            @endswitch
        @endforeach
    @endif
    @vite('resources/js/unit3d/chat.js')
@endsection
