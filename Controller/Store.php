<?php

namespace Store\Controller;

use App\Controller\App;

class Store extends App {

    protected function before() {
        if (!$this->isAllowed('store/manage')) {
            return $this->stop(401);
        }
    }

    public function index() {
        return $this->render('store:views/index.php');
    }

    public function products() {
        return $this->render('store:views/products.php');
    }

    public function orders() {
        return $this->render('store:views/orders.php');
    }

    public function customers() {
        return $this->render('store:views/customers.php');
    }

    public function promotions() {
        return $this->render('store:views/promotions.php');
    }

    public function suppliers() {
        return $this->render('store:views/suppliers.php');
    }

    public function reports() {
        return $this->render('store:views/reports.php');
    }

    public function content() {
        return $this->render('store:views/content.php');
    }

    public function settings() {
        return $this->render('store:views/settings.php');
    }

    public function getProducts() {
        $this->helper('session')->close();

        $limit = intval($this->param('limit', 20));
        $page = intval($this->param('page', 1));
        if ($limit < 1) $limit = 20;
        $skip = ($page - 1) * $limit;
        $search = trim($this->param('search', ''));
        
        $criteria = [];
        if ($search) {
            $criteria['$or'] = [
                ['name' => ['$regex' => $search, '$options' => 'i']],
                ['sku' => ['$regex' => $search, '$options' => 'i']],
                ['description' => ['$regex' => $search, '$options' => 'i']],
                ['category' => ['$regex' => $search, '$options' => 'i']],
                ['brand' => ['$regex' => $search, '$options' => 'i']]
            ];
        }

        $total = $this->app->dataStorage->count('store/products', $criteria);
        $pages = ceil($total / $limit);
        
        $sortKey = $this->param('sort', 'name');
        $sortDir = intval($this->param('sort_dir', 1));
        $options = [
            'limit' => $limit,
            'skip' => $skip,
            'sort' => [$sortKey => $sortDir]
        ];
        if ($criteria) {
            $options['filter'] = $criteria;
        }

        $products = $this->app->dataStorage->find('store/products', $options)->toArray();


        foreach ($products as &$prod) {
            if (isset($prod['image']) && \str_starts_with($prod['image'], 'assets://')) {
                $id = \str_replace('assets://', '', $prod['image']);
                $prod['image_url'] = $this->app->routeUrl('/assets/link/' . $id);
            } else {
                $prod['image_url'] = $prod['image'] ?? null;
            }
        }

        return [
            'products' => $products,
            'count' => $total,
            'pages' => $pages ?: 1,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function saveProduct() {
        $this->hasValidCsrfToken(true);

        $product = $this->param('product');
        if (!$product || !isset($product['name']) || !isset($product['price'])) {
            return $this->stop(400, 'Missing product details');
        }

        // Generate SKU if missing
        if (empty($product['sku'])) {
            $product['sku'] = 'SKU-' . \strtoupper(\substr(\uniqid(), 7, 6));
        }

        // Set ID from SKU or random string
        if (empty($product['_id'])) {
            $product['_id'] = 'prod-' . \strtolower(\str_replace(' ', '-', $product['sku']));
        }

        $product['price'] = (float) $product['price'];
        $product['stock'] = (int) $product['stock'];

        $existing = $this->app->dataStorage->findOne('store/products', ['_id' => $product['_id']]);
        if ($existing) {
            $this->app->dataStorage->save('store/products', $product);
        } else {
            $this->app->dataStorage->insert('store/products', $product);
        }

        $this->app->module('system')->log("Store Addon: Product {$product['name']} was saved", 'store', 'info', $product);

        return ['success' => true, 'product' => $product];
    }

    public function deleteProduct() {
        $this->hasValidCsrfToken(true);

        $id = $this->param('id');
        if (!$id) {
            return $this->stop(400, 'Missing product ID');
        }

        $this->app->dataStorage->remove('store/products', ['_id' => $id]);
        $this->app->module('system')->log("Store Addon: Product {$id} was deleted", 'store', 'info');

        return ['success' => true];
    }

    public function getOrders() {
        $this->helper('session')->close();

        $limit = intval($this->param('limit', 20));
        $page = intval($this->param('page', 1));
        if ($limit < 1) $limit = 20;
        $skip = ($page - 1) * $limit;
        $search = trim($this->param('search', ''));
        
        $criteria = [];
        if ($search) {
            $criteria['$or'] = [
                ['order_id' => ['$regex' => $search, '$options' => 'i']],
                ['customer_name' => ['$regex' => $search, '$options' => 'i']],
                ['customer_email' => ['$regex' => $search, '$options' => 'i']],
                ['status' => ['$regex' => $search, '$options' => 'i']],
                ['payment_status' => ['$regex' => $search, '$options' => 'i']]
            ];
        }

        $total = $this->app->dataStorage->count('store/orders', $criteria);
        $pages = ceil($total / $limit);

        $sortKey = $this->param('sort', 'created');
        $sortDir = intval($this->param('sort_dir', -1));
        $options = [
            'limit' => $limit,
            'skip' => $skip,
            'sort' => [$sortKey => $sortDir]
        ];
        if ($criteria) {
            $options['filter'] = $criteria;
        }

        $orders = $this->app->dataStorage->find('store/orders', $options)->toArray();



        return [
            'orders' => $orders,
            'count' => $total,
            'pages' => $pages ?: 1,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function createOrder() {
        $this->hasValidCsrfToken(true);

        $customerName = $this->param('customer_name');
        $customerEmail = $this->param('customer_email');
        $items = $this->param('items'); // Array of items: product_id, quantity
        $voucherCode = $this->param('voucher_code', '');
        $courier = $this->param('courier', 'Manual');
        $shippingCost = floatval($this->param('shipping_cost', 0));

        if (!$customerName || !$customerEmail || empty($items)) {
            return $this->stop(400, 'Missing order parameters');
        }

        // Calculate pricing and check stock
        $totalAmount = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = $this->app->dataStorage->findOne('store/products', ['_id' => $item['product_id']]);
            if (!$product) {
                return $this->stop(400, "Product {$item['product_id']} not found");
            }

            $qty = (int) $item['quantity'];
            if ($qty <= 0) continue;

            if ($product['stock'] < $qty) {
                return $this->stop(400, "Insufficient stock for {$product['name']}. Available: {$product['stock']}");
            }

            // Deduct stock
            $product['stock'] -= $qty;
            $this->app->dataStorage->save('store/products', $product);

            $itemCost = $product['price'] * $qty;
            $totalAmount += $itemCost;

            $orderItems[] = [
                'product_id' => $product['_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $qty
            ];
        }

        if (empty($orderItems)) {
            return $this->stop(400, 'Order cannot be empty');
        }

        // Calculate discount
        $discountAmount = 0;
        if ($voucherCode) {
            $voucher = $this->app->dataStorage->findOne('store/vouchers', ['code' => $voucherCode]);
            if ($voucher && ($voucher['active'] === 'true' || $voucher['active'] === true || $voucher['active'] === 1 || $voucher['active'] === '1')) {
                if ($voucher['type'] === 'percent') {
                    $discountAmount = $totalAmount * (floatval($voucher['value']) / 100);
                } else {
                    $discountAmount = floatval($voucher['value']);
                }
                if ($discountAmount > $totalAmount) {
                    $discountAmount = $totalAmount;
                }
            }
        }

        // Get PPN/tax from store/settings
        $storeSettings = $this->app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [];
        $taxPercent = floatval($storeSettings['tax_percent'] ?? 0);
        $taxAmount = ($totalAmount - $discountAmount) * ($taxPercent / 100);

        // Grand total
        $grandTotal = ($totalAmount - $discountAmount) + $taxAmount + $shippingCost;

        $orderUniqueId = 'ORD-' . \strtoupper(\substr(\uniqid(), 7, 6));
        $transactionId = 'TX-' . \strtoupper(\dechex(\time())) . \strtoupper(\dechex(\rand(1000, 9999)));

        // 1. Get Midtrans credentials to generate Snap tokens
        $settings = $this->app->dataStorage->findOne('midtrans/settings', ['_id' => 'config']) ?? [];
        $mode = $settings['mode'] ?? 'sandbox';
        $serverKey = '';
        if ($mode === 'sandbox') {
            $serverKey = $settings['sandbox_server_key'] ?? '';
        } else {
            $serverKey = $settings['production_server_key'] ?? '';
        }

        $snapToken = '';
        $redirectUrl = '';

        if ($serverKey) {
            $apiUrl = $mode === 'sandbox'
                ? 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                : 'https://app.midtrans.com/snap/v1/transactions';

            $itemDetails = \array_map(function($item) {
                return [
                    'id' => $item['product_id'],
                    'price' => (int) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'name' => \substr($item['name'], 0, 50)
                ];
            }, $orderItems);

            if ($discountAmount > 0) {
                $itemDetails[] = [
                    'id' => 'VOUCHER-' . $voucherCode,
                    'price' => -(int) $discountAmount,
                    'quantity' => 1,
                    'name' => 'Promo Code: ' . $voucherCode
                ];
            }
            if ($taxAmount > 0) {
                $itemDetails[] = [
                    'id' => 'TAX',
                    'price' => (int) $taxAmount,
                    'quantity' => 1,
                    'name' => 'Tax (' . $taxPercent . '%)'
                ];
            }
            if ($shippingCost > 0) {
                $itemDetails[] = [
                    'id' => 'SHIPPING',
                    'price' => (int) $shippingCost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost (' . $courier . ')'
                ];
            }

            $payload = [
                'transaction_details' => [
                    'order_id' => $transactionId,
                    'gross_amount' => (int) $grandTotal
                ],
                'customer_details' => [
                    'first_name' => $customerName,
                    'email' => $customerEmail
                ],
                'item_details' => $itemDetails
            ];

            $ch = \curl_init($apiUrl);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, CURLOPT_POST, true);
            \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($payload));
            \curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . \base64_encode($serverKey . ':')
            ]);

            $response = \curl_exec($ch);
            $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (\PHP_VERSION_ID < 80500) { \curl_close($ch); }

            if ($httpCode === 200 || $httpCode === 201) {
                $resData = \json_decode($response, true);
                $snapToken = $resData['token'] ?? '';
                $redirectUrl = $resData['redirect_url'] ?? '';
            }
        }

        // 2. Save order document
        $order = [
            '_id' => 'ord-' . \strtolower($orderUniqueId),
            'order_id' => $orderUniqueId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'items' => $orderItems,
            'total_amount' => $grandTotal,
            'subtotal_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'voucher_code' => $voucherCode,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'courier' => $courier,
            'resi' => '',
            'status' => 'pending',
            'payment_status' => 'pending',
            'transaction_id' => $transactionId,
            'snap_token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'created' => \time()
        ];
        $this->app->dataStorage->insert('store/orders', $order);

        // 3. Save matching transaction in midtrans/transactions
        $txRecord = [
            '_id' => $transactionId,
            'transaction_id' => $transactionId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'amount' => $grandTotal,
            'payment_method' => 'Credit Card / Qris',
            'status' => 'pending',
            'snap_token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'created' => \time()
        ];
        $this->app->dataStorage->insert('midtrans/transactions', $txRecord);

        // Send transactional email if mailer is configured
        try {
            $emailBody = $this->app->render('midtrans:views/emails/payment_charge.php', ['transaction' => $txRecord]);
            $this->app->mailer->mail($customerEmail, "Complete your payment for Order {$orderUniqueId}", $emailBody);
            
            // Save local email preview
            $previewDir = APP_DIR . '/storage/tmp/midtrans-emails';
            if (!\file_exists($previewDir)) {
                \mkdir($previewDir, 0777, true);
            }
            \file_put_contents("{$previewDir}/{$transactionId}-pending.html", $emailBody);
        } catch (\Exception $e) {
            // Silently ignore mailer config issues for testing
        }

        // Notify event stream
        if (isset($this->app->helpers['eventStream'])) {
            $this->app->helper('eventStream')->trigger('midtrans.transactions.updated', []);
        }

        $this->app->module('system')->log("Store Addon: Order {$orderUniqueId} was created", 'store', 'info', $order);

        return ['success' => true, 'order' => $order];
    }

