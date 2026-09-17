@extends('manage.layouts.auth')

@section('title', 'Staff Sign In')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen w-full">
    <!-- Left Column (Col 6): Dark Brand Showcase -->
    <div class="relative hidden lg:flex flex-col justify-between p-12 xl:p-16 bg-gradient-to-br from-[#0B132B] via-[#0F172A] to-[#162038] text-white overflow-hidden">
        <!-- Ambient Glowing Lights -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-orange-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Brand Header -->
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white grid place-items-center shadow-lg shadow-black/30">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L3 6V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V6L12 2Z" fill="#111827" />
                    <path d="M9 11.5L11 13.5L15 9.5" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div class="text-2xl font-extrabold tracking-tight text-white">
                auto<span class="text-brand-500 italic">Secure</span>
            </div>
        </div>

        <!-- Center Hero Text & Features -->
        <div class="relative z-10 my-auto max-w-lg">
            <!-- Operations Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-sky-400 mb-6 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-sky-400 shadow-[0_0_8px_#38bdf8]"></span>
                <span>Fleet Monitoring & Operations Portal · v2.0</span>
            </div>

            <h1 class="text-3xl xl:text-4xl font-extrabold leading-tight tracking-tight text-white mb-4">
                Fleet Monitoring & Active Security Operations
            </h1>
            <p class="text-sm xl:text-base leading-relaxed text-slate-400 mb-8">
                High-precision GPS telemetry, automated remote immobilisation, and 24/7 emergency recovery dispatch for enterprise fleets.
            </p>

            <div class="space-y-3.5">
                <!-- Feature 1 -->
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/[0.04] border border-white/[0.08] backdrop-blur-sm">
                    <div class="w-9 h-9 rounded-lg bg-blue-500/15 border border-blue-500/30 grid place-items-center text-blue-400 shrink-0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-100 mb-0.5">Active Theft Mitigation</div>
                        <div class="text-xs text-slate-400 leading-snug">Instant unauthorized movement alarms and one-tap engine cut-off commands.</div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/[0.04] border border-white/[0.08] backdrop-blur-sm">
                    <div class="w-9 h-9 rounded-lg bg-blue-500/15 border border-blue-500/30 grid place-items-center text-blue-400 shrink-0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-100 mb-0.5">Live GPS Telemetry</div>
                        <div class="text-xs text-slate-400 leading-snug">Sub-second telemetry refresh with route breadcrumbs and geofence alarms.</div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/[0.04] border border-white/[0.08] backdrop-blur-sm">
                    <div class="w-9 h-9 rounded-lg bg-blue-500/15 border border-blue-500/30 grid place-items-center text-blue-400 shrink-0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-100 mb-0.5">Rapid Recovery Dispatch</div>
                        <div class="text-xs text-slate-400 leading-snug">Real-time incident response routing with under 20-minute average resolution.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Stats -->
        <div class="relative z-10 flex items-center justify-between text-xs text-slate-500 border-t border-white/10 pt-6">
            <span>© 2026 autoSecure Ltd.</span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Security Systems Operational</span>
            </span>
        </div>
    </div>

    <!-- Right Column (Col 6): Clean White Auth Form -->
    <div class="flex items-center justify-center p-6 sm:p-10 lg:p-14 bg-white">
        <div class="w-full max-w-md">
            <!-- Mobile Brand Logo -->
            <div class="lg:hidden flex items-center gap-2.5 mb-8">
                <div class="w-9 h-9 rounded-xl bg-slate-900 grid place-items-center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L3 6V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V6L12 2Z" fill="#FFFFFF" />
                        <path d="M9 11.5L11 13.5L15 9.5" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="text-xl font-extrabold tracking-tight text-slate-900">
                    auto<span class="text-brand-500 italic">Secure</span>
                </div>
            </div>

            <!-- Form Header -->
            <div class="mb-8">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-xs font-bold text-slate-600 uppercase tracking-wide mb-3">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Staff Portal</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1.5">Sign In</h2>
                <p class="text-sm text-slate-500">Enter your staff credentials to access operations.</p>
            </div>

            @if ($errors->any())
                <div class="flex items-start gap-3 p-3.5 mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 mt-0.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div class="space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('manage.login.store') }}" class="space-y-4 sm:space-y-5">
                @csrf

                <!-- Work Email -->
                <div>
                    <label for="email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Work Email</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="w-full h-12 pl-11 pr-4 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 transition"
                            placeholder="admin@autosecure.com"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="w-full h-12 pl-11 pr-11 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 transition"
                            placeholder="••••••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="absolute right-3 text-slate-400 hover:text-slate-600 p-1" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between text-xs sm:text-sm pt-1">
                    <label class="flex items-center gap-2 text-slate-600 cursor-pointer select-none" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1" class="w-4 h-4 rounded text-blue-600 accent-blue-600 cursor-pointer">
                        <span>Keep me signed in</span>
                    </label>

                    <a href="#" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline" onclick="alert('Please contact your System Administrator to reset staff credentials.')">
                        Forgot password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" data-loading-text="Signing in..." class="w-full h-12 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-[0.99] text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-slate-900/20 transition-all">
                    <span>Sign In to Dashboard</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>

            <!-- Security Badge Box -->
            <div class="mt-8 p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center gap-2.5 text-xs text-slate-500">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-500 shrink-0">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <span>Protected with enterprise-grade AES-256 TLS encryption. Staff actions are logged for compliance.</span>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const input = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = 'password';
            eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }
</script>
@endsection
