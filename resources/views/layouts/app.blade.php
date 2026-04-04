<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM360 - Google Contacts Sync')</title>
    <!-- Using Tailwind via CDN for rapid prototyping of a modern, premium look -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#0f172a',
                            surface: '#1e293b',
                            border: '#334155',
                            text: '#f1f5f9'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery & Select2 (Moved to layout for common use) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; transition: background-color 0.3s ease, color 0.3s ease; }
        .dark body { background-color: #0f172a; color: #f1f5f9; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #475569; }
        
        .dark .select2-container--default .select2-selection--single { background-color: #334155; border-color: #475569; height: 38px; display: flex; align-items: center; border-radius: 0.5rem; }
        .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #f1f5f9; line-height: normal; }
        .dark .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; top: 1px; }
        .dark .select2-dropdown { background-color: #1e293b; border-color: #475569; color: #f1f5f9; z-index: 10000; }
        .dark .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #2563eb; color: white; }
        .select2-container--default .select2-selection--single { height: 38px; display: flex; align-items: center; border-radius: 0.5rem; border-color: #cbd5e1; outline: none; }
        .select2-container { width: 100% !important; }
    </style>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    @yield('styles')
</head>
<body class="h-screen overflow-hidden flex flex-col text-slate-800 dark:text-slate-200 dark:bg-slate-900 transition-colors duration-300">

    <!-- Top Navigation -->
    <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm z-10 transition-colors">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-lg">
                            C
                        </div>
                        <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">CRM<span class="text-blue-600">360</span></span>
                    </div>
                    
                    <div class="hidden md:flex items-center gap-1">
                        <a href="{{ route('contacts.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('contacts.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/50' }} transition-all">
                            <i class="fa-solid fa-address-book mr-1.5 opacity-70"></i> Contactos
                        </a>
                        <a href="{{ route('calendar.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('calendar.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/50' }} transition-all">
                            <i class="fa-solid fa-calendar-days mr-1.5 opacity-70"></i> Agenda
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" type="button" class="text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700 rounded-lg text-sm p-2.5 transition-all">
                        <i id="theme-toggle-dark-icon" class="hidden fa-solid fa-moon w-5 h-5 flex items-center justify-center text-lg"></i>
                        <i id="theme-toggle-light-icon" class="hidden fa-solid fa-sun w-5 h-5 flex items-center justify-center text-lg text-yellow-500"></i>
                    </button>

                    @if(session('success'))
                        <div class="hidden sm:inline-flex text-sm text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 font-medium">
                            <i class="fa-solid fa-circle-check mr-1 mt-0.5"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if(!session('google_access_token'))
                        <a href="{{ route('google.login') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <i class="fa-brands fa-google mr-2"></i> Connect
                        </a>
                    @else
                        <div class="flex items-center gap-2">
                            <a href="{{ route('contacts.match') }}" class="hidden lg:inline-flex items-center px-4 py-2 border border-emerald-300 dark:border-emerald-600 text-sm font-medium rounded-md text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-800/50 transition-colors shadow-sm">
                                <i class="fa-solid fa-link mr-2 text-emerald-500"></i> Emparejar
                            </a>
                            <a href="{{ route('contacts.sync') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-600 text-sm font-medium rounded-md text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                <i class="fa-solid fa-rotate mr-2 text-slate-500"></i> Sync
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    <script>
        // Theme Toggle Logic
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
