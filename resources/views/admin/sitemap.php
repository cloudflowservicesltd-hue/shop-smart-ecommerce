<?php
$breadcrumbs = [['SEO', '/admin/seo'], ['Sitemap Editor', '']];
$success = Session::get('success'); Session::remove('success');

$sitemapPath = ROOT_PATH . '/public/sitemap.xml';
$sitemapContent = '';
if (file_exists($sitemapPath)) {
    $sitemapContent = file_get_contents($sitemapPath);
}

// Count URLs in current sitemap
$urlCount = substr_count($sitemapContent, '<url>');
$hasSitemap = !empty($sitemapContent);
?>

<div class="space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="/admin/seo" class="hover:text-amber-600">SEO</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-gray-900 font-medium">Sitemap Editor</span>
    </div>

    <?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-2 text-green-700">
        <i data-lucide="check-circle" class="w-5 h-5"></i> <?= e($success) ?>
    </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="file-code" class="w-6 h-6 text-amber-600"></i> Sitemap Editor
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                <?php if ($hasSitemap): ?>
                Current sitemap has <strong><?= $urlCount ?></strong> URLs
                <?php else: ?>
                No sitemap.xml found — generate one below
                <?php endif; ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/sitemap.xml" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="external-link" class="w-4 h-4"></i> View Live
            </a>
        </div>
    </div>

    <!-- Auto-generate -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
        <h3 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
            <i data-lucide="sparkles" class="w-5 h-5 text-amber-600"></i> Auto-Generate from Database
        </h3>
        <p class="text-sm text-gray-600 mb-4">Automatically create a sitemap with all your products, categories, brands, and static pages.</p>
        <div class="flex items-center gap-3">
            <button onclick="autoGenerate()" id="autoGenBtn" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-amber-700 transition-colors text-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Generate & Save
            </button>
            <button onclick="previewGenerate()" id="previewBtn" class="inline-flex items-center gap-2 bg-white text-gray-700 font-medium px-6 py-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors text-sm">
                <i data-lucide="eye" class="w-4 h-4"></i> Preview First
            </button>
        </div>
        <div id="genStatus" class="mt-3"></div>
    </div>

    <!-- Manual Editor -->
    <form method="POST" action="/admin/sitemap/save" class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i data-lucide="code" class="w-5 h-5 text-amber-600"></i> Sitemap XML Content
                </h3>
                <span class="text-xs text-gray-400">sitemap.xml</span>
            </div>
            <textarea name="sitemap_content" id="sitemapEditor" rows="20"
                      class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y leading-relaxed"
                      placeholder="<?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?>
&lt;urlset xmlns=&quot;http://www.sitemaps.org/schemas/sitemap/0.9&quot;&gt;
  &lt;url&gt;
    &lt;loc&gt;https://yourdomain.com/&lt;/loc&gt;
    &lt;changefreq&gt;daily&lt;/changefreq&gt;
    &lt;priority&gt;1.0&lt;/priority&gt;
  &lt;/url&gt;
&lt;/urlset&gt;"><?= e($sitemapContent) ?></textarea>
        </div>

        <div class="flex items-center justify-between">
            <a href="/admin/seo" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 font-medium px-6 py-3 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to SEO
            </a>
            <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i> Save Sitemap
            </button>
        </div>
    </form>
</div>

<script>
async function previewGenerate() {
    const btn = document.getElementById('previewBtn');
    const status = document.getElementById('genStatus');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Loading...';
    status.innerHTML = '<div class="flex items-center gap-2 text-amber-600 text-sm"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Generating preview...</div>';

    try {
        const resp = await fetch('/admin/sitemap/generate');
        const data = await resp.json();
        if (data.success) {
            document.getElementById('sitemapEditor').value = data.sitemap;
            const count = (data.sitemap.match(/<url>/g) || []).length;
            status.innerHTML = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700"><i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i> Preview loaded — ' + count + ' URLs generated. Review and click Save when ready.</div>';
        } else {
            status.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">' + (data.error || 'Failed') + '</div>';
        }
    } catch(e) {
        status.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">Network error</div>';
    }
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i> Preview First';
}

async function autoGenerate() {
    const btn = document.getElementById('autoGenBtn');
    const status = document.getElementById('genStatus');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Generating...';
    status.innerHTML = '';

    const formData = new FormData();
    formData.append('auto_generate', '1');
    formData.append('sitemap_content', ''); // not needed but form expects it

    try {
        const resp = await fetch('/admin/sitemap/save', { method: 'POST', body: formData });
        const text = await resp.text();
        // It redirects, so if we get here it means something went wrong
        // Actually the PHP will redirect, so we just wait
        if (resp.redirected) {
            window.location.href = resp.url;
        } else {
            // Try form submit instead
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/sitemap/save';
            const input1 = document.createElement('input');
            input1.type = 'hidden'; input1.name = 'auto_generate'; input1.value = '1';
            form.appendChild(input1);
            document.body.appendChild(form);
            form.submit();
        }
    } catch(e) {
        status.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">Error: ' + e.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="refresh-cw" class="w-4 h-4"></i> Generate & Save';
    }
}
</script>