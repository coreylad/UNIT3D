<div style="display: flex; flex-direction: column; gap: 1rem">
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

                <form id="migration-config-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label for="host" class="form__label">{{ __('migration.source-host') }}</label>
                        <input
                            type="text"
                            id="host"
                            name="host"
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
                            name="port"
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
                            name="username"
                            class="form__input"
                            required
                        />
                    </div>

                    <div>
                        <label for="password" class="form__label">{{ __('migration.source-password') }}</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form__input"
                            required
                        />
                    </div>

                    <div class="form__group -col-span-2">
                        <label for="database" class="form__label">{{ __('migration.source-database-name') }}</label>
                        <input
                            type="text"
                            id="database"
                            name="database"
                            class="form__input"
                            placeholder="admin_TSSE8"
                            required
                        />
                    </div>
                </form>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <button
                        id="test-connection-btn"
                        class="form__button form__button--primary"
                        onclick="testConnection()"
                    >
                        {{ __('migration.test-connection') }}
                    </button>
                </div>

                <div id="connection-status" style="padding: 1rem; border-radius: 0.5rem; display: none;">
                </div>
            </section>

            <!-- Migration Summary Section -->
            <section id="summary-section" style="display: none;">
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
                        <tbody id="summary-table">
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button
                        id="start-migration-btn"
                        class="form__button form__button--primary"
                        onclick="startMigration()"
                        style="display: none;"
                    >
                        {{ __('migration.start-migration') }}
                    </button>
                    <button
                        id="clear-selection-btn"
                        class="form__button form__button--secondary"
                        onclick="clearSelection()"
                        style="display: none;"
                    >
                        {{ __('common.clear') }}
                    </button>
                </div>
            </section>

            <!-- Progress Section -->
            <section id="progress-section" style="display: none;">
                <h3 style="margin-bottom: 1rem;">{{ __('migration.migration-progress') }}</h3>

                <div id="migration-progress" style="margin-bottom: 1.5rem;">
                    <div style="background: #f0f0f0; border-radius: 0.25rem; overflow: hidden;">
                        <div
                            id="progress-bar"
                            style="width: 0%; height: 2rem; background: linear-gradient(90deg, #4CAF50, #45a049); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; transition: width 0.3s ease;"
                        >
                            0%
                        </div>
                    </div>
                </div>

                <div id="migration-logs" style="background: #f5f5f5; border: 1px solid #ddd; border-radius: 0.5rem; padding: 1rem; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.875rem;">
                </div>
            </section>

            <!-- Completed Section -->
            <section id="completed-section" style="display: none;">
                <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 0.5rem; padding: 1rem; color: #155724;">
                    <h4>✅ {{ __('migration.migration-completed') }}</h4>
                    <div id="completion-summary"></div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button
                        class="form__button form__button--primary"
                        onclick="resetMigration()"
                    >
                        {{ __('common.back') }}
                    </button>
                </div>
            </section>
        </div>
    </section>
</div>

