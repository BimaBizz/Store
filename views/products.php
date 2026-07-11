<?php

?>

<style>
    .product-img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 6px;
        background-color: #f1faee;
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
    kiss-dialog {
        z-index: 99999 !important;
    }
</style>

<vue-view>
    <template>
        <div class="kiss-margin-large-top kiss-margin-large-bottom animated fadeIn" style="padding-right: 1.5rem; padding-left: 1.5rem;">
            <div class="kiss-flex kiss-flex-middle kiss-margin-large-bottom">
                <div class="kiss-flex-1">
                    <ul class="kiss-breadcrumbs kiss-margin-xsmall-bottom">
                        <li><a href="<?=$this->route('/store')?>"><?=t('Store')?></a></li>
                        <li><span><?=t('Products')?></span></li>
                    </ul>
                    <h3 class="kiss-margin-none">Product Inventory</h3>
                </div>
                <div>
                    <button class="kiss-button kiss-button-primary" @click="openAddProductModal">Add Product</button>
                </div>
            </div>

            <app-loader class="kiss-margin-large" v-if="loading"></app-loader>

            <div v-else>
                <kiss-card theme="bordered contrast" class="kiss-padding-large">
                    <div class="kiss-flex kiss-flex-middle kiss-margin-bottom" gap="medium">
                        <div class="kiss-size-4 kiss-text-bold kiss-flex-1">Products</div>
                        <div class="kiss-width-1-4@m">
                            <input type="text" class="kiss-input" placeholder="Search products..." v-model="searchQuery">
                        </div>
                    </div>

                    <div v-if="products.length">
                        
                        <kiss-grid cols="1 4@m" gap="medium" class="kiss-margin-bottom" v-if="view === 'cards'">
                            <div v-for="prod in products" :key="prod._id">
                                <kiss-card theme="bordered contrast" class="kiss-padding">
                                    <img :src="getProductImageUrl(prod.image)" class="product-img kiss-margin-bottom" alt="Product Image">
                                    <div class="kiss-text-bold kiss-size-medium">{{ prod.name }}</div>
                                    <div class="kiss-flex kiss-flex-middle kiss-margin-xsmall-top">
                                        <div class="kiss-flex-1">
                                            <small class="kiss-color-muted">SKU: {{ prod.sku }}</small>
                                        </div>
                                        <div>
                                            <span v-if="prod.stock === 0" class="stock-badge stock-empty">Out of Stock</span>
                                            <span v-else-if="prod.stock < 5" class="stock-badge stock-low">Low: {{ prod.stock }}</span>
                                            <span v-else class="stock-badge stock-ok">Stock: {{ prod.stock }}</span>
                                        </div>
                                    </div>
                                    <div class="kiss-size-large kiss-text-bold kiss-margin-top" style="color: #06d6a0;">
                                        {{ formatCurrency(prod.price) }}
                                    </div>
                                    
                                    <div class="kiss-margin-top kiss-flex kiss-flex-right" gap="xsmall" style="gap: 8px;">
                                        <button class="kiss-button kiss-button-small" @click="openEditProductModal(prod)"><icon>edit</icon></button>
                                        <button class="kiss-button kiss-button-small kiss-button-danger" @click="deleteProduct(prod)"><icon>delete</icon></button>
                                    </div>
                                </kiss-card>
                            </div>
                        </kiss-grid>

                        
                        <div class="kiss-overflow-auto" v-else>
                            <table class="kiss-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="prod in products" :key="prod._id">
                                        <td><img :src="getProductImageUrl(prod.image)" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                                        <td class="kiss-text-bold">{{ prod.name }}</td>
                                        <td><code>{{ prod.sku }}</code></td>
                                        <td class="kiss-text-bold" style="color: #06d6a0;">{{ formatCurrency(prod.price) }}</td>
                                        <td>
                                            <span v-if="prod.stock === 0" class="stock-badge stock-empty">Out of Stock</span>
                                            <span v-else-if="prod.stock < 5" class="stock-badge stock-low">Low: {{ prod.stock }}</span>
                                            <span v-else class="stock-badge stock-ok">Stock: {{ prod.stock }}</span>
                                        </td>
                                        <td>{{ prod.category || '—' }}</td>
                                        <td>{{ prod.brand || '—' }}</td>
                                        <td>
                                            <div class="kiss-flex" gap="xsmall" style="gap: 8px;">
                                                <button class="kiss-button kiss-button-small" @click="openEditProductModal(prod)"><icon>edit</icon></button>
                                                <button class="kiss-button kiss-button-small kiss-button-danger" @click="deleteProduct(prod)"><icon>delete</icon></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        
                        <div class="kiss-flex kiss-flex-middle kiss-margin-large-top">
                            <div class="kiss-flex-1">
                                <app-pagination v-if="count">
                                    <div class="kiss-color-muted">{{ count }} products</div>
                                    <a class="kiss-margin-small-start" v-if="(page - 1) >= 1" @click="page--; loadProducts()">Previous</a>
                                    <div class="kiss-margin-small-start kiss-overlay-input" v-if="count > limit">
                                        <strong>{{ page }} &mdash; {{pages}}</strong>
                                        <select v-model="page" @change="loadProducts()" v-if="pages > 1">
                                            <option v-for="p in pages" :value="p">{{ p }}</option>
                                        </select>
                                    </div>
                                    <a class="kiss-margin-small-start" v-if="(page + 1) <= pages" @click="page++; loadProducts()">Next</a>
                                    
                                    
                                    <div class="kiss-margin-start kiss-overlay-input">
                                        <span class="kiss-color-muted">Show:</span> {{ limit }}
                                        <select v-model="limit" @change="page = 1; loadProducts()">
                                            <option v-for="l in [4, 8, 12, 16, 24, 48]" :value="l">{{ l }}</option>
                                        </select>
                                    </div>
                                    
                                    
                                    <div class="kiss-margin-start">
                                        <a @click="sortDir = sortDir == -1 ? 1 : -1; loadProducts()"><icon>{{ sortDir == 1 ? 'arrow_downward':'arrow_upward' }}</icon></a>
                                        <div class="kiss-margin-xsmall-start kiss-overlay-input">
                                            <span class="kiss-color-muted">{{ sortOptions[sortKey] }}</span>
                                            <select v-model="sortKey" @change="loadProducts()">
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
                        <icon size="large">inventory</icon>
                        <p class="kiss-margin-small-top">No products found.</p>
                    </div>
                </kiss-card>
            </div>
        </div>

        
        <kiss-dialog ref="productModal">
            <kiss-content class="kiss-padding-large" style="width: 850px; max-width: 100%;">
                <h3 class="kiss-margin-bottom">{{ productForm && productForm._id ? 'Edit Product' : 'Add New Product' }}</h3>
                
                <form @submit.prevent="saveProduct" v-if="productForm">
                    <kiss-grid cols="1 2@m" gap="medium">
                        <div span="2@m">
                            <label class="kiss-size-xsmall kiss-text-bold">Product Name</label>
                            <input type="text" class="kiss-input" required v-model="productForm.name">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">SKU</label>
                            <input type="text" class="kiss-input" required v-model="productForm.sku">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Price (IDR)</label>
                            <input type="number" class="kiss-input" required v-model="productForm.price">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Stock Quantity</label>
                            <input type="number" class="kiss-input" required v-model="productForm.stock">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Category</label>
                            <input type="text" class="kiss-input" placeholder="e.g. Beans, Merch" v-model="productForm.category">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Brand</label>
                            <input type="text" class="kiss-input" placeholder="e.g. Gayo, V60" v-model="productForm.brand">
                        </div>
                        <div>
                            <label class="kiss-size-xsmall kiss-text-bold">Variants (Comma separated)</label>
                            <input type="text" class="kiss-input" placeholder="e.g. Black, White" v-model="productForm.variants">
                        </div>
                        <div span="2@m">
                             <label class="kiss-size-xsmall kiss-text-bold">Product Images</label>
                             
                             <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;" v-if="productFormImages.length">
                                 <div v-for="(img, idx) in productFormImages" :key="idx" style="position: relative; width: 72px; height: 72px; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.15);">
                                     <img :src="getProductImageUrl(img)" style="width: 100%; height: 100%; object-fit: cover;">
                                     <button type="button" @click="removeProductImage(idx)" style="position: absolute; top: 2px; right: 2px; width: 18px; height: 18px; background: rgba(0,0,0,0.7); color: #fff; border: none; border-radius: 50%; font-size: 10px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
                                 </div>
                             </div>
                             <div class="kiss-flex" style="gap: 8px;">
                                 <button type="button" class="kiss-button" @click="pickProductImage">+ Add Image</button>
                                 <span class="kiss-size-xsmall" style="align-self: center; opacity: 0.6;">{{ productFormImages.length }} image(s) selected</span>
                             </div>
                         </div>
                        <div span="2@m">
                            <label class="kiss-size-xsmall kiss-text-bold">Description</label>
                            <textarea class="kiss-input kiss-textarea" rows="4" v-model="productForm.description"></textarea>
                        </div>
                    </kiss-grid>

                    <div class="kiss-margin-large-top kiss-flex kiss-flex-right" gap="small" style="gap: 8px;">
                        <button type="button" class="kiss-button" @click="$refs.productModal.close()">Cancel</button>
                        <button type="submit" class="kiss-button kiss-button-primary">Save Product</button>
                    </div>
                </form>
            </kiss-content>
        </kiss-dialog>
    </template>

    <script type="module">
        export default {
            data() {
                return {
                    products: [],
                    loading: true,
                    productForm: null,
                    page: 1,
                    pages: 1,
                    limit: 4,
                    count: 0,
                    searchQuery: '',
                    searchTimer: null,
                    view: 'cards',
                    sortKey: 'name',
                    sortDir: 1,
                    sortOptions: {
                        name: 'Name',
                        sku: 'SKU',
                        price: 'Price',
                        stock: 'Stock'
                    }
                }
            },

            computed: {
                productFormImages() {
                    if (!this.productForm || !this.productForm.image) return [];
                    return this.productForm.image.split(',').map(s => s.trim()).filter(Boolean);
                }
            },

            watch: {
                searchQuery() {
                    this.page = 1;
                    if (this.searchTimer) clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        this.loadProducts();
                    }, 300);
                }
            },

            mounted() {
                this.loadProducts();
            },

            methods: {
                loadProducts() {
                    this.loading = true;
                    this.$request('/store/getProducts', {
                        page: this.page,
                        limit: this.limit,
                        search: this.searchQuery,
                        sort: this.sortKey,
                        sort_dir: this.sortDir
                    }).then(res => {
                        if (Array.isArray(res)) {
                            this.products = res;
                            this.count = res.length;
                            this.pages = 1;
                            this.page = 1;
                        } else {
                            this.products = res.products || [];
                            this.count = res.count || 0;
                            this.pages = res.pages || 1;
                            this.page = res.page || 1;
                        }
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                        App.ui.notify('Error fetching products list', 'danger');
                    });
                },
                formatCurrency(value) {
                    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
                },
                openAddProductModal() {
                    this.productForm = {
                        name: '',
                        sku: '',
                        price: 0,
                        stock: 10,
                        image: '',
                        description: '',
                        category: '',
                        brand: '',
                        variants: ''
                    };
                    this.$refs.productModal.show();
                },
                openEditProductModal(product) {
                    this.productForm = {
                        category: '',
                        brand: '',
                        variants: '',
                        ...product
                    };
                    this.$refs.productModal.show();
                },
                saveProduct() {
                    this.$request('/store/saveProduct', { product: this.productForm }).then(res => {
                        this.$refs.productModal.close();
                        this.loadProducts();
                        App.ui.notify('Product saved successfully!', 'success');
                    }).catch(err => {
                        App.ui.notify('Error saving product', 'danger');
                    });
                },
                deleteProduct(product) {
                    if (confirm(`Are you sure you want to delete ${product.name}?`)) {
                        this.$request('/store/deleteProduct', { id: product._id }).then(res => {
                            this.loadProducts();
                            App.ui.notify('Product deleted successfully!', 'success');
                        }).catch(err => {
                            App.ui.notify('Error deleting product', 'danger');
                        });
                    }
                },
                getProductImageUrl(imagePathOrId) {
                    if (!imagePathOrId) return 'https://images.unsplash.com/photo-1545665277-5937489579f2?w=500';
                    const first = imagePathOrId.includes(',') ? imagePathOrId.split(',')[0].trim() : imagePathOrId.trim();
                    if (first.startsWith('assets://')) {
                        const id = first.replace('assets://', '');
                        return App.route('/assets/link/' + id);
                    }
                    return first;
                },
                pickProductImage() {
                    App.utils.selectAsset(asset => {
                        const newImg = 'assets://' + asset._id;
                        const imgs = (this.productForm.image || '').split(',').map(s => s.trim()).filter(Boolean);
                        if (!imgs.includes(newImg)) {
                            imgs.push(newImg);
                        }
                        this.productForm.image = imgs.join(', ');
                    }, {type: 'image'});
                },
                removeProductImage(idx) {
                    const imgs = (this.productForm.image || '').split(',').map(s => s.trim()).filter(Boolean);
                    imgs.splice(idx, 1);
                    this.productForm.image = imgs.join(', ');
                }
            }
        }
    </script>
</vue-view>

<?=$this->render('store:views/partials/sidebar.php')?>
