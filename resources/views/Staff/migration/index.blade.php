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

            @if (session('migration-warning'))
                <div class="alert alert-warning" style="margin: 0.75rem 1.25rem;">
                    <strong>{{ __('common.warning') }}:</strong> {{ session('migration-warning') }}
                </div>
            @endif

            <div class="panel__body" style="display: flex; flex-direction: column; gap: 2rem;">
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

                <section x-show="showGroupMapping" x-cloak>
                    <h3 style="margin-bottom: 0.4rem;">{{ __('migration.group-mapping') }}</h3>
                    <p style="font-size: 0.82rem; opacity: 0.6; margin-bottom: 1rem;">{{ __('migration.group-mapping-hint') }}</p>

                    <template x-if="groupMappingError">
                        <div class="migration-panel__status migration-panel__status--error" style="margin-bottom: 1rem;">
                            <p x-text="groupMappingError" style="margin: 0;"></p>
                        </div>
                    </template>

                    <template x-if="loadingGroups">
                        <p style="font-size: 0.85rem; opacity: 0.6;">{{ __('common.loading') }}...</p>
                    </template>

                    <template x-if="!loadingGroups && sourceGroups.length > 0">
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('migration.source-group') }}</th>
                                        <th scope="col">-></th>
                                        <th scope="col">{{ __('migration.unit3d-group') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="sg in sourceGroups" :key="sg.gid ?? sg.id ?? sg.gid">
                                        <tr>
                                            <td>
                                                <span x-text="sg.title ?? sg.name ?? sg.group_name ?? '?'"></span>
                                                <span style="font-size: 0.72rem; opacity: 0.45; margin-left: 0.4em;" x-text="'#' + (sg.gid ?? sg.id ?? '')"></span>
                                            </td>
                                            <td style="color: var(--color-accent, hsl(38,92%,60%)); font-weight: 700;">-></td>
                                            <td>
                                                <select
                                                    class="form__input"
                                                    style="padding: 0.25rem 0.5rem; font-size: 0.82rem;"
                                                    @change="groupMap[sg.gid ?? sg.id] = parseInt($el.value)"
                                                    :value="groupMap[sg.gid ?? sg.id] ?? ''"
                                                >
                                                    <option value="">-- {{ __('migration.group-unassigned') }} --</option>
                                                    <template x-for="ug in unit3dGroups" :key="ug.id">
                                                        <option
                                                            :value="ug.id"
                                                            :selected="(groupMap[sg.gid ?? sg.id] ?? null) === ug.id"
                                                            x-text="ug.name"
                                                        ></option>
                                                    </template>
                                                </select>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </section>

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

                <section x-show="showProgress || showCompleted" x-cloak>
                    <h3 style="margin-bottom: 1rem;">{{ __('migration.migration-progress') }}</h3>

                    <div class="migration-panel__progress-track">
                        <div
                            class="migration-panel__progress-fill"
                            :style="{ width: progress + '%' }"
                            x-text="progress + '%'"
                        ></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; margin: -0.5rem 0 0.9rem;">
                        <div style="font-size: 0.82rem; opacity: 0.72;">
                            <strong x-text="currentTableLabel || 'Waiting to start'"></strong>
                            <span x-show="currentPage > 0">
                                | page <span x-text="currentPage"></span>
                                | offset <span x-text="currentOffset.toLocaleString()"></span>
                            </span>
                        </div>
                        <div style="font-size: 0.78rem; opacity: 0.58;" x-text="progressStatus"></div>
                    </div>

                    <div style="font-size: 0.78rem; opacity: 0.6; margin-bottom: 0.6rem;">
                        Verbose live log
                    </div>
                    <div class="migration-panel__log" x-html="migrationLogs"></div>
                </section>

                <section x-show="showCompleted" x-cloak>
                    <div class="migration-panel__complete-box">
                        <h4 x-text="migrationHadErrors ? 'Migration finished with errors' : '{{ __('migration.migration-completed') }}'"></h4>
                        <template x-for="(result, table) in completionSummary" :key="table">
                            <div style="margin-bottom: 1rem;">
                                <div x-html="result.success
                                    ? `<strong style='color:hsl(140,55%,60%)'>${table}:</strong> ${(result.count ?? 0).toLocaleString()} records migrated`
                                    : `<strong style='color:hsl(4,70%,62%)'>${table}:</strong> ${result.error || (result.logs && result.logs.length ? result.logs.slice(-1)[0] : 'Unknown error')}`">
                                </div>
                                <template x-if="result.logs && result.logs.length">
                                    <details style="margin-top: 0.4rem;">
                                        <summary style="cursor: pointer; font-size: 0.78rem; opacity: 0.6;">Show logs (<span x-text="result.logs.length"></span> entries)</summary>
                                        <div style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.72rem; line-height: 1.5; padding: 0.5rem; background: rgba(0,0,0,0.3); border-radius: 3px; margin-top: 0.3rem; word-break: break-all;">
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
                showGroupMapping: false,
                loadingGroups: false,
                groupMappingError: null,
                sourceGroups: [],
                unit3dGroups: [],
                groupMap: {},
                showSummary: false,
                showProgress: false,
                showCompleted: false,
                migrationHadErrors: false,
                summaryData: {},
                selectedTables: [],
                progress: 0,
                migrationLogs: '',
                completionSummary: {},
                currentTableLabel: '',
                currentPage: 0,
                currentOffset: 0,
                progressStatus: 'Idle',

                getTableLabel(tableName) {
                    const labels = {
                        users: '{{ __('migration.users') }}',
                        torrents: '{{ __('migration.torrents') }}',
                        peers: '{{ __('migration.peers') }}',
                        snatched: '{{ __('migration.snatched') }}',
                        comments: 'Comments',
                        forums: 'Forums',
                        forum_threads: 'Forum Threads',
                        forum_posts: 'Forum Posts',
                    };
                    return labels[tableName] || tableName;
                },

                async _fetchJson(url, body) {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });
                    const ct = response.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) {
                        const text = await response.text();
                        let detail = '';
                        const titleMatch = text.match(/<title[^>]*>([^<]+)<\/title>/i);
                        const h1Match = text.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i);
                        const msgMatch = text.match(/class="[^"]*exception[^"]*message[^"]*"[^>]*>([\s\S]*?)<\/\w+>/i)
                            || text.match(/class="[^"]*message[^"]*"[^>]*>([\s\S]*?)<\/\w+>/i);
                        if (msgMatch) {
                            detail = msgMatch[1].replace(/<[^>]+>/g, '').trim();
                        } else if (h1Match) {
                            detail = h1Match[1].replace(/<[^>]+>/g, '').trim();
                        } else if (titleMatch) {
                            detail = titleMatch[1].trim();
                        } else {
                            detail = text.replace(/<[^>]+>/g, ' ').replace(/\s{2,}/g, ' ').trim().slice(0, 400);
                        }
                        throw new Error(`HTTP ${response.status} - ${detail}`);
                    }
                    return response.json();
                },

                async testConnection() {
                    this.testingConnection = true;
                    this.connectionStatus = true;
                    this.connectionMessage = '{{ __('common.loading') }}...';

                    try {
                        const data = await this._fetchJson('{{ route('staff.migrations.test-connection') }}', this.form);
                        this.testingConnection = false;

                        if (data.success) {
                            this.connectionSuccess = true;
                            const driverBadge = data.driver
                                ? ` <span style="font-size:0.72rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;background:hsl(38deg 92% 56% / 18%);color:hsl(38deg 92% 62%);border:1px solid hsl(38deg 92% 56% / 35%);border-radius:2px;padding:0.1rem 0.45rem;">${data.driver.toUpperCase()}</span>`
                                : '';
                            this.connectionMessage = data.message.replace(/ \(driver:.*?\)/, '') + driverBadge;
                            await this.getSummary();
                        } else {
                            this.connectionSuccess = false;
                            const parts = data.message.split('\n\n');
                            let html = '<strong>' + parts[0].replace(/\n/g, '<br>') + '</strong>';
                            if (parts.length > 1) {
                                html += '<br><span style="display:block;margin-top:0.5rem;font-size:0.78rem;opacity:0.6;font-family:monospace;word-break:break-all;">' + parts.slice(1).join(' ') + '</span>';
                            }
                            this.connectionMessage = html;
                        }
                    } catch (error) {
                        this.testingConnection = false;
                        this.connectionSuccess = false;
                        this.connectionMessage = error.message;
                    }
                },

                async getSummary() {
                    try {
                        const data = await this._fetchJson('{{ route('staff.migrations.get-summary') }}', this.form);
                        if (data.success) {
                            this.summaryData = data.data;
                            this.showSummary = true;
                            await this.loadGroups();
                        } else {
                            this.connectionSuccess = false;
                            this.connectionMessage = '{{ __('common.error') }}: ' + data.message;
                        }
                    } catch (error) {
                        this.connectionSuccess = false;
                        this.connectionMessage = '{{ __('common.error') }}: ' + error.message;
                    }
                },

                async loadGroups() {
                    this.loadingGroups = true;
                    this.showGroupMapping = true;
                    this.groupMappingError = null;

                    try {
                        const data = await this._fetchJson('{{ route('staff.migrations.get-groups') }}', this.form);

                        if (!data.success) {
                            this.groupMappingError = data.message ?? '{{ __('common.error') }}';
                            return;
                        }

                        this.unit3dGroups = data.unit3dGroups ?? [];
                        this.sourceGroups = (data.sourceGroups ?? []).map((sg) => ({
                            ...sg,
                            gid: sg.gid ?? sg.id ?? 0,
                        }));

                        const suggestions = data.suggestions ?? {};
                        this.groupMap = {};
                        this.sourceGroups.forEach((sg) => {
                            const sid = sg.gid ?? sg.id;
                            if (suggestions[sid] !== undefined) {
                                this.groupMap[sid] = suggestions[sid];
                            }
                        });
                    } catch (error) {
                        this.groupMappingError = error.message;
                    } finally {
                        this.loadingGroups = false;
                    }
                },

                handleTableToggle(tableName, checked) {
                    if (checked) {
                        if (!this.selectedTables.includes(tableName)) {
                            this.selectedTables.push(tableName);
                        }
                    } else {
                        this.selectedTables = this.selectedTables.filter((t) => t !== tableName);
                    }
                },

                clearSelection() {
                    this.selectedTables = [];
                    document.querySelectorAll('input[type="checkbox"][id^="table-"]').forEach((cb) => {
                        cb.checked = false;
                    });
                },

                async startMigration() {
                    if (!confirm('{{ __('migration.confirm-migration') }}')) {
                        return;
                    }

                    this.showSummary = false;
                    this.showGroupMapping = false;
                    this.showProgress = true;
                    this.showCompleted = false;
                    this.migrationHadErrors = false;
                    this.completionSummary = {};
                    this.migrationLogs = '';
                    this.progress = 0;
                    this.currentTableLabel = '';
                    this.currentPage = 0;
                    this.currentOffset = 0;
                    this.progressStatus = 'Preparing migration';

                    const tables = [...this.selectedTables];
                    const total = tables.length;
                    const pageSize = 100;

                    this._appendLog(`Starting migration run for ${tables.length} table(s): ${tables.join(', ')}`, 'hsl(38,92%,60%)');
                    this._appendLog(`Source: ${this.form.host}:${this.form.port}/${this.form.database}`, 'hsl(210,12%,55%)', '0.76rem');
                    this._appendLog(`Page size: ${pageSize} rows per request`, 'hsl(210,12%,55%)', '0.76rem');

                    for (let i = 0; i < tables.length; i++) {
                        const table = tables[i];
                        const tableLabel = this.getTableLabel(table);
                        this.progress = Math.round((i / total) * 100);
                        this.currentTableLabel = tableLabel;
                        this.currentPage = 0;
                        this.currentOffset = 0;
                        this.progressStatus = `Starting ${tableLabel}`;
                        this._appendLog(`Migrating ${tableLabel}...`, 'hsl(38,92%,60%)');

                        let offset = 0;
                        let tableTotal = 0;
                        let tableDone = false;
                        let tableSucceeded = true;
                        let tableError = null;
                        const tableLogs = [];
                        let requestCount = 0;

                        while (!tableDone) {
                            try {
                                requestCount += 1;
                                this.currentPage = requestCount;
                                this.currentOffset = offset;
                                this.progressStatus = `${tableLabel}: requesting rows ${offset.toLocaleString()}-${(offset + pageSize - 1).toLocaleString()}`;
                                this._appendLog(`[${table}] Request ${requestCount}: offset=${offset}, limit=${pageSize}`, 'hsl(210,12%,55%)', '0.74rem');

                                const data = await this._fetchJson(
                                    '{{ route('staff.migrations.start') }}',
                                    { ...this.form, tables: [table], offset, page_size: pageSize, group_map: this.groupMap }
                                );

                                const result = data.data?.[table] ?? (data.success === false
                                    ? { success: false, error: data.message ?? 'Server error', logs: data.logs, done: true }
                                    : { success: false, error: 'No result returned', done: true });

                                tableTotal += result.count ?? 0;
                                tableDone = result.done ?? true;
                                offset += pageSize;

                                if (result.logs?.length) {
                                    tableLogs.push(...result.logs);
                                    result.logs.forEach((entry) => {
                                        const colour = entry.includes('failed') || entry.includes('Error')
                                            ? 'hsl(4,65%,60%)'
                                            : 'hsl(210,12%,55%)';
                                        this._appendLog(entry, colour, '0.72rem');
                                    });
                                }

                                if (!result.success) {
                                    tableSucceeded = false;
                                    tableError = result.error || (result.logs?.length ? result.logs.slice(-1)[0] : 'Unknown error');
                                    this.migrationHadErrors = true;
                                    this.progressStatus = `${tableLabel}: failed`;
                                    this._appendLog(`Failed ${tableLabel}: ${tableError}`, 'hsl(4,70%,62%)');
                                    tableDone = true;
                                } else if (!tableDone) {
                                    this.progressStatus = `${tableLabel}: ${tableTotal.toLocaleString()} migrated so far`;
                                    this._appendLog(`${tableLabel}: ${tableTotal.toLocaleString()} migrated so far...`, 'hsl(210,12%,55%)', '0.72rem');
                                } else {
                                    this.progressStatus = `${tableLabel}: completed`;
                                }
                            } catch (error) {
                                tableSucceeded = false;
                                this.migrationHadErrors = true;
                                tableError = error.message;
                                this.progressStatus = `${tableLabel}: request failed`;
                                this._appendLog(`Request failed for ${tableLabel}: ${error.message}`, 'hsl(4,70%,62%)');
                                tableDone = true;
                            }
                        }

                        this.completionSummary[table] = {
                            success: tableSucceeded,
                            count: tableTotal,
                            logs: tableLogs,
                            error: tableError,
                        };

                        if (tableSucceeded) {
                            this._appendLog(`Completed ${tableLabel}: ${tableTotal.toLocaleString()} records migrated`, 'hsl(140,55%,60%)');
                        }
                    }

                    this.progress = 100;
                    this.progressStatus = this.migrationHadErrors ? 'Completed with errors' : 'Completed successfully';
                    this.showCompleted = true;
                },

                _appendLog(html, colour = 'hsl(210,15%,65%)', fontSize = '0.82rem') {
                    this.migrationLogs += `<div style="color:${colour};font-size:${fontSize};line-height:1.6;">${html}</div>`;
                    this.$nextTick(() => {
                        const log = document.querySelector('.migration-panel__log');
                        if (log) {
                            log.scrollTop = log.scrollHeight;
                        }
                    });
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
                    this.showGroupMapping = false;
                    this.loadingGroups = false;
                    this.groupMappingError = null;
                    this.sourceGroups = [];
                    this.unit3dGroups = [];
                    this.groupMap = {};
                    this.showSummary = false;
                    this.showProgress = false;
                    this.showCompleted = false;
                    this.migrationHadErrors = false;
                    this.summaryData = {};
                    this.selectedTables = [];
                    this.progress = 0;
                    this.migrationLogs = '';
                    this.completionSummary = {};
                    this.currentTableLabel = '';
                    this.currentPage = 0;
                    this.currentOffset = 0;
                    this.progressStatus = 'Idle';
                    document.getElementById('migration-config-form').reset();
                },
            };
        }
    </script>
@endsection
