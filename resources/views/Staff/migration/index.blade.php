@extends('layout.with-main')

@section('title')
    <title>
        {{ __('migration.database-migration') }} - {{ __('staff.staff-dashboard') }}
        - {{ config('other.title') }}
    </title>
@endsection

@section('meta')
    <meta
        name="description"
        content="{{ __('migration.database-migration') }} - {{ __('staff.staff-dashboard') }}"
    />
@endsection

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumb--active">{{ __('migration.database-migration') }}</li>
@endsection

@section('page', 'page__staff-migration-manager--index')



@section('main')
    <div class="migration-panel" style="display: flex; flex-direction: column; gap: 1rem" x-data="migrationPanel()">
        <section class="panelV2">
            <header class="panel__header">
                <h2 class="panel__heading">{{ __('migration.database-migration') }}</h2>
            </header>

            @if(session('migration-warning'))
                <div class="alert alert-warning" style="margin: 0.75rem 1.25rem;">
                    <strong>⚠️ {{ __('common.warning') }}:</strong> {{ session('migration-warning') }}
                </div>
            @endif

            <div class="panel__body" style="display: flex; flex-direction: column; gap: 2rem;">
                {{-- Configuration Section --}}
                <section>
                    <h3 style="margin-bottom: 1rem;">{{ __('migration.configuration') }}</h3>

                    <form @submit.prevent id="migration-config-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label for="host" class="form__label">{{ __('migration.source-host') }}</label>
                            <input type="text" id="host" x-model="form.host" class="form__input" placeholder="localhost" required />
                        </div>
                        <div>
                            <label for="port" class="form__label">{{ __('migration.source-port') }}</label>
                            <input type="number" id="port" x-model.number="form.port" class="form__input" placeholder="3306" required />
                        </div>
                        <div>
                            <label for="username" class="form__label">{{ __('migration.source-username') }}</label>
                            <input type="text" id="username" x-model="form.username" class="form__input" required />
                        </div>
                        <div>
                            <label for="password" class="form__label">{{ __('migration.source-password') }}</label>
                            <input type="password" id="password" x-model="form.password" class="form__input" required />
                        </div>
                        <div class="form__group -col-span-2">
                            <label for="database" class="form__label">{{ __('migration.source-database-name') }}</label>
                            <input type="text" id="database" x-model="form.database" class="form__input" placeholder="admin_TSSE8" required />
                        </div>
                    </form>

                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                        <button class="form__button form__button--primary" :disabled="testingConnection" @click="testConnection()">
                            <i class="{{ config('other.font-awesome') }} fa-plug" style="margin-right: 0.4em;"></i>
                            {{ __('migration.test-connection') }}
                        </button>
                    </div>

                    <div
                        x-show="connectionStatus"
                        x-cloak
                        class="migration-panel__status"
                        :class="connectionSuccess ? 'migration-panel__status--success' : 'migration-panel__status--error'"
                    >
                        <p x-html="connectionMessage" style="margin: 0;"></p>
                    </div>
                </section>

                {{-- Migration Summary Section --}}
                <section x-show="showSummary" x-cloak>
                    <h3 style="margin-bottom: 1rem;">{{ __('migration.migration-status') }}</h3>

                    <div class="data-table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('migration.migration-table') }}</th>
                                    <th scope="col">{{ __('migration.migration-rows') }}</th>
                                    <th scope="col">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(rowCount, tableName) in summaryData" :key="tableName">
                                    <tr>
                                        <td x-text="getTableLabel(tableName)"></td>
                                        <td x-text="rowCount.toLocaleString()"></td>
                                        <td>
                                            <input type="checkbox" :id="'table-' + tableName" :value="tableName" @change="handleTableToggle(tableName, $el.checked)" style="cursor: pointer;" />
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button class="form__button form__button--primary" @click="startMigration()" x-show="selectedTables.length > 0">
                            {{ __('migration.start-migration') }}
                        </button>
                        <button class="form__button form__button--secondary" @click="clearSelection()" x-show="selectedTables.length > 0">
                            {{ __('common.clear') }}
                        </button>
                    </div>
                </section>

                {{-- Progress Section --}}
                <section x-show="showProgress" x-cloak>
                    <h3 style="margin-bottom: 1rem;">{{ __('migration.migration-progress') }}</h3>

                    <div class="migration-panel__progress-track">
                        <div
                            class="migration-panel__progress-fill"
                            :style="{ width: progress + '%' }"
                            x-text="progress + '%'"
                        ></div>
                    </div>

                    <div class="migration-panel__log" x-html="migrationLogs"></div>
                </section>

                {{-- Completed Section --}}
                <section x-show="showCompleted" x-cloak>
                    <div class="migration-panel__complete-box">
                        <h4>✅ {{ __('migration.migration-completed') }}</h4>
                        <ul>
                            <template x-for="(result, table) in completionSummary" :key="table">
                                <li x-html="result.success ? `✅ ${table}: ${result.count} records migrated` : `❌ ${table}: ${result.error}`"></li>
                            </template>
                        </ul>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button class="form__button form__button--primary" @click="resetMigration()">
                            {{ __('common.back') }}
                        </button>
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection

