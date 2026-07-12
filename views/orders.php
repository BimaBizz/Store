<style>
    .status-badge {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-block;
    }
    .status-completed { background-color: rgba(6, 214, 160, 0.1); color: #06d6a0; }
    .status-processing { background-color: rgba(58, 134, 255, 0.1); color: #3a86ff; }
    .status-shipping { background-color: rgba(131, 56, 236, 0.1); color: #8338ec; }
    .status-pending { background-color: rgba(255, 190, 11, 0.1); color: #ffbe0b; }
    .status-cancelled { background-color: rgba(239, 71, 111, 0.1); color: #ef4771; }
    .status-refunded { background-color: rgba(120, 120, 120, 0.15); color: #777777; }
    
    
    .clickable-row:hover {
        background: rgba(128, 128, 128, 0.08) !important;
    }
</style>

<vue-view>
    <template>
        <div class="kiss-margin-small-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?= $this->route('/store') ?>"><?= t('Store') ?></a></li>
                        <li><span><?= t('Orders') ?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Sales Orders & Fulfillment</h3>
                </div>
                <div>
                    <button class="kiss-button kiss-button-success" @click="openCreateOrderModal">Simulate Customer Purchase</button>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                <kiss-card theme="bordered contrast" class="kiss-padding-large">
                    <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                        <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Sales Orders</div>
                        <div class="kiss-width-1-4@m">
                            <input type="text" class="kiss-input" placeholder="Search orders..." v-model="searchQuery">
                        </div>
                    </div>

                    <div v-if="orders.length">
                        
                        <div class="kiss-overflow-auto" v-if="view === 'table'">
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Customer Details</th>
                                        <th>Courier & Tracking</th>
                                        <th>Items Ordered</th>
                                        <th>Grand Total</th>
                                        <th>Order Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in orders" :key="order._id">
                                        <td><code class="kiss-text-bold">{{ order.order_id }}</code></td>
                                        <td>{{ formatDate(order.created) }}</td>
                                        <td>
                                            <div class="kiss-text-bold">{{ order.customer_name }}</div>
                                            <small class="kiss-color-muted">{{ order.customer_email }}</small>
                                        </td>
                                        <td>
                                            <div v-if="order.courier">
                                                <span class="kiss-badge kiss-badge-outline kiss-size-xsmall">{{ order.courier }}</span>
                                                <div v-if="order.resi" class="kiss-margin-xsmall-top"><small class="kiss-color-muted">Resi: <code>{{ order.resi }}</code></small></div>
                                            </div>
                                            <div v-else><small class="kiss-color-muted">—</small></div>
                                        </td>
                                        <td>
                                            <div v-for="item in order.items" class="kiss-size-xsmall">
                                                - {{ item.name }} (x{{ item.quantity }})
                                            </div>
                                        </td>
                                        <td class="kiss-text-bold">{{ formatCurrency(order.total_amount) }}</td>
                                        <td>
                                            <span :class="['status-badge', 'status-' + order.status]">{{ order.status }}</span>
                                        </td>
                                        <td>
                                            <span v-if="order.payment_status === 'settled'" class="status-badge status-completed">Paid</span>
                                            <span v-else-if="order.payment_status === 'pending'" class="status-badge status-pending">Pending</span>
                                            <span v-else class="status-badge status-cancelled">Failed</span>
                                        </td>
                                        <td>
                                            <div class="kiss-flex" gap="xsmall" style="gap: 4px;">
                                                <button class="kiss-button kiss-button-small" @click="showOrderDetails(order)">Details</button>
                                                <a v-if="order.payment_status === 'pending' && order.redirect_url" :href="order.redirect_url" target="_blank" class="kiss-button kiss-button-small kiss-button-primary">Pay</a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        
                        <kiss-grid cols="1 3@m" gap="medium" class="kiss-margin-bottom" v-else>
                            <div v-for="order in orders" :key="order._id">
                                <kiss-card theme="bordered contrast" class="kiss-padding clickable-row" @click="showOrderDetails(order)" style="cursor: pointer;">
                                    <div class="kiss-flex kiss-flex-middle kiss-flex-between kiss-margin-bottom">
                                        <code class="kiss-text-bold">{{ order.order_id }}</code>
                                        <span :class="['status-badge', 'status-' + order.status]">{{ order.status }}</span>
                                    </div>
                                    <div class="kiss-margin-small-bottom">
                                        <div class="kiss-text-bold">{{ order.customer_name }}</div>
                                        <small class="kiss-color-muted">{{ order.customer_email }}</small>
                                    </div>
                                    <div class="kiss-margin-top" style="border-top: 1px dashed rgba(255,255,255,0.05); padding-top: 8px;">
                                        <div v-for="item in order.items" class="kiss-size-xsmall kiss-color-muted">
                                            {{ item.name }} (x{{ item.quantity }})
                                        </div>
                                    </div>
                                    <div class="kiss-margin-small-top kiss-flex kiss-flex-middle kiss-flex-between">
                                        <div>
                                            <span class="kiss-color-muted kiss-size-xsmall">TOTAL</span>
                                            <div class="kiss-text-bold" style="color: #06d6a0;">{{ formatCurrency(order.total_amount) }}</div>
                                        </div>
                                        <div>
                                            <span v-if="order.payment_status === 'settled'" class="status-badge status-completed">Paid</span>
                                            <span v-else-if="order.payment_status === 'pending'" class="status-badge status-pending">Pending</span>
                                            <span v-else class="status-badge status-cancelled">Failed</span>
                                        </div>
                                    </div>
                                    <div class="kiss-margin-top kiss-flex kiss-flex-right">
                                        <button class="kiss-button kiss-button-small" @click.stop="showOrderDetails(order)">Details</button>
                                        <a v-if="order.payment_status === 'pending' && order.redirect_url" :href="order.redirect_url" target="_blank" class="kiss-button kiss-button-small kiss-button-primary kiss-margin-small-start" @click.stop="">Pay</a>
                                    </div>
                                </kiss-card>
                            </div>
                        </kiss-grid>

                        
                        <div class="kiss-flex kiss-flex-middle kiss-margin-small-top">
                            <div class="kiss-flex-1">
                                <app-pagination v-if="count">
                                    <div class="kiss-color-muted">{{ count }} orders</div>
                                    <a class="kiss-margin-small-start" v-if="(page - 1) >= 1" @click="page--; loadOrders()">Previous</a>
                                    <div class="kiss-margin-small-start kiss-overlay-input" v-if="count > limit">
                                        <strong>{{ page }} &mdash; {{pages}}</strong>
                                        <select v-model="page" @change="loadOrders()" v-if="pages > 1">
                                            <option v-for="p in pages" :value="p">{{ p }}</option>
                                        </select>
                                    </div>
                                    <a class="kiss-margin-small-start" v-if="(page + 1) <= pages" @click="page++; loadOrders()">Next</a>
                                    
                                    
                                    <div class="kiss-margin-start kiss-overlay-input">
                                        <span class="kiss-color-muted">Show:</span> {{ limit }}
                                        <select v-model="limit" @change="page = 1; loadOrders()">
                                            <option v-for="l in [5, 10, 15, 20, 25]" :value="l">{{ l }}</option>
                                        </select>
                                    </div>
                                    
                                    
                                    <div class="kiss-margin-start">
                                        <a @click="sortDir = sortDir == -1 ? 1 : -1; loadOrders()"><icon>{{ sortDir == 1 ? 'arrow_downward':'arrow_upward' }}</icon></a>
                                        <div class="kiss-margin-xsmall-start kiss-overlay-input">
                                            <span class="kiss-color-muted">{{ sortOptions[sortKey] }}</span>
                                            <select v-model="sortKey" @change="loadOrders()">
                                                <option v-for="(lbl, key) in sortOptions" :value="key">{{ lbl }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </app-pagination>
                            </div>
                            
                            
                            <div class="kiss-flex kiss-flex-middle" gap="small" style="gap: 12px;">
                                <a class="kiss-link-muted" :class="view=='cards' ? 'kiss-color-primary' : 'kiss-color-muted'" @click="view='cards'"><icon size="large">grid_view</icon></a>
                                <a class="kiss-link-muted" :class="view=='table' ? 'kiss-color-primary' : 'kiss-color-muted'" @click="view='table'"><icon size="large">dns</icon></a>
                            </div>
                        </div>
                    </div>
                    <div v-else class="kiss-align-center kiss-padding-large kiss-color-muted">
                        No orders found. Click "Simulate Customer Purchase" to create test transactions.
                    </div>
                </kiss-card>
            </div>
        </div>

        
        <kiss-dialog ref="orderModal">
            <kiss-content class="kiss-padding-large" v-if="orderForm" style="width: 750px; max-width: 100%;">
                <h3>Checkout Simulation</h3>
                
                <form @submit.prevent="createOrder">
                    <kiss-grid cols="1 2@m" gap="medium">
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Customer Name</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" placeholder="e.g. Ahmad" v-model="orderForm.customer_name" required>
                        </div>
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Customer Email</label>
                            <input type="email" class="kiss-input kiss-margin-small-top" placeholder="e.g. ahmad@example.com" v-model="orderForm.customer_email" required>
                        </div>
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Courier Service</label>
                            <select class="kiss-input kiss-select kiss-margin-small-top" v-model="orderForm.courier">
                                <option value="Manual">Manual Delivery</option>
                                <option value="JNE Regular">JNE Regular</option>
                                <option value="JNT Express">J&T Express</option>
                                <option value="GoSend">GoSend instant</option>
                            </select>
                        </div>
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Shipping Cost (IDR)</label>
                            <input type="number" class="kiss-input kiss-margin-small-top" v-model.number="orderForm.shipping_cost" @input="calculateTotals">
                        </div>
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Promo Voucher Code (Optional)</label>
                            <select class="kiss-input kiss-select kiss-margin-small-top" v-model="orderForm.voucher_code" @change="calculateTotals">
                                <option value="">-- No Promo Applied --</option>
                                <option v-for="v in activeVouchers" :key="v.code" :value="v.code">{{ v.code }} ({{ v.type === 'percent' ? v.value + '%' : formatCurrency(v.value) }})</option>
                            </select>
                        </div>
                    </kiss-grid>

                    <div class="kiss-margin-small-top">
                        <label class="kiss-text-bold kiss-size-small">Select Products & Quantities</label>
                        <div class="kiss-overflow-auto kiss-margin-small-top" style="max-height: 200px; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 10px;">
                            <div v-for="prod in products" :key="prod._id" class="kiss-flex kiss-flex-middle kiss-margin-small-bottom kiss-padding-small" style="background: rgba(0,0,0,0.02); border-radius: 4px; border: 1px solid rgba(255,255,255,0.03);">
                                <div class="kiss-flex-1">
                                    <div class="kiss-text-bold">{{ prod.name }}</div>
                                    <small class="kiss-color-muted">{{ formatCurrency(prod.price) }} — Stock: {{ prod.stock }}</small>
                                </div>
                                <div class="kiss-flex kiss-flex-middle" gap="xsmall" style="gap: 8px;">
                                    <input type="number" class="kiss-input" style="width: 70px;" min="0" :max="prod.stock" placeholder="0" v-model.number="orderForm.quantities[prod._id]" @input="calculateTotals">
                                </div>
                            </div>
                        </div>
                    </div>

                    <kiss-card theme="contrast" class="kiss-padding kiss-margin-small-top" style="background: rgba(0,0,0,0.15);">
                        <div class="kiss-flex kiss-flex-between kiss-size-small kiss-margin-xsmall-bottom">
                            <span>Subtotal:</span>
                            <span>{{ formatCurrency(totals.subtotal) }}</span>
                        </div>
                        <div class="kiss-flex kiss-flex-between kiss-size-small kiss-margin-xsmall-bottom" style="color: #ef4771;" v-if="totals.discount > 0">
                            <span>Promo Discount:</span>
                            <span>- {{ formatCurrency(totals.discount) }}</span>
                        </div>
                        <div class="kiss-flex kiss-flex-between kiss-size-small kiss-margin-xsmall-bottom">
                            <span>Tax ({{ shopSettings.tax_percent || 0 }}%):</span>
                            <span>{{ formatCurrency(totals.tax) }}</span>
                        </div>
                        <div class="kiss-flex kiss-flex-between kiss-size-small kiss-margin-small-bottom">
                            <span>Shipping Cost:</span>
                            <span>{{ formatCurrency(totals.shipping) }}</span>
                        </div>
                        <hr style="margin: 8px 0; border-color: rgba(255,255,255,0.05);">
                        <div class="kiss-flex kiss-flex-between kiss-text-bold">
                            <span>Grand Total:</span>
                            <span style="color: #06d6a0;">{{ formatCurrency(totals.grandTotal) }}</span>
                        </div>
                    </kiss-card>

                    <div class="kiss-margin-small-top kiss-flex kiss-flex-right" gap="small" style="gap: 8px;">
                        <button type="button" class="kiss-button" @click="$refs.orderModal.close()">Cancel</button>
                        <button type="submit" class="kiss-button kiss-button-success" :disabled="creatingOrder">
                            {{ creatingOrder ? 'Processing...' : 'Place simulated order' }}
                        </button>
                    </div>
                </form>
            </kiss-content>
        </kiss-dialog>

        
        <kiss-dialog ref="orderDetailsModal">
            <kiss-content class="kiss-padding-large" v-if="activeOrder" style="width: 750px; max-width: 100%;">
                <div class="kiss-flex kiss-flex-middle kiss-margin-bottom">
                    <div class="kiss-flex-1">
                        <h3 class="kiss-margin-none">Order Fulfillments Details</h3>
                        <small class="kiss-color-muted">Order ID: <code>{{ activeOrder.order_id }}</code></small>
                    </div>
                    <div>
                        <span :class="['status-badge', 'status-' + activeOrder.status]">{{ activeOrder.status }}</span>
                    </div>
                </div>

                <kiss-card theme="bordered" class="kiss-padding kiss-margin-bottom" style="background: rgba(0,0,0,0.02);">
                    <kiss-grid cols="1 2@m" gap="medium">
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">CUSTOMER DETAILS</span>
                            <div class="kiss-text-bold">{{ activeOrder.customer_name }}</div>
                            <div class="kiss-size-small"><code>{{ activeOrder.customer_email }}</code></div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">PAYMENT STATUS</span>
                            <div>
                                <span v-if="activeOrder.payment_status === 'settled'" class="status-badge status-completed">PAID (SETTLED)</span>
                                <span v-else-if="activeOrder.payment_status === 'pending'" class="status-badge status-pending">PENDING</span>
                                <span v-else class="status-badge status-cancelled">FAILED</span>
                            </div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">TRANSACTION ID</span>
                            <div class="kiss-size-small"><code>{{ activeOrder.transaction_id || '—' }}</code></div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">COURIER TRACKING</span>
                            <div v-if="activeOrder.courier">
                                <span class="kiss-badge kiss-badge-outline kiss-size-xsmall">{{ activeOrder.courier }}</span>
                                <div class="kiss-size-small kiss-color-muted kiss-margin-xsmall-top" v-if="activeOrder.resi">Resi: <code>{{ activeOrder.resi }}</code></div>
                            </div>
                            <div v-else>—</div>
                        </div>
                    </kiss-grid>
                </kiss-card>

                <div class="kiss-margin-small-top">
                    <div class="kiss-text-bold kiss-size-medium kiss-margin-small-bottom">Items Ordered</div>
                    <table class="kiss-table">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in activeOrder.items">
                                <td>{{ item.name }}</td>
                                <td>{{ formatCurrency(item.price) }}</td>
                                <td>x{{ item.quantity }}</td>
                                <td class="kiss-text-bold">{{ formatCurrency(item.price * item.quantity) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="kiss-margin-top kiss-padding" style="background: rgba(0,0,0,0.05); border-radius: 6px; width: 300px; margin-left: auto;">
                        <hr style="margin: 8px 0; border-color: rgba(255,255,255,0.05);">
                        <div class="kiss-flex kiss-flex-between kiss-text-bold">
                            <span>Grand Total:</span>
                            <span style="color: #e63946;">{{ formatCurrency(activeOrder.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                
                <div v-if="activeOrder.status === 'processing'" class="kiss-margin-small-top kiss-padding" style="border: 1px solid rgba(58,134,255,0.3); border-radius: 8px; background: rgba(58,134,255,0.02);">
                    <div class="kiss-text-bold kiss-color-primary kiss-margin-small-bottom">Process Shipment Fulfillments</div>
                    <kiss-grid cols="1 2@m" gap="small">
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Courier</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="fulfillment.courier" placeholder="e.g. JNE Regular">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Resi/AWB Code</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="fulfillment.resi" placeholder="e.g. JNE1234567">
                        </div>
                    </kiss-grid>
                    <div class="kiss-margin-top kiss-flex kiss-flex-right">
                        <button type="button" class="kiss-button kiss-button-primary kiss-button-small" @click="shipOrder(activeOrder)">
                            Fulfill & Ship Order
                        </button>
                    </div>
                </div>

                <hr class="kiss-margin-large">

                <div class="kiss-flex kiss-flex-between kiss-flex-middle">
                    <div>
                        
                        <button v-if="activeOrder.status === 'completed'" class="kiss-button kiss-button-danger kiss-button-small" @click="updateOrderStatus(activeOrder, 'refunded')">
                            Process Refund/Return
                        </button>
                        <button v-if="activeOrder.status === 'pending'" class="kiss-button kiss-button-danger kiss-button-small" @click="updateOrderStatus(activeOrder, 'cancelled')">
                            Cancel Order
                        </button>
                    </div>
                    <div class="kiss-flex" gap="small" style="gap: 8px;">
                        <button class="kiss-button" @click="$refs.orderDetailsModal.close()">Close</button>
                        
                        
                        <button v-if="activeOrder.status === 'shipping'" class="kiss-button kiss-button-success" @click="updateOrderStatus(activeOrder, 'completed')">
                            Deliver / Complete Order
                        </button>
                        
                        <button v-if="activeOrder.status === 'pending'" class="kiss-button kiss-button-primary" @click="updateOrderStatus(activeOrder, 'completed')">
                            Mark Paid & Completed
                        </button>
                    </div>
                </div>
            </kiss-content>
        </kiss-dialog>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    products: [],
                    orders: [],
                    vouchers: [],
                    shopSettings: {},
                    loading: true,
                    creatingOrder: false,
                    orderForm: null,
                    activeOrder: null,
                    totals: {
                        subtotal: 0,
                        discount: 0,
                        tax: 0,
                        shipping: 0,
                        grandTotal: 0
                    },
                    fulfillment: {
                        courier: '',
                        resi: ''
                    },
                    page: 1,
                    pages: 1,
                    limit: 5,
                    count: 0,
                    searchQuery: '',
                    searchTimer: null,
                    view: 'table',
                    sortKey: 'created',
                    sortDir: -1,
                    sortOptions: {
                        created: 'Order Date',
                        total_amount: 'Amount',
                        status: 'Fulfillment Status'
                    }
                }
            },

            watch: {
                searchQuery() {
                    this.page = 1;
                    if (this.searchTimer) clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        this.loadOrders();
                    }, 300);
                }
            },

            computed: {
                activeVouchers() {
                    return this.vouchers.filter(v => v.active === true || v.active === 'true' || v.active === 1);
                }
            },

            mounted() {
                this.loadData();
            },

            methods: {
                loadData() {
                    this.loading = true;
                    Promise.all([
                        this.$request('/store/getProducts', { limit: 1000 }),
                        this.$request('/store/getVouchers', { limit: 1000 }),
                        this.$request('/store/getSettings')
                    ]).then(([productsRes, vouchersRes, settings]) => {
                        if (Array.isArray(productsRes)) {
                            this.products = productsRes;
                        } else {
                            this.products = productsRes.products || [];
                        }
                        if (Array.isArray(vouchersRes)) {
                            this.vouchers = vouchersRes;
                        } else {
                            this.vouchers = vouchersRes.vouchers || [];
                        }
                        this.shopSettings = settings || {};
                        
                        this.loadOrders();
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error fetching orders details', 'danger');
                    });
                },
                loadOrders() {
                    this.$request('/store/getOrders', {
                        page: this.page,
                        limit: this.limit,
                        search: this.searchQuery,
                        sort: this.sortKey,
                        sort_dir: this.sortDir
                    }).then(res => {
                        if (Array.isArray(res)) {
                            this.orders = res;
                            this.count = res.length;
                            this.pages = 1;
                            this.page = 1;
                        } else {
                            this.orders = res.orders || [];
                            this.count = res.count || 0;
                            this.pages = res.pages || 1;
                            this.page = res.page || 1;
                        }
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error fetching orders list', 'danger');
                    });
                },
                formatCurrency(value) {
                    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
                },
                formatDate(timestamp) {
                    const date = new Date(timestamp * 1000);
                    return date.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                openCreateOrderModal() {
                    let quantities = {};
                    this.products.forEach(p => {
                        quantities[p._id] = 0;
                    });

                    this.orderForm = {
                        customer_name: '',
                        customer_email: '',
                        quantities: quantities,
                        courier: 'Manual',
                        shipping_cost: 0,
                        voucher_code: ''
                    };
                    this.calculateTotals();
                    this.$refs.orderModal.show();
                },
                calculateTotals() {
                    if (!this.orderForm) return;

                    let subtotal = 0;
                    this.products.forEach(p => {
                        const qty = parseInt(this.orderForm.quantities[p._id]) || 0;
                        if (qty > 0) {
                            subtotal += p.price * qty;
                        }
                    });

                    let discount = 0;
                    if (this.orderForm.voucher_code) {
                        const voucher = this.vouchers.find(v => v.code === this.orderForm.voucher_code);
                        if (voucher) {
                            if (voucher.type === 'percent') {
                                discount = subtotal * (parseFloat(voucher.value) / 100);
                            } else {
                                discount = parseFloat(voucher.value);
                            }
                            if (discount > subtotal) discount = subtotal;
                        }
                    }

                    const taxPercent = parseFloat(this.shopSettings.tax_percent) || 0;
                    const tax = (subtotal - discount) * (taxPercent / 100);
                    const shipping = parseFloat(this.orderForm.shipping_cost) || 0;

                    this.totals.subtotal = subtotal;
                    this.totals.discount = discount;
                    this.totals.tax = tax;
                    this.totals.shipping = shipping;
                    this.totals.grandTotal = (subtotal - discount) + tax + shipping;
                },
                createOrder() {
                    let items = [];
                    Object.entries(this.orderForm.quantities).forEach(([prodId, qty]) => {
                        if (qty > 0) {
                            items.push({
                                product_id: prodId,
                                quantity: qty
                            });
                        }
                    });

                    if (items.length === 0) {
                        App.ui.notify('Please select at least 1 product with quantity > 0', 'warning');
                        return;
                    }

                    this.creatingOrder = true;
                    this.$request('/store/createOrder', {
                        customer_name: this.orderForm.customer_name,
                        customer_email: this.orderForm.customer_email,
                        items: items,
                        courier: this.orderForm.courier,
                        shipping_cost: this.orderForm.shipping_cost,
                        voucher_code: this.orderForm.voucher_code
                    }).then(res => {
                        this.creatingOrder = false;
                        this.$refs.orderModal.close();
                        this.loadOrders();
                        App.ui.notify('Order checkout simulation successful!', 'success');
                        
                        if (res.order && res.order.redirect_url) {
                            if (confirm('A payment token has been generated. Open Midtrans checkout now?')) {
                                window.open(res.order.redirect_url, '_blank');
                            }
                        }
                    }).catch(err => {
                        this.creatingOrder = false;
                        App.ui.notify(err.message || 'Error processing order simulation', 'danger');
                    });
                },
                showOrderDetails(order) {
                    this.activeOrder = order;
                    this.fulfillment.courier = order.courier || 'Manual';
                    this.fulfillment.resi = order.resi || '';
                    this.$refs.orderDetailsModal.show();
                },
                shipOrder(order) {
                    if (!this.fulfillment.courier || !this.fulfillment.resi) {
                        App.ui.notify('Please fill both Courier and Resi code to ship', 'warning');
                        return;
                    }
                    this.$request('/store/updateOrderStatus', {
                        id: order._id,
                        status: 'shipping',
                        courier: this.fulfillment.courier,
                        resi: this.fulfillment.resi
                    }).then(res => {
                        this.$refs.orderDetailsModal.close();
                        this.loadOrders();
                        App.ui.notify(`Order ${order.order_id} fulfilled and shipped!`, 'success');
                    }).catch(err => {
                        App.ui.notify('Error updating shipment status', 'danger');
                    });
                },
                updateOrderStatus(order, status) {
                    this.$request('/store/updateOrderStatus', { id: order._id, status }).then(res => {
                        this.$refs.orderDetailsModal.close();
                        this.loadOrders();
                        App.ui.notify(`Order ${order.order_id} marked as ${status}!`, 'success');
                    }).catch(err => {
                        App.ui.notify('Error updating order status', 'danger');
                    });
                }
            }
        }
    </script>
</vue-view>

<?= $this->render('store:views/partials/sidebar.php') ?>
