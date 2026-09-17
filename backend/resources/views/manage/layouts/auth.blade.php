<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Staff Sign In') · autoSecure Management</title>
    
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
    @yield('content')

    <!-- Universal Form Submit Disabler & Loading Animation -->
    <script>
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
                        
                        // Lock current dimensions to avoid layout shifts
                        const currentWidth = submitBtn.offsetWidth;
                        const currentHeight = submitBtn.offsetHeight;
                        if (currentWidth && currentHeight) {
                            submitBtn.style.minWidth = currentWidth + 'px';
                            submitBtn.style.minHeight = currentHeight + 'px';
                        }

                        const loadingText = submitBtn.dataset.loadingText || 'Signing in...';
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none', 'select-none');
                        
                        submitBtn.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
