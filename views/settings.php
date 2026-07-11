<?php
// Renders the Store/Toko Settings & Invoice UI
?>

<style>
    .invoice-preview {
        background: #ffffff;
        color: #333333;
        font-family: 'Courier New', Courier, monospace;
        border: 1px dashed #cccccc;
        padding: 30px;
        max-width: 600px;
        margin: 0 auto;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .invoice-preview th {
        color: #fff !important;
    }
    .invoice-header {
        border-bottom: 2px solid #333333;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
</style>

<vue-view>
    <template>
        <div class="kiss-margin-large-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?=$this->route('/store')?>"><?=t('Store')?></a></li>
                        <li><span><?=t('Settings')?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Shop Configuration & Invoice template</h3>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                <kiss-grid cols="1 2@m" gap="medium">
                    <!-- Shop Settings Form -->
                    <kiss-card theme="bordered contrast" class="kiss-padding-large">
                        <div class="kiss-size-4 kiss-text-bold kiss-margin-large-bottom">Shop Credentials & Defaults</div>
                        
                        <form @submit.prevent="saveSettings">
                            <div class="kiss-margin">
                                <label class="kiss-text-bold kiss-size-small">Shop Name</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="settings.shop_name" required>
                            </div>

                            <div class="kiss-margin">
                                <label class="kiss-text-bold kiss-size-small">Website Favicon (Tab Icon)</label>
                                <div class="kiss-margin-small-top" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                    <!-- Preview -->
                                    <div style="width: 48px; height: 48px; border: 2px dashed rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.03); overflow: hidden; flex-shrink: 0;">
                                        <img v-if="settings.favicon" :src="getFaviconPreviewUrl" style="width: 100%; height: 100%; object-fit: contain;" alt="Favicon Preview">
                                        <span v-else style="font-size: 20px;">🌐</span>
                                    </div>
                                    <!-- Actions -->
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button type="button" class="kiss-button kiss-button-small" @click="pickFavicon">
                                            <span>📁 Pick from Assets</span>
                                        </button>
                                        <button type="button" class="kiss-button kiss-button-small" @click="settings.favicon = ''" v-if="settings.favicon">
                                            ✕ Clear
                                        </button>
                                    </div>
                                </div>
                                <div class="kiss-margin-small-top" style="font-size: 0.75rem; color: var(--kiss-color-muted);">Upload a square image (PNG, SVG, ICO) — recommended 32×32 or 64×64 pixels</div>
                                <!-- Direct URL fallback -->
                                <div class="kiss-margin-small-top">
                                    <input type="text" class="kiss-input" v-model="settings.favicon" placeholder="Or paste image URL directly (https://...)" style="font-size: 0.8rem;">
                                </div>
                            </div>
                            <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin">
                                <div>
                                    <label class="kiss-text-bold kiss-size-small">Contact Email</label>
                                    <input type="email" class="kiss-input kiss-margin-small-top" v-model="settings.shop_email" required>
                                </div>
                                <div>
                                    <label class="kiss-text-bold kiss-size-small">Phone Number</label>
                                    <input type="text" class="kiss-input kiss-margin-small-top" v-model="settings.shop_phone" required>
                                </div>
                            </kiss-grid>
                            <div class="kiss-margin">
                                <label class="kiss-text-bold kiss-size-small">Shop Address</label>
                                <textarea class="kiss-input kiss-margin-small-top" rows="2" v-model="settings.shop_address" required></textarea>
                            </div>

                            <hr class="kiss-margin-large">

                            <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin">
                                <div>
                                    <label class="kiss-text-bold kiss-size-small">Currency Code</label>
                                    <input type="text" class="kiss-input kiss-margin-small-top" v-model="settings.currency" required>
                                </div>
                                <div>
                                    <label class="kiss-text-bold kiss-size-small">Tax Percentage (PPN %)</label>
                                    <input type="number" class="kiss-input kiss-margin-small-top" v-model.number="settings.tax_percent" required min="0">
                                </div>
                            </kiss-grid>

                            <div class="kiss-margin-large-top">
                                <button type="submit" class="kiss-button kiss-button-primary" :disabled="saving">
                                    <span v-if="saving">Saving...</span>
                                    <span v-else>Save Settings</span>
                                </button>
                            </div>
                        </form>
                    </kiss-card>

                    <!-- Invoice Live Preview -->
                    <div>
                        <div class="kiss-text-bold kiss-size-medium kiss-margin-small-bottom">Invoice PDF/HTML Live Preview</div>
                        
                        <div class="invoice-preview">
                            <div class="invoice-header kiss-flex kiss-flex-between kiss-flex-bottom">
                                <div>
                                    <div class="kiss-text-bold kiss-size-large">{{ settings.shop_name }}</div>
                                    <div class="kiss-size-xsmall kiss-color-muted" style="margin-top: 4px;">{{ settings.shop_address }}</div>
                                </div>
                                <div class="kiss-align-right">
                                    <div class="kiss-text-bold">INVOICE</div>
                                    <small class="kiss-color-muted">#INV-987654</small>
                                </div>
                            </div>

                            <kiss-grid cols="2" class="kiss-margin-small-bottom" style="font-size: 11px;">
                                <div>
                                    <b>Billed To:</b><br>
                                    Bima Mahendra<br>
                                    bima@example.com
                                </div>
                                <div class="kiss-align-right">
                                    <b>Date:</b> July 06, 2026<br>
                                    <b>Status:</b> PAID
                                </div>
                            </kiss-grid>

                            <table style="width: 100%; font-size: 11px; margin-top: 15px; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid #333; font-weight: bold;">
                                        <th align="left" style="padding-bottom: 5px;">Item</th>
                                        <th align="right" style="padding-bottom: 5px;">Price</th>
                                        <th align="center" style="padding-bottom: 5px;">Qty</th>
                                        <th align="right" style="padding-bottom: 5px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 6px 0;">Premium Arabica Coffee</td>
                                        <td align="right">Rp 150.000</td>
                                        <td align="center">2</td>
                                        <td align="right">Rp 300.000</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" align="right" style="padding-top: 10px;">Subtotal Amount:</td>
                                        <td align="right" style="padding-top: 10px;">Rp 300.000</td>
                                    </tr>
                                    <tr v-if="settings.tax_percent > 0">
                                        <td colspan="3" align="right">PPN Tax ({{ settings.tax_percent }}%):</td>
                                        <td align="right">Rp {{ (300000 * (settings.tax_percent / 100)).toLocaleString('id-ID') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" align="right">Shipping (JNE Regular):</td>
                                        <td align="right">Rp 15.000</td>
                                    </tr>
                                    <tr style="font-weight: bold; font-size: 13px;">
                                        <td colspan="3" align="right" style="padding-top: 10px; border-top: 1px double #333;">Grand Total:</td>
                                        <td align="right" style="padding-top: 10px; border-top: 1px double #333;">Rp {{ (315000 + (300000 * (settings.tax_percent / 100))).toLocaleString('id-ID') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </kiss-grid>
            </div>
        </div>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    settings: {
                        shop_name: '',
                        shop_email: '',
                        shop_phone: '',
                        shop_address: '',
                        tax_percent: 0,
                        currency: 'IDR',
                        favicon: ''
                    },
                    loading: true,
                    saving: false
                }
            },

            computed: {
                getFaviconPreviewUrl() {
                    if (!this.settings.favicon) return '';
                    if (this.settings.favicon.startsWith('assets://')) {
                        const id = this.settings.favicon.replace('assets://', '');
                        return App.route('/assets/link/' + id);
                    }
                    return this.settings.favicon;
                }
            },

            mounted() {
                this.loadSettings();
            },

            methods: {
                loadSettings() {
                    this.loading = true;
                    this.$request('/store/getSettings').then(res => {
                        this.settings = Object.assign({
                            shop_name: 'Online Store',
                            shop_email: 'store@example.com',
                            shop_phone: '+62812345678',
                            shop_address: 'Jakarta, Indonesia',
                            tax_percent: 11,
                            currency: 'IDR',
                            favicon: ''
                        }, res || {});
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error loading settings', 'danger');
                    });
                },
                saveSettings() {
                    this.saving = true;
                    this.$request('/store/saveSettings', { settings: this.settings }).then(res => {
                        this.saving = false;
                        App.ui.notify('Settings saved successfully!', 'success');
                    }).catch(err => {
                        this.saving = false;
                        App.ui.notify('Error saving settings', 'danger');
                    });
                },
                pickFavicon() {
                    App.utils.selectAsset(asset => {
                        this.settings.favicon = 'assets://' + asset._id;
                    }, { type: 'image' });
                }
            }
        }
    </script>
</vue-view>

<?=$this->render('store:views/partials/sidebar.php')?>
