<?php
    $storeFront = $this->retrieve('storeFront') ?? [];
    $enableFrontend = !empty($storeFront['enableFrontend']);
    $shopUrl = $enableFrontend ? '/' : '/shop';
?>
<?=$this->render('store:views/shop/header.php')?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 3rem;">
<div v-if="selectedProduct" class="product-detail-container">
    
    <a href="<?=$shopUrl?>" class="btn btn-outline btn-ghost" style="margin-bottom: 2rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
        &larr; Back to Shop
    </a>

    <div class="product-detail-grid">
        
        <div class="product-detail-media" style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="product-img-wrapper-detail" style="border-radius: 16px; overflow: hidden; background: var(--card-bg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); aspect-ratio: 1; display: flex; align-items: center; justify-content: center;">
                <img :src="currentDetailImage || selectedProduct.image_url || 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800'" :alt="selectedProduct.name" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <div v-if="selectedProduct.image_urls && selectedProduct.image_urls.length > 1" style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem;">
                <div v-for="(img, idx) in selectedProduct.image_urls" :key="idx" 
                     @click="currentDetailImage = img"
                     style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; flex-shrink: 0;"
                     :style="{ borderColor: (currentDetailImage === img || (!currentDetailImage && idx === 0)) ? 'var(--accent-site)' : 'var(--border-color)' }">
                    <img :src="img" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        </div>

        
        <div class="product-detail-info" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div>
                <span class="product-category-badge" v-if="selectedProduct.category" style="margin-bottom: 0.75rem; display: inline-block;">{{ selectedProduct.category }}</span>
                <h1 style="font-family: var(--font-title); font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-top: 0.25rem; color: var(--text-primary);">{{ selectedProduct.name }}</h1>
                <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.75rem;">
                    <span class="product-sku" style="font-size: 0.9rem; color: var(--text-muted); padding: 0.25rem">SKU: {{ selectedProduct.sku }}</span>
                    <span class="stock-indicator" :class="getStockClass(selectedProduct.stock)" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">
                        {{ selectedProduct.stock > 0 ? (selectedProduct.stock <= 5 ? 'Low Stock' : 'In Stock') : 'Out of Stock' }}
                    </span>
                </div>
            </div>

            <div style="font-size: 2rem; font-weight: 700; color: var(--accent-site);">
                {{ formatIDR(selectedProduct.price) }}
            </div>

            <div style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 1.5rem 0;">
                <h4 style="margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Description</h4>
                <p style="color: var(--text-secondary); line-height: 1.7; font-size: 1rem; white-space: pre-line;">{{ selectedProduct.description }}</p>
            </div>

            
            <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                <div v-if="selectedProduct.stock > 0" style="display: flex; align-items: center; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: var(--bg-surface);">
                    <button class="qty-btn" @click="detailQty = Math.max(1, detailQty - 1)" style="padding: 0.75rem 1.25rem; background: transparent; border: none; color: var(--text-primary); cursor: pointer; transition: background 0.2s;">-</button>
                    <span style="padding: 0 1rem; font-weight: 600; min-width: 2rem; text-align: center; color: var(--text-primary);">{{ detailQty }}</span>
                    <button class="qty-btn" @click="detailQty = Math.min(selectedProduct.stock, detailQty + 1)" style="padding: 0.75rem 1.25rem; background: transparent; border: none; color: var(--text-primary); cursor: pointer; transition: background 0.2s;">+</button>
                </div>
                
                <div style="flex: 1; min-width: 200px; display: flex; gap: 1rem;">
                    <button class="btn btn-primary" @click="addToCart(selectedProduct, detailQty)" :disabled="selectedProduct.stock <= 0" style="flex: 1; padding: 1rem;">
                        {{ selectedProduct.stock > 0 ? 'Add to Cart' : 'Out of Stock' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div v-else style="text-align: center; padding: 4rem 0;">
    <h2>Product details loading...</h2>
</div>
</div>

<script>
    window.INITIAL_PRODUCT = <?= \json_encode($product) ?>;
</script>

<?=$this->render('store:views/shop/footer.php')?>
