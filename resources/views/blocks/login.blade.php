@guest
<section class="panelV2">
    <h2 class="panel__heading">{{ __('auth.login') }}</h2>
    <div class="panel__body">
        <form class="form" method="POST" action="{{ route('login') }}">
            @csrf
            <p class="form__group">
                <input
                    id="username"
                    class="form__text"
                    autocomplete="username"
                    autofocus
                    name="username"
                    required
                    type="text"
                    value="{{ old('username') }}"
                    placeholder=" "
                />
                <label class="form__label form__label--floating" for="username">
                    {{ __('auth.username') }}
                </label>
            </p>
            <p class="form__group">
                <input
                    id="password"
                    class="form__text"
                    autocomplete="current-password"
                    name="password"
                    required
                    type="password"
                    placeholder=" "
                />
                <label class="form__label form__label--floating" for="password">
                    {{ __('auth.password') }}
                </label>
            </p>
            <p class="form__group">
                <label class="form__label">
                    <input
                        id="remember"
                        class="form__checkbox"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        type="checkbox"
                    />
                    {{ __('auth.remember-me') }}
                </label>
            </p>
            @if (config('captcha.enabled'))
                @hiddencaptcha
            @endif

            <button class="form__button form__button--filled">{{ __('auth.login') }}</button>
            @if (Session::has('errors'))
                <ul class="form__errors">
                    @foreach ($errors->all() as $error)
                        <li class="form__error">{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </form>
        <footer class="form__footer">
            @if (! config('other.invite-only'))
                <a class="form__footer-item" href="{{ route('register') }}">
                    {{ __('auth.signup') }}
                </a>
            @elseif (config('other.application_signups'))
                <a class="form__footer-item" href="{{ route('application.create') }}">
                    {{ __('auth.apply') }}
                </a>
            @endif
            <a class="form__footer-item" href="{{ route('password.request') }}">
                {{ __('auth.lost-password') }}
            </a>
            <a class="form__footer-item" href="{{ route('passkey.request') }}">
                {{ __('auth.lost-passkey') }}
            </a>
        </footer>
    </div>
</section>
@endguest
