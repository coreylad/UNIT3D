@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Theme builder - {{ __('staff.staff-dashboard') }} - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">Theme builder</li>
@endsection

@section('page', 'page__staff-theme-builder--index')

@section('main')
    <section class="panelV2">
        <header class="panel__header">
            <h2 class="panel__heading">Theme builder</h2>
            <div class="panel__actions">
                <a class="panel__action form__button form__button--text" href="{{ route('staff.theme_builder.create') }}">
                    {{ __('common.create') }} Theme
                </a>
            </div>
        </header>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Base style</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($themes as $theme)
                        <tr>
                            <td>{{ $theme['name'] ?? 'Untitled' }}</td>
                            <td>{{ $baseStyles[$theme['base_style'] ?? 2] ?? 'Unknown' }}</td>
                            <td>{{ $theme['created_at'] ?? 'N/A' }}</td>
                            <td>
                                <form
                                    method="POST"
                                    action="{{ route('staff.theme_builder.apply', ['theme' => $theme['id']]) }}"
                                    style="display:inline-block"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button class="form__button form__button--text" type="submit">
                                        Apply
                                    </button>
                                </form>
                                <form
                                    method="POST"
                                    action="{{ route('staff.theme_builder.destroy', ['theme' => $theme['id']]) }}"
                                    style="display:inline-block"
                                    x-data="confirmation"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="form__button form__button--text"
                                        type="submit"
                                        x-on:click.prevent="confirmAction"
                                        data-b64-deletion-message="{{ base64_encode('Delete this theme?') }}"
                                    >
                                        {{ __('common.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No themes yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
