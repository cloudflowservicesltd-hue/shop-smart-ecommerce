<?php
$loginLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_logo'")['value'] ?? '';
$loginTitle = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_title'")['value'] ?? 'ShopSmart';
$loginSubtitle = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_subtitle'")['value'] ?? 'AI-Powered Ecommerce & POS';
$loginDescription = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_description'")['value'] ?? 'Complete business solution with intelligent marketing, real-time inventory, and seamless payment processing.';
$loginBgColor = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_bg_color'")['value'] ?? '#b45309';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= e($loginTitle) ?></title>
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
    <div class="hidden lg:flex lg:w-1/2 p-12 flex-col justify-between text-white relative overflow-hidden" style="background-color:<?= e($loginBgColor) ?>">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-20 w-64 h-64 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-8">
                <?php if ($loginLogo): ?>
                <img src="<?= e($loginLogo) ?>" alt="<?= e($loginTitle) ?>" class="w-10 h-10 rounded-xl object-cover">
                <?php else: ?>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i data-lucide="shopping-bag" class="w-6 h-6"></i></div>
                <?php endif; ?>
                <span class="font-heading font-bold text-2xl"><?= e($loginTitle) ?></span>
            </div>
            <h2 class="font-heading text-3xl font-bold mb-4"><?= e($loginSubtitle) ?></h2>
            <p class="text-white/70 text-sm leading-relaxed max-w-md"><?= e($loginDescription) ?></p>
        </div>
        <div class="relative z-10 grid grid-cols-2 gap-4 mt-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="package" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">Smart Inventory</p><p class="text-xs text-white/60 mt-1">Real-time stock tracking with AI forecasts</p></div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="sparkles" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">AI Marketing</p><p class="text-xs text-white/60 mt-1">Auto-generate ads for all platforms</p></div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="monitor" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">POS System</p><p class="text-xs text-white/60 mt-1">Fast in-store sales terminal</p></div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4"><i data-lucide="credit-card" class="w-6 h-6 mb-2"></i><p class="text-sm font-medium">Multi-Payment</p><p class="text-xs text-white/60 mt-1">M-Pesa, Stripe, IntaSend & more</p></div>
        </div>
    </div>

    <!-- Right side (form) -->
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center gap-2 mb-8 justify-center">
                <?php if ($loginLogo): ?>
                <img src="<?= e($loginLogo) ?>" alt="<?= e($loginTitle) ?>" class="w-9 h-9 rounded-lg object-cover">
                <?php else: ?>
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background-color:<?= e($loginBgColor) ?>"><i data-lucide="shopping-bag" class="w-5 h-5 text-white"></i></div>
                <?php endif; ?>
                <span class="font-heading font-bold text-xl"><?= e($loginTitle) ?></span>
            </div>

            <h1 class="font-heading font-bold text-2xl text-gray-900 mb-1">Forgot your password?</h1>
            <p class="text-sm text-gray-500 mb-6">Enter your email and we'll send you a reset link.</p>

            <?php if ($error = Session::flash('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success = Session::flash('success')): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i><?= e($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/forgot-password" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required placeholder="you@example.com"
                            value="<?= e(Request::post('email', '')) ?>"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <button type="submit" class="w-full text-white py-2.5 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity" style="background-color:<?= e($loginBgColor) ?>">
                    Send Reset Link
                </button>
            </form>

            <p class="text-sm text-gray-500 text-center mt-6"><a href="/login" class="font-medium inline-flex items-center gap-1 hover:opacity-80 transition-opacity" style="color:<?= e($loginBgColor) ?>"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Login</a></p>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>