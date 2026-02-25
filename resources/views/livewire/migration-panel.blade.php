<div style="display: flex; flex-direction: column; gap: 1rem" x-data="migrationPanel()">
    <section class="panelV2">
        <header class="panel__header">
            <h2 class="panel__heading">{{ __('migration.database-migration') }}</h2>
        </header>

        @if(session('migration-warning'))
            <div class="alert alert-warning" style="margin: 1rem;">
                <strong>⚠️ {{ __('common.warning') }}:</strong> {{ session('migration-warning') }}
            </div>
        @endif

        <div class="panel__body" style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Configuration Section -->
            <section>
                <h3 style="margin-bottom: 1rem;">{{ __('migration.configuration') }}</h3>

                <form @submit.prevent id="migration-config-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label for="host" class="form__label">{{ __('migration.source-host') }}</label>
                        <input
                            type="text"
                            id="host"
                            x-model="form.host"
                            class="form__input"
                            placeholder="localhost"
                            required
                        />
                    </div>

                    <div>
                        <label for="port" class="form__label">{{ __('migration.source-port') }}</label>
                        <input
                            type="number"
                            id="port"
                            x-model.number="form.port"
                            class="form__input"
                            placeholder="3306"
                            value="3306"
                            required
                        />
                    </div>

                    <div>
                        <label for="username" class="form__label">{{ __('migration.source-username') }}</label>
                        <input
                            type="text"
                            id="username"
                            x-model="form.username"
                            class="form__input"
                            required
                        />
                    </div>

                    <div>
                        <label for="password" class="form__label">{{ __('migration.source-password') }}</label>
                        <input
                            type="password"
                            id="password"
                            x-model="form.password"
                            class="form__input"
                            required
                        />
                    </div>

                    <div class="form__group -col-span-2">
                        <label for="database" class="form__label">{{ __('migration.source-database-name') }}</label>
                        <input
                            type="text"
                            id="database"
                            x-model="form.database"
                            class="form__input"
                            placeholder="admin_TSSE8"
                            required
                        />
                    </div>
                </form>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <button
                        class="form__button form__button--primary"
                        :disabled="testingConnection"
                        @click="testConnection()"
                    >
                        {{ __('migration.test-connection') }}
                    </button>
                </div>

                <div x-show="connectionStatus" style="padding: 1rem; border-radius: 0.5rem;" :style="connectionStatus && {
                    backgroundColor: connectionSuccess ? '#d4edda' : '#f8d7da',
                    borderColor: connectionSuccess ? '#c3e6cb' : '#f5c6cb',
                    color: connectionSuccess ? '#155724' : '#721c24',
                }">
                    <p x-html="connectionMessage"></p>
                </div>
            </section>

            <!-- Migration Summary Section -->
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
                                        <input
                                            type="checkbox"
                                            :id="'table-' + tableName"
                                            :value="tableName"
                                            @change="handleTableToggle(tableName, $el.checked)"
                                            style="cursor: pointer;"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button
                        class="form__button form__button--primary"
                        @click="startMigration()"
                        x-show="selectedTables.length > 0"
                    >
                        {{ __('migration.start-migration') }}
                    </button>
                    <button
                        class="form__button form__button--secondary"
                        @click="clearSelection()"
                        x-show="selectedTables.length > 0"
                    >
                        {{ __('common.clear') }}
                    </button>
                </div>
            </section>

            <!-- Progress Section -->
            <section x-show="showProgress" x-cloak>
                <h3 style="margin-bottom: 1rem;">{{ __('migration.migration-progress') }}</h3>

                <div id="migration-progress" style="margin-bottom: 1.5rem;">
                    <div style="background: #f0f0f0; border-radius: 0.25rem; overflow: hidden;">
                        <div
                            style="height: 2rem; background: linear-gradient(90deg, #4CAF50, #45a049); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; transition: width 0.3s ease;"
                            :style="{ width: progress + '%' }"
                            x-text="progress + '%'"
                        >
                        </div>
                    </div>
                </div>

                <div style="background: #f5f5f5; border: 1px solid #ddd; border-radius: 0.5rem; padding: 1rem; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.875rem;" x-html="migrationLogs">
                </div>
            </section>

            <!-- Completed Section -->
            <section x-show="showCompleted" x-cloak>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 0.5rem; padding: 1rem; color: #155724;">
                    <h4>✅ {{ __('migration.migration-completed') }}</h4>
                    <ul style="margin: 0.5rem 0 0;">
                        <template x-for="(result, table) in completionSummary" :key="table">
                            <li x-html="result.success ? `✅ ${table}: ${result.count} records migrated` : `❌ ${table}: ${result.error}`"></li>
                        </template>
                    </ul>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button
                        class="form__button form__button--primary"
                        @click="resetMigration()"
                    >
                        {{ __('common.back') }}
                    </button>
                </div>
            </section>
        </div>
    </section>
</div>

<script>
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

        async testConnection() {
            this.testingConnection = true;
            this.connectionStatus = true;
            this.connectionMessage = '⏳ {{ __('common.loading') }}...';

            try {
                const response = await fetch('{{ route('staff.migrations.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();
                this.testingConnection = false;

                if (data.success) {
                    this.connectionSuccess = true;
                    this.connectionMessage = '✅ ' + data.message;
                    await this.getSummary();
                } else {
                    this.connectionSuccess = false;
                    this.connectionMessage = '❌ ' + data.message;
                }
            } catch (error) {
                this.testingConnection = false;
                this.connectionSuccess = false;
                this.connectionMessage = '❌ {{ __('common.error') }}: ' + error.message;
            }
        },

        async getSummary() {
            try {
                const response = await fetch('{{ route('staff.migrations.get-summary') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (data.success) {
                    this.summaryData = data.data;
                    this.showSummary = true;
                } else {
                    alert('{{ __('common.error') }}: ' + data.message);
                }
            } catch (error) {
                alert('{{ __('common.error') }}: ' + error.message);
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
            this.migrationLogs = '<p style="color: #ff9800;">⏳ {{ __('common.loading') }}...</p>';

            const formData = {
                ...this.form,
                tables: this.selectedTables
            };

            try {
                const response = await fetch('{{ route('staff.migrations.start') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(formData),
                });

                const data = await response.json();

                if (data.success) {
                    this.completionSummary = data.data;
                    this.progress = 100;
                    this.migrationLogs = '';
                    this.showProgress = false;
                    this.showCompleted = true;
                } else {
                    this.migrationLogs = `<div style="color: #c33;">❌ Error: ${data.message}</div>`;
                }
            } catch (error) {
                this.migrationLogs = `<div style="color: #c33;">❌ Error: ${error.message}</div>`;
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


<style>
    .form__input {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        font-family: inherit;
    }

    .form__button {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.2s ease;
    }

    .form__button--primary {
        background-color: #1976d2;
        color: white;
    }

    .form__button--primary:hover:not(:disabled) {
        background-color: #1565c0;
    }

    .form__button--secondary {
        background-color: #757575;
        color: white;
    }

    .form__button--secondary:hover:not(:disabled) {
        background-color: #616161;
    }

    .form__button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .alert {
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeaa7;
        color: #856404;
    }

    #summary-table tr:hover {
        background-color: #f5f5f5;
    }

    #summary-table input[type="checkbox"] {
        cursor: pointer;
    }

    .-col-span-2 {
        grid-column: span 2;
    }
</style>