<script>
    let selectedTables = [];

    function getFormData() {
        return {
            host: document.getElementById('host').value,
            port: parseInt(document.getElementById('port').value),
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            database: document.getElementById('database').value,
        };
    }

    function testConnection() {
        const button = document.getElementById('test-connection-btn');
        const statusDiv = document.getElementById('connection-status');
        const formData = getFormData();

        button.disabled = true;
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<p style="color: #ff9800;">⏳ {{ __('common.loading') }}...</p>';

        fetch('{{ route('staff.migrations.test-connection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(formData),
        })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;

                if (data.success) {
                    statusDiv.style.backgroundColor = '#d4edda';
                    statusDiv.style.borderColor = '#c3e6cb';
                    statusDiv.style.color = '#155724';
                    statusDiv.innerHTML = '<p>✅ ' + data.message + '</p>';

                    // Get migration summary
                    getSummary(formData);
                } else {
                    statusDiv.style.backgroundColor = '#f8d7da';
                    statusDiv.style.borderColor = '#f5c6cb';
                    statusDiv.style.color = '#721c24';
                    statusDiv.innerHTML = '<p>❌ ' + data.message + '</p>';
                }
            })
            .catch(error => {
                button.disabled = false;
                statusDiv.style.backgroundColor = '#f8d7da';
                statusDiv.style.borderColor = '#f5c6cb';
                statusDiv.style.color = '#721c24';
                statusDiv.innerHTML = '<p>❌ {{ __('common.error') }}: ' + error.message + '</p>';
            });
    }

    function getSummary(config) {
        fetch('{{ route('staff.migrations.get-summary') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(config),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySummary(data.data);
                    document.getElementById('summary-section').style.display = 'block';
                } else {
                    alert('{{ __('common.error') }}: ' + data.message);
                }
            })
            .catch(error => {
                alert('{{ __('common.error') }}: ' + error.message);
            });
    }

    function displaySummary(summary) {
        const tbody = document.getElementById('summary-table');
        tbody.innerHTML = '';
        selectedTables = [];

        const tables = [
            { name: 'users', label: '{{ __('migration.users') }}' },
            { name: 'torrents', label: '{{ __('migration.torrents') }}' },
            { name: 'peers', label: '{{ __('migration.peers') }}' },
            { name: 'snatched', label: '{{ __('migration.snatched') }}' },
        ];

        tables.forEach(table => {
            const rowCount = summary[table.name] || 0;
            if (rowCount > 0) {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = 'table-' + table.name;
                checkbox.value = table.name;
                checkbox.onchange = (e) => {
                    if (e.target.checked) {
                        selectedTables.push(table.name);
                    } else {
                        selectedTables = selectedTables.filter(t => t !== table.name);
                    }
                    updateMigrationButton();
                };

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><label for="table-${table.name}" style="cursor: pointer;">${table.label}</label></td>
                    <td>${rowCount.toLocaleString()}</td>
                    <td><input type="checkbox" id="table-${table.name}" value="${table.name}" onchange="handleTableToggle(this)"></td>
                `;
                tbody.appendChild(tr);
            }
        });

        document.getElementById('start-migration-btn').style.display = 'none';
        document.getElementById('clear-selection-btn').style.display = 'none';
    }

    function handleTableToggle(checkbox) {
        if (checkbox.checked) {
            selectedTables.push(checkbox.value);
        } else {
            selectedTables = selectedTables.filter(t => t !== checkbox.value);
        }
        updateMigrationButton();
    }

    function updateMigrationButton() {
        const migrateBtn = document.getElementById('start-migration-btn');
        const clearBtn = document.getElementById('clear-selection-btn');

        if (selectedTables.length > 0) {
            migrateBtn.style.display = 'inline-block';
            clearBtn.style.display = 'inline-block';
        } else {
            migrateBtn.style.display = 'none';
            clearBtn.style.display = 'none';
        }
    }

    function clearSelection() {
        document.querySelectorAll('#summary-table input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        selectedTables = [];
        updateMigrationButton();
    }

    function startMigration() {
        if (!confirm('{{ __('migration.confirm-migration') }}')) {
            return;
        }

        const formData = getFormData();
        formData.tables = selectedTables;

        document.getElementById('summary-section').style.display = 'none';
        document.getElementById('progress-section').style.display = 'block';

        const logsDiv = document.getElementById('migration-logs');
        logsDiv.innerHTML = '<p style="color: #ff9800;">⏳ {{ __('common.loading') }}...</p>';

        fetch('{{ route('staff.migrations.start') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(formData),
        })
            .then(response => response.json())
            .then(data => {
                updateProgress(100, 'Migration completed!');

                if (data.success) {
                    displayCompletionSummary(data.data);
                    document.getElementById('progress-section').style.display = 'none';
                    document.getElementById('completed-section').style.display = 'block';
                } else {
                    displayMigrationError(data.message || '{{ __('migration.migration-failed') }}');
                }
            })
            .catch(error => {
                displayMigrationError(error.message);
            });
    }

    function updateProgress(percentage, message) {
        const progressBar = document.getElementById('progress-bar');
        const logsDiv = document.getElementById('migration-logs');

        progressBar.style.width = percentage + '%';
        progressBar.innerText = percentage + '%';

        if (message) {
            const timestamp = new Date().toLocaleTimeString();
            logsDiv.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            logsDiv.scrollTop = logsDiv.scrollHeight;
        }
    }

    function displayCompletionSummary(data) {
        const summary = document.getElementById('completion-summary');
        let html = '<ul style="margin-top: 0.5rem;">';

        for (const [table, result] of Object.entries(data)) {
            if (result.success) {
                html += `<li>✅ ${table}: ${result.count} records migrated</li>`;
            } else {
                html += `<li>❌ ${table}: ${result.error}</li>`;
            }
        }

        html += '</ul>';
        summary.innerHTML = html;
    }

    function displayMigrationError(message) {
        const logsDiv = document.getElementById('migration-logs');
        logsDiv.innerHTML = `<div style="color: #c33;">❌ Error: ${message}</div>`;

        const progressBar = document.getElementById('progress-bar');
        progressBar.style.background = 'linear-gradient(90deg, #f44336, #da190b)';
    }

    function resetMigration() {
        document.getElementById('summary-section').style.display = 'none';
        document.getElementById('progress-section').style.display = 'none';
        document.getElementById('completed-section').style.display = 'none';
        document.getElementById('migration-config-form').reset();
        document.getElementById('connection-status').style.display = 'none';
        selectedTables = [];
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
