<style>
    .clickable-row:hover {
        background: rgba(128, 128, 128, 0.08) !important;
    }
    .supplier-initials {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #3a86ff;
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
        <div class="kiss-margin-small-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?= $this->route('/store') ?>"><?= t('Store') ?></a></li>
                        <li><span><?= t('Suppliers & Purchasing') ?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Supplier Directory & Purchasing (ERP)</h3>
                </div>
                <div class="kiss-flex" gap="small" style="gap: 8px;">
                    <button class="kiss-button kiss-button-outline" @click="activeTab = 'suppliers'">Supplier List</button>
                    <button class="kiss-button kiss-button-outline" @click="activeTab = 'purchasing'">Purchasing Log</button>
                    <button class="kiss-button kiss-button-primary" v-if="activeTab === 'suppliers'" @click="openAddSupplierModal">Add Supplier</button>
                    <button class="kiss-button kiss-button-success" v-if="activeTab === 'purchasing'" @click="openCreatePurchaseModal">Create Purchase Receipt</button>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                
                <div v-if="activeTab === 'suppliers'">
                    <kiss-card theme="bordered contrast" class="kiss-padding-large">
                        <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                            <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Supplier Profiles</div>
                            <div class="kiss-width-1-4@m">
                                <input type="text" class="kiss-input" placeholder="Search suppliers..." v-model="searchQuery">
                            </div>
                        </div>

                        <div v-if="suppliers.length">
                            
                            <div class="kiss-overflow-auto" v-if="view === 'table'">
                                <table class="kiss-table">
                                    <thead>
                                        <tr>
                                            <th>Supplier Name</th>
                                            <th>Contact Person</th>
                                            <th>Phone Number</th>
                                            <th>Address</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="sup in suppliers" :key="sup._id">
                                            <td class="kiss-text-bold">
                                                <div class="kiss-flex kiss-flex-middle" gap="small" style="gap: 10px;">
                                                    <div class="supplier-initials">{{ getInitials(sup.name) }}</div>
                                                    <span>{{ sup.name }}</span>
                                                </div>
                                            </td>
                                            <td>{{ sup.contact || '—' }}</td>
                                            <td><code>{{ sup.phone || '—' }}</code></td>
                                            <td>{{ sup.address || '—' }}</td>
                                            <td>
                                                <div class="kiss-flex" gap="xsmall" style="gap: 4px;">
                                                    <button class="kiss-button kiss-button-small" @click="openEditSupplierModal(sup)">Edit</button>
                                                    <button class="kiss-button kiss-button-small kiss-button-danger" @click="deleteSupplier(sup)">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            
                            <kiss-grid cols="1 3@m" gap="medium" class="kiss-margin-bottom" v-else>
                                <div v-for="sup in suppliers" :key="sup._id">
                                    <kiss-card theme="bordered contrast" class="kiss-padding clickable-row">
                                        <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="small" style="gap: 12px;">
                                            <div class="supplier-initials">{{ getInitials(sup.name) }}</div>
                                            <div class="kiss-flex-1">
                                                <div class="kiss-text-bold kiss-size-medium">{{ sup.name }}</div>
                                                <small class="kiss-color-muted">CP: {{ sup.contact || '—' }}</small>
                                            </div>
                                        </div>
                                        <div class="kiss-size-small kiss-margin-bottom">
                                            <div><span class="kiss-color-muted">Phone:</span> <code>{{ sup.phone || '—' }}</code></div>
                                            <div class="kiss-margin-xsmall-top"><span class="kiss-color-muted">Address:</span> {{ sup.address || '—' }}</div>
                                        </div>
                                        <div class="kiss-margin-small-top kiss-flex kiss-flex-right" gap="xsmall" style="gap: 8px;">
                                            <button class="kiss-button kiss-button-small" @click="openEditSupplierModal(sup)">Edit</button>
                                            <button class="kiss-button kiss-button-small kiss-button-danger" @click="deleteSupplier(sup)">Delete</button>
                                        </div>
                                    </kiss-card>
                                </div>
                            </kiss-grid>

                            
                            <div class="kiss-flex kiss-flex-middle kiss-margin-small-top">
                                <div class="kiss-flex-1">
                                    <app-pagination v-if="count">
                                        <div class="kiss-color-muted">{{ count }} suppliers</div>
                                        <a class="kiss-margin-small-start" v-if="(page - 1) >= 1" @click="page--; loadSuppliers()">Previous</a>
                                        <div class="kiss-margin-small-start kiss-overlay-input" v-if="count > limit">
                                            <strong>{{ page }} &mdash; {{pages}}</strong>
                                            <select v-model="page" @change="loadSuppliers()" v-if="pages > 1">
                                                <option v-for="p in pages" :value="p">{{ p }}</option>
                                            </select>
                                        </div>
                                        <a class="kiss-margin-small-start" v-if="(page + 1) <= pages" @click="page++; loadSuppliers()">Next</a>
                                        
                                        
                                        <div class="kiss-margin-start kiss-overlay-input">
                                            <span class="kiss-color-muted">Show:</span> {{ limit }}
                                            <select v-model="limit" @change="page = 1; loadSuppliers()">
                                                <option v-for="l in [5, 10, 15, 20, 25]" :value="l">{{ l }}</option>
                                            </select>
                                        </div>
                                        
                                        
                                        <div class="kiss-margin-start">
                                            <a @click="sortDir = sortDir == -1 ? 1 : -1; loadSuppliers()"><icon>{{ sortDir == 1 ? 'arrow_downward':'arrow_upward' }}</icon></a>
                                            <div class="kiss-margin-xsmall-start kiss-overlay-input">
                                                <span class="kiss-color-muted">{{ sortOptions[sortKey] }}</span>
                                                <select v-model="sortKey" @change="loadSuppliers()">
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
                            <icon size="large">local_shipping</icon>
                            <p class="kiss-margin-small-top">No suppliers found.</p>
                        </div>
                    </kiss-card>
                </div>

                
                <div v-if="activeTab === 'purchasing'">
                    <kiss-card theme="bordered contrast" class="kiss-padding-large">
                        <div class="kiss-overflow-auto" v-if="purchases.length">
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th>Purchase ID</th>
                                        <th>Supplier</th>
                                        <th>Order Date</th>
                                        <th>Items Received</th>
                                        <th>Receipt Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in purchases" :key="p._id">
                                        <td><code class="kiss-text-bold">{{ p.purchase_id }}</code></td>
                                        <td class="kiss-text-bold">{{ getSupplierName(p.supplier_id) }}</td>
                                        <td>{{ formatDate(p.created) }}</td>
                                        <td>
                                            <div v-for="item in p.items" class="kiss-size-xsmall">
                                                - {{ getProductName(item.product_id) }} (x{{ item.quantity }})
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="p.status === 'received'" class="kiss-badge kiss-badge-success">Stock Received</span>
                                            <span v-else class="kiss-badge kiss-badge-warning">Pending Delivery</span>
                                        </td>
                                        <td>
                                            <button v-if="p.status !== 'received'" class="kiss-button kiss-button-small kiss-button-success" @click="receivePurchase(p)">Confirm Arrival</button>
                                            <span v-else class="kiss-color-muted kiss-size-small">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="kiss-align-center kiss-padding-large kiss-color-muted">
                            <icon size="large">receipt</icon>
                            <p class="kiss-margin-small-top">No purchase logs verified yet.</p>
                        </div>
                    </kiss-card>
                </div>
            </div>
        </div>

        
        <kiss-dialog ref="supplierModal">
            <kiss-content class="kiss-padding-large" v-if="supplierForm" style="width: 600px; max-width: 100%;">
                <h3>{{ supplierForm._id ? 'Edit Supplier' : 'Add Supplier' }}</h3>
                <form @submit.prevent="saveSupplier">
                    <div class="kiss-margin">
                        <label class="kiss-text-bold kiss-size-small">Supplier Name</label>
                        <input type="text" class="kiss-input kiss-margin-small-top" required v-model="supplierForm.name">
                    </div>
                    <kiss-grid cols="1 2@m" gap="medium">
                        <div class="kiss-margin">
                            <label class="kiss-text-bold kiss-size-small">Contact Person</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="supplierForm.contact">
                        </div>
                        <div class="kiss-margin">
                            <label class="kiss-text-bold kiss-size-small">Phone Number</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="supplierForm.phone">
                        </div>
                    </kiss-grid>
                    <div class="kiss-margin">
                        <label class="kiss-text-bold kiss-size-small">Address</label>
                        <textarea class="kiss-input kiss-margin-small-top" rows="2" v-model="supplierForm.address"></textarea>
                    </div>
                    <div class="kiss-margin-small-top kiss-flex kiss-flex-right" gap="small" style="gap: 8px;">
                        <button type="button" class="kiss-button" @click="$refs.supplierModal.close()">Cancel</button>
                        <button type="submit" class="kiss-button kiss-button-primary">Save Supplier</button>
                    </div>
                </form>
            </kiss-content>
        </kiss-dialog>

        
        <kiss-dialog ref="purchaseModal">
            <kiss-content class="kiss-padding-large" v-if="purchaseForm" style="width: 750px; max-width: 100%;">
                <h3>Log Purchase Receipt (Stock-In ERP)</h3>
                <form @submit.prevent="savePurchasing">
                    <div class="kiss-margin">
                        <label class="kiss-text-bold kiss-size-small">Select Supplier</label>
                        <select class="kiss-input kiss-select kiss-margin-small-top" v-model="purchaseForm.supplier_id" required>
                            <option value="">-- Choose Supplier --</option>
                            <option v-for="s in suppliers" :key="s._id" :value="s._id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div class="kiss-margin-small-top">
                        <label class="kiss-text-bold kiss-size-small">Select Product & Qty to Add</label>
                        <div class="kiss-overflow-auto kiss-margin-small-top" style="max-height: 200px; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 10px;">
                            <div v-for="prod in products" :key="prod._id" class="kiss-flex kiss-flex-middle kiss-margin-small-bottom kiss-padding-small" style="background: rgba(0,0,0,0.02); border-radius: 4px; border: 1px solid rgba(255,255,255,0.03);">
                                <div class="kiss-flex-1">
                                    <div class="kiss-text-bold">{{ prod.name }}</div>
                                    <small class="kiss-color-muted">Current Stock: {{ prod.stock }}</small>
                                </div>
                                <div class="kiss-flex kiss-flex-middle" gap="xsmall">
                                    <input type="number" class="kiss-input" style="width: 70px;" min="0" placeholder="Qty" v-model.number="purchaseForm.quantities[prod._id]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kiss-margin-small-top kiss-flex kiss-flex-right" gap="small" style="gap: 8px;">
                        <button type="button" class="kiss-button" @click="$refs.purchaseModal.close()">Cancel</button>
                        <button type="submit" class="kiss-button kiss-button-success">Log Purchase Order</button>
                    </div>
                </form>
            </kiss-content>
        </kiss-dialog>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    activeTab: 'suppliers',
                    suppliers: [],
                    purchases: [],
                    products: [],
                    loading: true,
                    supplierForm: null,
                    purchaseForm: null,
                    page: 1,
                    pages: 1,
                    limit: 5,
                    count: 0,
                    searchQuery: '',
                    searchTimer: null,
                    view: 'table',
                    sortKey: 'name',
                    sortDir: 1,
                    sortOptions: {
                        name: 'Supplier Name',
                        contact: 'Contact Person'
                    }
                }
            },

            watch: {
                searchQuery() {
                    this.page = 1;
                    if (this.searchTimer) clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        this.loadSuppliers();
                    }, 300);
                },
                activeTab() {
                    this.searchQuery = '';
                    this.page = 1;
                    if (this.activeTab === 'suppliers') {
                        this.loadSuppliers();
                    }
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
                        this.$request('/store/getPurchasing')
                    ]).then(([productsRes, purchases]) => {
                        if (Array.isArray(productsRes)) {
                            this.products = productsRes;
                        } else {
                            this.products = productsRes.products || [];
                        }
                        this.purchases = purchases || [];
                        
                        this.loadSuppliers();
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error loading suppliers data', 'danger');
                    });
                },
                loadSuppliers() {
                    this.$request('/store/getSuppliers', {
                        page: this.page,
                        limit: this.limit,
                        search: this.searchQuery,
                        sort: this.sortKey,
                        sort_dir: this.sortDir
                    }).then(res => {
                        if (Array.isArray(res)) {
                            this.suppliers = res;
                            this.count = res.length;
                            this.pages = 1;
                            this.page = 1;
                        } else {
                            this.suppliers = res.suppliers || [];
                            this.count = res.count || 0;
                            this.pages = res.pages || 1;
                            this.page = res.page || 1;
                        }
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error loading suppliers list', 'danger');
                    });
                },
                getSupplierName(id) {
                    const sup = this.suppliers.find(s => s._id === id);
                    return sup ? sup.name : 'Unknown Supplier';
                },
                getProductName(id) {
                    const p = this.products.find(prod => prod._id === id);
                    return p ? p.name : 'Unknown Product';
                },
                getInitials(name) {
                    if (!name) return 'S';
                    const parts = name.trim().split(' ');
                    if (parts.length > 1) {
                        return (parts[0][0] + parts[1][0]).toUpperCase();
                    }
                    return parts[0][0].toUpperCase();
                },
                formatDate(timestamp) {
                    if (!timestamp) return '—';
                    return new Date(timestamp * 1000).toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                openAddSupplierModal() {
                    this.supplierForm = {
                        name: '',
                        contact: '',
                        phone: '',
                        address: ''
                    };
                    this.$refs.supplierModal.show();
                },
                openEditSupplierModal(sup) {
                    this.supplierForm = { ...sup };
                    this.$refs.supplierModal.show();
                },
                saveSupplier() {
                    this.$request('/store/saveSupplier', { supplier: this.supplierForm }).then(res => {
                        this.$refs.supplierModal.close();
                        this.loadSuppliers();
                        App.ui.notify('Supplier profile saved successfully!', 'success');
                    }).catch(err => {
                        App.ui.notify('Error saving supplier', 'danger');
                    });
                },
                deleteSupplier(sup) {
                    if (confirm(`Are you sure you want to delete supplier ${sup.name}?`)) {
                        this.$request('/store/deleteSupplier', { id: sup._id }).then(res => {
                            this.loadSuppliers();
                            App.ui.notify('Supplier deleted successfully!', 'success');
                        }).catch(err => {
                            App.ui.notify('Error deleting supplier', 'danger');
                        });
                    }
                },
                openCreatePurchaseModal() {
                    let quantities = {};
                    this.products.forEach(p => {
                        quantities[p._id] = 0;
                    });
                    this.purchaseForm = {
                        supplier_id: '',
                        quantities: quantities
                    };
                    this.$refs.purchaseModal.show();
                },
                savePurchasing() {
                    let items = [];
                    Object.entries(this.purchaseForm.quantities).forEach(([prodId, qty]) => {
                        if (qty > 0) {
                            items.push({
                                product_id: prodId,
                                quantity: qty
                            });
                        }
                    });

                    if (!this.purchaseForm.supplier_id || items.length === 0) {
                        App.ui.notify('Please select a supplier and at least 1 product quantity', 'warning');
                        return;
                    }

                    this.$request('/store/savePurchasing', {
                        purchase: {
                            supplier_id: this.purchaseForm.supplier_id,
                            items: items
                        }
                    }).then(res => {
                        this.$refs.purchaseModal.close();
                        this.loadData();
                        App.ui.notify('Purchase receipt logged successfully!', 'success');
                    }).catch(err => {
                        App.ui.notify('Error logging purchase order', 'danger');
                    });
                },
                receivePurchase(purchase) {
                    if (confirm('Verify that the goods have arrived? This will automatically add item quantities to product inventory stocks.')) {
                        this.$request('/store/receivePurchasing', { id: purchase._id }).then(res => {
                            this.loadData();
                            App.ui.notify('Receipt confirmed! Product stocks incremented.', 'success');
                        }).catch(err => {
                            App.ui.notify('Error receiving purchasing order', 'danger');
                        });
                    }
                }
            }
        }
    </script>
</vue-view>

<?= $this->render('store:views/partials/sidebar.php') ?>
