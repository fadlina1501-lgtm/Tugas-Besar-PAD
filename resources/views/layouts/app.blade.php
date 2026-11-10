<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Dashboard') · Campus Facility Maintenance</title>

    <link rel="stylesheet" href="{{ asset('css/cfm.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-shell">

        {{-- Sidebar --}}
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">CF</span>
                <span>
                    <strong>Campus Facility</strong>
                    <small>Maintenance System</small>
                </span>
            </a>

            <div class="nav-caption">WORKSPACE</div>

            <nav class="nav-links">
                {{-- Dashboard --}}
                <a
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"
                >
                    <span>▦</span>
                    Dashboard
                </a>

                {{-- Data Aset hanya untuk Sarpras dan Manajemen --}}
                @if(in_array(auth()->user()->role, ['sarpras', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}"
                        href="{{ route('assets.index') }}"
                    >
                        <span>▤</span>
                        Data Aset
                    </a>
                @endif

                {{-- Life-Cycle Cost --}}
                @if(in_array(auth()->user()->role, ['sarpras', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('life-cycle-cost.*') ? 'active' : '' }}"
                        href="{{ route('life-cycle-cost.index') }}"
                    >
                        <span>◒</span>
                        Life-Cycle Cost
                    </a>
                @endif

                {{-- Master Data --}}
                @if(in_array(auth()->user()->role, ['sarpras', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('master-data.*') ? 'active' : '' }}"
                        href="{{ route('master-data.index') }}"
                    >
                        <span>◈</span>
                        Master Data
                    </a>
                @endif

                {{-- Laporan Kerusakan --}}
                @if(in_array(auth()->user()->role, ['pengguna', 'sarpras', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}"
                    >
                        <span>◉</span>
                        Laporan Kerusakan
                    </a>
                @endif

                {{-- Tiket Maintenance --}}
                @if(in_array(auth()->user()->role, ['sarpras', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}"
                        href="{{ route('tickets.index') }}"
                    >
                        <span>▣</span>
                        Tiket Maintenance
                    </a>
                @endif

                {{-- Work Order --}}
                @if(in_array(auth()->user()->role, ['sarpras', 'teknisi', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('work-orders.*') ? 'active' : '' }}"
                        href="{{ route('work-orders.index') }}"
                    >
                        <span>⚒</span>
                        Work Order
                    </a>
                @endif

                {{-- Suku Cadang --}}
                @if(in_array(auth()->user()->role, ['teknisi', 'manajemen']))
                    <a
                        class="nav-link {{ request()->routeIs('spare-parts.*') ? 'active' : '' }}"
                        href="{{ route('spare-parts.index') }}"
                    >
                        <span>◫</span>
                        Suku Cadang
                    </a>
                @endif

                {{-- Manajemen Pengguna --}}
                @if(auth()->user()->role === 'manajemen')
                    <a
                        class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}"
                    >
                        <span>♙</span>
                        Manajemen Pengguna
                    </a>
                @endif
            </nav>

            {{-- Status Sistem --}}
            <div class="sidebar-bottom">
                <div class="sidebar-note">
                    <span class="dot"></span>

                    <div>
                        <strong>System Status</strong>
                        <small>Operational · Local</small>
                    </div>
                </div>

                <small class="muted">Facility &amp; Life-Cycle Cost</small>
            </div>
        </aside>

        {{-- Area Konten Utama --}}
        <main class="main-area">

            {{-- Header --}}
            <header class="topbar">
                <div>
                    <div class="eyebrow">CAMPUS FACILITY / OPERATIONS</div>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                </div>

                {{-- Profil Pengguna --}}
                <div class="user-menu">
                    <div class="avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="user-meta">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->roleLabel() }}</small>
                    </div>

                    {{-- Tombol Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="logout-button"
                            title="Keluar dari akun"
                            aria-label="Keluar dari akun"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>

                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            {{-- Konten Halaman --}}
            <section class="page-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        <span>✓</span>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        <span>!</span>
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">
                        <span>!</span>

                        <div>
                            <strong>Periksa kembali input kamu.</strong>

                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </section>

            {{-- Footer --}}
            <footer class="footer">
                Campus Facility Maintenance &amp; Life-Cycle Cost System
                <span>·</span>
                Gedung B Cacuk
            </footer>

        </main>
    </div>

    @stack('scripts')
</body>
</html>
