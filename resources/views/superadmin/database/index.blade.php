<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 28px 24px; max-width: 1180px; margin: 0 auto; }
        section { background: linear-gradient(180deg, #fffaf5 0%, #fff 100%); border: 1px solid #fdba74; border-radius: 8px; padding: 24px; box-shadow: 0 10px 24px rgba(154, 52, 18, .08); margin-bottom: 20px; }
        h1, h2 { margin: 6px 0 4px; line-height: 1.15; }
        h1 { font-size: 32px; }
        h2 { font-size: 23px; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .flash { color: #166534; margin: 0 0 14px; }
        .error { color: #b91c1c; margin: 0 0 14px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .panel { border: 1px solid #fed7aa; border-radius: 8px; padding: 18px; background: #fff; }
        .panel form { margin-top: 14px; display: grid; gap: 12px; }
        label { display: grid; gap: 7px; font-weight: 700; color: #7c2d12; }
        input[type="file"], input[type="text"] { width: 100%; border: 1px solid #fdba74; border-radius: 6px; padding: 10px 11px; font: inherit; color: #431407; background: #fffaf5; }
        .check-label { display: flex; align-items: flex-start; gap: 9px; font-weight: 700; line-height: 1.35; }
        .button { display: inline-flex; justify-content: center; align-items: center; border: 0; border-radius: 6px; background: #ea580c; color: #fff; min-height: 40px; padding: 10px 14px; text-decoration: none; font: inherit; font-weight: 700; cursor: pointer; }
        .danger { background: #b91c1c; }
        .secondary { background: #7c2d12; }
        table { width: 100%; border-collapse: collapse; overflow: hidden; }
        th, td { padding: 13px 12px; text-align: left; border-bottom: 1px solid #ffedd5; vertical-align: top; }
        th { color: #9a3412; font-size: 14px; white-space: nowrap; }
        tbody tr:hover { background: #fff7ed; }
        .empty-state { padding: 20px; border: 1px dashed #fdba74; border-radius: 8px; color: #9a3412; }
        @media (max-width: 920px) {
            .grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            main { padding: 16px; }
            h1 { font-size: 26px; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
@include('partials.header')

<main>
    <section>
        <p class="eyebrow">Admin</p>
        <h1>Database</h1>
        <div class="muted">Backup, import, dan reset database aplikasi Exambro.</div>
    </section>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="grid">
        <section class="panel">
            <h2>Backup Database</h2>
            <div class="muted">Buat file SQL dari seluruh tabel database saat ini.</div>
            <form method="post" action="{{ route('admin.database.backup') }}">
                @csrf
                <button class="button" type="submit">Backup Sekarang</button>
            </form>
        </section>

        <section class="panel">
            <h2>Import Database</h2>
            <div class="muted">Masukkan file SQL hasil backup untuk mengisi ulang database.</div>
            <form method="post" action="{{ route('admin.database.import') }}" enctype="multipart/form-data" onsubmit="return confirm('Import database akan menjalankan isi file SQL. Lanjutkan?')">
                @csrf
                <label>
                    File SQL
                    <input type="file" name="database_file" accept=".sql,.txt" required>
                </label>
                <label class="check-label">
                    <input type="checkbox" name="confirm_import" value="1" required>
                    Saya paham import akan mengubah isi database.
                </label>
                <button class="button secondary" type="submit">Import Database</button>
            </form>
        </section>

        <section class="panel">
            <h2>Reset Database</h2>
            <div class="muted">Kosongkan seluruh tabel data. Akun admin yang sedang dipakai akan dibuat ulang.</div>
            <form method="post" action="{{ route('admin.database.reset') }}" onsubmit="return confirm('Reset database akan menghapus data dari semua tabel. Lanjutkan?')">
                @csrf
                <label class="check-label">
                    <input type="checkbox" name="backup_before_reset" value="1" checked>
                    Buat backup sebelum reset.
                </label>
                <label>
                    Ketik RESET
                    <input type="text" name="confirm_reset" placeholder="RESET" required>
                </label>
                <button class="button danger" type="submit">Reset Database</button>
            </form>
        </section>
    </div>

    <section>
        <h2>File Backup</h2>

        @if ($backups->isEmpty())
            <div class="empty-state">Belum ada file backup.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($backups as $backup)
                        <tr>
                            <td><strong>{{ $backup['name'] }}</strong></td>
                            <td>{{ $backup['size'] }}</td>
                            <td>{{ $backup['created_at'] }}</td>
                            <td>
                                <a class="button" href="{{ route('admin.database.download', $backup['name']) }}">Download</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</main>
</body>
</html>
