<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Superadmin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        header { background: #f97316; color: #fff; padding: 18px 28px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 18px; }
        nav { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
        nav a {
            color: #fff;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 6px;
            background: rgba(255,255,255,0.16);
        }
        nav a.active { background: #fff; color: #c2410c; font-weight: 700; }
        main { padding: 24px; max-width: 1160px; margin: 0 auto; }
        section { background: #fff; border: 1px solid #fed7aa; border-radius: 8px; padding: 20px; margin-bottom: 18px; }
        h1 { margin: 6px 0 4px; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 18px 0; }
        .summary-item { background: #fff; border: 1px solid #fed7aa; border-radius: 8px; padding: 16px; }
        .summary-item strong { display: block; margin-top: 6px; font-size: 28px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ffedd5; }
        button { border: 0; border-radius: 6px; background: #9a3412; color: #fff; padding: 10px 12px; cursor: pointer; }
        @media (max-width: 900px) {
            .summary, .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header>
    <div class="topbar">
        <div>
            <strong>Dashboard Superadmin</strong>
            <div>{{ auth()->user()->username }}</div>
        </div>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Keluar</button>
        </form>
    </div>
    <nav>
        <a href="#" class="active">Data Siswa</a>
        <a href="#">Data Guru</a>
        <a href="#">Data Ujian</a>
    </nav>
</header>

<main>
    <section>
        <p class="eyebrow">Superadmin</p>
        <h1>Selamat datang, Pak Frans</h1>
        <div class="muted">Ringkasan awal pengelolaan data Exambro.</div>
    </section>

    <div class="summary">
        <div class="summary-item">
            <span class="muted">Data siswa</span>
            <strong>{{ $summary['students'] }}</strong>
        </div>
        <div class="summary-item">
            <span class="muted">Data guru</span>
            <strong>{{ $summary['teachers'] }}</strong>
        </div>
        <div class="summary-item">
            <span class="muted">Data ujian</span>
            <strong>{{ $summary['exams'] }}</strong>
        </div>
    </div>

    <div class="grid">
        <section>
            <h2>Data Siswa</h2>
            <table>
                <thead>
                    <tr><th>Nama</th><th>Kelas</th></tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $student->nama_siswa }}</td>
                            <td>{{ $student->nama_kelas ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="muted">Belum ada data siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Data Guru</h2>
            <table>
                <thead>
                    <tr><th>Nama</th><th>Mapel</th></tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td>{{ $teacher->nama_guru }}</td>
                            <td>{{ $teacher->mapel ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="muted">Belum ada data guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Data Ujian</h2>
            <table>
                <thead>
                    <tr><th>Ujian</th><th>Kelas</th></tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr>
                            <td>{{ $exam->title }}</td>
                            <td>{{ $exam->class_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="muted">Belum ada data ujian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</main>
</body>
</html>
