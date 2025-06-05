<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'CafeBar') - Панель управления</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #F5F5DC;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            width: 200px;
            background-color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            height: 100vh;
            position: fixed;
            top: 0;
            left: -200px;
            transition: left 0.3s ease-in-out;
            z-index: 50;
        }
        .sidebar.open {
            left: 0;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #6B4F4F;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        .sidebar-item i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }
        .sidebar-item:hover {
            background-color: #f0e6e6;
            color: #A52A2A;
        }
        .sidebar-toggle {
            position: absolute;
            top: 16px;
            right: 16px;
            color: #6B4F4F;
            font-size: 20px;
            cursor: pointer;
            display: block;
        }
        .notification {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.75rem 1rem;
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 9998;
            animation: fadeOut 5s forwards;
            border: 2px solid #D4A373;
            width: 90%;
            max-width: 400px;
            text-align: center;
            opacity: 1;
            background-color: #10B981;
        }
        .notification.bg-green-500 {
            background-color: #10B981;
        }
        .notification.bg-red-500 {
            background-color: #EF4444;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
        .btn {
            background-color: #6B4F4F;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            border: 1px solid #D4A373;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #543737;
        }
        .btn-lg {
            padding: 12px 24px;
            font-size: 1.125rem;
        }
        .top-bar {
            background-color: white;
            padding: 12px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #D4A373;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 10000;
            display: block !important;
        }
        .content {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 56px);
            transition: margin-left 0.3s ease-in-out;
            margin-left: 0;
        }
        .content.shifted {
            margin-left: 200px;
        }
        .no-sidebar .content {
            margin-left: 0 !important;
            justify-content: center;
        }
        .menu-toggle {
            position: fixed;
            top: 12px;
            left: 12px;
            color: #6B4F4F;
            font-size: 20px;
            cursor: pointer;
            z-index: 60;
            display: block;
        }
        .sidebar.open ~ .menu-toggle {
            display: none;
        }
        @media (min-width: 640px) {
            .sidebar {
                width: 256px;
                left: 0;
            }
            .sidebar:not(.open) {
                left: -256px;
            }
            .content.shifted {
                margin-left: 256px;
            }
            .sidebar-item {
                font-size: 1rem;
            }
            .menu-toggle {
                display: none;
            }
            .sidebar-toggle {
                display: none;
            }
        }
        @media (max-width: 640px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }
            .sidebar.open {
                left: 0;
            }
            .content {
                margin-left: 0;
            }
            .content.shifted {
                margin-left: 200px;
            }
            .menu-toggle {
                display: block;
            }
            .sidebar.open .sidebar-toggle {
                display: block;
            }
            .top-bar {
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body class="@if (in_array(Route::currentRouteName(), ['home', 'resume.create', 'login'])) no-sidebar @endif">
    <!-- Верхняя панель -->
    @if (in_array(Route::currentRouteName(), ['home', 'resume.create']))
        <div class="top-bar">
            @if (Session::has('access_token'))
                <form id="logout-top-bar" action="{{ route('profile.logout') }}" method="POST" class="float-right">
                    @csrf
                    <button type="submit" class="btn bg-red-500 hover:bg-red-600">Выход</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn float-right">Вход для сотрудников</a>
            @endif
        </div>
    @endif

    <div class="flex h-screen @if (in_array(Route::currentRouteName(), ['home', 'resume.create'])) mt-14 @else mt-0 @endif">
        <!-- Кнопка открытия боковой панели -->
        @if (Session::has('access_token') && in_array(strtolower(Session::get('user_role')), ['waiter', 'cook', 'barkeeper']) && !in_array(Route::currentRouteName(), ['home', 'resume.create', 'login']))
            <div class="menu-toggle" id="menu-toggle" onclick="toggleSidebar(true)">
                <i class="fas fa-bars"></i>
            </div>
        @endif

        <!-- Боковая панель -->
        @if (Session::has('access_token') && in_array(strtolower(Session::get('user_role')), ['waiter', 'cook', 'barkeeper']) && !in_array(Route::currentRouteName(), ['home', 'resume.create', 'login']))
            <div class="sidebar" id="sidebar">
                <div class="sidebar-toggle" onclick="toggleSidebar(false)">
                    <i class="fas fa-times"></i>
                </div>
                <div class="p-4 border-b border-[#D4A373]">
                    <h2 class="text-xl sm:text-2xl font-bold text-[#6B5B4F] text-center">CafeBar</h2>
                </div>
                <nav class="mt-6">
                    <a href="{{ route('menu.index') }}" class="sidebar-item flex items-center py-2 text-gray-700">
                        <i class="fas fa-utensils"></i> <span class="sidebar-text">Меню</span>
                    </a>
                    <a href="{{ route('profile.show') }}" class="sidebar-item flex items-center py-2 text-gray-700">
                        <i class="fas fa-user"></i> <span class="sidebar-text">Профиль</span>
                    </a>
                    @if (strtolower(Session::get('user_role')) === 'waiter')
                        <a href="{{ route('waiter.orders') }}" class="sidebar-item flex items-center py-2 text-gray-700">
                            <i class="fas fa-clipboard-list"></i> <span class="sidebar-text">Заказы</span>
                        </a>
                    @elseif (strtolower(Session::get('user_role')) === 'cook')
                        <a href="{{ route('cook.orders') }}" class="sidebar-item flex items-center py-2 text-gray-700">
                            <i class="fas fa-hamburger"></i> <span class="sidebar-text">Заказы</span>
                        </a>
                    @elseif (strtolower(Session::get('user_role')) === 'barkeeper')
                        <a href="{{ route('barkeeper.orders') }}" class="sidebar-item flex items-center py-2 text-gray-700">
                            <i class="fas fa-glass-martini"></i> <span class="sidebar-text">Заказы</span>
                        </a>
                    @endif
                    <form id="logout-form" action="{{ route('profile.logout') }}" method="POST">
                        @csrf
                        <a href="#" onclick="document.getElementById('logout-form').submit();" class="sidebar-item flex items-center py-2 text-gray-700">
                            <i class="fas fa-sign-out-alt"></i> <span class="sidebar-text">Выход</span>
                        </a>
                    </form>
                </nav>
            </div>
        @endif

        <!-- Основной контент -->
        <div class="content" id="main-content">
            <div class="w-full max-w-7xl p-4 sm:p-6">
                <header class="bg-white shadow-md p-3 sm:p-4 mb-6 rounded-lg border border-[#D4A373]">
                    <h1 class="text-xl sm:text-3xl font-semibold text-[#6B5B4F]">@yield('title', 'CafeBar')</h1>
                </header>
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar(open) {
            if (document.body.classList.contains('no-sidebar')) {
                return;
            }
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('main-content');
            const menuToggle = document.getElementById('menu-toggle');
            if (sidebar && content && menuToggle) {
                sidebar.classList.toggle('open', open);
                content.classList.toggle('shifted', open);
                menuToggle.style.display = open ? 'none' : 'block';
                sessionStorage.setItem('sidebarOpen', open);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Диагностика: проверяем наличие .top-bar
            const topBar = document.querySelector('.top-bar');
            if (['home', 'resume.create'].includes('{{ Route::currentRouteName() }}')) {
                if (topBar) {
                    topBar.style.display = 'block';
                    topBar.style.visibility = 'visible';
                    console.log('Top bar is visible on {{ Route::currentRouteName() }}');
                } else {
                    console.error('Top bar not found on {{ Route::currentRouteName() }}');
                }
            }

            // Логика боковой панели (только для страниц без .no-sidebar)
            if (document.body.classList.contains('no-sidebar')) {
                return;
            }

            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('main-content');
            const menuToggle = document.getElementById('menu-toggle');
            const isSidebarOpen = sessionStorage.getItem('sidebarOpen') === 'true';
            if (sidebar && content && menuToggle) {
                if (window.innerWidth <= 640) {
                    sidebar.classList.toggle('open', isSidebarOpen);
                    content.classList.toggle('shifted', isSidebarOpen);
                    menuToggle.style.display = isSidebarOpen ? 'none' : 'block';
                } else {
                    sidebar.classList.add('open');
                    content.classList.add('shifted');
                    menuToggle.style.display = 'none';
                }
            }
        });

        window.addEventListener('resize', () => {
            if (document.body.classList.contains('no-sidebar')) {
                return;
            }
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('main-content');
            const menuToggle = document.getElementById('menu-toggle');
            if (sidebar && content && menuToggle) {
                if (window.innerWidth <= 640) {
                    const isSidebarOpen = sessionStorage.getItem('sidebarOpen') === 'true';
                    sidebar.classList.toggle('open', isSidebarOpen);
                    content.classList.toggle('shifted', isSidebarOpen);
                    menuToggle.style.display = isSidebarOpen ? 'none' : 'block';
                } else {
                    sidebar.classList.add('open');
                    content.classList.add('shifted');
                    menuToggle.style.display = 'none';
                }
            }
        });

        setTimeout(() => {
            const notification = document.querySelector('.notification');
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>