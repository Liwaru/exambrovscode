<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Guru</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        header { background: #f97316; color: #fff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
        main { padding: 24px; max-width: 1160px; margin: 0 auto; }
        section { background: #fff; border: 1px solid #fed7aa; border-radius: 8px; padding: 20px; margin-bottom: 18px; }
        .hero h1 { margin: 6px 0 4px; font-size: 28px; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(120px, 1fr)); gap: 12px; margin: 18px 0; }
        .summary-item { background: #fff; border: 1px solid #fed7aa; border-radius: 8px; padding: 16px; }
        .summary-item strong { display: block; font-size: 28px; margin-top: 6px; }
        button { border: 0; border-radius: 6px; background: #ea580c; color: #fff; padding: 10px 12px; cursor: pointer; }
        button.secondary { background: #9a3412; }
        .status { background: #ffedd5; color: #9a3412; padding: 4px 8px; border-radius: 999px; font-size: 12px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .flash { color: #166534; }
        .session-list { display: grid; gap: 14px; }
        .session-item {
            display: grid;
            grid-template-columns: minmax(220px, 1.6fr) repeat(3, minmax(120px, 0.7fr)) auto;
            gap: 16px;
            align-items: center;
            padding: 18px;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            background: #fffaf5;
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
            .summary { grid-template-columns: 1fr; }
            .session-item { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header>
    <div>
        <strong>Dashboard Guru</strong>
        <div>{{ auth()->user()->username }}</div>
    </div>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="secondary">Keluar</button>
    </form>
</header>

<main>
    <section class="hero">
        <p class="eyebrow">Exambro</p>
        <h1>Kelola sesi ujian</h1>
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
            <span class="muted">Dari e-learning</span>
            <strong>{{ $summary['synced'] }}</strong>
        </div>
    </div>

    <section>
        <h2>Sesi ujian</h2>
        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif

        <div class="session-list">
            @forelse ($sessions as $session)
                <article class="session-item">
                    <div>
                        <div class="session-title"><strong>{{ $session->title }}</strong></div>
                        <div class="muted">{{ $session->class_name }} · {{ $session->exam_date->format('d-m-Y') }}</div>
                        <div style="margin-top: 8px;">
                            @if ($session->external_exam_id)
                                <span class="status">{{ $session->external_source }} #{{ $session->external_exam_id }}</span>
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
                    </div>
                </article>
            @empty
                <div class="empty-state">Belum ada sesi ujian dari e-learning.</div>
            @endforelse
        </div>
    </section>
</main>
</body>
</html>
