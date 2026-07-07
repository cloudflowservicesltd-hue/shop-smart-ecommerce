<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium"><?= e($page['title']) ?></span>
        </nav>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-10">
    <!-- Page Title -->
    <h1 class="font-heading text-3xl font-bold text-gray-900 mb-8"><?= e($page['title']) ?></h1>

    <?php if (($page['slug'] ?? '') === 'contact-us'): ?>
    <!-- Contact Form (shown before page content for contact-us slug) -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-8">
        <div class="flex items-center gap-2 mb-6">
            <i data-lucide="send" class="w-5 h-5 text-amber-600"></i>
            <h2 class="font-heading text-lg font-semibold text-gray-900">Send Us a Message</h2>
        </div>

        <form method="POST" action="/page/contact-us">
            <?= csrf() ?>

            <div class="grid sm:grid-cols-2 gap-5">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                        placeholder="Your full name">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                        placeholder="your@email.com">
                </div>

                <!-- Subject -->
                <div class="sm:col-span-2">
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1.5">Subject</label>
                    <input type="text" id="subject" name="subject"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                        placeholder="What is this about?">
                </div>

                <!-- Message -->
                <div class="sm:col-span-2">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">Message <span class="text-red-500">*</span></label>
                    <textarea id="message" name="message" rows="5" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors resize-none"
                        placeholder="Write your message here..."></textarea>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-amber-700 transition-colors text-sm">
                    <i data-lucide="send" class="w-4 h-4"></i> Send Message
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <div class="bg-white border border-gray-200 rounded-2xl p-8">
        <div class="text-gray-700 leading-relaxed whitespace-pre-wrap text-sm"><?= e($page['content']) ?></div>
    </div>
</div>