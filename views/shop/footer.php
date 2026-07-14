        </main>
        
        <?php
        $app = \Cockpit::instance();
        $storeSettings = $app->dataStorage->findOne('store/settings', ['_id' => 'config']);
        $shopName = $storeSettings['shop_name'] ?? 'Online Store';
        $logoLetter = strtoupper(substr($shopName, 0, 1)) ?: 'S';

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
        ?>
        <footer class="shop-footer">
            <div class="container footer-grid">
                
                <div class="footer-col branding">
                    <div class="logo-container" style="margin-bottom: 1rem;">
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
                        <div class="logo-text">{{ settings.shop_name || 'Online Store' }}</div>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6; margin-top: 0.5rem;">
                        Shop premium products with exclusive deals and fast delivery right to your doorstep.
                    </p>
                </div>

                <?php
                $storeFront = $this->retrieve('onlineshop') ?? [];
                $enableFrontend = !empty($storeFront['mainPageEnable']);
                $shopUrl = $enableFrontend ? '/' : '/shop';
                $trackerUrl = $enableFrontend ? '/tracker' : '/shop/tracker';
                $securityUrl = $enableFrontend ? '/security' : '/shop/security';
                ?>
                <div class="footer-col">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?= $shopUrl ?>">Browse Products</a></li>
                        <li><a href="<?= $trackerUrl ?>">Track Order Status</a></li>
                        <li v-if="homepageContent.shipping_policy"><a href="<?= $securityUrl ?>">Security & Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="footer-links contacts">
                        <li v-if="settings.shop_address">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>{{ settings.shop_address }}</span>
                        </li>
                        <li v-if="settings.shop_phone">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            <span>{{ settings.shop_phone }}</span>
                        </li>
                        <li v-if="settings.shop_email">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <span>{{ settings.shop_email }}</span>
                        </li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4 class="footer-title">Payment Secure</h4>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.75rem;">Powered securely by Midtrans Gateway</p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        <span class="stock-indicator available" style="font-size: 0.7rem; font-weight: 700; border-radius: 4px; padding: 0.25rem 0.5rem; background: var(--bg-surface-hover); border: 1px solid var(--border-color); color: var(--text-primary);">MIDTRANS</span>
                        <span class="stock-indicator available" style="font-size: 0.7rem; font-weight: 700; border-radius: 4px; padding: 0.25rem 0.5rem; background: var(--bg-surface-hover); border: 1px solid var(--border-color); color: var(--text-primary);">VISA</span>
                        <span class="stock-indicator available" style="font-size: 0.7rem; font-weight: 700; border-radius: 4px; padding: 0.25rem 0.5rem; background: var(--bg-surface-hover); border: 1px solid var(--border-color); color: var(--text-primary);">MASTERCARD</span>
                        <span class="stock-indicator available" style="font-size: 0.7rem; font-weight: 700; border-radius: 4px; padding: 0.25rem 0.5rem; background: var(--bg-surface-hover); border: 1px solid var(--border-color); color: var(--text-primary);">VA</span>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="container" style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; font-size: 0.8rem; color: var(--text-secondary); align-items: center;">
                    <span>&copy; {{ new Date().getFullYear() }} {{ settings.shop_name || 'Online Store' }}. All rights reserved.</span>
                    <span style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--accent-site);"></span>
                        Secure Checkout Powered by Midtrans
                    </span>
                </div>
            </div>
        </footer>

        
        <div class="modal-overlay" :class="{ active: isDetailOpen }" @click.self="isDetailOpen = false">
            <div class="modal" v-if="selectedProduct">
                <div class="modal-header">
                    <h3 class="modal-title">Product Details</h3>
                    <button class="btn btn-ghost" @click="isDetailOpen = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="detail-layout">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <img :src="currentDetailImage || selectedProduct.image_url || 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=500'" :alt="selectedProduct.name" class="detail-img">
                            
                            <div v-if="selectedProduct.image_urls && selectedProduct.image_urls.length > 1" style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem;">
                                <div v-for="(img, idx) in selectedProduct.image_urls" :key="idx" 
                                     @click="currentDetailImage = img"
                                     style="width: 50px; height: 50px; border-radius: 6px; overflow: hidden; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; flex-shrink: 0;"
                                     :style="{ borderColor: (currentDetailImage === img || (!currentDetailImage && idx === 0)) ? 'var(--accent-site)' : 'var(--border-color)' }">
                                    <img :src="img" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </div>
                        </div>
                        <div>
                            <h2 class="detail-title">{{ selectedProduct.name }}</h2>
                            <p class="detail-sku">{{ selectedProduct.sku }}</p>

                            <!-- Price -->
                            <div style="display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                                <span class="detail-price" style="font-size: 1.5rem; font-weight: 700; color: var(--accent-site);">{{ formatIDR(selectedProduct.price) }}</span>
                                <span class="detail-old-price" v-if="selectedProduct.original_price && selectedProduct.original_price > selectedProduct.price" style="text-decoration: line-through; color: var(--text-muted); font-size: 1.1rem;">{{ formatIDR(selectedProduct.original_price) }}</span>
                                <span class="detail-disc-badge" v-if="selectedProduct.discount_percent" style="background: #ef4444; color: #fff; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px;">-{{ selectedProduct.discount_percent }}% OFF</span>
                            </div>

                            <!-- Variant Picker -->
                            <div v-if="selectedProduct.variants && selectedProduct.variants.trim()" style="margin-bottom: 1rem;">
                                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                                    Variant
                                    <span v-if="selectedVariant" style="font-weight: 400; color: var(--accent-site); text-transform: none; letter-spacing: 0;">— {{ selectedVariant }}</span>
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                    <button
                                        v-for="v in selectedProduct.variants.split(',').map(x => x.trim()).filter(Boolean)"
                                        :key="v"
                                        @click="selectedVariant = (selectedVariant === v ? null : v)"
                                        :style="{
                                            padding: '0.35rem 0.85rem',
                                            borderRadius: '6px',
                                            border: selectedVariant === v ? '2px solid var(--accent-site)' : '1px solid var(--border-color)',
                                            background: selectedVariant === v ? 'var(--accent-site)' : 'var(--bg-surface)',
                                            color: selectedVariant === v ? 'var(--text-on-accent)' : 'var(--text-primary)',
                                            fontWeight: 600,
                                            fontSize: '0.82rem',
                                            cursor: 'pointer',
                                            transition: 'all 0.15s ease'
                                        }"
                                    >{{ v }}</button>
                                </div>
                            </div>

                            <!-- Add to Cart -->
                            <div v-if="selectedProduct.stock > 0" style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
                                <div class="qty-selectors" style="height: 2.5rem;">
                                    <button class="qty-btn" style="width: 2.5rem; height: 2.5rem;" @click="detailQty = Math.max(1, detailQty - 1)">-</button>
                                    <span class="qty-value" style="font-size: 1rem; min-width: 2rem;">{{ detailQty }}</span>
                                    <button class="qty-btn" style="width: 2.5rem; height: 2.5rem;" @click="detailQty = Math.min(selectedProduct.stock, detailQty + 1)">+</button>
                                </div>
                                <button class="btn btn-primary" style="flex: 1; height: 2.5rem;" @click="addToCart(selectedProduct, detailQty)">
                                    Add To Cart
                                </button>
                            </div>
                            <div v-else style="margin-bottom: 1rem; color: var(--accent-rose); font-weight: 700; text-align: center;">
                                Currently Out of Stock
                            </div>

                            <!-- Description -->
                            <p class="detail-description">{{ selectedProduct.description }}</p>
                            
                            <div class="detail-info-row">
                                <span style="color: var(--text-secondary);">Category</span>
                                <span style="font-weight: 600;">{{ selectedProduct.category || 'N/A' }}</span>
                            </div>
                            <div class="detail-info-row">
                                <span style="color: var(--text-secondary);">Brand / Origin</span>
                                <span style="font-weight: 600;">{{ selectedProduct.brand || 'N/A' }}</span>
                            </div>
                            <div class="detail-info-row">
                                <span style="color: var(--text-secondary);">In Stock Availability</span>
                                <span class="stock-indicator" :class="getStockClass(selectedProduct.stock)">
                                    {{ selectedProduct.stock }} items
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="drawer-overlay" :class="{ active: isCartOpen }" @click.self="isCartOpen = false"></div>
        <div class="drawer" :class="{ active: isCartOpen }">
            <div class="drawer-header">
                <h3 class="drawer-title">Shopping Cart ({{ cartCount }} items)</h3>
                <button class="btn btn-ghost" @click="isCartOpen = false">&times;</button>
            </div>
            
            <div class="drawer-body">
                
                <div v-if="cart.length === 0" style="text-align: center; padding: 4rem 1rem; color: var(--text-secondary);">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; color: var(--text-muted);"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <h4>Your Cart is Empty</h4>
                    <p style="font-size: 0.85rem; margin-top: 0.25rem;">Browse products and add items to your cart.</p>
                </div>

                
                <div v-else>
                    <div class="cart-item" v-for="item in cart" :key="item.product_id">
                        <img :src="item.image_url || 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=200'" :alt="item.name" class="cart-item-img">
                        <div class="cart-item-details">
                            <span class="cart-item-name">{{ item.name }}</span>
                            <span class="cart-item-price">{{ formatIDR(item.price) }}</span>
                            
                            <div class="cart-item-controls">
                                <div class="qty-selectors">
                                    <button class="qty-btn" @click="updateCartQty(item, -1)">-</button>
                                    <span class="qty-value">{{ item.quantity }}</span>
                                    <button class="qty-btn" @click="updateCartQty(item, 1)">+</button>
                                </div>
                                <button class="btn btn-ghost" style="padding: 0.25rem; color: var(--accent-rose);" @click="removeFromCart(item)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="drawer-footer" v-if="cart.length > 0">
                
                <div class="voucher-section">
                    <label class="form-label" style="margin-bottom: 0.25rem;">Have a Promo Code?</label>
                    <div class="voucher-input-group">
                        <input type="text" class="voucher-input" v-model="voucherCode" placeholder="..." :disabled="discountAmount > 0">
                        <button class="btn btn-outline" style="padding: 0.5rem 1rem;" @click="applyVoucher" v-if="discountAmount === 0" :disabled="!voucherCode">Apply</button>
                        <button class="btn btn-ghost" style="color: var(--accent-rose);" @click="removeVoucher" v-else>Remove</button>
                    </div>
                    <div v-if="voucherMsg" class="voucher-status" :class="voucherValid ? 'success' : 'error'">
                        {{ voucherMsg }}
                    </div>
                </div>

                <div class="courier-section">
                    <label class="form-label">Shipping Courier Service</label>
                    <select class="courier-select" v-model="selectedCourier" @change="calculateShipping">
                        <option value="Manual">Shop Self Delivery (IDR 0)</option>
                        <option value="JNE">JNE Regular (IDR 15.000)</option>
                        <option value="J&T">J&T Express (IDR 18.000)</option>
                        <option value="POS">Pos Indonesia (IDR 12.000)</option>
                    </select>
                </div>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>{{ formatIDR(cartSubtotal) }}</span>
                </div>
                <div class="summary-row discount" v-if="discountAmount > 0">
                    <span>Discount Voucher:</span>
                    <span>-{{ formatIDR(discountAmount) }}</span>
                </div>
                <div class="summary-row">
                    <span>PPN / Tax ({{ settings.tax_percent || 11 }}%):</span>
                    <span>{{ formatIDR(cartTax) }}</span>
                </div>
                <div class="summary-row">
                    <span>Shipping fee:</span>
                    <span>{{ formatIDR(shippingCost) }}</span>
                </div>
                
                <div class="summary-row total">
                    <span>Grand Total:</span>
                    <span style="color: var(--accent-green);">{{ formatIDR(cartTotal) }}</span>
                </div>

                <button class="btn btn-primary" style="width: 100%; margin-top: 1.25rem; padding: 0.85rem;" @click="openCheckoutModal">
                    Proceed to Checkout &rarr;
                </button>
            </div>
        </div>

        <div class="modal-overlay" :class="{ active: isCheckoutOpen }" @click.self="isCheckoutOpen = false">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Shipping & Billing Details</h3>
                    <button class="btn btn-ghost" @click="isCheckoutOpen = false">&times;</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="submitCheckout">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" v-model="checkoutForm.name" required placeholder="Budi Santoso">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" v-model="checkoutForm.email" required placeholder="budi@example.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" v-model="checkoutForm.phone" required placeholder="+62812345678">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Delivery Street Address</label>
                            <textarea class="form-control" v-model="checkoutForm.address" required placeholder="Jl. Raya No. 12, RT. 03 RW. 04"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" v-model="checkoutForm.city" required placeholder="Jakarta Selatan">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Zip / Postal Code</label>
                                <input type="text" class="form-control" v-model="checkoutForm.zip" required placeholder="12190">
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <div class="summary-row" style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary);">
                                <span>Grand Total to Pay:</span>
                                <span style="color: var(--accent-green);">{{ formatIDR(cartTotal) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.85rem;" :disabled="submittingOrder">
                            <span v-if="submittingOrder">Processing order...</span>
                            <span v-else>Confirm & Pay Now</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal-overlay" :class="{ active: isPaymentOpen }" @click.self="cancelPayment">
            <div class="modal" style="max-width: 440px;">
                <div class="modal-header">
                    <h3 class="modal-title">Secure Payment Simulator</h3>
                    <button class="btn btn-ghost" @click="cancelPayment">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center;">
                    <div class="payment-sim-card">
                        <div class="payment-sim-card-logo">
                            <div class="payment-chip"></div>
                            <div style="color: #ffffff; font-weight: 800; font-style: italic;">VISAMOCK</div>
                        </div>
                        <div class="payment-sim-title">Billing to {{ checkoutForm.name }}</div>
                        <div class="payment-sim-number">•••• •••• •••• 9845</div>
                        <div class="payment-sim-meta">
                            <span>EXP: 12/29</span>
                            <span style="font-weight: 700; color: var(--accent-green);">{{ formatIDR(cartTotal) }}</span>
                        </div>
                    </div>

                    <div v-if="paymentStep === 'pending'">
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                            Clicking "Pay Now" simulates a successful callback webhook response from the payment gateway to Cockpit, finalizing the order logs.
                        </p>
                        <button class="btn btn-primary" style="width: 100%; padding: 0.85rem;" @click="simulatePaymentSuccess">
                            Pay Now
                        </button>
                    </div>

                    <div v-if="paymentStep === 'processing'">
                        <div class="loader-spinner"></div>
                        <p style="color: var(--text-secondary);">Verifying payment session token...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="toast-container">
            <div class="toast" v-for="t in toasts" :key="t.id" :class="t.type">
                <span>{{ t.message }}</span>
            </div>
        </div>
    </div>

    <script>
        <?php include __DIR__ . '/shop.js'; ?>
    </script>
</body>
</html>
