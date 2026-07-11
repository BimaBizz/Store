<?php


// Register routes
$this->bindClass('Store\\Controller\\Store', '/store');
$this->bindClass('Store\\Controller\\Shop', '/shop');

$this->on('app.layout.init', function() {

    $this->helper('menus')->addLink('modules', [
        'label'  => 'Online Store',
        'icon'   => 'store:icon.svg',
        'route'  => '/store',
        'active' => false,
        'group'  => 'shop', 
    ]);
});

$this->on('app.settings.collect', function($settings) {

    $settings['Shop'][] = [
        'icon' => 'store:icon.svg',
        'route' => '/store',
        'label' => 'Store',
        'permission' => 'store/manage'
    ];
});

$this->on('app.permissions.collect', function (ArrayObject $permissions) {
    $permissions['Online Store'] = [
        'store/manage' => 'Manage Online Store products, orders, customers, and reports',
        'store/api/products' => 'Access products list API',
        'store/api/content' => 'Access storefront CMS content API',
        'store/api/order' => 'Create orders/checkout API',
        'store/api/vouchers' => 'Access promo vouchers list API',
        'store/api/auth' => 'Access customer registration & authentication APIs'
    ];
});

