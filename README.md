# Cockpit CMS - Storefront & E-Commerce Addon

This addon implements a fully featured storefront and headless e-commerce content management system built on top of the Lime Framework and Cockpit. It provides a premium, high-performance dark-themed shopping experience, dynamic settings panel, and customer tracking system.

## 🚀 Key Features

### 1. Storefront CMS & Content Customization

- **Hero Banner Settings**: Set badge, dynamic title, description, buttons, background image, and stats cards.
- **Sub-hero Feature Promo Banners**: Manage a multi-slide promo carousel slideshow. Each slide can configure custom text, small tags, a custom featured image, and link directly to a specific product detail page. Supports automatic sliding every 5 seconds.
- **Flash Sales (Today's Deals)**: Enable flash sales using a dynamic date-time countdown timer.
    - **Product Link Picker**: A searchable and paginated modal dialog to bulk-link catalog products to the flash sale horizontal scroll deck.
    - **Duration-based Visibility**: If the end date-time is empty or expired, the entire flash sale block hides automatically from the home page.

### 2. Marketing & Promotions (Vouchers)

- **Voucher Management**: Create code promotions with discount types (percentage / fixed amount), thresholds, and validity dates.
- **Promo Topbar Banner**: Checkbox options to highlight a specific voucher code in a prominent top-bar banner across the shop. The backend enforces a uniqueness validation ensuring **at most 1 active voucher** can claim topbar real estate.

### 3. Split-screen Auth & Profile Dashboard

- **Profile Navigation**: Replaced text link in header navbar with a profile user icon next to the shopping cart.
- **mockup Split-Screen Login/Register**: The customer portal uses a split-screen design. Form modules (Login, Sign-Up, Reset Password) occupy the left panel, while a concentric circle grid wireframe graphic with an image placeholder occupies the right.
- **Order History & Webhook Action Simulation**: Logged-in customers can view past transactions, click "Pay Now" to mock Midtrans Snap callback successes, or click "Track" to check shipping updates.

### 4. Direct Multi-stage Timeline Order Tracker

- **Real-time Tracker**: Accessible via `/tracker` (or `/shop/tracker`). Displays courier details, airway bills (resi), and ordered items.
- **Non-overflowing Scale Progress Bar**: Rendered via hardware-accelerated CSS `transform: scaleX(...)` ensuring the active indicator stops exactly at the center of the current tracking step (Order Placed, Processing, Shipped, Delivered) without spilling past boundaries.
- **Auto-run URL Query Parsing**: Clicking "Track" in the dashboard redirect-routes the customer with query parameters (`?order_id=...&email=...`), triggering an immediate automatic status fetch upon load.

### 5. Dedicated Pages (About Us, FAQs, Security & Policies)

- **Separate Pages Routing**: Removed modal pop-ups for information blocks. Content created in CMS is served on dedicated, indexable routes `/about`, `/faq`, and `/security` (with fallback paths like `/shop/about`).
- **Header & Footer Menu Integration**: Links populate dynamically in the header navigation list (About Us & FAQs) and footer Quick Links (Security & Policy) only when content is provided in the admin workspace.

---

## 📁 Directory Structure

```
Store/
├── bootstrap.php         # Entrypoint, storefront URL routes binding & filters
├── admin.php             # Admin backend MVC UI routes registration
├── api.php               # Public REST/GraphQL API endpoints
├── Controller/
│   ├── Store.php         # Admin MVC controller (vouchers validation, CMS save)
│   └── Shop.php          # Storefront controller (index, product, tracker, auth)
└── views/
    ├── settings.php      # Main store catalog config options UI
    ├── promotions.php    # Vouchers and discounts database editor
    ├── content.php       # Homepage banners, picker modal & duration controls
    └── shop/
        ├── header.php    # Shared public storefront header & nav logo
        ├── footer.php    # Shared footer links & Midtrans checkout Snap modal
        ├── index.php     # Homepage layout (hero banner, categories, flash rows)
        ├── product.php   # Product detail page & absolute path voucher validators
        ├── tracker.php   # Live resi shipping timeline tracker view
        ├── dashboard.php # Split-screen login & profile order history view
        ├── about.php     # Dedicated About Us page
        ├── faq.php       # Dedicated FAQs page
        └── security.php  # Dedicated Security & Policy page
```

---

## ⚙️ Global Configuration (`config/config.php`)

You can configure the storefront behaviour globally within Cockpit's main config file `/config/config.php`:

```php
return [
    // ... other config options

    'storeFront' => [
        'enableFrontend' => true, // Set to true to bind the storefront directly to domain root '/' and activate clean SEO URLs
    ],
];
```

- **`enableFrontend = true` (Default Recommended)**: Activates clean, user-friendly paths directly at root domain (e.g. `/`, `/tracker`, `/dashboard`, `/about`, `/faq`, `/security`).
- **`enableFrontend = false` (or missing)**: Storefront functions fallback into subpaths starting with `/shop/*` (e.g. `/shop`, `/shop/tracker`, `/shop/dashboard`, `/shop/about`). This is ideal if you host standard admin panels on the root `/`.

---

## 💾 Database Collections

The addon operates on Cockpit's unified storage schema:

- `store/products`: Holds catalog entries, SKU, price, original_price, rating, and stock counts.
- `store/vouchers`: Holds promotional codes, expiration rules, and topbar display states.
- `store/orders`: Logs completed checkouts, shipping timeline, tracking resi, and payment status.
- `store/settings`: Global key-value configs (Midtrans API keys, shop address, favicon, tax_percent).
- `store/content`: Stores homepage customized widgets (sliders, badges, static page layouts).

---

## 🧭 Routing Reference

When `storeFront.enableFrontend` is turned on, the following root bindings take effect:

- `/` -> Homepage
- `/product/:id` -> Single Product details
- `/tracker` -> Airway bill resi timeline tracker
- `/dashboard` -> Split-screen login & orders history
- `/about` -> Store details
- `/faq` -> Frequently Asked Questions
- `/security` -> Security and Policies

When `enableFrontend` is turned off, views fallback to standard subpaths prefixing with `/shop/*`.
