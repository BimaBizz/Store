<?= $this->render('store:views/shop/header.php') ?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 2.5rem;">

                <div class="order-search-card" style="width: 600px; margin: 0 auto;" v-if="!activeTrackedOrder">
                    <h2 class="hero-title" style="font-size: 1.8rem; text-align: center; margin-bottom: 0.5rem;">Track Shipment</h2>
                    <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.9rem;">
                        Enter your registered email and Order ID (e.g. ORD-XXXXXX) to view checkout details and live shipping timeline.
                    </p>
                    <form @submit.prevent="trackOrder">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" v-model="trackEmail" required placeholder="name@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Order ID</label>
                            <input type="text" class="form-control" v-model="trackOrderId" required placeholder="ORD-XXXXXX">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" :disabled="loadingTrack">
                            <span v-if="loadingTrack">Searching...</span>
                            <span v-else>Find Order Receipt</span>
                        </button>
                    </form>
                </div>

                <div class="order-result-card" v-else>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <button class="btn btn-outline btn-ghost" @click="activeTrackedOrder = null" style="padding-left: 0; margin-bottom: 1rem;">
                                &larr; Track another order
                            </button>
                            <h2 style="font-family: var(--font-title); font-size: 1.8rem; font-weight: 800;">Order: {{ activeTrackedOrder.order_id }}</h2>
                            <p style="color: var(--text-muted); font-size: 0.85rem;">Date: {{ formatDate(activeTrackedOrder.created) }}</p>
                        </div>
                        <div style="text-align: right;">
                            <span class="stock-indicator available" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;" :class="getOrderStatusClass(activeTrackedOrder.status)">
                                Status: {{ activeTrackedOrder.status }}
                            </span>
                            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem;">
                                Payment: <span :style="{ color: activeTrackedOrder.payment_status === 'settled' ? 'var(--accent-green)' : 'var(--accent-amber)' }">{{ activeTrackedOrder.payment_status }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="timeline">
                        <div class="timeline-progress" :style="{ transform: 'scaleX(' + getTimelineProgressScale(activeTrackedOrder.status) + ')' }"></div>
                        
                        <div class="timeline-step" :class="getTimelineStepClass(activeTrackedOrder.status, 'pending')">
                            <div class="timeline-icon">1</div>
                            <div class="timeline-label">Order Placed</div>
                        </div>
                        <div class="timeline-step" :class="getTimelineStepClass(activeTrackedOrder.status, 'processing')">
                            <div class="timeline-icon">2</div>
                            <div class="timeline-label">Processing</div>
                        </div>
                        <div class="timeline-step" :class="getTimelineStepClass(activeTrackedOrder.status, 'shipping')">
                            <div class="timeline-icon">3</div>
                            <div class="timeline-label">Shipped</div>
                        </div>
                        <div class="timeline-step" :class="getTimelineStepClass(activeTrackedOrder.status, 'completed')">
                            <div class="timeline-icon">4</div>
                            <div class="timeline-label">Delivered</div>
                        </div>
                    </div>

                    <div class="invoice-grid">
                        <div>
                            <div class="invoice-details-block" style="margin-bottom: 1.5rem;">
                                <h3 class="invoice-section-title">Items Ordered</h3>
                                <div class="invoice-item-row" v-for="item in activeTrackedOrder.items" :key="item.product_id">
                                    <div>
                                        <div style="font-weight: 600;">{{ item.name }}</div>
                                        <div style="color: var(--text-muted); font-size: 0.75rem;">Qty: {{ item.quantity }} &times; {{ formatIDR(item.price) }}</div>
                                    </div>
                                    <div style="font-weight: 700; color: var(--text-primary);">{{ formatIDR(item.price * item.quantity) }}</div>
                                </div>
                            </div>

                            <div class="invoice-details-block">
                                <h3 class="invoice-section-title">Shipping & Logistics Information</h3>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; font-size: 0.875rem;">
                                    <div>
                                        <p style="color: var(--text-muted);">Courier:</p>
                                        <p style="font-weight: 600; color: var(--text-primary);">{{ activeTrackedOrder.courier }}</p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted);">Airway Bill (No Resi):</p>
                                        <p style="font-weight: 700; color: var(--accent-site);" v-if="activeTrackedOrder.resi">{{ activeTrackedOrder.resi }}</p>
                                        <p style="font-style: italic; color: var(--text-muted);" v-else>Not yet available</p>
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <p style="color: var(--text-muted);">Address:</p>
                                        <p style="color: var(--text-primary);">
                                            {{ activeTrackedOrder.customer_name }}<br>
                                            {{ activeTrackedOrder.customer_address }}, {{ activeTrackedOrder.customer_city }} {{ activeTrackedOrder.customer_zip }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="invoice-details-block" style="background-color: var(--bg-secondary);">
                                <h3 class="invoice-section-title">Billing Summary</h3>
                                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: var(--text-secondary);">Subtotal:</span>
                                        <span>{{ formatIDR(activeTrackedOrder.subtotal_amount) }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;" v-if="activeTrackedOrder.discount_amount > 0" class="discount">
                                        <span style="color: var(--accent-rose);">Voucher Discount ({{ activeTrackedOrder.voucher_code }}):</span>
                                        <span>-{{ formatIDR(activeTrackedOrder.discount_amount) }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: var(--text-secondary);">Tax / PPN ({{ settings.tax_percent || 11 }}%):</span>
                                        <span>{{ formatIDR(activeTrackedOrder.tax_amount) }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: var(--text-secondary);">Courier cost:</span>
                                        <span>{{ formatIDR(activeTrackedOrder.shipping_cost) }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 0.25rem;">
                                        <span>Grand Total:</span>
                                        <span style="color: var(--accent-green);">{{ formatIDR(activeTrackedOrder.total_amount) }}</span>
                                    </div>
                                    <div v-if="activeTrackedOrder.payment_status === 'pending'" style="margin-top: 1.5rem;">
                                        <button class="btn btn-primary" style="width: 100%;" @click="payPendingOrder(activeTrackedOrder)">
                                            Pay Now (Midtrans SNAP)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

</div>
<?= $this->render('store:views/shop/footer.php') ?>
