<?php

namespace Store\Controller;

use App\Controller\Base;

class Shop extends Base
{
    public function index()
    {
        return $this->render('store:views/shop/index.php');
    }

    public function tracker()
    {
        return $this->render('store:views/shop/tracker.php');
    }

    public function dashboard()
    {
        return $this->render('store:views/shop/dashboard.php');
    }

    public function about()
    {
        return $this->render('store:views/shop/about.php');
    }

    public function faq()
    {
        return $this->render('store:views/shop/faq.php');
    }

    public function security()
    {
        return $this->render('store:views/shop/security.php');
    }

    public function finishedOrder()
    {
        $orderId = \trim($this->app->param('order_id', ''));
        $transactionStatus = \trim($this->app->param('transaction_status', ''));
        $statusCode = \trim($this->app->param('status_code', ''));

        if ($orderId && $transactionStatus) {
            if (\in_array($transactionStatus, ['settlement', 'capture'])) {
                $internalStatus = 'success';
                $orderStatus = 'processing';
                $paymentStatus = 'settled';
            } elseif (\in_array($transactionStatus, ['pending', 'authorize'])) {
                $internalStatus = 'pending';
                $orderStatus = 'pending';
                $paymentStatus = 'pending';
            } else {
                $internalStatus = 'failed';
                $orderStatus = 'cancelled';
                $paymentStatus = 'failed';
            }

            $transaction = $this->app->dataStorage->findOne('midtrans/transactions', ['transaction_id' => $orderId]);
            if ($transaction && $transaction['status'] !== $internalStatus) {
                $oldStatus = $transaction['status'];
                $transaction['status'] = $internalStatus;
                $this->app->dataStorage->save('midtrans/transactions', $transaction);

                if (isset($this->app->helpers['eventStream'])) {
                    $this->helper('eventStream')->add('midtrans.transactions.updated', $transaction);
                }

                if ($internalStatus === 'success' && $oldStatus !== 'success') {
                    try {
                        $emailBody = $this->app->render('midtrans:views/emails/payment_success.php', [
                            'transaction' => $transaction,
                        ]);
                        $mailDir = $this->app->path('#tmp:') . '/midtrans-emails';
                        if (!\file_exists($mailDir)) {
                            @\mkdir($mailDir, 0755, true);
                        }
                        @\file_put_contents($mailDir . '/' . $orderId . '-success.html', $emailBody);
                        $this->app->mailer->mail(
                            $transaction['customer_email'],
                            'Payment Receipt - Order ' . $orderId,
                            $emailBody,
                        );
                    } catch (\Throwable $e) {
                    }
                }
            }

            $order = $this->app->dataStorage->findOne('store/orders', ['transaction_id' => $orderId]);
            if ($order) {
                $order['status'] = $orderStatus;
                $order['payment_status'] = $paymentStatus;
                $order['updated'] = \time();
                $this->app->dataStorage->save('store/orders', $order);
            }

            $this->app
                ->module('system')
                ->log(
                    "Finished Order: Transaction {$orderId} → status={$transactionStatus} (internal={$internalStatus})",
                    'store',
                    'info',
                );
        }

        return $this->render('store:views/shop/finished-order.php');
    }

    public function product($id = null)
    {
        if (!$id) {
            $this->app->response->status = 404;
            return 'Product not found';
        }

        $product = $this->app->dataStorage->findOne('store/products', ['_id' => $id]);
        if (!$product) {
            $this->app->response->status = 404;
            return 'Product not found';
        }

        $imageUrls = [];
        $images = isset($product['image']) ? array_filter(array_map('trim', explode(',', $product['image']))) : [];
        foreach ($images as $img) {
            if (\str_starts_with($img, 'assets://')) {
                $assetId = \str_replace('assets://', '', $img);
                $asset = $this->app->dataStorage->findOne('assets', ['_id' => $assetId]);
                if ($asset) {
                    $imageUrls[] = $this->app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                } else {
                    $imageUrls[] = $this->app->routeUrl('/assets/link/' . $assetId);
                }
            } else {
                $imageUrls[] = $img;
            }
        }
        $product['image_url'] = !empty($imageUrls) ? $imageUrls[0] : null;
        $product['image_urls'] = $imageUrls;

        return $this->render('store:views/shop/product.php', \compact('product'));
    }

