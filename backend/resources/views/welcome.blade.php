<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>autoSecure · Intelligent Vehicle Security & Active Fleet Telematics</title>
    
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
<body class="bg-[#0B1222] text-slate-100 overflow-x-hidden">

    <!-- Ambient Glowing Background Lights -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-gradient-to-b from-blue-600/20 via-orange-500/10 to-transparent blur-3xl opacity-70"></div>
        <div class="absolute top-[800px] -left-40 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="absolute top-[1400px] -right-40 w-[600px] h-[600px] bg-orange-500/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Navigation Bar -->
    <nav class="sticky top-0 z-50 bg-[#0B1222]/80 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-white grid place-items-center shadow-lg shadow-black/40 group-hover:scale-105 transition-transform">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L3 6V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V6L12 2Z" fill="#111827" />
                        <path d="M9 11.5L11 13.5L15 9.5" stroke="#F97316" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="text-2xl font-extrabold tracking-tight text-white">
                    auto<span class="text-brand-500 italic">Secure</span>
                </div>
            </a>

            <!-- Navigation Links (Desktop) -->
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300">
                <a href="#features" class="hover:text-white transition">Features</a>
                <a href="#telematics" class="hover:text-white transition">Live Telematics</a>
                <a href="#recovery" class="hover:text-white transition">Recovery Workflow</a>
                <a href="#security" class="hover:text-white transition">Security & SLA</a>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-3.5">
                <a href="{{ route('manage.login') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 hover:border-slate-500 bg-slate-900/60 hover:bg-slate-800 text-xs sm:text-sm font-bold text-slate-200 transition">
                    Staff Portal
                </a>
                
                <a href="{{ route('manage.login') }}" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-slate-950 font-extrabold text-xs sm:text-sm shadow-lg shadow-orange-500/25 transition active:scale-95">
                    <span>Get Protected</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative z-10 pt-16 sm:pt-24 pb-20 sm:pb-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto">
            <!-- Pill Badge -->
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-semibold text-sky-400 mb-8 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Next-Gen Telematics & Active Recovery Platform</span>
            </div>

            <!-- Headline -->
            <h1 class="text-4xl sm:text-6xl font-extrabold text-white tracking-tight leading-[1.12] mb-6">
                Intelligent Security & <br class="hidden sm:block">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-orange-400 to-amber-300">
                    Real-Time Theft Mitigation
                </span>
            </h1>

            <!-- Subtitle -->
            <p class="text-base sm:text-lg text-slate-400 leading-relaxed max-w-2xl mx-auto mb-10">
                Continuous high-precision GPS telematics, instant unauthorized movement alarms, remote engine immobilisation, and 24/7 dedicated field recovery dispatch.
            </p>

            <!-- CTA Button Group -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('manage.dashboard') }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-white hover:bg-slate-100 text-slate-950 font-bold text-sm shadow-xl shadow-white/10 flex items-center justify-center gap-2.5 transition active:scale-95">
                    <span>Explore Management Portal</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>

                <a href="#features" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-900/80 hover:bg-slate-800 border border-slate-700/80 text-white font-bold text-sm flex items-center justify-center gap-2 transition">
                    <span>View System Capabilities</span>
                </a>
            </div>

            <!-- Trust Bar / Key Proof Points -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-16 border-t border-slate-800/80 mt-16 text-left">
                <div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">98.4%</div>
                    <div class="text-xs text-slate-400 mt-0.5">Verified Recovery Rate</div>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">&lt; 18m</div>
                    <div class="text-xs text-slate-400 mt-0.5">Avg. Response Time</div>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">10,000+</div>
                    <div class="text-xs text-slate-400 mt-0.5">Protected Vehicles</div>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">99.99%</div>
                    <div class="text-xs text-slate-400 mt-0.5">Telemetry Uptime SLA</div>
                </div>
            </div>
        </div>

        <!-- Telemetry Showcase Graphic Card -->
        <div id="telematics" class="mt-16 sm:mt-20 p-4 sm:p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl backdrop-blur-2xl relative overflow-hidden">
            <div class="flex flex-col lg:flex-row items-stretch gap-6">
                <!-- Live Telemetry Card Preview -->
                <div class="w-full lg:w-1/3 bg-slate-950/80 border border-slate-800/80 rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/20 text-xs font-bold text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                Live Feed Online
                            </span>
                            <span class="text-xs text-slate-500 font-mono">ID: TRK-9082</span>
                        </div>

                        <div class="text-lg font-bold text-white mb-1">Toyota Corolla</div>
                        <div class="text-xs text-slate-400 mb-6">Reg: ABC-123DE • VIN: 4T1B11HK5JU192834</div>

                        <div class="space-y-3">
                            <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800/80 flex items-center justify-between text-xs">
                                <span class="text-slate-400">Speed Telemetry</span>
                                <span class="font-bold text-sky-400 text-sm">74 km/h</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800/80 flex items-center justify-between text-xs">
                                <span class="text-slate-400">Engine Ignition</span>
                                <span class="font-bold text-emerald-400">RUNNING (Armed)</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800/80 flex items-center justify-between text-xs">
                                <span class="text-slate-400">GPS Accuracy</span>
                                <span class="font-bold text-white">± 1.8 metres</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Remote Relay</span>
                        <span class="text-xs font-bold text-amber-400">Ready for Command</span>
                    </div>
                </div>

                <!-- Interactive SVG Map / Trail View -->
                <div class="w-full lg:w-2/3 min-h-[320px] bg-slate-950 rounded-2xl relative overflow-hidden border border-slate-800/80 flex flex-col justify-between p-6">
                    <div class="absolute inset-0 opacity-40">
                        <svg class="w-full h-full" viewBox="0 0 700 400" fill="none">
                            <path d="M 40 80 L 660 80 M 40 200 L 660 200 M 40 320 L 660 320" stroke="#1E293B" stroke-width="2" />
                            <path d="M 120 40 L 120 360 M 300 40 L 300 360 M 500 40 L 500 360" stroke="#1E293B" stroke-width="2" />
                            <path d="M 120 320 Q 300 240, 500 180 T 620 80" fill="none" stroke="#38BDF8" stroke-width="4" stroke-dasharray="10 5" />
                            <circle cx="620" cy="80" r="24" fill="#F97316" fill-opacity="0.3" />
                            <circle cx="620" cy="80" r="14" fill="#F97316" fill-opacity="0.7" />
                            <circle cx="620" cy="80" r="7" fill="#FFFFFF" />
                        </svg>
                    </div>

                    <!-- Top Map Label -->
                    <div class="relative z-10 flex items-center justify-between">
                        <div class="bg-slate-900/90 border border-slate-700/80 px-4 py-2 rounded-xl text-xs font-bold text-white">
                            Active Geofence Trail • Lekki Corridor
                        </div>
                        <div class="bg-slate-900/90 border border-slate-700/80 px-3 py-2 rounded-xl text-xs text-slate-300">
                            Telemetry Frequency: <span class="text-sky-400 font-bold">1 Hz Continuous</span>
                        </div>
                    </div>

                    <!-- Bottom Coordinates -->
                    <div class="relative z-10 bg-slate-900/90 border border-slate-800 p-3 rounded-xl flex items-center justify-between text-xs text-slate-400 mt-auto">
                        <div>Coordinates: <span class="font-mono text-white">6.4382° N, 3.4721° E</span></div>
                        <div class="text-emerald-400 font-bold">Encrypted Telemetry Stream</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Section -->
    <section id="features" class="relative z-10 py-20 sm:py-28 bg-[#0F172A]/90 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 sm:mb-20">
                <div class="text-xs font-bold text-brand-500 uppercase tracking-wider mb-3">Enterprise Capabilities</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Comprehensive Security from Sensor to Dispatch
                </h2>
                <p class="text-slate-400 text-sm sm:text-base mt-3">
                    AutoSecure combines hardware telematics, automated cellular security protocols, and human field recovery operations.
                </p>
            </div>

            <!-- Features 6-Box Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- Feature 1 -->
                <div class="p-7 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 grid place-items-center mb-5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Active Theft Mitigation</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Instant unauthorized movement triggers automatic perimeter alarms, remote ignition cut-off, and recovery incident creation.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-7 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 grid place-items-center mb-5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Continuous GPS Telematics</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Sub-second positioning refresh rate, speed and ignition status tracking, and automated geofencing breach alerts.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-7 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 grid place-items-center mb-5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Rapid Response Dispatch</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Direct field response team integration with verified law enforcement liaison delivering under 18-minute average response time.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="p-7 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 grid place-items-center mb-5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Smart Dashcam Integration</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Dual-channel high-definition cameras with impact-triggered incident clipping and automated cloud evidence backup.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="p-7 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 grid place-items-center mb-5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Biometric Mobile Control</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Hardware-backed Face ID and device PIN confirmation required for sensitive vehicle actions like engine immobilisation.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="p-7 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 grid place-items-center mb-5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Forensic Incident Reports</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        One-click certified PDF export of timestamped GPS coordinates, recovery officer logs, and evidence for insurance claims.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4-Step Recovery Workflow -->
    <section id="recovery" class="relative z-10 py-20 sm:py-28 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <div class="text-xs font-bold text-sky-400 uppercase tracking-wider mb-3">Live Emergency Protocol</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                How Active Theft Recovery Operates
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Step 1 -->
            <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 relative">
                <div class="text-4xl font-extrabold text-slate-800 mb-4">01</div>
                <h3 class="text-base font-bold text-white mb-1.5">Unauthorized Movement</h3>
                <p class="text-xs sm:text-sm text-slate-400">
                    Vibration sensor or geofence boundary trigger instantly notifies owner and dispatch centre.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 relative">
                <div class="text-4xl font-extrabold text-slate-800 mb-4">02</div>
                <h3 class="text-base font-bold text-white mb-1.5">Engine Immobilisation</h3>
                <p class="text-xs sm:text-sm text-slate-400">
                    Remote engine cutoff command safely deployed after biometric confirmation.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 relative">
                <div class="text-4xl font-extrabold text-slate-800 mb-4">03</div>
                <h3 class="text-base font-bold text-white mb-1.5">Officer Dispatch</h3>
                <p class="text-xs sm:text-sm text-slate-400">
                    Field recovery team routed directly to live GPS coordinates with law enforcement backup.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 relative">
                <div class="text-4xl font-extrabold text-slate-800 mb-4">04</div>
                <h3 class="text-base font-bold text-white mb-1.5">Safe Handover</h3>
                <p class="text-xs sm:text-sm text-slate-400">
                    Vehicle secured, incident closed, and certified forensic recovery summary generated.
                </p>
            </div>
        </div>
    </section>

    <!-- Bottom CTA Banner -->
    <section class="relative z-10 py-16 sm:py-20 bg-gradient-to-r from-blue-900/40 via-slate-900 to-orange-950/30 border-y border-slate-800">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <h2 class="text-2xl sm:text-4xl font-extrabold text-white mb-4 tracking-tight">
                Ready to protect your fleet with autoSecure?
            </h2>
            <p class="text-slate-400 text-sm sm:text-base max-w-xl mx-auto mb-8">
                Connect your trackers, automate incident recovery workflows, and access real-time telemetry from any device.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-10">
                <a href="{{ route('manage.login') }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-orange-500 hover:bg-orange-600 text-slate-950 font-bold text-sm shadow-xl shadow-orange-500/20 transition">
                    Access Staff Portal
                </a>
                <a href="{{ route('manage.dashboard') }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm border border-slate-700 transition">
                    Live Operations Overview
                </a>
            </div>

            <!-- Enterprise Demo Request Form -->
            <div class="max-w-md mx-auto p-6 rounded-2xl bg-slate-950/70 border border-slate-800 backdrop-blur-xl text-left">
                <div class="text-xs font-bold text-sky-400 uppercase tracking-wide mb-1">Request Enterprise Pilot</div>
                <div class="text-sm text-slate-300 mb-4">Connect with our fleet telematics engineers within 24 hours.</div>

                <form onsubmit="event.preventDefault(); const btn=this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<svg class=\'animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950 inline-block\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg><span>Submitting Pilot Request...</span>'; setTimeout(() => { alert('Thank you! Our fleet telematics team will contact you shortly.'); btn.disabled=false; btn.innerHTML='<span>Request Pilot Access</span>'; }, 1500);" class="space-y-3">
                    <div>
                        <input type="email" placeholder="Enter corporate or fleet email..." required class="w-full h-11 px-4 rounded-xl bg-slate-900 border border-slate-700 text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-orange-500 transition">
                    </div>
                    <button type="submit" class="w-full h-11 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-slate-950 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg shadow-orange-500/20 transition active:scale-98">
                        <span>Request Pilot Access</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative z-10 py-12 border-t border-slate-900 text-slate-500 text-xs text-center">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-extrabold text-white text-sm">auto<span class="text-brand-500 italic">Secure</span></span>
                <span>© 2026 autoSecure Ltd. All rights reserved.</span>
            </div>

            <div class="flex items-center gap-6 text-slate-400">
                <a href="#features" class="hover:text-white transition">Features</a>
                <a href="{{ route('manage.login') }}" class="hover:text-white transition">Staff Sign In</a>
                <a href="{{ route('manage.dashboard') }}" class="hover:text-white transition">Operations</a>
            </div>
        </div>
    </footer>

    <!-- Universal Form Disabler & Loading Animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form:not([onsubmit])').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
                    if (submitBtn) {
                        if (submitBtn.dataset.submitting === 'true') {
                            e.preventDefault();
                            return false;
                        }
                        submitBtn.dataset.submitting = 'true';
                        
                        const currentWidth = submitBtn.offsetWidth;
                        const currentHeight = submitBtn.offsetHeight;
                        if (currentWidth && currentHeight) {
                            submitBtn.style.minWidth = currentWidth + 'px';
                            submitBtn.style.minHeight = currentHeight + 'px';
                        }

                        const loadingText = submitBtn.dataset.loadingText || 'Processing...';
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none');
                        
                        submitBtn.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current inline-block shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>${loadingText}</span>
                        `;
                    }
                });
            });
        });
    </script>
</body>
</html>
