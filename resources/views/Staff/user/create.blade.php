@extends('layout.with-main-and-sidebar')

@section('title', __('user.new-user'))

@section('content')
    <div class="panelV2">
        <h2 class="panel__heading">{{ __('user.new-user') }}</h2>
        <div class="panel__body">
            <form class="form" method="POST" action="{{ route('staff.users.store') }}">
                @csrf
                <p class="form__group">
                    <input
                        id="username"
                        class="form__text"
                        type="text"
                        name="username"
                        required
                        placeholder=" "
                        value="{{ old('username') }}"
                    >
                    <label class="form__label" for="username">{{ __('user.username') }}</label>
                </p>
                <p class="form__group">
                    <input
                        id="email"
                        class="form__text"
                        type="email"
                        name="email"
                        required
                        placeholder=" "
                        value="{{ old('email') }}"
                    >
                    <label class="form__label" for="email">{{ __('user.email') }}</label>
                </p>
                <p class="form__group">
                    <input
                        id="password"
                        class="form__text"
                        type="password"
                        name="password"
                        required
                        placeholder=" "
                    >
                    <label class="form__label" for="password">{{ __('user.password') }}</label>
                </p>
                <p class="form__group">
                    <input
                        id="password_confirmation"
                        class="form__text"
                        type="password"
                        name="password_confirmation"
                        required
                        placeholder=" "
                    >
                    <label class="form__label" for="password_confirmation">{{ __('user.confirm-password') }}</label>
                </p>
                <p class="form__group">
                    <select name="group_id" id="group_id" class="form__select">
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <label class="form__label" for="group_id">{{ __('user.group') }}</label>
                </p>
                <p class="form__group">
                    <button type="submit" class="form__button form__button--filled">
                        {{ __('user.add-user') }}
                    </button>
                </p>
            </form>
        </div>
    </div>
@endsection
