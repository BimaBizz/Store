<?php

$this->on('app.admin.init', function () {
    include __DIR__ . '/admin.php';
});

$this->on('app.api.request', function () {
    include __DIR__ . '/api.php';
});

$app->on('app.admin.request', function () use ($app) {
    $app->bind('/shop/finished-order', function () use ($app) {
        return $app->invoke('Store\\Controller\\Shop', 'finishedOrder');
    });

    $storeFront = $app->retrieve('onlineshop') ?? [];
    if (!empty($storeFront['enableFrontend'])) {
        $shopSettings = $app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [];
        $shopName = $shopSettings['shop_name'] ?? 'Online Coffee Store';
        $shopSlug = \strtolower(\preg_replace('/[^a-zA-Z0-9-]/', '', \str_replace(' ', '-', $shopName)));
        $shopSlug = \preg_replace('/-+/', '-', $shopSlug);
        $dashRoute = '/dash-' . $shopSlug;

        $app->bind('/', function () use ($app) {
            if ($app->helper('auth')->getUser()) {
                return $app->reroute('/store');
            }
            return $app->invoke('Store\\Controller\\Shop', 'index');
        });

        $app->bind('/tracker', function () use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'tracker');
        });

        $app->bind('/dashboard', function () use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'dashboard');
        });

        $app->bind('/about', function () use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'about');
        });

        $app->bind('/faq', function () use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'faq');
        });

        $app->bind('/security', function () use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'security');
        });

        $app->bind('/product/:id', function ($params) use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'product', ['id' => $params['id']]);
        });

        $app->bind($dashRoute, function () use ($app) {
            if ($app->helper('auth')->getUser()) {
                return $app->reroute('/store');
            }
            return $app->invoke('App\\Controller\\Auth', 'login');
        });

        $route = $app->request->route ?? '';
        if ($route === '/auth/login') {
            $to = $app->param('to');
            if ($to) {
                return $app->reroute($dashRoute . '?to=' . \urlencode($to));
            }
            return $app->reroute('/');
        }

        $app->bind('/finished-order', function () use ($app) {
            return $app->invoke('Store\\Controller\\Shop', 'finishedOrder');
        });
    }
});
