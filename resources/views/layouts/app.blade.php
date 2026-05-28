<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-50:  #fff1f1;
            --brand-100: #ffe1e1;
            --brand-500: #ef4444;
            --brand-600: #dc2626;
            --brand-700: #b91c1c;
            --brand-rgb: 220, 38, 38;
            --ink-900:   #0f172a;
            --ink-700:   #334155;
            --ink-500:   #64748b;
            --ink-300:   #cbd5e1;
            --bg-soft:   #f6f8fc;
            --line:      #e6ebf4;
        }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-soft);
            color: var(--ink-900);
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--brand-600); text-decoration: none; }
        a:hover { color: var(--brand-700); }
        .text-primary { color: var(--brand-600) !important; }

        /* Sidebar */
        .app-shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .sidebar .brand {
            padding: 1.25rem 1.25rem;
            border-bottom: 1px solid var(--line);
            display: flex; align-items: center; gap: .65rem;
            font-weight: 700; font-size: 1.05rem; color: var(--ink-900);
        }
        .sidebar .brand .logo {
            width: 38px; height: 38px; border-radius: 10px;
            background: #fff;
            border: 1px solid var(--line);
            display: flex; align-items: center; justify-content: center;
            padding: .25rem;
            overflow: hidden;
        }
        .sidebar .brand .logo img {
            width: 100%; height: 100%;
            object-fit: contain;
        }
        .nav-section { padding: 1rem .75rem; }
        .nav-title {
            font-size: .72rem; font-weight: 600;
            color: var(--ink-500); text-transform: uppercase; letter-spacing: .06em;
            padding: 0 .75rem .5rem;
        }
        .nav-link-item {
            display: flex; align-items: center; gap: .65rem;
            padding: .55rem .75rem; border-radius: 10px;
            color: var(--ink-700); font-weight: 500; font-size: .92rem;
            margin-bottom: 2px;
            transition: background .15s, color .15s;
        }
        .nav-link-item i { font-size: 1.05rem; color: var(--ink-500); }
        .nav-link-item:hover { background: var(--brand-50); color: var(--brand-700); }
        .nav-link-item:hover i { color: var(--brand-600); }
        .nav-link-item.active {
            background: var(--brand-50);
            color: var(--brand-700);
        }
        .nav-link-item.active i { color: var(--brand-600); }
        .sidebar .footer {
            margin-top: auto;
            padding: .85rem 1rem;
            border-top: 1px solid var(--line);
            font-size: .8rem; color: var(--ink-500);
        }

        /* Topbar + main */
        .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--line);
            padding: .85rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 30;
        }
        .topbar .page-title { font-weight: 600; font-size: 1.05rem; margin: 0; }
        .topbar .user-chip {
            display: flex; align-items: center; gap: .65rem;
            padding: .35rem .65rem .35rem .35rem;
            border: 1px solid var(--line); border-radius: 999px;
            background: #fff;
        }
        .avatar {
            width: 32px; height: 32px; border-radius: 999px;
            background: var(--brand-100); color: var(--brand-700);
            display: grid; place-items: center; font-weight: 600;
        }
        .content { padding: 1.5rem; }

        /* Cards */
        .card { border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
        .card-header { background: #fff; border-bottom: 1px solid var(--line); padding: 1rem 1.25rem; font-weight: 600; }
        .card-body { padding: 1.25rem; }

        /* Stat cards */
        .stat-card { border: 1px solid var(--line); border-radius: 14px; background: #fff; padding: 1.1rem 1.25rem; }
        .stat-card .label { color: var(--ink-500); font-size: .8rem; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
        .stat-card .value { font-size: 1.85rem; font-weight: 700; margin-top: .35rem; color: var(--ink-900); }
        .stat-card .icon {
            width: 42px; height: 42px; border-radius: 12px;
            display: grid; place-items: center; font-size: 1.25rem;
        }
        .stat-card .delta { font-size: .8rem; color: var(--ink-500); margin-top: .25rem; }
        .bg-soft-primary { background: var(--brand-50); color: var(--brand-700); }
        .bg-soft-success { background: #e7f7ee; color: #157347; }
        .bg-soft-warning { background: #fff5e6; color: #b15c00; }
        .bg-soft-danger  { background: #fdecec; color: #b02a37; }
        .bg-soft-info    { background: #e5f4fb; color: #0c6f97; }

        /* Tables */
        .table { --bs-table-bg: transparent; margin-bottom: 0; }
        .table thead th {
            background: #fafbfd; color: var(--ink-500);
            font-weight: 600; font-size: .8rem;
            text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 1px solid var(--line);
            padding: .85rem 1rem;
        }
        .table tbody td { padding: .9rem 1rem; vertical-align: middle; border-color: var(--line); font-size: .9rem; }
        .table-hover tbody tr:hover { background: var(--bg-soft); }

        /* Badges */
        .badge-soft {
            display: inline-flex; align-items: center; gap: .3rem;
            font-weight: 500; font-size: .75rem; padding: .3rem .6rem;
            border-radius: 999px;
        }

        /* Forms */
        .form-label { font-weight: 500; color: var(--ink-700); font-size: .88rem; margin-bottom: .35rem; }
        .form-control, .form-select {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: .55rem .8rem;
            font-size: .92rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
        }
        .form-check-input:checked {
            background-color: var(--brand-600);
            border-color: var(--brand-600);
        }
        .form-check-input:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
        }
        .form-text { color: var(--ink-500); font-size: .8rem; }
        textarea.form-control { min-height: 96px; resize: vertical; }

        /* Buttons */
        .btn { border-radius: 10px; padding: .5rem .95rem; font-weight: 500; font-size: .9rem; }
        .btn-primary { background: var(--brand-600); border-color: var(--brand-600); }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background: var(--brand-700) !important;
            border-color: var(--brand-700) !important;
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
        }
        .btn-outline-secondary { color: var(--ink-700); border-color: var(--line); background: #fff; }
        .btn-outline-secondary:hover { background: var(--bg-soft); color: var(--ink-900); border-color: var(--ink-300); }
        .btn-soft-danger { background: #fdecec; color: #b02a37; border: 1px solid #f5c2c7; }
        .btn-soft-danger:hover { background: #f8d7da; color: #842029; }
        .page-link { color: var(--brand-600); }
        .active > .page-link,
        .page-link.active {
            background-color: var(--brand-600);
            border-color: var(--brand-600);
        }

        /* Login */
        .auth-shell {
            min-height: 100vh; display: grid; place-items: center;
            background:
                radial-gradient(1200px 600px at -10% -10%, rgba(var(--brand-rgb), .08), transparent 60%),
                radial-gradient(800px 500px at 110% 110%, rgba(185, 28, 28, .07), transparent 60%),
                var(--bg-soft);
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%; max-width: 420px;
            background: #fff; border: 1px solid var(--line); border-radius: 16px;
            padding: 2rem; box-shadow: 0 10px 30px rgba(15,23,42,.05);
        }

        @media (max-width: 991px) {
            .sidebar { position: fixed; transform: translateX(-100%); transition: transform .2s; z-index: 1040; }
            .sidebar.show { transform: translateX(0); }
            .menu-toggle { display: inline-flex !important; }
        }
        .menu-toggle { display: none; }
        .backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.4); display: none; z-index: 1030; }
        .backdrop.show { display: block; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        @auth
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="logo">
                    <img src="{{ asset('images/app-logo.png') }}" alt="Beacon Engineering">
                </div>
                <div>
                    <div>Daily Closing</div>
                    <small class="text-muted fw-normal" style="font-size:.72rem">Pelaporan Harian</small>
                </div>
            </div>
            @php
                $hasTeam = auth()->user()->isSuperAdmin() || auth()->user()->level !== \App\Models\User::LEVEL_STAFF;
                $teamMenuLabel = auth()->user()->isSuperAdmin() ? 'Semua Laporan' : 'Laporan Tim';
            @endphp
            <nav class="nav-section flex-grow-1">
                <div class="nav-title">Menu</div>
                <a href="{{ route('dashboard') }}" class="nav-link-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <div class="nav-title mt-3">Laporan Harian</div>
                <a href="{{ route('daily-reports.create') }}" class="nav-link-item {{ request()->routeIs('daily-reports.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-square"></i> Buat Laporan
                </a>
                <a href="{{ route('daily-reports.mine') }}" class="nav-link-item {{ request()->routeIs('daily-reports.mine') ? 'active' : '' }}">
                    <i class="bi bi-person-lines-fill"></i> Laporan Saya
                </a>
                @if($hasTeam)
                    <a href="{{ route('daily-reports.index') }}" class="nav-link-item {{ request()->routeIs('daily-reports.index') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> {{ $teamMenuLabel }}
                    </a>
                @endif

                @if(auth()->user()->canManageUsers())
                    <div class="nav-title mt-3">Administrasi</div>
                    <a href="{{ route('users.index') }}" class="nav-link-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> Manajemen User
                    </a>
                @endif
            </nav>
            <div class="footer">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="fw-semibold text-truncate" style="font-size:.85rem; color: var(--ink-900)">{{ auth()->user()->name }}</div>
                        <div class="text-truncate" style="font-size:.75rem">
                            {{ auth()->user()->role_label }}
                            @if(auth()->user()->is_super_admin && auth()->user()->level)
                                <span class="text-muted">· L{{ auth()->user()->level }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </aside>
        <div class="backdrop" id="backdrop"></div>
        @endauth

        <div class="main">
            @auth
            <header class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary menu-toggle" type="button" id="menuToggle" aria-label="Toggle menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="d-none d-md-inline text-muted small">{{ now()->translatedFormat('l, d F Y') }}</span>
                    <div class="dropdown">
                        <button class="user-chip border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                            <span class="d-none d-sm-inline fw-semibold small">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down small text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border:1px solid var(--line) !important; border-radius:12px;">
                            <li class="px-3 py-2 small text-muted">
                                Login sebagai<br>
                                <span class="fw-semibold text-dark">{{ auth()->user()->email }}</span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a href="{{ route('password.edit') }}" class="dropdown-item">
                                    <i class="bi bi-shield-lock me-2"></i>Ubah Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            @endauth

            <main class="content">
                @if (session('success'))
                    <div class="alert alert-success border-0 d-flex align-items-center" role="alert" style="background:#e7f7ee; color:#157347; border-radius:12px;">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif
                @if ($errors->any() && ! View::exists($errors->any() ? null : null))
                    {{-- Per-view error blocks handle field errors. Show a generic banner only for non-field errors. --}}
                @endif
                @if (session('warning'))
                    <div class="alert border-0 d-flex align-items-center" role="alert" style="background:#fff5e6; color:#b15c00; border-radius:12px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>{{ session('warning') }}</div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger border-0" role="alert" style="background:#fdecec; color:#b02a37; border-radius:12px;">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');
        if (toggle && sidebar && backdrop) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });
            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
