<?=$this->render('store:views/shop/header.php')?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 2.5rem;">

            
                
                
                <div v-if="!customerSession.logged_in" class="auth-split-layout">
                    
                    <div class="auth-form-panel">
                        <div class="auth-form-container">
                            
                            
                            <div v-if="activeAuthForm === 'login'">
                                <h2>Login to your account</h2>
                                <p class="subtitle">Enter your email below to login to your account</p>
                                <form @submit.prevent="submitLogin">
                                    <div class="form-group" style="margin-bottom: 1.25rem;">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" v-model="authForm.email" placeholder="m@example.com" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 1.25rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                            <label class="form-label" style="margin-bottom: 0;">Password</label>
                                            <a href="#" style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none;" @click.prevent="activeAuthForm = 'forgot'">Forgot your password?</a>
                                        </div>
                                        <input type="password" class="form-control" v-model="authForm.password" required>
                                    </div>
                                    <button type="submit" class="btn-auth-submit" :disabled="submittingAuth">
                                        {{ submittingAuth ? 'Logging In...' : 'Login' }}
                                    </button>
                                </form>
                                                                
                                <p style="margin-top: 1.5rem; text-align: center; font-size: 0.88rem; color: var(--text-muted);">
                                    Don't have an account? <a href="#" style="color: var(--text-primary); text-decoration: underline;" @click.prevent="activeAuthForm = 'register'">Sign up</a>
                                </p>
                            </div>

                            
                            <div v-if="activeAuthForm === 'register'">
                                <h2>Create an account</h2>
                                <p class="subtitle">Enter your details below to sign up</p>
                                <form @submit.prevent="submitRegister">
                                    <div class="form-group" style="margin-bottom: 0.85rem;">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" v-model="authForm.name" placeholder="John Doe" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0.85rem;">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" v-model="authForm.email" placeholder="you@example.com" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0.85rem;">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" v-model="authForm.password" placeholder="At least 6 characters" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0.85rem;">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" v-model="authForm.phone" placeholder="+62812345678">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0.85rem;">
                                        <label class="form-label">Street Address</label>
                                        <textarea class="form-control" v-model="authForm.address" placeholder="Jl. Raya No. 12" style="height: 60px; resize: none;"></textarea>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.85rem;">
                                        <div class="form-group">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" v-model="authForm.city" placeholder="Jakarta">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Zip Code</label>
                                            <input type="text" class="form-control" v-model="authForm.zip" placeholder="12190">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-auth-submit" :disabled="submittingAuth">
                                        {{ submittingAuth ? 'Creating Account...' : 'Register' }}
                                    </button>
                                </form>
                                <p style="margin-top: 1.5rem; text-align: center; font-size: 0.88rem; color: var(--text-muted);">
                                    Already have an account? <a href="#" style="color: var(--text-primary); text-decoration: underline;" @click.prevent="activeAuthForm = 'login'">Login</a>
                                </p>
                            </div>

                            
                            <div v-if="activeAuthForm === 'forgot'">
                                <h2>Reset Password</h2>
                                <p class="subtitle">Enter your email address to receive reset link</p>
                                <form @submit.prevent="submitForgotPassword">
                                    <div class="form-group" style="margin-bottom: 1.25rem;">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" v-model="authForm.email" placeholder="you@example.com" required>
                                    </div>
                                    <button type="submit" class="btn-auth-submit" :disabled="submittingAuth">
                                        {{ submittingAuth ? 'Sending Link...' : 'Send Reset Link' }}
                                    </button>
                                    <button type="button" class="btn btn-outline" style="width: 100%; margin-top: 0.75rem;" @click="activeAuthForm = 'login'">
                                        Back to Login
                                    </button>
                                </form>
                            </div>

                            
                            <div v-if="activeAuthForm === 'reset'">
                                <h2>Set New Password</h2>
                                <p class="subtitle">Set a secure password for account <code>{{ urlParams.email }}</code></p>
                                <form @submit.prevent="submitResetPassword">
                                    <div class="form-group" style="margin-bottom: 1.25rem;">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" v-model="authForm.password" placeholder="At least 6 characters" required>
                                    </div>
                                    <button type="submit" class="btn-auth-submit" :disabled="submittingAuth">
                                        {{ submittingAuth ? 'Resetting Password...' : 'Save Password' }}
                                    </button>
                                </form>
                            </div>
                            
                        </div>
                    </div>
                    
                    
                    <div class="auth-visual-panel">
                        <div class="auth-visual-inner">
                            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="100" cy="100" r="80" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                                <circle cx="100" cy="100" r="60" stroke="rgba(255,255,255,0.05)" stroke-width="1" />
                                <circle cx="100" cy="100" r="40" stroke="rgba(255,255,255,0.07)" stroke-width="1" />
                                <circle cx="100" cy="100" r="20" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
                                <line x1="100" y1="20" x2="100" y2="180" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                                <line x1="20" y1="100" x2="180" y2="100" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                                <line x1="43.43" y1="43.43" x2="156.57" y2="156.57" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                                <line x1="156.57" y1="43.43" x2="43.43" y2="156.57" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                                <g opacity="0.35" transform="translate(88,88)">
                                    <rect x="0" y="0" width="24" height="24" rx="4" stroke="white" stroke-width="1.5" fill="none"/>
                                    <circle cx="7" cy="7" r="1.5" fill="white"/>
                                    <path d="M2 19 L8 12 L13 17 L19 9 L22 12" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>

                
                <div v-else>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="font-family: var(--font-title); font-size: 1.8rem; margin: 0;">Welcome, {{ customerSession.customer.name }}</h2>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0.25rem 0 0 0;"><code>{{ customerSession.customer.email }}</code></p>
                        </div>
                        <button type="button" class="btn btn-outline" @click="submitLogout">Logout</button>
                    </div>

                    <div style="display: grid; grid-template-columns: auto auto; gap: 2rem; align-items: start;" class="dashboard-grid">
                        
                        
                        <div class="order-search-card" style="margin: 0;">
                            <h3 style="font-family: var(--font-title); font-size: 1.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Account Profile</h3>
                            <form @submit.prevent="submitUpdateProfile">
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" v-model="profileForm.name" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label class="form-label">Email Address (Read-only)</label>
                                    <input type="email" class="form-control" :value="customerSession.customer.email" readonly>
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" v-model="profileForm.phone">
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label class="form-label">Street Address</label>
                                    <textarea class="form-control" v-model="profileForm.address" style="height: 60px; resize: none;"></textarea>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" v-model="profileForm.city">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Zip Code</label>
                                        <input type="text" class="form-control" v-model="profileForm.zip">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label class="form-label">Change Password (Leave blank to keep current)</label>
                                    <input type="password" class="form-control" v-model="profileForm.password" placeholder="Enter new password">
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;" :disabled="updatingProfile">
                                    {{ updatingProfile ? 'Saving Changes...' : 'Save Profile Changes' }}
                                </button>
                            </form>
                        </div>

                        
                        <div class="order-search-card" style="margin: 0;">
                            <h3 style="font-family: var(--font-title); font-size: 1.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Order History</h3>
                            <div v-if="customerSession.orders && customerSession.orders.length > 0">
                                <div v-for="order in customerSession.orders" :key="order._id" style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1rem; background: rgba(255,255,255,0.01);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                                        <div>
                                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Order ID:</span>
                                            <code style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin-left: 0.25rem;">{{ order.order_id }}</code>
                                        </div>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">{{ formatDate(order.created) }}</span>
                                    </div>
                                    
                                    <div style="margin-bottom: 1rem;">
                                        <div v-for="item in order.items" :key="item.product_id" style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem; color: var(--text-secondary);">
                                            <span>- {{ item.name }} (x{{ item.quantity }})</span>
                                            <span>{{ formatIDR(item.price * item.quantity) }}</span>
                                        </div>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.03); padding-top: 0.75rem;">
                                        <div>
                                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Grand Total: </span>
                                            <strong style="color: var(--accent-green);">{{ formatIDR(order.total_amount) }}</strong>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <span class="stock-indicator" :class="order.payment_status === 'settled' ? 'available' : 'low-stock'" style="font-size: 0.75rem;">
                                                Payment: {{ order.payment_status }}
                                            </span>
                                            <button type="button" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" @click="quickTrackOrder(order)">Track</button>
                                            <button v-if="order.payment_status === 'pending'" type="button" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" @click="payPendingOrder(order)">Pay Now</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else style="text-align: center; padding: 3rem 1rem; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-md);">
                                No orders placed yet. Add items to your cart and place an order to see your purchase history!
                            </div>
                        </div>

                    </div>
                </div>

</div>
<?=$this->render('store:views/shop/footer.php')?>
