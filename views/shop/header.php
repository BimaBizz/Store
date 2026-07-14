<?php
$storeFront = $this->retrieve('onlineshop') ?? [];
$enableFrontend = !empty($storeFront['enableFrontend']);
$shopUrl = $enableFrontend ? '/' : '/shop';
$trackerUrl = $enableFrontend ? '/tracker' : '/shop/tracker';
$dashboardUrl = $enableFrontend ? '/dashboard' : '/shop/dashboard';
$activeTab = \str_contains($_SERVER['REQUEST_URI'], '/tracker')
    ? 'tracker'
    : (\str_contains($_SERVER['REQUEST_URI'], '/dashboard')
        ? 'dashboard'
        : 'shop');

$app = \Cockpit::instance();
$storeSettings = $app->dataStorage->findOne('store/settings', ['_id' => 'config']);
$shopName = $storeSettings['shop_name'] ?? 'Online Store';
$logoLetter = strtoupper(substr($shopName, 0, 1)) ?: 'S';
$pageTitle = match ($activeTab) {
    'tracker' => "Track Order - {$shopName}",
    'dashboard' => "My Account - {$shopName}",
    default => $shopName,
};

$faviconRaw = $storeSettings['favicon'] ?? '';
$faviconUrl = '';
if ($faviconRaw) {
    if (str_starts_with($faviconRaw, 'assets://')) {
        $assetId = str_replace('assets://', '', $faviconRaw);
        $asset = $app->dataStorage->findOne('assets', ['_id' => $assetId]);
        if ($asset) {
            $faviconUrl = $app->fileStorage->getURL('uploads://' . trim($asset['path'], '/'));
        } else {
            $faviconUrl = $app->routeUrl('/assets/link/' . $assetId);
        }
    } else {
        $faviconUrl = $faviconRaw;
    }
}

$topbarVoucher = null;
$vouchers = $app->dataStorage->find('store/vouchers')->toArray();
if ($vouchers) {
    foreach ($vouchers as $v) {
        $showInTopbar = $v['show_in_topbar'] ?? false;
        $isActive = $v['active'] ?? false;

        $isTopbarEnabled =
            $showInTopbar === true || $showInTopbar === 'true' || $showInTopbar === 1 || $showInTopbar === '1';
        $isVoucherActive = $isActive === true || $isActive === 'true' || $isActive === 1 || $isActive === '1';

        if ($isTopbarEnabled && $isVoucherActive) {
            $topbarVoucher = $v;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Shop premium products with exclusive deals and fast delivery.">
    <?php if ($faviconUrl): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="shortcut icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script>
        window.ENABLE_FRONTEND = <?= $enableFrontend ? 'true' : 'false' ?>;
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <style>
        <?php include __DIR__ . '/shop.css'; ?>
    </style>
</head>
<body>
    <div id="shop-app">
        
        <?php if ($topbarVoucher): ?>
        <div class="promo-topbar">
            <span><?= htmlspecialchars(
                $topbarVoucher['topbar_description'] ?? '' ?: '🔥 Gunakan kode promo: ' . $topbarVoucher['code'],
            ) ?></span>
            <button class="promo-topbar-close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
        <?php endif; ?>

        
        <header>
            <div class="container header-content">
                
                <a href="<?= $shopUrl ?>" class="logo-container" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.75rem;">
                    <?php if ($faviconUrl): ?>
                    <div style="color: #fff; font-size: 1.1rem; border-radius: 8px; width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center; font-weight: 800; overflow: hidden; padding: 0;">
                        <img src="<?= htmlspecialchars(
                            $faviconUrl,
                        ) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                    <?php else: ?>
                    <div class="logo-icon" style="background: var(--accent-site); color: var(--text-on-accent); font-size: 1.1rem; border-radius: 8px; width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center; font-weight: 800;"><?= htmlspecialchars(
                        $logoLetter,
                    ) ?></div>
                    <?php endif; ?>
                    <div class="logo-text"><?= htmlspecialchars($shopName) ?></div>
                </a>

<?php
$aboutUrl = $enableFrontend ? '/about' : '/shop/about';
$faqUrl = $enableFrontend ? '/faq' : '/shop/faq';
?>
                
                <nav class="header-nav">
                    <a href="<?= $shopUrl ?>" class="header-nav-link <?= $activeTab === 'shop'
    ? 'active'
    : '' ?>">Home</a>
                    <a href="<?= $aboutUrl ?>" class="header-nav-link" v-if="homepageContent.about_us">About Us</a>
                    <a href="<?= $faqUrl ?>" class="header-nav-link" v-if="homepageContent.faq">FAQs</a>
                    <a href="<?= $trackerUrl ?>" class="header-nav-link <?= $activeTab === 'tracker'
    ? 'active'
    : '' ?>">Track Order</a>
                </nav>

                
                <div class="nav-buttons" style="display: flex; align-items: center; gap: 0.75rem;">
                    <button class="btn btn-ghost theme-toggle-btn" @click="toggleTheme" title="Toggle Theme" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; color: var(--text-primary);">
                        <svg v-if="theme === 'light'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                    </button>
                    <button class="btn btn-ghost cart-btn" @click="isCartOpen = true" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-primary); width: 2.25rem; height: 2.25rem; text-decoration: none; transition: background-color 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span class="cart-badge" v-if="cartCount > 0">{{ cartCount }}</span>
                    </button>
                    <a href="<?= $dashboardUrl ?>" class="btn btn-ghost account-btn" :class="{ active: activeTab === 'dashboard' }" title="My Account" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-primary); width: 2.25rem; height: 2.25rem; text-decoration: none; transition: background-color 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                </div>
            </div>
        </header>

<main>
