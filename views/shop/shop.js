const { createApp } = Vue;

createApp({
    data() {
        return {
            currentTab: window.location.pathname.includes('/tracker')
                ? 'tracker'
                : window.location.pathname.includes('/dashboard')
                  ? 'dashboard'
                  : 'shop',
            settings: {},
            products: [],
            categories: [],

            homepageContent: { banners: [], faq: '', shipping_policy: '', about_us: '' },
            activeBannerIdx: 0,
            bannerTimer: null,
            activeInfoTab: 'about',

            customerSession: { logged_in: false, customer: null, orders: [] },
            activeAuthForm: 'login',
            submittingAuth: false,
            updatingProfile: false,
            urlParams: { email: '', token: '' },
            authForm: { name: '', email: '', password: '', phone: '', address: '', city: '', zip: '' },
            profileForm: { name: '', phone: '', address: '', city: '', zip: '', password: '' },

            searchQuery: '',
            selectedCategory: 'All',
            loadingProducts: false,

            selectedProduct: window.INITIAL_PRODUCT || null,
            isDetailOpen: false,
            detailQty: 1,

            isCartOpen: false,
            cart: [],

            voucherCode: '',
            voucherValid: false,
            voucherMsg: '',
            discountAmount: 0,
            discountType: 'flat',
            discountVal: 0,

            selectedCourier: 'Manual',
            shippingCost: 0,

            isCheckoutOpen: false,
            submittingOrder: false,
            checkoutForm: {
                name: '',
                email: '',
                phone: '',
                address: '',
                city: '',
                zip: '',
            },

            isPaymentOpen: false,
            paymentStep: 'pending',
            activePendingOrder: null,

            isSuccessOpen: false,
            successOrder: null,

            trackEmail: '',
            trackOrderId: '',
            loadingTrack: false,
            activeTrackedOrder: null,

            toasts: [],
            toastId: 0,

            flashCountdown: null,
            flashCountdownEnd: null,
            explorePage: 0,
            explorePerPage: 8,
            activePromoIdx: 0,
            promoTimer: null,
            theme: localStorage.getItem('theme') || 'dark',
            currentDetailImage: null,
        };
    },
    computed: {
        promoSlides() {
            return this.homepageContent.promo_banners || [];
        },
        filteredProducts() {
            return this.products;
        },
        flashProducts() {
            const ids = this.homepageContent.flash_product_ids || [];
            if (ids.length > 0) {
                return ids.map((id) => this.products.find((p) => p._id === id)).filter(Boolean);
            }

            return this.products.slice(0, 8);
        },
        bestSellingProducts() {
            return this.products.slice(0, 4);
        },
        newArrivalProducts() {
            const sorted = [...this.products].reverse();
            return sorted.slice(0, 4);
        },
        pagedProducts() {
            const start = this.explorePage * this.explorePerPage;
            return this.filteredProducts.slice(start, start + this.explorePerPage);
        },
        totalExplorePages() {
            return Math.ceil(this.filteredProducts.length / this.explorePerPage);
        },
        cartCount() {
            return this.cart.reduce((total, item) => total + item.quantity, 0);
        },
        cartSubtotal() {
            return this.cart.reduce((total, item) => total + item.price * item.quantity, 0);
        },
        cartTax() {
            const rate = parseFloat(this.settings.tax_percent ?? 11) / 100;
            return Math.round((this.cartSubtotal - this.discountAmount) * rate);
        },
        cartTotal() {
            const val = this.cartSubtotal - this.discountAmount + this.cartTax + this.shippingCost;
            return val > 0 ? val : 0;
        },
    },
    mounted() {
        this.loadSettings();
        this.loadHomepageContent();
        this.loadCustomerDashboard();
        this.fetchProducts();
        this.loadCartFromStorage();

        const params = new URLSearchParams(window.location.search);
        const email = params.get('email');
        const token = params.get('reset_token');
        if (email && token) {
            this.currentTab = 'dashboard';
            this.activeAuthForm = 'reset';
            this.urlParams = { email, token };
        }

        const orderIdParam = params.get('order_id') || params.get('orderId');
        const emailParam = params.get('email');
        if (orderIdParam && emailParam) {
            this.trackEmail = emailParam;
            this.trackOrderId = orderIdParam;
            this.trackOrder();
        }
        document.documentElement.setAttribute('data-theme', this.theme);
        if (this.selectedProduct) {
            this.currentDetailImage = this.selectedProduct.image_url;
        }
    },
    methods: {
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', this.theme);
            localStorage.setItem('theme', this.theme);
        },
        showToast(message, type = 'success') {
            const id = this.toastId++;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },
        async loadSettings() {
            try {
                const res = await fetch('/shop/getSettings');
                this.settings = await res.json();

                if (this.settings.midtrans_client_key) {
                    const scriptSrc =
                        this.settings.midtrans_mode === 'production'
                            ? 'https://app.midtrans.com/snap/snap.js'
                            : 'https://app.sandbox.midtrans.com/snap/snap.js';

                    const script = document.createElement('script');
                    script.src = scriptSrc;
                    script.setAttribute('data-client-key', this.settings.midtrans_client_key);
                    document.head.appendChild(script);
                }
            } catch (e) {
                this.showToast('Failed to load store settings', 'error');
            }
        },
        async loadHomepageContent() {
            try {
                const res = await fetch('/shop/getHomepageContent');
                this.homepageContent = await res.json();

                if (this.homepageContent.about_us) this.activeInfoTab = 'about';
                else if (this.homepageContent.faq) this.activeInfoTab = 'faq';
                else if (this.homepageContent.shipping_policy) this.activeInfoTab = 'shipping';

                if (this.homepageContent.banners && this.homepageContent.banners.length > 1) {
                    this.startBannerAutoPlay();
                }

                this.startFlashCountdown();

                this.startPromoAutoPlay();
            } catch (e) {
                this.showToast('Failed to load homepage content', 'error');
            }
        },
        startBannerAutoPlay() {
            if (this.bannerTimer) clearInterval(this.bannerTimer);
            this.bannerTimer = setInterval(() => {
                if (this.homepageContent.banners && this.homepageContent.banners.length > 0) {
                    this.activeBannerIdx = (this.activeBannerIdx + 1) % this.homepageContent.banners.length;
                }
            }, 5000);
        },
        startPromoAutoPlay() {
            if (this.promoTimer) clearInterval(this.promoTimer);
            this.promoTimer = setInterval(() => {
                if (this.promoSlides.length > 0) {
                    this.activePromoIdx = (this.activePromoIdx + 1) % this.promoSlides.length;
                }
            }, 5000);
        },
        async fetchProducts() {
            this.loadingProducts = true;
            try {
                const url = new URL(window.location.origin + '/shop/getProducts');
                if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
                if (this.selectedCategory !== 'All') url.searchParams.set('category', this.selectedCategory);

                const res = await fetch(url);
                this.products = await res.json();

                if (this.categories.length === 0) {
                    const cats = new Set(this.products.map((p) => p.category).filter(Boolean));
                    this.categories = Array.from(cats);
                }
                this.syncCartWithProducts();
            } catch (e) {
                this.showToast('Failed to retrieve products list', 'error');
            } finally {
                this.loadingProducts = false;
            }
        },
        selectCategory(cat) {
            this.selectedCategory = cat;
            this.explorePage = 0;
            this.fetchProducts();
        },
        openProductDetails(prod) {
            this.selectedProduct = prod;
            this.currentDetailImage = prod.image_url;
            this.detailQty = 1;
            this.isDetailOpen = true;
        },
        openProductDetail(prod) {
            this.openProductDetails(prod);
        },
        goToProduct(prod) {
            window.location.href = this.getProductUrl(prod);
        },
        scrollToProducts() {
            let el = document.getElementById('products-section');
            if (!el) {
                el = document.getElementById('bestselling-section');
            }
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        scrollFlash(dir) {
            const row = document.getElementById('flash-row');
            if (row) row.scrollBy({ left: dir * 660, behavior: 'smooth' });
        },
        scrollCatRow(dir) {
            const row = document.getElementById('cat-row');
            if (row) row.scrollBy({ left: dir * 400, behavior: 'smooth' });
        },
        startFlashCountdown() {
            const endTimeStr = this.homepageContent.flash_sale_end_time;
            if (!endTimeStr) {
                this.flashCountdown = null;
                return;
            }
            const endTime = new Date(endTimeStr);
            if (isNaN(endTime.getTime())) {
                this.flashCountdown = null;
                return;
            }
            this.flashCountdownEnd = endTime;

            let expiredTriggered = false;
            const tick = () => {
                const now = new Date();
                const diff = this.flashCountdownEnd - now;
                if (diff <= 0) {
                    this.flashCountdown = null;
                    if (!expiredTriggered) {
                        expiredTriggered = true;
                        this.homepageContent.flash_sale_end_time = '';
                        this.resetFlashSaleDiscounts();
                    }
                    return;
                }
                const d = String(Math.floor(diff / 86400000)).padStart(2, '0');
                const h = String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0');
                const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
                const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                this.flashCountdown = { d, h, m, s };
            };
            tick();
            setInterval(tick, 1000);
        },
        async resetFlashSaleDiscounts() {
            try {
                await fetch('/shop/resetDiscounts', { method: 'POST' });
                await this.fetchProducts();
                this.showToast('Flash sale ended. Prices have been updated.', 'info');
            } catch (e) {
                console.error('Failed to reset flash sale discounts', e);
            }
        },
        addToCart(prod, qty = 1) {
            if (prod.stock <= 0) {
                this.showToast('Product is currently out of stock', 'error');
                return;
            }

            const existing = this.cart.find((item) => item.product_id === prod._id);
            const currentQty = existing ? existing.quantity : 0;

            if (currentQty + qty > prod.stock) {
                this.showToast(`Cannot add more items. Only ${prod.stock} in stock.`, 'error');
                return;
            }

            if (existing) {
                existing.quantity += qty;
            } else {
                this.cart.push({
                    product_id: prod._id,
                    name: prod.name,
                    sku: prod.sku,
                    price: prod.price,
                    image_url: prod.image_url,
                    quantity: qty,
                });
            }

            this.saveCartToStorage();
            this.showToast(`${prod.name} added to cart!`);
            this.isDetailOpen = false;

            if (this.discountAmount > 0) {
                this.reapplyDiscount();
            }
        },
        updateCartQty(item, change) {
            const originalProduct = this.products.find((p) => p._id === item.product_id);
            const stock = originalProduct ? originalProduct.stock : 99;

            if (item.quantity + change <= 0) {
                this.removeFromCart(item);
                return;
            }

            if (item.quantity + change > stock) {
                this.showToast(`Only ${stock} items available in stock.`, 'error');
                return;
            }

            item.quantity += change;
            this.saveCartToStorage();

            if (this.discountAmount > 0) {
                this.reapplyDiscount();
            }
        },
        removeFromCart(item) {
            this.cart = this.cart.filter((c) => c.product_id !== item.product_id);
            this.saveCartToStorage();
            this.showToast('Item removed from cart');

            if (this.discountAmount > 0) {
                this.reapplyDiscount();
            }
        },
        saveCartToStorage() {
            localStorage.setItem('shop_cart', JSON.stringify(this.cart));
        },
        loadCartFromStorage() {
            const raw = localStorage.getItem('shop_cart');
            if (raw) {
                try {
                    this.cart = JSON.parse(raw);
                } catch (e) {
                    this.cart = [];
                }
            }
        },
        syncCartWithProducts() {
            if (!this.cart || this.cart.length === 0) return;
            let cartUpdated = false;
            this.cart = this.cart.map(item => {
                const prod = this.products.find(p => p._id === item.product_id);
                if (prod) {
                    if (item.price !== prod.price || item.name !== prod.name || item.image_url !== prod.image_url) {
                        item.price = prod.price;
                        item.name = prod.name;
                        item.image_url = prod.image_url;
                        cartUpdated = true;
                    }
                    if (item.quantity > prod.stock) {
                        item.quantity = prod.stock;
                        cartUpdated = true;
                    }
                }
                return item;
            }).filter(item => {
                const prodExists = this.products.find(p => p._id === item.product_id);
                if (!prodExists) {
                    cartUpdated = true;
                    return false;
                }
                return item.quantity > 0;
            });
            if (cartUpdated) {
                this.saveCartToStorage();
                if (this.discountAmount > 0) {
                    this.reapplyDiscount();
                }
            }
        },
        async applyVoucher() {
            if (!this.voucherCode) return;
            try {
                const url = new URL(window.location.origin + '/shop/validateVoucher');
                url.searchParams.set('code', this.voucherCode);

                const res = await fetch(url);
                const result = await res.json();

                if (result.valid) {
                    this.voucherValid = true;
                    this.discountType = result.type;
                    this.discountVal = result.value;
                    this.reapplyDiscount();
                    this.voucherMsg = `Promo voucher applied successfully!`;
                } else {
                    this.voucherValid = false;
                    this.voucherMsg = result.message || 'Invalid voucher code';
                    this.discountAmount = 0;
                }
            } catch (e) {
                this.showToast('Failed to validate promo code', 'error');
            }
        },
        reapplyDiscount() {
            if (this.discountType === 'percent') {
                this.discountAmount = Math.round(this.cartSubtotal * (this.discountVal / 100));
            } else {
                this.discountAmount = this.discountVal;
            }
            if (this.discountAmount > this.cartSubtotal) {
                this.discountAmount = this.cartSubtotal;
            }
        },
        removeVoucher() {
            this.voucherCode = '';
            this.voucherValid = false;
            this.voucherMsg = '';
            this.discountAmount = 0;
            this.discountVal = 0;
        },
        calculateShipping() {
            if (this.selectedCourier === 'JNE') this.shippingCost = 15000;
            else if (this.selectedCourier === 'J&T') this.shippingCost = 18000;
            else if (this.selectedCourier === 'POS') this.shippingCost = 12000;
            else this.shippingCost = 0;
        },
        openCheckoutModal() {
            this.isCartOpen = false;
            this.isCheckoutOpen = true;
        },
        async submitCheckout() {
            this.submittingOrder = true;
            try {
                const payload = {
                    customer_name: this.checkoutForm.name,
                    customer_email: this.checkoutForm.email,
                    customer_phone: this.checkoutForm.phone,
                    customer_address: this.checkoutForm.address,
                    customer_city: this.checkoutForm.city,
                    customer_zip: this.checkoutForm.zip,
                    items: this.cart.map((i) => ({ product_id: i.product_id, quantity: i.quantity })),
                    voucher_code: this.voucherCode,
                    courier: this.selectedCourier,
                    shipping_cost: this.shippingCost,
                };

                const res = await fetch('/shop/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();
                if (res.status !== 200) {
                    throw new Error(data.error || 'Server error during checkout');
                }

                this.isCheckoutOpen = false;

                if (data.order.snap_token && typeof snap !== 'undefined') {
                    const orderData = data.order;
                    const self = this;
                    const finishedBase = window.ENABLE_FRONTEND ? '/finished-order' : '/shop/finished-order';

                    snap.pay(data.order.snap_token, {
                        onSuccess: function (result) {
                            self.cart = [];
                            self.saveCartToStorage();
                            self.removeVoucher();
                            window.location.href =
                                finishedBase +
                                '?order_id=' +
                                encodeURIComponent(result.order_id || orderData.transaction_id) +
                                '&status_code=' +
                                encodeURIComponent(result.status_code || '200') +
                                '&transaction_status=' +
                                encodeURIComponent(result.transaction_status || 'settlement');
                        },
                        onPending: function (result) {
                            self.cart = [];
                            self.saveCartToStorage();
                            self.removeVoucher();
                            window.location.href =
                                finishedBase +
                                '?order_id=' +
                                encodeURIComponent(result.order_id || orderData.transaction_id) +
                                '&status_code=' +
                                encodeURIComponent(result.status_code || '201') +
                                '&transaction_status=' +
                                encodeURIComponent(result.transaction_status || 'pending');
                        },
                        onError: function (result) {
                            self.cart = [];
                            self.saveCartToStorage();
                            self.removeVoucher();
                            window.location.href =
                                finishedBase +
                                '?order_id=' +
                                encodeURIComponent(result.order_id || orderData.transaction_id) +
                                '&status_code=' +
                                encodeURIComponent(result.status_code || '400') +
                                '&transaction_status=' +
                                encodeURIComponent(result.transaction_status || 'failed');
                        },
                        onClose: function () {
                            self.cart = [];
                            self.saveCartToStorage();
                            self.removeVoucher();
                            window.location.href =
                                finishedBase +
                                '?order_id=' +
                                encodeURIComponent(orderData.transaction_id) +
                                '&status_code=201&transaction_status=pending';
                        },
                    });
                } else if (data.order.redirect_url) {
                    this.cart = [];
                    this.saveCartToStorage();
                    this.removeVoucher();
                    window.location.href = data.order.redirect_url;
                } else {
                    this.activePendingOrder = data.order;
                    this.isPaymentOpen = true;
                    this.paymentStep = 'pending';
                }
            } catch (e) {
                this.showToast(e.message || 'Checkout failed. Please check stock levels.', 'error');
            } finally {
                this.submittingOrder = false;
            }
        },
        cancelPayment() {
            this.isPaymentOpen = false;
            this.showToast('Payment cancelled. You can pay from Order History later.', 'error');
            const order = this.activePendingOrder;
            this.cart = [];
            this.saveCartToStorage();
            this.removeVoucher();
            const finishedBase = window.ENABLE_FRONTEND ? '/finished-order' : '/shop/finished-order';
            if (order) {
                window.location.href =
                    finishedBase +
                    '?order_id=' +
                    encodeURIComponent(order.transaction_id || '') +
                    '&status_code=201&transaction_status=pending';
            }
        },
        payPendingOrder(order) {
            const finishedBase = window.ENABLE_FRONTEND ? '/finished-order' : '/shop/finished-order';
            if (order.snap_token && typeof snap !== 'undefined') {
                const self = this;
                snap.pay(order.snap_token, {
                    onSuccess: function (result) {
                        window.location.href =
                            finishedBase +
                            '?order_id=' +
                            encodeURIComponent(result.order_id || order.transaction_id) +
                            '&status_code=' +
                            encodeURIComponent(result.status_code || '200') +
                            '&transaction_status=' +
                            encodeURIComponent(result.transaction_status || 'settlement');
                    },
                    onPending: function (result) {
                        window.location.href =
                            finishedBase +
                            '?order_id=' +
                            encodeURIComponent(result.order_id || order.transaction_id) +
                            '&status_code=' +
                            encodeURIComponent(result.status_code || '201') +
                            '&transaction_status=' +
                            encodeURIComponent(result.transaction_status || 'pending');
                    },
                    onError: function (result) {
                        window.location.href =
                            finishedBase +
                            '?order_id=' +
                            encodeURIComponent(result.order_id || order.transaction_id) +
                            '&status_code=' +
                            encodeURIComponent(result.status_code || '400') +
                            '&transaction_status=' +
                            encodeURIComponent(result.transaction_status || 'failed');
                    },
                    onClose: function () {
                        window.location.href =
                            finishedBase +
                            '?order_id=' +
                            encodeURIComponent(order.transaction_id || '') +
                            '&status_code=201&transaction_status=pending';
                    },
                });
            } else if (order.redirect_url) {
                window.location.href = order.redirect_url;
            } else {
                this.openPaymentSimulator(order);
            }
        },
        simulatePaymentSuccess() {
            this.paymentStep = 'processing';
            const order = this.activePendingOrder;
            const finishedBase = window.ENABLE_FRONTEND ? '/finished-order' : '/shop/finished-order';
            setTimeout(() => {
                try {
                    this.isPaymentOpen = false;
                    this.cart = [];
                    this.saveCartToStorage();
                    this.removeVoucher();
                    window.location.href =
                        finishedBase +
                        '?order_id=' +
                        encodeURIComponent((order && order.transaction_id) || '') +
                        '&status_code=200&transaction_status=settlement';
                } catch (err) {
                    this.showToast('Payment simulation error', 'error');
                    this.isPaymentOpen = false;
                }
            }, 1500);
        },
        async trackOrder() {
            this.loadingTrack = true;
            try {
                const url = new URL(window.location.origin + '/shop/checkOrderStatus');
                url.searchParams.set('email', this.trackEmail);
                url.searchParams.set('order_id', this.trackOrderId);

                const res = await fetch(url);
                const data = await res.json();

                if (res.status !== 200) {
                    throw new Error(data.error || 'No matching order found');
                }

                this.activeTrackedOrder = data;
            } catch (e) {
                this.showToast(e.message, 'error');
                this.activeTrackedOrder = null;
            } finally {
                this.loadingTrack = false;
            }
        },
        openPaymentSimulator(order) {
            this.activePendingOrder = order;
            this.isPaymentOpen = true;
            this.paymentStep = 'pending';
        },

        formatIDR(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
            }).format(value);
        },
        formatDate(timestamp) {
            return new Date(timestamp * 1000).toLocaleString('en-US', {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
        },
        getStockClass(stock) {
            if (stock === 0) return 'empty';
            if (stock <= 5) return 'low';
            return 'available';
        },
        getOrderStatusClass(status) {
            if (status === 'completed') return 'available';
            if (status === 'cancelled' || status === 'refunded') return 'empty';
            return 'low';
        },
        getTimelineProgressScale(status) {
            if (status === 'pending') return 0;
            if (status === 'processing') return 0.3333;
            if (status === 'shipping') return 0.6666;
            if (status === 'completed') return 1;
            return 0;
        },
        getTimelineStepClass(orderStatus, stepName) {
            const stages = ['pending', 'processing', 'shipping', 'completed'];
            const currentIdx = stages.indexOf(orderStatus);
            const stepIdx = stages.indexOf(stepName);

            if (currentIdx === -1) return '';
            if (currentIdx > stepIdx) return 'completed';
            if (currentIdx === stepIdx) return 'active';
            return '';
        },

        async loadCustomerDashboard() {
            try {
                const res = await fetch('/shop/getCustomerDashboard');
                const data = await res.json();
                if (data.logged_in) {
                    this.customerSession = data;

                    this.profileForm.name = data.customer.name || '';
                    this.profileForm.phone = data.customer.phone || '';
                    this.profileForm.address = data.customer.address || '';
                    this.profileForm.city = data.customer.city || '';
                    this.profileForm.zip = data.customer.zip || '';
                    this.profileForm.password = '';

                    if (!this.checkoutForm.email) {
                        this.checkoutForm.name = data.customer.name || '';
                        this.checkoutForm.email = data.customer.email || '';
                        this.checkoutForm.phone = data.customer.phone || '';
                        this.checkoutForm.address = data.customer.address || '';
                        this.checkoutForm.city = data.customer.city || '';
                        this.checkoutForm.zip = data.customer.zip || '';
                    }
                } else {
                    this.customerSession = { logged_in: false, customer: null, orders: [] };
                }
            } catch (e) {
                this.showToast('Failed to check customer session', 'error');
            }
        },
        async submitLogin() {
            this.submittingAuth = true;
            try {
                const res = await fetch('/shop/customerLogin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: this.authForm.email,
                        password: this.authForm.password,
                    }),
                });
                const data = await res.json();
                if (res.status !== 200) {
                    throw new Error(data.error || 'Login failed');
                }
                this.showToast('Logged in successfully!');
                this.authForm.password = '';
                await this.loadCustomerDashboard();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.submittingAuth = false;
            }
        },
        async submitRegister() {
            this.submittingAuth = true;
            try {
                const res = await fetch('/shop/customerRegister', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.authForm),
                });
                const data = await res.json();
                if (res.status !== 200) {
                    throw new Error(data.error || 'Registration failed');
                }
                this.showToast('Account registered successfully!');
                this.authForm = { name: '', email: '', password: '', phone: '', address: '', city: '', zip: '' };
                await this.loadCustomerDashboard();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.submittingAuth = false;
            }
        },
        async submitLogout() {
            try {
                await fetch('/shop/customerLogout');
                this.customerSession = { logged_in: false, customer: null, orders: [] };
                this.checkoutForm = { name: '', email: '', phone: '', address: '', city: '', zip: '' };
                this.showToast('Logged out successfully.');
                this.currentTab = 'shop';
            } catch (e) {
                this.showToast('Failed to logout', 'error');
            }
        },
        async submitForgotPassword() {
            this.submittingAuth = true;
            try {
                const res = await fetch('/shop/customerForgotPassword', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: this.authForm.email }),
                });
                const data = await res.json();
                this.showToast(data.message || 'Reset link sent.');
                this.activeAuthForm = 'login';
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.submittingAuth = false;
            }
        },
        async submitResetPassword() {
            this.submittingAuth = true;
            try {
                const res = await fetch('/shop/customerResetPassword', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: this.urlParams.email,
                        token: this.urlParams.token,
                        password: this.authForm.password,
                    }),
                });
                const data = await res.json();
                if (res.status !== 200) {
                    throw new Error(data.error || 'Reset failed');
                }
                this.showToast('Password changed successfully! You can login now.');
                this.activeAuthForm = 'login';
                this.authForm.password = '';
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.submittingAuth = false;
            }
        },
        async submitUpdateProfile() {
            this.updatingProfile = true;
            try {
                const res = await fetch('/shop/updateCustomerProfile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.profileForm),
                });
                const data = await res.json();
                if (res.status !== 200) {
                    throw new Error(data.error || 'Profile update failed');
                }
                this.showToast('Profile changes saved!');
                await this.loadCustomerDashboard();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.updatingProfile = false;
            }
        },
        quickTrackOrder(order) {
            const enableFrontend = window.ENABLE_FRONTEND || false;
            const baseUrl = enableFrontend ? '/tracker' : '/shop/tracker';
            const url = `${baseUrl}?order_id=${encodeURIComponent(order.order_id)}&email=${encodeURIComponent(order.customer_email || this.customerSession.customer.email)}`;
            window.location.href = url;
        },
        getProductUrl(prod) {
            const enableFrontend = window.ENABLE_FRONTEND || false;
            return enableFrontend ? `/product/${prod._id}` : `/shop/product/${prod._id}`;
        },
        goToProductById(id) {
            const enableFrontend = window.ENABLE_FRONTEND || false;
            window.location.href = enableFrontend ? `/product/${id}` : `/shop/product/${id}`;
        },
    },
}).mount('#shop-app');
