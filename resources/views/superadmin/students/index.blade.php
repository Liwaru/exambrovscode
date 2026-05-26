<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Siswa</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 28px 24px; max-width: 1160px; margin: 0 auto; }
        section {
            background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(154, 52, 18, .08);
        }
        .page-head { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 18px; }
        h1 { margin: 6px 0 4px; font-size: 32px; line-height: 1.15; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .button {
            display: inline-flex;
            align-items: center;
            border-radius: 6px;
            background: #ea580c;
            color: #fff;
            padding: 11px 14px;
            text-decoration: none;
            font-weight: 700;
        }
        .flash { color: #166534; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; overflow: hidden; }
        th, td { padding: 13px 12px; text-align: left; border-bottom: 1px solid #ffedd5; }
        th { color: #9a3412; font-size: 14px; }
        tbody tr:hover { background: #fff7ed; }
        .row-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .row-actions form { margin: 0; }
        button.button { border: 0; cursor: pointer; font: inherit; }
        .danger { background: #b91c1c; }
        .empty-state { padding: 20px; border: 1px dashed #fdba74; border-radius: 8px; color: #9a3412; }
        @media (max-width: 700px) {
            main { padding: 16px; }
            .page-head { align-items: flex-start; flex-direction: column; }
            h1 { font-size: 26px; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
@include('partials.header')

<main>
    <section>
        <div class="page-head">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Data Siswa</h1>
                <div class="muted">Daftar siswa yang terdaftar di Exambro.</div>
            </div>
            <a class="button" href="{{ route('admin.students.create') }}">Tambah Siswa</a>
        </div>

        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif

        @if ($students->isEmpty())
            <div class="empty-state">Belum ada data siswa.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Username</th>
                        <th>Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr>
                            <td><strong>{{ $student->nama_siswa }}</strong></td>
                            <td>{{ $student->username ?? '-' }}</td>
                            <td>{{ $student->nama_kelas ?? '-' }}</td>
                            <td>
                                <div class="row-actions">
                                    <a class="button" href="{{ route('admin.students.edit', $student->id_siswa) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.students.destroy', $student->id_siswa) }}" onsubmit="return confirm('Hapus data siswa ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="button danger">Hapus</button>
                                    </form>
                                </div>
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
