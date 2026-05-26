<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') · {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-50:  #eef4ff;
            --brand-100: #dbe7ff;
            --brand-500: #3b6cf6;
            --brand-600: #2f59d8;
            --brand-700: #2848b3;
            --ink-900:   #0f172a;
            --ink-700:   #334155;
            --ink-500:   #64748b;
            --line:      #e6ebf4;
            --bg-soft:   #f6f8fc;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(1200px 600px at -10% -10%, rgba(59,108,246,.10), transparent 60%),
                radial-gradient(800px 500px at 110% 110%, rgba(47,89,216,.10), transparent 60%),
                var(--bg-soft);
            display: grid; place-items: center; padding: 1.5rem;
            color: var(--ink-900);
        }
        .auth-card {
            width: 100%; max-width: 420px;
            background: #fff; border: 1px solid var(--line); border-radius: 16px;
            padding: 2rem; box-shadow: 0 10px 30px rgba(15,23,42,.05);
        }
        .login-logo-wrap {
            width: min(255px, 72vw);
            height: 64px;
            margin: 0 auto .85rem;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-logo {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .brand-mark {
            width: 52px; height: 52px; border-radius: 14px;
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            color: #fff; display: grid; place-items: center; font-size: 1.4rem;
        }
        .form-label { font-weight: 500; color: var(--ink-700); font-size: .88rem; margin-bottom: .35rem; }
        .form-control {
            border: 1px solid var(--line); border-radius: 10px; padding: .55rem .8rem;
        }
        .form-control:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(59, 108, 246, .15);
        }
        .input-group-text { background: #fff; border-color: var(--line); border-radius: 10px 0 0 10px; }
        .input-group .form-control { border-radius: 0 10px 10px 0; }
        .btn-primary {
            background: var(--brand-600); border-color: var(--brand-600);
            border-radius: 10px; padding: .6rem .95rem; font-weight: 600;
        }
        .btn-primary:hover { background: var(--brand-700); border-color: var(--brand-700); }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
