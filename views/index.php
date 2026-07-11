<?php
// Renders the Store/Toko Dashboard Manager UI
?>

<style>
    .store-hero {
        background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%);
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .store-hero-glow {
        position: absolute;
        bottom: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(230, 57, 70, 0.2) 0%, rgba(230, 57, 70, 0) 70%);
        pointer-events: none;
    }
    .stock-badge {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }
    .stock-ok {
        background-color: rgba(6, 214, 160, 0.1);
        color: #06d6a0;
    }
    .stock-low {
        background-color: rgba(255, 190, 11, 0.1);
        color: #ffbe0b;
    }
    .stock-empty {
        background-color: rgba(239, 71, 111, 0.1);
        color: #ef4771;
    }
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
</style>

<vue-view>
    <template>
        <div class="kiss-margin-large-top kiss-margin-large-bottom" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <!-- Hero Banner -->
            <div class="store-hero kiss-padding-large kiss-margin-large-bottom">
                <div class="store-hero-glow"></div>
                <div class="kiss-flex kiss-flex-middle">
                    <div class="kiss-flex-1">
                        <div class="kiss-size-large kiss-text-bold" style="color: #ffffff;">Online Store Manager</div>
                        <div class="kiss-size-small kiss-margin-xsmall-top" style="color: rgba(255,255,255,0.7);">
                            Manage store products, process order shipments, and review billing status.
                        </div>
                    </div>
                </div>
            </div>

            <div class="kiss-padding-large kiss-align-center" v-if="loading">
                <app-loader></app-loader>
            </div>

            <div v-else class="animated fadeIn">
                <kiss-grid cols="1 5@m" gap="medium" class="kiss-margin-large-bottom">
                    <kiss-card theme="bordered contrast" class="kiss-padding">
                        <div class="kiss-size-xsmall kiss-color-muted kiss-text-bold">TOTAL REVENUE</div>
                        <div class="kiss-size-large kiss-text-bold kiss-margin-xsmall-top" style="color: #06d6a0;">
                            {{ formatCurrency(stats.totalSales) }}
                        </div>
                    </kiss-card>
                    <kiss-card theme="bordered contrast" class="kiss-padding">
                        <div class="kiss-size-xsmall kiss-color-muted kiss-text-bold">TOTAL ORDERS</div>
                        <div class="kiss-size-large kiss-text-bold kiss-margin-xsmall-top">
                            {{ stats.orderCount }}
                        </div>
                    </kiss-card>
                    <kiss-card theme="bordered contrast" class="kiss-padding">
                        <div class="kiss-size-xsmall kiss-color-muted kiss-text-bold">PENDING ORDERS</div>
                        <div class="kiss-size-large kiss-text-bold kiss-margin-xsmall-top" :style="{ color: stats.pendingCount ? '#ffbe0b' : 'inherit' }">
                            {{ stats.pendingCount }}
                        </div>
                    </kiss-card>
                    <kiss-card theme="bordered contrast" class="kiss-padding">
                        <div class="kiss-size-xsmall kiss-color-muted kiss-text-bold">AVG ORDER VALUE</div>
                        <div class="kiss-size-large kiss-text-bold kiss-margin-xsmall-top">
                            {{ formatCurrency(stats.avgOrderValue) }}
                        </div>
                    </kiss-card>
                    <kiss-card theme="bordered contrast" class="kiss-padding">
                        <div class="kiss-size-xsmall kiss-color-muted kiss-text-bold">LOW STOCK ITEMS</div>
                        <div class="kiss-size-large kiss-text-bold kiss-margin-xsmall-top" :style="{ color: stats.lowStockCount ? '#ef4771' : 'inherit' }">
                            {{ stats.lowStockCount }}
                        </div>
                    </kiss-card>
                </kiss-grid>

                <!-- Recent Orders & Stock alert -->
                <kiss-grid cols="1 3@m" gap="large">
                    <div span="2@m">
                        <kiss-card class="kiss-padding-large" theme="bordered contrast">
                            <div class="kiss-text-bold kiss-size-medium kiss-margin-bottom">Recent Orders</div>
                            <div class="kiss-overflow-auto" v-if="orders.length">
                                <table class="kiss-table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Fulfillment</th>
                                            <th>Payment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="order in orders.slice(0, 5)" :key="order._id">
                                            <td><code class="kiss-text-bold">{{ order.order_id }}</code></td>
                                            <td>
                                                <div>{{ order.customer_name }}</div>
                                                <small class="kiss-color-muted">{{ order.customer_email }}</small>
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
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="kiss-align-center kiss-padding-large kiss-color-muted">
                                No orders created yet. Go to Orders page to simulate a purchase.
                            </div>
                        </kiss-card>
                    </div>

                    <div>
                        <kiss-card class="kiss-padding-large" theme="bordered contrast">
                            <div class="kiss-text-bold kiss-size-medium kiss-margin-bottom">Stock Highlights</div>
                            <ul class="kiss-list" v-if="products.length">
                                <li v-for="prod in products" :key="prod._id" class="kiss-flex kiss-flex-middle">
                                    <div class="kiss-flex-1">
                                        <div class="kiss-text-bold kiss-size-small">{{ prod.name }}</div>
                                        <small class="kiss-color-muted">SKU: {{ prod.sku }}</small>
                                    </div>
                                    <div>
                                        <span v-if="prod.stock === 0" class="stock-badge stock-empty">Out of Stock</span>
                                        <span v-else-if="prod.stock < 5" class="stock-badge stock-low">Low: {{ prod.stock }} left</span>
                                        <span v-else class="stock-badge stock-ok">{{ prod.stock }} units</span>
                                    </div>
                                </li>
                            </ul>
                        </kiss-card>
                    </div>
                </kiss-grid>
            </div>
        </div>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    products: [],
                    orders: [],
                    stats: { totalSales: 0, orderCount: 0, avgOrderValue: 0, lowStockCount: 0, pendingCount: 0 },
                    loading: true
                }
            },

            mounted() {
                this.load();
            },

            methods: {
                load() {
                    this.loading = true;
                    Promise.all([
                        this.$request('/store/getProducts'),
                        this.$request('/store/getOrders'),
                        this.$request('/store/getDashboardStats')
                    ]).then(([products, orders, stats]) => {
                        this.products = products.products || [];
                        this.orders = orders.orders || [];
                        this.stats = stats || { totalSales: 0, orderCount: 0, avgOrderValue: 0, lowStockCount: 0, pendingCount: 0 };
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error fetching Store / Toko data', 'danger');
                    });
                },
                formatCurrency(value) {
                    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
                }
            }
        }
    </script>
</vue-view>

<?=$this->render('store:views/partials/sidebar.php')?>
