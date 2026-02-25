<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="UTF-8" />
        <title>{{ __('auth.lost-passkey') }} - {{ config('other.title') }}</title>
        @section('meta')
        <meta
            name="description"
            content="Reset your passkey on {{ config('other.title') }}"
        />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta property="og:title" content="{{ __('auth.lost-passkey') }}" />
        <meta property="og:site_name" content="{{ config('other.title') }}" />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="{{ url('/img/og.png') }}" />
        <meta property="og:description" content="{{ config('unit3d.powered-by') }}" />
        <meta property="og:url" content="{{ url('/') }}" />
        <meta property="og:locale" content="{{ config('app.locale') }}" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        @show
        <link rel="shortcut icon" href="{{ url('/favicon.ico') }}" type="image/x-icon" />
        <link rel="icon" href="{{ url('/favicon.ico') }}" type="image/x-icon" />
        @vite('resources/sass/pages/_auth.scss')
    </head>
    <body>
        <main>
            <section class="auth-form">
                <form class="auth-form__form" method="POST" action="{{ route('passkey.email') }}">
                    @csrf
                    <a class="auth-form__branding" href="{{ route('home.index') }}">
                        <i class="fal fa-tv-retro"></i>
                        <span class="auth-form__site-logo">{{ \config('other.title') }}</span>
                    </a>
                    <h2 style="text-align: center; margin: 1rem 0; font-size: 1.5rem;">{{ __('auth.reset-my-passkey') }}</h2>
                    <p style="text-align: center; margin: 1rem 0; color: #666;">
                        {{ __('auth.passkey-reset-request') }}
                    </p>

                    @if (Session::has('warning') || Session::has('success') || Session::has('info') || Session::has('status'))
                        <ul class="auth-form__important-infos">
                            @if (Session::has('warning'))
                                <li class="auth-form__important-info">
                                    Warning: {{ Session::get('warning') }}
                                </li>
                            @endif

                            @if (Session::has('info'))
                                <li class="auth-form__important-info">
                                    Info: {{ Session::get('info') }}
                                </li>
                            @endif

                            @if (Session::has('success'))
                                <li class="auth-form__important-info">
                                    Success: {{ Session::get('success') }}
                                </li>
                            @endif

                            @if (Session::has('status'))
                                <li class="auth-form__important-info">
                                    {{ Session::get('status') }}
                                </li>
                            @endif
                        </ul>
                    @endif

                    <p class="auth-form__text-input-group">
                        <label class="auth-form__label" for="email">
                            {{ __('auth.email') }}
                        </label>
                        <input
                            id="email"
                            class="auth-form__text-input"
                            autofocus
                            name="email"
                            required
                            type="email"
                            value="{{ old('email') }}"
                        />
                    </p>
                    @if (config('captcha.enabled'))
                        @hiddencaptcha
                    @endif

                    <button class="auth-form__primary-button">
                        {{ __('auth.passkey-reset-request') }}
                    </button>

                    @if ($errors->any())
                        <ul class="auth-form__errors">
                            @foreach ($errors->all() as $error)
                                <li class="auth-form__error">{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </form>
                <footer class="auth-form__footer">
                    <a class="auth-form__footer-item" href="{{ route('login') }}">
                        {{ __('auth.login') }}
                    </a>
                    <a class="auth-form__footer-item" href="{{ route('password.request') }}">
                        {{ __('auth.lost-password') }}
                    </a>
                </footer>
            </section>
        </main>
    </body>
</html>
