<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7ed; color: #431407; }
        main { padding: 28px 24px; max-width: 1160px; margin: 0 auto; }
        section {
            background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 10px 24px rgba(154, 52, 18, .08);
        }
        h1 { margin: 6px 0 4px; font-size: 32px; line-height: 1.15; }
        .eyebrow { margin: 0; color: #ea580c; font-weight: 700; }
        .muted { color: #9a3412; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin: 18px 0 0; }
        .summary-item {
            background: linear-gradient(180deg, #fff7ed 0%, #fffaf5 100%);
            border: 1px solid #fdba74;
            border-left: 5px solid #f97316;
            border-radius: 8px;
            padding: 22px;
            min-height: 112px;
            box-shadow: 0 8px 18px rgba(154, 52, 18, .07);
        }
        .summary-item span { display: block; font-size: 17px; }
        .summary-item strong { display: block; margin-top: 10px; font-size: 34px; line-height: 1; }
        button { border: 0; border-radius: 6px; background: #9a3412; color: #fff; padding: 10px 12px; cursor: pointer; }
        @media (max-width: 700px) {
            main { padding: 16px; }
            h1 { font-size: 26px; }
            .summary { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@include('partials.header')

<main>
    <section>
        <p class="eyebrow">Admin</p>
        <h1>Selamat datang, Pak Dedi</h1>
        <div class="muted">Ringkasan awal pengelolaan data Exambro.</div>
    </section>

    <div class="summary">
        <div class="summary-item" id="data-siswa">
            <span class="muted">Data siswa</span>
            <strong>{{ $summary['students'] }}</strong>
        </div>
        <div class="summary-item" id="data-guru">
            <span class="muted">Data guru</span>
            <strong>{{ $summary['teachers'] }}</strong>
        </div>
        <div class="summary-item" id="data-ujian">
            <span class="muted">Data ujian</span>
            <strong>{{ $summary['exams'] }}</strong>
        </div>
    </div>

</main>
</body>
</html>
