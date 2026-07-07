<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ShopSmart</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif'],heading:['Poppins','sans-serif']}}}}</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="flex items-center gap-2 mb-6 justify-center">
            <div class="w-9 h-9 bg-amber-600 rounded-lg flex items-center justify-center"><i data-lucide="shopping-bag" class="w-5 h-5 text-white"></i></div>
            <span class="font-heading font-bold text-xl">Shop<span class="text-amber-600">Smart</span></span>
        </div>
        <h1 class="font-heading font-bold text-2xl text-center mb-1">Create your account</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Join ShopSmart for the best shopping experience</p>

        <?php if ($refParam = Request::query('ref', '')): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm mb-4 flex items-center gap-2">
            <i data-lucide="gift" class="w-4 h-4 text-amber-600 shrink-0"></i>
            <span>You were referred by <strong class="font-mono uppercase tracking-wider"><?= e($refParam) ?></strong> — welcome!</span>
        </div>
        <?php endif; ?>

        <?php if ($error = Session::getFlash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name</label><input type="text" name="name" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="tel" name="phone" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="+254..."></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label><input type="email" name="email" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" required minlength="6" id="regPassword" placeholder="Min 6 characters" class="w-full px-3 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" onclick="togglePwd('regPassword',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1" aria-label="Toggle password visibility">
                        <i data-lucide="eye-off" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" required id="regConfirm" placeholder="Re-enter password" class="w-full px-3 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" onclick="togglePwd('regConfirm',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1" aria-label="Toggle password visibility">
                        <i data-lucide="eye-off" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referral Code <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="referral_code" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 uppercase tracking-wider" placeholder="Enter referral code e.g. REFXXXXXXXX" value="<?= e(Request::query('ref', Session::get('referral_code', ''))) ?>">
                <p class="text-xs text-gray-400 mt-1">If someone referred you, enter their code here to earn rewards</p>
            </div>
            <button type="submit" class="w-full bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">Create Account</button>
        </form>
        <p class="text-sm text-gray-500 text-center mt-4">Already have an account? <a href="/login" class="text-amber-600 hover:text-amber-700 font-medium">Sign in</a></p>
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