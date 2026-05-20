<!DOCTYPE html>
<html lang="en">
  <head>
    <title>TRUMARK CRM - @yield('title')</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('vali/css/main.css') }}">
    <style>
        body, .app-menu__item, .app-title, .tile-title, h1, h2, h3, h4, h5, h6 {
            font-family: 'Century Gothic', 'Segoe UI', sans-serif !important;
        }
        :root {
            --primary-color: #940000;
        }
        .app-header {
            background-color: #940000 !important;
        }
        .app-header__logo {
            background-color: #940000 !important;
            font-family: 'Century Gothic', sans-serif !important;
            font-weight: 900 !important;
            font-size: 24px !important;
            text-transform: uppercase;
        }
        .app-sidebar {
            background-color: #222d32 !important;
        }
        .app-sidebar__user {
            background-color: transparent !important;
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }
        .app-sidebar__user-avatar-initials {
            width: 60px;
            height: 60px;
            background-color: #940000;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
        }
        .app-sidebar__user-name {
            font-size: 16px !important;
            font-weight: 600 !important;
            margin-bottom: 0;
            color: #fff;
        }
        .app-sidebar__user-designation {
            font-size: 13px !important;
            color: #00d4ff !important; /* Cyan like in image */
        }
        .app-menu__item {
            border-left: 3px solid transparent;
            transition: all 0.3s;
            color: #fff !important;
        }
        .app-menu__item.active, .app-menu__item:hover {
            background: #1a2226 !important;
            border-left-color: #940000 !important;
            color: #fff !important;
        }
        .treeview-menu {
            background: #2c3b41 !important;
        }
        .treeview-item {
            color: #fff !important;
            font-size: 13px !important;
            padding: 8px 15px 8px 45px !important;
            display: flex;
            align-items: center;
            opacity: 0.85;
            transition: all 0.3s;
        }
        .treeview-item:hover, .treeview-item.active {
            background: rgba(255,255,255,0.08) !important;
            text-decoration: none !important;
            opacity: 1;
            padding-left: 50px !important; /* Slight shift on hover */
        }
        .treeview-item i {
            margin-right: 10px;
            font-size: 14px;
            width: 15px;
            text-align: center;
        }
        .treeview.is-expanded [data-toggle='treeview'] {
            background: #1a2226 !important;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .app-search__input {
            background-color: rgba(255,255,255,0.1);
            border-color: transparent;
            color: #fff;
        }
        .app-sidebar__toggle {
            color: #fff !important;
            line-height: 50px;
        }
        .app-sidebar__toggle:hover {
            background-color: rgba(0, 0, 0, 0.1) !important;
            text-decoration: none;
        }
        .app-nav__item {
            color: #fff !important;
        }
        .app-menu__item.active {
            border-left: 5px solid #940000 !important;
            background: #1a2226 !important;
        }
        .btn-primary, .bg-primary, .badge-primary { background-color: #940000 !important; border-color: #940000 !important; }
        .text-primary { color: #940000 !important; }
        .app-title h1 i { color: #940000; }
        .widget-small.primary.coloured-icon .icon { background-color: #940000 !important; }

        /* ── TABLET: 768px – 1024px ─────────────────────────────────── */
        @media (min-width: 768px) and (max-width: 1024px) {
            .app-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px !important;
            }
            .app-sidebar {
                transform: translateX(-230px);
                transition: transform 0.3s ease;
                position: fixed;
                z-index: 1050;
                height: 100%;
            }
            body.sidebar-open .app-sidebar {
                transform: translateX(0);
            }
            .app-header__logo { width: auto; padding: 0 15px; }
            .app-search { display: none !important; }
            .widget-small { margin-bottom: 15px; }
            .tile { margin-bottom: 15px; }
            [class*="col-lg-3"] { flex: 0 0 50%; max-width: 50%; }
        }

        /* ── MOBILE: ≤767px ─────────────────────────────────────────── */
        @media (max-width: 767px) {
            /* Prevent ANY horizontal overflow on the page */
            html, body {
                overflow-x: hidden !important;
                max-width: 100vw !important;
            }
            /* Clamp all rows and containers to viewport */
            .container, .container-fluid,
            .row { max-width: 100% !important; margin-left: 0 !important; margin-right: 0 !important; }
            * { box-sizing: border-box; }
            /* Header */
            .app-header {
                padding: 0 8px !important;
                height: 52px !important;
            }
            .app-header__logo {
                font-size: 17px !important;
                letter-spacing: 2px !important;
                width: auto !important;
                padding: 0 10px !important;
            }
            .app-search { display: none !important; }
            .app-nav {
                display: flex !important;
                align-items: center !important;
                height: 52px !important;
            }
            .app-nav__item {
                padding: 0 10px !important;
                display: flex !important;
                align-items: center !important;
                height: 52px !important;
                line-height: 52px !important;
            }
            .app-nav__item i { vertical-align: middle; }

            /* Sidebar: slide in from left, hidden by default */
            .app-sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100% !important;
                z-index: 1060 !important;
                transform: translateX(-230px) !important;
                transition: transform 0.3s ease !important;
                width: 230px !important;
            }
            body.sidebar-open .app-sidebar {
                transform: translateX(0) !important;
            }
            .app-sidebar__overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                z-index: 1050;
            }
            body.sidebar-open .app-sidebar__overlay {
                display: block !important;
            }

            /* Main content: full width */
            .app-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 10px 8px !important;
                margin-top: 52px !important;
            }

            /* Page title */
            .app-title {
                flex-direction: column !important;
                align-items: flex-start !important;
                padding: 10px 12px !important;
                gap: 6px;
            }
            .app-title h1 { font-size: 15px !important; margin-bottom: 0; }
            .app-title p  { font-size: 12px !important; margin-bottom: 0; }
            .app-breadcrumb { display: none !important; }

            /* Tiles & Widgets */
            .tile { padding: 12px !important; margin-bottom: 10px !important; border-radius: 8px; }
            .tile-title { font-size: 13px !important; }
            .widget-small {
                margin-bottom: 10px !important;
                padding: 12px !important;
            }
            .widget-small .info h4 { font-size: 13px !important; }
            .widget-small .info p  { font-size: 18px !important; }
            .widget-small .icon    { width: 50px !important; }

            /* Grid: stack all columns on mobile */
            [class*="col-md-"], [class*="col-lg-"], [class*="col-sm-"] {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }

            /* Tables: horizontal scroll */
            .table-responsive { overflow-x: auto !important; -webkit-overflow-scrolling: touch; }
            .table th, .table td { white-space: nowrap; font-size: 12px !important; padding: 6px 8px !important; }

            /* Filter / action bars */
            .d-flex.justify-content-between,
            .d-flex.align-items-center.justify-content-between {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
            }
            .d-flex.align-items-center { flex-wrap: wrap; gap: 6px; }

            /* Branch/filter dropdowns */
            select.form-control, .select2-container { width: 100% !important; max-width: 100% !important; }

            /* Buttons */
            .btn { font-size: 12px !important; padding: 6px 10px !important; }
            .btn-group .btn { font-size: 11px !important; }

            /* Charts */
            div[id$="Chart"] { min-height: 200px !important; }

            /* Badges & filter buttons */
            .badge { font-size: 11px !important; }

            /* Pagination */
            .pagination { flex-wrap: wrap; gap: 2px; }
            .page-link  { padding: 5px 9px !important; font-size: 12px !important; }

            /* DataTables controls */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none !important;
                text-align: left !important;
                margin-bottom: 8px !important;
            }
            .dataTables_wrapper .dataTables_length select {
                width: auto;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
            }

            /* Cards inside stats pages */
            .card, .stat-card { margin-bottom: 10px !important; }

            /* Notification dropdown */
            .app-notification.dropdown-menu { width: 92vw !important; right: -40px !important; }
        }
    </style>
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    @yield('styles')
  </head>
  <body class="app sidebar-mini rtl">
    <!-- Navbar-->
    <header class="app-header"><a class="app-header__logo" href="{{ route('dashboard') }}">TRUMARK</a>
      <!-- Sidebar toggle button--><a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a>
      <!-- Navbar Right Menu-->
      <ul class="app-nav">
        <li class="app-search">
          <input class="app-search__input" type="search" placeholder="Search">
          <button class="app-search__button"><i class="fa fa-search"></i></button>
        </li>
        <!--Notification Menu-->
        @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
        <li class="dropdown">
            <a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Show notifications">
                <i class="fa fa-bell-o fa-lg"></i>
                @if($unreadCount > 0)
                    <span class="badge badge-danger" style="position: absolute; top: 5px; right: 5px; border-radius: 50%; padding: 3px 6px; font-size: 10px;">{{ $unreadCount }}</span>
                @endif
            </a>
            <ul class="app-notification dropdown-menu dropdown-menu-right">
                <li class="app-notification__title">
                    {{ $unreadCount > 0 ? "You have {$unreadCount} new notifications." : "You have no new notifications." }}
                </li>
                <div class="app-notification__content" style="max-height: 400px; overflow-y: auto;">
                    @forelse(auth()->user()->unreadNotifications as $notification)
                        <li>
                            <a class="app-notification__item" href="{{ $notification->data['link'] ?? '#' }}">
                                <span class="app-notification__icon">
                                    <span class="fa-stack fa-lg">
                                        <i class="fa fa-circle fa-stack-2x {{ $notification->data['color'] ?? 'text-primary' }}"></i>
                                        <i class="fa {{ $notification->data['icon'] ?? 'fa-info' }} fa-stack-1x fa-inverse"></i>
                                    </span>
                                </span>
                                <div>
                                    <p class="app-notification__message">{{ $notification->data['title'] ?? 'System Notification' }}</p>
                                    <p class="app-notification__meta small text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                                    <p class="mb-0 x-small text-dark">{{ substr($notification->data['message'] ?? 'New activity recorded.', 0, 80) }}...</p>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="text-center py-3 text-muted">
                            <i class="fa fa-check-circle-o fa-3x d-block mb-2"></i>
                            All caught up!
                        </li>
                    @endforelse
                </div>
                <li class="app-notification__footer">
                    <div class="d-flex justify-content-between p-2">
                        @if($unreadCount > 0)
                        <form action="{{ route('notifications.read_all') }}" method="POST" class="flex-grow-1 mr-1">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm btn-block text-muted small">Mark all as read</button>
                        </form>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm btn-block flex-grow-1">View All</a>
                    </div>
                </li>
            </ul>
        </li>
        <!-- User Menu-->
        <li class="dropdown"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu"><i class="fa fa-user fa-lg"></i></a>
          <ul class="dropdown-menu settings-menu dropdown-menu-right">
            <li><a class="dropdown-item" href="{{ route('profile.index') }}"><i class="fa fa-user fa-lg"></i> Profile</a></li>
            <li>
                <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa fa-sign-out fa-lg"></i> Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>
          </ul>
        </li>
      </ul>
    </header>
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <aside class="app-sidebar">
      <div class="app-sidebar__user">
        @if(auth()->user()->avatar)
            <img class="app-sidebar__user-avatar" src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="User Image" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
        @else
            @php
                $initials = '';
                $names = explode(' ', auth()->user()->name);
                foreach ($names as $n) {
                    $initials .= strtoupper(substr($n, 0, 1));
                }
                $initials = substr($initials, 0, 2);
            @endphp
            <div class="app-sidebar__user-avatar-initials">{{ $initials }}</div>
        @endif
        <div>
          @php
              $nameParts = explode(' ', auth()->user()->name);
              $displayName = count($nameParts) > 1 ? $nameParts[0] . ' ' . end($nameParts) : auth()->user()->name;
          @endphp
          <p class="app-sidebar__user-name">{{ strtoupper($displayName) }}</p>
          <p class="app-sidebar__user-designation">{{ ucfirst(auth()->user()->role) }}</p>
        </div>
      </div>
      <ul class="app-menu">
        <li><a class="app-menu__item {{ Route::is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="app-menu__icon fa fa-dashboard"></i><span class="app-menu__label">Dashboard</span></a></li>
        
        <li><a class="app-menu__item {{ Route::is('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="app-menu__icon fa fa-bell"></i><span class="app-menu__label">Notifications</span></a></li>
        
        <li class="treeview {{ Route::is('customers.*') ? 'is-expanded' : '' }}">
            <a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-users"></i><span class="app-menu__label">CRM Database</span><i class="treeview-indicator fa fa-angle-right"></i></a>
            <ul class="treeview-menu">
                <li><a class="treeview-item {{ Route::is('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="fa fa-users"></i> All Leads</a></li>
                <li><a class="treeview-item {{ Route::is('customers.import') ? 'active' : '' }}" href="{{ route('customers.import') }}"><i class="fa fa-cloud-upload"></i> Bulk Import Leads</a></li>
                <li><a class="treeview-item {{ Route::is('customers.create') ? 'active' : '' }}" href="{{ route('customers.create') }}"><i class="fa fa-user-plus"></i> Add New Lead</a></li>
                <li><a class="treeview-item {{ Route::is('customers.follow_ups') ? 'active' : '' }}" href="{{ route('customers.follow_ups') }}"><i class="fa fa-calendar-check-o"></i> Follow-ups</a></li>
                @if(auth()->user()->role === 'super_admin' || auth()->user()->role === 'manager')
                    <li><a class="treeview-item {{ Route::is('customers.bulk_delegate') ? 'active' : '' }}" href="{{ route('customers.bulk_delegate') }}"><i class="fa fa-exchange"></i> Quick Delegation</a></li>
                @endif
            </ul>
        </li>

        <li class="treeview {{ Route::is('reports.*') ? 'is-expanded' : '' }}">
            <a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-bar-chart"></i><span class="app-menu__label">Reports & Stats</span><i class="treeview-indicator fa fa-angle-right"></i></a>
            <ul class="treeview-menu">
                <li><a class="treeview-item {{ Route::is('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="fa fa-line-chart"></i> Performance Overview</a></li>
                <li>
                    <a class="treeview-item {{ Route::is('reports.statistics') ? 'active' : '' }}" href="{{ route('reports.statistics') }}">
                        <i class="fa fa-pie-chart"></i> {{ auth()->user()->role === 'sales_officer' ? 'My Market Stats' : 'Market Demographics' }}
                    </a>
                </li>
                <li>
                    <a class="treeview-item {{ Route::is('reports.surveys') ? 'active' : '' }}" href="{{ route('reports.surveys') }}">
                        <i class="fa fa-commenting-o"></i> Customer Surveys
                    </a>
                </li>
            </ul>
        </li>

        <li class="treeview {{ Route::is('kpi.*') ? 'is-expanded' : '' }}">
            <a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-trophy"></i><span class="app-menu__label">Performance & KPIs</span><i class="treeview-indicator fa fa-angle-right"></i></a>
            <ul class="treeview-menu">
                <li>
                    <a class="treeview-item {{ Route::is('kpi.leaderboard') ? 'active' : '' }}" href="{{ route('kpi.leaderboard') }}">
                        <i class="fa fa-{{ auth()->user()->role === 'sales_officer' ? 'user' : 'list-ol' }}"></i> {{ auth()->user()->role === 'sales_officer' ? 'My KPI Status' : 'Leaderboard' }}
                    </a>
                </li>
                <li><a class="treeview-item {{ Route::is('kpi.guide') ? 'active' : '' }}" href="{{ route('kpi.guide') }}"><i class="fa fa-info-circle"></i> Points Guide</a></li>
                @if(auth()->user()->role !== 'sales_officer')
                    <li><a class="treeview-item {{ Route::is('kpi.comparison') ? 'active' : '' }}" href="{{ route('kpi.comparison') }}"><i class="fa fa-balance-scale"></i> Branch Comparison</a></li>
                @endif
                <li><a class="treeview-item {{ Route::is('kpi.activities') ? 'active' : '' }}" href="{{ route('kpi.activities') }}"><i class="fa fa-history"></i> Point History</a></li>
                @if(auth()->user()->role !== 'sales_officer')
                    <li><a class="treeview-item {{ Route::is('kpi.attendance') ? 'active' : '' }}" href="{{ route('kpi.attendance') }}"><i class="fa fa-clock-o"></i> Attendance Log</a></li>
                @endif
            </ul>
        </li>
        
        <li><a class="app-menu__item {{ Route::is('customers.sms_reminders') ? 'active' : '' }}" href="{{ route('customers.sms_reminders') }}"><i class="app-menu__icon fa fa-envelope"></i><span class="app-menu__label">SMS Reminders</span></a></li>

        @if(auth()->user()->role === 'super_admin')
        <li class="treeview {{ Route::is('staff.*') || Route::is('branches.*') || Route::is('settings.index') || Route::is('audit_logs.index') ? 'is-expanded' : '' }}">
            <a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-lock"></i><span class="app-menu__label">Administration</span><i class="treeview-indicator fa fa-angle-right"></i></a>
            <ul class="treeview-menu">
                <li><a class="treeview-item {{ Route::is('staff.index') ? 'active' : '' }}" href="{{ route('staff.index') }}"><i class="fa fa-vcard-o"></i> Manage Staff</a></li>
                <li><a class="treeview-item {{ Route::is('branches.index') ? 'active' : '' }}" href="{{ route('branches.index') }}"><i class="fa fa-map-marker"></i> Manage Branches</a></li>
                <li><a class="treeview-item {{ Route::is('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}"><i class="fa fa-sliders"></i> System Settings</a></li>
                <li><a class="treeview-item {{ Route::is('audit_logs.index') ? 'active' : '' }}" href="{{ route('audit_logs.index') }}"><i class="fa fa-shield"></i> Security Audit Logs</a></li>
            </ul>
        </li>
        @endif
      </ul>
    </aside>
    <main class="app-content">
      <div class="app-title">
        <div>
          <h1><i class="fa @yield('page_icon')"></i> @yield('title')</h1>
          <p>@yield('subtitle')</p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
          <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
          @if(!Route::is('dashboard'))
            <li class="breadcrumb-item active">@yield('title')</li>
          @endif
        </ul>
      </div>
      
    @yield('content')
    </main>
    <!-- Essential javascripts for application to work-->
    <script src="{{ asset('vali/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali/js/main.js') }}"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="{{ asset('vali/js/plugins/pace.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        @if(session('success'))
            Toast.fire({
                icon: 'success',
                title: "{{ session('success') }}"
            });
        @endif

        @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: "{{ session('error') }}"
            });
        @endif
    </script>
    <script>
        // Mobile/Tablet: sidebar open/close via hamburger & overlay
        (function() {
            var toggleBtn = document.querySelector('[data-toggle="sidebar"]');
            var overlay   = document.querySelector('.app-sidebar__overlay');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    if (window.innerWidth <= 1024) {
                        e.preventDefault();
                        document.body.classList.toggle('sidebar-open');
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    document.body.classList.remove('sidebar-open');
                });
            }

            // Close sidebar on any menu item click (mobile)
            document.querySelectorAll('.app-menu__item:not([data-toggle="treeview"]), .treeview-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        document.body.classList.remove('sidebar-open');
                    }
                });
            });
        })();
    </script>
    @yield('scripts')
  </body>
</html>
