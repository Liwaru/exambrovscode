<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Guru</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.22), transparent 32%),
                radial-gradient(circle at bottom right, rgba(124, 45, 18, 0.18), transparent 30%),
                #f97316;
            color: #431407;
            font-family: Arial, sans-serif;
        }
        main {
            width: min(420px, calc(100% - 32px));
            padding: 30px;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 22px 50px rgba(124, 45, 18, 0.24);
        }
        h1 {
            margin: 0 0 8px;
            color: #431407;
            font-size: 28px;
        }
        p {
            margin: 0 0 20px;
            color: #9a3412;
        }
        label {
            display: block;
            margin-top: 14px;
            color: #7c2d12;
            font-size: 14px;
            font-weight: 600;
        }
        input {
            width: 100%;
            margin-top: 6px;
            padding: 12px;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            background: #fffaf5;
            color: #431407;
        }
        input:focus {
            outline: 2px solid #fb923c;
            outline-offset: 1px;
            background: #ffffff;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border: 0;
            border-radius: 6px;
            background: #ea580c;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover {
            background: #c2410c;
        }
        .error {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 6px;
            background: #fff1f2;
            color: #be123c;
            font-size: 14px;
        }
    </style>
</head>
<body>
<main>
    <h1>Login Guru</h1>
    <p>Masuk untuk mengelola sesi ujian.</p>

    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('login.attempt') }}">
        @csrf
        <label>
            Username
            <input type="text" name="username" value="{{ old('username') }}" required>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Masuk</button>
    </form>
</main>
</body>
</html>
