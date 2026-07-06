<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShopSmart</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif'],heading:['Poppins','sans-serif']}}}}</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <!-- Left side (branding) -->
    <div class="hidden lg:flex lg:w-1/2 bg-amber-700 p-12 flex-col justify-between text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-20 w-64 h-64 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-amber-300 rounded-full blur-3xl"></div>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-8">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i data-lucide="shopping-bag" class="w-6 h-6"></i></div>
                <span class="font-heading font-bold text-2xl">Shop<span class="text-amber-200">Smart</span></span>
            </div>
            <h2 class="font-heading text-3xl font-bold mb-4">AI-Powered Ecommerce & POS</h2>
            <p class="text-amber-100 text-sm leading-relaxed max-w-md">Complete business solution with intelligent marketing, real-time inventory, and seamless payment processing.</p>
        </div>
        <div class="relative z-10 grid grid-cols-2 gap-4 mt-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="package" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">Smart Inventory</p><p class="text-xs text-amber-200 mt-1">Real-time stock tracking with AI forecasts</p></div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="sparkles" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">AI Marketing</p><p class="text-xs text-amber-200 mt-1">Auto-generate ads for all platforms</p></div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="monitor" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">POS System</p><p class="text-xs text-amber-200 mt-1">Fast in-store sales terminal</p></div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="credit-card" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">Multi-Payment</p><p class="text-xs text-amber-200 mt-1">M-Pesa, Stripe, IntaSend & more</p></div>
        </div>
    </div>

    <!-- Right side (form) -->
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center gap-2 mb-8 justify-center">
                <div class="w-9 h-9 bg-amber-600 rounded-lg flex items-center justify-center"><i data-lucide="shopping-bag" class="w-5 h-5 text-white"></i></div>
                <span class="font-heading font-bold text-xl">Shop<span class="text-amber-600">Smart</span></span>
            </div>

            <h1 class="font-heading font-bold text-2xl text-gray-900 mb-1">Welcome back</h1>
            <p class="text-sm text-gray-500 mb-6">Sign in to your account to continue</p>

            <?php if ($error = Session::flash('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required placeholder="you@example.com"
                            value="<?= e(Request::post('email', '')) ?>"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required placeholder="Enter your password" id="loginPassword"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <button type="button" onclick="togglePwd('loginPassword',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1" aria-label="Toggle password visibility">
                            <i data-lucide="eye-off" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
                    Sign In
                </button>
                <div class="text-right">
                    <a href="/forgot-password" class="text-sm text-amber-600 hover:text-amber-700 font-medium">Forgot your password?</a>
                </div>
            </form>

            <p class="text-sm text-gray-500 text-center mt-6">Don't have an account? <a href="/register" class="text-amber-600 hover:text-amber-700 font-medium">Create account</a></p>

            <!-- Demo Credentials -->
            <div class="mt-8 bg-gray-50 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-600 mb-2">Demo Accounts:</p>
                <div class="space-y-1.5 text-xs text-gray-500">
                    <p><span class="font-medium text-gray-700">Admin:</span> admin@ecommerce.com / admin123</p>
                    <p><span class="font-medium text-gray-700">Manager:</span> manager@ecommerce.com / manager123</p>
                    <p><span class="font-medium text-gray-700">Cashier:</span> cashier@ecommerce.com / cashier123</p>
                    <p><span class="font-medium text-gray-700">Customer:</span> jane@example.com / customer123</p>
                </div>
            </div>
        </div>
    </div>
    <script>
    function togglePwd(id, btn) {
        const inp = document.getElementById(id);
        const isHidden = inp.type === 'password';
        inp.type = isHidden ? 'text' : 'password';
        btn.innerHTML = '<i data-lucide="' + (isHidden ? 'eye' : 'eye-off') + '" class="w-4 h-4"></i>';
        lucide.createIcons();
    }
    lucide.createIcons();
    </script>
</body>
</html>