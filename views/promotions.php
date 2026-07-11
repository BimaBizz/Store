<?php
// Renders the Store/Toko Promotions & Vouchers UI
?>

<style>
    .clickable-row:hover {
        background: rgba(128, 128, 128, 0.08) !important;
    }
</style>

<vue-view>
    <template>
        <div class="kiss-margin-large-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?=$this->route('/store')?>"><?=t('Store')?></a></li>
                        <li><span><?=t('Promotions')?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Diskon & Promo Vouchers</h3>
                </div>
                <div>
                    <button class="kiss-button kiss-button-primary" @click="openAddVoucherModal">Create Voucher</button>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                <kiss-card theme="bordered contrast" class="kiss-padding-large">
                    <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                        <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Promo Vouchers</div>
                        <div class="kiss-width-1-4@m">
                            <input type="text" class="kiss-input" placeholder="Search vouchers..." v-model="searchQuery">
                        </div>
                    </div>

                    <div v-if="vouchers.length">
                        <!-- TABLE VIEW -->
                        <div class="kiss-overflow-auto" v-if="view === 'table'">
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th>Promo Code</th>
                                        <th>Discount Type</th>
                                        <th>Discount Value</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="v in vouchers" :key="v._id">
                                        <td><code class="kiss-text-bold" style="font-size: 1rem; color: #8338ec;">{{ v.code }}</code></td>
                                        <td>
                                            <span class="kiss-badge kiss-badge-outline">{{ v.type === 'percent' ? 'Percentage (%)' : 'Fixed Amount (IDR)' }}</span>
                                        </td>
                                        <td class="kiss-text-bold">
                                            {{ v.type === 'percent' ? v.value + '%' : formatCurrency(v.value) }}
                                        </td>
                                        <td>
                                            <span v-if="v.active === true || v.active === 'true' || v.active === 1 || v.active === '1'" class="kiss-badge kiss-badge-success" style="padding: 4px 8px;">Active</span>
                                            <span v-else class="kiss-badge kiss-badge-outline" style="padding: 4px 8px; color: #777;">Inactive</span>
                                        </td>
                                        <td>
                                            <div class="kiss-flex" gap="xsmall" style="gap: 4px;">
                                                <button class="kiss-button kiss-button-small" @click="openEditVoucherModal(v)">Edit</button>
                                                <button class="kiss-button kiss-button-small kiss-button-danger" @click="deleteVoucher(v)">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- CARDS/GRID VIEW -->
                        <kiss-grid cols="1 3@m" gap="medium" class="kiss-margin-bottom" v-else>
                            <div v-for="v in vouchers" :key="v._id">
                                <kiss-card theme="bordered contrast" class="kiss-padding clickable-row">
                                    <div class="kiss-flex kiss-flex-middle kiss-flex-between kiss-margin-bottom">
                                        <code class="kiss-text-bold" style="font-size: 1.1rem; color: #8338ec;">{{ v.code }}</code>
                                        <span v-if="v.active === true || v.active === 'true' || v.active === 1 || v.active === '1'" class="kiss-badge kiss-badge-success">Active</span>
                                        <span v-else class="kiss-badge kiss-badge-outline" style="color: #777;">Inactive</span>
                                    </div>
                                    <div class="kiss-size-large kiss-text-bold kiss-margin-top" style="color: #06d6a0;">
                                        {{ v.type === 'percent' ? v.value + '%' : formatCurrency(v.value) }} OFF
                                    </div>
                                    <div class="kiss-margin-xsmall-top">
                                        <span class="kiss-size-xsmall kiss-color-muted">{{ v.type === 'percent' ? 'Percentage discount coupon' : 'Fixed value discount coupon' }}</span>
                                    </div>
                                    <div class="kiss-margin-large-top kiss-flex kiss-flex-right" gap="xsmall" style="gap: 8px;">
                                        <button class="kiss-button kiss-button-small" @click="openEditVoucherModal(v)">Edit</button>
                                        <button class="kiss-button kiss-button-small kiss-button-danger" @click="deleteVoucher(v)">Delete</button>
                                    </div>
                                </kiss-card>
                            </div>
                        </kiss-grid>

                        <!-- PAGINATION FOOTER -->
                        <div class="kiss-flex kiss-flex-middle kiss-margin-large-top">
                            <div class="kiss-flex-1">
                                <app-pagination v-if="count">
                                    <div class="kiss-color-muted">{{ count }} vouchers</div>
                                    <a class="kiss-margin-small-start" v-if="(page - 1) >= 1" @click="page--; loadVouchers()">Previous</a>
                                    <div class="kiss-margin-small-start kiss-overlay-input" v-if="count > limit">
                                        <strong>{{ page }} &mdash; {{pages}}</strong>
                                        <select v-model="page" @change="loadVouchers()" v-if="pages > 1">
                                            <option v-for="p in pages" :value="p">{{ p }}</option>
                                        </select>
                                    </div>
                                    <a class="kiss-margin-small-start" v-if="(page + 1) <= pages" @click="page++; loadVouchers()">Next</a>
                                    
                                    <!-- Show Limit Selector -->
                                    <div class="kiss-margin-start kiss-overlay-input">
                                        <span class="kiss-color-muted">Show:</span> {{ limit }}
                                        <select v-model="limit" @change="page = 1; loadVouchers()">
                                            <option v-for="l in [5, 10, 15, 20, 25]" :value="l">{{ l }}</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Sort Option Selector -->
                                    <div class="kiss-margin-start">
                                        <a @click="sortDir = sortDir == -1 ? 1 : -1; loadVouchers()"><icon>{{ sortDir == 1 ? 'arrow_downward':'arrow_upward' }}</icon></a>
                                        <div class="kiss-margin-xsmall-start kiss-overlay-input">
                                            <span class="kiss-color-muted">{{ sortOptions[sortKey] }}</span>
                                            <select v-model="sortKey" @change="loadVouchers()">
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
                        <icon size="large">local_offer</icon>
                        <p class="kiss-margin-small-top">No vouchers found.</p>
                    </div>
                </kiss-card>
            </div>
        </div>

        <!-- VOUCHER FORM DIALOG -->
        <kiss-dialog ref="voucherModal">
            <kiss-content class="kiss-padding-large" v-if="voucherForm" style="width: 750px; max-width: 100%;">
                <h3>{{ voucherForm._id ? 'Edit Voucher' : 'Create Voucher' }}</h3>
                <form @submit.prevent="saveVoucher">
                    <div class="kiss-margin">
                        <label class="kiss-text-bold kiss-size-small">Voucher Code</label>
                        <input type="text" class="kiss-input kiss-margin-small-top" placeholder="e.g. COFFEETIME" v-model="voucherForm.code" required style="text-transform: uppercase;">
                    </div>
                    <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin">
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Discount Type</label>
                            <select class="kiss-input kiss-select kiss-margin-small-top" v-model="voucherForm.type">
                                <option value="percent">Percent Discount (%)</option>
                                <option value="fixed">Fixed Amount (Rp)</option>
                            </select>
                        </div>
                        <div>
                            <label class="kiss-text-bold kiss-size-small">Discount Value</label>
                            <input type="number" class="kiss-input kiss-margin-small-top" v-model.number="voucherForm.value" required min="1">
                        </div>
                    </kiss-grid>

                    <div class="kiss-margin">
                        <label class="kiss-flex kiss-flex-middle">
                            <input type="checkbox" class="kiss-checkbox" v-model="voucherForm.active">
                            <span class="kiss-margin-small-left">Voucher is Active</span>
                        </label>
                    </div>

                    <div class="kiss-margin">
                        <label class="kiss-flex kiss-flex-middle">
                            <input type="checkbox" class="kiss-checkbox" v-model="voucherForm.show_in_topbar">
                            <span class="kiss-margin-small-left">Show on Website Promo Topbar</span>
                        </label>
                    </div>

                    <div class="kiss-margin animate fadeIn" v-if="voucherForm.show_in_topbar">
                        <label class="kiss-text-bold kiss-size-small">Promo Topbar Description</label>
                        <input type="text" class="kiss-input kiss-margin-small-top" placeholder="e.g. 🔥 Summer Sale! Up to 10% OFF with code PROMO10" v-model="voucherForm.topbar_description" required>
                    </div>

                    <div class="kiss-margin-large-top kiss-flex kiss-flex-right" gap="small" style="gap: 8px;">
                        <button type="button" class="kiss-button" @click="$refs.voucherModal.close()">Cancel</button>
                        <button type="submit" class="kiss-button kiss-button-primary">Save Voucher</button>
                    </div>
                </form>
            </kiss-content>
        </kiss-dialog>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    vouchers: [],
                    loading: true,
                    voucherForm: null,
                    page: 1,
                    pages: 1,
                    limit: 5,
                    count: 0,
                    searchQuery: '',
                    searchTimer: null,
                    view: 'table',
                    sortKey: 'code',
                    sortDir: 1,
                    sortOptions: {
                        code: 'Voucher Code',
                        value: 'Value',
                        active: 'Status'
                    }
                }
            },

            watch: {
                searchQuery() {
                    this.page = 1;
                    if (this.searchTimer) clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        this.loadVouchers();
                    }, 300);
                }
            },

            mounted() {
                this.loadVouchers();
            },

            methods: {
                loadVouchers() {
                    this.loading = true;
                    this.$request('/store/getVouchers', {
                        page: this.page,
                        limit: this.limit,
                        search: this.searchQuery,
                        sort: this.sortKey,
                        sort_dir: this.sortDir
                    }).then(res => {
                        if (Array.isArray(res)) {
                            this.vouchers = res;
                            this.count = res.length;
                            this.pages = 1;
                            this.page = 1;
                        } else {
                            this.vouchers = res.vouchers || [];
                            this.count = res.count || 0;
                            this.pages = res.pages || 1;
                            this.page = res.page || 1;
                        }
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error loading vouchers list', 'danger');
                    });
                },
                formatCurrency(value) {
                    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
                },
                openAddVoucherModal() {
                    this.voucherForm = {
                        code: '',
                        type: 'percent',
                        value: 10,
                        active: true,
                        show_in_topbar: false,
                        topbar_description: ''
                    };
                    this.$refs.voucherModal.show();
                },
                openEditVoucherModal(v) {
                    this.voucherForm = { 
                        ...v,
                        active: v.active === true || v.active === 'true' || v.active === 1 || v.active === '1',
                        show_in_topbar: v.show_in_topbar === true || v.show_in_topbar === 'true' || v.show_in_topbar === 1 || v.show_in_topbar === '1',
                        topbar_description: v.topbar_description || ''
                    };
                    this.$refs.voucherModal.show();
                },
                saveVoucher() {
                    // Force code upper-case
                    this.voucherForm.code = this.voucherForm.code.toUpperCase();
                    this.$request('/store/saveVoucher', { voucher: this.voucherForm }).then(res => {
                        this.$refs.voucherModal.close();
                        this.loadVouchers();
                        App.ui.notify('Voucher saved successfully!', 'success');
                    }).catch(err => {
                        App.ui.notify(err.message || err.error || 'Error saving voucher', 'danger');
                    });
                },
                deleteVoucher(v) {
                    if (confirm(`Are you sure you want to delete voucher ${v.code}?`)) {
                        this.$request('/store/deleteVoucher', { id: v._id }).then(res => {
                            this.loadVouchers();
                            App.ui.notify('Voucher deleted successfully!', 'success');
                        }).catch(err => {
                            App.ui.notify('Error deleting voucher', 'danger');
                        });
                    }
                }
            }
        }
    </script>
</vue-view>

<?=$this->render('store:views/partials/sidebar.php')?>
