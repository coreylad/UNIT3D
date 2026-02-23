@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Create theme - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumbV2">
        <a href="{{ route('staff.theme_builder.index') }}" class="breadcrumb__link">Theme builder</a>
    </li>
    <li class="breadcrumb--active">Create theme</li>
@endsection

@section('page', 'page__staff-theme-builder--create')

@section('main')
    <section class="panelV2">
        <h2 class="panel__heading">Create theme</h2>
        <div class="panel__body">
            <form class="form" method="POST" action="{{ route('staff.theme_builder.store') }}">
                @csrf
                <p class="form__group">
                    <input
                        id="name"
                        class="form__text"
                        type="text"
                        name="name"
                        required
                        placeholder=" "
                        value="{{ old('name') }}"
                    >
                    <label class="form__label" for="name">Theme name</label>
                </p>
                <p class="form__group">
                    <select name="base_style" id="base_style" class="form__select" required>
                        @foreach ($baseStyles as $value => $label)
                            <option value="{{ $value }}" @selected(old('base_style') == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <label class="form__label" for="base_style">Base style</label>
                </p>
                <p class="form__group">
                    <textarea
                        id="variables"
                        class="form__textarea"
                        name="variables"
                        rows="12"
                        placeholder="--color-blue: #2196f3\n--color-400: #42a5f5\n--background-pipe: url('/public/img/pipes/blue.png')"
                        required
                    >{{ old('variables') }}</textarea>
                    <label class="form__label" for="variables">CSS variables</label>
                </p>
                <p class="form__group">
                    <input
                        id="body_font"
                        class="form__text"
                        type="text"
                        name="body_font"
                        placeholder=" "
                        value="{{ old('body_font') }}"
                    >
                    <label class="form__label" for="body_font">Body font-family</label>
                </p>
                <p class="form__group">
                    <input
                        id="heading_font"
                        class="form__text"
                        type="text"
                        name="heading_font"
                        placeholder=" "
                        value="{{ old('heading_font') }}"
                    >
                    <label class="form__label" for="heading_font">Heading font-family</label>
                </p>
                <p class="form__group">
                    <textarea
                        id="extra_css"
                        class="form__textarea"
                        name="extra_css"
                        rows="6"
                        placeholder="body { letter-spacing: 0.2px; }"
                    >{{ old('extra_css') }}</textarea>
                    <label class="form__label" for="extra_css">Extra CSS (optional)</label>
                </p>
                <p class="form__group">
                    <button type="submit" class="form__button form__button--filled">
                        {{ __('common.create') }} Theme
                    </button>
                </p>
            </form>
        </div>
    </section>
@endsection
