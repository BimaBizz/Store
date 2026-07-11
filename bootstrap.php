<?php

// load admin related code
$this->on('app.admin.init', function() {
    include(__DIR__.'/admin.php');
});

// load api request related code
$this->on('app.api.request', function() {
    include(__DIR__.'/api.php');
});

// --- Storefront Custom URL & Login Path Override ---
$app->on('app.admin.request', function() use ($app) {
    $storeFront = $app->retrieve('storeFront') ?? [];
    if (!empty($storeFront['enableFrontend'])) {
        
        // Dynamic shop slug resolution
        $shopSettings = $app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [];
        $shopName = $shopSettings['shop_name'] ?? 'Online Coffee Store';
        $shopSlug = \strtolower(\preg_replace('/[^a-zA-Z0-9-]/', '', \str_replace(' ', '-', $shopName)));
        $shopSlug = \preg_replace('/-+/', '-', $shopSlug);
        $dashRoute = '/dash-' . $shopSlug;

        // 1. Root / is mapped to shop catalog storefront
        $app->bind('/', function() use ($app) {
            if ($app->helper('auth')->getUser()) {
                return $app->reroute('/store');
            }
            return $app->invoke('Store\\Controller\\Shop', 'index');
        });

        // 2. Main tabs subpaths mapped to storefront subpages
        $app->bind('/tracker', function() use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'tracker');
        });

        $app->bind('/dashboard', function() use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'dashboard');
        });

        $app->bind('/about', function() use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'about');
        });

        $app->bind('/faq', function() use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'faq');
        });

        $app->bind('/security', function() use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'security');
        });

        $app->bind('/product/:id', function($params) use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'product', ['id' => $params['id']]);
        });

        // 3. Cockpit login page becomes /dash-{shopname}
        $app->bind($dashRoute, function() use ($app) {
            if ($app->helper('auth')->getUser()) {
                return $app->reroute('/store');
            }
            return $app->invoke('App\\Controller\\Auth', 'login');
        });

        // 4. Intercept direct access to /auth/login and redirect to /dash-{shopname} or /
        $route = $app->request->route ?? '';
        if ($route === '/auth/login') {
            $to = $app->param('to');
            if ($to) {
                return $app->reroute($dashRoute . '?to=' . \urlencode($to));
            }
            return $app->reroute('/');
        }

        // 5. Midtrans Snap finish redirect for enableFrontend mode
        $app->bind('/finished-order', function() use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'finishedOrder');
        });
    }

    // Always register /shop/finished-order regardless of enableFrontend setting
    $app->bind('/shop/finished-order', function() use ($app) {
        return $app->invoke('Store\\Controller\\Shop', 'finishedOrder');
    });
});