    public function updateOrderStatus() {
        $this->hasValidCsrfToken(true);

        $id = $this->param('id');
        $status = $this->param('status');
        $courier = $this->param('courier', '');
        $resi = $this->param('resi', '');

        if (!$id || !$status) {
            return $this->stop(400, 'Missing parameters');
        }

        $order = $this->app->dataStorage->findOne('store/orders', ['_id' => $id]);
        if (!$order) {
            return $this->stop(404, 'Order not found');
        }

        $order['status'] = $status;
        if ($courier) {
            $order['courier'] = $courier;
        }
        if ($resi) {
            $order['resi'] = $resi;
        }
        
        if ($status === 'completed') {
            $order['payment_status'] = 'settled';
        } elseif ($status === 'refunded') {
            $order['payment_status'] = 'failed';
        }

        $this->app->dataStorage->save('store/orders', $order);

        // Sync with transactional database status
        if (!empty($order['transaction_id'])) {
            $tx = $this->app->dataStorage->findOne('midtrans/transactions', ['_id' => $order['transaction_id']]);
            if ($tx) {
                if ($status === 'completed') {
                    $tx['status'] = 'success';
                } elseif ($status === 'cancelled' || $status === 'refunded') {
                    $tx['status'] = 'failed';
                }
                $this->app->dataStorage->save('midtrans/transactions', $tx);
            }
        }

        $this->app->module('system')->log("Store Addon: Order {$order['order_id']} status updated to {$status}", 'store', 'info');

        return ['success' => true];
    }

