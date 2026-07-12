<vue-view>
    <template>
        <div class="kiss-margin-small-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?= $this->route('/store') ?>"><?= t('Store') ?></a></li>
                        <li><span><?= t('Homepage Content') ?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">CMS Storefront Content</h3>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else class="kiss-width-2-3@m" style="margin: 0 auto;">
                <form @submit.prevent="saveContent">
                    
                    <kiss-card theme="bordered contrast" class="kiss-padding-large kiss-margin-bottom">
                        <div class="kiss-size-4 kiss-text-bold kiss-margin-large-bottom">Homepage Hero Banner settings</div>
                        
                        <div class="kiss-margin">
                            <label class="kiss-text-bold kiss-size-small">Hero Badge / Tag</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_badge" placeholder="e.g. FLASH PROMO">
                        </div>

                        <div class="kiss-margin-small-top">
                            <label class="kiss-text-bold kiss-size-small">Hero Title</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_title" placeholder="e.g. Up to 10% off with Voucher" required>
                        </div>

                        <div class="kiss-margin-small-top">
                            <label class="kiss-text-bold kiss-size-small">Hero Description</label>
                            <textarea class="kiss-input kiss-margin-small-top" rows="3" v-model="content.hero_desc" placeholder="Enter sub-headline description text..." required></textarea>
                        </div>

                        <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin-small-top">
                            <div>
                                <label class="kiss-text-bold kiss-size-small">Primary CTA Button Text</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_btn_primary" placeholder="e.g. Shop Now">
                            </div>
                            <div>
                                <label class="kiss-text-bold kiss-size-small">Secondary CTA Button Text</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_btn_secondary" placeholder="e.g. View Cart">
                            </div>
                        </kiss-grid>

                        <div class="kiss-margin-small-top">
                            <label class="kiss-text-bold kiss-size-small">Promo Code Hint Box</label>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_code_hint" placeholder="e.g. Use code PROMO10 at checkout to save!">
                        </div>

                        <div class="kiss-margin-small-top">
                            <label class="kiss-text-bold kiss-size-small">Hero Featured Image</label>
                            <div class="kiss-margin-small-top" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                <div style="width: 100px; height: 100px; border: 2px dashed rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.03); overflow: hidden; flex-shrink: 0;">
                                    <img v-if="content.hero_image" :src="getHeroPreviewUrl" style="width: 100%; height: 100%; object-fit: cover;" alt="Hero Preview">
                                    <span v-else style="font-size: 24px;">🖼️</span>
                                </div>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="kiss-button" @click="pickHeroImage">📁 Select Image</button>
                                    <button type="button" class="kiss-button kiss-button-danger" @click="content.hero_image = ''" v-if="content.hero_image">✕ Clear</button>
                                </div>
                            </div>
                            <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_image" placeholder="Or paste public image URL directly">
                        </div>

                        <hr class="kiss-margin-large">
                        <div class="kiss-size-medium kiss-text-bold kiss-margin-bottom">Status Float Cards (On top of Image)</div>

                        <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin">
                            <div>
                                <label class="kiss-text-bold kiss-size-small">Card 1: Label</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_card1_label" placeholder="e.g. Sales Today">
                            </div>
                            <div>
                                <label class="kiss-text-bold kiss-size-small">Card 1: Value</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_card1_val" placeholder="e.g. +2,840">
                            </div>
                        </kiss-grid>

                        <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin-small-top">
                            <div>
                                <label class="kiss-text-bold kiss-size-small">Card 2: Label</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_card2_label" placeholder="e.g. Happy Buyers">
                            </div>
                            <div>
                                <label class="kiss-text-bold kiss-size-small">Card 2: Value</label>
                                <input type="text" class="kiss-input kiss-margin-small-top" v-model="content.hero_card2_val" placeholder="e.g. 18.5K">
                            </div>
                        </kiss-grid>
                    </kiss-card>

                    
                    <kiss-dialog ref="productPickerModal">
                        <kiss-content class="kiss-padding-large" style="width: 800px; max-width: 100%;">
                            <div class="kiss-flex kiss-flex-middle kiss-margin-bottom">
                                <h3 class="kiss-margin-none kiss-flex-1">Select Products for Flash Sales</h3>
                                <button type="button" class="kiss-button kiss-button-small" @click="$refs.productPickerModal.close()">✕</button>
                            </div>
                            
                            
                            <div class="kiss-margin-bottom">
                                <input type="text" class="kiss-input" placeholder="Filter by product name, SKU or category..." v-model="productSearchQuery">
                            </div>
                            
                            
                            <div style="max-height: 400px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; background: rgba(0,0,0,0.15);">
                                <div v-if="filteredAvailableProducts.length === 0" class="kiss-align-center kiss-padding kiss-color-muted">
                                    No matching products found in catalog.
                                </div>
                                <table class="kiss-table" v-else>
                                    <thead>
                                        <tr>
                                            <th width="40"></th>
                                            <th width="60">Image</th>
                                            <th>Product Name</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th class="kiss-align-right">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="prod in filteredAvailableProducts" :key="prod._id" @click="toggleProductSelection(prod._id)" style="cursor: pointer;" class="clickable-row">
                                            <td @click.stop>
                                                <input type="checkbox" :value="prod._id" v-model="tempSelectedProductIds" class="kiss-checkbox">
                                            </td>
                                            <td>
                                                <div style="width: 36px; height: 36px; border-radius: 4px; overflow: hidden; background: #333;">
                                                    <img :src="prod.image_url || 'https://images.unsplash.com/photo-1545665277-5937489579f2?w=100'" style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                            </td>
                                            <td><strong class="kiss-size-small">{{ prod.name }}</strong></td>
                                            <td><code>{{ prod.sku }}</code></td>
                                            <td><span class="kiss-badge kiss-badge-outline">{{ prod.category || 'General' }}</span></td>
                                            <td class="kiss-align-right">Rp {{ parseInt(prod.price).toLocaleString('id-ID') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="kiss-margin-small-top kiss-flex kiss-flex-right" style="gap: 8px;">
                                <button type="button" class="kiss-button" @click="$refs.productPickerModal.close()">Cancel</button>
                                <button type="button" class="kiss-button kiss-button-primary" @click="applyProductSelection">
                                    Apply Selection ({{ tempSelectedProductIds.length }} items)
                                </button>
                            </div>
                        </kiss-content>
                    </kiss-dialog>

                    
                    <kiss-card theme="bordered contrast" class="kiss-padding-large kiss-margin-bottom">
                        <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                            <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Feature Promo Banners (Sub-hero Slideshow)</div>
                            <button type="button" class="kiss-button kiss-button-primary kiss-button-small" @click="addPromoSlide">
                                + Add Promo Slide
                            </button>
                        </div>
                        <p class="kiss-size-small kiss-color-muted kiss-margin-large-bottom">Configure multiple custom promo banners that automatically slide/carousel below the product categories row.</p>
                        
                        <div v-if="!content.promo_banners || content.promo_banners.length === 0" class="kiss-align-center kiss-padding kiss-color-muted" style="border: 2px dashed rgba(255,255,255,0.08); border-radius: 8px; background: rgba(0,0,0,0.15);">
                            No promo slides added yet. Click "Add Promo Slide" to create one.
                        </div>
                        <div v-else>
                            <div v-for="(slide, idx) in content.promo_banners" :key="idx" style="border: 1px solid rgba(255,255,255,0.08); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; background: rgba(0,0,0,0.15); position: relative;">
                                <div style="position: absolute; right: 15px; top: 15px; display: flex; gap: 8px;">
                                    <button type="button" class="kiss-button kiss-button-danger kiss-button-small" style="padding: 2px 8px;" @click="content.promo_banners.splice(idx, 1)">✕ Remove</button>
                                </div>
                                <h4 class="kiss-text-bold kiss-margin-bottom" style="margin-top: 0; color: var(--kiss-color-primary);">Slide Banner #{{ idx + 1 }}</h4>
                                
                                <kiss-grid cols="1 2@m" gap="medium" class="kiss-margin">
                                    <div>
                                        <label class="kiss-text-bold kiss-size-small">Promo Badge / Tag</label>
                                        <input type="text" class="kiss-input kiss-margin-small-top" v-model="slide.badge" placeholder="e.g. MYSTORE">
                                    </div>
                                    <div>
                                        <label class="kiss-text-bold kiss-size-small">CTA Button Text</label>
                                        <input type="text" class="kiss-input kiss-margin-small-top" v-model="slide.btn_text" placeholder="e.g. Buy Now!">
                                    </div>
                                </kiss-grid>

                                <div class="kiss-margin-small-top">
                                    <label class="kiss-text-bold kiss-size-small">Promo Title</label>
                                    <input type="text" class="kiss-input kiss-margin-small-top" v-model="slide.title" placeholder="e.g. Enhance Your Shopping Experience" required>
                                </div>

                                <div class="kiss-margin-small-top">
                                    <label class="kiss-text-bold kiss-size-small">Promo Featured Image</label>
                                    <div class="kiss-margin-small-top" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                        <div style="width: 80px; height: 80px; border: 2px dashed rgba(255,255,255,0.15); border-radius: 6px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.03); overflow: hidden; flex-shrink: 0;">
                                            <img v-if="slide.image" :src="getSlidePreviewUrl(slide.image)" style="width: 100%; height: 100%; object-fit: cover;" alt="Promo Preview">
                                            <span v-else style="font-size: 20px;">🖼️</span>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button type="button" class="kiss-button kiss-button-small" @click="pickSlideImage(idx)">📁 Select Image</button>
                                            <button type="button" class="kiss-button kiss-button-small kiss-button-danger" @click="slide.image = ''" v-if="slide.image">✕ Clear</button>
                                        </div>
                                    </div>
                                    <input type="text" class="kiss-input kiss-margin-small-top" v-model="slide.image" placeholder="Or paste public image URL directly">
                                </div>

                                <div class="kiss-margin-small-top">
                                    <label class="kiss-text-bold kiss-size-small">Linked Product (Link to Detail Page)</label>
                                    <select class="kiss-input kiss-select kiss-margin-small-top" v-model="slide.product_id">
                                        <option value="">-- No Product Link (Scroll to Catalog) --</option>
                                        <option v-for="prod in availableProducts" :key="prod._id" :value="prod._id">
                                            {{ prod.name }} ({{ prod.sku }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </kiss-card>

                    
                    <kiss-card theme="bordered contrast" class="kiss-padding-large kiss-margin-bottom">
                        <div class="kiss-size-4 kiss-text-bold kiss-margin-large-bottom">Static Pages (HTML/Markdown)</div>
                        
                        <div class="kiss-margin">
                            <label class="kiss-text-bold kiss-size-small">About Our Shop</label>
                            <field-wysiwyg v-model="content.about_us" class="kiss-margin-small-top" height="300px"></field-wysiwyg>
                        </div>

                        <div class="kiss-margin-small-top">
                            <label class="kiss-text-bold kiss-size-small">FAQ (Frequently Asked Questions)</label>
                            <field-wysiwyg v-model="content.faq" class="kiss-margin-small-top" height="300px"></field-wysiwyg>
                        </div>

                        <div class="kiss-margin-small-top">
                            <label class="kiss-text-bold kiss-size-small">Security and Policies</label>
                            <field-wysiwyg v-model="content.shipping_policy" class="kiss-margin-small-top" height="300px"></field-wysiwyg>
                        </div>
                    </kiss-card>

                    
                    <kiss-card theme="bordered contrast" class="kiss-padding-large kiss-margin-bottom">
                        <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                            <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Flash Sales / Today's Deals</div>
                            <button type="button" class="kiss-button kiss-button-primary kiss-button-small" @click="openProductPicker">
                                🔗 Link Products
                            </button>
                        </div>
                        <p class="kiss-size-small kiss-color-muted kiss-margin-large-bottom">Select products from your catalog to feature in the Flash Sales horizontal scroll row on the homepage.</p>
                        
                        <div class="kiss-margin-bottom" style="margin-bottom: 1.5rem;">
                            <label class="kiss-text-bold kiss-size-small">Flash Sales End Time / Duration</label>
                            <input type="datetime-local" class="kiss-input kiss-margin-small-top" v-model="content.flash_sale_end_time">
                            <span class="kiss-size-xsmall kiss-color-muted" style="margin-top: 4px; display: inline-block;">Leave blank to completely HIDE the Flash Sales section from the website.</span>
                        </div>
                        
                        
                        <div v-if="linkedProducts.length === 0" class="kiss-align-center kiss-padding kiss-color-muted" style="border: 2px dashed rgba(255,255,255,0.08); border-radius: 8px; background: rgba(0,0,0,0.15);">
                            No products linked to Flash Sales yet. Click "Link Products" to add.
                        </div>
                        <div v-else class="kiss-overflow-auto" style="border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; background: rgba(0,0,0,0.15); max-height: 300px; overflow-y: auto;">
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th width="60">Image</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Category</th>
                                        <th width="80" class="kiss-align-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="prod in linkedProducts" :key="prod._id">
                                        <td>
                                            <div style="width: 36px; height: 36px; border-radius: 4px; overflow: hidden; background: #333;">
                                                <img :src="prod.image_url || 'https://images.unsplash.com/photo-1545665277-5937489579f2?w=100'" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        </td>
                                        <td><strong class="kiss-size-small">{{ prod.name }}</strong></td>
                                        <td><code>{{ prod.sku }}</code></td>
                                        <td><span class="kiss-badge kiss-badge-outline">{{ prod.category || 'General' }}</span></td>
                                        <td class="kiss-align-right">
                                            <button type="button" class="kiss-button kiss-button-small kiss-button-danger" style="padding: 2px 8px;" @click="unlinkProduct(prod._id)">✕ Unlink</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </kiss-card>

                    <div class="kiss-margin-small-top kiss-flex kiss-flex-right">
                        <button type="submit" class="kiss-button kiss-button-success" :disabled="saving">
                            <span v-if="saving">Saving Content...</span>
                            <span v-else>Save CMS Content</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    content: {
                        banners: [],
                        faq: '',
                        shipping_policy: '',
                        about_us: '',
                        
                        
                        hero_badge: '',
                        hero_title: '',
                        hero_desc: '',
                        hero_btn_primary: '',
                        hero_btn_secondary: '',
                        hero_code_hint: '',
                        hero_image: '',
                        hero_card1_label: '',
                        hero_card1_val: '',
                        hero_card2_label: '',
                        hero_card2_val: '',
                        
                        
                        flash_product_ids: [],
                        flash_sale_end_time: '',
                        
                        
                        promo_badge: '',
                        promo_title: '',
                        promo_btn_text: '',
                        promo_image: '',
                        promo_banners: []
                    },
                    availableProducts: [],
                    tempSelectedProductIds: [],
                    productSearchQuery: '',
                    loading: true,
                    saving: false
                }
            },

            mounted() {
                this.loadContent();
                this.loadProducts();
            },

            methods: {
                loadContent() {
                    this.loading = true;
                    this.$request('/store/getContent').then(res => {
                        this.content = Object.assign({
                            banners: [],
                            faq: '',
                            shipping_policy: '',
                            about_us: '',
                            hero_badge: 'Flash Promo',
                            hero_title: 'Up to 10% off with Voucher',
                            hero_desc: 'Shop premium electronics, fashion, gadgets, and more — all in one place with exclusive deals.',
                            hero_btn_primary: 'Shop Now',
                            hero_btn_secondary: 'View Cart',
                            hero_code_hint: 'Use code PROMO10 at checkout to save!',
                            hero_image: '',
                            hero_card1_label: 'Sales Today',
                            hero_card1_val: '+2,840',
                            hero_card2_label: 'Happy Buyers',
                            hero_card2_val: '18.5K',
                            flash_product_ids: [],
                            flash_sale_end_time: '',
                            promo_badge: 'MYSTORE',
                            promo_title: 'Enhance Your Shopping Experience',
                            promo_btn_text: 'Buy Now!',
                            promo_image: '',
                            promo_banners: []
                        }, res || {});
                        if (!this.content.banners) this.content.banners = [];
                        if (!this.content.promo_banners) this.content.promo_banners = [];
                        if (this.content.promo_banners.length === 0) {
                            this.content.promo_banners.push({
                                badge: 'MYSTORE',
                                title: 'Enhance Your Shopping Experience',
                                btn_text: 'Buy Now!',
                                image: '',
                                product_id: ''
                            });
                        }
                        if (!this.content.flash_product_ids) this.content.flash_product_ids = [];
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error loading CMS content', 'danger');
                    });
                },
                loadProducts() {
                    this.$request('/store/getProducts', { limit: 100 }).then(res => {
                        this.availableProducts = res.products || [];
                    }).catch(err => {
                        App.ui.notify('Error loading catalog products', 'danger');
                    });
                },
                getProductImageUrl(imagePathOrId) {
                    if (!imagePathOrId) return 'https://images.unsplash.com/photo-1545665277-5937489579f2?w=500';
                    if (imagePathOrId.startsWith('assets://')) {
                        const id = imagePathOrId.replace('assets://', '');
                        return App.route('/assets/link/' + id);
                    }
                    return imagePathOrId;
                },
                addBanner() {
                    this.content.banners.push('');
                    this.pickBannerImage(this.content.banners.length - 1);
                },
                pickBannerImage(index) {
                    App.utils.selectAsset(asset => {
                        this.content.banners[index] = 'assets://' + asset._id;
                        this.$forceUpdate();
                    }, {type: 'image'});
                },
                pickHeroImage() {
                    App.utils.selectAsset(asset => {
                        this.content.hero_image = 'assets://' + asset._id;
                        this.$forceUpdate();
                    }, {type: 'image'});
                },
                pickPromoImage() {
                    App.utils.selectAsset(asset => {
                        this.content.promo_image = 'assets://' + asset._id;
                        this.$forceUpdate();
                    }, {type: 'image'});
                },
                addPromoSlide() {
                    if (!this.content.promo_banners) this.content.promo_banners = [];
                    this.content.promo_banners.push({
                        badge: 'MYSTORE',
                        title: 'Enhance Your Shopping Experience',
                        btn_text: 'Buy Now!',
                        image: '',
                        product_id: ''
                    });
                },
                pickSlideImage(index) {
                    App.utils.selectAsset(asset => {
                        this.content.promo_banners[index].image = 'assets://' + asset._id;
                        this.$forceUpdate();
                    }, {type: 'image'});
                },
                saveContent() {
                    this.saving = true;
                    this.$request('/store/saveContent', { content: this.content }).then(res => {
                        this.saving = false;
                        App.ui.notify('CMS Content saved successfully!', 'success');
                    }).catch(err => {
                        this.saving = false;
                        App.ui.notify('Error saving content', 'danger');
                    });
                },
                openProductPicker() {
                    this.tempSelectedProductIds = [...(this.content.flash_product_ids || [])];
                    this.productSearchQuery = '';
                    this.$refs.productPickerModal.show();
                },
                toggleProductSelection(id) {
                    if (this.tempSelectedProductIds.includes(id)) {
                        this.tempSelectedProductIds = this.tempSelectedProductIds.filter(x => x !== id);
                    } else {
                        this.tempSelectedProductIds.push(id);
                    }
                },
                applyProductSelection() {
                    this.content.flash_product_ids = [...this.tempSelectedProductIds];
                    this.$refs.productPickerModal.close();
                },
                unlinkProduct(id) {
                    this.content.flash_product_ids = (this.content.flash_product_ids || []).filter(x => x !== id);
                }
            },
            computed: {
                linkedProducts() {
                    return (this.content.flash_product_ids || []).map(id => this.availableProducts.find(p => p._id === id)).filter(Boolean).filter(p => p.discount_percent > 0);
                },
                filteredAvailableProducts() {
                    const q = this.productSearchQuery.trim().toLowerCase();
                    const discounted = this.availableProducts.filter(p => p.discount_percent > 0);
                    if (!q) return discounted;
                    return discounted.filter(p => {
                        return (p.name || '').toLowerCase().includes(q) ||
                               (p.sku || '').toLowerCase().includes(q) ||
                               (p.category || '').toLowerCase().includes(q);
                    });
                },
                getHeroPreviewUrl() {
                    if (!this.content.hero_image) return '';
                    if (this.content.hero_image.startsWith('assets://')) {
                        const id = this.content.hero_image.replace('assets://', '');
                        return App.route('/assets/link/' + id);
                    }
                    return this.content.hero_image;
                },
                getPromoPreviewUrl() {
                    if (!this.content.promo_image) return '';
                    if (this.content.promo_image.startsWith('assets://')) {
                        const id = this.content.promo_image.replace('assets://', '');
                        return App.route('/assets/link/' + id);
                    }
                    return this.content.promo_image;
                },
                getSlidePreviewUrl() {
                    return (image) => {
                        if (!image) return '';
                        if (image.startsWith('assets://')) {
                            const id = image.replace('assets://', '');
                            return App.route('/assets/link/' + id);
                        }
                        return image;
                    }
                }
            }
        }
    </script>
</vue-view>

<?= $this->render('store:views/partials/sidebar.php') ?>
