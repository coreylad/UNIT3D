@extends('layout.with-main-and-sidebar')

@section('title')
    <title>{{ $user->username }} Casino - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('users.show', ['user' => $user]) }}" class="breadcrumb__link">
            {{ $user->username }}
        </a>
    </li>
    <li class="breadcrumbV2">
        <a href="{{ route('users.earnings.index', ['user' => $user]) }}" class="breadcrumb__link">
            {{ __('bon.bonus') }} {{ __('bon.points') }}
        </a>
    </li>
    <li class="breadcrumb--active">Casino</li>
@endsection

@section('nav-tabs')
    @include('user.buttons.user')
@endsection

@section('page', 'page__user-casino--index')

@section('main')
    <section class="panelV2">
        <header class="panel__header">
            <h2 class="panel__heading">Casino history</h2>
            <div class="panel__actions">
                @if (auth()->user()->is($user))
                    <a class="panel__action form__button form__button--text" href="{{ route('casino.index') }}">
                        Open a wager
                    </a>
                @endif
            </div>
        </header>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Created by</th>
                        <th>Accepted by</th>
                        <th>Stake</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($casinoWagers as $wager)
                        <tr>
                            <td><x-user-tag :user="$wager->creator" :anon="false" /></td>
                            <td>
                                @if ($wager->challenger)
                                    <x-user-tag :user="$wager->challenger" :anon="false" />
                                @else
                                    Waiting
                                @endif
                            </td>
                            <td>{{ \App\Helpers\StringHelper::formatBytes($wager->amount, 2) }}</td>
                            <td>{{ ucfirst($wager->status) }}</td>
                            <td>
                                @if ($wager->winner_id === $user->id)
                                    Won
                                @elseif ($wager->loser_id === $user->id)
                                    Lost
                                @else
                                    Pending
                                @endif
                            </td>
                            <td>
                                <time datetime="{{ $wager->updated_at }}" title="{{ $wager->updated_at }}">
                                    {{ $wager->updated_at->format('Y-m-d H:i') }}
                                </time>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No casino wagers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $casinoWagers->links('partials.pagination') }}
    </section>
@endsection

@section('sidebar')
    <section class="panelV2">
        <h2 class="panel__heading">Summary</h2>
        <dl class="key-value">
            <div class="key-value__group">
                <dt>Won</dt>
                <dd>{{ \App\Helpers\StringHelper::formatBytes($stats['totalWon'], 2) }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Lost</dt>
                <dd>{{ \App\Helpers\StringHelper::formatBytes($stats['totalLost'], 2) }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Net</dt>
                <dd>{{ \App\Helpers\StringHelper::formatBytes(abs($stats['net']), 2) }} {{ $stats['net'] < 0 ? 'down' : 'up' }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Completed</dt>
                <dd>{{ $stats['completed'] }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Open wagers</dt>
                <dd>{{ $stats['openCount'] }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Open escrow</dt>
                <dd>{{ \App\Helpers\StringHelper::formatBytes($stats['openExposure'], 2) }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Win rate</dt>
                <dd>{{ $stats['winRate'] }}%</dd>
            </div>
        </dl>
    </section>
@endsection