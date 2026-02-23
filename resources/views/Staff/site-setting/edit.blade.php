@extends('layout.with-main')

@section('title')
    <title>Site Settings - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">
        Site Settings
    </li>
@endsection

@section('page', 'page__staff-site-setting--edit')

@section('main')
    <section class="panelV2">
        <h2 class="panel__heading">
            <i class="{{ config('other.font-awesome') }} fa-cog"></i>
            Site Settings
        </h2>
        <div class="panel__body">
            @if (session('success'))
                <div class="form__group">
                    <span class="badge badge--success">{{ session('success') }}</span>
                </div>
            @endif
            <form
                class="form"
                method="POST"
                action="{{ route('staff.site_settings.update') }}"
            >
                @csrf
                @method('PATCH')
                <p class="form__group">
                    <input
                        id="title"
                        class="form__text"
                        name="title"
                        required
                        type="text"
                        maxlength="100"
                        value="{{ old('title', $siteSetting->title) }}"
                    />
                    <label class="form__label form__label--floating" for="title">
                        Site Name
                    </label>
                </p>
                <p class="form__group">
                    <input
                        id="sub_title"
                        class="form__text"
                        name="sub_title"
                        required
                        type="text"
                        maxlength="200"
                        value="{{ old('sub_title', $siteSetting->sub_title) }}"
                    />
                    <label class="form__label form__label--floating" for="sub_title">
                        Site Subtitle
                    </label>
                </p>
                <p class="form__group">
                    <textarea
                        id="meta_description"
                        class="form__textarea"
                        name="meta_description"
                        required
                        maxlength="500"
                        rows="3"
                        placeholder=" "
                    >{{ old('meta_description', $siteSetting->meta_description) }}</textarea>
                    <label class="form__label form__label--floating" for="meta_description">
                        SEO Meta Description
                    </label>
                </p>
                <p class="form__group">
                    <textarea
                        id="login_message"
                        class="form__textarea"
                        name="login_message"
                        maxlength="1000"
                        rows="4"
                        placeholder=" "
                    >{{ old('login_message', $siteSetting->login_message) }}</textarea>
                    <label class="form__label form__label--floating" for="login_message">
                        Login Page Message (optional)
                    </label>
                </p>
                @if ($errors->any())
                    <div class="form__group">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li class="form__error">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <p class="form__group">
                    <button class="form__button form__button--filled">
                        {{ __('common.save') }}
                    </button>
                </p>
            </form>
        </div>
    </section>
@endsection
