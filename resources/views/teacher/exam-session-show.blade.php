<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $session->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 24px; max-width: 1040px; margin: 0 auto; }
        section { background: #fff; border: 1px solid #fed7aa; border-radius: 8px; padding: 20px; margin-bottom: 18px; }
        h1 { margin: 6px 0 8px; }
        .muted { color: #9a3412; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
        .meta-item { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 14px; }
        .pin-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .pin-card { border: 1px solid #fed7aa; border-radius: 8px; padding: 18px; }
        .pin-value { font-size: 30px; font-weight: 700; margin: 8px 0 14px; }
        .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 8px; font-size: 12px; font-weight: 700; }
        .status-online { background: #dcfce7; color: #166534; }
        .status-offline { background: #fee2e2; color: #991b1b; }
        .log-type { font-weight: 700; color: #9a3412; }
        .log-message { color: #7c2d12; font-size: 14px; margin-top: 3px; }
        .pagination-bar { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; margin-top: 14px; }
        .pagination-controls { display: flex; flex-wrap: wrap; gap: 6px; }
        .pagination-controls a, .pagination-controls span { display: inline-block; padding: 8px 10px; background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; border-radius: 6px; text-decoration: none; }
        .pagination-controls a.active { background: #ea580c; color: #fff; border-color: #ea580c; }
        .pagination-controls span.disabled { opacity: .55; cursor: not-allowed; }
        button, a.button {
            display: inline-block;
            border: 0;
            border-radius: 6px;
            background: #ea580c;
            color: #fff;
            padding: 10px 12px;
            text-decoration: none;
            cursor: pointer;
        }
        button.secondary, a.secondary { background: #9a3412; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ffedd5; }
        .flash { color: #166534; }
        .actions { display: flex; gap: 8px; align-items: center; }
        @media (max-width: 760px) {
            .meta, .pin-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@include('partials.header', ['dashboardRoute' => 'teacher.dashboard', 'dashboardLabel' => 'Ke dashboard guru'])

<main>
    <div style="margin-bottom: 14px;">
        <a class="button secondary" href="{{ route('teacher.dashboard') }}">Kembali</a>
    </div>

    <section>
        <p class="eyebrow">{{ $session->external_exam_id ? 'Sesi dari e-learning' : 'Sesi manual' }}</p>
        <h1>{{ $session->title }}</h1>
        <div class="muted">Guru mengelola PIN masuk dan PIN keluar dari halaman ini.</div>

        <div class="meta">
            <div class="meta-item">
                <div class="muted">Kelas</div>
                <strong>{{ $session->class_name }}</strong>
            </div>
            <div class="meta-item">
                <div class="muted">Tanggal</div>
                <strong>{{ $session->exam_date->format('d-m-Y') }}</strong>
            </div>
            <div class="meta-item">
                <div class="muted">Status</div>
                <strong id="session-status">{{ $session->status }}</strong>
            </div>
        </div>
    </section>

    <section>
        <h2>PIN Ujian</h2>
        <p class="flash" id="pin-feedback">{{ session('status') }}</p>
        <div class="pin-grid">
            <div class="pin-card">
                <div class="muted">PIN masuk</div>
                <div class="pin-value" id="entry-pin-value">{{ $session->entry_pin ?? '-' }}</div>
                <form method="post" action="{{ route('teacher.exam-sessions.entry-pin', $session) }}" data-pin-form data-pin-target="entry-pin-value" data-status-target="session-status">
                    @csrf
                    <button type="submit">Generate PIN masuk</button>
                </form>
            </div>
            <div class="pin-card">
                <div class="muted">PIN keluar</div>
                <div class="pin-value" id="exit-pin-value">{{ $session->exit_pin ?? '-' }}</div>
                <form method="post" action="{{ route('teacher.exam-sessions.exit-pin', $session) }}" data-pin-form data-pin-target="exit-pin-value">
                    @csrf
                    <button type="submit" class="secondary">Generate PIN keluar</button>
                </form>
            </div>
        </div>
    </section>

    <section>
        <h2>Status Perangkat Siswa</h2>
        <table>
            <thead>
                <tr>
                    <th>Perangkat</th>
                    <th>Aktivitas Terakhir</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="device-status-body">
                @forelse ($lastSeenByDevice as $activity)
                    @php
                        $secondsSinceSeen = $activity->occurred_at?->diffInSeconds(now()) ?? 999999;
                        $isOnline = $secondsSinceSeen <= 90;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $activity->student_username ?: 'Siswa belum login' }}</strong>
                            <div class="muted">{{ $activity->device_name ?: $activity->device_id ?: '-' }}</div>
                        </td>
                        <td>
                            {{ $activity->occurred_at?->format('d-m-Y H:i:s') }}
                            <div class="muted">{{ $activity->occurred_at?->diffForHumans() }}</div>
                        </td>
                        <td>
                            <span class="status-pill {{ $isOnline ? 'status-online' : 'status-offline' }}">
                                {{ $isOnline ? 'Online' : 'Tidak terdeteksi / kemungkinan restart' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada aktivitas dari aplikasi siswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section>
        <h2>Riwayat Aktivitas Siswa</h2>
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Siswa / Perangkat</th>
                    <th>Aktivitas</th>
                </tr>
            </thead>
            <tbody id="activity-log-body">
                @forelse ($activityLogs as $activity)
                    @php
                        $labels = [
                            'joined' => 'Masuk ujian',
                            'heartbeat' => 'Masih aktif',
                            'app_reopened' => 'Restart / dibuka ulang',
                            'app_backgrounded' => 'Aplikasi keluar layar',
                            'app_closed' => 'Aplikasi ditutup',
                            'interrupted' => 'Percobaan keluar paksa',
                            'student_identified' => 'Identitas e-learning terbaca',
                            'elearning_page_loaded' => 'Halaman e-learning terbuka',
                            'back_pressed' => 'Tombol Back ditekan',
                            'home_pressed' => 'Tombol Home / Recent ditekan',
                            'exit_button_pressed' => 'Tombol keluar ujian ditekan',
                            'exit_pin_failed' => 'PIN keluar salah',
                            'exited' => 'Keluar resmi',
                        ];
                    @endphp
                    <tr>
                        <td>
                            {{ $activity->occurred_at?->format('d-m-Y H:i:s') }}
                            <div class="muted">{{ $activity->occurred_at?->diffForHumans() }}</div>
                        </td>
                        <td>
                            <strong>{{ $activity->student_username ?: 'Siswa belum login' }}</strong>
                            <div class="muted">{{ $activity->device_name ?: $activity->device_id ?: '-' }}</div>
                        </td>
                        <td>
                            <div class="log-type">{{ $labels[$activity->event_type] ?? $activity->event_type }}</div>
                            @if ($activity->message)
                                <div class="log-message">{{ $activity->message }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada riwayat aktivitas.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-bar" id="activity-pagination">
            <div class="pagination-controls" id="activity-pagination-controls">
                @if ($activityLogs->lastPage() > 1)
                    @if ($activityLogs->onFirstPage())
                        <span class="disabled">Sebelumnya</span>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(['page' => $activityLogs->currentPage() - 1]) }}" data-activity-page="{{ $activityLogs->currentPage() - 1 }}">Sebelumnya</a>
                    @endif

                    @for ($page = 1; $page <= $activityLogs->lastPage(); $page++)
                        <a class="{{ $page === $activityLogs->currentPage() ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['page' => $page]) }}" data-activity-page="{{ $page }}">{{ $page }}</a>
                    @endfor

                    @if ($activityLogs->hasMorePages())
                        <a href="{{ request()->fullUrlWithQuery(['page' => $activityLogs->currentPage() + 1]) }}" data-activity-page="{{ $activityLogs->currentPage() + 1 }}">Berikutnya</a>
                    @else
                        <span class="disabled">Berikutnya</span>
                    @endif
                @endif
            </div>
        </div>
    </section>

</main>
<script>
    const activityLogsUrl = @json(route('teacher.exam-sessions.activity-logs', $session));
    const activityStreamUrl = @json(route('teacher.exam-sessions.activity-stream', $session));
    let currentActivityPage = @json($activityLogs->currentPage());
    const activityLabels = {
        joined: 'Masuk ujian',
        heartbeat: 'Masih aktif',
        app_reopened: 'Restart / dibuka ulang',
        app_backgrounded: 'Aplikasi keluar layar',
        app_closed: 'Aplikasi ditutup',
        interrupted: 'Percobaan keluar paksa',
        student_identified: 'Identitas e-learning terbaca',
        elearning_page_loaded: 'Halaman e-learning terbuka',
        back_pressed: 'Tombol Back ditekan',
        home_pressed: 'Tombol Home / Recent ditekan',
        exit_button_pressed: 'Tombol keluar ujian ditekan',
        exit_pin_failed: 'PIN keluar salah',
        exited: 'Keluar resmi',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderDeviceStatus(devices) {
        const body = document.getElementById('device-status-body');

        if (! devices.length) {
            body.innerHTML = '<tr><td colspan="3" class="muted">Belum ada aktivitas dari aplikasi siswa.</td></tr>';
            return;
        }

        body.innerHTML = devices.map((device) => {
            const statusClass = device.is_online ? 'status-online' : 'status-offline';
            const statusText = device.is_online ? 'Online' : 'Tidak terdeteksi / kemungkinan restart';

            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(device.student_username || 'Siswa belum login')}</strong>
                        <div class="muted">${escapeHtml(device.device_label || '-')}</div>
                    </td>
                    <td>
                        ${escapeHtml(device.occurred_at || '-')}
                        <div class="muted">${escapeHtml(device.relative_time || '')}</div>
                    </td>
                    <td>
                        <span class="status-pill ${statusClass}">${statusText}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function buildActivityLogsUrl(page = currentActivityPage) {
        const url = new URL(activityLogsUrl, window.location.origin);
        url.searchParams.set('page', page);

        return url.toString();
    }

    function buildPageUrl(page) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);

        return url.toString();
    }

    function renderActivityPagination(pagination) {
        const controls = document.getElementById('activity-pagination-controls');

        if (! pagination || ! pagination.total) {
            controls.innerHTML = '';
            return;
        }

        currentActivityPage = pagination.current_page;

        if (pagination.last_page <= 1) {
            controls.innerHTML = '';
            return;
        }

        const pages = Array.from({ length: pagination.last_page }, (_, index) => index + 1)
            .map((page) => `
                <a class="${page === pagination.current_page ? 'active' : ''}" href="${buildPageUrl(page)}" data-activity-page="${page}">
                    ${page}
                </a>
            `)
            .join('');

        controls.innerHTML = `
            ${pagination.current_page <= 1
                ? '<span class="disabled">Sebelumnya</span>'
                : `<a href="${buildPageUrl(pagination.current_page - 1)}" data-activity-page="${pagination.current_page - 1}">Sebelumnya</a>`}
            ${pages}
            ${pagination.current_page >= pagination.last_page
                ? '<span class="disabled">Berikutnya</span>'
                : `<a href="${buildPageUrl(pagination.current_page + 1)}" data-activity-page="${pagination.current_page + 1}">Berikutnya</a>`}
        `;
    }

    function renderActivityLogs(logs) {
        const body = document.getElementById('activity-log-body');

        if (! logs.length) {
            body.innerHTML = '<tr><td colspan="3" class="muted">Belum ada riwayat aktivitas.</td></tr>';
            return;
        }

        body.innerHTML = logs.map((log) => {
            const label = activityLabels[log.event_type] || log.event_type;
            const message = log.message
                ? `<div class="log-message">${escapeHtml(log.message)}</div>`
                : '';

            return `
                <tr>
                    <td>
                        ${escapeHtml(log.occurred_at || '-')}
                        <div class="muted">${escapeHtml(log.relative_time || '')}</div>
                    </td>
                    <td>
                        <strong>${escapeHtml(log.student_username || 'Siswa belum login')}</strong>
                        <div class="muted">${escapeHtml(log.device_label || '-')}</div>
                    </td>
                    <td>
                        <div class="log-type">${escapeHtml(label)}</div>
                        ${message}
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function refreshActivityLogs() {
        try {
            const response = await fetch(buildActivityLogsUrl(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (! response.ok) {
                return;
            }

            const data = await response.json();
            renderDeviceStatus(data.devices || []);
            renderActivityLogs(data.logs || []);
            renderActivityPagination(data.pagination);
        } catch (error) {
            // Keep the current table visible if the network drops briefly.
        }
    }

    function connectActivityStream() {
        if (! window.EventSource) {
            refreshActivityLogs();
            return;
        }

        const source = new EventSource(activityStreamUrl);

        source.addEventListener('activity', (event) => {
            const data = JSON.parse(event.data);
            renderDeviceStatus(data.devices || []);
            refreshActivityLogs();
        });

        source.onerror = () => {
            source.close();
            setTimeout(connectActivityStream, 3000);
        };
    }

    refreshActivityLogs();
    connectActivityStream();

    document.getElementById('activity-pagination-controls').addEventListener('click', (event) => {
        const link = event.target.closest('[data-activity-page]');

        if (! link) {
            return;
        }

        event.preventDefault();
        currentActivityPage = Number(link.dataset.activityPage);
        window.history.pushState({}, '', buildPageUrl(currentActivityPage));
        refreshActivityLogs();
    });

    window.addEventListener('popstate', () => {
        const page = Number(new URL(window.location.href).searchParams.get('page') || 1);
        currentActivityPage = page;
        refreshActivityLogs();
    });

    document.querySelectorAll('[data-pin-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const feedback = document.getElementById('pin-feedback');
            const pinTarget = document.getElementById(form.dataset.pinTarget);
            const statusTarget = form.dataset.statusTarget
                ? document.getElementById(form.dataset.statusTarget)
                : null;

            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = 'Memproses...';

            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                if (! response.ok) {
                    throw new Error('Gagal membuat PIN.');
                }

                const data = await response.json();
                pinTarget.textContent = data.pin;
                feedback.textContent = data.message;

                if (statusTarget && data.status) {
                    statusTarget.textContent = data.status;
                }
            } catch (error) {
                feedback.textContent = error.message;
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
</script>
</body>
</html>
