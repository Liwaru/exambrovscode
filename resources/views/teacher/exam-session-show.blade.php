<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $session->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        header { background: #f97316; color: #fff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
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
<header>
    <div>
        <strong>Detail Ujian</strong>
        <div>{{ auth()->user()->username }}</div>
    </div>
    <div class="actions">
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="secondary">Keluar</button>
        </form>
    </div>
</header>

<main>
    <div style="margin-bottom: 14px;">
        <a class="button secondary" href="{{ route('teacher.dashboard') }}">Kembali</a>
    </div>

    <section>
        <p class="eyebrow">{{ $session->external_exam_id ? 'Dari e-learning' : 'Sesi manual' }}</p>
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

</main>
<script>
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
