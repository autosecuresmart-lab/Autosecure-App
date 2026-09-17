@extends('manage.layouts.app')

@section('title', 'Users Management')

@section('content')
<div class="space-y-6">
    <!-- Success & Error Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white grid place-items-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-start gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-red-500 text-white grid place-items-center shrink-0 mt-0.5">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="text-sm flex-1">
                <div class="font-bold mb-1">Please fix the following errors:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Page Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Users Management</span>
                <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-600 font-bold">
                    {{ $stats['total_users'] }} total
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage customer accounts, vehicles, hardware devices, and security access</p>
        </div>
        
        <div class="flex items-center gap-3">
            <button onclick="openCreateUserModal()" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Create New User</span>
            </button>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Card 1: Total Users -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Users</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 grid place-items-center">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 tracking-tight leading-none mb-1">{{ number_format($stats['total_users']) }}</div>
            <div class="text-xs text-slate-500 font-medium">Registered customer base</div>
        </div>

        <!-- Card 2: Active Accounts -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Status</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 grid place-items-center">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-emerald-600 tracking-tight leading-none mb-1">{{ number_format($stats['active_users']) }}</div>
            <div class="text-xs text-slate-500 font-medium">Active & verified accounts</div>
        </div>

        <!-- Card 3: Business Accounts -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Business Accounts</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 grid place-items-center">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 tracking-tight leading-none mb-1">{{ number_format($stats['business_users']) }}</div>
            <div class="text-xs text-slate-500 font-medium">Commercial & Fleet Accounts</div>
        </div>

        <!-- Card 4: Linked Assets & Hardware -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Vehicles & Devices</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 grid place-items-center">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 tracking-tight leading-none mb-1">
                {{ $stats['total_vehicles'] }} <span class="text-base text-slate-400 font-normal">/ {{ $stats['total_devices'] }} dev</span>
            </div>
            <div class="text-xs text-slate-500 font-medium">Registered vehicles & active devices</div>
        </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('manage.users.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <!-- Keyword Search -->
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $filters['search'] }}" 
                    placeholder="Search by name, email, phone, company, or UUID..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                >
            </div>

            <!-- Status Filter -->
            <div class="w-full sm:w-44">
                <select name="status" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ $filters['status'] === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <!-- Account Type Filter -->
            <div class="w-full sm:w-44">
                <select name="account_type" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
                    <option value="">All Account Types</option>
                    <option value="individual" {{ $filters['account_type'] === 'individual' ? 'selected' : '' }}>Individual</option>
                    <option value="business" {{ $filters['account_type'] === 'business' ? 'selected' : '' }}>Business / Fleet</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-none px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs sm:text-sm font-semibold transition">
                    Filter
                </button>
                @if($filters['search'] || $filters['status'] || $filters['account_type'])
                    <a href="{{ route('manage.users.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs sm:text-sm font-medium transition" title="Clear Filters">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-4 sm:px-6">User / Customer</th>
                        <th class="py-3.5 px-4">Account Type</th>
                        <th class="py-3.5 px-4">Contact</th>
                        <th class="py-3.5 px-4 text-center">Vehicles & Devices</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Joined Date</th>
                        <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <!-- User Name & Avatar -->
                            <td class="py-4 px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 text-white font-extrabold text-sm grid place-items-center shrink-0 shadow-sm">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('manage.users.show', $user) }}" class="font-bold text-slate-900 hover:text-brand-500 transition truncate block">
                                            {{ $user->name }}
                                        </a>
                                        <div class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5">
                                            <span>{{ $user->email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Account Type -->
                            <td class="py-4 px-4">
                                @if($user->account_type === 'business')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-[11px]">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                        <span>Business</span>
                                    </span>
                                    @if($user->company_name)
                                        <div class="text-[11px] text-slate-400 truncate mt-0.5 max-w-[120px]">{{ $user->company_name }}</div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-[11px]">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <span>Individual</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Phone -->
                            <td class="py-4 px-4">
                                <div class="text-xs font-medium text-slate-700">{{ $user->phone ?? '—' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $user->timezone ?? 'Africa/Lagos' }}</div>
                            </td>

                            <!-- Vehicles & Devices counts -->
                            <td class="py-4 px-4 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-xs" title="Vehicles">
                                        🚗 {{ $user->vehicles_count }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-bold text-xs" title="Bound Devices">
                                        📡 {{ $user->devices_count }}
                                    </span>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4">
                                @if($user->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active
                                    </span>
                                @elseif($user->status === 'suspended')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Suspended
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Pending
                                    </span>
                                @endif
                            </td>

                            <!-- Joined Date -->
                            <td class="py-4 px-4 text-xs text-slate-500 whitespace-nowrap">
                                <div>{{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $user->created_at ? $user->created_at->diffForHumans() : '' }}</div>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('manage.users.show', $user) }}" class="p-2 rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-600 text-slate-700 font-medium transition shadow-sm" title="View Full Details">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>

                                    <form method="POST" action="{{ route('manage.users.status', $user) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="{{ $user->status === 'active' ? 'Suspend User' : 'Activate User' }}">
                                            @if($user->status === 'active')
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                </svg>
                                            @else
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 grid place-items-center mx-auto">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="8" y1="12" x2="16" y2="12"></line>
                                        </svg>
                                    </div>
                                    <div class="text-sm font-bold text-slate-800">No users found</div>
                                    <p class="text-xs text-slate-500">Try adjusting your filters or search keywords, or register a new customer user.</p>
                                    <button onclick="openCreateUserModal()" class="mt-2 inline-flex items-center gap-2 bg-brand-500 text-white px-3.5 py-2 rounded-xl text-xs font-bold hover:bg-brand-600 transition">
                                        + Create New User
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- CREATE USER MODAL                          -->
<!-- ========================================== -->
<div id="createUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeCreateUserModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 sm:p-6 text-center">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-2xl border border-slate-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-brand-500 text-white grid place-items-center shadow-sm">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900" id="modal-title">Create New Customer Account</h3>
                        <p class="text-xs text-slate-500">Register user details and optionally bind their first vehicle</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateUserModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form method="POST" action="{{ route('manage.users.store') }}" class="p-6 space-y-4">
                @csrf

                <!-- Basic Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. John Doe" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required placeholder="e.g. john@example.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" placeholder="e.g. +234 801 234 5678" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    </div>

                    <!-- Account Type -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Account Type <span class="text-red-500">*</span></label>
                        <select name="account_type" id="modalAccountType" onchange="toggleCompanyInput(this.value)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                            <option value="individual">Individual</option>
                            <option value="business">Business / Commercial Fleet</option>
                        </select>
                    </div>

                    <!-- Company Name (Conditional) -->
                    <div id="modalCompanyField" class="hidden sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Company / Organization Name</label>
                        <input type="text" name="company_name" placeholder="e.g. Apex Logistics Ltd" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Initial Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Account Status <span class="text-red-500">*</span></label>
                        <select name="status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                            <option value="active" selected>Active</option>
                            <option value="pending">Pending Verification</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <!-- Optional Initial Vehicle Section Toggle -->
                <div class="pt-3 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="attach_vehicle" value="1" id="attachVehicleCheckbox" onchange="toggleVehicleInputs(this.checked)" class="w-4 h-4 rounded text-brand-500 focus:ring-brand-500 border-slate-300">
                        <span class="text-xs font-bold text-slate-700">Assign first vehicle to this customer immediately</span>
                    </label>

                    <div id="vehicleInputsContainer" class="hidden mt-3 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Vehicle Specification</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Make</label>
                                <input type="text" name="vehicle_make" placeholder="e.g. Toyota" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Model</label>
                                <input type="text" name="vehicle_model" placeholder="e.g. Camry" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Year</label>
                                <input type="number" name="vehicle_year" value="2024" min="1950" max="2050" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">License Plate Number</label>
                                <input type="text" name="vehicle_plate" placeholder="e.g. LAG-884-XY" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs uppercase">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions Footer -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateUserModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs sm:text-sm font-bold shadow-sm shadow-brand-500/20 transition hover:scale-[1.02] active:scale-[0.98]">
                        Create Customer Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateUserModal() {
        document.getElementById('createUserModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function toggleCompanyInput(type) {
        const companyField = document.getElementById('modalCompanyField');
        if (type === 'business') {
            companyField.classList.remove('hidden');
        } else {
            companyField.classList.add('hidden');
        }
    }

    function toggleVehicleInputs(checked) {
        const container = document.getElementById('vehicleInputsContainer');
        if (checked) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeCreateUserModal();
        }
    });
</script>
@endsection
