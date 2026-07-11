<?php
    $orderId          = $_GET['order_id']          ?? '';
    $statusCode       = $_GET['status_code']       ?? '';
    $transactionStatus = $_GET['transaction_status'] ?? '';

    // Fetch order & transaction data from storage
    $order       = null;
    $transaction = null;

    if ($orderId) {
        // order_id from snap = midtrans transaction_id (e.g. TX-xxx)
        $transaction = $this->dataStorage->findOne('midtrans/transactions', ['transaction_id' => $orderId]);
        $order       = $this->dataStorage->findOne('store/orders', ['transaction_id' => $orderId]);
    }

    // Map transaction_status to human-friendly state
    $isSuccess = in_array($transactionStatus, ['settlement', 'capture', 'success']);
    $isPending = in_array($transactionStatus, ['pending', 'authorize']);
    $isFailed  = in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure', 'failed']);

    // Determine display values
    $displayAmount  = $order['total_amount']      ?? ($transaction['amount'] ?? 0);
    $displayEmail   = $order['customer_email']    ?? ($transaction['customer_email'] ?? '');
    $displayOrderId = $order['order_id']          ?? $orderId;
    $displayMethod  = $transaction['payment_method'] ?? 'Midtrans Payment';

    $storeFront    = $this->retrieve('storeFront') ?? [];
    $enableFrontend = !empty($storeFront['enableFrontend']);
    $shopUrl       = $enableFrontend ? '/' : '/shop';
    $trackerUrl    = $enableFrontend ? '/tracker' : '/shop/tracker';
    $shopName      = $storeFront['shop_name'] ?? 'Online Coffee Store';

    $state = $isSuccess ? 'success' : ($isPending ? 'pending' : 'failed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation – <?= htmlspecialchars($shopName) ?></title>
    <meta name="description" content="Your payment status and order confirmation from <?= htmlspecialchars($shopName) ?>.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        <?php include(__DIR__.'/shop.css'); ?>

        /* ─── Finished-order page styles ─── */
        .fo-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .fo-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .fo-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 3rem 2.5rem;
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            animation: fo-fadeUp 0.55s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes fo-fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fo-card::before {
            content: '';
            position: absolute;
            top: -80px;
            left: 50%;
            transform: translateX(-50%);
            width: 360px;
            height: 360px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .fo-card.success::before { background: radial-gradient(circle, rgba(6,214,160,0.13) 0%, transparent 70%); }
        .fo-card.pending::before  { background: radial-gradient(circle, rgba(255,190,11,0.13) 0%, transparent 70%); }
        .fo-card.failed::before   { background: radial-gradient(circle, rgba(239,71,113,0.13) 0%, transparent 70%); }

        .fo-card > * { position: relative; z-index: 1; }

        /* Icon */
        .fo-icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
            animation: fo-pop 0.5s 0.3s cubic-bezier(.34,1.56,.64,1) both;
        }

        @keyframes fo-pop {
            from { transform: scale(0.4); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .fo-icon-wrap.success { background: rgba(6,214,160,0.15); border: 2px solid rgba(6,214,160,0.4); }
        .fo-icon-wrap.pending { background: rgba(255,190,11,0.15); border: 2px solid rgba(255,190,11,0.4); }
        .fo-icon-wrap.failed  { background: rgba(239,71,113,0.15); border: 2px solid rgba(239,71,113,0.4); }

        .fo-icon-wrap.success svg { color: #06d6a0; }
        .fo-icon-wrap.pending svg { color: #ffbe0b; }
        .fo-icon-wrap.failed  svg { color: #ef4771; }

        /* Status badge */
        .fo-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.95rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        .fo-status-badge.success { background: rgba(6,214,160,0.12);  color: #06d6a0; border: 1px solid rgba(6,214,160,0.3); }
        .fo-status-badge.pending { background: rgba(255,190,11,0.12); color: #ffbe0b; border: 1px solid rgba(255,190,11,0.3); }
        .fo-status-badge.failed  { background: rgba(239,71,113,0.12); color: #ef4771; border: 1px solid rgba(239,71,113,0.3); }

        /* Title */
        .fo-title {
            font-family: var(--font-title);
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.65rem;
            letter-spacing: -0.02em;
        }

        .fo-subtitle {
            color: var(--text-secondary);
            font-size: 0.92rem;
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .fo-subtitle strong { color: var(--text-primary); }

        /* Detail rows */
        .fo-details {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .fo-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            padding: 0.45rem 0;
        }

        .fo-detail-row + .fo-detail-row {
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .fo-detail-label { color: var(--text-muted); }

        .fo-detail-value {
            font-weight: 600;
            color: var(--text-primary);
            text-align: right;
        }

        .fo-detail-value.blue  { color: var(--accent-blue); }
        .fo-detail-value.green { color: var(--accent-green); }
        .fo-detail-value.amber { color: var(--accent-amber); }
        .fo-detail-value.rose  { color: var(--accent-rose); }
        .fo-detail-value.mono  { font-family: monospace; font-size: 0.8rem; }

        /* Action buttons */
        .fo-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .fo-actions a {
            display: block;
            width: 100%;
            padding: 0.85rem 1.25rem;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
        }

        .fo-btn-primary {
            background: var(--accent-blue);
            color: #fff;
        }

        .fo-btn-primary:hover {
            background: var(--accent-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(58,134,255,0.35);
        }

        .fo-btn-outline {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .fo-btn-outline:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
            border-color: rgba(255,255,255,0.2);
        }

        /* Confetti */
        .fo-confetti {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .fo-confetti span {
            position: absolute;
            top: -10px;
            opacity: 0;
            animation: fo-fall linear forwards;
        }

        @keyframes fo-fall {
            0%   { opacity: 1; transform: translateY(0) rotate(0deg); }
            100% { opacity: 0; transform: translateY(110vh) rotate(720deg); }
        }

        /* Responsive */
        @media (max-width: 600px) {
            .fo-card { padding: 2.25rem 1.5rem; }
            .fo-title { font-size: 1.55rem; }
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <?php if ($isSuccess): ?>
    <div class="fo-confetti" id="fo-confetti" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="fo-page">
        <!-- Minimal header -->
        <header>
            <div class="container header-content">
                <a href="<?= $shopUrl ?>" class="logo-container" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:.75rem;">
                    <div class="logo-icon">☕</div>
                    <div class="logo-text"><?= htmlspecialchars($shopName) ?></div>
                </a>
            </div>
        </header>

        <!-- Main card -->
        <div class="fo-body">
            <div class="fo-card <?= $state ?>">

                <!-- Icon -->
                <div class="fo-icon-wrap <?= $state ?>">
                    <?php if ($isSuccess): ?>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    <?php elseif ($isPending): ?>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    <?php endif; ?>
                </div>

                <!-- Badge -->
                <div class="fo-status-badge <?= $state ?>">
                    <span>●</span>
                    <?php
                        if ($isSuccess)     echo 'Payment Successful';
                        elseif ($isPending) echo 'Payment Pending';
                        else                echo 'Payment Not Completed';
                    ?>
                </div>

                <!-- Title & subtitle -->
                <?php if ($isSuccess): ?>
                    <h1 class="fo-title">Order Placed Successfully!</h1>
                    <p class="fo-subtitle">
                        Your transaction has been processed.
                        <?php if ($displayEmail): ?>
                            A copy of the receipt was sent to <strong><?= htmlspecialchars($displayEmail) ?></strong>.
                        <?php endif; ?>
                    </p>
                <?php elseif ($isPending): ?>
                    <h1 class="fo-title">Payment Pending</h1>
                    <p class="fo-subtitle">
                        Your order is awaiting payment confirmation. Please complete the payment as soon as possible.
                        <?php if ($displayEmail): ?>
                            Instructions were sent to <strong><?= htmlspecialchars($displayEmail) ?></strong>.
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <h1 class="fo-title">Payment Not Completed</h1>
                    <p class="fo-subtitle">
                        Your payment was not completed or was declined. Please try again or contact support if the issue persists.
                    </p>
                <?php endif; ?>

                <!-- Detail block -->
                <?php if ($displayOrderId || $displayAmount || $transactionStatus): ?>
                <div class="fo-details">
                    <?php if ($displayOrderId): ?>
                    <div class="fo-detail-row">
                        <span class="fo-detail-label">Order Tracking ID</span>
                        <span class="fo-detail-value blue"><?= htmlspecialchars($displayOrderId) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($orderId && $orderId !== $displayOrderId): ?>
                    <div class="fo-detail-row">
                        <span class="fo-detail-label">Transaction ID</span>
                        <span class="fo-detail-value mono"><?= htmlspecialchars($orderId) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($displayAmount): ?>
                    <div class="fo-detail-row">
                        <span class="fo-detail-label">Amount <?= $isSuccess ? 'Paid' : 'Total' ?></span>
                        <span class="fo-detail-value <?= $isSuccess ? 'green' : ($isPending ? 'amber' : 'rose') ?>">
                            Rp <?= number_format((float)$displayAmount, 0, ',', '.') ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($displayMethod): ?>
                    <div class="fo-detail-row">
                        <span class="fo-detail-label">Payment Method</span>
                        <span class="fo-detail-value"><?= htmlspecialchars($displayMethod) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($statusCode || $transactionStatus): ?>
                    <div class="fo-detail-row">
                        <span class="fo-detail-label">Status</span>
                        <span class="fo-detail-value mono"><?= htmlspecialchars($statusCode) ?> — <?= htmlspecialchars($transactionStatus) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- CTA buttons -->
                <div class="fo-actions">
                    <?php if ($isSuccess || $isPending): ?>
                        <a href="<?= $trackerUrl ?>?prefill_order=<?= urlencode($displayOrderId) ?>" class="fo-btn-primary" id="track-order-btn">
                            📦 Track My Order
                        </a>
                    <?php endif; ?>
                    <a href="<?= $shopUrl ?>" class="<?= ($isSuccess || $isPending) ? 'fo-btn-outline' : 'fo-btn-primary' ?>" id="back-to-shop-btn">
                        Return to Shop Homepage
                    </a>
                </div>

            </div>
        </div>
    </div>

    <?php if ($isSuccess): ?>
    <script>
        (function() {
            var colors = ['#06d6a0','#3a86ff','#ffbe0b','#ef4771','#ffffff','#a855f7'];
            var container = document.getElementById('fo-confetti');
            if (!container) return;
            for (var i = 0; i < 65; i++) {
                var span = document.createElement('span');
                var size = Math.random() * 9 + 4;
                span.style.left = (Math.random() * 100) + 'vw';
                span.style.width = size + 'px';
                span.style.height = size + 'px';
                span.style.background = colors[Math.floor(Math.random() * colors.length)];
                span.style.animationDuration = (Math.random() * 2.5 + 1.5) + 's';
                span.style.animationDelay = (Math.random() * 1.4) + 's';
                span.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                container.appendChild(span);
            }
        })();
    </script>
    <?php endif; ?>

</body>
</html>
