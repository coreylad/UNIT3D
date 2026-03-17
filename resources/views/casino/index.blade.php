@extends('layout.with-main-and-sidebar')

@section('title')
    <title>Casino - {{ config('other.title') }}</title>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb--active">Casino</li>
@endsection

@section('page', 'page__casino--index')

@section('main')
    <section class="panelV2">
        <header class="panel__header">
            <h2 class="panel__heading">Peer-to-peer upload wagers</h2>
            <div class="panel__actions">
                <a class="panel__action form__button form__button--text" href="{{ route('users.casino.index', ['user' => $user]) }}">
                    My casino history
                </a>
            </div>
        </header>
        <div class="panel__body">
            <form class="form" method="POST" action="{{ route('casino.store') }}">
                @csrf
                <p class="form__group">
                    <label class="form__label" for="amount">Stake</label>
                    <select id="amount" name="amount" class="form__select" required>
                        <option value="">Select stake</option>
                        @foreach ($allowedAmounts as $amount)
                            <option value="{{ $amount }}">{{ \App\Helpers\StringHelper::formatBytes($amount, 2) }}</option>
                        @endforeach
                    </select>
                </p>
                <p class="form__group">
                    <textarea id="message" class="form__textarea" name="message" placeholder=" "></textarea>
                    <label class="form__label form__label--floating" for="message">Optional note</label>
                </p>
                <p class="form__group">
                    <button class="form__button form__button--filled">Open wager</button>
                </p>
            </form>
        </div>
    </section>

    <section class="panelV2">
        <header class="panel__header">
            <h2 class="panel__heading">Open wagers</h2>
        </header>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Stake</th>
                        <th>Note</th>
                        <th>Opened</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($openWagers as $wager)
                        <tr>
                            <td><x-user-tag :user="$wager->creator" :anon="false" /></td>
                            <td>{{ \App\Helpers\StringHelper::formatBytes($wager->amount, 2) }}</td>
                            <td>{{ $wager->message ?: 'No note' }}</td>
                            <td>
                                <time datetime="{{ $wager->created_at }}" title="{{ $wager->created_at }}">
                                    {{ $wager->created_at->diffForHumans() }}
                                </time>
                            </td>
                            <td>
                                @if (auth()->id() === $wager->creator_id)
                                    <form method="POST" action="{{ route('casino.cancel', ['casinoWager' => $wager]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="form__button form__button--outlined">Cancel</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('casino.accept', ['casinoWager' => $wager]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="form__button form__button--filled">Accept</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No open wagers right now.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $openWagers->links('partials.pagination') }}
    </section>

    <section class="panelV2">
        <header class="panel__header">
            <h2 class="panel__heading">Recent settlements</h2>
        </header>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Winner</th>
                        <th>Loser</th>
                        <th>Stake</th>
                        <th>Payout</th>
                        <th>Settled</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentWagers as $wager)
                        <tr>
                            <td><x-user-tag :user="$wager->winner" :anon="false" /></td>
                            <td><x-user-tag :user="$wager->loser" :anon="false" /></td>
                            <td>{{ \App\Helpers\StringHelper::formatBytes($wager->amount, 2) }}</td>
                            <td>{{ \App\Helpers\StringHelper::formatBytes($wager->amount * 2, 2) }}</td>
                            <td>
                                <time datetime="{{ $wager->settled_at }}" title="{{ $wager->settled_at }}">
                                    {{ $wager->settled_at?->diffForHumans() }}
                                </time>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No settled wagers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@section('sidebar')
    <section class="panelV2">
        <h2 class="panel__heading">Your casino stats</h2>
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
                <dt>Open escrow</dt>
                <dd>{{ \App\Helpers\StringHelper::formatBytes($stats['openExposure'], 2) }}</dd>
            </div>
            <div class="key-value__group">
                <dt>Win rate</dt>
                <dd>{{ $stats['winRate'] }}%</dd>
            </div>
        </dl>
    </section>
    <section class="panelV2">
        <h2 class="panel__heading">Rules</h2>
        <div class="panel__body">
            <p>Opening a wager locks your upload credit until another user accepts it or you cancel it.</p>
            <p>When a wager is accepted, both stakes are escrowed and the winner receives the full payout.</p>
            <p>Minimum ratio required: {{ number_format(config('casino.minimum_ratio'), 2) }}</p>
        </div>
    </section>
@endsection