<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catatan Aktivitas</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 28px 24px; max-width: 1180px; margin: 0 auto; }
        section { background: linear-gradient(180deg, #fffaf5 0%, #fff 100%); border: 1px solid #fdba74; border-radius: 8px; padding: 24px; box-shadow: 0 10px 24px rgba(154, 52, 18, .08); margin-bottom: 20px; }
        h1, h2 { margin: 6px 0 4px; line-height: 1.15; }
        h1 { font-size: 32px; }
        h2 { font-size: 24px; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .flash { color: #166534; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; overflow: hidden; }
        th, td { padding: 13px 12px; text-align: left; border-bottom: 1px solid #ffedd5; vertical-align: top; }
        th { color: #9a3412; font-size: 14px; white-space: nowrap; }
        tbody tr:hover { background: #fff7ed; }
        .button { display: inline-flex; align-items: center; border: 0; border-radius: 6px; background: #ea580c; color: #fff; min-height: 38px; padding: 10px 14px; text-decoration: none; font: inherit; font-weight: 700; cursor: pointer; }
        .empty-state { padding: 20px; border: 1px dashed #fdba74; border-radius: 8px; color: #9a3412; }
        .badge { display: inline-flex; border-radius: 999px; background: #ffedd5; color: #9a3412; padding: 5px 9px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 18px; }
        .tab-link { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 10px 16px; border-radius: 6px; border: 1px solid #fdba74; color: #9a3412; text-decoration: none; font-weight: 700; background: #fff; }
        .tab-link.active { background: #ea580c; border-color: #ea580c; color: #fff; }
        .tab-panel[hidden] { display: none; }
        .page-head { margin-bottom: 18px; }
        .pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 16px; color: #9a3412; }
        .pagination-info { font-size: 14px; }
        .pagination-controls { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .pagination-controls a,
        .pagination-controls span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            min-height: 36px;
            padding: 8px 11px;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            background: #fff7ed;
            color: #9a3412;
            text-decoration: none;
            font-size: 14px;
            line-height: 1;
        }
        .pagination-controls a:hover { background: #ffedd5; }
        .pagination-controls .active { background: #ea580c; border-color: #ea580c; color: #fff; font-weight: 700; }
        .pagination-controls .disabled { opacity: .55; cursor: not-allowed; }
        @media (max-width: 760px) {
            main { padding: 16px; }
            h1 { font-size: 26px; }
            h2 { font-size: 21px; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
@include('partials.header')

<main>
    <section>
        <div class="page-head">
            <p class="eyebrow">Admin</p>
            <h1>Catatan Aktivitas</h1>
            <div class="muted">Pantau aktivitas pengguna dan perubahan data secara terpisah.</div>
            <div class="tabs" aria-label="Pilihan catatan aktivitas">
                <a class="tab-link {{ $activeTab === 'aktivitas' ? 'active' : '' }}" href="#aktivitas" data-tab-target="aktivitas">Aktivitas</a>
                <a class="tab-link {{ $activeTab === 'data' ? 'active' : '' }}" href="#data" data-tab-target="data">Data</a>
            </div>
        </div>

        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif
    </section>

    @php
        $levelLabel = fn ($level) => match ((int) $level) {
            1 => 'Siswa',
            2 => 'Guru',
            3 => 'Admin',
            4 => 'Kepala Sekolah',
            default => 'Sistem',
        };
    @endphp

    <div class="tab-panel" data-tab-panel="aktivitas" {{ $activeTab !== 'aktivitas' ? 'hidden' : '' }}>
        <section>
            <h2>Riwayat Aktivitas</h2>
            <p class="muted">Berisi login, logout, buka menu, dan aktivitas penggunaan fitur.</p>

            @if ($activityLogs->isEmpty())
                <div class="empty-state">Belum ada aktivitas yang tercatat.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Level</th>
                            <th>Aktivitas</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activityLogs as $activity)
                            <tr>
                                <td>{{ $activity->created_at?->format('d-m-Y H:i:s') }}</td>
                                <td>{{ $activity->actor_username ?? 'Sistem / tidak login' }}</td>
                                <td>{{ $levelLabel($activity->actor_level) }}</td>
                                <td>
                                    <span class="badge">{{ $activity->event_type }}</span><br>
                                    {{ $activity->description }}
                                </td>
                                <td>{{ $activity->ip_address ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $activityLogs->firstItem() }}-{{ $activityLogs->lastItem() }} dari {{ $activityLogs->total() }} aktivitas
                    </div>
                    <div class="pagination-controls">
                        @if ($activityLogs->onFirstPage())
                            <span class="disabled">Sebelumnya</span>
                        @else
                            <a href="{{ $activityLogs->previousPageUrl() }}">Sebelumnya</a>
                        @endif

                        @for ($page = 1; $page <= $activityLogs->lastPage(); $page++)
                            @if ($page === $activityLogs->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $activityLogs->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($activityLogs->hasMorePages())
                            <a href="{{ $activityLogs->nextPageUrl() }}">Berikutnya</a>
                        @else
                            <span class="disabled">Berikutnya</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>

    <div class="tab-panel" data-tab-panel="data" {{ $activeTab !== 'data' ? 'hidden' : '' }}>
        <section>
            <h2>Data yang Bisa Dipulihkan</h2>
            <p class="muted">Data yang dihapus dari menu siswa, guru, dan ujian akan muncul di sini.</p>

            @if ($recoverableLogs->isEmpty())
                <div class="empty-state">Tidak ada data terhapus yang menunggu dipulihkan.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Waktu Hapus</th>
                            <th>Data</th>
                            <th>Dihapus Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recoverableLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d-m-Y H:i') }}</td>
                                <td>
                                    <strong>{{ $log->description }}</strong><br>
                                    <span class="badge">{{ $log->subject_table }} #{{ $log->subject_id }}</span>
                                </td>
                                <td>{{ $log->actor_username ?? '-' }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.activity-logs.restore', $log) }}" onsubmit="return confirm('Pulihkan data ini?')">
                                        @csrf
                                        <button class="button" type="submit">Pulihkan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section>
            <h2>Riwayat Perubahan Data</h2>
            <p class="muted">Berisi tambah, edit, hapus, pulihkan, backup, import, dan reset database.</p>

            @if ($dataLogs->isEmpty())
                <div class="empty-state">Belum ada perubahan data yang tercatat.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Level</th>
                            <th>Perubahan</th>
                            <th>Data</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataLogs as $activity)
                            <tr>
                                <td>{{ $activity->created_at?->format('d-m-Y H:i:s') }}</td>
                                <td>{{ $activity->actor_username ?? 'Sistem / tidak login' }}</td>
                                <td>{{ $levelLabel($activity->actor_level) }}</td>
                                <td>
                                    <span class="badge">{{ $activity->event_type }}</span><br>
                                    {{ $activity->description }}
                                </td>
                                <td>
                                    @if ($activity->subject_table)
                                        {{ $activity->subject_table }} #{{ $activity->subject_id }}
                                        @if ($activity->restored_at)
                                            <br><span class="muted">Dipulihkan {{ $activity->restored_at->format('d-m-Y H:i') }}</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $activity->ip_address ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $dataLogs->firstItem() }}-{{ $dataLogs->lastItem() }} dari {{ $dataLogs->total() }} perubahan
                    </div>
                    <div class="pagination-controls">
                        @if ($dataLogs->onFirstPage())
                            <span class="disabled">Sebelumnya</span>
                        @else
                            <a href="{{ $dataLogs->previousPageUrl() }}">Sebelumnya</a>
                        @endif

                        @for ($page = 1; $page <= $dataLogs->lastPage(); $page++)
                            @if ($page === $dataLogs->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $dataLogs->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($dataLogs->hasMorePages())
                            <a href="{{ $dataLogs->nextPageUrl() }}">Berikutnya</a>
                        @else
                            <span class="disabled">Berikutnya</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</main>
<script>
    (() => {
        const tabs = [...document.querySelectorAll('[data-tab-target]')];
        const panels = [...document.querySelectorAll('[data-tab-panel]')];

        const activateTab = (name, updateHash = true) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.tabTarget === name;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.tabPanel !== name;
            });

            if (updateHash) {
                history.replaceState(null, '', `#${name}`);
            }
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', (event) => {
                event.preventDefault();
                activateTab(tab.dataset.tabTarget);
            });
        });

        const initialTab = location.hash === '#data' ? 'data' : '{{ $activeTab }}';
        activateTab(initialTab, false);
    })();
</script>
</body>
</html>