    public function getDashboardStats() {
        $this->helper('session')->close();

        $productsRes = $this->getProducts();
        $ordersRes = $this->getOrders();

        $products = $productsRes['products'] ?? [];
        $orders = $ordersRes['orders'] ?? [];

        $totalSales = 0;
        $orderCount = \count($orders);
        $lowStockCount = 0;
        $pendingCount = 0;

        foreach ($orders as $order) {
            if (($order['payment_status'] ?? '') === 'settled' || ($order['status'] ?? '') === 'completed') {
                $totalSales += (float) ($order['total_amount'] ?? 0);
            }
            if (($order['status'] ?? '') === 'pending') {
                $pendingCount++;
            }
        }

        foreach ($products as $prod) {
            if (($prod['stock'] ?? 0) < 5) {
                $lowStockCount++;
            }
        }

        $avgOrderValue = $orderCount ? \round($totalSales / $orderCount) : 0;

        return [
            'totalSales' => $totalSales,
            'orderCount' => $orderCount,
            'avgOrderValue' => $avgOrderValue,
            'lowStockCount' => $lowStockCount,
            'pendingCount' => $pendingCount
        ];
    }

    public function getCustomers() {
        $this->helper('session')->close();
        
        $limit = intval($this->param('limit', 20));
        $page = intval($this->param('page', 1));
        $skip = ($page - 1) * $limit;
        $search = trim($this->param('search', ''));
        
        $criteria = [];
        if ($search) {
            $criteria['$or'] = [
                ['name' => ['$regex' => $search, '$options' => 'i']],
                ['email' => ['$regex' => $search, '$options' => 'i']]
            ];
        }
        
        $total = $this->app->dataStorage->count('store/customers', $criteria);
        $pages = ceil($total / $limit);
        
        $sortKey = $this->param('sort', 'created');
        $sortDir = intval($this->param('sort_dir', -1));
        $options = [
            'limit' => $limit,
            'skip' => $skip,
            'sort' => [$sortKey => $sortDir]
        ];
        if ($criteria) {
            $options['filter'] = $criteria;
        }
        
        $customers = $this->app->dataStorage->find('store/customers', $options)->toArray();
        $emails = array_map(fn($c) => $c['email'] ?? '', $customers);
        
        $orders = [];
        if (!empty($emails)) {
            $orders = $this->app->dataStorage->find('store/orders', [
                'filter' => ['customer_email' => ['$in' => $emails]]
            ])->toArray();
        }
        
        $statsMap = [];
        foreach ($orders as $order) {
            $email = $order['customer_email'] ?? '';
            if (!$email) continue;
            
            if (!isset($statsMap[$email])) {
                $statsMap[$email] = [
                    'orders_count' => 0,
                    'total_spend' => 0
                ];
            }
            
            $statsMap[$email]['orders_count']++;
            if (($order['payment_status'] ?? '') === 'settled') {
                $statsMap[$email]['total_spend'] += (float)($order['total_amount'] ?? 0);
            }
        }
        
        foreach ($customers as &$c) {
            $email = $c['email'] ?? '';
            $stats = $statsMap[$email] ?? ['orders_count' => 0, 'total_spend' => 0];
            $c['orders_count'] = $stats['orders_count'];
            $c['total_spend'] = $stats['total_spend'];
            
            unset($c['password']);
            unset($c['reset_token']);
            unset($c['reset_token_expires']);
        }
        
        return [
            'customers' => $customers,
            'count' => $total,
            'pages' => $pages ?: 1,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function getCustomerOrders() {
        $this->helper('session')->close();
        $email = $this->param('email');
        if (!$email) return $this->stop(400, 'Missing email');
        
        $orders = $this->app->dataStorage->find('store/orders', [
            'filter' => ['customer_email' => $email],
            'sort' => ['created' => -1]
        ])->toArray();
        
        return $orders;
    }

    public function getVouchers() {
        $this->helper('session')->close();
        
        $limit = intval($this->param('limit', 20));
        $page = intval($this->param('page', 1));
        if ($limit < 1) $limit = 20;
        $skip = ($page - 1) * $limit;
        $search = trim($this->param('search', ''));
        
        $criteria = [];
        if ($search) {
            $criteria['$or'] = [
                ['name' => ['$regex' => $search, '$options' => 'i']],
                ['code' => ['$regex' => $search, '$options' => 'i']]
            ];
        }
        
        $total = $this->app->dataStorage->count('store/vouchers', $criteria);
        $pages = ceil($total / $limit);
        
        $sortKey = $this->param('sort', 'code');
        $sortDir = intval($this->param('sort_dir', 1));
        $options = [
            'limit' => $limit,
            'skip' => $skip,
            'sort' => [$sortKey => $sortDir]
        ];
        if ($criteria) {
            $options['filter'] = $criteria;
        }
        
        $vouchers = $this->app->dataStorage->find('store/vouchers', $options)->toArray();
        
        return [
            'vouchers' => $vouchers,
            'count' => $total,
            'pages' => $pages ?: 1,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function saveVoucher() {
        $this->hasValidCsrfToken(true);
        $voucher = $this->param('voucher', []);
        
        // Validasi: hanya boleh 1 voucher yang memiliki show_in_topbar = true
        if (!empty($voucher['show_in_topbar'])) {
            $criteria = ['show_in_topbar' => true];
            if (!empty($voucher['_id'])) {
                $criteria['_id'] = ['$ne' => $voucher['_id']];
            }
            $existing = $this->app->dataStorage->findOne('store/vouchers', $criteria);
            if ($existing) {
                return $this->stop(['error' => "Voucher '{$existing['code']}' sudah aktif di promo topbar. Silakan matikan terlebih dahulu."], 400);
            }
        }

        if (empty($voucher['_id'])) {
            $this->app->dataStorage->insert('store/vouchers', $voucher);
        } else {
            $this->app->dataStorage->save('store/vouchers', $voucher);
        }
        return ['success' => true, 'voucher' => $voucher];
    }

    public function deleteVoucher() {
        $this->hasValidCsrfToken(true);
        $id = $this->param('id');
        if ($id) {
            $this->app->dataStorage->remove('store/vouchers', ['_id' => $id]);
        }
        return ['success' => true];
    }

    public function getSuppliers() {
        $this->helper('session')->close();
        
        $limit = intval($this->param('limit', 20));
        $page = intval($this->param('page', 1));
        if ($limit < 1) $limit = 20;
        $skip = ($page - 1) * $limit;
        $search = trim($this->param('search', ''));
        
        $criteria = [];
        if ($search) {
            $criteria['$or'] = [
                ['name' => ['$regex' => $search, '$options' => 'i']],
                ['email' => ['$regex' => $search, '$options' => 'i']],
                ['phone' => ['$regex' => $search, '$options' => 'i']],
                ['contact_person' => ['$regex' => $search, '$options' => 'i']]
            ];
        }
        
        $total = $this->app->dataStorage->count('store/suppliers', $criteria);
        $pages = ceil($total / $limit);
        
        $sortKey = $this->param('sort', 'name');
        $sortDir = intval($this->param('sort_dir', 1));
        $options = [
            'limit' => $limit,
            'skip' => $skip,
            'sort' => [$sortKey => $sortDir]
        ];
        if ($criteria) {
            $options['filter'] = $criteria;
        }
        
        $suppliers = $this->app->dataStorage->find('store/suppliers', $options)->toArray();
        
        return [
            'suppliers' => $suppliers,
            'count' => $total,
            'pages' => $pages ?: 1,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function saveSupplier() {
        $this->hasValidCsrfToken(true);
        $supplier = $this->param('supplier', []);
        
        if (empty($supplier['_id'])) {
            $this->app->dataStorage->insert('store/suppliers', $supplier);
        } else {
            $this->app->dataStorage->save('store/suppliers', $supplier);
        }
        return ['success' => true, 'supplier' => $supplier];
    }

    public function deleteSupplier() {
        $this->hasValidCsrfToken(true);
        $id = $this->param('id');
        if ($id) {
            $this->app->dataStorage->remove('store/suppliers', ['_id' => $id]);
        }
        return ['success' => true];
    }

    public function getPurchasing() {
        $this->helper('session')->close();
        return $this->app->dataStorage->find('store/purchasing', ['sort' => ['created' => -1]])->toArray();
    }

    public function savePurchasing() {
        $this->hasValidCsrfToken(true);
        $purchase = $this->param('purchase', []);
        $purchase['created'] = time();
        $purchase['status'] = 'pending';
        
        $this->app->dataStorage->insert('store/purchasing', $purchase);
        return ['success' => true, 'purchase' => $purchase];
    }

    public function receivePurchasing() {
        $this->hasValidCsrfToken(true);
        $id = $this->param('id');
        if (!$id) return $this->stop(400, 'Missing ID');
        
        $purchase = $this->app->dataStorage->findOne('store/purchasing', ['_id' => $id]);
        if (!$purchase || $purchase['status'] === 'received') {
            return ['success' => false, 'error' => 'Invalid purchasing order'];
        }
        
        $purchase['status'] = 'received';
        $purchase['received_at'] = time();
        
        $this->app->dataStorage->save('store/purchasing', $purchase);
        
        foreach ($purchase['items'] as $item) {
            $prodId = $item['product_id'] ?? null;
            $qty = intval($item['quantity'] ?? 0);
            if ($prodId && $qty > 0) {
                $product = $this->app->dataStorage->findOne('store/products', ['_id' => $prodId]);
                if ($product) {
                    $product['stock'] = intval($product['stock'] ?? 0) + $qty;
                    $this->app->dataStorage->save('store/products', $product);
                }
            }
        }
        
        return ['success' => true];
    }

    public function getReportsData() {
        $this->helper('session')->close();
        $orders = $this->app->dataStorage->find('store/orders')->toArray();
        
        $dailySales = [];
        $productSales = [];
        
        foreach ($orders as $order) {
            if ($order['payment_status'] !== 'settled' && $order['status'] !== 'completed') {
                continue;
            }
            
            $date = date('Y-m-d', $order['created']);
            $amount = floatval($order['total_amount']);
            
            $dailySales[$date] = ($dailySales[$date] ?? 0) + $amount;
            
            foreach ($order['items'] as $item) {
                $pName = $item['name'] ?? 'Unknown';
                $qty = intval($item['quantity'] ?? 1);
                $subtotal = floatval($item['price'] ?? 0) * $qty;
                
                $productSales[$pName] = ($productSales[$pName] ?? 0) + $subtotal;
            }
        }
        
        ksort($dailySales);
        arsort($productSales);
        $topProducts = array_slice($productSales, 0, 5, true);
        
        return [
            'daily' => $dailySales,
            'products' => $topProducts
        ];
    }

    public function getContent() {
        $this->helper('session')->close();
        $content = $this->app->dataStorage->findOne('store/content', ['_id' => 'homepage']) ?? [
            '_id' => 'homepage',
            'banners' => [],
            'faq' => '',
            'shipping_policy' => '',
            'about_us' => ''
        ];
        return $content;
    }

    public function saveContent() {
        $this->hasValidCsrfToken(true);
        $content = $this->param('content', []);
        $content['_id'] = 'homepage';
        
        $exists = $this->app->dataStorage->findOne('store/content', ['_id' => 'homepage']);
        if ($exists) {
            $this->app->dataStorage->save('store/content', $content);
        } else {
            $this->app->dataStorage->insert('store/content', $content);
        }
        
        return ['success' => true];
    }

    public function getSettings() {
        $this->helper('session')->close();
        $settings = $this->app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [
            '_id' => 'config',
            'shop_name' => 'Online Coffee Store',
            'shop_email' => 'store@example.com',
            'shop_phone' => '+62812345678',
            'shop_address' => 'Jakarta, Indonesia',
            'tax_percent' => 11,
            'currency' => 'IDR'
        ];
        return $settings;
    }

    public function saveSettings() {
        $this->hasValidCsrfToken(true);
        $settings = $this->param('settings', []);
        $settings['_id'] = 'config';
        
        $exists = $this->app->dataStorage->findOne('store/settings', ['_id' => 'config']);
        if ($exists) {
            $this->app->dataStorage->save('store/settings', $settings);
        } else {
            $this->app->dataStorage->insert('store/settings', $settings);
        }
        
        return ['success' => true];
    }

    public function toggleCustomerActive() {
        $this->hasValidCsrfToken(true);
        $id = $this->param('id');
        if (!$id) return $this->stop(400, 'Missing ID');
        
        $customer = $this->app->dataStorage->findOne('store/customers', ['_id' => $id]);
        if (!$customer) {
            return ['success' => false, 'error' => 'Customer not found'];
        }
        
        $customer['active'] = !($customer['active'] ?? true);
        $this->app->dataStorage->save('store/customers', $customer);
        
        return ['success' => true, 'active' => $customer['active']];
    }
}