    public function getProducts()
    {
        $search = \trim($this->param('search', ''));
        $category = \trim($this->param('category', ''));

        $criteria = [];
        if ($search) {
            $criteria['$or'] = [
                ['name' => ['$regex' => $search, '$options' => 'i']],
                ['sku' => ['$regex' => $search, '$options' => 'i']],
                ['description' => ['$regex' => $search, '$options' => 'i']],
            ];
        }

        if ($category && $category !== 'All') {
            $criteria['category'] = $category;
        }

        $products = $this->app->dataStorage->find('store/products', ['filter' => $criteria])->toArray();

        foreach ($products as &$prod) {
            $imageUrls = [];
            $images = isset($prod['image']) ? array_filter(array_map('trim', explode(',', $prod['image']))) : [];
            foreach ($images as $img) {
                if (\str_starts_with($img, 'assets://')) {
                    $id = \str_replace('assets://', '', $img);
                    $asset = $this->app->dataStorage->findOne('assets', ['_id' => $id]);
                    if ($asset) {
                        $asset['url'] = $this->app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                        $imageUrls[] = $asset['url'];
                    } else {
                        $imageUrls[] = $this->app->routeUrl('/assets/link/' . $id);
                    }
                } else {
                    $imageUrls[] = $img;
                }
            }
            $prod['image_url'] = !empty($imageUrls) ? $imageUrls[0] : null;
            $prod['image_urls'] = $imageUrls;
        }

        return $products;
    }

    public function getSettings()
    {
        $settings = $this->app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [
            '_id' => 'config',
            'shop_name' => 'Online Coffee Store',
            'shop_email' => 'store@example.com',
            'shop_phone' => '+62812345678',
            'shop_address' => 'Jakarta, Indonesia',
            'tax_percent' => 11,
            'currency' => 'IDR',
        ];

        $midtransSettings = $this->app->dataStorage->findOne('midtrans/settings', ['_id' => 'config']) ?? [];
        $settings['midtrans_client_key'] =
            ($midtransSettings['mode'] ?? 'sandbox') === 'production'
                ? $midtransSettings['production_client_key'] ?? ''
                : $midtransSettings['sandbox_client_key'] ?? '';
        $settings['midtrans_mode'] = $midtransSettings['mode'] ?? 'sandbox';

        return $settings;
    }

