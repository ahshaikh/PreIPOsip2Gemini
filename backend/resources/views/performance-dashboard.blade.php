<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Monitoring Dashboard - Pre-IPO SIP</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #60a5fa;
        }

        .subtitle {
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .period-selector {
            margin-bottom: 20px;
        }

        .period-selector button {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            padding: 8px 16px;
            margin-right: 10px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .period-selector button:hover {
            background: #334155;
        }

        .period-selector button.active {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px;
        }

        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 15px;
            color: #60a5fa;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .metric {
            margin-bottom: 15px;
        }

        .metric-label {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 5px;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e2e8f0;
        }

        .metric-value.success {
            color: #22c55e;
        }

        .metric-value.warning {
            color: #eab308;
        }

        .metric-value.danger {
            color: #ef4444;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.success {
            background: #22c55e20;
            color: #22c55e;
        }

        .status-badge.error {
            background: #ef444420;
            color: #ef4444;
        }

        .status-badge.pending {
            background: #eab30820;
            color: #eab308;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #334155;
        }

        th {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.875rem;
        }

        td {
            color: #e2e8f0;
        }

        .refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1e293b;
            border: 1px solid #334155;
            padding: 10px 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #334155;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-message {
            background: #ef444420;
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Performance Monitoring Dashboard</h1>
        <p class="subtitle">Real-time application performance metrics</p>

        <div class="period-selector">
            <button class="active" data-period="1h">Last Hour</button>
            <button data-period="24h">Last 24 Hours</button>
            <button data-period="7d">Last 7 Days</button>
            <button data-period="30d">Last 30 Days</button>
        </div>

        <div id="error-container"></div>

        <div class="grid">
            <!-- Database Metrics -->
            <div class="card">
                <h2>📊 Database</h2>
                <div class="metric">
                    <div class="metric-label">Active Connections</div>
                    <div class="metric-value" id="db-connections">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Slow Queries</div>
                    <div class="metric-value warning" id="slow-queries">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Avg Query Time</div>
                    <div class="metric-value" id="avg-query-time">-</div>
                </div>
            </div>

            <!-- Cache Metrics -->
            <div class="card">
                <h2>⚡ Cache (Redis)</h2>
                <div class="metric">
                    <div class="metric-label">Status</div>
                    <div id="cache-status">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Hit Rate</div>
                    <div class="metric-value success" id="cache-hit-rate">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Memory Used</div>
                    <div class="metric-value" id="cache-memory">-</div>
                </div>
            </div>

            <!-- Queue Metrics -->
            <div class="card">
                <h2>🔄 Queue</h2>
                <div class="metric">
                    <div class="metric-label">Default Queue</div>
                    <div class="metric-value" id="queue-default">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">High Priority</div>
                    <div class="metric-value" id="queue-high">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Webhooks</div>
                    <div class="metric-value" id="queue-webhooks">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Failed Jobs</div>
                    <div class="metric-value danger" id="queue-failed">-</div>
                </div>
            </div>

            <!-- Webhook Metrics -->
            <div class="card">
                <h2>🔗 Webhooks</h2>
                <div class="metric">
                    <div class="metric-label">Total Processed</div>
                    <div class="metric-value" id="webhook-total">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Success Rate</div>
                    <div class="metric-value success" id="webhook-success-rate">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Pending Retries</div>
                    <div class="metric-value warning" id="webhook-pending">-</div>
                </div>
            </div>

            <!-- System Metrics -->
            <div class="card">
                <h2>💻 System</h2>
                <div class="metric">
                    <div class="metric-label">PHP Version</div>
                    <div class="metric-value" id="php-version" style="font-size: 1rem;">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Memory Usage</div>
                    <div class="metric-value" id="memory-usage">-</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Peak Memory</div>
                    <div class="metric-value" id="peak-memory">-</div>
                </div>
            </div>
        </div>

        <!-- Slow Queries Table -->
        <div class="card">
            <h2>🐌 Recent Slow Queries</h2>
            <div style="overflow-x: auto;">
                <table id="slow-queries-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>SQL</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #94a3b8;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Webhook Failures -->
        <div class="card">
            <h2>❌ Recent Webhook Failures</h2>
            <div style="overflow-x: auto;">
                <table id="webhook-failures-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Type</th>
                            <th>Retries</th>
                            <th>Error</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="refresh-indicator" id="refresh-indicator" style="display: none;">
        <div class="spinner"></div>
        <span>Refreshing...</span>
    </div>

    <script>
        let currentPeriod = '1h';
        const API_BASE = '/api/v1/admin/performance';

        // Period selector
        document.querySelectorAll('.period-selector button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.period-selector button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentPeriod = btn.dataset.period;
                loadData();
            });
        });

        async function loadData() {
            const indicator = document.getElementById('refresh-indicator');
            indicator.style.display = 'flex';

            try {
                const response = await fetch(`${API_BASE}/overview?period=${currentPeriod}`);
                const data = await response.json();

                updateDashboard(data);
            } catch (error) {
                showError('Failed to load performance data: ' + error.message);
            } finally {
                indicator.style.display = 'none';
            }
        }

        function updateDashboard(data) {
            // Database metrics
            document.getElementById('db-connections').textContent = data.database?.connection_stats?.threads_connected || '0';
            document.getElementById('slow-queries').textContent = data.database?.query_stats?.slow_query_count || '0';
            document.getElementById('avg-query-time').textContent = (data.database?.query_stats?.avg_query_time || 0) + 'ms';

            // Cache metrics
            if (data.cache.connected) {
                document.getElementById('cache-status').innerHTML = '<span class="status-badge success">Connected</span>';
                document.getElementById('cache-hit-rate').textContent = (data.cache.hit_rate || 0) + '%';
                document.getElementById('cache-memory').textContent = data.cache.used_memory || 'N/A';
            } else {
                document.getElementById('cache-status').innerHTML = '<span class="status-badge error">Disconnected</span>';
            }

            // Queue metrics
            document.getElementById('queue-default').textContent = data.queue.default?.size || '0';
            document.getElementById('queue-high').textContent = data.queue.high?.size || '0';
            document.getElementById('queue-webhooks').textContent = data.queue.webhooks?.size || '0';
            document.getElementById('queue-failed').textContent = data.queue.failed || '0';

            // Webhook metrics
            const webhookTotal = data.webhooks.total || 0;
            const webhookSuccess = data.webhooks.success || 0;
            const successRate = webhookTotal > 0 ? ((webhookSuccess / webhookTotal) * 100).toFixed(1) : '0';

            document.getElementById('webhook-total').textContent = webhookTotal;
            document.getElementById('webhook-success-rate').textContent = successRate + '%';
            document.getElementById('webhook-pending').textContent = data.webhooks.pending || '0';

            // System metrics
            document.getElementById('php-version').textContent = data.system.php_version || 'N/A';
            document.getElementById('memory-usage').textContent = data.system.memory_usage || 'N/A';
            document.getElementById('peak-memory').textContent = data.system.peak_memory || 'N/A';

            // Update slow queries table
            updateSlowQueriesTable(data.database?.slow_queries || []);

            // Update webhook failures table
            updateWebhookFailuresTable(data.webhooks?.recent_failures || []);
        }

        function updateSlowQueriesTable(queries) {
            const tbody = document.querySelector('#slow-queries-table tbody');

            if (queries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #94a3b8;">No slow queries found</td></tr>';
                return;
            }

            tbody.innerHTML = queries.slice(0, 10).map(q => `
                <tr>
                    <td>${q.time}ms</td>
                    <td><span class="status-badge ${q.time > 2000 ? 'error' : 'warning'}">${q.time}ms</span></td>
                    <td style="max-width: 500px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${q.sql}</td>
                    <td>${new Date(q.timestamp).toLocaleString()}</td>
                </tr>
            `).join('');
        }

        function updateWebhookFailuresTable(failures) {
            const tbody = document.querySelector('#webhook-failures-table tbody');

            if (failures.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #94a3b8;">No recent failures</td></tr>';
                return;
            }

            tbody.innerHTML = failures.map(f => `
                <tr>
                    <td>#${f.id}</td>
                    <td>${f.event_type}</td>
                    <td>${f.retry_count}</td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${f.error_message}</td>
                    <td>${new Date(f.created_at).toLocaleString()}</td>
                </tr>
            `).join('');
        }

        function showError(message) {
            const container = document.getElementById('error-container');
            container.innerHTML = `<div class="error-message">${message}</div>`;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        // Auto-refresh every 30 seconds
        setInterval(loadData, 30000);

        // Initial load
        loadData();
    </script>
</body>
</html>
