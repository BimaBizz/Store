<?php
// Renders the Store/Toko Customers List UI
?>

<style>
    .clickable-row:hover {
        background: rgba(128, 128, 128, 0.08) !important;
    }
    .badge-inactive {
        background-color: rgba(239, 71, 111, 0.25) !important;
        color: #ef4771 !important;
    }
    .badge-active {
        background-color: rgba(6, 214, 160, 0.25) !important;
        color: #06d6a0 !important;
    }
    .customer-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #8338ec;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
    }
</style>

<vue-view>
    <template>
        <div class="kiss-margin-large-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?=$this->route('/store')?>"><?=t('Store')?></a></li>
                        <li><span><?=t('Customers')?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Customer Database</h3>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                <kiss-card theme="bordered contrast" class="kiss-padding-large">
                    <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                        <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Customer Profiles</div>
                        <div class="kiss-width-1-4@m">
                            <input type="text" class="kiss-input" placeholder="Search customers..." v-model="searchQuery">
                        </div>
                    </div>

                    <div v-if="customers.length">
                        <!-- TABLE VIEW -->
                        <div class="kiss-overflow-auto" v-if="view === 'table'">
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Email Address</th>
                                        <th>Total Purchases</th>
                                        <th>Total Sales Contribution</th>
                                        <th>Registered Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cust in customers" :key="cust.email" @click="showCustomerDetails(cust)" style="cursor: pointer;" class="clickable-row">
                                        <td class="kiss-text-bold">
                                            <div class="kiss-flex kiss-flex-middle" gap="small" style="gap: 10px;">
                                                <div class="customer-avatar">{{ getInitials(cust.name) }}</div>
                                                <span>{{ cust.name }}</span>
                                            </div>
                                        </td>
                                        <td><code>{{ cust.email }}</code></td>
                                        <td>{{ cust.orders_count }} orders</td>
                                        <td class="kiss-text-bold" style="color: #06d6a0;">{{ formatCurrency(cust.total_spend) }}</td>
                                        <td class="kiss-color-muted">{{ formatDate(cust.created) }}</td>
                                        <td>
                                            <span class="kiss-badge badge-active" v-if="cust.active !== false">Active</span>
                                            <span class="kiss-badge badge-inactive" v-else>Inactive</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- CARDS/GRID VIEW -->
                        <kiss-grid cols="1 3@m" gap="medium" class="kiss-margin-bottom" v-else>
                            <div v-for="cust in customers" :key="cust._id" @click="showCustomerDetails(cust)" style="cursor: pointer;">
                                <kiss-card theme="bordered contrast" class="kiss-padding clickable-row">
                                    <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="small" style="gap: 12px;">
                                        <div class="customer-avatar">{{ getInitials(cust.name) }}</div>
                                        <div class="kiss-flex-1">
                                            <div class="kiss-text-bold kiss-size-medium">{{ cust.name }}</div>
                                            <small class="kiss-color-muted">{{ cust.email }}</small>
                                        </div>
                                    </div>
                                    <kiss-grid cols="2" gap="small" class="kiss-margin-top">
                                        <div>
                                            <span class="kiss-color-muted kiss-size-xsmall">PURCHASES</span>
                                            <div class="kiss-text-bold">{{ cust.orders_count }} orders</div>
                                        </div>
                                        <div>
                                            <span class="kiss-color-muted kiss-size-xsmall">TOTAL SPENT</span>
                                            <div class="kiss-text-bold" style="color: #06d6a0;">{{ formatCurrency(cust.total_spend) }}</div>
                                        </div>
                                    </kiss-grid>
                                    <div class="kiss-margin-large-top kiss-flex kiss-flex-middle kiss-flex-between">
                                        <small class="kiss-color-muted">Since: {{ formatDate(cust.created) }}</small>
                                        <span class="kiss-badge badge-active" v-if="cust.active !== false">Active</span>
                                        <span class="kiss-badge badge-inactive" v-else>Inactive</span>
                                    </div>
                                </kiss-card>
                            </div>
                        </kiss-grid>

                        <!-- PAGINATION FOOTER -->
                        <div class="kiss-flex kiss-flex-middle kiss-margin-large-top">
                            <div class="kiss-flex-1">
                                <app-pagination v-if="count">
                                    <div class="kiss-color-muted">{{ count }} customers</div>
                                    <a class="kiss-margin-small-start" v-if="(page - 1) >= 1" @click="page--; loadCustomers()">Previous</a>
                                    <div class="kiss-margin-small-start kiss-overlay-input" v-if="count > limit">
                                        <strong>{{ page }} &mdash; {{pages}}</strong>
                                        <select v-model="page" @change="loadCustomers()" v-if="pages > 1">
                                            <option v-for="p in pages" :value="p">{{ p }}</option>
                                        </select>
                                    </div>
                                    <a class="kiss-margin-small-start" v-if="(page + 1) <= pages" @click="page++; loadCustomers()">Next</a>
                                    
                                    <!-- Show Limit Selector -->
                                    <div class="kiss-margin-start kiss-overlay-input">
                                        <span class="kiss-color-muted">Show:</span> {{ limit }}
                                        <select v-model="limit" @change="page = 1; loadCustomers()">
                                            <option v-for="l in [5, 10, 15, 20, 25]" :value="l">{{ l }}</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Sort Option Selector -->
                                    <div class="kiss-margin-start">
                                        <a @click="sortDir = sortDir == -1 ? 1 : -1; loadCustomers()"><icon>{{ sortDir == 1 ? 'arrow_downward':'arrow_upward' }}</icon></a>
                                        <div class="kiss-margin-xsmall-start kiss-overlay-input">
                                            <span class="kiss-color-muted">{{ sortOptions[sortKey] }}</span>
                                            <select v-model="sortKey" @change="loadCustomers()">
                                                <option v-for="(lbl, key) in sortOptions" :value="key">{{ lbl }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </app-pagination>
                            </div>
                            
                            <!-- Layout View Toggle -->
                            <div class="kiss-flex kiss-flex-middle" gap="small" style="gap: 12px;">
                                <a class="kiss-link-muted" :class="view=='cards' ? 'kiss-color-primary' : 'kiss-color-muted'" @click="view='cards'"><icon size="large">grid_view</icon></a>
                                <a class="kiss-link-muted" :class="view=='table' ? 'kiss-color-primary' : 'kiss-color-muted'" @click="view='table'"><icon size="large">dns</icon></a>
                            </div>
                        </div>
                    </div>
                    <div v-else class="kiss-align-center kiss-padding-large kiss-color-muted">
                        <icon size="large">people</icon>
                        <p class="kiss-margin-small-top">No customers found.</p>
                    </div>
                </kiss-card>
            </div>
        </div>

        <!-- CUSTOMER DETAILS DIALOG -->
        <kiss-dialog ref="customerDetailsModal">
            <kiss-content class="kiss-padding-large" v-if="activeCustomer" style="width: 800px; max-width: 100%;">
                <div class="kiss-flex kiss-flex-middle kiss-margin-bottom">
                    <div class="kiss-flex-1">
                        <h3 class="kiss-margin-none">Customer Profile Details</h3>
                    </div>
                    <div class="kiss-flex kiss-flex-middle" gap="small" style="gap: 12px;">
                        <span class="kiss-size-small kiss-color-muted">Status:</span>
                        <button 
                            :class="['kiss-button kiss-button-small', activeCustomer.active !== false ? 'kiss-button-success' : 'kiss-button-danger']"
                            @click="toggleCustomerStatus(activeCustomer)">
                            {{ activeCustomer.active !== false ? 'Active' : 'Inactive' }}
                        </button>
                    </div>
                </div>

                <kiss-card theme="bordered" class="kiss-padding kiss-margin-bottom" style="background: rgba(0,0,0,0.02);">
                    <kiss-grid cols="1 2@m" gap="medium">
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">NAME</span>
                            <div class="kiss-text-bold kiss-size-large">{{ activeCustomer.name }}</div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">EMAIL ADDRESS</span>
                            <div class="kiss-text-bold"><code>{{ activeCustomer.email }}</code></div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">PHONE NUMBER</span>
                            <div>{{ activeCustomer.phone || '—' }}</div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">TOTAL SPENT (SETTLED)</span>
                            <div class="kiss-text-bold" style="color: #06d6a0;">{{ formatCurrency(activeCustomer.total_spend || 0) }}</div>
                        </div>
                        <div class="kiss-column-span-2@m">
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">SHIPPING ADDRESS</span>
                            <div>{{ activeCustomer.address || '—' }}</div>
                            <div class="kiss-size-xsmall kiss-color-muted" v-if="activeCustomer.city || activeCustomer.zip">
                                {{ activeCustomer.city }} {{ activeCustomer.zip }}
                            </div>
                        </div>
                        <div>
                            <span class="kiss-color-muted kiss-size-xsmall kiss-text-bold">REGISTERED DATE</span>
                            <div>{{ formatDate(activeCustomer.created) }}</div>
                        </div>
                    </kiss-grid>
                </kiss-card>

                <div class="kiss-margin-large-top">
                    <div class="kiss-text-bold kiss-size-medium kiss-margin-small-bottom">Purchase History</div>
                    
                    <app-loader class="kiss-margin-large" v-if="loadingOrders"></app-loader>
                    
                    <div v-else>
                        <div class="kiss-overflow-auto" v-if="activeCustomerOrders.length">
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Grand Total</th>
                                        <th>Fulfillment</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in paginatedCustomerOrders" :key="order._id">
                                        <td><code>{{ order.order_id }}</code></td>
                                        <td>{{ formatDate(order.created) }}</td>
                                        <td>
                                            <div v-for="item in order.items" class="kiss-size-xsmall">
                                                - {{ item.name }} (x{{ item.quantity }})
                                            </div>
                                        </td>
                                        <td class="kiss-text-bold">{{ formatCurrency(order.total_amount) }}</td>
                                        <td>
                                            <span class="kiss-badge kiss-badge-outline kiss-size-xsmall">{{ order.status }}</span>
                                        </td>
                                        <td>
                                            <span v-if="order.payment_status === 'settled'" class="kiss-badge kiss-badge-success kiss-size-xsmall">Paid</span>
                                            <span v-else class="kiss-badge kiss-badge-warning kiss-size-xsmall">{{ order.payment_status }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Purchase History Pagination -->
                            <div class="kiss-margin-small-top kiss-flex kiss-flex-middle kiss-flex-between" v-if="orderPages > 1" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                                <div class="kiss-size-xsmall kiss-color-muted">
                                    Showing {{ (orderPage - 1) * orderLimit + 1 }}-{{ Math.min(orderPage * orderLimit, activeCustomerOrders.length) }} of {{ activeCustomerOrders.length }} orders
                                </div>
                                <div class="kiss-flex kiss-flex-middle kiss-gap-small">
                                    <button class="kiss-button kiss-button-small" :disabled="orderPage === 1" @click="orderPage--">Prev</button>
                                    <span class="kiss-size-small kiss-text-bold" style="margin: 0 0.5rem;">Page {{ orderPage }} of {{ orderPages }}</span>
                                    <button class="kiss-button kiss-button-small" :disabled="orderPage === orderPages" @click="orderPage++">Next</button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="kiss-align-center kiss-padding kiss-color-muted" style="border: 1px dashed rgba(255,255,255,0.1); border-radius: 6px;">
                            <p class="kiss-margin-none">No purchase records found for this customer.</p>
                        </div>
                    </div>
                </div>

                <div class="kiss-margin-large-top kiss-flex kiss-flex-right">
                    <button class="kiss-button" @click="$refs.customerDetailsModal.close()">Close</button>
                </div>
            </kiss-content>
        </kiss-dialog>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    customers: [],
                    activeCustomerOrders: [],
                    orderPage: 1,
                    orderLimit: 3,
                    loading: true,
                    loadingOrders: false,
                    searchQuery: '',
                    activeCustomer: null,
                    page: 1,
                    pages: 1,
                    limit: 5,
                    count: 0,
                    searchTimer: null,
                    view: 'table',
                    sortKey: 'created',
                    sortDir: -1,
                    sortOptions: {
                        created: 'Registered Date',
                        name: 'Name',
                        email: 'Email',
                        total_spend: 'Total Spend'
                    }
                }
            },

            computed: {
                paginatedCustomerOrders() {
                    const start = (this.orderPage - 1) * this.orderLimit;
                    const end = start + this.orderLimit;
                    return this.activeCustomerOrders.slice(start, end);
                },
                orderPages() {
                    return Math.ceil(this.activeCustomerOrders.length / this.orderLimit) || 1;
                }
            },

            watch: {
                searchQuery() {
                    this.page = 1;
                    if (this.searchTimer) clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        this.loadCustomers();
                    }, 300);
                }
            },

            mounted() {
                this.loadCustomers();
            },

            methods: {
                loadCustomers() {
                    this.loading = true;
                    this.$request('/store/getCustomers', {
                        page: this.page,
                        limit: this.limit,
                        search: this.searchQuery,
                        sort: this.sortKey,
                        sort_dir: this.sortDir
                    }).then(res => {
                        if (Array.isArray(res)) {
                            this.customers = res;
                            this.count = res.length;
                            this.pages = 1;
                            this.page = 1;
                        } else {
                            this.customers = res.customers || [];
                            this.count = res.count || 0;
                            this.pages = res.pages || 1;
                            this.page = res.page || 1;
                        }
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error loading customers database', 'danger');
                    });
                },
                formatCurrency(value) {
                    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
                },
                formatDate(timestamp) {
                    if (!timestamp) return '—';
                    return new Date(timestamp * 1000).toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },
                getInitials(name) {
                    if (!name) return 'C';
                    const parts = name.trim().split(' ');
                    if (parts.length > 1) {
                        return (parts[0][0] + parts[1][0]).toUpperCase();
                    }
                    return parts[0][0].toUpperCase();
                },
                showCustomerDetails(cust) {
                    this.activeCustomer = cust;
                    this.activeCustomerOrders = [];
                    this.orderPage = 1;
                    this.loadingOrders = true;
                    this.$refs.customerDetailsModal.show();
                    
                    this.$request('/store/getCustomerOrders', { email: cust.email }).then(orders => {
                        this.activeCustomerOrders = orders || [];
                        this.loadingOrders = false;
                    }).catch(err => {
                        this.loadingOrders = false;
                        App.ui.notify('Error loading order history', 'danger');
                    });
                },
                toggleCustomerStatus(cust) {
                    this.$request('/store/toggleCustomerActive', { id: cust._id }).then(res => {
                        if (res && res.success) {
                            cust.active = res.active;
                            App.ui.notify('Customer status updated successfully!', 'success');
                        } else {
                            App.ui.notify('Failed to update status', 'danger');
                        }
                    }).catch(err => {
                        App.ui.notify('Failed to update status', 'danger');
                    });
                }
            }
        }
    </script>
</vue-view>

<?=$this->render('store:views/partials/sidebar.php')?>