    public function getHomepageContent()
    {
        $content = $this->app->dataStorage->findOne('store/content', ['_id' => 'homepage']) ?? [
            '_id' => 'homepage',
            'banners' => [],
            'faq' => '',
            'shipping_policy' => '',
            'about_us' => '',
        ];

        $endTimeStr = $content['flash_sale_end_time'] ?? '';
        if ($endTimeStr) {
            $endTime = \strtotime($endTimeStr);
            if ($endTime && \time() >= $endTime) {
                $products = $this->app->dataStorage->find('store/products')->toArray();
                foreach ($products as $product) {
                    if (isset($product['original_price']) && $product['original_price'] > 0) {
                        $product['price'] = $product['original_price'];
                    }
                    $product['discount_percent'] = 0;
                    $this->app->dataStorage->save('store/products', $product);
                }
                $content['flash_sale_end_time'] = '';
                $content['flash_product_ids'] = [];
                $this->app->dataStorage->save('store/content', $content);
            }
        }

        if (isset($content['banners']) && \is_array($content['banners'])) {
            $resolvedBanners = [];
            foreach ($content['banners'] as $banner) {
                if ($banner && \str_starts_with($banner, 'assets://')) {
                    $id = \str_replace('assets://', '', $banner);
                    $asset = $this->app->dataStorage->findOne('assets', ['_id' => $id]);
                    if ($asset) {
                        $resolvedBanners[] = $this->app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                    } else {
                        $resolvedBanners[] = $this->app->routeUrl('/assets/link/' . $id);
                    }
                } else {
                    $resolvedBanners[] = $banner;
                }
            }
            $content['banners'] = $resolvedBanners;
        }

        if (isset($content['hero_image']) && \str_starts_with($content['hero_image'], 'assets://')) {
            $id = \str_replace('assets://', '', $content['hero_image']);
            $asset = $this->app->dataStorage->findOne('assets', ['_id' => $id]);
            if ($asset) {
                $content['hero_image_url'] = $this->app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
            } else {
                $content['hero_image_url'] = $this->app->routeUrl('/assets/link/' . $id);
            }
        } else {
            $content['hero_image_url'] =
                $content['hero_image'] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80';
        }

        if (isset($content['promo_image']) && \str_starts_with($content['promo_image'], 'assets://')) {
            $id = \str_replace('assets://', '', $content['promo_image']);
            $asset = $this->app->dataStorage->findOne('assets', ['_id' => $id]);
            if ($asset) {
                $content['promo_image_url'] = $this->app->fileStorage->getURL(
                    'uploads://' . \trim($asset['path'], '/'),
                );
            } else {
                $content['promo_image_url'] = $this->app->routeUrl('/assets/link/' . $id);
            }
        } else {
            $content['promo_image_url'] =
                $content['promo_image'] ?? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80';
        }

        if (isset($content['promo_banners']) && \is_array($content['promo_banners'])) {
            foreach ($content['promo_banners'] as &$slide) {
                if (!empty($slide['image']) && \str_starts_with($slide['image'], 'assets://')) {
                    $id = \str_replace('assets://', '', $slide['image']);
                    $asset = $this->app->dataStorage->findOne('assets', ['_id' => $id]);
                    if ($asset) {
                        $slide['image_url'] = $this->app->fileStorage->getURL(
                            'uploads://' . \trim($asset['path'], '/'),
                        );
                    } else {
                        $slide['image_url'] = $this->app->routeUrl('/assets/link/' . $id);
                    }
                } else {
                    $slide['image_url'] =
                        $slide['image'] ?? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80';
                }
            }
        }

        return $content;
    }

    public function validateVoucher()
    {
        $code = \trim($this->param('code', ''));
        if (!$code) {
            return ['valid' => false, 'message' => 'Voucher code is required'];
        }

        $voucher = $this->app->dataStorage->findOne('store/vouchers', ['code' => $code]);
        if (
            $voucher &&
            ($voucher['active'] === 'true' ||
                $voucher['active'] === true ||
                $voucher['active'] === 1 ||
                $voucher['active'] === '1')
        ) {
            return [
                'valid' => true,
                'code' => $voucher['code'],
                'type' => $voucher['type'],
                'value' => \floatval($voucher['value']),
            ];
        }

        return ['valid' => false, 'message' => 'Invalid or expired voucher code'];
    }

