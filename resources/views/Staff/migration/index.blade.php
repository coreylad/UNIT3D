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
                        <h4 x-text="migrationHadErrors ? '⚠️ Migration finished with errors' : '✅ {{ __('migration.migration-completed') }}'"></h4>
                        <template x-for="(result, table) in completionSummary" :key="table">
                            <div style="margin-bottom:1rem;">
                                <div x-html="result.success
                                    ? `<strong style='color:hsl(140,55%,60%)'>✅ ${table}:</strong> ${(result.count ?? 0).toLocaleString()} records migrated`
                                    : `<strong style='color:hsl(4,70%,62%)'>❌ ${table}:</strong> ${result.error ?? 'Unknown error'}`">
                                </div>
                                <template x-if="result.logs && result.logs.length">
                                    <details style="margin-top:0.4rem;">
                                        <summary style="cursor:pointer;font-size:0.78rem;opacity:0.6;">Show logs (<span x-text="result.logs.length"></span> entries)</summary>
                                        <div style="max-height:200px;overflow-y:auto;font-family:monospace;font-size:0.72rem;line-height:1.5;padding:0.5rem;background:rgba(0,0,0,0.3);border-radius:3px;margin-top:0.3rem;word-break:break-all;">
                                            <template x-for="(entry, i) in result.logs" :key="i">
                                                <div x-text="entry" :style="entry.includes('failed') || entry.includes('Error') ? 'color:hsl(4,70%,65%)' : 'color:hsl(210,15%,70%)'"></div>
                                            </template>
                                        </div>
                                    </details>
                                </template>
                            </div>
                        </template>
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
                migrationHadErrors: false,
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
                        // Try to pull the real exception message from Laravel's debug HTML page
                        let detail = '';
                        const titleMatch = text.match(/<title[^>]*>([^<]+)<\/title>/i);
                        const h1Match   = text.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i);
                        const msgMatch  = text.match(/class="[^"]*exception[^"]*message[^"]*"[^>]*>([\s\S]*?)<\/\w+>/i)
                                       || text.match(/class="[^"]*message[^"]*"[^>]*>([\s\S]*?)<\/\w+>/i);
                        if (msgMatch) {
                            detail = msgMatch[1].replace(/<[^>]+>/g, '').trim();
                        } else if (h1Match) {
                            detail = h1Match[1].replace(/<[^>]+>/g, '').trim();
                        } else if (titleMatch) {
                            detail = titleMatch[1].trim();
                        } else {
                            // Last resort: strip all tags and take first 400 chars
                            detail = text.replace(/<[^>]+>/g, ' ').replace(/\s{2,}/g, ' ').trim().slice(0, 400);
                        }
                        throw new Error(`HTTP ${response.status} — ${detail}`);
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
                            const driverBadge = data.driver
                                ? ` <span style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;background:hsl(38deg 92% 56% / 18%);color:hsl(38deg 92% 62%);border:1px solid hsl(38deg 92% 56% / 35%);border-radius:2px;padding:0.1rem 0.45rem;">${data.driver.toUpperCase()}</span>`
                                : '';
                            this.connectionMessage = '✅ ' + data.message.replace(/ \(driver:.*?\)/, '') + driverBadge;
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

                    this.showSummary  = false;
                    this.showProgress = true;
                    this.showCompleted = false;
                    this.migrationHadErrors = false;
                    this.completionSummary  = {};
                    this.migrationLogs = '';
                    this.progress = 0;

                    const tables = [...this.selectedTables];
                    const total  = tables.length;

                    // ── Migrate one table per HTTP request ───────────────────────────
                    // This keeps every individual request short so Nginx never times out.
                    for (let i = 0; i < tables.length; i++) {
                        const table = tables[i];
                        this.progress = Math.round((i / total) * 100);
                        this._appendLog(`⏳ Migrating <strong>${table}</strong>…`, 'hsl(38,92%,60%)');

                        try {
                            const data = await this._fetchJson(
                                '{{ route('staff.migrations.start') }}',
                                { ...this.form, tables: [table] }
                            );

                            const result = data.data?.[table] ?? (data.success === false
                                ? { success: false, error: data.message ?? 'Server error', logs: data.logs }
                                : { success: false, error: 'No result returned' });

                            this.completionSummary[table] = result;

                            if (result.success) {
                                this._appendLog(`✅ <strong>${table}</strong>: ${(result.count ?? 0).toLocaleString()} records migrated`, 'hsl(140,55%,60%)');
                            } else {
                                this.migrationHadErrors = true;
                                this._appendLog(`❌ <strong>${table}</strong>: ${result.error ?? 'Unknown error'}`, 'hsl(4,70%,62%)');
                            }

                            // Show last few service log lines for this table
                            if (result.logs?.length) {
                                result.logs.slice(-8).forEach(entry => {
                                    const colour = (entry.includes('failed') || entry.includes('Error'))
                                        ? 'hsl(4,65%,60%)' : 'hsl(210,12%,55%)';
                                    this._appendLog(`&nbsp;&nbsp;&nbsp;${entry}`, colour, '0.72rem');
                                });
                            }

                        } catch (error) {
                            this.migrationHadErrors = true;
                            this.completionSummary[table] = { success: false, error: error.message };
                            this._appendLog(`❌ <strong>${table}</strong>: ${error.message}`, 'hsl(4,70%,62%)');
                        }
                    }

                    this.progress = 100;
                    this.showProgress = false;
                    this.showCompleted = true;
                },

                _appendLog(html, colour = 'hsl(210,15%,65%)', fontSize = '0.82rem') {
                    this.migrationLogs += `<div style="color:${colour};font-size:${fontSize};line-height:1.6;">${html}</div>`;
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
                    this.migrationHadErrors = false;
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