@section('javascripts')
    <script nonce="{{ HDVinnie\SecureHeaders\SecureHeaders::nonce('script') }}">
        function migrationPanel() {
            return {
                form: {
                    host: 'localhost',
                    port: 3306,
                    username: '',
                    password: '',
                    database: '',
                },
                testingConnection: false,
                connectionStatus: false,
                connectionSuccess: false,
                connectionMessage: '',
                showSummary: false,
                showProgress: false,
                showCompleted: false,
                summaryData: {},
                selectedTables: [],
                progress: 0,
                migrationLogs: '',
                completionSummary: {},

                getTableLabel(tableName) {
                    const labels = {
                        users: '{{ __('migration.users') }}',
                        torrents: '{{ __('migration.torrents') }}',
                        peers: '{{ __('migration.peers') }}',
                        snatched: '{{ __('migration.snatched') }}'
                    };
                    return labels[tableName] || tableName;
                },

                async _fetchJson(url, body) {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });
                    const ct = response.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) {
                        const text = await response.text();
                        // Strip HTML tags for a readable plain-text snippet
                        const snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s{2,}/g, ' ').trim().slice(0, 300);
                        throw new Error(`Server returned HTTP ${response.status} (non-JSON). ${snippet}`);
                    }
                    return response.json();
                },

                async testConnection() {
                    this.testingConnection = true;
                    this.connectionStatus = true;
                    this.connectionMessage = '⏳ {{ __('common.loading') }}...';

                    try {
                        const data = await this._fetchJson(
                            '{{ route('staff.migrations.test-connection') }}',
                            this.form
                        );
                        this.testingConnection = false;

                        if (data.success) {
                            this.connectionSuccess = true;
                            this.connectionMessage = '✅ ' + data.message;
                            await this.getSummary();
                        } else {
                            this.connectionSuccess = false;
                            const parts = data.message.split('\n\n');
                            let html = '<strong>' + parts[0].replace(/\n/g, '<br>') + '</strong>';
                            if (parts.length > 1) {
                                html += '<br><span style="display:block;margin-top:0.5rem;font-size:0.78rem;opacity:0.6;font-family:monospace;word-break:break-all;">' + parts.slice(1).join(' ') + '</span>';
                            }
                            this.connectionMessage = '❌ ' + html;
                        }
                    } catch (error) {
                        this.testingConnection = false;
                        this.connectionSuccess = false;
                        this.connectionMessage = '❌ ' + error.message;
                    }
                },

                async getSummary() {
                    try {
                        const data = await this._fetchJson(
                            '{{ route('staff.migrations.get-summary') }}',
                            this.form
                        );
                        if (data.success) {
                            this.summaryData = data.data;
                            this.showSummary = true;
                        } else {
                            this.connectionSuccess = false;
                            this.connectionMessage = '❌ {{ __('common.error') }}: ' + data.message;
                        }
                    } catch (error) {
                        this.connectionSuccess = false;
                        this.connectionMessage = '❌ {{ __('common.error') }}: ' + error.message;
                    }
                },

                handleTableToggle(tableName, checked) {
                    if (checked) {
                        if (!this.selectedTables.includes(tableName)) {
                            this.selectedTables.push(tableName);
                        }
                    } else {
                        this.selectedTables = this.selectedTables.filter(t => t !== tableName);
                    }
                },

                clearSelection() {
                    this.selectedTables = [];
                    document.querySelectorAll('input[type="checkbox"][id^="table-"]').forEach(cb => {
                        cb.checked = false;
                    });
                },

                async startMigration() {
                    if (!confirm('{{ __('migration.confirm-migration') }}')) {
                        return;
                    }

                    this.showSummary = false;
                    this.showProgress = true;
                    this.migrationLogs = '<p style="color:hsl(38,92%,60%);">⏳ {{ __('common.loading') }}...</p>';

                    const formData = {
                        ...this.form,
                        tables: this.selectedTables
                    };

                    try {
                        const data = await this._fetchJson(
                            '{{ route('staff.migrations.start') }}',
                            formData
                        );

                        if (data.success) {
                            this.completionSummary = data.data;
                            this.progress = 100;
                            this.migrationLogs = '';
                            this.showProgress = false;
                            this.showCompleted = true;
                        } else {
                            this.migrationLogs = `<div style="color:hsl(4deg,70%,62%);">❌ Error: ${data.message}</div>`;
                        }
                    } catch (error) {
                        this.migrationLogs = `<div style="color:hsl(4deg,70%,62%);">❌ Error: ${error.message}</div>`;
                    }
                },

                resetMigration() {
                    this.form = {
                        host: 'localhost',
                        port: 3306,
                        username: '',
                        password: '',
                        database: '',
                    };
                    this.connectionStatus = false;
                    this.showSummary = false;
                    this.showProgress = false;
                    this.showCompleted = false;
                    this.summaryData = {};
                    this.selectedTables = [];
                    this.progress = 0;
                    this.migrationLogs = '';
                    this.completionSummary = {};
                    document.getElementById('migration-config-form').reset();
                }
            };
        }
    </script>
@endsection
