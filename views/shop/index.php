<?= $this->render('store:views/shop/header.php') ?>

<section class="hero-banner">
    <div class="hero-banner-bg">
        <div class="hero-noise"></div>
    </div>
    <div class="container hero-banner-inner">
        <div class="hero-banner-left">
            <div class="hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                {{ homepageContent.hero_badge || 'Flash Promo' }}
            </div>
            <h1 class="hero-title" v-html="homepageContent.hero_title || 'Up to <span class=\'hero-accent\'>10% off</span><br>with Voucher'"></h1>
            <p class="hero-desc">{{ homepageContent.hero_desc || 'Shop premium electronics, fashion, gadgets, and more — all in one place with exclusive deals.' }}</p>
            <div class="hero-actions">
                <button class="btn-hero-primary" @click="scrollToProducts">
                    {{ homepageContent.hero_btn_primary || 'Shop Now' }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
                <button class="btn-hero-outline" @click="isCartOpen = true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    {{ homepageContent.hero_btn_secondary || 'View Cart' }} <span class="cart-pill" v-if="cartCount > 0">{{ cartCount }}</span>
                </button>
            </div>
            <div class="hero-voucher-box" v-if="homepageContent.hero_code_hint">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                <span v-html="homepageContent.hero_code_hint"></span>
            </div>
        </div>
        <div class="hero-banner-right">
            <div class="hero-img-container">
                <div class="hero-img-glow"></div>
                <img :src="homepageContent.hero_image_url || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80'" alt="Premium Products" class="hero-product-img">
                <div class="hero-float-card hero-float-card-1" v-if="homepageContent.hero_card1_label">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    <div>
                        <div class="float-card-label">{{ homepageContent.hero_card1_label }}</div>
                        <div class="float-card-value">{{ homepageContent.hero_card1_val }}</div>
                    </div>
                </div>
                <div class="hero-float-card hero-float-card-2" v-if="homepageContent.hero_card2_label">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    <div>
                        <div class="float-card-label">{{ homepageContent.hero_card2_label }}</div>
                        <div class="float-card-value">{{ homepageContent.hero_card2_val }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div v-if="homepageContent.banners && homepageContent.banners.length > 1" class="hero-dots">
        <button v-for="(b, i) in homepageContent.banners" :key="i" class="hero-dot" :class="{ active: activeBannerIdx === i }" @click="activeBannerIdx = i"></button>
    </div>
</section>

<section class="section flash-sales-section" id="products-section" v-if="homepageContent.flash_sale_end_time && flashProducts.length > 0">
    <div class="container">
        <div class="section-header">
            <div class="section-header-left">
                <div class="section-badge flash-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Flash Sales
                </div>
                <h2 class="section-title">Today's <span>Deals</span></h2>
            </div>
            <div class="flash-countdown" v-if="flashCountdown">
                <div class="countdown-box">
                    <span class="countdown-num">{{ flashCountdown.d }}</span>
                    <span class="countdown-lbl">D</span>
                </div>
                <span class="countdown-sep">:</span>
                <div class="countdown-box">
                    <span class="countdown-num">{{ flashCountdown.h }}</span>
                    <span class="countdown-lbl">H</span>
                </div>
                <span class="countdown-sep">:</span>
                <div class="countdown-box">
                    <span class="countdown-num">{{ flashCountdown.m }}</span>
                    <span class="countdown-lbl">M</span>
                </div>
                <span class="countdown-sep">:</span>
                <div class="countdown-box">
                    <span class="countdown-num">{{ flashCountdown.s }}</span>
                    <span class="countdown-lbl">S</span>
                </div>
            </div>
            <button class="section-see-all" @click="selectedCategory = 'All'">See All</button>
        </div>

        <div v-if="loadingProducts" class="loader-spinner-center"><div class="loader-spinner"></div></div>
        
        <div v-else-if="flashProducts.length > 0" class="flash-scroll-wrapper">
            <div class="flash-products-row" id="flash-row">
                <div class="flash-card" v-for="prod in flashProducts" :key="'flash-'+prod._id" @click="goToProduct(prod)">
                    <div class="flash-card-img-wrap">
                        <span class="flash-badge-hot">HOT</span>
                        <span class="flash-disc-badge" v-if="prod.discount_percent">-{{ prod.discount_percent }}%</span>
                        <img :src="prod.image_url || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'" :alt="prod.name" class="flash-card-img">
                        <button class="flash-quick-add" @click.stop="addToCart(prod)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        </button>
                    </div>
                    <div class="flash-card-info">
                        <div class="flash-card-cat" v-if="prod.category">{{ prod.category }}</div>
                        <div class="flash-card-name">{{ prod.name }}</div>
                        <div class="flash-price-row">
                            <span class="flash-price">{{ formatIDR(prod.price) }}</span>
                            <span class="flash-old-price" v-if="prod.original_price && prod.original_price > prod.price">{{ formatIDR(prod.original_price) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <button class="flash-nav-btn flash-prev" @click="scrollFlash(-1)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="flash-nav-btn flash-next" @click="scrollFlash(1)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>

        <div v-else class="empty-state-inline">No products available</div>

        <div class="section-divider"></div>
    </div>
</section>

<section class="section categories-section">
    <div class="container">
        <div class="section-header">
            <div class="section-header-left">
                <div class="section-badge">Categories</div>
                <h2 class="section-title">Browse By <span>Category</span></h2>
            </div>
            <div class="cat-nav-btns">
                <button class="cat-nav-btn" @click="scrollCatRow(-1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button class="cat-nav-btn" @click="scrollCatRow(1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
        <div class="categories-row" id="cat-row">
            <button class="cat-chip" :class="{ active: selectedCategory === 'All' }" @click="selectCategory('All')">
                <div class="cat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                </div>
                <span>All Items</span>
            </button>
            <button class="cat-chip" v-for="cat in categories" :key="cat" :class="{ active: selectedCategory === cat }" @click="selectCategory(cat)">
                <div class="cat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                </div>
                <span>{{ cat }}</span>
            </button>
        </div>
        <div class="section-divider"></div>
    </div>
</section>

<section class="section bestselling-section" id="bestselling-section">
    <div class="container">
        <div class="section-header">
            <div class="section-header-left">
                <div class="section-badge">This Month</div>
                <h2 class="section-title">Best <span>Selling</span> Products</h2>
            </div>
            <button class="btn-view-all" @click="selectedCategory = 'All'">View All</button>
        </div>
        <div v-if="loadingProducts" class="loader-spinner-center"><div class="loader-spinner"></div></div>
        <div v-else-if="bestSellingProducts.length === 0" class="empty-state-inline">No products found</div>
        <div v-else class="products-grid-4">
            <div class="product-card-v2" v-for="prod in bestSellingProducts" :key="'best-'+prod._id" @click="goToProduct(prod)">
                <div class="product-card-img-wrap">
                    <span class="sale-badge" v-if="prod.discount_percent">-{{ prod.discount_percent }}%</span>
                    <span class="new-badge" v-else-if="prod.is_new">NEW</span>
                    <img :src="prod.image_url || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'" :alt="prod.name" class="product-card-img">
                    <div class="product-card-actions-overlay">
                        <button class="overlay-action-btn" @click.stop="addToCart(prod)" title="Add to Cart">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="product-card-info">
                    <div class="product-card-cat" v-if="prod.category">{{ prod.category }}</div>
                    <div class="product-card-name">{{ prod.name }}</div>
                    <div class="product-card-price-row">
                        <span class="product-card-price">{{ formatIDR(prod.price) }}</span>
                        <span class="product-card-old-price" v-if="prod.original_price && prod.original_price > prod.price">{{ formatIDR(prod.original_price) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="promo-banner-section" v-if="promoSlides.length > 0">
    <div class="container">
        <div class="promo-banner-card" v-for="(slide, idx) in promoSlides" :key="idx" v-show="activePromoIdx === idx">
            <div class="promo-banner-content">
                <div class="promo-banner-category">{{ slide.badge || 'Premium Store' }}</div>
                <h2 class="promo-banner-title" v-html="slide.title || 'Enhance Your<br>Shopping Experience'"></h2>
                <div class="promo-banner-dots" v-if="promoSlides.length > 1">
                    <span v-for="(s, sIdx) in promoSlides" :key="sIdx" class="promo-dot" :class="{ active: activePromoIdx === sIdx }" @click="activePromoIdx = sIdx"></span>
                </div>
                <button class="btn-promo-shop" @click="slide.product_id ? goToProductById(slide.product_id) : scrollToProducts()">
                    {{ slide.btn_text || 'Buy Now!' }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </div>
            <div class="promo-banner-img">
                <img :src="slide.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80'" alt="Featured Product">
            </div>
        </div>
    </div>
</section>

<section class="section explore-section">
    <div class="container">
        <div class="section-header">
            <div class="section-header-left">
                <div class="section-badge">Our Products</div>
                <h2 class="section-title">Explore <span>Our Products</span></h2>
            </div>
            <div class="explore-nav-btns">
                <button class="cat-nav-btn" @click="explorePage = Math.max(0, explorePage - 1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button class="cat-nav-btn" @click="explorePage++">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>

        
        <div class="explore-toolbar">
            <div class="explore-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" v-model="searchQuery" placeholder="Search products..." @input="fetchProducts" class="explore-search-input">
            </div>
            <div class="explore-cat-pills">
                <button class="cat-pill" :class="{ active: selectedCategory === 'All' }" @click="selectCategory('All')">All</button>
                <button class="cat-pill" v-for="cat in categories" :key="'ep-'+cat" :class="{ active: selectedCategory === cat }" @click="selectCategory(cat)">{{ cat }}</button>
            </div>
        </div>

        <div v-if="loadingProducts" class="loader-spinner-center"><div class="loader-spinner"></div></div>
        <div v-else-if="filteredProducts.length === 0" class="empty-state-inline">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <p>No products found. Try a different search or category.</p>
        </div>
        <div v-else class="products-grid-4">
            <div class="product-card-v2" v-for="prod in pagedProducts" :key="'exp-'+prod._id" @click="goToProduct(prod)">
                <div class="product-card-img-wrap">
                    <span class="sale-badge" v-if="prod.discount_percent">-{{ prod.discount_percent }}%</span>
                    <span class="new-badge" v-else-if="prod.is_new">NEW</span>
                    <img :src="prod.image_url || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'" :alt="prod.name" class="product-card-img">
                    <div class="product-card-actions-overlay">
                        <button class="overlay-action-btn" @click.stop="addToCart(prod)" title="Add to Cart">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        </button>
                    </div>
                    <div class="stock-out-overlay" v-if="prod.stock <= 0">
                        <span>Out of Stock</span>
                    </div>
                </div>
                <div class="product-card-info">
                    <div class="product-card-cat" v-if="prod.category">{{ prod.category }}</div>
                    <div class="product-card-name">{{ prod.name }}</div>
                    <div class="product-card-price-row">
                        <span class="product-card-price">{{ formatIDR(prod.price) }}</span>
                        <span class="product-card-old-price" v-if="prod.original_price && prod.original_price > prod.price">{{ formatIDR(prod.original_price) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="explore-pagination" v-if="totalExplorePages > 1">
            <button class="page-btn" v-for="p in totalExplorePages" :key="p" :class="{ active: explorePage === p - 1 }" @click="explorePage = p - 1">{{ p }}</button>
        </div>

        <div class="explore-cta" v-if="filteredProducts.length > 0">
            <button class="btn-load-more" @click="selectedCategory = 'All'; searchQuery = ''; fetchProducts()">
                View All Products
            </button>
        </div>
    </div>
</section>

<section class="section new-arrival-section" v-if="newArrivalProducts.length > 0">
    <div class="container">
        <div class="section-header">
            <div class="section-header-left">
                <div class="section-badge">Featured</div>
                <h2 class="section-title">New <span>Arrival</span></h2>
            </div>
        </div>
        <div class="new-arrival-grid">
            
            <div class="na-large-card" v-if="newArrivalProducts[0]" @click="goToProduct(newArrivalProducts[0])">
                <img :src="newArrivalProducts[0].image_url || 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600'" :alt="newArrivalProducts[0].name" class="na-bg-img">
                <div class="na-card-content">
                    <div class="na-card-cat">{{ newArrivalProducts[0].category || 'New' }}</div>
                    <div class="na-card-name">{{ newArrivalProducts[0].name }}</div>
                </div>
            </div>
            
            <div class="na-right-col">
                
                <div class="na-top-card" v-if="newArrivalProducts[1]" @click="goToProduct(newArrivalProducts[1])">
                    <img :src="newArrivalProducts[1].image_url || 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=600'" :alt="newArrivalProducts[1].name" class="na-bg-img">
                    <div class="na-card-content">
                        <div class="na-card-cat">{{ newArrivalProducts[1].category || 'New' }}</div>
                        <div class="na-card-name">{{ newArrivalProducts[1].name }}</div>
                    </div>
                </div>
                
                <div class="na-bottom-row">
                    <div class="na-small-card" v-if="newArrivalProducts[2]" @click="goToProduct(newArrivalProducts[2])">
                        <img :src="newArrivalProducts[2].image_url || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'" :alt="newArrivalProducts[2].name" class="na-bg-img">
                        <div class="na-card-content">
                            <div class="na-card-cat">{{ newArrivalProducts[2].category || 'New' }}</div>
                            <div class="na-card-name">{{ newArrivalProducts[2].name }}</div>
                        </div>
                    </div>
                    <div class="na-small-card" v-if="newArrivalProducts[3]" @click="goToProduct(newArrivalProducts[3])">
                        <img :src="newArrivalProducts[3].image_url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'" :alt="newArrivalProducts[3].name" class="na-bg-img">
                        <div class="na-card-content">
                            <div class="na-card-cat">{{ newArrivalProducts[3].category || 'New' }}</div>
                            <div class="na-card-name">{{ newArrivalProducts[3].name }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="trust-section">
    <div class="container">
        <div class="trust-grid">
            <div class="trust-card">
                <div class="trust-icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                </div>
                <div class="trust-info">
                    <div class="trust-title">Free & Fast Delivery</div>
                    <div class="trust-desc">Free delivery for all orders over IDR 500.000</div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 12 19.79 19.79 0 0 1 1.07 3.36 2 2 0 0 1 3.04 2H6a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.09 9.91A16 16 0 0 0 13 15.82l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
                <div class="trust-info">
                    <div class="trust-title">24/7 Customer Service</div>
                    <div class="trust-desc">Friendly 24/7 customer support via chat</div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                </div>
                <div class="trust-info">
                    <div class="trust-title">Money Back Guarantee</div>
                    <div class="trust-desc">We return money within 30 days</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->render('store:views/shop/footer.php') ?>
