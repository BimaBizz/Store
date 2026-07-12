<?= $this->render('store:views/shop/header.php') ?>

<div class="container" style="padding-top: 4rem; padding-bottom: 5rem; min-height: 60vh;">
    <div style="max-width: 800px; margin: 0 auto; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 2.5rem; box-shadow: var(--shadow-lg);">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--text-primary); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            FAQs
        </h1>
        <div class="wysiwyg-content" v-html="homepageContent.faq || 'Loading content...'" style="color: var(--text-secondary); line-height: 1.8; font-size: 1rem;">
        </div>
    </div>
</div>

<?= $this->render('store:views/shop/footer.php') ?>
