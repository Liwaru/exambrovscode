@php
    $dashboardRoute = $dashboardRoute ?? (auth()->user()?->role === 'admin' ? 'admin.dashboard' : 'teacher.dashboard');
    $dashboardLabel = $dashboardLabel ?? 'Ke dashboard';
    $navItems = $navItems ?? [];
@endphp

<style>
    .app-header {
        background: #f97316;
        color: #fff;
        padding: 15px 22px;
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr) 112px;
        align-items: center;
        gap: 18px;
    }

    .app-brand-link {
        display: inline-flex;
        align-items: center;
        color: #fff;
        text-decoration: none;
    }

    .app-brand-logo {
        height: 50px;
        width: auto;
        display: block;
    }

    .app-nav {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .app-nav-link {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        border-radius: 6px;
        border: 1px solid rgba(255,255,255,.28);
        background: rgba(255,255,255,.08);
        color: #fff;
        min-width: 102px;
        min-height: 50px;
        padding: 7px 11px;
        text-decoration: none;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.1;
        text-align: center;
        box-shadow: none;
    }

    .app-nav-link:hover {
        border-color: rgba(255,255,255,.5);
        background: rgba(255,255,255,.16);
    }

    .app-nav-icon {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .app-logout-button {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid rgba(255,255,255,.42);
        border-radius: 6px;
        background: rgba(255,255,255,.1);
        color: #fff;
        min-height: 38px;
        padding: 9px 14px;
        cursor: pointer;
        font-weight: 700;
        box-shadow: none;
    }

    .app-logout-button:hover {
        border-color: rgba(255,255,255,.65);
        background: rgba(255,255,255,.18);
    }

    .app-logout-icon {
        width: 16px;
        height: 16px;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    @media (max-width: 700px) {
        .app-header {
            grid-template-columns: 1fr auto;
            gap: 14px;
        }

        .app-nav {
            order: 3;
            grid-column: 1 / -1;
            justify-content: flex-start;
        }
    }
</style>

<header class="app-header">
    <a class="app-brand-link" href="{{ route($dashboardRoute) }}" aria-label="{{ $dashboardLabel }}">
        <img class="app-brand-logo" src="{{ asset('images/sph.png') }}" alt="SPH">
    </a>

    @if ($navItems)
        <nav class="app-nav" aria-label="Menu utama">
            @foreach ($navItems as $item)
                <a class="app-nav-link" href="{{ $item['href'] }}">
                    @switch($item['icon'] ?? '')
                        @case('students')
                            <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            @break

                        @case('teachers')
                            <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5Z" />
                                <path d="M6 12v5c3 2 9 2 12 0v-5" />
                            </svg>
                            @break

                        @case('exams')
                            <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                <path d="M14 2v6h6" />
                                <path d="M9 15h6" />
                                <path d="M9 11h2" />
                            </svg>
                            @break
                    @endswitch

                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="app-logout-button">
            <svg class="app-logout-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="M16 17l5-5-5-5" />
                <path d="M21 12H9" />
            </svg>
            <span>Keluar</span>
        </button>
    </form>
</header>
