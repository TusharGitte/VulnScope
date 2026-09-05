<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VAPT Platform' }} | Authorized Web Security Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #05070d;
            --bg-secondary: #0d1220;
            --bg-card: #121a2b;
            --bg-card-hover: #17223a;
            --border-color: #1f2c44;
            --border-focus: #3b82f6;
            --text-main: #f3f4f6;
            --text-muted: #8b98b3;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --accent-cyan: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --info: #3b82f6;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.4);
            --shadow-md: 0 8px 24px -8px rgba(0,0,0,0.55);
            --shadow-glow: 0 0 0 1px rgba(59,130,246,0.15), 0 8px 30px -10px rgba(37,99,235,0.35);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-primary);
            background-image:
                radial-gradient(ellipse 900px 500px at 15% -10%, rgba(37,99,235,0.16), transparent 60%),
                radial-gradient(ellipse 700px 500px at 100% 0%, rgba(6,182,212,0.10), transparent 55%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        code, pre, .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        ::selection { background: rgba(37,99,235,0.35); color: #fff; }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: #2c3e5c; }

        header.navbar {
            background: rgba(10, 14, 24, 0.72);
            backdrop-filter: blur(16px) saturate(140%);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 0.9rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
            transition: opacity 0.15s ease;
        }
        .nav-brand:hover { opacity: 0.85; }

        .brand-badge {
            background: linear-gradient(135deg, #0ea5e9, #2563eb 60%, #7c3aed);
            color: #fff;
            padding: 0.25rem 0.55rem;
            border-radius: var(--radius-sm);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 2px 10px -2px rgba(37,99,235,0.6);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            position: relative;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            padding-bottom: 2px;
            transition: color 0.15s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            left: 0; right: 100%;
            bottom: -2px;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-cyan), var(--primary));
            border-radius: 2px;
            transition: right 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #fff;
        }
        .nav-link:hover::after, .nav-link.active::after { right: 0; }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.15rem;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: var(--radius-sm);
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease;
            border: 1px solid transparent;
        }
        .btn:active { transform: translateY(1px) scale(0.99); }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: #fff;
            box-shadow: 0 4px 14px -4px rgba(37,99,235,0.55);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover), #2563eb);
            box-shadow: 0 6px 20px -4px rgba(37,99,235,0.7);
        }

        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-main);
            border-color: var(--border-color);
        }
        .btn-secondary:hover {
            background: var(--bg-card-hover);
            border-color: #3b4d6b;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #f87171);
            color: #fff;
            box-shadow: 0 4px 14px -4px rgba(239,68,68,0.5);
        }
        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #34d399);
            color: #fff;
            box-shadow: 0 4px 14px -4px rgba(16,185,129,0.5);
        }
        .btn-success:hover {
            background: #059669;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            box-shadow: none;
        }

        main.container {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
            flex: 1;
        }

        .alert {
            padding: 0.95rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
            animation: alert-in 0.2s ease;
        }
        @keyframes alert-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .card {
            background: linear-gradient(180deg, var(--bg-card), rgba(18,26,43,0.7));
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover { border-color: #2c3e5c; }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .step-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .step-item:hover { border-color: #34486b; transform: translateY(-1px); }

        .step-item.active {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.10);
            color: #fff;
            box-shadow: var(--shadow-glow);
        }

        .step-item.completed {
            border-color: var(--success);
            color: #fff;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: #fff;
            flex-shrink: 0;
        }

        .step-item.active .step-num {
            background: linear-gradient(135deg, var(--primary), var(--accent-cyan));
        }

        .step-item.completed .step-num {
            background: var(--success);
        }

        .badge {
            display: inline-block;
            padding: 0.22rem 0.65rem;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-critical { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.5); }
        .badge-high { background: rgba(249, 115, 22, 0.2); color: #fdba74; border: 1px solid rgba(249,115,22,0.5); }
        .badge-medium { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245,158,11,0.5); }
        .badge-low { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59,130,246,0.5); }
        .badge-info { background: rgba(148, 163, 184, 0.18); color: #cbd5e1; border: 1px solid rgba(100,116,139,0.5); }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: var(--radius-sm);
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        table.data-table th {
            text-align: left;
            padding: 0.8rem 1rem;
            background: var(--bg-secondary);
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        table.data-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        table.data-table tr:last-child td { border-bottom: none; }

        table.data-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: #fff;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .form-input::placeholder, .form-textarea::placeholder { color: #5b6b87; }

        footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            background: var(--bg-secondary);
        }

        @media (max-width: 720px) {
            header.navbar { padding: 0.75rem 1.1rem; }
            .nav-links { gap: 1rem; order: 3; width: 100%; justify-content: center; }
            main.container { padding: 1.1rem; }
            .workflow-steps { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <a href="{{ route('dashboard') }}" class="nav-brand">
            <span class="brand-badge">VAPT</span>
            <span>Security Assessment Platform</span>
        </a>

        @auth
            <nav class="nav-links">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">Projects</a>
                @auth<a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Settings</a>@endauth
            </nav>

            <div class="user-menu">
                <span style="font-size: 0.85rem; color: var(--text-muted);">{{ Auth::user()->name }} ({{ Auth::user()->role }})</span>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Log Out</button>
                </form>
            </div>
        @else
            <div class="user-menu">
                <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">Log In</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
            </div>
        @endauth
    </header>

    <main class="container">
        @if (session('success'))
            <div class="alert alert-success">
                <span>✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                <span>✕</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div>
                    <strong>Please review the errors below:</strong>
                    <ul style="margin-left: 1.25rem; margin-top: 0.5rem;">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        Authorized Web Vulnerability Assessment & Penetration Testing Platform &bull; Server Time: {{ now()->toDateTimeString() }}
    </footer>
</body>
</html>