    public function checkout()
    {
        $customerName = \trim($this->param('customer_name', ''));
        $customerEmail = \trim($this->param('customer_email', ''));
        $customerPhone = \trim($this->param('customer_phone', ''));
        $customerAddress = \trim($this->param('customer_address', ''));
        $customerCity = \trim($this->param('customer_city', ''));
        $customerZip = \trim($this->param('customer_zip', ''));

        $itemsInput = $this->param('items');
        $voucherCode = \trim($this->param('voucher_code', ''));
        $courier = $this->param('courier', 'Manual');
        $shippingCost = \floatval($this->param('shipping_cost', 0));

        if (!$customerName || !$customerEmail || !$customerAddress || empty($itemsInput)) {
            return $this->stop(['error' => 'Missing customer details or empty cart items'], 400);
        }

        $items = [];
        $totalAmount = 0;

        foreach ($itemsInput as $item) {
            $prodId = $item['product_id'] ?? null;
            $qty = \intval($item['quantity'] ?? 0);

            if (!$prodId || $qty <= 0) {
                continue;
            }

            $product = $this->app->dataStorage->findOne('store/products', ['_id' => $prodId]);
            if (!$product) {
                return $this->stop(['error' => "Product not found: {$prodId}"], 400);
            }

            if ($product['stock'] < $qty) {
                return $this->stop(
                    ['error' => "Product {$product['name']} is out of stock (Available: {$product['stock']})"],
                    400,
                );
            }

            $items[] = [
                'product_id' => $prodId,
                'name' => $product['name'],
                'price' => \floatval($product['price']),
                'quantity' => $qty,
            ];

            $totalAmount += \floatval($product['price']) * $qty;
        }

        if (empty($items)) {
            return $this->stop(['error' => 'No valid items in the cart'], 400);
        }

        $discountAmount = 0;
        if ($voucherCode) {
            $voucher = $this->app->dataStorage->findOne('store/vouchers', ['code' => $voucherCode]);
            if (
                $voucher &&
                ($voucher['active'] === 'true' ||
                    $voucher['active'] === true ||
                    $voucher['active'] === 1 ||
                    $voucher['active'] === '1')
            ) {
                if ($voucher['type'] === 'percent') {
                    $discountAmount = $totalAmount * (\floatval($voucher['value']) / 100);
                } else {
                    $discountAmount = \floatval($voucher['value']);
                }
                if ($discountAmount > $totalAmount) {
                    $discountAmount = $totalAmount;
                }
            }
        }

        $storeSettings = $this->app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [];
        $taxPercent = \floatval($storeSettings['tax_percent'] ?? 11);
        $taxAmount = ($totalAmount - $discountAmount) * ($taxPercent / 100);

        $grandTotal = $totalAmount - $discountAmount + $taxAmount + $shippingCost;

        foreach ($items as $item) {
            $product = $this->app->dataStorage->findOne('store/products', ['_id' => $item['product_id']]);
            $product['stock'] -= $item['quantity'];
            $this->app->dataStorage->save('store/products', $product);
        }

        $customer = $this->app->dataStorage->findOne('store/customers', ['email' => $customerEmail]);
        if (!$customer) {
            $customer = [
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
                'address' => $customerAddress,
                'city' => $customerCity,
                'zip' => $customerZip,
                'active' => true,
                'created' => \time(),
            ];
            $this->app->dataStorage->insert('store/customers', $customer);
        }

        $orderId = 'ORD-' . \strtoupper(\bin2hex(\random_bytes(4)));
        $transactionId = 'TX-' . \strtoupper(\dechex(\time())) . \strtoupper(\dechex(\rand(1000, 9999)));

        $order = [
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
            'customer_city' => $customerCity,
            'customer_zip' => $customerZip,
            'items' => $items,
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
            'redirect_url' => '',
            'created' => \time(),
            'updated' => \time(),
        ];

        $midtransSettings = $this->app->dataStorage->findOne('midtrans/settings', ['_id' => 'config']) ?? [];
        $mode = $midtransSettings['mode'] ?? 'sandbox';
        $serverKey =
            $mode === 'production'
                ? $midtransSettings['production_server_key'] ?? ''
                : $midtransSettings['sandbox_server_key'] ?? '';

        if ($serverKey) {
            $snapUrl =
                $mode === 'production'
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $itemDetails = \array_map(function ($item) {
                return [
                    'id' => $item['product_id'],
                    'price' => (int) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'name' => \substr($item['name'], 0, 50),
                ];
            }, $items);

            if ($discountAmount > 0) {
                $itemDetails[] = [
                    'id' => 'VOUCHER-' . $voucherCode,
                    'price' => -(int) $discountAmount,
                    'quantity' => 1,
                    'name' => 'Promo Code: ' . $voucherCode,
                ];
            }
            if ($taxAmount > 0) {
                $itemDetails[] = [
                    'id' => 'TAX',
                    'price' => (int) $taxAmount,
                    'quantity' => 1,
                    'name' => 'Tax (' . $taxPercent . '%)',
                ];
            }
            if ($shippingCost > 0) {
                $itemDetails[] = [
                    'id' => 'SHIPPING',
                    'price' => (int) $shippingCost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost (' . $courier . ')',
                ];
            }

            $baseUrl =
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') .
                '://' .
                ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $finishRedirectUrl = $baseUrl . '/shop/finished-order';

            $snapPayload = [
                'transaction_details' => [
                    'order_id' => $transactionId,
                    'gross_amount' => (int) $grandTotal,
                ],
                'customer_details' => [
                    'first_name' => $customerName,
                    'email' => $customerEmail,
                    'phone' => $customerPhone,
                ],
                'item_details' => $itemDetails,
                'callbacks' => [
                    'finish' => $finishRedirectUrl,
                ],
            ];

            $ch = \curl_init();
            \curl_setopt($ch, CURLOPT_URL, $snapUrl);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, CURLOPT_POST, true);
            \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($snapPayload));
            \curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . \base64_encode($serverKey . ':'),
            ]);
            \curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            \curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            \curl_setopt($ch, CURLOPT_ENCODING, '');
            $snapResponse = \curl_exec($ch);
            $snapHttpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (\PHP_VERSION_ID < 80500) {
                \curl_close($ch);
            }

            if ($snapHttpCode === 200 || $snapHttpCode === 201) {
                $snapData = \json_decode($snapResponse, true);
                if (isset($snapData['redirect_url'])) {
                    $order['redirect_url'] = $snapData['redirect_url'];
                }
                if (isset($snapData['token'])) {
                    $order['snap_token'] = $snapData['token'];
                }
            }
        }

        $this->app->dataStorage->save('store/orders', $order);

        $midtransTransaction = [
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'amount' => (int) $grandTotal,
            'payment_method' => 'Midtrans Snap',
            'status' => 'pending',
            'snap_token' => $order['snap_token'] ?? '',
            'redirect_url' => $order['redirect_url'] ?? '',
            'created' => \time(),
        ];
        $this->app->dataStorage->save('midtrans/transactions', $midtransTransaction);

        if (isset($this->app->helpers['eventStream'])) {
            $this->helper('eventStream')->add('midtrans.transactions.updated', $midtransTransaction);
        }

        $this->app
            ->module('system')
            ->log(
                "Store Checkout: Order {$orderId} / Transaction {$transactionId} created for {$customerName} (Total: IDR {$grandTotal})",
                'store',
                'info',
                $order,
            );

        try {
            $emailBody = $this->app->render('midtrans:views/emails/payment_charge.php', [
                'transaction' => $midtransTransaction,
            ]);

            $mailDir = $this->app->path('#tmp:') . '/midtrans-emails';
            if (!\file_exists($mailDir)) {
                @\mkdir($mailDir, 0755, true);
            }

            @\file_put_contents($mailDir . '/' . $transactionId . '-pending.html', $emailBody);

            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
            $parts = \explode(':', $host);
            $ip = $parts[0];
            $port = isset($parts[1]) ? (int) $parts[1] : 80;

            $secToken = \md5($transactionId . $this->app->retrieve('sec-key'));
            $fp = @\fsockopen($ip, $port, $errno, $errstr, 0.5);
            if ($fp) {
                $path = $this->app->routeUrl('/shop/sendTransactionEmail');
                $qs = 'tx_id=' . \urlencode($transactionId) . '&token=' . \urlencode($secToken);
                $req = "GET {$path}?{$qs} HTTP/1.1\r\nHost: {$host}\r\nConnection: Close\r\n\r\n";
                \fwrite($fp, $req);
                \fclose($fp);
            } else {
                $this->app->mailer->mail($customerEmail, 'Payment Details - Order ' . $transactionId, $emailBody);
            }
        } catch (\Throwable $e) {
        }

        return ['success' => true, 'order' => $order];
    }

    public function checkOrderStatus()
    {
        $email = \trim($this->param('email', ''));
        $orderId = \trim($this->param('order_id', ''));

        if (!$email || !$orderId) {
            return $this->stop(['error' => 'Email and Order ID are required'], 400);
        }

        $order = $this->app->dataStorage->findOne('store/orders', [
            'customer_email' => $email,
            'order_id' => $orderId,
        ]);

        if (!$order) {
            return $this->stop(['error' => 'Order not found or details do not match'], 404);
        }

        return $order;
    }

    public function sendEmailBackground()
    {
        $orderId = \trim($this->param('order_id', ''));
        $email = \trim($this->param('email', ''));
        $token = \trim($this->param('token', ''));

        if (!$orderId || !$email || !$token) {
            return $this->stop(['error' => 'Missing parameters'], 400);
        }

        if ($token !== \md5($orderId . $this->app->retrieve('sec-key'))) {
            return $this->stop(['error' => 'Unauthorized'], 403);
        }

        $emailFile = $this->app->path('#tmp:') . "/midtrans-emails/order-{$orderId}.html";
        if (!\file_exists($emailFile)) {
            return ['success' => false, 'error' => 'Receipt file not found'];
        }

        try {
            $this->app->mailer->mail(
                $email,
                "Your Order Checkout Receipt ({$orderId})",
                \file_get_contents($emailFile),
            );
            @\unlink($emailFile);
        } catch (\Throwable $e) {
        }

        return ['success' => true];
    }

    public function sendTransactionEmail()
    {
        $txId = \trim($this->param('tx_id', ''));
        $token = \trim($this->param('token', ''));

        if (!$txId || !$token) {
            return $this->stop(['error' => 'Missing parameters'], 400);
        }

        if ($token !== \md5($txId . $this->app->retrieve('sec-key'))) {
            return $this->stop(['error' => 'Unauthorized'], 403);
        }

        $emailFile = $this->app->path('#tmp:') . '/midtrans-emails/' . $txId . '-pending.html';
        if (!\file_exists($emailFile)) {
            return ['success' => false, 'error' => 'Email file not found'];
        }

        $transaction = $this->app->dataStorage->findOne('midtrans/transactions', ['transaction_id' => $txId]);
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        try {
            $this->app->mailer->mail(
                $transaction['customer_email'],
                'Payment Details - Order ' . $txId,
                \file_get_contents($emailFile),
            );
        } catch (\Throwable $e) {
        }

        return ['success' => true];
    }

    public function customerLogin()
    {
        if (\session_status() === PHP_SESSION_NONE) {
            \session_start();
        }
        $email = \trim($this->param('email', ''));
        $password = \trim($this->param('password', ''));

        if (!$email || !$password) {
            return $this->stop(['error' => 'Email and Password are required'], 400);
        }

        $customer = $this->app->dataStorage->findOne('store/customers', ['email' => $email]);
        if (!$customer) {
            return $this->stop(['error' => 'Invalid email or password'], 400);
        }

        if (isset($customer['active']) && ($customer['active'] === false || $customer['active'] === 'false')) {
            return $this->stop(['error' => 'Account is suspended. Please contact support.'], 403);
        }

        if (!\password_verify($password, $customer['password'])) {
            return $this->stop(['error' => 'Invalid email or password'], 400);
        }

        unset($customer['password']);
        $_SESSION['store_customer'] = $customer;

        return ['success' => true, 'customer' => $customer];
    }

    public function customerRegister()
    {
        if (\session_status() === PHP_SESSION_NONE) {
            \session_start();
        }
        $name = \trim($this->param('name', ''));
        $email = \trim($this->param('email', ''));
        $password = \trim($this->param('password', ''));
        $phone = \trim($this->param('phone', ''));
        $address = \trim($this->param('address', ''));
        $city = \trim($this->param('city', ''));
        $zip = \trim($this->param('zip', ''));

        if (!$name || !$email || !$password) {
            return $this->stop(['error' => 'Name, Email and Password are required'], 400);
        }

        $existing = $this->app->dataStorage->findOne('store/customers', ['email' => $email]);
        if ($existing) {
            return $this->stop(['error' => 'Email address is already registered'], 400);
        }

        $customer = [
            'name' => $name,
            'email' => $email,
            'password' => \password_hash($password, PASSWORD_DEFAULT),
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'zip' => $zip,
            'active' => true,
            'created' => \time(),
        ];

        $this->app->dataStorage->insert('store/customers', $customer);

        unset($customer['password']);
        $_SESSION['store_customer'] = $customer;

        return ['success' => true, 'customer' => $customer];
    }

    public function customerLogout()
    {
        if (\session_status() === PHP_SESSION_NONE) {
            \session_start();
        }
        unset($_SESSION['store_customer']);
        return ['success' => true];
    }

    public function customerForgotPassword()
    {
        $email = \trim($this->param('email', ''));
        if (!$email) {
            return $this->stop(['error' => 'Email address is required'], 400);
        }

        $customer = $this->app->dataStorage->findOne('store/customers', ['email' => $email]);
        if (!$customer) {
            return [
                'success' => true,
                'message' => 'If your email is registered, a password reset link has been sent.',
            ];
        }

        $resetToken = \bin2hex(\random_bytes(16));
        $customer['reset_token'] = $resetToken;
        $customer['reset_token_expire'] = \time() + 3600;
        $this->app->dataStorage->save('store/customers', $customer);

        try {
            $midtransSettings = $this->app->dataStorage->findOne('midtrans/settings', ['_id' => 'config']) ?? [];
            $mode = $midtransSettings['mode'] ?? 'sandbox';
            $serverKey =
                $mode === 'production'
                    ? $midtransSettings['production_server_key'] ?? ''
                    : $midtransSettings['sandbox_server_key'] ?? '';

            $resetLink =
                $this->app->routeUrl('/shop/dashboard') . "?reset_token={$resetToken}&email=" . \urlencode($email);

            $emailBody = "<h1>Reset Your Password</h1>
                          <p>Hi {$customer['name']},</p>
                          <p>We received a request to reset your password. Click the link below to set a new password:</p>
                          <p><a href='{$resetLink}' style='padding: 10px 20px; background: #3a86ff; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a></p>
                          <p>If you didn't request a password reset, you can safely ignore this email.</p>";

            $emailDir = APP_DIR . '/storage/tmp/midtrans-emails';
            if (!\file_exists($emailDir)) {
                \mkdir($emailDir, 0777, true);
            }
            $tempId = 'reset-' . \bin2hex(\random_bytes(8));
            \file_put_contents("{$emailDir}/order-{$tempId}.html", $emailBody);

            if ($serverKey) {
                $secToken = \md5($tempId . $this->app->retrieve('sec-key'));
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
                $parts = \explode(':', $host);
                $ip = $parts[0];
                $port = isset($parts[1]) ? \intval($parts[1]) : 80;

                $fp = @\fsockopen($ip, $port, $errno, $errstr, 0.5);
                if ($fp) {
                    $path = $this->app->routeUrl('/shop/sendEmailBackground');
                    $out =
                        "GET {$path}?order_id={$tempId}&email=" .
                        \urlencode($email) .
                        "&token={$secToken} HTTP/1.1\r\n";
                    $out .= "Host: {$host}\r\n";
                    $out .= "Connection: Close\r\n\r\n";
                    \fwrite($fp, $out);
                    \fclose($fp);
                } else {
                    $this->app->mailer->mail($email, 'Reset Your Account Password', $emailBody);
                }
            }
        } catch (\Throwable $e) {
        }

        return ['success' => true, 'message' => 'If your email is registered, a password reset link has been sent.'];
    }

    public function customerResetPassword()
    {
        $email = \trim($this->param('email', ''));
        $token = \trim($this->param('token', ''));
        $password = \trim($this->param('password', ''));

        if (!$email || !$token || !$password) {
            return $this->stop(['error' => 'All fields are required'], 400);
        }

        $customer = $this->app->dataStorage->findOne('store/customers', [
            'email' => $email,
            'reset_token' => $token,
        ]);

        if (!$customer) {
            return $this->stop(['error' => 'Invalid or expired reset token'], 400);
        }

        if (isset($customer['reset_token_expire']) && \time() > $customer['reset_token_expire']) {
            return $this->stop(['error' => 'Reset token has expired'], 400);
        }

        $customer['password'] = \password_hash($password, PASSWORD_DEFAULT);
        unset($customer['reset_token']);
        unset($customer['reset_token_expire']);
        $this->app->dataStorage->save('store/customers', $customer);

        return ['success' => true, 'message' => 'Password reset successfully. You can now login.'];
    }

    public function getCustomerDashboard()
    {
        if (\session_status() === PHP_SESSION_NONE) {
            \session_start();
        }

        $customer = $_SESSION['store_customer'] ?? null;
        if (!$customer) {
            return ['logged_in' => false];
        }

        $custDb = $this->app->dataStorage->findOne('store/customers', ['email' => $customer['email']]);
        if (!$custDb || (isset($custDb['active']) && ($custDb['active'] === false || $custDb['active'] === 'false'))) {
            unset($_SESSION['store_customer']);
            return ['logged_in' => false];
        }

        unset($custDb['password']);

        $orders = $this->app->dataStorage
            ->find('store/orders', [
                'filter' => ['customer_email' => $customer['email']],
                'sort' => ['created' => -1],
            ])
            ->toArray();

        return [
            'logged_in' => true,
            'customer' => $custDb,
            'orders' => $orders,
        ];
    }

    public function updateCustomerProfile()
    {
        if (\session_status() === PHP_SESSION_NONE) {
            \session_start();
        }

        $customerSession = $_SESSION['store_customer'] ?? null;
        if (!$customerSession) {
            return $this->stop(['error' => 'Unauthorized action'], 401);
        }

        $custDb = $this->app->dataStorage->findOne('store/customers', ['email' => $customerSession['email']]);
        if (!$custDb) {
            return $this->stop(['error' => 'Customer profile not found'], 404);
        }

        $name = \trim($this->param('name', ''));
        $phone = \trim($this->param('phone', ''));
        $address = \trim($this->param('address', ''));
        $city = \trim($this->param('city', ''));
        $zip = \trim($this->param('zip', ''));
        $password = \trim($this->param('password', ''));

        if (!$name) {
            return $this->stop(['error' => 'Name cannot be empty'], 400);
        }

        $custDb['name'] = $name;
        $custDb['phone'] = $phone;
        $custDb['address'] = $address;
        $custDb['city'] = $city;
        $custDb['zip'] = $zip;

        if ($password) {
            $custDb['password'] = \password_hash($password, PASSWORD_DEFAULT);
        }

        $this->app->dataStorage->save('store/customers', $custDb);

        unset($custDb['password']);
        $_SESSION['store_customer'] = $custDb;

        return ['success' => true, 'customer' => $custDb];
    }

    public function resetDiscounts()
    {
        $content = $this->app->dataStorage->findOne('store/content', ['_id' => 'homepage']) ?? [];
        $endTimeStr = $content['flash_sale_end_time'] ?? '';

        if ($endTimeStr) {
            $endTime = \strtotime($endTimeStr);
            if ($endTime && \time() >= $endTime) {
                $products = $this->app->dataStorage->find('store/products')->toArray();
                foreach ($products as $product) {
                    if (isset($product['original_price']) && $product['original_price'] > 0) {
                        $product['price'] = $product['original_price'];
                    }
                    $product['discount_percent'] = 0;
                    $this->app->dataStorage->save('store/products', $product);
                }

                $content['flash_sale_end_time'] = '';
                $content['flash_product_ids'] = [];
                $this->app->dataStorage->save('store/content', $content);

                return ['success' => true, 'message' => 'Discounts reset successfully'];
            }
        }

        return ['success' => false, 'message' => 'No active flash sale or not expired yet'];
    }
}
