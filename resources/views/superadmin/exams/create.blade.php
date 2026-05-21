<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Ujian</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 28px 24px; max-width: 760px; margin: 0 auto; }
        section { background: linear-gradient(180deg, #fffaf5 0%, #fff 100%); border: 1px solid #fdba74; border-radius: 8px; padding: 24px; box-shadow: 0 10px 24px rgba(154, 52, 18, .08); }
        h1 { margin: 6px 0 4px; font-size: 32px; line-height: 1.15; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .form-grid { display: grid; gap: 14px; margin-top: 20px; }
        .time-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        label { display: grid; gap: 7px; color: #7c2d12; font-weight: 700; }
        input, select { width: 100%; border: 1px solid #fdba74; border-radius: 6px; padding: 11px 12px; color: #431407; font: inherit; background: #fff; }
        input:focus, select:focus { outline: 2px solid #fed7aa; border-color: #ea580c; }
        .error { color: #b91c1c; font-weight: 400; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        button, .button { display: inline-flex; align-items: center; border: 0; border-radius: 6px; background: #ea580c; color: #fff; padding: 11px 14px; text-decoration: none; cursor: pointer; font-weight: 700; }
        .secondary { background: #9a3412; }
        @media (max-width: 700px) {
            main { padding: 16px; }
            h1 { font-size: 26px; }
            .time-grid { grid-template-columns: 1fr; }
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
        <h1>Tambah Ujian</h1>
        <div class="muted">Tentukan pengawas, tanggal, jam, dan kelas ujian.</div>

        <form method="post" action="{{ route('admin.exams.store') }}" class="form-grid">
            @csrf

            <label>
                Nama ujian
                <select name="title" required>
                    <option value="">Pilih materi ujian</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject }}" @selected(old('title') === $subject)>{{ $subject }}</option>
                    @endforeach
                </select>
                @error('title') <span class="error">{{ $message }}</span> @enderror
            </label>

            <label>
                Guru pengawas
                <select name="teacher_id" required>
                    <option value="">Pilih guru pengawas</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id_user }}" @selected(old('teacher_id') == $teacher->id_user)>
                            {{ $teacher->nama_guru }}
                        </option>
                    @endforeach
                </select>
                @error('teacher_id') <span class="error">{{ $message }}</span> @enderror
            </label>

            <label>
                Tanggal ujian
                <input type="date" name="exam_date" value="{{ old('exam_date') }}" required>
                @error('exam_date') <span class="error">{{ $message }}</span> @enderror
            </label>

            <div class="time-grid">
                <label>
                    Jam mulai
                    <input type="time" name="start_time" value="{{ old('start_time') }}" required>
                    @error('start_time') <span class="error">{{ $message }}</span> @enderror
                </label>

                <label>
                    Jam selesai
                    <input type="time" name="end_time" value="{{ old('end_time') }}" required>
                    @error('end_time') <span class="error">{{ $message }}</span> @enderror
                </label>
            </div>

            <label>
                Kelas
                <select name="class_name" required>
                    <option value="">Pilih kelas</option>
                    @foreach ($classes as $className)
                        <option value="{{ $className }}" @selected(old('class_name', 'RPL XI') === $className)>{{ $className }}</option>
                    @endforeach
                </select>
                @error('class_name') <span class="error">{{ $message }}</span> @enderror
            </label>

            <div class="actions">
                <button type="submit">Simpan Ujian</button>
                <a class="button secondary" href="{{ route('admin.exams') }}">Batal</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
