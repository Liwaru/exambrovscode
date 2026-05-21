<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Siswa</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 28px 24px; max-width: 720px; margin: 0 auto; }
        section {
            background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(154, 52, 18, .08);
        }
        h1 { margin: 6px 0 4px; font-size: 32px; line-height: 1.15; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .form-grid { display: grid; gap: 14px; margin-top: 20px; }
        label { display: grid; gap: 7px; color: #7c2d12; font-weight: 700; }
        input {
            width: 100%;
            border: 1px solid #fdba74;
            border-radius: 6px;
            padding: 11px 12px;
            color: #431407;
            font: inherit;
        }
        input:focus { outline: 2px solid #fed7aa; border-color: #ea580c; }
        .error { color: #b91c1c; font-weight: 400; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        button, .button {
            display: inline-flex;
            align-items: center;
            border: 0;
            border-radius: 6px;
            background: #ea580c;
            color: #fff;
            padding: 11px 14px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700;
        }
        .secondary { background: #9a3412; }
        @media (max-width: 700px) {
            main { padding: 16px; }
            h1 { font-size: 26px; }
        }
    </style>
</head>
<body>
@include('partials.header', [
    'dashboardRoute' => 'admin.dashboard',
    'dashboardLabel' => 'Ke dashboard admin',
    'navItems' => [
        ['label' => 'Data Siswa', 'href' => route('admin.students'), 'icon' => 'students'],
        ['label' => 'Data Guru', 'href' => route('admin.teachers'), 'icon' => 'teachers'],
        ['label' => 'Data Ujian', 'href' => route('admin.exams'), 'icon' => 'exams'],
    ],
])

<main>
    <section>
        <p class="eyebrow">Admin</p>
        <h1>Tambah Siswa</h1>
        <div class="muted">Isi nama dan kelas siswa. Username dibuat otomatis dari nama siswa.</div>

        <form method="post" action="{{ route('admin.students.store') }}" class="form-grid">
            @csrf

            <label>
                Nama siswa
                <input type="text" name="nama_siswa" value="{{ old('nama_siswa') }}" required>
                @error('nama_siswa') <span class="error">{{ $message }}</span> @enderror
            </label>

            <label>
                Kelas
                <input type="text" name="nama_kelas" value="{{ old('nama_kelas', 'RPL XI') }}" required>
                @error('nama_kelas') <span class="error">{{ $message }}</span> @enderror
            </label>

            <div class="actions">
                <button type="submit">Simpan Siswa</button>
                <a class="button secondary" href="{{ route('admin.students') }}">Batal</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
