<?php

$this->on('restApi.config', function ($restApi) {
    $restApi->addEndPoint('/store/products', [
        'GET' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/products', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }
            $products = $app->dataStorage->find('store/products')->toArray();

            foreach ($products as &$prod) {
                if (isset($prod['image']) && \str_starts_with($prod['image'], 'assets://')) {
                    $id = \str_replace('assets://', '', $prod['image']);
                    $asset = $app->dataStorage->findOne('assets', ['_id' => $id]);
                    if ($asset) {
                        $asset['url'] = $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                        $prod['image_asset'] = $asset;
                        $prod['image_url'] = $asset['url'];
                    } else {
                        $prod['image_asset'] = null;
                        $prod['image_url'] = $app->routeUrl('/assets/link/' . $id);
                    }
                } else {
                    $prod['image_asset'] = null;
                    $prod['image_url'] = $prod['image'] ?? null;
                }
            }

            return $products;
        },
    ]);

    $restApi->addEndPoint('/store/content', [
        'GET' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/content', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }

            $content = $app->dataStorage->findOne('store/content', ['_id' => 'homepage']) ?? [
                '_id' => 'homepage',
                'banners' => [],
                'faq' => '',
                'shipping_policy' => '',
                'about_us' => '',
            ];

            if (isset($content['banners']) && is_array($content['banners'])) {
                $resolvedBanners = [];
                foreach ($content['banners'] as $banner) {
                    if ($banner && \str_starts_with($banner, 'assets://')) {
                        $id = \str_replace('assets://', '', $banner);
                        $asset = $app->dataStorage->findOne('assets', ['_id' => $id]);
                        if ($asset) {
                            $resolvedBanners[] = $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                        } else {
                            $resolvedBanners[] = $app->routeUrl('/assets/link/' . $id);
                        }
                    } else {
                        $resolvedBanners[] = $banner;
                    }
                }
                $content['banners'] = $resolvedBanners;
            }
            return $content;
        },
    ]);

    $restApi->addEndPoint('/store/order', [
        'POST' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/order', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }
            $customerName = $app->param('customer_name');
            $customerEmail = $app->param('customer_email');
            $itemsInput = $app->param('items');
            $voucherCode = $app->param('voucher_code', '');
            $courier = $app->param('courier', 'Manual');
            $shippingCost = floatval($app->param('shipping_cost', 0));

            if (!$customerName || !$customerEmail || empty($itemsInput)) {
                $app->response->status = 400;
                return ['error' => 'Missing customer details or items list'];
            }

            $items = [];
            $totalAmount = 0;

            foreach ($itemsInput as $item) {
                $prodId = $item['product_id'] ?? null;
                $qty = intval($item['quantity'] ?? 0);

                if (!$prodId || $qty <= 0) {
                    continue;
                }

                $product = $app->dataStorage->findOne('store/products', ['_id' => $prodId]);
                if (!$product) {
                    $app->response->status = 400;
                    return ['error' => "Product not found: {$prodId}"];
                }

                if ($product['stock'] < $qty) {
                    $app->response->status = 400;
                    return ['error' => "Product {$product['name']} is out of stock (Available: {$product['stock']})"];
                }

                $items[] = [
                    'product_id' => $prodId,
                    'name' => $product['name'],
                    'price' => floatval($product['price']),
                    'quantity' => $qty,
                ];

                $totalAmount += floatval($product['price']) * $qty;
            }

            if (empty($items)) {
                $app->response->status = 400;
                return ['error' => 'No valid items provided'];
            }

            $discountAmount = 0;
            if ($voucherCode) {
                $voucher = $app->dataStorage->findOne('store/vouchers', ['code' => $voucherCode]);
                if (
                    $voucher &&
                    ($voucher['active'] === 'true' ||
                        $voucher['active'] === true ||
                        $voucher['active'] === 1 ||
                        $voucher['active'] === '1')
                ) {
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

            $storeSettings = $app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [];
            $taxPercent = floatval($storeSettings['tax_percent'] ?? 0);
            $taxAmount = ($totalAmount - $discountAmount) * ($taxPercent / 100);

            $grandTotal = $totalAmount - $discountAmount + $taxAmount + $shippingCost;

            foreach ($items as $item) {
                $product = $app->dataStorage->findOne('store/products', ['_id' => $item['product_id']]);
                $product['stock'] -= $item['quantity'];
                $app->dataStorage->save('store/products', $product);
            }

            $orderId = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
            $transactionId = 'TX-' . strtoupper(dechex(time())) . strtoupper(dechex(rand(1000, 9999)));
            $order = [
                'order_id' => $orderId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
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
                'created' => time(),
                'updated' => time(),
            ];

            $midtransSettings = $app->dataStorage->findOne('midtrans/settings', ['_id' => 'config']) ?? [];
            $mode = $midtransSettings['mode'] ?? 'sandbox';
            $serverKey =
                $mode === 'production'
                    ? $midtransSettings['production_server_key'] ?? ''
                    : $midtransSettings['sandbox_server_key'] ?? '';

            if ($serverKey) {
                $url =
                    $mode === 'production'
                        ? 'https://app.midtrans.com/snap/v1/transactions'
                        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

                $itemDetails = array_map(function ($item) {
                    return [
                        'id' => $item['product_id'],
                        'price' => (int) $item['price'],
                        'quantity' => (int) $item['quantity'],
                        'name' => substr($item['name'], 0, 50),
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

                $payload = [
                    'transaction_details' => [
                        'order_id' => $transactionId,
                        'gross_amount' => intval($grandTotal),
                    ],
                    'customer_details' => [
                        'first_name' => $customerName,
                        'email' => $customerEmail,
                    ],
                    'item_details' => $itemDetails,
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Basic ' . base64_encode($serverKey . ':'),
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (\PHP_VERSION_ID < 80500) {
                    curl_close($ch);
                }

                if ($httpCode === 201 || $httpCode === 200) {
                    $resData = json_decode($response, true);
                    if (isset($resData['redirect_url'])) {
                        $order['redirect_url'] = $resData['redirect_url'];
                    }
                }
            }

            $app->dataStorage->save('store/orders', $order);

            $app->trigger('midtrans.log', [
                'event' => 'order_created',
                'message' => "Order {$orderId} created for {$customerName} (Total: IDR {$grandTotal})",
                'data' => $order,
            ]);

            try {
                $emailBody = "<h1>Order {$orderId} Received!</h1>
                              <p>Thank you for shopping. Total amount: IDR {$grandTotal}.</p>";
                if ($order['redirect_url']) {
                    $emailBody .= "<p><a href='{$order['redirect_url']}' style='padding: 10px 20px; background: #3a86ff; color: #fff; text-decoration: none; border-radius: 5px;'>Pay with Midtrans</a></p>";
                }

                $emailDir = APP_DIR . '/storage/tmp/midtrans-emails';
                if (!\file_exists($emailDir)) {
                    \mkdir($emailDir, 0777, true);
                }
                \file_put_contents("{$emailDir}/order-{$orderId}.html", $emailBody);

                if ($serverKey) {
                    $app->mailer->mail($customerEmail, "Your Order Checkout Receipt ({$orderId})", $emailBody);
                }
            } catch (\Throwable $e) {
            }

            if (isset($app->helpers['eventStream'])) {
                $app->helper('eventStream')->trigger('midtrans.transactions.updated', []);
            }

            return ['success' => true, 'order' => $order];
        },
    ]);

    $restApi->addEndPoint('/store/customers', [
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }

            $limit = intval($app->param('limit', 20));
            $page = intval($app->param('page', 1));
            $skip = ($page - 1) * $limit;
            $search = trim($app->param('search', ''));

            $criteria = [];
            if ($search) {
                $criteria['$or'] = [
                    ['name' => ['$regex' => $search, '$options' => 'i']],
                    ['email' => ['$regex' => $search, '$options' => 'i']],
                ];
            }

            $total = $app->dataStorage->count('store/customers', $criteria);
            $pages = ceil($total / $limit);

            $options = [
                'limit' => $limit,
                'skip' => $skip,
                'sort' => ['created' => -1],
            ];
            if ($criteria) {
                $options['filter'] = $criteria;
            }

            $customers = $app->dataStorage->find('store/customers', $options)->toArray();
            $emails = array_map(fn($c) => $c['email'] ?? '', $customers);

            $orders = [];
            if (!empty($emails)) {
                $orders = $app->dataStorage
                    ->find('store/orders', [
                        'filter' => ['customer_email' => ['$in' => $emails]],
                    ])
                    ->toArray();
            }

            $statsMap = [];
            foreach ($orders as $order) {
                $email = $order['customer_email'] ?? '';
                if (!$email) {
                    continue;
                }

                if (!isset($statsMap[$email])) {
                    $statsMap[$email] = [
                        'orders_count' => 0,
                        'total_spend' => 0,
                    ];
                }

                $statsMap[$email]['orders_count']++;
                if (($order['payment_status'] ?? '') === 'settled') {
                    $statsMap[$email]['total_spend'] += (float) ($order['total_amount'] ?? 0);
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
                'limit' => $limit,
            ];
        },
    ]);

    $restApi->addEndPoint('/store/vouchers', [
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }
            return $app->dataStorage->find('store/vouchers')->toArray();
        },
    ]);

    $restApi->addEndPoint('/store/suppliers', [
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }
            return $app->dataStorage->find('store/suppliers')->toArray();
        },
    ]);

    $restApi->addEndPoint('/store/reports', [
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }
            $orders = $app->dataStorage->find('store/orders')->toArray();
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
                'products' => $topProducts,
            ];
        },
    ]);

    $restApi->addEndPoint('/store/settings', [
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }
            $settings = $app->dataStorage->findOne('store/settings', ['_id' => 'config']) ?? [
                '_id' => 'config',
                'shop_name' => 'Online Coffee Store',
                'shop_email' => 'store@example.com',
                'shop_phone' => '+62812345678',
                'shop_address' => 'Jakarta, Indonesia',
                'tax_percent' => 11,
                'currency' => 'IDR',
            ];
            return $settings;
        },
    ]);

    $restApi->addEndPoint('/store/auth/register', [
        'POST' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/auth', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }

            $name = trim($app->param('name', ''));
            $email = trim($app->param('email', ''));
            $password = $app->param('password', '');
            $phone = trim($app->param('phone', ''));
            $address = trim($app->param('address', ''));
            $city = trim($app->param('city', ''));
            $zip = trim($app->param('zip', ''));

            if (!$name || !$email || !$password) {
                $app->response->status = 400;
                return ['error' => 'Name, email, and password are required.'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $app->response->status = 400;
                return ['error' => 'Invalid email address.'];
            }

            $existing = $app->dataStorage->findOne('store/customers', ['email' => $email]);
            if ($existing) {
                $app->response->status = 400;
                return ['error' => 'Email already registered.'];
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $customer = [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'zip' => $zip,
                'active' => true,
                'created' => time(),
            ];

            $app->dataStorage->insert('store/customers', $customer);

            return [
                'success' => true,
                'customer' => [
                    'name' => $name,
                    'email' => $email,
                ],
            ];
        },
    ]);

    $restApi->addEndPoint('/store/auth/login', [
        'POST' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/auth', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }
            $email = trim($app->param('email', ''));
            $password = $app->param('password', '');

            if (!$email || !$password) {
                $app->response->status = 400;
                return ['error' => 'Email and password are required.'];
            }

            $customer = $app->dataStorage->findOne('store/customers', ['email' => $email, 'active' => true]);

            if (!$customer || !password_verify($password, $customer['password'])) {
                $app->response->status = 401;
                return ['error' => 'Invalid credentials.'];
            }

            $payload = [
                'id' => $customer['_id'],
                'email' => $customer['email'],
                'role' => 'customer',
                'exp' => time() + 86400 * 30,
            ];

            $token = $app->helper('jwt')->create($payload);

            return [
                'success' => true,
                'token' => $token,
                'customer' => [
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                ],
            ];
        },
    ]);

    $restApi->addEndPoint('/store/auth/forgot-password', [
        'POST' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/auth', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }
            $email = trim($app->param('email', ''));
            $redirectUrl = $app->param('redirect_url', '');

            if (!$email) {
                $app->response->status = 400;
                return ['error' => 'Email is required.'];
            }

            $customer = $app->dataStorage->findOne('store/customers', ['email' => $email, 'active' => true]);

            if (!$customer) {
                $app->response->status = 400;
                return ['error' => 'Customer not found.'];
            }

            $resetToken = bin2hex(random_bytes(32));
            $customer['reset_token'] = $resetToken;
            $customer['reset_token_expires'] = time() + 3600;

            $app->dataStorage->save('store/customers', $customer);

            $resetLink = $redirectUrl
                ? "{$redirectUrl}?token={$resetToken}"
                : $app->routeUrl("/store/reset-password?token={$resetToken}", true);
            $emailBody = '<h1>Password Reset Request</h1>';
            $emailBody .= "<p>Hello {$customer['name']},</p>";
            $emailBody .=
                '<p>We received a request to reset your password. Click the link below to set a new password:</p>';
            $emailBody .= "<p><a href='{$resetLink}'>Reset Password</a></p>";
            $emailBody .= '<p>This link will expire in 1 hour.</p>';

            try {
                $previewDir = APP_DIR . '/storage/tmp/store-emails';
                if (!file_exists($previewDir)) {
                    mkdir($previewDir, 0777, true);
                }
                file_put_contents("{$previewDir}/reset-{$customer['email']}.html", $emailBody);
            } catch (\Exception $e) {
            }

            try {
                $mailer = $app->helper('mailer');
                $mailer->send([
                    'to' => $email,
                    'subject' => 'Store Password Reset Request',
                    'body' => $emailBody,
                ]);
            } catch (\Exception $e) {
            }

            return [
                'success' => true,
                'message' => 'Reset email sent.',
            ];
        },
    ]);

    $restApi->addEndPoint('/store/auth/reset-password', [
        'POST' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/auth', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }
            $token = $app->param('token', '');
            $password = $app->param('password', '');

            if (!$token || !$password) {
                $app->response->status = 400;
                return ['error' => 'Token and password are required.'];
            }

            $customer = $app->dataStorage->findOne('store/customers', ['reset_token' => $token]);

            if (!$customer) {
                $app->response->status = 400;
                return ['error' => 'Invalid token.'];
            }

            if (time() > ($customer['reset_token_expires'] ?? 0)) {
                $app->response->status = 400;
                return ['error' => 'Token has expired.'];
            }

            $customer['password'] = password_hash($password, PASSWORD_DEFAULT);
            unset($customer['reset_token']);
            unset($customer['reset_token_expires']);

            $app->dataStorage->save('store/customers', $customer);

            return [
                'success' => true,
                'message' => 'Password reset successfully.',
            ];
        },
    ]);
});
