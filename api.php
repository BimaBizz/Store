<?php

/**
 * @OA\Tag(
 *   name="store",
 *   description="Online Store — products, orders, customers, vouchers, auth & more",
 * )
 */

$this->on('restApi.config', function ($restApi) {
    // ─────────────────────────────────────────────
    // GET /store/products
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/products', [
        /**
         * @OA\Get(
         *     path="/store/products",
         *     tags={"store"},
         *     summary="List all products",
         *     description="Returns all products with resolved image URLs. Requires store/api/products or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Parameter(name="category", in="query", required=false, @OA\Schema(type="string")),
         *     @OA\Parameter(name="search", in="query", description="Search by name, SKU, or description", required=false, @OA\Schema(type="string")),
         *     @OA\Response(response="200", description="Array of product objects", @OA\JsonContent(type="array", @OA\Items(type="object"))),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
        'GET' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/products', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }

            $search = trim($app->param('search', ''));
            $category = trim($app->param('category', ''));

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

            $options = $criteria ? ['filter' => $criteria] : [];
            $products = $app->dataStorage->find('store/products', $options)->toArray();

            foreach ($products as &$prod) {
                $imageUrls = [];
                $images = isset($prod['image']) ? array_filter(array_map('trim', explode(',', $prod['image']))) : [];
                foreach ($images as $img) {
                    if (\str_starts_with($img, 'assets://')) {
                        $id = \str_replace('assets://', '', $img);
                        $asset = $app->dataStorage->findOne('assets', ['_id' => $id]);
                        if ($asset) {
                            $url = $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                            $asset['url'] = $url;
                            $imageUrls[] = $url;
                        } else {
                            $imageUrls[] = $app->routeUrl('/assets/link/' . $id);
                        }
                    } else {
                        $imageUrls[] = $img;
                    }
                }
                $prod['image_url'] = !empty($imageUrls) ? $imageUrls[0] : null;
                $prod['image_urls'] = $imageUrls;
            }

            return $products;
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/product/:id   [NEW]
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/product/:id', [
        /**
         * @OA\Get(
         *     path="/store/product/{id}",
         *     tags={"store"},
         *     summary="Get a single product by ID",
         *     description="Returns a product with fully resolved image URLs. Requires store/api/products or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
         *     @OA\Response(response="200", description="Product object", @OA\JsonContent(type="object")),
         *     @OA\Response(response="403", description="Forbidden"),
         *     @OA\Response(response="404", description="Product not found")
         * )
         */
        'GET' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/products', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }

            $id = $params['id'] ?? null;
            $product = $id ? $app->dataStorage->findOne('store/products', ['_id' => $id]) : null;

            if (!$product) {
                $app->response->status = 404;
                return ['error' => 'Product not found'];
            }

            $imageUrls = [];
            $images = isset($product['image']) ? array_filter(array_map('trim', explode(',', $product['image']))) : [];
            foreach ($images as $img) {
                if (\str_starts_with($img, 'assets://')) {
                    $assetId = \str_replace('assets://', '', $img);
                    $asset = $app->dataStorage->findOne('assets', ['_id' => $assetId]);
                    if ($asset) {
                        $url = $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'));
                        $asset['url'] = $url;
                        $imageUrls[] = $url;
                    } else {
                        $imageUrls[] = $app->routeUrl('/assets/link/' . $assetId);
                    }
                } else {
                    $imageUrls[] = $img;
                }
            }
            $product['image_url'] = !empty($imageUrls) ? $imageUrls[0] : null;
            $product['image_urls'] = $imageUrls;

            return $product;
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/content
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/content', [
        /**
         * @OA\Get(
         *     path="/store/content",
         *     tags={"store"},
         *     summary="Get storefront CMS content",
         *     description="Returns the homepage CMS content (hero, banners, about, FAQ) with resolved image URLs. Requires store/api/content or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Response(response="200", description="Homepage content object", @OA\JsonContent(type="object")),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
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

            if (isset($content['hero_image']) && \str_starts_with($content['hero_image'], 'assets://')) {
                $id = \str_replace('assets://', '', $content['hero_image']);
                $asset = $app->dataStorage->findOne('assets', ['_id' => $id]);
                $content['hero_image_url'] = $asset
                    ? $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'))
                    : $app->routeUrl('/assets/link/' . $id);
            } else {
                $content['hero_image_url'] =
                    $content['hero_image'] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80';
            }

            if (isset($content['promo_image']) && \str_starts_with($content['promo_image'], 'assets://')) {
                $id = \str_replace('assets://', '', $content['promo_image']);
                $asset = $app->dataStorage->findOne('assets', ['_id' => $id]);
                $content['promo_image_url'] = $asset
                    ? $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'))
                    : $app->routeUrl('/assets/link/' . $id);
            } else {
                $content['promo_image_url'] =
                    $content['promo_image'] ??
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80';
            }

            if (isset($content['promo_banners']) && \is_array($content['promo_banners'])) {
                foreach ($content['promo_banners'] as &$slide) {
                    if (!empty($slide['image']) && \str_starts_with($slide['image'], 'assets://')) {
                        $id = \str_replace('assets://', '', $slide['image']);
                        $asset = $app->dataStorage->findOne('assets', ['_id' => $id]);
                        $slide['image_url'] = $asset
                            ? $app->fileStorage->getURL('uploads://' . \trim($asset['path'], '/'))
                            : $app->routeUrl('/assets/link/' . $id);
                    } else {
                        $slide['image_url'] =
                            $slide['image'] ??
                            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80';
                    }
                }
            }

            return $content;
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/settings
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/settings', [
        /**
         * @OA\Get(
         *     path="/store/settings",
         *     tags={"store"},
         *     summary="Get store settings",
         *     description="Returns shop configuration (name, currency, tax %, etc.). Requires store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Response(response="200", description="Store settings object", @OA\JsonContent(type="object")),
         *     @OA\Response(response="401", description="Unauthorized")
         * )
         */
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

    // ─────────────────────────────────────────────
    // POST /store/order
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/order', [
        /**
         * @OA\Post(
         *     path="/store/order",
         *     tags={"store"},
         *     summary="Create a new order (checkout)",
         *     description="Places an order, deducts stock, applies voucher/tax, and optionally creates a Midtrans Snap payment link. Requires store/api/order or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"customer_name","customer_email","items"},
         *             @OA\Property(property="customer_name", type="string", example="John Doe"),
         *             @OA\Property(property="customer_email", type="string", format="email", example="john@example.com"),
         *             @OA\Property(property="items", type="array", @OA\Items(type="object", @OA\Property(property="product_id", type="string"), @OA\Property(property="quantity", type="integer", example=1))),
         *             @OA\Property(property="voucher_code", type="string", example="PROMO10"),
         *             @OA\Property(property="courier", type="string", example="JNE"),
         *             @OA\Property(property="shipping_cost", type="number", example=15000)
         *         )
         *     ),
         *     @OA\Response(response="200", description="Order created successfully", @OA\JsonContent(type="object")),
         *     @OA\Response(response="400", description="Validation error"),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
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
                $emailBody = "<h1>Order {$orderId} Received!</h1><p>Thank you for shopping. Total amount: IDR {$grandTotal}.</p>";
                if ($order['redirect_url']) {
                    $emailBody .= "<p><a href='{$order['redirect_url']}' style='padding:10px 20px;background:#3a86ff;color:#fff;text-decoration:none;border-radius:5px;'>Pay with Midtrans</a></p>";
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

    // ─────────────────────────────────────────────
    // GET /store/orders   [NEW — admin]
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/orders', [
        /**
         * @OA\Get(
         *     path="/store/orders",
         *     tags={"store"},
         *     summary="List orders (admin)",
         *     description="Returns a paginated list of all orders. Requires store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", example=1)),
         *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=20)),
         *     @OA\Parameter(name="status", in="query", description="Filter by order status", required=false, @OA\Schema(type="string", enum={"pending","processing","completed","cancelled"})),
         *     @OA\Parameter(name="search", in="query", description="Search by customer email or order_id", required=false, @OA\Schema(type="string")),
         *     @OA\Response(response="200", description="Paginated orders", @OA\JsonContent(type="object")),
         *     @OA\Response(response="401", description="Unauthorized")
         * )
         */
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }

            $limit = intval($app->param('limit', 20));
            $page = intval($app->param('page', 1));
            $skip = ($page - 1) * $limit;
            $status = trim($app->param('status', ''));
            $search = trim($app->param('search', ''));

            $criteria = [];
            if ($status) {
                $criteria['status'] = $status;
            }
            if ($search) {
                $criteria['$or'] = [
                    ['customer_email' => ['$regex' => $search, '$options' => 'i']],
                    ['order_id' => ['$regex' => $search, '$options' => 'i']],
                ];
            }

            $options = [
                'limit' => $limit,
                'skip' => $skip,
                'sort' => ['created' => -1],
            ];
            if ($criteria) {
                $options['filter'] = $criteria;
            }

            $total = $app->dataStorage->count('store/orders', $criteria);
            $pages = max(1, (int) ceil($total / $limit));
            $orders = $app->dataStorage->find('store/orders', $options)->toArray();

            return [
                'orders' => $orders,
                'count' => $total,
                'pages' => $pages,
                'page' => $page,
                'limit' => $limit,
            ];
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/customers  (admin)
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/customers', [
        /**
         * @OA\Get(
         *     path="/store/customers",
         *     tags={"store"},
         *     summary="List customers (admin)",
         *     description="Returns a paginated customer list with order stats. Requires store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", example=1)),
         *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=20)),
         *     @OA\Parameter(name="search", in="query", description="Search by name or email", required=false, @OA\Schema(type="string")),
         *     @OA\Response(response="200", description="Paginated customers", @OA\JsonContent(type="object")),
         *     @OA\Response(response="401", description="Unauthorized")
         * )
         */
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

    // ─────────────────────────────────────────────
    // GET /store/vouchers  (admin)
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/vouchers', [
        /**
         * @OA\Get(
         *     path="/store/vouchers",
         *     tags={"store"},
         *     summary="List all vouchers (admin)",
         *     description="Returns all promo vouchers. Requires store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Response(response="200", description="Array of voucher objects", @OA\JsonContent(type="array", @OA\Items(type="object"))),
         *     @OA\Response(response="401", description="Unauthorized")
         * )
         */
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }
            return $app->dataStorage->find('store/vouchers')->toArray();
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/voucher/validate  [NEW — public]
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/voucher/validate', [
        /**
         * @OA\Get(
         *     path="/store/voucher/validate",
         *     tags={"store"},
         *     summary="Validate a voucher code",
         *     description="Checks if a voucher code is active and returns its discount type and value. Requires store/api/vouchers or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Parameter(name="code", in="query", description="Voucher code to validate", required=true, @OA\Schema(type="string", example="PROMO10")),
         *     @OA\Response(response="200", description="Validation result",
         *         @OA\JsonContent(type="object",
         *             @OA\Property(property="valid", type="boolean"),
         *             @OA\Property(property="code", type="string"),
         *             @OA\Property(property="type", type="string", enum={"percent","fixed"}),
         *             @OA\Property(property="value", type="number"),
         *             @OA\Property(property="message", type="string")
         *         )
         *     ),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
        'GET' => function ($params, $app) {
            $role = $app->helper('auth')->getUser('role');
            if (
                !$app->helper('acl')->isAllowed('store/api/vouchers', $role) &&
                !$app->helper('acl')->isAllowed('store/manage', $role)
            ) {
                $app->response->status = 403;
                return ['error' => 'Forbidden'];
            }

            $code = trim($app->param('code', ''));
            if (!$code) {
                $app->response->status = 400;
                return ['valid' => false, 'message' => 'Voucher code is required'];
            }

            $voucher = $app->dataStorage->findOne('store/vouchers', ['code' => $code]);
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
                    'value' => floatval($voucher['value']),
                ];
            }

            return ['valid' => false, 'message' => 'Invalid or expired voucher code'];
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/suppliers  (admin)
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/suppliers', [
        /**
         * @OA\Get(
         *     path="/store/suppliers",
         *     tags={"store"},
         *     summary="List all suppliers (admin)",
         *     description="Returns all supplier records. Requires store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Response(response="200", description="Array of supplier objects", @OA\JsonContent(type="array", @OA\Items(type="object"))),
         *     @OA\Response(response="401", description="Unauthorized")
         * )
         */
        'GET' => function ($params, $app) {
            if (!$app->helper('acl')->isAllowed('store/manage')) {
                $app->response->status = 401;
                return ['error' => 'Unauthorized'];
            }
            return $app->dataStorage->find('store/suppliers')->toArray();
        },
    ]);

    // ─────────────────────────────────────────────
    // GET /store/reports  (admin)
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/reports', [
        /**
         * @OA\Get(
         *     path="/store/reports",
         *     tags={"store"},
         *     summary="Get sales reports (admin)",
         *     description="Returns daily sales totals and top-5 best-selling products from settled or completed orders. Requires store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\Response(response="200", description="Report data",
         *         @OA\JsonContent(type="object",
         *             @OA\Property(property="daily", type="object", description="Date to revenue map"),
         *             @OA\Property(property="products", type="object", description="Top 5 products by revenue")
         *         )
         *     ),
         *     @OA\Response(response="401", description="Unauthorized")
         * )
         */
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

    // ─────────────────────────────────────────────
    // POST /store/auth/register
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/auth/register', [
        /**
         * @OA\Post(
         *     path="/store/auth/register",
         *     tags={"store"},
         *     summary="Register a new customer account",
         *     description="Creates a new customer in the store. Requires store/api/auth or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"name","email","password"},
         *             @OA\Property(property="name", type="string", example="Jane Doe"),
         *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
         *             @OA\Property(property="password", type="string", format="password"),
         *             @OA\Property(property="phone", type="string", example="+62812345678"),
         *             @OA\Property(property="address", type="string"),
         *             @OA\Property(property="city", type="string"),
         *             @OA\Property(property="zip", type="string")
         *         )
         *     ),
         *     @OA\Response(response="200", description="Customer registered", @OA\JsonContent(type="object")),
         *     @OA\Response(response="400", description="Validation error"),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
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
                'customer' => ['name' => $name, 'email' => $email],
            ];
        },
    ]);

    // ─────────────────────────────────────────────
    // POST /store/auth/login
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/auth/login', [
        /**
         * @OA\Post(
         *     path="/store/auth/login",
         *     tags={"store"},
         *     summary="Customer login",
         *     description="Authenticates a customer and returns a JWT token. Requires store/api/auth or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"email","password"},
         *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
         *             @OA\Property(property="password", type="string", format="password")
         *         )
         *     ),
         *     @OA\Response(response="200", description="Login successful — returns JWT token", @OA\JsonContent(type="object")),
         *     @OA\Response(response="400", description="Missing credentials"),
         *     @OA\Response(response="401", description="Invalid credentials"),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
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

    // ─────────────────────────────────────────────
    // POST /store/auth/forgot-password
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/auth/forgot-password', [
        /**
         * @OA\Post(
         *     path="/store/auth/forgot-password",
         *     tags={"store"},
         *     summary="Request a password reset email",
         *     description="Sends a password reset link to the customer email. Requires store/api/auth or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"email"},
         *             @OA\Property(property="email", type="string", format="email"),
         *             @OA\Property(property="redirect_url", type="string", description="URL to append the reset token to")
         *         )
         *     ),
         *     @OA\Response(response="200", description="Reset email sent", @OA\JsonContent(type="object")),
         *     @OA\Response(response="400", description="Email required or not found"),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
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

    // ─────────────────────────────────────────────
    // POST /store/auth/reset-password
    // ─────────────────────────────────────────────
    $restApi->addEndPoint('/store/auth/reset-password', [
        /**
         * @OA\Post(
         *     path="/store/auth/reset-password",
         *     tags={"store"},
         *     summary="Reset customer password using token",
         *     description="Validates the reset token and updates the customer password. Requires store/api/auth or store/manage permission.",
         *     security={{"api_key":{}}},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"token","password"},
         *             @OA\Property(property="token", type="string", description="Reset token from the email link"),
         *             @OA\Property(property="password", type="string", format="password", description="New password")
         *         )
         *     ),
         *     @OA\Response(response="200", description="Password reset successfully", @OA\JsonContent(type="object")),
         *     @OA\Response(response="400", description="Invalid or expired token"),
         *     @OA\Response(response="403", description="Forbidden")
         * )
         */
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
