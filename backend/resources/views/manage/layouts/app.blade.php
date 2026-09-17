<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · autoSecure Management</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#FFF7ED',
                            100: '#FFEDD5',
                            500: '#F97316',
                            600: '#EA580C',
                            700: '#C2410C',
                        },
                        navy: {
                            800: '#1E293B',
                            900: '#0F172A',
                            950: '#0B1222',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-900 min-h-screen">
<div class="flex min-h-screen">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm hidden lg:hidden transition-opacity"></div>

    <!-- Sidebar Navigation -->
    <aside id="mainSidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-[#111827] flex flex-col justify-between p-5 border-r border-slate-800 transition-transform duration-200 -translate-x-full lg:translate-x-0 lg:static lg:flex-shrink-0">
        <div>
            <!-- Brand Logo & Mobile Close Button -->
            <div class="flex items-center justify-between px-2 pb-6">
                <a href="{{ route('manage.dashboard') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white grid place-items-center shadow-md">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L3 6V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V6L12 2Z" fill="#111827" />
                            <path d="M9 11.5L11 13.5L15 9.5" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="text-xl font-extrabold tracking-tight text-white">
                        auto<span class="text-brand-500 italic">Secure</span>
                    </div>
                </a>

                <button onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            @php
                $currentModule = request()->route('module') ?? request()->segment(2);
                $isDashboard = request()->routeIs('manage.dashboard') || empty($currentModule);
            @endphp

            <!-- Nav Links List -->
            <nav class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('manage.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $isDashboard ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $isDashboard ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                @php
                    $isUsers = request()->routeIs('manage.users.*') || $currentModule === 'users' || $currentModule === 'customers';
                @endphp

                <!-- Users -->
                <a href="{{ route('manage.users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $isUsers ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $isUsers ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <span>Users</span>
                </a>

                <!-- Incidents -->
                <a href="{{ route('manage.incidents.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'incidents' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'incidents' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </span>
                    <span>Incidents</span>
                </a>

                <!-- Live Map -->
                <a href="{{ route('manage.live-map.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'live-map' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'live-map' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                    </span>
                    <span>Live Map</span>
                </a>

                @php
                    $isDevices = request()->routeIs('manage.devices.*') || $currentModule === 'devices';
                @endphp

                <!-- Devices -->
                <a href="{{ route('manage.devices.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $isDevices ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $isDevices ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                            <rect x="9" y="9" width="6" height="6"></rect>
                            <line x1="9" y1="1" x2="9" y2="4"></line>
                            <line x1="15" y1="1" x2="15" y2="4"></line>
                            <line x1="9" y1="20" x2="9" y2="23"></line>
                            <line x1="15" y1="20" x2="15" y2="23"></line>
                            <line x1="20" y1="9" x2="23" y2="9"></line>
                            <line x1="20" y1="14" x2="23" y2="14"></line>
                            <line x1="1" y1="9" x2="4" y2="9"></line>
                            <line x1="1" y1="14" x2="4" y2="14"></line>
                        </svg>
                    </span>
                    <span>Devices</span>
                </a>

                <!-- Assignments -->
                <a href="{{ route('manage.assignments.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'assignments' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'assignments' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </span>
                    <span>Assignments</span>
                </a>

                <!-- Alerts -->
                <a href="{{ route('manage.alerts.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'alerts' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'alerts' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </span>
                    <span>Alerts</span>
                </a>

                <!-- Reports -->
                <a href="{{ route('manage.reports.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'reports' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'reports' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                            <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                        </svg>
                    </span>
                    <span>Reports</span>
                </a>

                <!-- SLA Monitoring -->
                <a href="{{ route('manage.sla-monitoring.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'sla-monitoring' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'sla-monitoring' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </span>
                    <span>SLA Monitoring</span>
                </a>

                <!-- Audit Logs -->
                <a href="{{ route('manage.audit-logs.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'audit-logs' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'audit-logs' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </span>
                    <span>Audit Logs</span>
                </a>

                <!-- Users & Roles -->
                <a href="{{ route('manage.users-roles.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'users-roles' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'users-roles' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <span>Users & Roles</span>
                </a>

                <!-- Settings -->
                <a href="{{ route('manage.settings.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ $currentModule === 'settings' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-100 hover:bg-[#1E293B]/60' }}">
                    <span class="{{ $currentModule === 'settings' ? 'text-sky-400' : 'text-slate-400' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </span>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer User Card -->
        <div class="mt-6 p-3 rounded-xl bg-[#1E293B] border border-white/5 flex items-center justify-between gap-2.5">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-slate-700 grid place-items-center text-white font-bold text-xs shrink-0">
                    SA
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold text-white truncate">
                        {{ auth('admin')->user()?->name ?? 'Super Admin' }}
                    </div>
                    <div class="text-[11px] text-slate-400 truncate">
                        {{ auth('admin')->user()?->isSuperAdmin() ? 'Operations Lead' : (auth('admin')->user()?->roles->pluck('name')->first() ?? 'Staff') }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('manage.logout') }}">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition" title="Sign out">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area with Mobile Top Bar -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Mobile Top Navbar -->
        <header class="lg:hidden flex items-center justify-between px-4 py-3 bg-[#111827] text-white sticky top-0 z-30 border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <button onclick="toggleSidebar()" class="p-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div class="font-extrabold tracking-tight text-lg">
                    auto<span class="text-brand-500 italic">Secure</span>
                </div>
            </div>

            <div class="w-8 h-8 rounded-lg bg-slate-800 grid place-items-center text-xs font-bold text-slate-200">
                SA
            </div>
        </header>

        <!-- Main Inner Content -->
        <main class="flex-1 min-w-0 bg-[#F8FAFC] p-4 overflow-y-auto">
            @yield('content')
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }
    }

    // Universal Form Action Buttons Disabler & Loading Animation
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
                if (submitBtn) {
                    if (submitBtn.dataset.submitting === 'true') {
                        e.preventDefault();
                        return false;
                    }
                    submitBtn.dataset.submitting = 'true';
                    
                    // Lock dimensions to prevent UI shift
                    const currentWidth = submitBtn.offsetWidth;
                    const currentHeight = submitBtn.offsetHeight;
                    if (currentWidth && currentHeight) {
                        submitBtn.style.minWidth = currentWidth + 'px';
                        submitBtn.style.minHeight = currentHeight + 'px';
                    }

                    const isIconOnly = submitBtn.getAttribute('title') && submitBtn.children.length === 1 && submitBtn.querySelector('svg');
                    const loadingText = submitBtn.dataset.loadingText || (isIconOnly ? '' : 'Processing...');
                    
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none', 'select-none');
                    
                    if (isIconOnly) {
                        submitBtn.innerHTML = `
                            <svg class="animate-spin h-4 w-4 text-current inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        `;
                    } else {
                        submitBtn.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current inline-block shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>${loadingText}</span>
                        `;
                    }
                }
            });
        });
    });

    // Helper for non-form action buttons that trigger async actions
    function setButtonLoading(btn, text = 'Processing...') {
        if (!btn || btn.dataset.submitting === 'true') return false;
        btn.dataset.submitting = 'true';
        btn.style.minWidth = btn.offsetWidth + 'px';
        btn.style.minHeight = btn.offsetHeight + 'px';
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none', 'select-none');
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current inline-block shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>${text}</span>
        `;
        return true;
    }
</script>
</body>
</html>
