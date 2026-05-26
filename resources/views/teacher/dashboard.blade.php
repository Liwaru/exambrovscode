<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Guru</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 24px; max-width: 1160px; margin: 0 auto; }
        section {
            background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 10px 24px rgba(154, 52, 18, .08);
        }
        .hero h1 { margin: 6px 0 4px; font-size: 28px; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 18px 0; }
        .summary-item {
            background: linear-gradient(180deg, #fff7ed 0%, #fffaf5 100%);
            border: 1px solid #fdba74;
            border-left: 5px solid #f97316;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 8px 18px rgba(154, 52, 18, .07);
        }
        .summary-item strong { display: block; font-size: 28px; margin-top: 6px; }
        button { border: 0; border-radius: 6px; background: #ea580c; color: #fff; padding: 10px 12px; cursor: pointer; }
        button.secondary { background: #9a3412; }
        button.danger { background: #b91c1c; }
        button:disabled { opacity: .55; cursor: not-allowed; }
        .status { background: #ffedd5; color: #9a3412; padding: 4px 8px; border-radius: 999px; font-size: 12px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .flash { color: #166534; }
        .section-heading {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 16px;
        }
        .section-heading h2 { margin: 0; }
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .select-all {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #9a3412;
            font-weight: 700;
        }
        .session-check {
            width: 18px;
            height: 18px;
            accent-color: #ea580c;
        }
        .session-list { display: grid; gap: 14px; }
        .session-item {
            display: grid;
            grid-template-columns: 24px minmax(220px, 1.6fr) repeat(3, minmax(120px, 0.7fr)) auto;
            gap: 16px;
            align-items: center;
            padding: 18px;
            border: 1px solid #fdba74;
            border-left: 5px solid #f97316;
            border-radius: 8px;
            background: linear-gradient(180deg, #fff7ed 0%, #fffaf5 100%);
            box-shadow: 0 8px 18px rgba(154, 52, 18, .07);
            transition: background .16s ease, box-shadow .16s ease;
        }
        .session-item:hover {
            background: #fff7ed;
            box-shadow: 0 10px 22px rgba(154, 52, 18, .1);
        }
        .session-title { font-size: 18px; margin-bottom: 6px; }
        .session-meta { display: grid; gap: 4px; }
        .session-meta strong { font-size: 18px; }
        .empty-state { padding: 18px; border: 1px dashed #fdba74; border-radius: 8px; color: #9a3412; }
        a.manage-link {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background: #ea580c;
            border-radius: 6px;
            padding: 10px 12px;
        }
        @media (max-width: 900px) {
            .session-item { grid-template-columns: 1fr; }
            .section-heading { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
@include('partials.header')

<main>
    <section class="hero">
        <p class="eyebrow">Dashboard Guru</p>
        <h1>{{ ucwords(auth()->user()->username) }}</h1>
        <div class="muted">Ujian dari e-learning akan masuk ke sini sebagai sesi tersinkron, lalu guru membuat PIN masuk dan PIN keluar.</div>
    </section>

    <div class="summary">
        <div class="summary-item">
            <span class="muted">Total sesi</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="summary-item">
            <span class="muted">Sesi aktif</span>
            <strong>{{ $summary['active'] }}</strong>
        </div>
        <div class="summary-item">
            <span class="muted">Sesi dari e-learning</span>
            <strong>{{ $summary['synced'] }}</strong>
        </div>
    </div>

    <section>
        <form method="post" action="{{ route('teacher.exam-sessions.destroy-selected') }}" id="bulk-delete-form">
            @csrf
            @method('delete')
        </form>

        <div class="section-heading">
            <h2>Sesi ujian</h2>
            <div class="bulk-actions">
                <label class="select-all">
                    <input type="checkbox" class="session-check" id="select-all-sessions">
                    <span>Pilih semua</span>
                </label>
                <button type="submit" form="bulk-delete-form" class="danger" id="delete-selected-button" disabled>Hapus terpilih</button>
            </div>
        </div>

        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif

        <div class="session-list">
            @forelse ($sessions as $session)
                <article class="session-item">
                    <input type="checkbox" form="bulk-delete-form" class="session-check session-checkbox" name="exam_session_ids[]" value="{{ $session->id }}" aria-label="Pilih {{ $session->title }}">
                    <div>
                        <div class="session-title"><strong>{{ $session->title }}</strong></div>
                        <div class="muted">
                            {{ $session->class_name }} · {{ $session->exam_date->format('d-m-Y') }}
                            @if ($session->start_time && $session->end_time)
                                · {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                            @endif
                        </div>
                    </div>
                    <div class="session-meta">
                        <span class="muted">Status</span>
                        <strong>{{ $session->status }}</strong>
                    </div>
                    <div class="session-meta">
                        <span class="muted">PIN masuk</span>
                        <strong>{{ $session->entry_pin ?? '-' }}</strong>
                    </div>
                    <div class="session-meta">
                        <span class="muted">PIN keluar</span>
                        <strong>{{ $session->exit_pin ?? '-' }}</strong>
                    </div>
                    <div class="actions">
                        <a class="manage-link" href="{{ route('teacher.exam-sessions.show', $session) }}">Kelola</a>
                        <form method="post" action="{{ route('teacher.exam-sessions.status', $session) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $session->status === 'active' ? 'inactive' : 'active' }}">
                            <button type="submit" class="{{ $session->status === 'active' ? 'danger' : '' }}">
                                {{ $session->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty-state">Belum ada sesi ujian dari e-learning.</div>
            @endforelse
        </div>
    </section>
</main>
<script>
    const bulkDeleteForm = document.getElementById('bulk-delete-form');
    const selectAllSessions = document.getElementById('select-all-sessions');
    const deleteSelectedButton = document.getElementById('delete-selected-button');
    const sessionCheckboxes = Array.from(document.querySelectorAll('.session-checkbox'));

    function updateBulkDeleteState() {
        const checkedCount = sessionCheckboxes.filter((checkbox) => checkbox.checked).length;
        deleteSelectedButton.disabled = checkedCount === 0;

        if (! sessionCheckboxes.length) {
            selectAllSessions.disabled = true;
            return;
        }

        selectAllSessions.checked = checkedCount === sessionCheckboxes.length;
        selectAllSessions.indeterminate = checkedCount > 0 && checkedCount < sessionCheckboxes.length;
    }

    selectAllSessions.addEventListener('change', () => {
        sessionCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAllSessions.checked;
        });
        updateBulkDeleteState();
    });

    sessionCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateBulkDeleteState);
    });

    bulkDeleteForm.addEventListener('submit', (event) => {
        const checkedCount = sessionCheckboxes.filter((checkbox) => checkbox.checked).length;

        if (checkedCount === 0 || ! confirm(`Hapus ${checkedCount} sesi ujian terpilih?`)) {
            event.preventDefault();
        }
    });

    updateBulkDeleteState();
</script>
</body>
</html>
