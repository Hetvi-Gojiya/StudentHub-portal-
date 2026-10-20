<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub — Live Events Manager</title>
    <meta name="description" content="Manage campus events in real-time using AJAX. Add, edit, delete events without page reload.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0f1117;
            --surface:   #1a1d27;
            --surface2:  #22263a;
            --border:    #2e3350;
            --accent:    #6c63ff;
            --accent2:   #a78bfa;
            --green:     #10b981;
            --red:       #ef4444;
            --yellow:    #f59e0b;
            --blue:      #3b82f6;
            --text:      #e2e8f0;
            --muted:     #94a3b8;
            --radius:    14px;
            --shadow:    0 8px 32px rgba(0,0,0,0.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Header ── */
        .page-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            border-bottom: 1px solid var(--border);
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .page-header h1 {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(90deg, #a78bfa, #6c63ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .page-header .badge {
            background: rgba(108,99,255,0.18);
            border: 1px solid rgba(108,99,255,0.4);
            color: #a78bfa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .header-links { display: flex; gap: 10px; }
        .header-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            padding: 6px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all .2s;
        }
        .header-links a:hover { color: var(--text); border-color: var(--accent); background: rgba(108,99,255,.1); }

        /* ── Layout ── */
        .app-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 24px;
            padding: 28px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }
        @media (max-width: 900px) { .app-layout { grid-template-columns: 1fr; } }

        /* ── Card ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-title span { font-size: 18px; }

        /* ── Form ── */
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 13px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(108,99,255,.15);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group select option { background: var(--surface2); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-primary  { background: var(--accent);  color: #fff; }
        .btn-primary:hover  { background: #5b52d6; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(108,99,255,.35); }
        .btn-secondary { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
        .btn-secondary:hover { color: var(--text); border-color: var(--accent); }
        .btn-full { width: 100%; justify-content: center; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-edit   { background: rgba(59,130,246,.15);  color: var(--blue);  border: 1px solid rgba(59,130,246,.3); }
        .btn-edit:hover   { background: rgba(59,130,246,.25); }
        .btn-delete { background: rgba(239,68,68,.15);   color: var(--red);   border: 1px solid rgba(239,68,68,.3); }
        .btn-delete:hover { background: rgba(239,68,68,.25); }

        .form-actions { display: flex; gap: 10px; margin-top: 6px; }

        /* ── Toast Notifications ── */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 14px;
            min-width: 280px;
            max-width: 360px;
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slideIn .3s ease;
        }
        .toast.success { border-left: 4px solid var(--green); }
        .toast.error   { border-left: 4px solid var(--red); }
        .toast.info    { border-left: 4px solid var(--blue); }
        .toast-icon { font-size: 18px; }
        .toast-body { flex: 1; }
        .toast-title { font-weight: 700; margin-bottom: 2px; }
        .toast-msg   { color: var(--muted); font-size: 13px; }
        @keyframes slideIn  { from { opacity:0; transform: translateX(30px); } to { opacity:1; transform: translateX(0); } }
        @keyframes slideOut { from { opacity:1; transform: translateX(0); }    to { opacity:0; transform: translateX(30px); } }
        .toast.hiding { animation: slideOut .3s ease forwards; }

        /* ── Loading Spinner ── */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Toolbar (search/filter/api tester) ── */
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .toolbar input,
        .toolbar select {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 9px 13px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color .2s;
        }
        .toolbar input:focus,
        .toolbar select:focus { border-color: var(--accent); }
        .toolbar input[type="text"] { flex: 1; min-width: 180px; }

        /* ── Events Table ── */
        .events-table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr { background: var(--surface2); }
        thead th {
            padding: 12px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid var(--border);
        }
        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        tbody tr:hover { background: rgba(108,99,255,.05); }
        tbody td { padding: 12px 14px; vertical-align: middle; }
        .event-title-cell { font-weight: 600; color: var(--text); max-width: 200px; }
        .event-desc-cell  { color: var(--muted); font-size: 12px; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .table-actions { display: flex; gap: 6px; }

        /* ── Status Badges ── */
        .badge-status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
        }
        .badge-upcoming  { background: rgba(108,99,255,.18); color: #a78bfa; }
        .badge-ongoing   { background: rgba(16,185,129,.18); color: #34d399; }
        .badge-completed { background: rgba(148,163,184,.18); color: #94a3b8; }
        .badge-cancelled { background: rgba(239,68,68,.18);  color: #fca5a5; }

        .badge-cat {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(59,130,246,.15);
            color: var(--blue);
        }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--muted);
        }
        .empty-state .empty-icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state h3 { font-size: 16px; margin-bottom: 6px; color: var(--text); }

        /* ── API Network Log Panel ── */
        .network-log {
            margin-top: 24px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .network-log-header {
            background: var(--surface2);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }
        .network-log-header .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--green);
            display: inline-block;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1}50%{opacity:.4} }
        .log-entries {
            max-height: 220px;
            overflow-y: auto;
            padding: 8px 0;
        }
        .log-entry {
            padding: 8px 16px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            border-bottom: 1px solid rgba(255,255,255,.04);
            display: grid;
            grid-template-columns: 60px 70px 1fr 80px;
            gap: 12px;
            align-items: center;
        }
        .log-method { font-weight: 700; }
        .log-GET    { color: var(--green); }
        .log-POST   { color: var(--blue);  }
        .log-PUT    { color: var(--yellow);}
        .log-DELETE { color: var(--red);   }
        .log-status-ok  { color: var(--green); font-weight: 700; }
        .log-status-err { color: var(--red);   font-weight: 700; }
        .log-url    { color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .log-time   { color: var(--muted); text-align: right; }

        /* ── Stats Bar ── */
        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-chip {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stat-chip strong { font-size: 18px; font-weight: 800; }
        .stat-chip.purple strong { color: #a78bfa; }
        .stat-chip.green  strong { color: var(--green); }
        .stat-chip.yellow strong { color: var(--yellow); }

        /* ── Skeleton Loader ── */
        .skeleton-row td { padding: 12px 14px; }
        .skeleton {
            background: linear-gradient(90deg, var(--surface2) 25%, var(--border) 50%, var(--surface2) 75%);
            background-size: 400% 100%;
            animation: shimmer 1.4s ease infinite;
            border-radius: 6px;
            height: 14px;
        }
        @keyframes shimmer { 0%{background-position:100% 50%} 100%{background-position:0% 50%} }
    </style>
</head>
<body>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- Header -->
<header class="page-header">
    <div style="display:flex; align-items:center; gap:14px;">
        <h1>⚡ Live Events Manager</h1>
        <span class="badge">Fetch API · No Page Reload</span>
    </div>
    <div class="header-links">
        <a href="admin_dashboard.php">👑 Admin</a>
        <a href="view_records.php">👥 Students</a>
        <a href="manage_events.php">📋 Classic View</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</header>

<!-- App Layout -->
<div class="app-layout">

    <!-- LEFT: Add / Edit Form -->
    <div>
        <div class="card">
            <div class="card-title"><span>✨</span> <span id="form-heading">Add New Event</span></div>
            <form id="event-form" novalidate>
                <input type="hidden" id="edit-id" value="">

                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" id="f-title" placeholder="e.g. Tech Symposium 2025" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" id="f-date" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select id="f-category" required>
                            <option value="">-- Select --</option>
                            <option>Academic</option>
                            <option>Cultural</option>
                            <option>Sports</option>
                            <option>Technical</option>
                            <option>Workshop</option>
                            <option>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="f-location" placeholder="e.g. Main Auditorium">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="f-description" placeholder="Brief description of the event..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Max Participants</label>
                        <input type="number" id="f-maxp" min="1" placeholder="e.g. 100">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="f-status">
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Validation error panel -->
                <div id="validation-errors" style="display:none; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); border-radius: 8px; padding: 12px; margin-bottom: 12px; font-size: 13px; color: #fca5a5;"></div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submit-btn" style="flex:1; justify-content:center;">
                        <span id="submit-icon">➕</span>
                        <span id="submit-label">Add Event</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()" id="cancel-btn" style="display:none;">✕ Cancel</button>
                </div>
            </form>
        </div>

        <!-- API Tester (Advanced) -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-title"><span>🔬</span> Raw API Tester</div>
            <div class="form-group">
                <label>Endpoint</label>
                <select id="api-method">
                    <option value="GET">GET — List All Events</option>
                    <option value="GET-ID">GET — Single Event by ID</option>
                </select>
            </div>
            <div class="form-group" id="api-id-group" style="display:none;">
                <label>Event ID</label>
                <input type="number" id="api-id-val" placeholder="e.g. 1">
            </div>
            <button class="btn btn-secondary btn-full" onclick="testApi()" id="api-test-btn">
                🚀 Send Request
            </button>
            <div id="api-response-box" style="display:none; margin-top:14px; background: #0d1117; border:1px solid var(--border); border-radius:8px; padding:14px; max-height:180px; overflow-y:auto;">
                <pre id="api-response" style="font-size:11px; color:#a78bfa; white-space:pre-wrap; word-break:break-all;"></pre>
            </div>
        </div>
    </div>

    <!-- RIGHT: Events List -->
    <div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-chip purple"><strong id="stat-total">—</strong> Total Events</div>
            <div class="stat-chip green"><strong id="stat-upcoming">—</strong> Upcoming</div>
            <div class="stat-chip yellow"><strong id="stat-ongoing">—</strong> Ongoing</div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <input type="text" id="search-input" placeholder="🔍 Search events..." oninput="debounceSearch()">
            <select id="filter-category" onchange="loadEvents()">
                <option value="">All Categories</option>
                <option>Academic</option>
                <option>Cultural</option>
                <option>Sports</option>
                <option>Technical</option>
                <option>Workshop</option>
                <option>Other</option>
            </select>
            <select id="filter-status" onchange="loadEvents()">
                <option value="">All Statuses</option>
                <option value="upcoming">Upcoming</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button class="btn btn-secondary btn-sm" onclick="loadEvents()" id="refresh-btn" title="Refresh">🔄 Refresh</button>
        </div>

        <!-- Table -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="events-table-wrap">
                <table id="events-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Participants</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="events-tbody">
                        <!-- Skeleton loader -->
                        <tr class="skeleton-row"><td colspan="8"><div class="skeleton" style="width:100%;"></div></td></tr>
                        <tr class="skeleton-row"><td colspan="8"><div class="skeleton" style="width:85%;"></div></td></tr>
                        <tr class="skeleton-row"><td colspan="8"><div class="skeleton" style="width:70%;"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Network Log -->
        <div class="network-log">
            <div class="network-log-header">
                <div><span class="dot"></span> API Network Log</div>
                <button class="btn btn-secondary btn-sm" onclick="clearLog()">Clear</button>
            </div>
            <div class="log-entries" id="log-entries">
                <div style="padding: 16px; text-align:center; color: var(--muted); font-size: 13px;">No API calls yet. Interact with events to see live network activity.</div>
            </div>
        </div>

    </div>
</div>

<script>
// ── Config ──────────────────────────────────────────────────────
const API_URL = 'api_events.php';
let searchTimer = null;
let isEditing   = false;

// ── Toast ────────────────────────────────────────────────────────
function showToast(type, title, msg) {
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || 'ℹ️'}</div>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${msg}</div>
        </div>`;
    document.getElementById('toast-container').appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ── Network Log ──────────────────────────────────────────────────
function logRequest(method, url, statusCode, ok) {
    const container = document.getElementById('log-entries');
    // Clear placeholder if first entry
    if (container.querySelector('div')) container.innerHTML = '';
    const entry = document.createElement('div');
    entry.className = 'log-entry';
    const time = new Date().toLocaleTimeString('en-US', { hour12: false });
    entry.innerHTML = `
        <span class="log-method log-${method}">${method}</span>
        <span class="${ok ? 'log-status-ok' : 'log-status-err'}">${statusCode}</span>
        <span class="log-url">${url}</span>
        <span class="log-time">${time}</span>`;
    container.insertBefore(entry, container.firstChild);
}

function clearLog() {
    document.getElementById('log-entries').innerHTML =
        '<div style="padding:16px;text-align:center;color:var(--muted);font-size:13px;">Log cleared.</div>';
}

// ── Fetch Wrapper ────────────────────────────────────────────────
async function apiFetch(url, options = {}) {
    const method = options.method || 'GET';
    try {
        const res = await fetch(url, {
            headers: { 'Content-Type': 'application/json' },
            ...options
        });
        logRequest(method, url.replace(API_URL, '/api_events.php'), res.status, res.ok);
        const data = await res.json();
        return { ok: res.ok, status: res.status, data };
    } catch (err) {
        logRequest(method, url.replace(API_URL, '/api_events.php'), 'ERR', false);
        throw err;
    }
}

// ── Load / Display Events ─────────────────────────────────────────
async function loadEvents() {
    const search   = document.getElementById('search-input').value.trim();
    const category = document.getElementById('filter-category').value;
    const status   = document.getElementById('filter-status').value;

    const params = new URLSearchParams();
    if (search)   params.set('search', search);
    if (category) params.set('category', category);
    if (status)   params.set('status', status);

    const url = `${API_URL}?${params.toString()}`;

    // Show skeleton
    document.getElementById('events-tbody').innerHTML =
        `<tr class="skeleton-row"><td colspan="8"><div class="skeleton"></div></td></tr>`.repeat(3);

    document.getElementById('refresh-btn').innerHTML = '<span class="spinner"></span> Loading';

    try {
        const { ok, data } = await apiFetch(url);
        if (!ok) throw new Error(data.message);

        const events = data.data.events;
        renderTable(events);
        updateStats(events);
    } catch (err) {
        showToast('error', 'Load Failed', err.message || 'Could not fetch events.');
        document.getElementById('events-tbody').innerHTML =
            `<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--red);">⚠️ Failed to load events. Check server connection.</td></tr>`;
    } finally {
        document.getElementById('refresh-btn').innerHTML = '🔄 Refresh';
    }
}

function renderTable(events) {
    const tbody = document.getElementById('events-tbody');
    if (events.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="8">
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No events found</h3>
                    <p>Try different filters or add a new event using the form.</p>
                </div>
            </td></tr>`;
        return;
    }

    const statusBadge = {
        upcoming:  '<span class="badge-status badge-upcoming">Upcoming</span>',
        ongoing:   '<span class="badge-status badge-ongoing">Ongoing</span>',
        completed: '<span class="badge-status badge-completed">Completed</span>',
        cancelled: '<span class="badge-status badge-cancelled">Cancelled</span>',
    };

    tbody.innerHTML = events.map(ev => `
        <tr id="row-${ev.id}">
            <td style="color:var(--muted);font-size:12px;">#${ev.id}</td>
            <td>
                <div class="event-title-cell">${escHtml(ev.title)}</div>
                <div class="event-desc-cell">${escHtml(ev.description || '—')}</div>
            </td>
            <td style="white-space:nowrap;">${formatDate(ev.event_date)}</td>
            <td><span class="badge-cat">${escHtml(ev.category || '—')}</span></td>
            <td style="color:var(--muted);font-size:12px;">${escHtml(ev.location || '—')}</td>
            <td style="text-align:center;">${ev.max_participants ?? '∞'}</td>
            <td>${statusBadge[ev.status] || ev.status}</td>
            <td>
                <div class="table-actions">
                    <button class="btn btn-sm btn-edit" onclick="editEvent(${ev.id})" title="Edit">✏️</button>
                    <button class="btn btn-sm btn-delete" onclick="deleteEvent(${ev.id}, '${escHtml(ev.title).replace(/'/g, "\\'")}')" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>`).join('');
}

function updateStats(events) {
    document.getElementById('stat-total').textContent    = events.length;
    document.getElementById('stat-upcoming').textContent = events.filter(e => e.status === 'upcoming').length;
    document.getElementById('stat-ongoing').textContent  = events.filter(e => e.status === 'ongoing').length;
}

// ── Add / Submit Form ─────────────────────────────────────────────
document.getElementById('event-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id    = document.getElementById('edit-id').value;
    const body  = {
        title:            document.getElementById('f-title').value.trim(),
        event_date:       document.getElementById('f-date').value,
        category:         document.getElementById('f-category').value,
        location:         document.getElementById('f-location').value.trim(),
        description:      document.getElementById('f-description').value.trim(),
        max_participants: document.getElementById('f-maxp').value || null,
        status:           document.getElementById('f-status').value,
    };

    hideValidationErrors();

    const submitBtn   = document.getElementById('submit-btn');
    const submitLabel = document.getElementById('submit-label');
    const submitIcon  = document.getElementById('submit-icon');

    submitBtn.disabled = true;
    submitIcon.innerHTML = '<span class="spinner"></span>';
    submitLabel.textContent = isEditing ? 'Updating...' : 'Adding...';

    try {
        let result;
        if (isEditing) {
            result = await apiFetch(`${API_URL}?id=${id}`, { method: 'PUT',    body: JSON.stringify(body) });
        } else {
            result = await apiFetch(API_URL,                { method: 'POST',   body: JSON.stringify(body) });
        }

        if (!result.ok) {
            if (result.data.data?.validation_errors) {
                showValidationErrors(result.data.data.validation_errors);
            } else {
                showToast('error', 'Error', result.data.message);
            }
            return;
        }

        showToast('success', isEditing ? 'Event Updated!' : 'Event Added!', result.data.message);
        resetForm();
        loadEvents();

    } catch (err) {
        showToast('error', 'Request Failed', 'Could not reach server. Is XAMPP running?');
    } finally {
        submitBtn.disabled = false;
        submitIcon.textContent = isEditing ? '💾' : '➕';
        submitLabel.textContent = isEditing ? 'Update Event' : 'Add Event';
    }
});

// ── Edit ──────────────────────────────────────────────────────────
async function editEvent(id) {
    try {
        const { ok, data } = await apiFetch(`${API_URL}?id=${id}`);
        if (!ok) { showToast('error', 'Not Found', data.message); return; }

        const ev = data.data;
        document.getElementById('edit-id').value        = ev.id;
        document.getElementById('f-title').value        = ev.title;
        document.getElementById('f-date').value         = ev.event_date;
        document.getElementById('f-category').value     = ev.category;
        document.getElementById('f-location').value     = ev.location || '';
        document.getElementById('f-description').value  = ev.description || '';
        document.getElementById('f-maxp').value         = ev.max_participants || '';
        document.getElementById('f-status').value       = ev.status;

        isEditing = true;
        document.getElementById('form-heading').textContent   = 'Edit Event';
        document.getElementById('submit-icon').textContent    = '💾';
        document.getElementById('submit-label').textContent   = 'Update Event';
        document.getElementById('cancel-btn').style.display   = 'block';

        // Scroll to form
        document.getElementById('event-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('f-title').focus();

        showToast('info', 'Editing', `Now editing: ${ev.title}`);
    } catch (err) {
        showToast('error', 'Error', 'Failed to fetch event for editing.');
    }
}

// ── Delete ────────────────────────────────────────────────────────
async function deleteEvent(id, title) {
    if (!confirm(`Delete event:\n"${title}"\n\nThis cannot be undone.`)) return;

    // Animate row out
    const row = document.getElementById(`row-${id}`);
    if (row) { row.style.opacity = '0.4'; row.style.transition = 'opacity .3s'; }

    try {
        const { ok, data } = await apiFetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
        if (!ok) { showToast('error', 'Delete Failed', data.message); if (row) row.style.opacity = '1'; return; }

        showToast('success', 'Deleted!', data.message);
        loadEvents();
    } catch (err) {
        showToast('error', 'Error', 'Could not delete. Check server.');
        if (row) row.style.opacity = '1';
    }
}

// ── Reset Form ────────────────────────────────────────────────────
function resetForm() {
    document.getElementById('event-form').reset();
    document.getElementById('edit-id').value           = '';
    document.getElementById('form-heading').textContent = 'Add New Event';
    document.getElementById('submit-icon').textContent  = '➕';
    document.getElementById('submit-label').textContent = 'Add Event';
    document.getElementById('cancel-btn').style.display = 'none';
    isEditing = false;
    hideValidationErrors();
}

// ── Validation Errors ─────────────────────────────────────────────
function showValidationErrors(errors) {
    const box = document.getElementById('validation-errors');
    box.innerHTML = '<strong>⚠️ Please fix these errors:</strong><ul style="margin:6px 0 0 16px;">' +
        errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
    box.style.display = 'block';
}
function hideValidationErrors() {
    const box = document.getElementById('validation-errors');
    box.style.display = 'none';
    box.innerHTML = '';
}

// ── Debounce Search ───────────────────────────────────────────────
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadEvents, 350);
}

// ── Raw API Tester ────────────────────────────────────────────────
document.getElementById('api-method').addEventListener('change', function() {
    document.getElementById('api-id-group').style.display = this.value === 'GET-ID' ? 'block' : 'none';
});

async function testApi() {
    const methodVal = document.getElementById('api-method').value;
    const idVal     = document.getElementById('api-id-val').value;
    const btn       = document.getElementById('api-test-btn');
    btn.innerHTML   = '<span class="spinner"></span> Sending...';
    btn.disabled    = true;

    const url = methodVal === 'GET-ID' ? `${API_URL}?id=${idVal}` : API_URL;

    try {
        const { data } = await apiFetch(url);
        document.getElementById('api-response-box').style.display = 'block';
        document.getElementById('api-response').textContent = JSON.stringify(data, null, 2);
    } catch (err) {
        document.getElementById('api-response-box').style.display = 'block';
        document.getElementById('api-response').textContent = 'Error: ' + err.message;
    } finally {
        btn.innerHTML = '🚀 Send Request';
        btn.disabled  = false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });
}

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
});
</script>

</body>
</html>
